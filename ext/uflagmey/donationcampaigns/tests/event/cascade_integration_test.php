<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\event\topic_delete_listener;
use uflagmey\donationcampaigns\event\forum_delete_listener;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\description_formatter;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;

/**
 * The remaining ways phpBB destroys topics, driven through core's own code.
 *
 * The topic and forum listeners were each tested against their own event. What
 * this file establishes is the shape of the whole surface: WHICH core
 * operations reach a listener at all.
 *
 * Verified against 3.3.17 source and exercised below, these all funnel into
 * delete_topics() and therefore into core.delete_topics_before_query:
 *
 *   prune()                    ACP and MCP pruning        functions_admin:2443
 *   auto_prune()               scheduled pruning          -> prune()
 *   delete_posts()             a topic emptied of posts   functions_admin:1182
 *   user_delete()              with "delete posts"        functions_user:672
 *
 * Forum deletion is the ONE exception — it removes topics with a direct
 * DELETE and needs its own listener. That asymmetry is the reason ADR-007
 * exists, and the last test here pins it.
 */
class cascade_integration_test extends \phpbb_test_case
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
		require_once $phpbb_root_path . 'includes/functions_user.php';
		require_once $phpbb_root_path . 'includes/acp/acp_forums.php';

		$this->db_file = sys_get_temp_dir() . '/ufdc_cascade_' . getmypid() . '_' . uniqid() . '.sqlite3';

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
	 * @return void
	 */
	protected function create_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$schema = $reflection->newInstanceWithoutConstructor()->update_schema();

		$tables = array();

		foreach ($schema['add_tables'] as $name => $definition)
		{
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
				// auto_prune() writes these back; they postdate the baseline.
				'prune_next'				=> array('TIMESTAMP', 0),
				'prune_shadow_next'			=> array('TIMESTAMP', 0),
				'prune_shadow_days'			=> array('UINT', 0),
				'prune_shadow_freq'			=> array('UINT', 0),
				'enable_shadow_prune'		=> array('BOOL', 0),
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
	 * Forum 2 with topics 10 (old) and 11 (recent), each with one post and a
	 * campaign carrying donations. Forum 3 is a SUBFORUM of 2, holding topic
	 * 12 with its own campaign. Forum 9 is unrelated and must survive
	 * everything.
	 *
	 * @return void
	 */
	protected function seed()
	{
		$forums = array(
			array('forum_id' => 2, 'forum_name' => 'Parent', 'parent_id' => 0, 'left_id' => 1, 'right_id' => 4),
			array('forum_id' => 3, 'forum_name' => 'Child', 'parent_id' => 2, 'left_id' => 2, 'right_id' => 3),
			array('forum_id' => 9, 'forum_name' => 'Unrelated', 'parent_id' => 0, 'left_id' => 5, 'right_id' => 6),
		);

		foreach ($forums as $forum)
		{
			$this->insert('phpbb_forums', $forum);
		}

		$this->insert('phpbb_users', array(
			'user_id' => 7, 'username' => 'donor_author', 'username_clean' => 'donor_author',
			'user_permissions' => '', 'user_sig' => '', 'user_occ' => '', 'user_interests' => '',
		));

		// topic_time is what prune() compares against.
		$topics = array(
			array('topic_id' => 10, 'forum_id' => 2, 'topic_time' => 1000, 'topic_last_post_time' => 1000),
			array('topic_id' => 11, 'forum_id' => 2, 'topic_time' => 2000000000, 'topic_last_post_time' => 2000000000),
			array('topic_id' => 12, 'forum_id' => 3, 'topic_time' => 1000, 'topic_last_post_time' => 1000),
			array('topic_id' => 90, 'forum_id' => 9, 'topic_time' => 1000, 'topic_last_post_time' => 1000),
		);

		foreach ($topics as $topic)
		{
			$this->insert('phpbb_topics', array_merge(array(
				'topic_title'		=> 'Topic',
				'topic_poster'		=> 7,
				'topic_visibility'	=> 1,
				'topic_moved_id'	=> 0,
				'topic_posts_approved' => 1,
			), $topic));
		}

		foreach ($topics as $topic)
		{
			$this->insert('phpbb_posts', array(
				'post_id'			=> $topic['topic_id'] * 10,
				'topic_id'			=> $topic['topic_id'],
				'forum_id'			=> $topic['forum_id'],
				'poster_id'			=> 7,
				'post_time'			=> $topic['topic_time'],
				'post_visibility'	=> 1,
				'post_postcount'	=> 1,
			));
		}

		$campaigns = array(
			array('campaign_id' => 1, 'topic_id' => 10),
			array('campaign_id' => 2, 'topic_id' => 11),
			array('campaign_id' => 3, 'topic_id' => 12),
			array('campaign_id' => 9, 'topic_id' => 90),
		);

		foreach ($campaigns as $campaign)
		{
			$this->insert('phpbb_ufdc_campaigns', array_merge(array(
				'campaign_title' => 'Campaign', 'campaign_desc' => '',
				'desc_bbcode_uid' => '', 'desc_bbcode_bitfield' => '', 'desc_bbcode_options' => 7,
				'target_amount' => 10000, 'collected_amount' => 500, 'campaign_enabled' => 1,
				'show_donor_names' => 1, 'show_donation_count' => 1, 'external_url' => '',
				'campaign_created' => 1000, 'campaign_updated' => 1000,
			), $campaign));
		}

		foreach (array(1, 2, 3, 9) as $campaign_id)
		{
			$this->insert('phpbb_ufdc_donations', array(
				'campaign_id' => $campaign_id, 'donation_amount' => 500, 'donor_name' => 'Donor',
				'donation_time' => 1000, 'donation_public' => 1,
				'donation_created' => 1000, 'donation_updated' => 1000,
			));
		}
	}

	/**
	 * @param string $table
	 * @param array $row
	 * @return void
	 */
	protected function insert($table, array $row)
	{
		$this->db->sql_query('INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', $row));
	}

	/**
	 * A REAL dispatcher with BOTH listeners subscribed, so whichever path core
	 * takes, the extension responds the way it would on a live board.
	 *
	 * @return void
	 */
	protected function set_phpbb_globals()
	{
		global $phpbb_root_path, $db, $config, $phpbb_dispatcher, $user, $cache, $auth, $phpbb_container, $phpbb_log;

		require_once $phpbb_root_path . '../tests/mock/container_builder.php';
		require_once $phpbb_root_path . '../tests/mock/notification_manager.php';

		$db = $this->db;
		$config = new \phpbb\config\config(array(
			'search_type'		=> '\phpbb\search\fulltext_native',
			'num_posts'			=> 4,
			'num_topics'		=> 4,
		));
		$user = new \phpbb_mock_user();
		// delete_forum() reads $user->data['user_id'] for its log entry.
		$user->data = array('user_id' => 2, 'user_ip' => '127.0.0.1');
		$user->ip = '127.0.0.1';
		// phpBB's real dummy driver, not the test mock: the mock returns false
		// from a cache-wrapped sql_query(), which makes auto_prune() believe
		// the forum does not exist and silently do nothing.
		$cache = new \phpbb\cache\driver\dummy();
		$auth = new \phpbb\auth\auth();
		// delete_forum() writes an admin log entry.
		$phpbb_log = new \uflagmey\donationcampaigns\tests\acp\recording_log();

		$dispatcher = new \phpbb\event\dispatcher();
		$dispatcher->addListener('core.delete_topics_before_query', array(new topic_delete_listener($this->service), 'purge_campaigns'));
		$dispatcher->addListener('core.delete_forum_content_before_query', array(new forum_delete_listener($this->service), 'purge_campaigns'));
		$phpbb_dispatcher = $dispatcher;

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_container->set('notification_manager', new \phpbb_mock_notification_manager());
		$phpbb_container->set('attachment.manager', new stub_attachment_manager());
		$phpbb_container->set('text_formatter.utils', new stub_text_formatter_utils());
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

		$this->assertSame(0, $orphans, 'Donation rows were orphaned');
	}

	/**
	 * The unrelated forum, topic, campaign and donation must survive every
	 * scenario in this file.
	 *
	 * @return void
	 */
	protected function assert_unrelated_survives()
	{
		$this->assertNotNull($this->campaigns->find_by_id(9), 'An unrelated campaign was destroyed');
		$this->assertSame(1, $this->donations->count_by_campaign(9));
		$this->assertSame(1, $this->count_rows('phpbb_topics', 'topic_id = 90'));
		$this->assertSame(1, $this->count_rows('phpbb_forums', 'forum_id = 9'));
	}

	// ------------------------------------------------------------- pruning

	/**
	 * ACP and MCP pruning both call prune(), which ends in delete_topics().
	 */
	public function test_pruning_a_forum_removes_the_campaigns_of_pruned_topics()
	{
		// Everything last posted before this date goes.
		prune(2, 'posted', 1000000, 0, true);

		$this->assertNull($this->campaigns->find_by_id(1), 'A pruned topic kept its campaign');
		$this->assertSame(0, $this->donations->count_by_campaign(1));
		$this->assert_no_orphans();
	}

	public function test_pruning_leaves_recent_topics_and_their_campaigns_alone()
	{
		prune(2, 'posted', 1000000, 0, true);

		$this->assertNotNull($this->campaigns->find_by_id(2), 'A recent topic lost its campaign');
		$this->assertSame(1, $this->count_rows('phpbb_topics', 'topic_id = 11'));
		$this->assert_unrelated_survives();
	}

	/**
	 * auto_prune() is the scheduled path. It delegates to prune(), so the
	 * cascade is the same one — asserted rather than assumed.
	 */
	public function test_scheduled_pruning_reaches_the_same_cascade()
	{
		auto_prune(2, 'posted', 0, 1, 1, false);

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assert_no_orphans();
	}

	// -------------------------------------------- posts emptying their topic

	/**
	 * Deleting the last post of a topic makes core hard-delete the topic, via
	 * delete_topics() at functions_admin.php:1182. The campaign has to go with
	 * it — the topic it was attached to no longer exists.
	 */
	public function test_deleting_the_last_post_of_a_topic_removes_its_campaign()
	{
		delete_posts('topic_id', array(10));

		$this->assertSame(0, $this->count_rows('phpbb_topics', 'topic_id = 10'), 'Core did not remove the emptied topic');
		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertSame(0, $this->donations->count_by_campaign(1));
		$this->assert_no_orphans();
		$this->assert_unrelated_survives();
	}

	public function test_deleting_a_post_from_one_topic_leaves_other_campaigns_alone()
	{
		delete_posts('topic_id', array(10));

		$this->assertNotNull($this->campaigns->find_by_id(2));
		$this->assertNotNull($this->campaigns->find_by_id(3));
	}

	// -------------------------------------------------------- user deletion

	/**
	 * Deleting a user with the "delete posts" option calls
	 * delete_posts('poster_id', …), which empties their topics and hard-deletes
	 * them — so their campaigns cascade too.
	 */
	public function test_deleting_a_user_and_their_posts_removes_the_affected_campaigns()
	{
		delete_posts('poster_id', array(7));

		$this->assertSame(0, $this->count_rows('phpbb_topics', 'topic_id IN (10, 11, 12)'));
		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertNull($this->campaigns->find_by_id(2));
		$this->assertNull($this->campaigns->find_by_id(3));
		$this->assert_no_orphans();
	}

	// ------------------------------------------------ sub-forum orchestration

	/**
	 * Deleting a parent forum with "delete subforums" removes each forum's
	 * content in turn, so delete_forum_content() runs once per forum and the
	 * forum listener fires once per forum. Both campaigns must go.
	 */
	public function test_deleting_a_parent_forum_with_its_subforum_removes_both_campaigns()
	{
		$acp_forums = new \acp_forums();
		$acp_forums->delete_forum(2, 'delete', 'delete');

		$this->assertNull($this->campaigns->find_by_id(1), 'The parent forum kept a campaign');
		$this->assertNull($this->campaigns->find_by_id(2));
		$this->assertNull($this->campaigns->find_by_id(3), 'The SUBFORUM kept a campaign');
		$this->assert_no_orphans();
		$this->assert_unrelated_survives();
	}

	public function test_deleting_only_the_subforum_leaves_the_parent_alone()
	{
		$acp_forums = new \acp_forums();
		$acp_forums->delete_forum(3, 'delete', 'delete');

		$this->assertNull($this->campaigns->find_by_id(3));
		$this->assertNotNull($this->campaigns->find_by_id(1), 'The parent forum lost a campaign');
		$this->assertNotNull($this->campaigns->find_by_id(2));
		$this->assert_no_orphans();
	}

	// ------------------------------------------------------- the whole life

	/**
	 * A campaign from creation to destruction, through the paths a board
	 * actually takes, with the invariant checked at every step.
	 */
	public function test_a_campaign_survives_until_its_topic_does_not()
	{
		// Still there after an unrelated forum is emptied.
		$acp_forums = new \acp_forums();
		$acp_forums->delete_forum_content(9);

		$this->assertNotNull($this->campaigns->find_by_id(1));
		$this->assert_no_orphans();

		// Still there after a recent-topic prune that does not match it.
		prune(2, 'posted', 500, 0, true);
		$this->assertNotNull($this->campaigns->find_by_id(1));

		// Gone once its own topic is deleted.
		delete_topics('topic_id', array(10));

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertSame(0, $this->donations->count_by_campaign(1));
		$this->assert_no_orphans();
	}

	/**
	 * The architectural asymmetry, pinned: forum deletion does NOT reach the
	 * topic event, which is exactly why a second listener exists.
	 */
	public function test_forum_deletion_still_bypasses_the_topic_event()
	{
		global $phpbb_dispatcher;

		$fired = false;
		$phpbb_dispatcher->addListener('core.delete_topics_before_query', function () use (&$fired) {
			$fired = true;
		});

		$acp_forums = new \acp_forums();
		$acp_forums->delete_forum_content(3);

		$this->assertFalse($fired, 'Forum deletion now reaches delete_topics(); the two listeners may be redundant');
		$this->assertNull($this->campaigns->find_by_id(3), 'The forum listener did not clean up');
	}
}
