<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\event\topic_delete_listener;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\description_formatter;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;
use uflagmey\donationcampaigns\tests\service\failing_campaign_repository;
use uflagmey\donationcampaigns\tests\service\recording_driver;

/**
 * The topic-deletion cascade, driven through phpBB's REAL delete_topics().
 *
 * Calling the listener directly would prove only that the listener works. The
 * assumption worth testing is that core actually reaches it — that the event
 * name is right, that it fires on the hard-deletion path, and that the payload
 * carries what we expect. So these tests dispatch through a real
 * \phpbb\event\dispatcher with the listener subscribed, and call core's own
 * function.
 */
class topic_delete_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	/** @var campaign_service */
	protected $service;

	/** @var topic_delete_listener */
	protected $listener;

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

		require_once $phpbb_root_path . 'includes/functions_admin.php';

		$this->db_file = sys_get_temp_dir() . '/ufdc_topic_delete_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema();
		$this->seed();

		$this->campaigns = new campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$this->donations = new donation_repository($this->db, 'phpbb_ufdc_donations');

		$this->service = new campaign_service(
			$this->db,
			$this->campaigns,
			$this->donations,
			new topic_repository($this->db, 'phpbb_topics'),
			new description_formatter()
		);

		$this->listener = new topic_delete_listener($this->service);

		$this->set_phpbb_globals();
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
	 * phpBB's own tables from the 3.0.0 baseline migration, plus the 3.1+
	 * columns the deletion and visibility paths read. Building the whole
	 * migration chain would take minutes per test; this is core's real schema
	 * for every column these code paths actually touch.
	 *
	 * @return void
	 */
	protected function create_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$baseline = $reflection->newInstanceWithoutConstructor();
		$schema = $baseline->update_schema();

		$tables = array();

		foreach ($schema['add_tables'] as $name => $definition)
		{
			// Index names derived from the board prefix are shortened against
			// CONFIG_TABLE, which this harness pins; see campaign_repository_test.
			unset($definition['KEYS']);
			$tables['phpbb_' . $name] = $definition;
		}

		$this->tools->perform_schema_changes(array('add_tables' => $tables));

		$this->tools->perform_schema_changes(array('add_columns' => array(
			'phpbb_topics'	=> array(
				'topic_visibility'			=> array('TINT:3', 1),
				'topic_delete_user'			=> array('UINT', 0),
				'topic_delete_time'			=> array('TIMESTAMP', 0),
				'topic_delete_reason'		=> array('STEXT_UNI', ''),
				'topic_posts_approved'		=> array('UINT', 0),
				'topic_posts_unapproved'	=> array('UINT', 0),
				'topic_posts_softdeleted'	=> array('UINT', 0),
			),
			'phpbb_posts'	=> array(
				'post_visibility'		=> array('TINT:3', 1),
				'post_delete_user'		=> array('UINT', 0),
				'post_delete_time'		=> array('TIMESTAMP', 0),
				'post_delete_reason'	=> array('STEXT_UNI', ''),
			),
			'phpbb_forums'	=> array(
				'forum_posts_approved'		=> array('UINT', 0),
				'forum_posts_unapproved'	=> array('UINT', 0),
				'forum_posts_softdeleted'	=> array('UINT', 0),
				'forum_topics_approved'		=> array('UINT', 0),
				'forum_topics_unapproved'	=> array('UINT', 0),
				'forum_topics_softdeleted'	=> array('UINT', 0),
			),
		)));

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
	 * Topics 10, 20 and 30 in forum 2. Campaign 1 on topic 10 with three
	 * donations; campaign 2 on topic 20 with one. Topic 30 has no campaign,
	 * so a mixed deletion has something harmless in it.
	 *
	 * @return void
	 */
	protected function seed()
	{
		foreach (array(10, 20, 30) as $topic_id)
		{
			$this->db->sql_query('INSERT INTO phpbb_topics ' . $this->db->sql_build_array('INSERT', array(
				'topic_id'			=> $topic_id,
				'forum_id'			=> 2,
				'topic_title'		=> 'Topic ' . $topic_id,
				'topic_poster'		=> 2,
				'topic_time'		=> 1700000000,
				'topic_visibility'	=> 1,
			)));
		}

		$campaigns = array(
			array('campaign_id' => 1, 'topic_id' => 10, 'campaign_title' => 'Server fund', 'target_amount' => 10000, 'collected_amount' => 2500),
			array('campaign_id' => 2, 'topic_id' => 20, 'campaign_title' => 'Archive fund', 'target_amount' => 5000, 'collected_amount' => 700),
		);

		foreach ($campaigns as $campaign)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_desc'			=> '',
				'desc_bbcode_uid'		=> '',
				'desc_bbcode_bitfield'	=> '',
				'desc_bbcode_options'	=> 7,
				'campaign_enabled'		=> 1,
				'show_donor_names'		=> 1,
				'show_donation_count'	=> 1,
				'external_url'			=> '',
				'campaign_created'		=> 1700000000,
				'campaign_updated'		=> 1700000000,
			), $campaign)));
		}

		$donations = array(
			array('campaign_id' => 1, 'donation_amount' => 1000),
			array('campaign_id' => 1, 'donation_amount' => 1200),
			array('campaign_id' => 1, 'donation_amount' => 300),
			array('campaign_id' => 2, 'donation_amount' => 700),
		);

		foreach ($donations as $donation)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'donor_name'		=> 'Donor',
				'donation_time'		=> 1700000100,
				'donation_public'	=> 1,
				'donation_created'	=> 1700000000,
				'donation_updated'	=> 1700000000,
			), $donation)));
		}
	}

	/**
	 * A REAL dispatcher with the listener subscribed, so core reaches it the
	 * way it would on a live board. A mock dispatcher would silently swallow
	 * a wrong event name — exactly the mistake worth catching.
	 *
	 * @return void
	 */
	protected function set_phpbb_globals()
	{
		global $phpbb_root_path, $db, $config, $phpbb_dispatcher, $user, $cache, $auth, $phpbb_container;

		require_once $phpbb_root_path . '../tests/mock/container_builder.php';
		require_once $phpbb_root_path . '../tests/mock/notification_manager.php';

		$db = $this->db;
		$config = new \phpbb\config\config(array('search_type' => '\phpbb\search\fulltext_native'));
		$user = new \phpbb_mock_user();
		$cache = new \phpbb_mock_cache();
		$auth = new \phpbb\auth\auth();

		$dispatcher = new \phpbb\event\dispatcher();
		$dispatcher->addListener(
			'core.delete_topics_before_query',
			array($this->listener, 'purge_campaigns')
		);
		$phpbb_dispatcher = $dispatcher;

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_container->set('notification_manager', new \phpbb_mock_notification_manager());
	}

	/**
	 * @param string $table
	 * @param string $where
	 * @return int
	 */
	protected function count_rows($table, $where = '1=1')
	{
		$result = $this->db->sql_query("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}");
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	protected function assert_no_orphans()
	{
		$sql = 'SELECT COUNT(d.donation_id) AS orphans
			FROM phpbb_ufdc_donations d
			LEFT JOIN phpbb_ufdc_campaigns c ON c.campaign_id = d.campaign_id
			WHERE c.campaign_id IS NULL';
		$result = $this->db->sql_query($sql);
		$orphans = (int) $this->db->sql_fetchfield('orphans');
		$this->db->sql_freeresult($result);

		$this->assertSame(0, $orphans, 'Orphaned donation rows remain after deletion');
	}

	// -------------------------------------------- through core's real path

	public function test_deleting_one_topic_removes_its_campaign_and_donations()
	{
		delete_topics('topic_id', array(10));

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertSame(0, $this->donations->count_by_campaign(1));
		$this->assert_no_orphans();
	}

	public function test_the_topic_itself_is_still_deleted_by_core()
	{
		delete_topics('topic_id', array(10));

		$this->assertSame(0, $this->count_rows('phpbb_topics', 'topic_id = 10'), 'Core deletion was blocked');
	}

	public function test_unrelated_campaigns_and_donations_survive()
	{
		delete_topics('topic_id', array(10));

		$this->assertNotNull($this->campaigns->find_by_id(2));
		$this->assertSame(1, $this->donations->count_by_campaign(2));
		$this->assertSame(700, $this->campaigns->find_by_id(2)['collected_amount']);
	}

	public function test_deleting_a_topic_without_a_campaign_changes_nothing()
	{
		delete_topics('topic_id', array(30));

		$this->assertSame(2, $this->campaigns->count_all());
		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertSame(1, $this->donations->count_by_campaign(2));
	}

	public function test_deleting_several_topics_removes_every_campaign()
	{
		delete_topics('topic_id', array(10, 20));

		$this->assertSame(0, $this->campaigns->count_all());
		$this->assertSame(0, $this->count_rows('phpbb_ufdc_donations'));
		$this->assert_no_orphans();
	}

	public function test_a_mixed_set_removes_only_the_topics_that_had_campaigns()
	{
		delete_topics('topic_id', array(10, 30));

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertNotNull($this->campaigns->find_by_id(2));
		$this->assert_no_orphans();
	}

	public function test_an_unknown_topic_id_is_harmless()
	{
		delete_topics('topic_id', array(99999));

		$this->assertSame(2, $this->campaigns->count_all());
		$this->assertSame(4, $this->count_rows('phpbb_ufdc_donations'));
	}

	public function test_deleting_a_whole_forum_worth_of_topics_leaves_nothing_behind()
	{
		delete_topics('forum_id', array(2));

		$this->assertSame(0, $this->campaigns->count_all());
		$this->assertSame(0, $this->count_rows('phpbb_ufdc_donations'));
		$this->assert_no_orphans();
	}

	// ------------------------------------------------------ soft deletion

	/**
	 * Soft deletion routes through \phpbb\content_visibility and never calls
	 * delete_topics(), so this event never fires. That is required behaviour,
	 * not an accident: a soft-deleted topic can be restored, and restoring it
	 * to find its donation history gone would be unrecoverable data loss.
	 */
	public function test_a_soft_deleted_topic_keeps_its_campaign_and_donations()
	{
		global $phpbb_root_path, $phpEx, $auth, $config, $phpbb_dispatcher, $user;

		// content_visibility type-hints the concrete phpbb\user, so the mock
		// used elsewhere in this class will not do.
		$real_user = new \phpbb\user(
			new \phpbb\language\language(
				new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx)
			),
			'\phpbb\datetime'
		);

		$visibility = new \phpbb\content_visibility(
			$auth,
			$config,
			$phpbb_dispatcher,
			$this->db,
			$real_user,
			$phpbb_root_path,
			$phpEx,
			'phpbb_forums',
			'phpbb_posts',
			'phpbb_topics',
			'phpbb_users'
		);

		$visibility->set_topic_visibility(ITEM_DELETED, 10, 2, 2, time(), 'spam');

		// Guard against a vacuous pass: if the soft delete did not actually
		// happen, the assertions below would hold for the wrong reason.
		$this->assertSame(
			1,
			$this->count_rows('phpbb_topics', 'topic_id = 10 AND topic_visibility = ' . ITEM_DELETED),
			'The topic was not actually soft deleted, so this test proves nothing'
		);

		$this->assertNotNull($this->campaigns->find_by_id(1), 'A soft delete destroyed the campaign');
		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertSame(1, $this->count_rows('phpbb_topics', 'topic_id = 10'), 'The topic row should survive');
	}

	// ---------------------------------------------- listener-level contract

	public function test_the_listener_subscribes_to_the_verified_event()
	{
		$this->assertSame(
			array('core.delete_topics_before_query' => 'purge_campaigns'),
			topic_delete_listener::getSubscribedEvents()
		);
	}

	/**
	 * @param array $topic_ids
	 * @return \phpbb\event\data
	 */
	protected function event(array $topic_ids)
	{
		return new \phpbb\event\data(array(
			'topic_ids'	=> $topic_ids,
			'table_ary'	=> array('phpbb_topics', 'phpbb_bookmarks'),
		));
	}

	public function test_duplicate_topic_ids_are_collapsed()
	{
		$this->listener->purge_campaigns($this->event(array(10, 10, 10)));

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertNotNull($this->campaigns->find_by_id(2));
		$this->assert_no_orphans();
	}

	public function test_an_empty_topic_list_is_a_noop()
	{
		$this->listener->purge_campaigns($this->event(array()));

		$this->assertSame(2, $this->campaigns->count_all());
		$this->assertSame(4, $this->count_rows('phpbb_ufdc_donations'));
	}

	public function unusable_id_data()
	{
		return array(
			'zero'			=> array(0),
			'negative'		=> array(-1),
			'string all'	=> array('all'),
			'empty string'	=> array(''),
			'null'			=> array(null),
			'boolean'		=> array(false),
			'sql fragment'	=> array('1 OR 1=1'),
			'wildcard'		=> array('*'),
		);
	}

	/**
	 * A value that is not a usable topic id must narrow the operation to
	 * nothing, never widen it. "1 OR 1=1" casts to 1 and may legitimately
	 * match topic 1 — which does not exist here — but must never match
	 * everything.
	 *
	 * @dataProvider unusable_id_data
	 */
	public function test_an_unusable_id_never_widens_the_deletion($value)
	{
		$this->listener->purge_campaigns($this->event(array($value)));

		$this->assertSame(2, $this->campaigns->count_all(), 'An unusable id deleted campaigns');
		$this->assertSame(4, $this->count_rows('phpbb_ufdc_donations'));
	}

	public function test_a_non_array_payload_is_ignored()
	{
		$this->listener->purge_campaigns(new \phpbb\event\data(array(
			'topic_ids'	=> 'not an array',
			'table_ary'	=> array(),
		)));

		$this->assertSame(2, $this->campaigns->count_all());
	}

	/**
	 * The listener reads topic_ids and nothing else. table_ary belongs to
	 * core, and adding our tables to it would let core delete donation rows by
	 * topic_id — a column they do not have — or delete campaigns before their
	 * donations.
	 */
	public function test_the_listener_does_not_touch_table_ary()
	{
		$event = $this->event(array(10));
		$before = $event['table_ary'];

		$this->listener->purge_campaigns($event);

		$this->assertSame($before, $event['table_ary']);
	}

	public function test_the_listener_delegates_exactly_once()
	{
		$service = new counting_campaign_service(
			$this->db,
			$this->campaigns,
			$this->donations,
			new topic_repository($this->db, 'phpbb_topics'),
			new description_formatter()
		);

		$listener = new topic_delete_listener($service);
		$listener->purge_campaigns($this->event(array(10, 20)));

		$this->assertSame(1, $service->purge_calls, 'The listener must make one batched call, not one per topic');
		$this->assertSame(array(array(10, 20)), $service->purge_arguments);
	}

	/**
	 * Persistence belongs to repositories and coordination to the service. A
	 * listener that grew a query would bypass both, and the ordering guarantee
	 * with them.
	 */
	public function test_the_listener_contains_no_sql()
	{
		$source = file_get_contents(
			dirname(dirname(__DIR__)) . '/event/topic_delete_listener.php'
		);

		// Strip the docblocks: they discuss the deletion order in prose.
		$code = preg_replace('#/\*.*?\*/#s', '', $source);

		foreach (array('SELECT', 'INSERT', 'UPDATE', 'DELETE', 'sql_query', 'sql_build_array', '$this->db') as $fragment)
		{
			$this->assertStringNotContainsString($fragment, $code, "The listener contains {$fragment}");
		}
	}

	// -------------------------------------------------------------- ordering

	/**
	 * Donations must go before campaigns. The service owns this, but the
	 * cascade is the reason the service exists, so it is asserted on the real
	 * path as well as in isolation.
	 */
	public function test_donations_are_deleted_before_campaigns()
	{
		$recorder = new recording_driver();
		$recorder->sql_connect($this->db_file, '', '', '', '', false, false);

		$service = new campaign_service(
			$recorder,
			new campaign_repository($recorder, 'phpbb_ufdc_campaigns'),
			new donation_repository($recorder, 'phpbb_ufdc_donations'),
			new topic_repository($recorder, 'phpbb_topics'),
			new description_formatter()
		);

		$listener = new topic_delete_listener($service);
		$recorder->forget();

		$listener->purge_campaigns($this->event(array(10)));

		$donations_at = $recorder->first_query_matching('/DELETE FROM phpbb_ufdc_donations/');
		$campaigns_at = $recorder->first_query_matching('/DELETE FROM phpbb_ufdc_campaigns/');

		$recorder->sql_close();

		$this->assertNotNull($donations_at);
		$this->assertNotNull($campaigns_at);
		$this->assertLessThan($campaigns_at, $donations_at);
	}

	// -------------------------------------------------------------- rollback

	public function test_a_failing_donation_delete_rolls_back_and_propagates()
	{
		$service = new campaign_service(
			$this->db,
			$this->campaigns,
			new failing_donation_delete_repository($this->db, 'phpbb_ufdc_donations'),
			new topic_repository($this->db, 'phpbb_topics'),
			new description_formatter()
		);

		$listener = new topic_delete_listener($service);

		try
		{
			$listener->purge_campaigns($this->event(array(10)));
			$this->fail('The listener swallowed a cleanup failure');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('donation delete failed', $e->getMessage());
		}

		$this->assertNotNull($this->campaigns->find_by_id(1));
		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assert_no_orphans();
	}

	public function test_a_failing_campaign_delete_rolls_back_and_propagates()
	{
		$service = new campaign_service(
			$this->db,
			new failing_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			$this->donations,
			new topic_repository($this->db, 'phpbb_topics'),
			new description_formatter()
		);

		$listener = new topic_delete_listener($service);

		try
		{
			$listener->purge_campaigns($this->event(array(10)));
			$this->fail('The listener swallowed a cleanup failure');
		}
		catch (\RuntimeException $e)
		{
			// expected
		}

		$this->assertNotNull($this->campaigns->find_by_id(1), 'The campaign delete was not rolled back');
		$this->assertSame(3, $this->donations->count_by_campaign(1), 'The donation deletes were not rolled back');
		$this->assert_no_orphans();
	}
}
