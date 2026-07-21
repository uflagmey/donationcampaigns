<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\event\forum_delete_listener;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\description_formatter;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;
use uflagmey\donationcampaigns\tests\service\failing_campaign_repository;
use uflagmey\donationcampaigns\tests\service\recording_driver;

/**
 * The forum-deletion cascade.
 *
 * Deleting a forum does NOT call delete_topics(), so the topic cascade never
 * sees these topics. The fixture below is built specifically so that an
 * implementation trusting the event's topic_ids payload FAILS: that payload is
 * an attachments join, so it lists only topics that have an attachment, once
 * per attachment, and omits ordinary topics entirely.
 */
class forum_delete_test extends \phpbb_test_case
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

	/** @var forum_delete_listener */
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
		require_once $phpbb_root_path . 'includes/acp/acp_forums.php';

		$this->db_file = sys_get_temp_dir() . '/ufdc_forum_delete_' . getmypid() . '_' . uniqid() . '.sqlite3';

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

		$this->listener = new forum_delete_listener($this->service);

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
	 * THE LOAD-BEARING FIXTURE.
	 *
	 * Forum 2:
	 *   topic 10 — HAS an attachment,      campaign 1, 2 donations
	 *   topic 11 — NO attachment,          campaign 2, 1 donation   <-- the trap
	 *   topic 12 — TWO attachments,        campaign 3, 1 donation   <-- duplicates
	 *   topic 13 — soft deleted, no attachment, no campaign
	 *
	 * Forum 3:
	 *   topic 20 — campaign 4, 1 donation  <-- must survive untouched
	 *
	 * Core's attachment-derived payload for forum 2 is therefore [10, 12, 12]:
	 * incomplete (topic 11 missing) and duplicated. An implementation that
	 * trusts it leaves campaign 2 behind.
	 *
	 * @return void
	 */
	protected function seed()
	{
		$this->insert('phpbb_forums', array('forum_id' => 2, 'forum_name' => 'Fundraising', 'parent_id' => 0, 'left_id' => 1, 'right_id' => 2));
		$this->insert('phpbb_forums', array('forum_id' => 3, 'forum_name' => 'Other', 'parent_id' => 0, 'left_id' => 3, 'right_id' => 4));

		$topics = array(
			array('topic_id' => 10, 'forum_id' => 2, 'topic_visibility' => 1),
			array('topic_id' => 11, 'forum_id' => 2, 'topic_visibility' => 1),
			array('topic_id' => 12, 'forum_id' => 2, 'topic_visibility' => 1),
			// Previously soft deleted. Core deletes it with the rest of the forum.
			array('topic_id' => 13, 'forum_id' => 2, 'topic_visibility' => 2),
			array('topic_id' => 20, 'forum_id' => 3, 'topic_visibility' => 1),
		);

		foreach ($topics as $topic)
		{
			$this->insert('phpbb_topics', array_merge(array(
				'topic_title'	=> 'Topic',
				'topic_poster'	=> 2,
				'topic_time'	=> 1700000000,
			), $topic));
		}

		// One post per topic, so the attachments join has something to match.
		foreach ($topics as $topic)
		{
			$this->insert('phpbb_posts', array(
				'post_id'			=> $topic['topic_id'] * 10,
				'topic_id'			=> $topic['topic_id'],
				'forum_id'			=> $topic['forum_id'],
				'poster_id'			=> 2,
				'post_time'			=> 1700000000,
				'post_visibility'	=> 1,
				'post_postcount'	=> 1,
			));
		}

		// Attachments on topics 10 and 12 only, TWO of them on 12.
		$attachments = array(
			array('attach_id' => 1, 'topic_id' => 10, 'post_msg_id' => 100),
			array('attach_id' => 2, 'topic_id' => 12, 'post_msg_id' => 120),
			array('attach_id' => 3, 'topic_id' => 12, 'post_msg_id' => 120),
		);

		foreach ($attachments as $attachment)
		{
			$this->insert('phpbb_attachments', array_merge(array(
				'in_message'		=> 0,
				'poster_id'			=> 2,
				'is_orphan'			=> 0,
				'physical_filename'	=> 'x',
				'real_filename'		=> 'x',
				'attach_comment'	=> '',
				'extension'			=> 'txt',
				'mimetype'			=> 'text/plain',
				'filesize'			=> 1,
				'filetime'			=> 1700000000,
			), $attachment));
		}

		$campaigns = array(
			array('campaign_id' => 1, 'topic_id' => 10, 'campaign_title' => 'With attachment'),
			array('campaign_id' => 2, 'topic_id' => 11, 'campaign_title' => 'Without attachment'),
			array('campaign_id' => 3, 'topic_id' => 12, 'campaign_title' => 'Two attachments'),
			array('campaign_id' => 4, 'topic_id' => 20, 'campaign_title' => 'Another forum'),
		);

		foreach ($campaigns as $campaign)
		{
			$this->insert('phpbb_ufdc_campaigns', array_merge(array(
				'campaign_desc'			=> '',
				'desc_bbcode_uid'		=> '',
				'desc_bbcode_bitfield'	=> '',
				'desc_bbcode_options'	=> 7,
				'target_amount'			=> 10000,
				'collected_amount'		=> 0,
				'campaign_enabled'		=> 1,
				'show_donor_names'		=> 1,
				'show_donation_count'	=> 1,
				'external_url'			=> '',
				'campaign_created'		=> 1700000000,
				'campaign_updated'		=> 1700000000,
			), $campaign));
		}

		$donations = array(
			array('campaign_id' => 1, 'donation_amount' => 1000),
			array('campaign_id' => 1, 'donation_amount' => 500),
			array('campaign_id' => 2, 'donation_amount' => 700),
			array('campaign_id' => 3, 'donation_amount' => 300),
			array('campaign_id' => 4, 'donation_amount' => 900),
		);

		foreach ($donations as $donation)
		{
			$this->insert('phpbb_ufdc_donations', array_merge(array(
				'donor_name'		=> 'Donor',
				'donation_time'		=> 1700000100,
				'donation_public'	=> 1,
				'donation_created'	=> 1700000000,
				'donation_updated'	=> 1700000000,
			), $donation));
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
			'core.delete_forum_content_before_query',
			array($this->listener, 'purge_campaigns')
		);
		$phpbb_dispatcher = $dispatcher;

		$phpbb_container = new \phpbb_mock_container_builder();
		$phpbb_container->set('notification_manager', new \phpbb_mock_notification_manager());
		$phpbb_container->set('attachment.manager', new stub_attachment_manager());
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

		$this->assertSame(0, $orphans, 'Orphaned donation rows remain after forum deletion');
	}

	/**
	 * @param int $forum_id
	 * @return void
	 */
	protected function delete_forum($forum_id)
	{
		$acp_forums = new \acp_forums();
		$acp_forums->delete_forum_content($forum_id);
	}

	// ------------------------------------------- the payload is not the truth

	/**
	 * Confirms the fixture actually reproduces core's misleading payload. If
	 * this ever stops holding, the tests below stop proving anything.
	 */
	public function test_the_core_payload_really_is_incomplete_and_duplicated()
	{
		$sql = 'SELECT a.topic_id
			FROM phpbb_posts p, phpbb_attachments a
			WHERE p.forum_id = 2
				AND a.in_message = 0
				AND a.topic_id = p.topic_id';
		$result = $this->db->sql_query($sql);

		$payload = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$payload[] = (int) $row['topic_id'];
		}
		$this->db->sql_freeresult($result);

		sort($payload);

		$this->assertSame(array(10, 12, 12), $payload);
		$this->assertNotContains(11, $payload, 'The fixture no longer traps a payload-trusting implementation');
	}

	/**
	 * THE REGRESSION TEST FOR THE PAYLOAD TRAP.
	 *
	 * Campaign 2 sits on topic 11, which has no attachment and is therefore
	 * absent from the event's topic_ids. An implementation that used the
	 * payload leaves it behind. Nothing errors; the row simply survives,
	 * pointing at a topic that no longer exists.
	 *
	 * If this test is ever changed to accommodate an implementation, the
	 * implementation is wrong.
	 */
	public function test_a_campaign_on_a_topic_without_attachments_is_removed()
	{
		$this->delete_forum(2);

		$this->assertNull(
			$this->campaigns->find_by_id(2),
			'The campaign on the attachment-less topic survived. The cleanup is '
			. 'using the event payload instead of resolving the real topic list.'
		);
		$this->assertSame(0, $this->donations->count_by_campaign(2));
	}

	public function test_duplicated_payload_ids_cause_no_harm()
	{
		$this->delete_forum(2);

		// Campaign 3 is on topic 12, which appears twice in the payload.
		$this->assertNull($this->campaigns->find_by_id(3));
		$this->assertSame(0, $this->donations->count_by_campaign(3));
	}

	// ------------------------------------------ through core's real ACP path

	public function test_deleting_a_forum_removes_every_campaign_in_it()
	{
		$this->delete_forum(2);

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertNull($this->campaigns->find_by_id(2));
		$this->assertNull($this->campaigns->find_by_id(3));
		$this->assert_no_orphans();
	}

	public function test_deleting_a_forum_removes_every_donation_in_it()
	{
		$this->delete_forum(2);

		$this->assertSame(1, $this->count_rows('phpbb_ufdc_donations'), 'Only the other forum\'s donation should remain');
		$this->assertSame(1, $this->donations->count_by_campaign(4));
	}

	public function test_another_forum_is_untouched()
	{
		$this->delete_forum(2);

		$this->assertNotNull($this->campaigns->find_by_id(4));
		$this->assertSame(1, $this->donations->count_by_campaign(4));
		$this->assertSame(1, $this->count_rows('phpbb_topics', 'topic_id = 20'));
	}

	public function test_core_still_deletes_the_forum_content()
	{
		$this->delete_forum(2);

		$this->assertSame(0, $this->count_rows('phpbb_topics', 'forum_id = 2'), 'Core deletion was blocked');
	}

	/**
	 * A previously soft-deleted topic is hard deleted along with its forum;
	 * there is nothing left to restore it into. It carries no campaign here,
	 * so the assertion is that its presence breaks nothing.
	 */
	public function test_a_previously_soft_deleted_topic_does_not_disturb_cleanup()
	{
		$this->assertSame(1, $this->count_rows('phpbb_topics', 'topic_id = 13 AND topic_visibility = 2'));

		$this->delete_forum(2);

		$this->assertSame(0, $this->count_rows('phpbb_topics', 'topic_id = 13'));
		$this->assert_no_orphans();
	}

	public function test_a_forum_with_no_topics_is_a_noop()
	{
		$this->insert('phpbb_forums', array('forum_id' => 9, 'forum_name' => 'Empty', 'parent_id' => 0, 'left_id' => 5, 'right_id' => 6));

		$this->delete_forum(9);

		$this->assertSame(4, $this->campaigns->count_all());
		$this->assertSame(5, $this->count_rows('phpbb_ufdc_donations'));
	}

	public function test_a_forum_whose_topics_have_no_campaigns_is_a_noop()
	{
		$this->insert('phpbb_forums', array('forum_id' => 9, 'forum_name' => 'Plain', 'parent_id' => 0, 'left_id' => 5, 'right_id' => 6));
		$this->insert('phpbb_topics', array(
			'topic_id' => 90, 'forum_id' => 9, 'topic_title' => 'T', 'topic_poster' => 2,
			'topic_time' => 1700000000, 'topic_visibility' => 1,
		));

		$this->delete_forum(9);

		$this->assertSame(4, $this->campaigns->count_all());
		$this->assertSame(5, $this->count_rows('phpbb_ufdc_donations'));
	}

	public function test_an_unknown_forum_is_a_noop()
	{
		$this->delete_forum(9999);

		$this->assertSame(4, $this->campaigns->count_all());
		$this->assertSame(5, $this->count_rows('phpbb_ufdc_donations'));
	}

	// ---------------------------------------------- listener-level contract

	public function test_the_listener_subscribes_to_the_verified_event()
	{
		$this->assertSame(
			array('core.delete_forum_content_before_query' => 'purge_campaigns'),
			forum_delete_listener::getSubscribedEvents()
		);
	}

	/**
	 * A payload shaped like core's, including the misleading topic_ids.
	 *
	 * @param mixed $forum_id
	 * @param array $topic_ids
	 * @return \phpbb\event\data
	 */
	protected function event($forum_id, array $topic_ids = array(10, 12, 12))
	{
		return new \phpbb\event\data(array(
			'forum_id'		=> $forum_id,
			'topic_ids'		=> $topic_ids,
			'table_ary'		=> array('phpbb_topics', 'phpbb_posts'),
			'post_counts'	=> array(2 => 5),
		));
	}

	/**
	 * The listener must ask the service for a FORUM, not hand it a topic list.
	 * That is what keeps the resolution inside topic_repository where it can
	 * be correct.
	 */
	public function test_the_listener_delegates_once_using_only_the_forum_id()
	{
		$service = new counting_forum_campaign_service(
			$this->db,
			$this->campaigns,
			$this->donations,
			new topic_repository($this->db, 'phpbb_topics'),
			new description_formatter()
		);

		$listener = new forum_delete_listener($service);
		$listener->purge_campaigns($this->event(2));

		$this->assertSame(1, $service->forum_calls);
		$this->assertSame(array(2), $service->forum_arguments);
		$this->assertSame(0, $service->topic_calls_from_outside, 'The listener passed a topic list instead of a forum id');
	}

	/**
	 * An empty or absent topic_ids must change nothing: the listener does not
	 * read it at all.
	 */
	public function test_the_listener_ignores_the_topic_ids_payload_entirely()
	{
		$this->listener->purge_campaigns($this->event(2, array()));

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertNull($this->campaigns->find_by_id(2));
		$this->assertNull($this->campaigns->find_by_id(3));
		$this->assertNotNull($this->campaigns->find_by_id(4));
	}

	public function test_a_misleading_topic_ids_payload_does_not_widen_the_cleanup()
	{
		// A payload naming a topic in ANOTHER forum must not remove it.
		$this->listener->purge_campaigns($this->event(2, array(20, 20, 20)));

		$this->assertNotNull($this->campaigns->find_by_id(4), 'A payload topic id from another forum was deleted');
	}

	public function unusable_forum_id_data()
	{
		return array(
			'zero'			=> array(0),
			'negative'		=> array(-1),
			'string all'	=> array('all'),
			'empty string'	=> array(''),
			'null'			=> array(null),
			'sql fragment'	=> array('1 OR 1=1'),
			'wildcard'		=> array('*'),
		);
	}

	/**
	 * @dataProvider unusable_forum_id_data
	 */
	public function test_an_unusable_forum_id_never_widens_the_cleanup($forum_id)
	{
		$this->listener->purge_campaigns($this->event($forum_id));

		$this->assertSame(4, $this->campaigns->count_all(), 'An unusable forum id deleted campaigns');
		$this->assertSame(5, $this->count_rows('phpbb_ufdc_donations'));
	}

	public function test_the_listener_does_not_touch_table_ary()
	{
		$event = $this->event(2);
		$before = $event['table_ary'];

		$this->listener->purge_campaigns($event);

		$this->assertSame($before, $event['table_ary']);
	}

	public function test_the_listener_contains_no_sql_or_repository_access()
	{
		$source = file_get_contents(
			dirname(dirname(__DIR__)) . '/event/forum_delete_listener.php'
		);

		// Strip docblocks: they quote core's payload query in prose.
		$code = preg_replace('#/\*.*?\*/#s', '', $source);

		$forbidden = array(
			'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'sql_query', 'sql_build_array',
			'$this->db', 'repository', 'topic_repository',
		);

		foreach ($forbidden as $fragment)
		{
			$this->assertStringNotContainsString($fragment, $code, "The listener contains {$fragment}");
		}
	}

	/**
	 * A denormalised forum_id on the campaigns table would make this cascade a
	 * one-liner and would have to be kept correct on every topic move, forever.
	 * The schema deliberately has no such column.
	 */
	public function test_the_campaigns_table_has_no_forum_id_column()
	{
		$this->assertFalse(
			$this->tools->sql_column_exists('phpbb_ufdc_campaigns', 'forum_id'),
			'A denormalised forum_id would need maintaining on every topic move'
		);
	}

	// -------------------------------------------------------------- ordering

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

		$listener = new forum_delete_listener($service);
		$recorder->forget();

		$listener->purge_campaigns($this->event(2));

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

		$listener = new forum_delete_listener($service);

		try
		{
			$listener->purge_campaigns($this->event(2));
			$this->fail('The listener swallowed a cleanup failure');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('donation delete failed', $e->getMessage());
		}

		$this->assertSame(4, $this->campaigns->count_all());
		$this->assertSame(5, $this->count_rows('phpbb_ufdc_donations'));
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

		$listener = new forum_delete_listener($service);

		try
		{
			$listener->purge_campaigns($this->event(2));
			$this->fail('The listener swallowed a cleanup failure');
		}
		catch (\RuntimeException $e)
		{
			// expected
		}

		$this->assertSame(4, $this->campaigns->count_all(), 'The campaign delete was not rolled back');
		$this->assertSame(5, $this->count_rows('phpbb_ufdc_donations'), 'The donation deletes were not rolled back');
		$this->assert_no_orphans();
	}

	// ------------------------------------------------- independence of paths

	/**
	 * The two cascades must not depend on one another. Forum deletion never
	 * calls delete_topics(), so a listener subscribed only to the topic event
	 * would leave every campaign in the forum behind.
	 */
	public function test_forum_deletion_does_not_fire_the_topic_deletion_event()
	{
		global $phpbb_dispatcher;

		$fired = false;
		$phpbb_dispatcher->addListener('core.delete_topics_before_query', function () use (&$fired) {
			$fired = true;
		});

		$this->delete_forum(2);

		$this->assertFalse(
			$fired,
			'Forum deletion reached delete_topics() after all — the two cascades may no longer need to be separate'
		);
	}
}
