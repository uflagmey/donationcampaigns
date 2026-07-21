<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\repository;

use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;

/**
 * Persistence only. This class holds no validation, no transactions and no
 * business rules, so these tests assert storage behaviour and typing — not
 * whether a campaign is allowed to exist.
 */
class campaign_repository_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var campaign_repository */
	protected $repository;

	/** @var string */
	protected $db_file;

	/** @var string */
	protected $table = 'phpbb_ufdc_campaigns';

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_repo_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema('phpbb_');

		$this->repository = new campaign_repository($this->db, $this->table);

		$this->seed();
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
	 * @param string $prefix
	 * @param bool $with_indexes
	 */
	protected function create_schema($prefix, $with_indexes = true)
	{
		$migration = new m1_initial_schema(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			'',
			'php',
			$prefix
		);

		$schema = $migration->update_schema();

		if (!$with_indexes)
		{
			// phpBB shortens an over-long index name by stripping the board's
			// table prefix, which it derives from CONFIG_TABLE. This test
			// bootstrap pins CONFIG_TABLE to phpbb_config while creating a
			// differently-prefixed table, so the strip does not apply and the
			// name is rejected. On a real board the two always agree, because
			// both come from the same configured prefix.
			//
			// Indexes are irrelevant to what this test checks — that the
			// repository queries the table name it was given — so they are
			// omitted rather than worked around.
			foreach ($schema['add_tables'] as $table => $definition)
			{
				unset($schema['add_tables'][$table]['KEYS']);
			}
		}

		$this->tools->perform_schema_changes($schema);

		// The campaign button's label arrived in m6; the fixture walks the same
		// chain a real board does rather than hand-copying the columns.
		$link_text = new \uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			'',
			'php',
			$prefix
		);
		$this->tools->perform_schema_changes($link_text->update_schema());
	}

	/**
	 * Three campaigns: two enabled on topics 10 and 20, one disabled on 30.
	 */
	protected function seed()
	{
		$rows = array(
			array('topic_id' => 10, 'campaign_title' => 'Server fund', 'target_amount' => 100000, 'collected_amount' => 2500, 'campaign_enabled' => 1),
			array('topic_id' => 20, 'campaign_title' => 'Archive restoration', 'target_amount' => 50000, 'collected_amount' => 0, 'campaign_enabled' => 1),
			array('topic_id' => 30, 'campaign_title' => 'Disabled campaign', 'target_amount' => 1000, 'collected_amount' => 0, 'campaign_enabled' => 0),
		);

		foreach ($rows as $row)
		{
			$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_desc'			=> '',
				'desc_bbcode_uid'		=> '',
				'desc_bbcode_bitfield'	=> '',
				'desc_bbcode_options'	=> 7,
				'show_donor_names'		=> 1,
				'show_donation_count'	=> 1,
				'external_url'			=> '',
				'campaign_created'		=> 1700000000,
				'campaign_updated'		=> 1700000000,
			), $row));
			$this->db->sql_query($sql);
		}
	}

	protected function row_count($where = '1=1')
	{
		$sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE {$where}";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	// ---------------------------------------------------------------- reads

	public function test_find_by_id_returns_the_campaign()
	{
		$campaign = $this->repository->find_by_id(1);

		$this->assertNotNull($campaign);
		$this->assertSame('Server fund', $campaign['campaign_title']);
	}

	/**
	 * Not-found is null, never false and never an empty array. A caller can
	 * then use a single === null check everywhere.
	 */
	public function test_find_by_id_returns_null_when_absent()
	{
		$this->assertNull($this->repository->find_by_id(99999));
	}

	public function test_find_by_topic_id_returns_the_campaign()
	{
		$campaign = $this->repository->find_by_topic_id(20);

		$this->assertNotNull($campaign);
		$this->assertSame('Archive restoration', $campaign['campaign_title']);
	}

	public function test_find_by_topic_id_returns_null_for_a_topic_without_a_campaign()
	{
		$this->assertNull($this->repository->find_by_topic_id(999));
	}

	/**
	 * The repository is persistence only: it returns a disabled campaign just
	 * as it returns an enabled one. Filtering on campaign_enabled is a business
	 * rule and belongs to campaign_service.
	 */
	public function test_find_by_topic_id_returns_disabled_campaigns_too()
	{
		$campaign = $this->repository->find_by_topic_id(30);

		$this->assertNotNull($campaign);
		$this->assertFalse($campaign['campaign_enabled']);
	}

	public function test_exists_for_topic()
	{
		$this->assertTrue($this->repository->exists_for_topic(10));
		$this->assertTrue($this->repository->exists_for_topic(30), 'A disabled campaign still exists');
		$this->assertFalse($this->repository->exists_for_topic(999));
	}

	public function test_find_all_returns_every_campaign()
	{
		$this->assertCount(3, $this->repository->find_all(25, 0));
	}

	public function test_find_all_returns_an_empty_array_when_there_are_none()
	{
		$this->repository->delete_by_ids(array(1, 2, 3));

		$this->assertSame(array(), $this->repository->find_all(25, 0));
	}

	public function test_find_all_honours_limit_and_offset()
	{
		$this->assertCount(2, $this->repository->find_all(2, 0));
		$this->assertCount(1, $this->repository->find_all(2, 2));
	}

	public function test_count_all()
	{
		$this->assertSame(3, $this->repository->count_all());

		$this->repository->delete_by_ids(array(1, 2, 3));

		$this->assertSame(0, $this->repository->count_all());
	}

	// ------------------------------------------------------------ hydration

	/**
	 * Scalars arrive from the database as strings on most drivers. Casting at
	 * the repository boundary means no consumer has to remember to do it.
	 */
	public function test_read_values_are_cast_to_their_intended_php_types()
	{
		$campaign = $this->repository->find_by_id(1);

		$this->assertIsInt($campaign['campaign_id']);
		$this->assertIsInt($campaign['topic_id']);
		$this->assertIsInt($campaign['target_amount']);
		$this->assertIsInt($campaign['collected_amount']);
		$this->assertIsInt($campaign['desc_bbcode_options']);
		$this->assertIsInt($campaign['campaign_created']);
		$this->assertIsInt($campaign['campaign_updated']);

		$this->assertIsBool($campaign['campaign_enabled']);
		$this->assertIsBool($campaign['show_donor_names']);
		$this->assertIsBool($campaign['show_donation_count']);

		$this->assertIsString($campaign['campaign_title']);
		$this->assertIsString($campaign['campaign_desc']);
		$this->assertIsString($campaign['external_url']);
	}

	public function test_money_values_are_integers_not_floats()
	{
		$campaign = $this->repository->find_by_id(1);

		$this->assertIsInt($campaign['target_amount']);
		$this->assertIsInt($campaign['collected_amount']);
		$this->assertSame(100000, $campaign['target_amount']);
		$this->assertSame(2500, $campaign['collected_amount']);
	}

	public function test_find_all_hydrates_every_row()
	{
		foreach ($this->repository->find_all(25, 0) as $campaign)
		{
			$this->assertIsInt($campaign['campaign_id']);
			$this->assertIsBool($campaign['campaign_enabled']);
		}
	}

	// --------------------------------------------------------------- writes

	public function test_insert_returns_the_new_id_and_persists()
	{
		$id = $this->repository->insert(array(
			'topic_id'			=> 40,
			'campaign_title'	=> 'Legal fund',
			'target_amount'		=> 25000,
			'collected_amount'	=> 0,
			'campaign_enabled'	=> 1,
			'campaign_created'	=> 1700000001,
			'campaign_updated'	=> 1700000001,
		));

		$this->assertIsInt($id);
		$this->assertGreaterThan(0, $id);
		$this->assertSame('Legal fund', $this->repository->find_by_id($id)['campaign_title']);
	}

	public function test_update_changes_only_the_named_row()
	{
		$this->repository->update(1, array('campaign_title' => 'Renamed'));

		$this->assertSame('Renamed', $this->repository->find_by_id(1)['campaign_title']);
		$this->assertSame('Archive restoration', $this->repository->find_by_id(2)['campaign_title']);
	}

	public function test_set_collected_amount()
	{
		$this->repository->set_collected_amount(1, 7777);

		$this->assertSame(7777, $this->repository->find_by_id(1)['collected_amount']);
		$this->assertSame(0, $this->repository->find_by_id(2)['collected_amount']);
	}

	// ------------------------------------------------------------- deletion

	public function test_delete_by_ids_removes_only_the_requested_rows()
	{
		$this->repository->delete_by_ids(array(1));

		$this->assertNull($this->repository->find_by_id(1));
		$this->assertNotNull($this->repository->find_by_id(2));
		$this->assertNotNull($this->repository->find_by_id(3));
	}

	public function test_delete_by_ids_handles_multiple_rows()
	{
		$this->repository->delete_by_ids(array(1, 3));

		$this->assertSame(1, $this->row_count());
		$this->assertNotNull($this->repository->find_by_id(2));
	}

	public function test_delete_by_ids_with_an_empty_array_is_a_noop()
	{
		$this->repository->delete_by_ids(array());

		$this->assertSame(3, $this->row_count(), 'An empty id list deleted rows');
	}

	public function test_delete_by_topic_ids_removes_only_the_requested_rows()
	{
		$this->repository->delete_by_topic_ids(array(10, 999));

		$this->assertNull($this->repository->find_by_topic_id(10));
		$this->assertNotNull($this->repository->find_by_topic_id(20));
		$this->assertNotNull($this->repository->find_by_topic_id(30));
	}

	public function test_delete_by_topic_ids_with_an_empty_array_is_a_noop()
	{
		$this->repository->delete_by_topic_ids(array());

		$this->assertSame(3, $this->row_count());
	}

	// ------------------------------------------------------------- cleanup

	public function test_find_campaign_ids_for_topics()
	{
		$ids = $this->repository->find_campaign_ids_for_topics(array(10, 30, 999));

		sort($ids);
		$this->assertSame(array(1, 3), $ids);
	}

	public function test_find_campaign_ids_for_topics_returns_ints()
	{
		foreach ($this->repository->find_campaign_ids_for_topics(array(10, 20)) as $id)
		{
			$this->assertIsInt($id);
		}
	}

	public function test_find_campaign_ids_for_topics_returns_an_empty_array_for_no_matches()
	{
		$this->assertSame(array(), $this->repository->find_campaign_ids_for_topics(array(9998, 9999)));
	}

	public function test_find_campaign_ids_for_topics_with_an_empty_input()
	{
		$this->assertSame(array(), $this->repository->find_campaign_ids_for_topics(array()));
	}

	// ------------------------------------------------------------- security

	/**
	 * Every id is cast to int before reaching SQL, so a string payload cannot
	 * alter the query. These assert no damage AND no error.
	 */
	public function injection_payloads()
	{
		return array(
			array("1 OR 1=1"),
			array("1; DROP TABLE phpbb_ufdc_campaigns"),
			array("1' OR '1'='1"),
			array("' UNION SELECT 1,2,3 --"),
			array("1/**/OR/**/1=1"),
		);
	}

	/**
	 * @dataProvider injection_payloads
	 */
	public function test_read_methods_resist_sql_injection($payload)
	{
		$this->repository->find_by_id($payload);
		$this->repository->find_by_topic_id($payload);
		$this->repository->exists_for_topic($payload);

		$this->assertSame(3, $this->row_count(), 'An injected read payload changed the data');
	}

	/**
	 * A payload is neutralised by the int cast, so "1 OR 1=1" deletes the row
	 * with id 1 and nothing more. The failure this guards against is the
	 * payload matching every row, or dropping the table.
	 *
	 * @dataProvider injection_payloads
	 */
	public function test_delete_methods_resist_sql_injection($payload)
	{
		$this->repository->delete_by_ids(array($payload));

		// Campaign 2 has no id prefix in any payload, so it must always survive.
		$this->assertNotNull(
			$this->repository->find_by_id(2),
			'An injected delete payload matched rows beyond its integer value'
		);
		$this->assertNotNull(
			$this->repository->find_by_id(3),
			'An injected delete payload matched rows beyond its integer value'
		);
		$this->assertGreaterThanOrEqual(
			2,
			$this->row_count(),
			'An injected delete payload removed more rows than its integer value'
		);
	}

	/**
	 * The same, for topic-id deletion.
	 *
	 * @dataProvider injection_payloads
	 */
	public function test_delete_by_topic_ids_resists_sql_injection($payload)
	{
		$this->repository->delete_by_topic_ids(array($payload));

		// No payload casts to 10, 20 or 30, so every row must survive.
		$this->assertSame(
			3,
			$this->row_count(),
			'An injected topic-id payload deleted campaign rows'
		);
	}

	/**
	 * @dataProvider injection_payloads
	 */
	public function test_string_values_are_escaped_on_write($payload)
	{
		$id = $this->repository->insert(array(
			'topic_id'			=> 50,
			'campaign_title'	=> $payload,
			'target_amount'		=> 100,
			'collected_amount'	=> 0,
			'campaign_enabled'	=> 1,
			'campaign_created'	=> 1700000002,
			'campaign_updated'	=> 1700000002,
		));

		// Stored verbatim, and the table survived.
		$this->assertSame($payload, $this->repository->find_by_id($id)['campaign_title']);
		$this->assertSame(4, $this->row_count());
	}

	// ------------------------------------------------------- table prefixes

	/**
	 * The table name is injected, never built from a hard-coded prefix.
	 */
	public function test_it_uses_the_injected_table_name()
	{
		$this->create_schema('custom_', false);

		$repository = new campaign_repository($this->db, 'custom_ufdc_campaigns');

		$id = $repository->insert(array(
			'topic_id'			=> 60,
			'campaign_title'	=> 'Custom prefix campaign',
			'target_amount'		=> 500,
			'collected_amount'	=> 0,
			'campaign_enabled'	=> 1,
			'campaign_created'	=> 1700000003,
			'campaign_updated'	=> 1700000003,
		));

		$this->assertSame('Custom prefix campaign', $repository->find_by_id($id)['campaign_title']);

		// The default-prefix table is untouched.
		$this->assertSame(3, $this->row_count());
		$this->assertNull($this->repository->find_by_topic_id(60));
	}
}
