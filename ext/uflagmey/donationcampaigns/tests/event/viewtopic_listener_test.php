<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\event\viewtopic_listener;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\description_formatter;
use uflagmey\donationcampaigns\service\currency_formatter;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;

/**
 * The public campaign box.
 *
 * The listener coordinates only: it asks campaign_service for the campaign,
 * formats what it gets, and assigns template variables. These tests assert on
 * the variables assigned, because that is the listener's whole observable
 * contract — and, critically, on the variables NOT assigned.
 */
class viewtopic_listener_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var viewtopic_listener */
	protected $listener;

	/** @var recording_template */
	protected $template;

	/** @var campaign_service */
	protected $service;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var string */
	protected $db_file;

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		global $phpbb_root_path, $phpEx;

		$this->set_phpbb_globals();

		$this->db_file = sys_get_temp_dir() . '/ufdc_viewtopic_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$migration = new m1_initial_schema(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			'',
			'php',
			'phpbb_'
		);
		$this->tools->perform_schema_changes($migration->update_schema());

		// The campaign button's label arrived in m6. Fixtures build the schema
		// from the migrations rather than a hand-written copy, so they have to
		// walk the same chain a real board does.
		$link_text = new \uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			'',
			'php',
			'phpbb_'
		);
		$this->tools->perform_schema_changes($link_text->update_schema());

		$this->seed();

		$this->campaigns = new campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$this->donations = new donation_repository($this->db, 'phpbb_ufdc_donations');

		$this->config = new \phpbb\config\config(array(
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_donor_list_limit'	=> 25,
		));

		$this->template = new recording_template();

		$service = $this->service = new campaign_service(
			$this->db,
			$this->campaigns,
			$this->donations,
			new topic_repository($this->db, 'phpbb_topics'),
			new description_formatter()
		);

		$language = $this->language();

		global $user;

		// No permissions by default: the pre-existing tests are about the
		// public box, and an administrator's link must not leak into them.
		// authorise() rebuilds the listener when a test needs one.
		$this->listener = new viewtopic_listener(
			$service,
			new currency_formatter($language),
			$this->config,
			$this->template,
			$language,
			new selective_auth(array()),
			$user,
			'adm/',
			'php'
		);
	}

	/**
	 * A real language service, resolving our real English file through a real
	 * extension manager. The plural forms and the "and N others" wording are
	 * part of what this listener produces, so stubbing lang() would test
	 * nothing.
	 *
	 * @return \phpbb\language\language
	 */
	protected function language()
	{
		global $phpbb_root_path, $phpEx;

		$loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$loader->set_extension_manager(new \phpbb_mock_extension_manager($phpbb_root_path, array(
			'uflagmey/donationcampaigns' => array(
				'ext_name'		=> 'uflagmey/donationcampaigns',
				'ext_active'	=> true,
				'ext_path'		=> 'ext/uflagmey/donationcampaigns/',
			),
		)));

		return new \phpbb\language\language($loader);
	}

	/**
	 * generate_text_for_display() reaches for $user, $config, $auth, $cache and
	 * $phpbb_dispatcher as globals. That coupling is core's, not ours; the
	 * listener simply calls the documented function. Accepted and recorded as a
	 * harness limitation in STATUS section 7.
	 *
	 * @return void
	 */
	protected function set_phpbb_globals()
	{
		global $user, $auth, $cache, $phpbb_dispatcher;

		$user = new \phpbb_mock_user();
		$user->optionset('viewcensors', true);

		$auth = new \phpbb\auth\auth();
		$cache = new \phpbb_mock_cache();
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		$GLOBALS['config'] = new \phpbb\config\config(array(
			'allow_nocensors'	=> false,
		));
	}

	public function tearDown(): void
	{
		if ($this->db)
		{
			$this->db->sql_close();
		}

		if ($this->db_file && file_exists($this->db_file))
		{
			unlink($this->db_file);
		}

		parent::tearDown();
	}

	/**
	 * Topic 10 carries an enabled campaign with three donations (two public)
	 * summing to 2500 against a target of 10000. Topic 20 carries a disabled
	 * one. Topic 30 carries none.
	 */
	protected function seed()
	{
		$campaigns = array(
			array(
				'campaign_id' => 1, 'topic_id' => 10, 'campaign_title' => 'Server fund',
				'campaign_desc' => 'Help us replace the server.',
				'target_amount' => 10000, 'collected_amount' => 2500,
				'campaign_enabled' => 1, 'show_donor_names' => 1, 'show_donation_count' => 1,
				'external_url' => 'https://example.org/donate',
			),
			array(
				'campaign_id' => 2, 'topic_id' => 20, 'campaign_title' => 'Disabled campaign',
				'campaign_desc' => '', 'target_amount' => 5000, 'collected_amount' => 0,
				'campaign_enabled' => 0, 'show_donor_names' => 1, 'show_donation_count' => 1,
				'external_url' => '',
			),
		);

		foreach ($campaigns as $campaign)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'desc_bbcode_uid'		=> '',
				'desc_bbcode_bitfield'	=> '',
				'desc_bbcode_options'	=> 7,
				'campaign_created'		=> 1700000000,
				'campaign_updated'		=> 1700000000,
			), $campaign)));
		}

		$donations = array(
			array('donation_amount' => 1000, 'donor_name' => 'Anna M.', 'donation_time' => 1700000100, 'donation_public' => 1),
			array('donation_amount' => 1200, 'donor_name' => 'Bernd K.', 'donation_time' => 1700000200, 'donation_public' => 0),
			array('donation_amount' => 300, 'donor_name' => '', 'donation_time' => 1700000300, 'donation_public' => 1),
		);

		foreach ($donations as $donation)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_id'		=> 1,
				'donation_created'	=> 1700000000,
				'donation_updated'	=> 1700000000,
			), $donation)));
		}
	}

	/**
	 * Fire the listener as core does, with a topic_id in the event data.
	 *
	 * @param int $topic_id
	 * @return void
	 */
	protected function view($topic_id)
	{
		$this->listener->assign_campaign_vars(
			new \phpbb\event\data(array('topic_id' => $topic_id))
		);
	}

	/**
	 * @param int $campaign_id
	 * @param array $columns
	 * @return void
	 */
	protected function set_campaign($campaign_id, array $columns)
	{
		$this->db->sql_query('UPDATE phpbb_ufdc_campaigns SET '
			. $this->db->sql_build_array('UPDATE', $columns)
			. ' WHERE campaign_id = ' . (int) $campaign_id);
	}

	// ------------------------------------------------------------ visibility

	public function test_the_box_is_shown_on_the_topic_that_owns_the_campaign()
	{
		$this->view(10);

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_SHOW']);
		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	public function test_the_box_is_absent_on_another_topic()
	{
		$this->view(30);

		$this->assertSame(array(), $this->template->vars);
	}

	public function test_a_disabled_campaign_is_not_shown_publicly()
	{
		$this->view(20);

		$this->assertSame(array(), $this->template->vars, 'A disabled campaign reached the template');
	}

	/**
	 * Nothing at all is assigned when there is no campaign — not even a false
	 * flag. Every topic view on a board runs this listener, so the cheap path
	 * has to stay cheap.
	 */
	public function test_no_campaign_assigns_nothing_and_raises_nothing()
	{
		$this->view(99999);

		$this->assertSame(array(), $this->template->vars);
		$this->assertSame(array(), $this->template->blocks);
	}

	// ------------------------------------------------------------- formatting

	public function test_the_amounts_are_formatted_for_display()
	{
		$this->view(10);

		$this->assertSame('25.00 €', $this->template->vars['DONATIONCAMPAIGNS_COLLECTED']);
		$this->assertSame('100.00 €', $this->template->vars['DONATIONCAMPAIGNS_TARGET']);
	}

	public function test_the_currency_settings_are_honoured()
	{
		$this->config->set('donationcampaigns_currency_exponent', 0);
		$this->config->set('donationcampaigns_currency_symbol', 'JPY');

		$this->view(10);

		// Exponent 0 keeps the amount whole, and public output is grouped:
		// the box is display, not an editable field.
		$this->assertSame('2,500 JPY', $this->template->vars['DONATIONCAMPAIGNS_COLLECTED']);
	}

	/**
	 * Stored values are integer minor units; only the display strings are
	 * formatted. The two must never be confused.
	 */
	public function test_formatting_does_not_alter_the_stored_values()
	{
		$this->view(10);

		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	// --------------------------------------------------------------- progress

	public function progress_data()
	{
		return array(
			//        collected, target, percent, capped, step
			'zero'			=> array(0, 10000, 0, 0),
			'below target'	=> array(2500, 10000, 25, 25),
			'rounds down'	=> array(2599, 10000, 25, 25),
			'at target'		=> array(10000, 10000, 100, 100),
			'above target'	=> array(15000, 10000, 150, 100),
			'far above'		=> array(1000000, 10000, 10000, 100),
			'tiny'			=> array(1, 10000, 0, 0),
		);
	}

	/**
	 * @dataProvider progress_data
	 */
	public function test_progress_is_calculated_with_integer_arithmetic($collected, $target, $percent, $step)
	{
		$this->set_campaign(1, array('collected_amount' => $collected, 'target_amount' => $target));

		$this->view(10);

		$this->assertSame($percent, $this->template->vars['DONATIONCAMPAIGNS_PERCENT_RAW'], 'The real percentage must be truthful');

		// bar_step() is now the only place the 100 cap lives.
		$this->assertSame($step, $this->template->vars['DONATIONCAMPAIGNS_PERCENT_STEP']);
		$this->assertLessThanOrEqual(100, $this->template->vars['DONATIONCAMPAIGNS_PERCENT_STEP'], 'The bar exceeded a full width');

		$this->assertIsInt($this->template->vars['DONATIONCAMPAIGNS_PERCENT_STEP']);
		$this->assertIsInt($this->template->vars['DONATIONCAMPAIGNS_PERCENT_RAW']);
	}

	/**
	 * The bar width is chosen by a CSS class, so the value must be a multiple
	 * of five for a class to exist. ADR-013 forbids inline styles.
	 */
	public function test_the_progress_step_is_always_a_multiple_of_five()
	{
		for ($collected = 0; $collected <= 10000; $collected += 137)
		{
			$this->set_campaign(1, array('collected_amount' => $collected, 'target_amount' => 10000));
			$this->template->vars = array();

			$this->view(10);

			$step = $this->template->vars['DONATIONCAMPAIGNS_PERCENT_STEP'];

			$this->assertSame(0, $step % 5, "Step {$step} has no stylesheet rule");
			$this->assertGreaterThanOrEqual(0, $step);
			$this->assertLessThanOrEqual(100, $step);
		}
	}

	/**
	 * Validation forbids a zero target, but a hand-edited or legacy row must
	 * not produce a division by zero on a public page.
	 */
	public function test_a_zero_target_does_not_divide_by_zero()
	{
		$this->set_campaign(1, array('target_amount' => 0, 'collected_amount' => 500));

		$this->view(10);

		$this->assertSame(0, $this->template->vars['DONATIONCAMPAIGNS_PERCENT_STEP']);
		$this->assertSame(0, $this->template->vars['DONATIONCAMPAIGNS_PERCENT_RAW']);
	}

	public function test_the_target_reached_flag_is_set_at_and_above_target()
	{
		$this->set_campaign(1, array('collected_amount' => 10000, 'target_amount' => 10000));
		$this->view(10);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_REACHED']);

		$this->template->vars = array();
		$this->set_campaign(1, array('collected_amount' => 9999));
		$this->view(10);
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_REACHED']);
	}

	// ------------------------------------------------------------ donor list

	public function test_the_donor_list_is_shown_when_enabled()
	{
		$this->view(10);

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_SHOW_DONORS']);
		$this->assertCount(2, $this->template->block('donationcampaigns_donor'));
	}

	public function test_the_donor_list_is_absent_when_disabled()
	{
		$this->set_campaign(1, array('show_donor_names' => 0));

		$this->view(10);

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_SHOW_DONORS']);
		$this->assertSame(array(), $this->template->block('donationcampaigns_donor'));
	}

	/**
	 * The privacy rule. A donor who asked not to be listed must not appear,
	 * and no row may carry an amount — an individual donation figure is not
	 * public information.
	 */
	public function test_only_public_donors_are_exposed()
	{
		$this->view(10);

		$names = array_column($this->template->block('donationcampaigns_donor'), 'NAME');

		$this->assertNotContains('Bernd K.', $names, 'A private donor was exposed');
		$this->assertContains('Anna M.', $names);
	}

	public function test_no_donor_row_carries_an_amount()
	{
		$this->view(10);

		foreach ($this->template->block('donationcampaigns_donor') as $row)
		{
			$this->assertSame(array('NAME'), array_keys($row), 'A donor row exposed more than a name');
		}
	}

	public function test_an_empty_donor_name_renders_as_anonymous()
	{
		$this->view(10);

		$names = array_column($this->template->block('donationcampaigns_donor'), 'NAME');

		$this->assertContains('Anonymous', $names);
	}

	public function test_the_donor_list_limit_is_respected()
	{
		$this->config->set('donationcampaigns_donor_list_limit', 1);

		$this->view(10);

		$this->assertCount(1, $this->template->block('donationcampaigns_donor'));
	}

	public function test_truncated_donors_are_summarised()
	{
		$this->config->set('donationcampaigns_donor_list_limit', 1);

		$this->view(10);

		$this->assertArrayHasKey('DONATIONCAMPAIGNS_AND_OTHERS', $this->template->vars);
		$this->assertStringContainsString('1', $this->template->vars['DONATIONCAMPAIGNS_AND_OTHERS']);
	}

	public function test_nothing_is_summarised_when_the_whole_list_fits()
	{
		$this->view(10);

		$this->assertArrayNotHasKey('DONATIONCAMPAIGNS_AND_OTHERS', $this->template->vars);
	}

	// ---------------------------------------------------------------- count

	public function test_the_donation_count_is_shown_when_enabled()
	{
		$this->view(10);

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_SHOW_COUNT']);
		// Three donations, including the non-public one.
		$this->assertStringContainsString('3', $this->template->vars['DONATIONCAMPAIGNS_COUNT']);
	}

	public function test_the_donation_count_is_absent_when_disabled()
	{
		$this->set_campaign(1, array('show_donation_count' => 0));

		$this->view(10);

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_SHOW_COUNT']);
		$this->assertArrayNotHasKey('DONATIONCAMPAIGNS_COUNT', $this->template->vars);
	}

	// ------------------------------------------------------------------ URL

	public function test_the_external_link_is_exposed_when_present()
	{
		$this->view(10);

		$this->assertSame('https://example.org/donate', $this->template->vars['DONATIONCAMPAIGNS_URL']);
	}

	public function test_the_external_link_is_empty_when_absent()
	{
		$this->set_campaign(1, array('external_url' => ''));

		$this->view(10);

		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_URL']);
	}

	public function unsafe_url_data()
	{
		return array(
			'javascript'	=> array('javascript:alert(1)'),
			'mixed case'	=> array('JaVaScRiPt:alert(1)'),
			'data'			=> array('data:text/html;base64,PHN2Zz4='),
			'vbscript'		=> array('vbscript:msgbox(1)'),
			'protocol rel'	=> array('//evil.example/donate'),
			'schemeless'	=> array('evil.example/donate'),
		);
	}

	/**
	 * Validation rejects these on the way in, but a row written before an
	 * upgrade, or edited directly in the database, must not become a live
	 * script link on a public page. The listener re-checks rather than trusting
	 * the column.
	 *
	 * @dataProvider unsafe_url_data
	 */
	public function test_an_unsafe_stored_url_is_never_rendered($url)
	{
		$this->set_campaign(1, array('external_url' => $url));

		$this->view(10);

		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_URL'], 'An unsafe URL reached the template');
	}

	// ------------------------------------------------------------- escaping

	/**
	 * The listener assigns RAW. Escaping is the template's job, with |e, so
	 * that it is visible to whoever reads the markup.
	 */
	public function test_a_title_is_assigned_raw_for_the_template_to_escape()
	{
		$this->set_campaign(1, array('campaign_title' => '<script>alert(1)</script>'));

		$this->view(10);

		$this->assertSame(
			'<script>alert(1)</script>',
			$this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE'],
			'The listener escaped a value the template is responsible for'
		);
	}

	public function test_a_title_breaking_out_of_an_attribute_is_escaped_when_rendered()
	{
		$this->set_campaign(1, array('campaign_title' => '" onmouseover="alert(1)'));

		$this->view(10);

		$this->assertStringNotContainsString('onmouseover="alert(1)"', $this->render_box());
	}

	public function test_a_donor_name_is_assigned_raw_for_the_template_to_escape()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_donations SET donor_name = '<img src=x onerror=alert(1)>' WHERE donation_id = 1");

		$this->view(10);

		$this->assertContains(
			'<img src=x onerror=alert(1)>',
			array_column($this->template->block('donationcampaigns_donor'), 'NAME')
		);
	}

	/**
	 * The description is the one field that may legitimately contain markup, so
	 * it goes through phpBB's own text-formatting path rather than being
	 * escaped here. Escaping it here as well would render an administrator's
	 * intended formatting as visible tags.
	 *
	 * Text stored the way phpBB stores it — escaped — survives the round trip
	 * intact and inert.
	 */
	public function test_a_stored_description_renders_through_the_phpbb_display_path()
	{
		$this->set_campaign(1, array('campaign_desc' => '&lt;script&gt;alert(1)&lt;/script&gt;'));

		$this->view(10);

		$desc = $this->template->vars['DONATIONCAMPAIGNS_DESC'];

		$this->assertStringNotContainsString('<script>', $desc);
		$this->assertStringContainsString('&lt;script&gt;', $desc);
	}

	/**
	 * PINS A phpBB BEHAVIOUR, AND A CONTRACT TASK 16 MUST HONOUR.
	 *
	 * generate_text_for_display() does NOT sanitise. It censors, parses bbcode
	 * and renders smilies, and it assumes the text was escaped on the way IN —
	 * which is how phpBB stores every piece of user text. Feed it raw markup
	 * and raw markup is what comes out.
	 *
	 * The consequence is that campaign_desc MUST be written through
	 * generate_text_for_storage() when the ACP lands in task 16. Writing a raw
	 * request value into that column is stored XSS, and no amount of care in
	 * this listener can undo it.
	 *
	 * This test asserts the hazard exists rather than pretending it does not,
	 * so the obligation is visible instead of assumed.
	 */
	public function test_the_display_path_does_not_sanitise_unescaped_storage()
	{
		$this->set_campaign(1, array('campaign_desc' => '<script>alert(1)</script>'));

		$this->view(10);

		$this->assertStringContainsString(
			'<script>',
			$this->template->vars['DONATIONCAMPAIGNS_DESC'],
			'phpBB has started escaping at display time. If so, revisit the '
			. 'storage contract for campaign_desc — it may now be double-escaping.'
		);
	}

	/**
	 * Plain text is stored raw, assigned raw, and escaped once by the template.
	 * That is the opposite contract to the description, deliberately: a title
	 * has no storage-side pipeline to escape it.
	 */
	public function test_plain_text_is_escaped_once_when_rendered()
	{
		$this->set_campaign(1, array('campaign_title' => 'Ampersand & "quotes"'));

		$this->view(10);

		$html = $this->render_box();

		$this->assertStringContainsString('Ampersand &amp; &quot;quotes&quot;', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html, 'The title was escaped twice');
	}

	// ------------------------------------------------------- internal fields

	/**
	 * The template gets display values, never storage internals. A bbcode uid
	 * or a raw row id in the page source is an information leak and an
	 * invitation for a style author to depend on it.
	 */
	public function test_no_internal_field_reaches_the_template()
	{
		$this->view(10);

		$forbidden = array(
			'campaign_id', 'topic_id', 'bbcode_uid', 'bbcode_bitfield',
			'bbcode_options', 'collected_amount', 'target_amount', 'campaign_enabled',
		);

		foreach (array_keys($this->template->vars) as $name)
		{
			foreach ($forbidden as $fragment)
			{
				$this->assertStringNotContainsStringIgnoringCase(
					$fragment,
					$name,
					"Template variable {$name} exposes an internal field"
				);
			}
		}
	}

	public function test_every_assigned_variable_carries_the_package_prefix()
	{
		$this->view(10);

		foreach (array_keys($this->template->vars) as $name)
		{
			$this->assertMatchesRegularExpression(
				'/^(S_)?DONATIONCAMPAIGNS_/',
				$name,
				"Template variable {$name} is not namespaced and may collide"
			);
		}
	}

	public function test_the_assigned_variables_are_exactly_the_documented_set()
	{
		$this->view(10);

		$expected = array(
			'S_DONATIONCAMPAIGNS_SHOW',
			'S_DONATIONCAMPAIGNS_SHOW_DONORS',
			'S_DONATIONCAMPAIGNS_SHOW_COUNT',
			'S_DONATIONCAMPAIGNS_REACHED',
			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE',
			'DONATIONCAMPAIGNS_DESC',
			'DONATIONCAMPAIGNS_TARGET',
			'DONATIONCAMPAIGNS_COLLECTED',
			'DONATIONCAMPAIGNS_PERCENT',
			'DONATIONCAMPAIGNS_PERCENT_RAW',
			'DONATIONCAMPAIGNS_PERCENT_STEP',
			'DONATIONCAMPAIGNS_URL',
			'DONATIONCAMPAIGNS_LINK_TEXT',
			'DONATIONCAMPAIGNS_COUNT',
		);

		$actual = array_keys($this->template->vars);

		sort($expected);
		sort($actual);

		$this->assertSame($expected, $actual);
	}

	// ------------------------------------------------------------- the event

	public function test_it_subscribes_to_the_verified_core_event()
	{
		$this->assertSame(
			array('core.viewtopic_assign_template_vars_before' => 'assign_campaign_vars'),
			viewtopic_listener::getSubscribedEvents()
		);
	}

	// ------------------------------------ rendered output, not just variables

	/**
	 * Render the shipped template with the values the listener produced, so
	 * the assertion is about the HTML a reader actually receives. Escaping now
	 * lives in the template, so inspecting assigned variables alone would no
	 * longer show whether output is safe.
	 *
	 * @return string
	 */
	/**
	 * THE CAP, ASSERTED ON WHAT A READER RECEIVES.
	 *
	 * A capped percentage used to be published as its own template variable.
	 * Nothing rendered it once aria-valuenow moved to the real figure, so it
	 * was removed and the invariant it carried now lives here, against the
	 * two things that actually still enforce and express it: the width class
	 * bar_step() chooses, and the markup itself.
	 *
	 * @dataProvider over_target_data
	 */
	public function test_a_campaign_over_target_renders_a_bar_capped_at_full_width($collected, $target, $percent)
	{
		$this->set_campaign(1, array('collected_amount' => $collected, 'target_amount' => $target));

		$this->view(10);
		$html = $this->render_box();

		// The bar stops at full width...
		preg_match('/donationcampaigns-bar--(\\d+)/', $html, $bar);

		$this->assertNotEmpty($bar, 'No progress bar was rendered');
		$this->assertSame(
			100,
			(int) $bar[1],
			'The bar is not capped at full width; a class beyond 100 has no stylesheet rule'
		);

		// ...the text and aria-valuetext both state the truth...
		$this->assertStringContainsString($percent . '%', $html, 'The real percentage is not displayed');
		$this->assertStringContainsString('aria-valuetext="' . $percent . '%"', $html, 'The announced value is not the displayed one');

		// ...and aria-valuenow stays inside the range ARIA declares.
		preg_match('/aria-valuenow="(\\d+)"/', $html, $now);
		$this->assertNotEmpty($now);
		$this->assertLessThanOrEqual(100, (int) $now[1], 'aria-valuenow exceeds aria-valuemax');
	}

	public function over_target_data()
	{
		return array(
			'just over'	=> array(10001, 10000, 100),
			'half again'	=> array(15000, 10000, 150),
			'far above'	=> array(1000000, 10000, 10000),
		);
	}

	/**
	 * The counterpart: at or below target, bar and text agree exactly.
	 */
	public function test_below_target_the_bar_and_the_text_agree()
	{
		$this->set_campaign(1, array('collected_amount' => 2500, 'target_amount' => 10000));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringContainsString('donationcampaigns-bar--25', $html);
		$this->assertStringContainsString('25%', $html);
		$this->assertStringContainsString('aria-valuenow="25"', $html);
		$this->assertStringContainsString('aria-valuetext="25%"', $html);
	}

	protected function render_box()
	{
		return \uflagmey\donationcampaigns\tests\template_renderer::render(
			file_get_contents(dirname(dirname(__DIR__)) . '/styles/prosilver/template/event/viewtopic_body_poll_before.html'),
			$this->template->vars,
			$this->template->blocks
		);
	}

	public function test_a_malicious_campaign_title_renders_as_text_not_markup()
	{
		$this->set_campaign(1, array('campaign_title' => '<script>alert(1)</script>'));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringNotContainsString('<script>alert(1)</script>', $html);
		$this->assertStringContainsString('&lt;script&gt;', $html);
	}

	public function test_a_malicious_donor_name_renders_as_text_not_markup()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_donations SET donor_name = '<img src=x onerror=alert(1)>' WHERE donation_id = 1");

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringNotContainsString('<img src=x', $html);
		$this->assertStringContainsString('&lt;img', $html);
	}

	/**
	 * The double-escaping case. A donor who literally types "&amp;" must see
	 * "&amp;" on screen, which means "&amp;amp;" in the source — escaped once,
	 * not twice.
	 */
	public function test_an_entity_like_donor_name_is_escaped_exactly_once()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_donations SET donor_name = '&amp; Co' WHERE donation_id = 1");

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringContainsString('&amp;amp; Co', $html, 'The name was not escaped');
		$this->assertStringNotContainsString('&amp;amp;amp;', $html, 'The name was escaped twice');
	}

	public function test_a_quote_in_a_title_cannot_break_the_progress_bar_attributes()
	{
		$this->set_campaign(1, array('campaign_title' => '" role="banner'));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringNotContainsString('role="banner"', $html);
		$this->assertSame(1, substr_count($html, 'role="progressbar"'));
	}

	/**
	 * An unsafe URL must never become an href. The service rejects these on
	 * input; this proves the rendered page is safe even for a row edited
	 * directly in the database.
	 *
	 * @dataProvider unsafe_url_data
	 */
	public function test_an_unsafe_url_never_becomes_a_link($url)
	{
		$this->set_campaign(1, array('external_url' => $url));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringNotContainsString('javascript:', strtolower($html));
		$this->assertStringNotContainsString('vbscript:', strtolower($html));
		$this->assertStringNotContainsString('data:text', strtolower($html));
	}

	public function control_character_url_data()
	{
		return array(
			'newline'		=> array("java\nscript:alert(1)"),
			'tab'			=> array("java\tscript:alert(1)"),
			'null byte'		=> array("javascript\0:alert(1)"),
			'encoded'		=> array('java%0ascript:alert(1)'),
			'leading space'	=> array('   javascript:alert(1)'),
		);
	}

	/**
	 * Control characters are the classic way past a naive scheme check.
	 *
	 * @dataProvider control_character_url_data
	 */
	public function test_a_url_with_control_characters_is_refused($url)
	{
		$this->set_campaign(1, array('external_url' => $url));

		$this->view(10);

		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_URL'], "URL '{$url}' was accepted");
	}

	/**
	 * The description is the one value rendered without escaping, because it
	 * has already been through phpBB's storage encoder. Text stored the way
	 * that encoder produces it must render inert.
	 */
	public function test_a_stored_description_renders_inert()
	{
		$this->set_campaign(1, array('campaign_desc' => '&lt;script&gt;alert(1)&lt;/script&gt;'));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringNotContainsString('<script>alert(1)</script>', $html);
	}

	// ------------------------------------------------- the button's label

	public function test_the_configured_link_text_is_what_the_button_says()
	{
		$this->set_campaign(1, array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'Über PayPal spenden',
		));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringContainsString('Über PayPal spenden', $html);
		$this->assertStringNotContainsString('>Donate<', $html, 'A fixed Donate label is still being rendered');
	}

	/**
	 * No destination, no button. There is nothing for a label to label.
	 */
	public function test_an_empty_url_renders_no_button_whatever_the_label_says()
	{
		$this->set_campaign(1, array(
			'external_url'			=> '',
			'external_link_text'	=> 'Über PayPal spenden',
		));

		$this->view(10);

		// The template gates the button on the URL. template_renderer does not
		// evaluate conditionals -- it leaves both branches in place -- so the
		// assertion is on the value the gate reads, with the gate itself
		// asserted statically in prosilver_assets_test.
		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_URL']);
	}

	/**
	 * The label is plain text the administrator typed. It has no markup
	 * pipeline, so anything markup-shaped has to arrive as literal characters.
	 *
	 * @dataProvider hostile_link_text_data
	 */
	public function test_a_hostile_link_text_renders_as_text($text, $must_appear, $must_not_appear)
	{
		$this->set_campaign(1, array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> $text,
		));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringContainsString($must_appear, $html);

		foreach ((array) $must_not_appear as $forbidden)
		{
			$this->assertStringNotContainsString($forbidden, $html, "Unescaped output: {$forbidden}");
		}
	}

	public function hostile_link_text_data()
	{
		return array(
			'script tag'	=> array('<script>alert(1)</script>', '&lt;script&gt;', '<script>'),
			'img onerror'	=> array('<img src=x onerror=alert(1)>', '&lt;img src=x', '<img'),
			'quote break'	=> array('" onmouseover="alert(1)', '&quot;', ' onmouseover="alert'),
			'ampersand'		=> array('Tea & Coffee', 'Tea &amp; Coffee', 'Tea & Coffee'),
			'entity-like'	=> array('&amp; already', '&amp;amp; already', null),
			// No BBCode pipeline on this field: the brackets stay brackets.
			'bbcode'		=> array('[b]Donate[/b]', '[b]Donate[/b]', '<b>'),
		);
	}

	/**
	 * Escaped exactly once. Double-escaping would show an administrator
	 * &amp;amp; where they typed &amp;.
	 */
	public function test_an_ampersand_is_escaped_exactly_once()
	{
		$this->set_campaign(1, array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'Coffee & Cake',
		));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringContainsString('Coffee &amp; Cake', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html);
	}

	/**
	 * A provider link is just an HTTPS URL. Nothing detects it, nothing
	 * behaves differently, no script or markup is loaded on its account.
	 */
	public function test_a_provider_url_is_rendered_as_an_ordinary_link()
	{
		$this->set_campaign(1, array(
			'external_url'			=> 'https://www.example-payments.test/pay/abc123',
			'external_link_text'	=> 'Über PayPal spenden',
		));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringContainsString('href="https://www.example-payments.test/pay/abc123"', $html);
		$this->assertStringContainsString('rel="noopener noreferrer nofollow"', $html);
		$this->assertStringNotContainsString('<script', $html);
		$this->assertStringNotContainsString('<form', $html);
		$this->assertStringNotContainsString('<img', $html);
	}

	/**
	 * REGRESSION 3. Entities are escaped exactly once in the final public
	 * HTML.
	 *
	 * phpBB stores user text HTML-escaped once, and generate_text_for_display()
	 * passes that through without escaping again -- the description is
	 * therefore the one public value the template must NOT put |e on. Adding
	 * one would show readers &amp;amp; where the administrator wrote &.
	 */
	public function test_an_ampersand_is_escaped_exactly_once_in_public_html()
	{
		// Stored the way phpBB stores it: escaped once.
		$this->set_campaign(1, array('campaign_desc' => 'Help us &amp; keep the board fast'));

		$this->view(10);
		$html = $this->render_box();

		$this->assertStringContainsString('Help us &amp; keep the board fast', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html, 'The description is escaped twice on the public page');
	}

	public function test_the_public_description_is_not_escaped_by_the_template()
	{
		$template = file_get_contents(
			dirname(dirname(__DIR__)) . '/styles/prosilver/template/event/viewtopic_body_poll_before.html'
		);

		// generate_text_for_display() has already produced safe HTML; escaping
		// it here would render an administrator's [b] as visible tags.
		$this->assertStringContainsString('{DONATIONCAMPAIGNS_DESC}', $template);
		$this->assertStringNotContainsString('{DONATIONCAMPAIGNS_DESC|e}', $template);
	}

	// ------------------------------------------------- the topic tools link

	/**
	 * Rebuild the listener with a specific set of granted permissions.
	 *
	 * @param string[] $granted
	 * @param bool $registered
	 * @return selective_auth
	 */
	protected function authorise(array $granted, $registered = true)
	{
		global $user, $auth;

		$auth = new selective_auth($granted);

		$user->data['is_registered'] = $registered;
		$user->data['user_id'] = $registered ? 2 : 1;
		$user->session_id = 'testsessionid';

		$language = $this->language();

		$this->listener = new viewtopic_listener(
			$this->service,
			new currency_formatter($language),
			$this->config,
			$this->template,
			$language,
			$auth,
			$user,
			'adm/',
			'php'
		);

		return $auth;
	}

	/**
	 * Both permissions, which is what an ordinary board administrator holds.
	 *
	 * @return selective_auth
	 */
	protected function authorise_admin()
	{
		return $this->authorise(array('a_', 'a_donationcampaigns'));
	}

	/**
	 * THE decisive test for the neutral label.
	 *
	 * The link must render on a topic that has NO campaign, which is exactly
	 * the case the old early return skipped. If this passes only when a
	 * campaign exists, campaigns can never be created.
	 */
	public function test_the_link_is_offered_on_a_topic_with_no_campaign()
	{
		$this->authorise_admin();
		$this->view(30);

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_TOPIC_LINK']);
	}

	public function test_the_link_is_offered_on_a_topic_with_an_enabled_campaign()
	{
		$this->authorise_admin();
		$this->view(10);

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_TOPIC_LINK']);
	}

	/**
	 * A disabled campaign still exists. The link must appear so it can be
	 * re-enabled; the public box must stay hidden.
	 */
	public function test_the_link_is_offered_on_a_topic_with_a_disabled_campaign()
	{
		$this->authorise_admin();
		$this->view(20);

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_TOPIC_LINK']);
		$this->assertArrayNotHasKey('S_DONATIONCAMPAIGNS_SHOW', $this->template->vars);
	}

	/**
	 * The label carries no verb, so the URL must carry no action either. The
	 * three states must be indistinguishable from the topic page.
	 */
	public function test_the_link_is_identical_in_all_three_campaign_states()
	{
		$urls = array();

		foreach (array(10, 20, 30) as $topic_id)
		{
			$this->template->vars = array();
			$this->authorise_admin();
			$this->view($topic_id);

			$urls[$topic_id] = $this->template->vars['U_DONATIONCAMPAIGNS_TOPIC_LINK'];
		}

		// Same shape everywhere; only the topic id differs.
		foreach ($urls as $topic_id => $url)
		{
			$this->assertStringNotContainsString('action=', $url, 'A verb in the URL can go stale');
			$this->assertStringContainsString('t=' . $topic_id, $url);
		}
	}

	public function test_the_link_names_the_campaigns_mode_of_this_extensions_module()
	{
		$this->authorise_admin();
		$this->view(30);

		$url = $this->template->vars['U_DONATIONCAMPAIGNS_TOPIC_LINK'];

		$this->assertStringContainsString('mode=campaigns', $url);
		$this->assertStringContainsString('adm/', $url);
	}

	/**
	 * LOAD-BEARING. The topic tools link is now the ONLY route to campaign
	 * creation, so a module identifier that does not resolve does not degrade
	 * the feature — it removes it. And it would fail at runtime, in
	 * production, only for administrators.
	 *
	 * This reverses phpBB's own mapping (functions_module.php:495-498 turns
	 * dashes back into backslashes) and asserts the result is the real ACP
	 * module class.
	 */
	public function test_the_generated_url_resolves_to_this_extensions_acp_module()
	{
		$this->authorise_admin();
		$this->view(30);

		$url = $this->template->vars['U_DONATIONCAMPAIGNS_TOPIC_LINK'];

		$this->assertSame(1, preg_match('/[?&]i=([^&]+)/', $url, $matches), 'The link carries no module identifier');

		// phpBB's reverse mapping, applied to what we emitted.
		$class = str_replace('-', '\\', urldecode($matches[1]));

		$this->assertTrue(
			class_exists($class),
			"The module identifier does not name a real class: {$class}"
		);
		$this->assertSame(
			ltrim(\uflagmey\donationcampaigns\acp\main_module::class, '\\'),
			ltrim($class, '\\'),
			'The link does not resolve to this extension ACP module'
		);
	}

	/**
	 * The dashed form is derived, never written down. A literal would survive
	 * a class rename and silently stop resolving.
	 */
	public function test_no_production_file_hardcodes_the_dashed_module_identifier()
	{
		$root = dirname(dirname(__DIR__));
		$found = array();

		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

		foreach ($files as $file)
		{
			$path = $file->getPathname();

			if (substr($path, -4) !== '.php' || strpos($path, '/tests/') !== false)
			{
				continue;
			}

			if (strpos(file_get_contents($path), '-uflagmey-donationcampaigns-') !== false)
			{
				$found[] = $path;
			}
		}

		$this->assertSame(array(), $found, 'The dashed module identifier is hard-coded');
	}

	public function test_the_link_carries_a_session_id()
	{
		$this->authorise_admin();
		$this->view(30);

		// The ACP defines NEED_SID; a link without one cannot be followed.
		$this->assertStringContainsString('sid=testsessionid', $this->template->vars['U_DONATIONCAMPAIGNS_TOPIC_LINK']);
	}

	/**
	 * The dropdown wrapper is conditional on a core tool being present. This
	 * variable is core's own escape hatch and is assigned nowhere in core PHP.
	 */
	public function test_the_topic_tools_wrapper_is_forced_open_for_the_link()
	{
		$this->authorise_admin();
		$this->view(30);

		$this->assertTrue($this->template->vars['S_DISPLAY_TOPIC_TOOLS']);
	}

	public function test_a_user_without_the_extension_permission_sees_no_link()
	{
		$this->authorise(array('a_'));
		$this->view(30);

		$this->assertArrayNotHasKey('S_DONATIONCAMPAIGNS_TOPIC_LINK', $this->template->vars);
		$this->assertArrayNotHasKey('S_DISPLAY_TOPIC_TOOLS', $this->template->vars);
	}

	/**
	 * a_ is a real, separate ACL option, not a prefix wildcard: holding
	 * a_donationcampaigns alone does NOT grant ACP entry. Rendering the link
	 * for this administrator would send them to a 403.
	 */
	public function test_an_administrator_who_cannot_enter_the_acp_sees_no_link()
	{
		$this->authorise(array('a_donationcampaigns'));
		$this->view(30);

		$this->assertArrayNotHasKey('S_DONATIONCAMPAIGNS_TOPIC_LINK', $this->template->vars);
	}

	public function test_a_guest_sees_no_link()
	{
		$this->authorise(array('a_', 'a_donationcampaigns'), false);
		$this->view(30);

		$this->assertArrayNotHasKey('S_DONATIONCAMPAIGNS_TOPIC_LINK', $this->template->vars);
	}

	public function test_both_permissions_are_actually_consulted()
	{
		$auth = $this->authorise_admin();
		$this->view(30);

		// Proving the link is hidden does not prove the right questions were
		// asked. These are the two that matter.
		$this->assertContains('a_', $auth->checked);
		$this->assertContains('a_donationcampaigns', $auth->checked);
	}

	/**
	 * The link must not cost a campaign lookup. This listener runs on every
	 * topic view on the board, and the neutral label exists precisely so that
	 * no query is needed to choose it.
	 */
	public function test_an_ordinary_reader_triggers_no_campaign_query_for_the_link()
	{
		$this->authorise(array());

		$before = $this->db->sql_num_queries();
		$this->view(30);
		$after = $this->db->sql_num_queries();

		// One lookup for the public box, and nothing more for the link.
		$this->assertLessThanOrEqual(1, $after - $before);
	}
}
