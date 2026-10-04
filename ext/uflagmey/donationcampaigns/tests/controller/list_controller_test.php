<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\controller;

use uflagmey\donationcampaigns\controller\list_controller;
use uflagmey\donationcampaigns\repository\campaign_list_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\service\campaign_list_service;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\currency_formatter;
use uflagmey\donationcampaigns\tests\event\recording_template;
use uflagmey\donationcampaigns\tests\repository\campaign_list_test_case;
use uflagmey\donationcampaigns\tests\service\passworded_user;
use uflagmey\donationcampaigns\tests\unit\forum_scoped_auth;

/**
 * The read-only board list page (ADR-018): switch, rows, paging.
 *
 * Which campaigns appear is campaign_list_service's business and is tested
 * there; here the fixture's plain reader of forum A and B sees campaigns 3
 * and 1 (see campaign_list_test_case).
 */
class list_controller_test extends campaign_list_test_case
{
	/** @var recording_helper */
	protected $helper;

	/** @var recording_template */
	protected $template;

	/** @var recording_pagination */
	protected $pagination;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var array */
	protected $grants;

	public function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx, $phpbb_dispatcher;

		require_once $phpbb_root_path . 'includes/functions.php';
		require_once $phpbb_root_path . '../tests/mock/request.php';

		// append_sid() dispatches through the global dispatcher.
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		$loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$loader->set_extension_manager(new \phpbb_mock_extension_manager($phpbb_root_path, array(
			'uflagmey/donationcampaigns' => array(
				'ext_name' => 'uflagmey/donationcampaigns', 'ext_active' => true,
				'ext_path' => 'ext/uflagmey/donationcampaigns/',
			),
		)));
		$this->language = new \phpbb\language\language($loader);

		$this->config = new \phpbb\config\config(array(
			'donationcampaigns_list_enabled'		=> 1,
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 2,
		));

		$this->grants = array('f_read' => array(self::FORUM_A, self::FORUM_B));
	}

	/**
	 * Build the controller as the current viewer and run it.
	 *
	 * @param array $get
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function display(array $get = array())
	{
		global $phpbb_root_path, $phpEx;

		$this->helper = new recording_helper();
		$this->template = new recording_template();
		$this->pagination = new recording_pagination();

		$auth = new forum_scoped_auth($this->grants);
		$user = new passworded_user($this->language, '\phpbb\datetime');

		$list = new campaign_list_service(
			$auth,
			$this->content_visibility($auth),
			$user,
			new campaign_list_repository($this->db, 'phpbb_ufdc_campaigns', 'phpbb_topics', 'phpbb_forums'),
			new donation_repository($this->db, 'phpbb_ufdc_donations')
		);

		// progress() is pure; the campaign repositories are never touched.
		$campaigns = new campaign_service(
			$this->createMock('\phpbb\db\driver\driver_interface'),
			$this->createMock('\uflagmey\donationcampaigns\repository\campaign_repository'),
			$this->createMock('\uflagmey\donationcampaigns\repository\donation_repository'),
			$this->createMock('\uflagmey\donationcampaigns\repository\topic_repository'),
			$this->createMock('\uflagmey\donationcampaigns\service\description_formatter')
		);

		$controller = new list_controller(
			$this->helper,
			new fake_path_helper(),
			$this->template,
			$this->language,
			$this->config,
			new \phpbb_mock_request($get),
			$this->pagination,
			$list,
			$campaigns,
			new currency_formatter($this->language, $this->config)
		);

		return $controller->display();
	}

	protected function rows()
	{
		return $this->template->block('donationcampaigns_list');
	}

	protected function render_page()
	{
		return \uflagmey\donationcampaigns\tests\template_renderer::render(
			file_get_contents(dirname(dirname(__DIR__)) . '/styles/prosilver/template/donationcampaigns_list.html'),
			$this->template->vars,
			$this->template->blocks
		);
	}

	protected function assert_not_found(callable $work)
	{
		try
		{
			$work();
		}
		catch (\phpbb\exception\http_exception $e)
		{
			$this->assertSame(404, $e->getStatusCode());
			$this->assertSame('PAGE_NOT_FOUND', $e->getMessage());

			return;
		}

		$this->fail('Expected a 404, but the page rendered');
	}

	// ---------------------------------------------------------------- switch

	public function test_the_switch_off_answers_404()
	{
		$this->config->set('donationcampaigns_list_enabled', 0);

		$this->assert_not_found(function () {
			$this->display();
		});
		$this->assertNull($this->helper->rendered);
		$this->assertSame(array(), $this->db->queries, 'A switched-off list must not query anything');
	}

	/**
	 * During an update the code can be live before m10 has added the key.
	 */
	public function test_a_missing_switch_answers_404()
	{
		$this->config->delete('donationcampaigns_list_enabled');

		$this->assert_not_found(function () {
			$this->display();
		});
	}

	public function test_the_switch_on_renders_the_list()
	{
		$this->display();

		$this->assertSame('donationcampaigns_list.html', $this->helper->rendered['template']);
		$this->assertSame('Donation campaigns', $this->helper->rendered['title']);
		$this->assertSame(array('Campaign 3', 'Campaign 1'), array_column($this->rows(), 'TITLE'));
	}

	public function test_the_page_has_a_breadcrumb_to_itself()
	{
		$this->display();

		$navlinks = $this->template->block('navlinks');
		$this->assertSame('Donation campaigns', $navlinks[0]['BREADCRUMB_NAME']);
		$this->assertSame('uflagmey_donationcampaigns_list', $navlinks[0]['U_BREADCRUMB']);
	}

	// ------------------------------------------------------------------ rows

	/**
	 * The exact contract: no donor names, no description, no link — an
	 * overview, the details stay in the topic.
	 */
	public function test_rows_carry_only_the_documented_keys()
	{
		$this->display();

		$keys = array_keys($this->rows()[0]);
		sort($keys);

		$this->assertSame(array(
			'COLLECTED', 'COUNT', 'FORUM_NAME', 'PERCENT', 'PERCENT_RAW', 'STEP', 'S_REACHED', 'TARGET', 'TITLE', 'U_FORUM', 'U_TOPIC',
		), $keys);
	}

	public function test_a_row_links_to_its_topic_and_forum_from_the_board_root()
	{
		$this->display();
		$row = $this->rows()[1];

		$this->assertSame(fake_path_helper::WEB_ROOT . 'viewtopic.php?t=10', $row['U_TOPIC']);
		$this->assertSame(fake_path_helper::WEB_ROOT . 'viewforum.php?f=' . self::FORUM_A, $row['U_FORUM']);
	}

	public function test_amounts_carry_the_currency_symbol()
	{
		$this->display();
		$row = $this->rows()[1];

		$this->assertStringContainsString('25.00', $row['COLLECTED']);
		$this->assertStringContainsString('€', $row['COLLECTED']);
		$this->assertStringContainsString('100.00', $row['TARGET']);
		$this->assertStringContainsString('€', $row['TARGET']);
	}

	public function test_progress_figures_come_from_the_shared_calculation()
	{
		$this->display();
		$row = $this->rows()[1];

		$this->assertSame(25, $row['PERCENT']);
		$this->assertSame(25, $row['PERCENT_RAW']);
		$this->assertSame(25, $row['STEP']);
		$this->assertFalse($row['S_REACHED']);
	}

	public function test_an_over_target_campaign_is_capped_and_marked_reached()
	{
		$this->db->sql_query('UPDATE phpbb_ufdc_campaigns SET collected_amount = 25000 WHERE campaign_id = 1');

		$this->display();
		$row = $this->rows()[1];

		$this->assertSame(100, $row['PERCENT']);
		$this->assertSame(250, $row['PERCENT_RAW']);
		$this->assertSame(100, $row['STEP']);
		$this->assertTrue($row['S_REACHED']);
	}

	public function test_the_count_is_shown_only_when_the_campaign_shows_it()
	{
		$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array(
			'campaign_id' => 1, 'donation_amount' => 2500, 'donor_name' => 'Secret Donor', 'donation_time' => 1700000100,
			'donation_public' => 1, 'donation_created' => 1700000100, 'donation_updated' => 1700000100,
		)));
		$this->db->sql_query('UPDATE phpbb_ufdc_campaigns SET show_donation_count = 0 WHERE campaign_id = 3');

		$this->display();
		$rows = $this->rows();

		$this->assertSame('', $rows[0]['COUNT']);
		$this->assertSame('1 donation', $rows[1]['COUNT']);
	}

	public function test_the_page_names_no_donor()
	{
		$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array(
			'campaign_id' => 1, 'donation_amount' => 2500, 'donor_name' => 'Secret Donor', 'donation_time' => 1700000100,
			'donation_public' => 1, 'donation_created' => 1700000100, 'donation_updated' => 1700000100,
		)));

		$this->display();

		$this->assertStringNotContainsString('Secret Donor', serialize($this->template->vars) . serialize($this->template->blocks));
		$this->assertStringNotContainsString('Secret Donor', $this->render_page());
	}

	// ------------------------------------------------------------- escaping

	public function test_a_campaign_title_is_escaped_and_a_forum_name_is_not_escaped_twice()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_campaigns SET campaign_title = '<b>Kosten & \"Miete\"</b>' WHERE campaign_id = 1");

		$this->display();
		$html = $this->render_page();

		// Our raw title: escaped once by the template.
		$this->assertStringContainsString('&lt;b&gt;Kosten &amp; &quot;Miete&quot;&lt;/b&gt;', $html);
		$this->assertStringNotContainsString('<b>Kosten', $html);
		// Core's pre-escaped forum name: printed as stored, not escaped again.
		$this->assertStringContainsString('Spenden &amp; Hilfe', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html);
	}

	// ----------------------------------------------------------- pagination

	public function test_25_per_page_with_the_total()
	{
		$this->seed_many(30);
		$this->display();

		$this->assertCount(25, $this->rows());
		$this->assertSame(array(
			'base_url'			=> 'uflagmey_donationcampaigns_list',
			'block_var_name'	=> 'pagination',
			'start_name'		=> 'start',
			'num_items'			=> 32,
			'per_page'			=> 25,
			'start'				=> 0,
		), $this->pagination->calls[0]);
		$this->assertSame('32 campaigns', $this->template->vars['DONATIONCAMPAIGNS_PUBLIC_LIST_TOTAL']);
	}

	public function test_the_second_page()
	{
		$this->seed_many(30);
		$this->display(array('start' => 25));

		$this->assertCount(7, $this->rows());
		$this->assertSame(25, $this->pagination->calls[0]['start']);
		$this->assertSame('Campaign 1', end($this->rows())['TITLE']);
	}

	/**
	 * @return array
	 */
	public function out_of_range_start_data()
	{
		return array(
			'beyond the last page'	=> array(500, 25),
			'negative'				=> array(-5, 0),
			'not a number'			=> array('abc', 0),
			'an array'				=> array(array(1), 0),
		);
	}

	/**
	 * @dataProvider out_of_range_start_data
	 */
	public function test_an_out_of_range_start_lands_on_a_valid_page($start, $expected)
	{
		$this->seed_many(30);
		$this->display(array('start' => $start));

		$this->assertSame($expected, $this->pagination->calls[0]['start']);
		$this->assertNotEmpty($this->rows());
	}

	// ------------------------------------------------------------ empty / guest

	public function test_an_empty_list_says_so()
	{
		$this->grants = array();

		$this->display();

		$this->assertSame(array(), $this->rows());
		$this->assertSame('donationcampaigns_list.html', $this->helper->rendered['template']);
		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_PUBLIC_LIST_EMPTY}', $this->render_page());
	}

	public function test_a_guest_sees_the_guest_readable_campaigns()
	{
		$this->grants = array('f_read' => array(self::FORUM_B));

		$this->display();

		$this->assertSame(array('Campaign 3'), array_column($this->rows(), 'TITLE'));
	}
}
