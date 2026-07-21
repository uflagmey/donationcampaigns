<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\repository;

use uflagmey\donationcampaigns\repository\topic_repository;

/**
 * Reads from phpBB's CORE topics table.
 *
 * Kept separate from campaign_repository because these queries touch a table
 * the extension does not own, and separate from campaign_service because
 * services contain no persistence. The extension never writes here.
 */
class topic_repository_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var topic_repository */
	protected $repository;

	/** @var string */
	protected $db_file;

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_topics_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_topics_table();
		$this->seed();

		$this->repository = new topic_repository($this->db, 'phpbb_topics');
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
	 * Built from phpBB's own baseline migration, so the schema is genuinely
	 * core's rather than an approximation.
	 */
	protected function create_topics_table()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$baseline = $reflection->newInstanceWithoutConstructor();
		$schema = $baseline->update_schema();

		$this->tools->perform_schema_changes(array('add_tables' => array(
			'phpbb_topics' => $schema['add_tables']['topics'],
		)));
	}

	/**
	 * Forum 2 holds topics 10, 11, 12. Forum 3 holds topic 30. Forum 4 is empty.
	 * Topic 40 in forum 3 is a SHADOW left behind by moving topic 10.
	 */
	protected function seed()
	{
		$topics = array(
			array('topic_id' => 10, 'forum_id' => 2),
			array('topic_id' => 11, 'forum_id' => 2),
			array('topic_id' => 12, 'forum_id' => 2),
			array('topic_id' => 30, 'forum_id' => 3),
			array('topic_id' => 40, 'forum_id' => 3, 'topic_moved_id' => 10),
		);

		foreach ($topics as $topic)
		{
			$sql = 'INSERT INTO phpbb_topics ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'topic_title'		=> 'Topic ' . $topic['topic_id'],
				'topic_poster'		=> 2,
				'topic_time'		=> 1700000000,
				'topic_moved_id'	=> 0,
			), $topic));
			$this->db->sql_query($sql);
		}
	}

	public function test_topic_exists_for_a_real_topic()
	{
		$this->assertTrue($this->repository->topic_exists(10));
		$this->assertTrue($this->repository->topic_exists(30));
	}

	public function test_topic_exists_is_false_for_an_unknown_topic()
	{
		$this->assertFalse($this->repository->topic_exists(99999));
	}

	public function test_topic_exists_is_false_for_a_zero_or_negative_id()
	{
		$this->assertFalse($this->repository->topic_exists(0));
		$this->assertFalse($this->repository->topic_exists(-1));
	}

	public function test_find_topic_ids_by_forum_returns_every_topic()
	{
		$ids = $this->repository->find_topic_ids_by_forum(2);

		sort($ids);
		$this->assertSame(array(10, 11, 12), $ids);
	}

	public function test_find_topic_ids_by_forum_scopes_to_the_requested_forum()
	{
		// Includes topic 40, the shadow: cleanup must still reach it.
		$this->assertSame(array(30, 40), $this->repository->find_topic_ids_by_forum(3));
	}

	/**
	 * An empty forum must return an empty array, never null. The forum-deletion
	 * cascade passes this straight into purge_for_topics().
	 */
	public function test_find_topic_ids_by_forum_returns_an_empty_array_for_an_empty_forum()
	{
		$this->assertSame(array(), $this->repository->find_topic_ids_by_forum(4));
	}

	public function test_find_topic_ids_by_forum_returns_ints()
	{
		foreach ($this->repository->find_topic_ids_by_forum(2) as $id)
		{
			$this->assertIsInt($id);
		}
	}

	public function injection_payloads()
	{
		return array(
			array('2 OR 1=1'),
			array('2; DROP TABLE phpbb_topics'),
			array("2' OR '1'='1"),
			array('2/**/OR/**/1=1'),
		);
	}

	/**
	 * @dataProvider injection_payloads
	 */
	public function test_it_resists_sql_injection($payload)
	{
		$ids = $this->repository->find_topic_ids_by_forum($payload);
		$this->repository->topic_exists($payload);

		// '2 OR 1=1' casts to 2, so forum 2's three topics are the most that
		// can come back — never every topic in the table.
		$this->assertLessThanOrEqual(3, count($ids));

		$sql = 'SELECT COUNT(*) AS total FROM phpbb_topics';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$this->assertSame(5, $total, 'An injected payload modified the topics table');
	}

	/**
	 * The table name is injected rather than assembled from a hard-coded prefix.
	 */
	public function test_it_uses_the_injected_table_name()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$baseline = $reflection->newInstanceWithoutConstructor();
		$schema = $baseline->update_schema();

		$this->tools->perform_schema_changes(array('add_tables' => array(
			'custom_topics' => $schema['add_tables']['topics'],
		)));

		$sql = 'INSERT INTO custom_topics ' . $this->db->sql_build_array('INSERT', array(
			'topic_id'		=> 77,
			'forum_id'		=> 9,
			'topic_title'	=> 'Custom prefix topic',
			'topic_poster'	=> 2,
			'topic_time'	=> 1700000000,
		));
		$this->db->sql_query($sql);

		$repository = new topic_repository($this->db, 'custom_topics');

		$this->assertTrue($repository->topic_exists(77));
		$this->assertSame(array(77), $repository->find_topic_ids_by_forum(9));

		// The default-prefix table is unaffected.
		$this->assertFalse($this->repository->topic_exists(77));
	}

	// --------------------------------------------------------- topic titles

	/**
	 * The ACP campaign list names topics rather than making an administrator
	 * recognise numeric ids. Titles are read here, not joined into the
	 * campaign query, so the public campaign read path stays untouched.
	 */
	public function test_find_titles_by_ids_returns_a_map_of_id_to_title()
	{
		$titles = $this->repository->find_titles_by_ids(array(10, 30));

		$this->assertSame(array(10 => 'Topic 10', 30 => 'Topic 30'), $titles);
	}

	public function test_find_titles_by_ids_keys_are_integers()
	{
		foreach (array_keys($this->repository->find_titles_by_ids(array(10))) as $key)
		{
			$this->assertIsInt($key);
		}
	}

	public function test_find_titles_by_ids_omits_topics_that_do_not_exist()
	{
		$titles = $this->repository->find_titles_by_ids(array(10, 99999));

		$this->assertArrayHasKey(10, $titles);
		$this->assertArrayNotHasKey(99999, $titles, 'A missing topic must be absent, not empty');
	}

	public function test_find_titles_by_ids_with_an_empty_list_is_an_empty_array()
	{
		$this->assertSame(array(), $this->repository->find_titles_by_ids(array()));
	}

	public function test_find_titles_by_ids_collapses_duplicates()
	{
		$this->assertCount(1, $this->repository->find_titles_by_ids(array(10, 10, 10)));
	}

	/**
	 * @dataProvider title_injection_payloads
	 */
	public function test_find_titles_by_ids_resists_sql_injection($payload)
	{
		$titles = $this->repository->find_titles_by_ids(array($payload));

		$this->assertLessThanOrEqual(1, count($titles), 'An injected id matched more than its integer value');
	}

	public function title_injection_payloads()
	{
		return array(
			array('1 OR 1=1'),
			array('1; DROP TABLE phpbb_topics'),
			array("' UNION SELECT 1,2,3 --"),
			array('*'),
		);
	}

	// --------------------------------------------------------- shadow topics

	/**
	 * A shadow is the stub left behind when a topic is moved. phpBB itself
	 * treats one exactly like a topic that does not exist: viewtopic.php
	 * answers 404 "The requested topic does not exist" (verified against
	 * 3.3.17 on the test board). It has no posts and cannot be read.
	 *
	 * So it cannot host a campaign, and this method — whose only caller is
	 * campaign validation — must not report one as an existing topic.
	 */
	public function test_a_shadow_topic_does_not_count_as_an_existing_topic()
	{
		$this->assertFalse(
			$this->repository->topic_exists(40),
			'A moved-topic shadow was accepted as a campaign host'
		);
	}

	public function test_a_real_topic_still_counts()
	{
		$this->assertTrue($this->repository->topic_exists(30));
	}

	/**
	 * Cleanup is the opposite case: a shadow still belongs to its forum, so a
	 * campaign attached to one before this rule existed must still be found
	 * and removed when the forum is deleted.
	 */
	public function test_a_shadow_is_still_listed_for_forum_cleanup()
	{
		$this->assertContains(40, $this->repository->find_topic_ids_by_forum(3));
	}

	public function test_a_shadow_still_has_a_title_for_the_acp()
	{
		$this->assertArrayHasKey(40, $this->repository->find_titles_by_ids(array(40)));
	}
}
