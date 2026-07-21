<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

use uflagmey\donationcampaigns\acp\main_module;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\donation_service;
use uflagmey\donationcampaigns\service\currency_formatter;
use uflagmey\donationcampaigns\tests\service\fake_description_formatter;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;
use uflagmey\donationcampaigns\tests\event\recording_template;

/**
 * Shared fixture for the ACP campaign tests.
 *
 * phpBB constructs ACP modules with no arguments and hands them globals, so
 * the globals are what this sets up. Two campaigns exist: an enabled one on
 * topic 10 with three donations, and a disabled one on topic 20 with one.
 * Topic 30 is free, so creation tests have somewhere to go.
 */
abstract class campaign_acp_test_case extends \phpbb_test_case
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

	/** @var campaign_service */
	protected $campaign_service;

	/** @var donation_service */
	protected $donation_service;

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

		global $phpbb_root_path;

		require_once $phpbb_root_path . 'includes/functions_acp.php';
		require_once $phpbb_root_path . '../tests/mock/container_builder.php';
		require_once $phpbb_root_path . '../tests/mock/request.php';

		$this->db_file = sys_get_temp_dir() . '/ufdc_acp_campaigns_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema();
		$this->seed();

		$this->campaigns = new campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$this->donations = new donation_repository($this->db, 'phpbb_ufdc_donations');
		$topics = new topic_repository($this->db, 'phpbb_topics');

		$this->campaign_service = new campaign_service($this->db, $this->campaigns, $this->donations, $topics, new fake_description_formatter());
		$this->donation_service = new donation_service($this->db, $this->campaigns, $this->donations);

		$this->config = new \phpbb\config\config(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 25,
			'form_token_lifetime'					=> 7200,
		));

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
	protected function create_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$core = $reflection->newInstanceWithoutConstructor()->update_schema();

		$tables = array();

		foreach (array('topics', 'users') as $name)
		{
			$definition = $core['add_tables'][$name];
			unset($definition['KEYS']);
			$tables['phpbb_' . $name] = $definition;
		}

		$this->tools->perform_schema_changes(array('add_tables' => $tables));

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
	}

	/**
	 * Campaign 1 is enabled on topic 10 with three donations totalling 2500
	 * against a target of 10000. Campaign 2 is disabled on topic 20 with one
	 * donation of 700.
	 *
	 * @return void
	 */
	protected function seed()
	{
		$this->db->sql_query('INSERT INTO phpbb_users ' . $this->db->sql_build_array('INSERT', array(
			'user_id' => 2, 'username' => 'admin', 'username_clean' => 'admin',
			'user_permissions' => '', 'user_sig' => '', 'user_occ' => '', 'user_interests' => '',
			'user_last_confirm_key' => 'confirm_key_value',
		)));

		// Topic 30 carries no campaign, so creation tests have somewhere to go.
		foreach (array(10 => 'Server replacement fund', 20 => 'Archive restoration topic', 30 => 'Legal costs topic') as $topic_id => $title)
		{
			$this->db->sql_query('INSERT INTO phpbb_topics ' . $this->db->sql_build_array('INSERT', array(
				'topic_id' => $topic_id, 'forum_id' => 2, 'topic_title' => $title,
				'topic_poster' => 2, 'topic_time' => 1700000000,
			)));
		}

		$campaigns = array(
			array('campaign_id' => 1, 'topic_id' => 10, 'campaign_title' => 'Server fund', 'target_amount' => 10000, 'collected_amount' => 2500, 'campaign_enabled' => 1),
			array('campaign_id' => 2, 'topic_id' => 20, 'campaign_title' => 'Archive fund', 'target_amount' => 5000, 'collected_amount' => 700, 'campaign_enabled' => 0),
		);

		foreach ($campaigns as $campaign)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_desc' => '', 'desc_bbcode_uid' => '', 'desc_bbcode_bitfield' => '',
				'desc_bbcode_options' => 7, 'show_donor_names' => 1, 'show_donation_count' => 1,
				'external_url' => '', 'campaign_created' => 1700000000, 'campaign_updated' => 1700000000,
			), $campaign)));
		}

		// One receipt is private: donation_public governs only whether the
		// front end names the donor, so the ACP must still list it.
		$donations = array(
			array('campaign_id' => 1, 'donation_amount' => 1000, 'donation_time' => 1700000100, 'donation_public' => 1),
			array('campaign_id' => 1, 'donation_amount' => 1200, 'donation_time' => 1700000200, 'donation_public' => 0),
			array('campaign_id' => 1, 'donation_amount' => 300, 'donation_time' => 1700000300, 'donation_public' => 1),
			array('campaign_id' => 2, 'donation_amount' => 700, 'donation_time' => 1700000400, 'donation_public' => 1),
		);

		foreach ($donations as $donation)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'donor_name' => 'Donor',
				'donation_created' => 1700000000, 'donation_updated' => 1700000000,
			), $donation)));
		}
	}

	/**
	 * @return void
	 */
	protected function set_globals()
	{
		global $phpbb_root_path, $phpEx, $auth, $language, $user, $request, $template, $config, $phpbb_log, $phpbb_container, $phpbb_dispatcher, $db;

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

		$user = new formatting_user();
		$user->data = array(
			'user_id'					=> 2,
			'user_form_salt'			=> 'test_salt',
			'user_last_confirm_key'		=> 'confirm_key_value',
		);
		$user->ip = '127.0.0.1';
		$user->lang = array('BACK_TO_PREV' => 'Back');
		$user->session_id = 'test_session';

		$db = $this->db;
		$request = new \phpbb_mock_request();
		$template = $this->template;
		$config = $this->config;
		$phpbb_log = $this->log;
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_container->set('uflagmey.donationcampaigns.campaign_service', $this->campaign_service);
		$phpbb_container->set('uflagmey.donationcampaigns.donation_service', $this->donation_service);
		$phpbb_container->set('uflagmey.donationcampaigns.currency_formatter', new currency_formatter($language));
		$phpbb_container->set('uflagmey.donationcampaigns.donation_repository', $this->donations);
		$phpbb_container->set('uflagmey.donationcampaigns.campaign_repository', $this->campaigns);
		$phpbb_container->set('uflagmey.donationcampaigns.topic_repository', new topic_repository($this->db, 'phpbb_topics'));
		$phpbb_container->set('pagination', new stub_pagination());
		// The campaign list's Edit link is generated as a frontend route.
		$phpbb_container->set('controller.helper', new \uflagmey\donationcampaigns\tests\controller\recording_helper());
	}

	/**
	 * Build a request. Setting $confirmed reproduces what phpBB's confirm_box
	 * posts back when an administrator clicks Yes, so the confirmation flow is
	 * exercised rather than bypassed.
	 *
	 * @param array $values
	 * @param bool $confirmed
	 * @return void
	 */
	protected function request(array $values = array(), $confirmed = false)
	{
		global $request, $user, $language;

		$post = $values;

		if ($confirmed)
		{
			$post = array_merge($post, array(
				'confirm'		=> $language->lang('YES'),
				'confirm_uid'	=> $user->data['user_id'],
				'sess'			=> $user->session_id,
				'confirm_key'	=> $user->data['user_last_confirm_key'],
			));
		}

		// The REQUEST superglobal is left to default to post+get: confirm_box
		// reads confirm_uid, sess and confirm_key from REQUEST, not POST.
		$request = new \phpbb_mock_request(array(), $post);
	}

	/**
	 * @return void
	 */
	protected function run_campaigns()
	{
		$this->module->main(1, 'campaigns');
	}

	/**
	 * Run the module and swallow the request-ending trigger_error that phpBB
	 * uses for a completed action.
	 *
	 * @return string The message raised, or an empty string
	 */
	protected function run_and_catch()
	{
		try
		{
			$this->run_campaigns();
		}
		catch (\Throwable $e)
		{
			return $e->getMessage();
		}

		return '';
	}

	/**
	 * @return array
	 */
	protected function rows()
	{
		return $this->template->block('donationcampaigns_row');
	}

	/**
	 * Render one of the shipped ACP templates with what the module assigned.
	 *
	 * Escaping lives in the templates now, so assertions about safety have to
	 * look at rendered output rather than at assigned variables.
	 *
	 * @param string $name Template basename, without acp_donationcampaigns_
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
	 * Validation errors that reached the template, as rendered sentences.
	 *
	 * @return array
	 */
	protected function errors()
	{
		return array_column($this->template->block('donationcampaigns_error'), 'MESSAGE');
	}

	/**
	 * Post a form carrying a valid phpBB form key, generated the same way core
	 * generates it, so check_form_key() is exercised rather than bypassed.
	 *
	 * @param string $form_name
	 * @param array $values
	 * @param bool $valid_token
	 * @return void
	 */
	protected function post_form($form_name, array $values, $valid_token = true)
	{
		global $request, $user;

		$creation_time = time() - 60;
		$token = $valid_token
			? sha1($creation_time . $user->data['user_form_salt'] . $form_name)
			: 'a_wrong_token';

		$request = new \phpbb_mock_request(array(), array_merge(array(
			'creation_time'	=> $creation_time,
			'form_token'	=> $token,
		), $values));
	}
}
