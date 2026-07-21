<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\controller;

use uflagmey\donationcampaigns\controller\campaign_controller;
use uflagmey\donationcampaigns\service\access;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\currency_formatter;
use uflagmey\donationcampaigns\tests\service\fake_description_formatter;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;
use uflagmey\donationcampaigns\tests\unit\forum_scoped_auth;
use uflagmey\donationcampaigns\tests\event\recording_template;
use uflagmey\donationcampaigns\tests\acp\recording_log;

/**
 * Shared fixture for the frontend campaign controller.
 *
 * Two forums: A = 2 and B = 3. Forum A holds topic 10 (an enabled campaign with
 * one donation — non-empty) and free topic 11; forum B holds topic 20 (a
 * disabled campaign with no donations — empty) and free topic 30. Topic 40 is a
 * moved shadow of 10. This is enough to exercise every state and every
 * forum-scoped authorisation the controller must get right.
 *
 * The controller talks to a recording controller.helper (which never renders a
 * real template) and a forum-scoped auth double, so a test asserts on the
 * variables the controller assigned and on which template/message/route it
 * asked for — the controller's whole observable contract.
 */
abstract class controller_test_case extends \phpbb_test_case
{
	const FORUM_A = 2;
	const FORUM_B = 3;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var campaign_controller */
	protected $controller;

	/** @var recording_helper */
	protected $helper;

	/** @var recording_template */
	protected $template;

	/** @var recording_log */
	protected $log;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	/** @var campaign_service */
	protected $campaign_service;

	/** @var string */
	protected $db_file;

	/** @var array<string, true|int[]> the current actor's ACL grants */
	protected $grants = array();

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		global $phpbb_root_path;

		require_once $phpbb_root_path . 'includes/functions.php';
		require_once $phpbb_root_path . '../tests/mock/request.php';

		$this->db_file = sys_get_temp_dir() . '/ufdc_ctrl_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema();
		$this->seed();

		$this->campaigns = new campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$this->donations = new donation_repository($this->db, 'phpbb_ufdc_donations');
		$topics = new topic_repository($this->db, 'phpbb_topics');

		$this->campaign_service = new campaign_service(
			$this->db, $this->campaigns, $this->donations, $topics, new fake_description_formatter()
		);

		$this->config = new \phpbb\config\config(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 25,
			'form_token_lifetime'					=> 7200,
		));

		$this->template = new recording_template();
		$this->log = new recording_log();
		$this->helper = new recording_helper();

		$this->set_globals();

		// Default actor: no permissions. Tests call as_actor() to grant.
		$this->grants = array();
		$this->rebuild();
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
	 * Rebuild the controller with a given set of ACL grants. The map is the
	 * forum_scoped_auth format: option => true (global) or int[] (forum ids).
	 *
	 * @param array<string, true|int[]> $grants
	 * @return void
	 */
	protected function as_actor(array $grants)
	{
		global $auth;

		$this->grants = $grants;
		$auth = new forum_scoped_auth($grants);
		$this->rebuild();
	}

	/**
	 * Rebuild the controller from the current globals and the stored grants.
	 *
	 * Called whenever the actor or the request changes, so the controller always
	 * holds the request under test rather than a stale one captured earlier.
	 *
	 * @return void
	 */
	protected function rebuild()
	{
		global $user, $request, $template, $config, $language;

		$this->controller = new campaign_controller(
			$this->helper,
			$template,
			$language,
			$config,
			$request,
			$this->log,
			$user,
			new access(new forum_scoped_auth($this->grants)),
			$this->campaign_service,
			$this->campaigns,
			new topic_repository($this->db, 'phpbb_topics'),
			new currency_formatter($language)
		);
	}

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

		foreach (array(m1_initial_schema::class, \uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text::class) as $migration_class)
		{
			$migration = new $migration_class(new \phpbb\config\config(array()), $this->db, $this->tools, '', 'php', 'phpbb_');
			$this->tools->perform_schema_changes($migration->update_schema());
		}
	}

	/**
	 * Forum A (2): topic 10 has enabled campaign 1 with one donation; topic 11 is
	 * free; topic 40 is a moved shadow of 10. Forum B (3): topic 20 has disabled
	 * campaign 2 with no donations; topic 30 is free.
	 */
	protected function seed()
	{
		$this->db->sql_query('INSERT INTO phpbb_users ' . $this->db->sql_build_array('INSERT', array(
			'user_id' => 2, 'username' => 'mod', 'username_clean' => 'mod',
			'user_permissions' => '', 'user_sig' => '', 'user_occ' => '', 'user_interests' => '',
		)));

		$topics = array(
			array('topic_id' => 10, 'forum_id' => self::FORUM_A, 'topic_moved_id' => 0),
			array('topic_id' => 11, 'forum_id' => self::FORUM_A, 'topic_moved_id' => 0),
			array('topic_id' => 20, 'forum_id' => self::FORUM_B, 'topic_moved_id' => 0),
			array('topic_id' => 30, 'forum_id' => self::FORUM_B, 'topic_moved_id' => 0),
			array('topic_id' => 40, 'forum_id' => self::FORUM_A, 'topic_moved_id' => 10),
		);
		foreach ($topics as $topic)
		{
			$this->db->sql_query('INSERT INTO phpbb_topics ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'topic_title' => 'Topic ' . $topic['topic_id'], 'topic_poster' => 2, 'topic_time' => 1700000000,
			), $topic)));
		}

		$campaigns = array(
			array('campaign_id' => 1, 'topic_id' => 10, 'campaign_title' => 'Server fund', 'target_amount' => 10000, 'collected_amount' => 1000, 'campaign_enabled' => 1),
			array('campaign_id' => 2, 'topic_id' => 20, 'campaign_title' => 'Archive fund', 'target_amount' => 5000, 'collected_amount' => 0, 'campaign_enabled' => 0),
		);
		foreach ($campaigns as $campaign)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_desc' => '', 'desc_bbcode_uid' => '', 'desc_bbcode_bitfield' => '', 'desc_bbcode_options' => 7,
				'show_donor_names' => 1, 'show_donation_count' => 1, 'external_url' => '', 'external_link_text' => 'How to donate',
				'campaign_created' => 1700000000, 'campaign_updated' => 1700000000,
			), $campaign)));
		}

		// Campaign 1 is non-empty; campaign 2 has no donations.
		$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array(
			'campaign_id' => 1, 'donation_amount' => 1000, 'donor_name' => 'Donor', 'donation_time' => 1700000100,
			'donation_public' => 1, 'donation_created' => 1700000000, 'donation_updated' => 1700000000,
		)));
	}

	protected function set_globals()
	{
		global $phpbb_root_path, $phpEx, $auth, $language, $user, $request, $template, $config, $phpbb_dispatcher, $db;

		// add_form_key()/check_form_key() dispatch through the global dispatcher.
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		// confirm_box() resets user_last_confirm_key through the global $db.
		$db = $this->db;

		$loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$loader->set_extension_manager(new \phpbb_mock_extension_manager($phpbb_root_path, array(
			'uflagmey/donationcampaigns' => array(
				'ext_name' => 'uflagmey/donationcampaigns', 'ext_active' => true,
				'ext_path' => 'ext/uflagmey/donationcampaigns/',
			),
		)));
		$language = new \phpbb\language\language($loader);

		$auth = new forum_scoped_auth(array());

		$user = new \phpbb_mock_user();
		$user->data = array(
			'user_id'				=> 2,
			'user_form_salt'		=> 'test_salt',
			'is_registered'			=> true,
			// confirm_box() validates a confirmation against this.
			'user_last_confirm_key'	=> 'confirm_key_value',
		);
		$user->ip = '127.0.0.1';
		$user->session_id = 'test_session';

		$request = new \phpbb_mock_request();
		$template = $this->template;
		$config = $this->config;
	}

	// ------------------------------------------------------------- request helpers

	/**
	 * A GET-style request (no POST body).
	 *
	 * @param array $get
	 * @return void
	 */
	protected function request(array $get = array())
	{
		global $request;
		$request = new \phpbb_mock_request($get);
		$this->rebuild();
	}

	/**
	 * A POST carrying a valid form key, generated exactly as core generates it.
	 *
	 * @param array $values
	 * @param bool $valid_token
	 * @return void
	 */
	protected function post(array $values, $valid_token = true)
	{
		global $request, $user;

		$creation_time = time() - 60;
		$token = $valid_token
			? sha1($creation_time . $user->data['user_form_salt'] . 'donationcampaigns_campaign')
			: 'a_wrong_token';

		$request = new \phpbb_mock_request(array(), array_merge(array(
			'creation_time'	=> $creation_time,
			'form_token'	=> $token,
			'submit'		=> 'Submit',
		), $values));
		$this->rebuild();
	}

	/**
	 * A POST carrying the enable toggle's form key.
	 *
	 * @param bool $valid_token
	 * @return void
	 */
	protected function post_toggle($valid_token = true)
	{
		global $request, $user;

		$creation_time = time() - 60;
		$token = $valid_token
			? sha1($creation_time . $user->data['user_form_salt'] . 'donationcampaigns_toggle')
			: 'a_wrong_token';

		$request = new \phpbb_mock_request(array(), array(
			'creation_time'	=> $creation_time,
			'form_token'	=> $token,
			'submit'		=> 'Enable',
		));
		$this->rebuild();
	}

	/**
	 * A request carrying a valid confirm_box confirmation.
	 *
	 * @return void
	 */
	protected function confirmed()
	{
		global $request, $user, $language;

		$request = new \phpbb_mock_request(array(), array(
			'confirm'		=> $language->lang('YES'),
			'confirm_uid'	=> $user->data['user_id'],
			'sess'			=> $user->session_id,
			'confirm_key'	=> $user->data['user_last_confirm_key'],
		));
		$this->rebuild();
	}

	/**
	 * Run an action, swallowing the render/exit a confirm_box dialog raises in
	 * the test harness. Used for the "unconfirmed" path, where nothing is written.
	 *
	 * @param callable $action
	 * @return void
	 */
	protected function swallow_dialog(callable $action)
	{
		try
		{
			$action();
		}
		catch (\Throwable $e)
		{
			// confirm_box(false) renders the dialog and, in the harness, unwinds.
		}
	}

	/**
	 * Run a controller action and return the http_exception it raised, or null.
	 *
	 * @param callable $action
	 * @return \phpbb\exception\http_exception|null
	 */
	protected function denial(callable $action)
	{
		try
		{
			$action();
		}
		catch (\phpbb\exception\http_exception $e)
		{
			return $e;
		}

		return null;
	}

	/**
	 * Assert an action produced the uniform not-available denial.
	 *
	 * @param callable $action
	 * @return void
	 */
	protected function assert_denied(callable $action)
	{
		$e = $this->denial($action);

		$this->assertNotNull($e, 'Expected a uniform denial, but none was raised');
		$this->assertSame(404, $e->getStatusCode());
		$this->assertSame('DONATIONCAMPAIGNS_NOT_AVAILABLE', $e->getMessage());
	}
}
