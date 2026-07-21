<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

use uflagmey\donationcampaigns\acp\main_module;
use uflagmey\donationcampaigns\service\settings_service;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;
use uflagmey\donationcampaigns\tests\event\recording_template;

/**
 * The ACP settings mode.
 *
 * The module is coordination only, so these tests cover what coordination
 * means in practice: that the permission is enforced on direct invocation,
 * that a request without a valid form key is refused, that nothing is written
 * when validation fails, and that what an administrator typed comes back to
 * them when it does.
 *
 * phpBB constructs ACP modules with no arguments and hands them globals, so
 * the globals are what the fixture sets up.
 */
class settings_mode_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var main_module */
	protected $module;

	/** @var recording_template */
	protected $template;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	/** @var settings_service */
	protected $settings;

	/** @var recording_log */
	protected $log;

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

		require_once $phpbb_root_path . 'includes/functions_acp.php';
		require_once $phpbb_root_path . '../tests/mock/container_builder.php';
		require_once $phpbb_root_path . '../tests/mock/request.php';

		$this->db_file = sys_get_temp_dir() . '/ufdc_acp_settings_' . getmypid() . '_' . uniqid() . '.sqlite3';

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

		$this->campaigns = new campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$this->donations = new donation_repository($this->db, 'phpbb_ufdc_donations');

		$this->config = new \phpbb\config\config(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 25,
			'form_token_lifetime'					=> 7200,
			'an_unrelated_key'						=> 'untouched',
		));

		$this->settings = new settings_service($this->config, $this->campaigns, $this->donations);
		$this->template = new recording_template();
		$this->log = new recording_log();

		$this->set_globals();

		$this->module = new main_module();
		$this->module->u_action = 'acp_action_url';
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
	 * @return void
	 */
	protected function set_globals()
	{
		global $phpbb_root_path, $phpEx, $auth, $language, $user, $request, $template, $config, $phpbb_log, $phpbb_container, $phpbb_dispatcher;

		$auth = new grantable_auth();
		$auth->granted = true;

		$loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$loader->set_extension_manager(new \phpbb_mock_extension_manager($phpbb_root_path, array(
			'uflagmey/donationcampaigns' => array(
				'ext_name'		=> 'uflagmey/donationcampaigns',
				'ext_active'	=> true,
				'ext_path'		=> 'ext/uflagmey/donationcampaigns/',
			),
		)));
		$language = new \phpbb\language\language($loader);

		$user = new \phpbb_mock_user();
		$user->data = array('user_id' => 2, 'user_form_salt' => 'test_salt');
		$user->ip = '127.0.0.1';
		// adm_back_link(), reached on every refusal path, reads $user->lang
		// directly rather than going through the language service.
		$user->lang = array('BACK_TO_PREV' => 'Back to previous page');

		$request = new \phpbb_mock_request();
		$template = $this->template;
		$config = $this->config;
		$phpbb_log = $this->log;
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_container->set('uflagmey.donationcampaigns.settings_service', $this->settings);
	}

	/**
	 * Post a settings form. A valid form token is generated the same way core
	 * generates it, so the CSRF check is exercised rather than bypassed.
	 *
	 * @param array $values
	 * @param bool $valid_token
	 * @return void
	 */
	protected function submit(array $values, $valid_token = true)
	{
		global $request, $user;

		$creation_time = time() - 60;
		$token = $valid_token
			? sha1($creation_time . $user->data['user_form_salt'] . 'donationcampaigns_settings')
			: 'a_wrong_token';

		$post = array_merge(array(
			'submit'		=> 'Submit',
			'creation_time'	=> $creation_time,
			'form_token'	=> $token,
		), $values);

		$request = new \phpbb_mock_request(array(), $post);
	}

	/**
	 * @return void
	 */
	protected function run_settings()
	{
		$this->module->main(1, 'settings');
	}

	/**
	 * Render a shipped ACP template with what the module assigned. Escaping
	 * lives in the template now, so safety is asserted on rendered output.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function render($name)
	{
		return \uflagmey\donationcampaigns\tests\template_renderer::render(
			file_get_contents(dirname(dirname(__DIR__)) . '/adm/style/acp_donationcampaigns_' . $name . '.html'),
			$this->template->vars,
			$this->template->blocks
		);
	}

	/**
	 * @return array
	 */
	protected function errors()
	{
		return array_column($this->template->block('donationcampaigns_error'), 'MESSAGE');
	}

	/**
	 * phpBB signals a refusal with trigger_error(..., E_USER_WARNING), which
	 * PHPUnit turns into a throwable. Asserted through a helper rather than
	 * expectWarning(), which PHPUnit 10 removes.
	 *
	 * @param callable $work
	 * @param string $needle
	 * @return void
	 */
	protected function assert_refused($work, $needle)
	{
		try
		{
			$work();
		}
		catch (\Throwable $e)
		{
			$this->assertStringContainsString($needle, $e->getMessage());

			return;
		}

		$this->fail("Expected a refusal containing {$needle}, but none was raised");
	}

	// ------------------------------------------------------------------ ACL

	/**
	 * The module info auth string only hides the menu entry. Reaching the
	 * module by URL must still be refused, and this is the check that does it.
	 */
	public function test_an_unauthorised_user_is_refused()
	{
		global $auth;

		$auth->granted = false;

		$this->assert_refused(function () {
			$this->run_settings();
		}, 'NOT_AUTHORISED');
	}

	public function test_an_unauthorised_user_reaches_no_settings_at_all()
	{
		global $auth;

		$auth->granted = false;

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// expected
		}

		$this->assertSame(array(), $this->template->vars, 'Settings were assigned to an unauthorised user');
	}

	public function test_the_permission_checked_is_the_extension_permission()
	{
		global $auth;

		$this->run_settings();

		$this->assertContains('a_donationcampaigns', $auth->checked, 'The extension permission was never checked');
	}

	public function test_an_authorised_user_reaches_the_settings_page()
	{
		$this->run_settings();

		$this->assertSame('acp_donationcampaigns_settings', $this->module->tpl_name);
		$this->assertNotEmpty($this->module->page_title);
	}

	public function test_an_unknown_mode_is_refused()
	{
		$this->assert_refused(function () {
			$this->module->main(1, 'not_a_mode');
		}, 'NO_MODE');
	}

	// ------------------------------------------------------------- display

	public function test_the_current_values_are_shown()
	{
		$this->run_settings();

		$this->assertSame('EUR', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_CODE']);
		$this->assertSame('€', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_SYMBOL']);
		$this->assertSame(2, $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_EXPONENT']);
		$this->assertSame(25, $this->template->vars['DONATIONCAMPAIGNS_DONOR_LIST_LIMIT']);
	}

	public function test_the_action_url_is_passed_through()
	{
		$this->run_settings();

		$this->assertSame('acp_action_url', $this->template->vars['U_ACTION']);
	}

	public function test_no_errors_are_shown_on_first_view()
	{
		$this->run_settings();

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ERROR']);
		$this->assertSame(array(), $this->errors());
	}

	// ---------------------------------------------------------------- CSRF

	public function test_a_submission_without_a_valid_form_key_is_refused()
	{
		global $language;

		$this->submit(array('donationcampaigns_currency_code' => 'USD'), false);

		// The refusal is rendered from the language file, not shown as a raw
		// key, so the expectation is the translated sentence.
		$this->assert_refused(function () {
			$this->run_settings();
		}, $language->lang('FORM_INVALID'));
	}

	public function test_a_submission_without_a_valid_form_key_writes_nothing()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'USD',
			'donationcampaigns_currency_symbol'		=> '$',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 50,
		), false);

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// expected
		}

		$this->assertSame('EUR', $this->config['donationcampaigns_currency_code'], 'A CSRF-failing request was saved');
	}

	// -------------------------------------------------------------- saving

	public function test_a_valid_submission_saves_every_value()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'usd',
			'donationcampaigns_currency_symbol'		=> '$',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 100,
		));

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// trigger_error(CONFIG_UPDATED) ends the request in phpBB
		}

		$this->assertSame('USD', $this->config['donationcampaigns_currency_code']);
		$this->assertSame('$', $this->config['donationcampaigns_currency_symbol']);
		$this->assertSame(2, (int) $this->config['donationcampaigns_currency_exponent']);
		$this->assertSame(100, (int) $this->config['donationcampaigns_donor_list_limit']);
	}

	public function test_a_valid_submission_is_logged()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'USD',
			'donationcampaigns_currency_symbol'		=> '$',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 100,
		));

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// expected
		}

		$this->assertContains('LOG_DONATIONCAMPAIGNS_SETTINGS_UPDATED', $this->log->operations);
	}

	public function test_unrelated_config_keys_are_untouched_by_a_save()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'USD',
			'donationcampaigns_currency_symbol'		=> '$',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 100,
		));

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// expected
		}

		$this->assertSame('untouched', $this->config['an_unrelated_key']);
	}

	// ----------------------------------------------------------- validation

	public function test_an_invalid_submission_shows_errors_and_saves_nothing()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'nope',
			'donationcampaigns_currency_symbol'		=> '',
			'donationcampaigns_currency_exponent'	=> 9,
			'donationcampaigns_donor_list_limit'	=> 0,
		));

		$this->run_settings();

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ERROR']);
		$this->assertCount(4, $this->errors(), 'Every failure should be reported at once');
		$this->assertSame('EUR', $this->config['donationcampaigns_currency_code'], 'An invalid form was partially saved');
		$this->assertSame(25, (int) $this->config['donationcampaigns_donor_list_limit']);
	}

	/**
	 * Errors reach the page as sentences, not as raw language keys. A page
	 * reading DONATIONCAMPAIGNS_ERROR_CURRENCY_CODE looks like a crash.
	 */
	public function test_errors_are_rendered_from_language_files()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'nope',
			'donationcampaigns_currency_symbol'		=> '$',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 25,
		));

		$this->run_settings();

		foreach ($this->errors() as $message)
		{
			$this->assertDoesNotMatchRegularExpression('/^DONATIONCAMPAIGNS_/', $message, "Untranslated key: {$message}");
			$this->assertNotEmpty($message);
		}
	}

	/**
	 * A rejected form comes back with what the administrator typed, not with
	 * the stored values. Retyping four fields because one was wrong is how
	 * people give up on a settings page.
	 */
	public function test_submitted_values_are_preserved_after_a_failure()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'GBP',
			'donationcampaigns_currency_symbol'		=> '£',
			// Only this one is wrong.
			'donationcampaigns_currency_exponent'	=> 9,
			'donationcampaigns_donor_list_limit'	=> 200,
		));

		$this->run_settings();

		$this->assertSame('GBP', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_CODE']);
		$this->assertSame('£', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_SYMBOL']);
		$this->assertSame(200, $this->template->vars['DONATIONCAMPAIGNS_DONOR_LIST_LIMIT']);
	}

	// ------------------------------------------------------------- escaping

	/**
	 * THE ESCAPING CONTRACT, end to end.
	 *
	 * The value is stored exactly as typed and escaped only when rendered.
	 * phpBB disables Twig's autoescaping, so an unescaped assignment would
	 * reach the page verbatim.
	 */
	public function test_html_like_input_is_stored_raw_and_rendered_as_text()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'USD',
			'donationcampaigns_currency_symbol'		=> '<script>alert(1)</script>',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 25,
		));

		$this->run_settings();

		// Rejected for length, so re-rendered from the submitted value —
		// assigned raw, escaped by the template.
		$this->assertSame('<script>alert(1)</script>', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_SYMBOL']);

		$html = $this->render('settings');

		$this->assertStringNotContainsString('<script>alert(1)</script>', $html);
		$this->assertStringContainsString('&lt;script&gt;', $html);
	}

	public function test_a_stored_html_like_symbol_is_escaped_when_displayed()
	{
		$this->config->set('donationcampaigns_currency_symbol', '<b>$</b>');

		$this->run_settings();

		$this->assertSame('<b>$</b>', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_SYMBOL']);

		$html = $this->render('settings');

		$this->assertStringNotContainsString('<b>$</b>', $html);
		$this->assertStringContainsString('&lt;b&gt;$&lt;/b&gt;', $html);
	}

	/**
	 * An attribute breakout is the realistic attack here: the value is
	 * rendered into value="..." on the settings form itself.
	 */
	public function test_a_quote_cannot_break_out_of_the_input_attribute()
	{
		$this->config->set('donationcampaigns_currency_symbol', '" onfocus="alert(1)');

		$this->run_settings();

		$this->assertStringNotContainsString('onfocus="alert(1)"', $this->render('settings'));
	}

	/**
	 * Escaping happens on the way OUT, never on the way in. If it happened on
	 * the way in, an administrator typing '&' would find '&amp;' stored and
	 * would see it grow on every subsequent save.
	 */
	public function test_an_ampersand_is_stored_plainly_and_does_not_grow()
	{
		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'USD',
			'donationcampaigns_currency_symbol'		=> 'R&D',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 25,
		));

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// expected
		}

		$this->assertSame('R&D', $this->config['donationcampaigns_currency_symbol'], 'The value was escaped into storage');

		// Rendering it is where the escaping happens — exactly once.
		$html = $this->settings_symbol_as_rendered();

		$this->assertStringContainsString('R&amp;D', $html);
		$this->assertStringNotContainsString('R&amp;amp;D', $html);
	}

	/**
	 * @return string
	 */
	protected function settings_symbol_as_rendered()
	{
		global $request;

		$request = new \phpbb_mock_request();
		$this->template->vars = array();

		$this->run_settings();

		return $this->render('settings');
	}

	// ------------------------------------------------------ exponent warning

	public function test_no_warning_is_shown_on_an_empty_board()
	{
		$this->run_settings();

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_HAS_AMOUNTS']);
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT']);
	}

	public function test_the_warning_is_shown_once_a_campaign_exists()
	{
		$this->seed_campaign();

		$this->run_settings();

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_HAS_AMOUNTS']);
	}

	public function test_changing_the_exponent_with_data_is_refused_without_confirmation()
	{
		$this->seed_campaign();

		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 3,
			'donationcampaigns_donor_list_limit'	=> 25,
		));

		$this->run_settings();

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ERROR']);
		$this->assertSame(2, (int) $this->config['donationcampaigns_currency_exponent'], 'The exponent changed without confirmation');
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT'], 'The confirmation control should now be offered');
	}

	public function test_a_confirmed_exponent_change_is_saved()
	{
		$this->seed_campaign();

		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 3,
			'donationcampaigns_donor_list_limit'	=> 25,
			'donationcampaigns_confirm_exponent'	=> 1,
		));

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// expected
		}

		$this->assertSame(3, (int) $this->config['donationcampaigns_currency_exponent']);
	}

	/**
	 * The stored integers are never rewritten. Only their interpretation
	 * changes, which is exactly what the warning says.
	 */
	public function test_a_confirmed_exponent_change_leaves_stored_amounts_byte_for_byte()
	{
		$this->seed_campaign();

		$before = $this->campaigns->find_by_id(1);

		$this->submit(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 0,
			'donationcampaigns_donor_list_limit'	=> 25,
			'donationcampaigns_confirm_exponent'	=> 1,
		));

		try
		{
			$this->run_settings();
		}
		catch (\Throwable $e)
		{
			// expected
		}

		$this->assertEquals($before, $this->campaigns->find_by_id(1), 'A stored amount was rewritten');
		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	/**
	 * @return void
	 */
	protected function seed_campaign()
	{
		$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array(
			'campaign_id'			=> 1,
			'topic_id'				=> 10,
			'campaign_title'		=> 'Server fund',
			'campaign_desc'			=> '',
			'desc_bbcode_uid'		=> '',
			'desc_bbcode_bitfield'	=> '',
			'desc_bbcode_options'	=> 7,
			'target_amount'			=> 100000,
			'collected_amount'		=> 2500,
			'campaign_enabled'		=> 1,
			'show_donor_names'		=> 1,
			'show_donation_count'	=> 1,
			'external_url'			=> '',
			'campaign_created'		=> 1700000000,
			'campaign_updated'		=> 1700000000,
		)));
	}
}
