<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\repository;

use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;
use uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text;
use uflagmey\donationcampaigns\migrations\v10x\m9_display_options;
use uflagmey\donationcampaigns\tests\service\recording_driver;
use uflagmey\donationcampaigns\tests\unit\forum_scoped_auth;

/**
 * Shared fixture for the board-wide campaign list (ADR-018).
 *
 * Every row below exists to be excluded for exactly ONE reason, so each
 * visibility rule can be tested on its own:
 *
 *   forum 2 "Spenden & Hilfe"   (name stored core-escaped)
 *     topic 10  approved        campaign 1  enabled          → listed
 *     topic 11  approved        campaign 2  DISABLED
 *     topic 30  MOVED SHADOW    campaign 4  enabled
 *     topic 40  UNAPPROVED      campaign 5  enabled
 *     topic 50  SOFT-DELETED    campaign 6  enabled
 *     topic 99  (row missing)   campaign 7  enabled, ORPHANED
 *   forum 3 "Intern"
 *     topic 20  approved        campaign 3  enabled          → listed with f_read on 3
 *   forum 4 "Geheim"            PASSWORD-protected
 *     topic 60  approved        campaign 8  enabled          → listed only when unlocked
 *
 * Campaign n is created at 1700000000 + n, so "newest first" is 8, 7, … 1.
 *
 * The visibility SQL is built by a REAL \phpbb\content_visibility over an
 * auth double, so the fragment under test is the one core produces.
 */
abstract class campaign_list_test_case extends \phpbb_test_case
{
	const FORUM_A = 2;
	const FORUM_B = 3;
	const FORUM_LOCKED = 4;

	/** @var recording_driver */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var string */
	protected $db_file;

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_list_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new recording_driver();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema();
		$this->seed();
		$this->db->forget();
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

	protected function create_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$core = $reflection->newInstanceWithoutConstructor()->update_schema();

		$tables = array();
		foreach (array('topics', 'forums') as $name)
		{
			$definition = $core['add_tables'][$name];
			unset($definition['KEYS']);
			$tables['phpbb_' . $name] = $definition;
		}
		$this->tools->perform_schema_changes(array('add_tables' => $tables));

		// topic_visibility replaced topic_approved in 3.1 (softdelete_p1).
		$this->tools->sql_column_add('phpbb_topics', 'topic_visibility', array('TINT:3', 0));

		foreach (array(m1_initial_schema::class, m6_campaign_link_text::class, m9_display_options::class) as $migration_class)
		{
			$migration = new $migration_class(new \phpbb\config\config(array()), $this->db, $this->tools, '', 'php', 'phpbb_');
			$this->tools->perform_schema_changes($migration->update_schema());
		}
	}

	protected function seed()
	{
		$forums = array(
			// Stored the way core stores a forum name: already escaped.
			array('forum_id' => self::FORUM_A, 'forum_name' => utf8_htmlspecialchars('Spenden & Hilfe'), 'forum_password' => ''),
			array('forum_id' => self::FORUM_B, 'forum_name' => 'Intern', 'forum_password' => ''),
			array('forum_id' => self::FORUM_LOCKED, 'forum_name' => 'Geheim', 'forum_password' => '$2y$10$hash'),
		);
		foreach ($forums as $forum)
		{
			$this->db->sql_query('INSERT INTO phpbb_forums ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'forum_desc' => '', 'forum_rules' => '', 'forum_parents' => '', 'forum_image' => '', 'forum_link' => '',
				'forum_last_poster_name' => '', 'forum_last_post_subject' => '', 'forum_last_poster_colour' => '',
			), $forum)));
		}

		$topics = array(
			array(10, self::FORUM_A, 0, ITEM_APPROVED),
			array(11, self::FORUM_A, 0, ITEM_APPROVED),
			array(20, self::FORUM_B, 0, ITEM_APPROVED),
			array(30, self::FORUM_A, 10, ITEM_APPROVED),
			array(40, self::FORUM_A, 0, ITEM_UNAPPROVED),
			array(50, self::FORUM_A, 0, ITEM_DELETED),
			array(60, self::FORUM_LOCKED, 0, ITEM_APPROVED),
		);
		foreach ($topics as $topic)
		{
			$this->insert_topic($topic[0], $topic[1], $topic[2], $topic[3]);
		}

		$campaigns = array(
			array(1, 10, 1),
			array(2, 11, 0),
			array(3, 20, 1),
			array(4, 30, 1),
			array(5, 40, 1),
			array(6, 50, 1),
			array(7, 99, 1),
			array(8, 60, 1),
		);
		foreach ($campaigns as $campaign)
		{
			$this->insert_campaign($campaign[0], $campaign[1], $campaign[2]);
		}
	}

	protected function insert_topic($topic_id, $forum_id, $moved_id = 0, $visibility = ITEM_APPROVED)
	{
		$this->db->sql_query('INSERT INTO phpbb_topics ' . $this->db->sql_build_array('INSERT', array(
			'topic_id'			=> $topic_id,
			'forum_id'			=> $forum_id,
			'topic_moved_id'	=> $moved_id,
			'topic_visibility'	=> $visibility,
			'topic_title'		=> 'Topic ' . $topic_id,
			'topic_poster'		=> 2,
			'topic_time'		=> 1700000000,
		)));
	}

	protected function insert_campaign($campaign_id, $topic_id, $enabled = 1, array $overrides = array())
	{
		$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array_merge(array(
			'campaign_id'			=> $campaign_id,
			'topic_id'				=> $topic_id,
			'campaign_title'		=> 'Campaign ' . $campaign_id,
			'campaign_desc'			=> 'Description ' . $campaign_id,
			'desc_bbcode_uid'		=> '',
			'desc_bbcode_bitfield'	=> '',
			'desc_bbcode_options'	=> 7,
			'target_amount'			=> 10000,
			'collected_amount'		=> 2500,
			'campaign_enabled'		=> $enabled,
			'show_donor_names'		=> 1,
			'show_donation_count'	=> 1,
			'external_url'			=> 'https://example.org/donate',
			'external_link_text'	=> 'Donate',
			'campaign_created'		=> 1700000000 + $campaign_id,
			'campaign_updated'		=> 1700000000 + $campaign_id,
		), $overrides)));
	}

	/**
	 * Add $count further listable campaigns in forum A, for paging.
	 *
	 * @param int $count
	 * @return void
	 */
	protected function seed_many($count)
	{
		for ($i = 0; $i < $count; $i++)
		{
			$topic_id = 1000 + $i;
			$this->insert_topic($topic_id, self::FORUM_A);
			$this->insert_campaign(100 + $i, $topic_id);
		}

		$this->db->forget();
	}

	/**
	 * A real content_visibility over an auth double.
	 *
	 * @param forum_scoped_auth $auth
	 * @return \phpbb\content_visibility
	 */
	protected function content_visibility(forum_scoped_auth $auth)
	{
		global $phpbb_root_path, $phpEx;

		return new \phpbb\content_visibility(
			$auth,
			new \phpbb\config\config(array()),
			new \phpbb_mock_event_dispatcher(),
			$this->db,
			$this->make_user(),
			$phpbb_root_path,
			$phpEx,
			'phpbb_forums',
			'phpbb_posts',
			'phpbb_topics',
			'phpbb_users'
		);
	}

	/**
	 * @return \phpbb\user
	 */
	protected function make_user()
	{
		global $phpbb_root_path, $phpEx;

		$language = new \phpbb\language\language(new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx));

		return new \phpbb\user($language, '\phpbb\datetime');
	}

	/**
	 * Campaign ids of a result, in order.
	 *
	 * @param array $rows
	 * @return int[]
	 */
	protected function ids(array $rows)
	{
		return array_column($rows, 'campaign_id');
	}
}
