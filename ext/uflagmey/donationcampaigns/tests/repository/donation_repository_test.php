<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\repository;

use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;

/**
 * Persistence only. These tests assert storage behaviour and typing, not
 * whether a donation is allowed to exist — that is donation_service's job
 * from task 10 onwards.
 *
 * The two rules this class exists to pin:
 *   - sum_by_campaign() returns int 0, never null, when nothing matches
 *   - a non-public donation still counts towards the sum and the count;
 *     donation_public governs name visibility only
 */
class donation_repository_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var donation_repository */
	protected $repository;

	/** @var string */
	protected $db_file;

	/** @var string */
	protected $table = 'phpbb_ufdc_donations';

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_donation_repo_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema('phpbb_');

		$this->repository = new donation_repository($this->db, $this->table);

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
			// See campaign_repository_test::create_schema() for why: phpBB
			// shortens an over-long index name using the prefix it derives from
			// CONFIG_TABLE, which this bootstrap pins to phpbb_config. Indexes
			// are irrelevant to what is asserted here.
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
	 * Campaign 1 has three donations summing to 2500, one of them non-public.
	 * Campaign 2 has none, so the empty-sum case is covered by real data.
	 */
	protected function seed()
	{
		$rows = array(
			array('donation_amount' => 1000, 'donor_name' => 'Anna M.', 'donation_time' => 1700000100, 'donation_public' => 1),
			array('donation_amount' => 1200, 'donor_name' => 'Bernd K.', 'donation_time' => 1700000200, 'donation_public' => 0),
			array('donation_amount' => 300, 'donor_name' => '', 'donation_time' => 1700000300, 'donation_public' => 1),
		);

		foreach ($rows as $row)
		{
			$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_id'		=> 1,
				'donation_created'	=> $row['donation_time'],
				'donation_updated'	=> $row['donation_time'],
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

	// -------------------------------------------------------------- the total

	public function test_sum_by_campaign()
	{
		// 1000 + 1200 + 300. The non-public donation is included.
		$this->assertSame(2500, $this->repository->sum_by_campaign(1));
	}

	/**
	 * SUM() over zero rows is NULL in every supported database. A null reaching
	 * collected_amount would render as an empty progress bar rather than a
	 * zeroed one, so the cast is part of the contract, not an implementation
	 * detail.
	 */
	public function test_sum_by_campaign_with_no_donations_is_integer_zero()
	{
		$sum = $this->repository->sum_by_campaign(2);

		$this->assertSame(0, $sum);
		$this->assertIsInt($sum);
		$this->assertNotNull($sum);
	}

	public function test_sum_by_campaign_is_an_int_not_a_float()
	{
		$this->assertIsInt($this->repository->sum_by_campaign(1));
	}

	public function test_sum_by_campaign_ignores_other_campaigns()
	{
		$this->repository->insert($this->donation(array('campaign_id' => 2, 'donation_amount' => 9999)));

		$this->assertSame(2500, $this->repository->sum_by_campaign(1));
		$this->assertSame(9999, $this->repository->sum_by_campaign(2));
	}

	// -------------------------------------------------------------- the count

	public function test_count_by_campaign_counts_non_public_donations()
	{
		$this->assertSame(3, $this->repository->count_by_campaign(1));
	}

	public function test_count_by_campaign_with_none_is_zero()
	{
		$this->assertSame(0, $this->repository->count_by_campaign(2));
	}

	// ----------------------------------------------------------------- reads

	public function test_find_by_id_returns_the_donation()
	{
		$donation = $this->repository->find_by_id(1);

		$this->assertNotNull($donation);
		$this->assertSame('Anna M.', $donation['donor_name']);
	}

	public function test_find_by_id_returns_null_when_absent()
	{
		$this->assertNull($this->repository->find_by_id(99999));
	}

	public function test_find_by_campaign_returns_every_row()
	{
		$this->assertCount(3, $this->repository->find_by_campaign(1));
	}

	/**
	 * Visibility is chosen by calling a different method, not by passing a
	 * boolean. The non-public donation is absent here and present above.
	 */
	public function test_find_public_by_campaign_returns_only_public_rows()
	{
		$rows = $this->repository->find_public_by_campaign(1, 25);

		$this->assertCount(2, $rows);

		foreach ($rows as $row)
		{
			$this->assertTrue($row['donation_public']);
			$this->assertNotSame('Bernd K.', $row['donor_name']);
		}
	}

	/**
	 * Newest first, which is the order the donor list is rendered in. The
	 * donation_id tie-break makes the order total, so pagination cannot show
	 * or skip the same row twice when two donations share a timestamp.
	 */
	public function test_find_by_campaign_orders_by_time_descending()
	{
		$rows = $this->repository->find_by_campaign(1);

		$this->assertSame(1700000300, $rows[0]['donation_time']);
		$this->assertSame(1700000200, $rows[1]['donation_time']);
		$this->assertSame(1700000100, $rows[2]['donation_time']);
	}

	public function test_find_by_campaign_breaks_time_ties_by_id_descending()
	{
		$first = $this->repository->insert($this->donation(array('donation_time' => 1700000900)));
		$second = $this->repository->insert($this->donation(array('donation_time' => 1700000900)));

		$rows = $this->repository->find_by_campaign(1);

		$this->assertSame($second, $rows[0]['donation_id']);
		$this->assertSame($first, $rows[1]['donation_id']);
	}

	public function test_find_public_by_campaign_honours_the_limit()
	{
		$this->assertCount(1, $this->repository->find_public_by_campaign(1, 1));
		$this->assertCount(2, $this->repository->find_public_by_campaign(1, 25));
	}

	public function test_find_by_campaign_returns_an_empty_array_when_there_are_none()
	{
		$this->assertSame(array(), $this->repository->find_by_campaign(2));
	}

	public function test_find_public_by_campaign_returns_an_empty_array_when_there_are_none()
	{
		$this->assertSame(array(), $this->repository->find_public_by_campaign(2, 25));
	}

	// -------------------------------------------------------------- hydration

	public function test_read_values_are_cast_to_their_intended_php_types()
	{
		$donation = $this->repository->find_by_id(1);

		$this->assertIsInt($donation['donation_id']);
		$this->assertIsInt($donation['campaign_id']);
		$this->assertIsInt($donation['donation_amount']);
		$this->assertIsInt($donation['donation_time']);
		$this->assertIsInt($donation['donation_created']);
		$this->assertIsInt($donation['donation_updated']);

		$this->assertIsBool($donation['donation_public']);

		$this->assertIsString($donation['donor_name']);
	}

	public function test_money_values_are_integers_not_floats()
	{
		$donation = $this->repository->find_by_id(1);

		$this->assertIsInt($donation['donation_amount']);
		$this->assertSame(1000, $donation['donation_amount']);
	}

	public function test_find_by_campaign_hydrates_every_row()
	{
		foreach ($this->repository->find_by_campaign(1) as $donation)
		{
			$this->assertIsInt($donation['donation_amount']);
			$this->assertIsBool($donation['donation_public']);
		}
	}

	// ----------------------------------------------------------------- writes

	public function test_insert_returns_the_new_id_and_persists()
	{
		$id = $this->repository->insert($this->donation(array(
			'campaign_id'		=> 2,
			'donation_amount'	=> 500,
			'donor_name'		=> 'Clara S.',
		)));

		$this->assertIsInt($id);
		$this->assertGreaterThan(0, $id);
		$this->assertSame('Clara S.', $this->repository->find_by_id($id)['donor_name']);
		$this->assertSame(500, $this->repository->sum_by_campaign(2));
	}

	public function test_update_changes_only_the_named_row()
	{
		$this->repository->update(1, array('donation_amount' => 2000));

		$this->assertSame(2000, $this->repository->find_by_id(1)['donation_amount']);
		$this->assertSame(1200, $this->repository->find_by_id(2)['donation_amount']);
		$this->assertSame(3500, $this->repository->sum_by_campaign(1));
	}

	// --------------------------------------------------------------- deletion

	public function test_delete_by_ids_removes_only_the_requested_rows()
	{
		$this->repository->delete_by_ids(array(1));

		$this->assertNull($this->repository->find_by_id(1));
		$this->assertNotNull($this->repository->find_by_id(2));
		$this->assertSame(1500, $this->repository->sum_by_campaign(1));
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

	/**
	 * The cascade path: donations must be removed before their campaign rows,
	 * or they are orphaned permanently. See specification section 7.3.4.
	 */
	public function test_delete_by_campaign_ids_removes_the_whole_campaign()
	{
		$this->repository->insert($this->donation(array('campaign_id' => 2, 'donation_amount' => 700)));

		$this->repository->delete_by_campaign_ids(array(1));

		$this->assertSame(0, $this->repository->sum_by_campaign(1));
		$this->assertSame(0, $this->repository->count_by_campaign(1));
		$this->assertSame(700, $this->repository->sum_by_campaign(2), 'A sibling campaign lost its donations');
	}

	public function test_delete_by_campaign_ids_handles_multiple_campaigns()
	{
		$this->repository->insert($this->donation(array('campaign_id' => 2, 'donation_amount' => 700)));

		$this->repository->delete_by_campaign_ids(array(1, 2));

		$this->assertSame(0, $this->row_count());
	}

	public function test_delete_by_campaign_ids_with_an_empty_array_is_a_noop()
	{
		$this->repository->delete_by_campaign_ids(array());

		$this->assertSame(3, $this->repository->count_by_campaign(1));
	}

	// --------------------------------------------------------------- security

	/**
	 * Every id is cast to int before reaching SQL, so a string payload cannot
	 * alter the query. These assert no damage AND no error.
	 */
	public function injection_payloads()
	{
		return array(
			array('1 OR 1=1'),
			array('1; DROP TABLE phpbb_ufdc_donations'),
			array("1' OR '1'='1"),
			array("' UNION SELECT 1,2,3 --"),
			array('1/**/OR/**/1=1'),
		);
	}

	/**
	 * @dataProvider injection_payloads
	 */
	public function test_read_methods_resist_sql_injection($payload)
	{
		$this->repository->find_by_id($payload);
		$this->repository->find_by_campaign($payload);
		$this->repository->find_public_by_campaign($payload, 25);
		$this->repository->count_by_campaign($payload);
		$this->repository->sum_by_campaign($payload);

		$this->assertSame(3, $this->row_count(), 'An injected read payload changed the data');
	}

	/**
	 * A payload is neutralised by the int cast, so "1 OR 1=1" deletes the row
	 * with id 1 and nothing more. The failure this guards against is the
	 * payload matching every row, or dropping the table.
	 *
	 * @dataProvider injection_payloads
	 */
	public function test_delete_by_ids_resists_sql_injection($payload)
	{
		$this->repository->delete_by_ids(array($payload));

		$this->assertNotNull(
			$this->repository->find_by_id(2),
			'An injected delete payload matched rows beyond its integer value'
		);
		$this->assertGreaterThanOrEqual(
			2,
			$this->row_count(),
			'An injected delete payload removed more rows than its integer value'
		);
	}

	/**
	 * @dataProvider injection_payloads
	 */
	public function test_delete_by_campaign_ids_resists_sql_injection($payload)
	{
		// Every payload casts to campaign 1, which it may legitimately delete.
		// Campaign 2 is what must never be touched.
		$this->repository->insert($this->donation(array('campaign_id' => 2, 'donation_amount' => 42)));

		$this->repository->delete_by_campaign_ids(array($payload));

		$this->assertSame(
			42,
			$this->repository->sum_by_campaign(2),
			'An injected campaign-id payload matched rows beyond its integer value'
		);
	}

	/**
	 * @dataProvider injection_payloads
	 */
	public function test_string_values_are_escaped_on_write($payload)
	{
		$id = $this->repository->insert($this->donation(array('donor_name' => $payload)));

		// Stored verbatim, and the table survived.
		$this->assertSame($payload, $this->repository->find_by_id($id)['donor_name']);
		$this->assertSame(4, $this->row_count());
	}

	// -------------------------------------------------------- table prefixes

	/**
	 * The table name is injected, never built from a hard-coded prefix.
	 */
	public function test_it_uses_the_injected_table_name()
	{
		$this->create_schema('custom_', false);

		$repository = new donation_repository($this->db, 'custom_ufdc_donations');

		$id = $repository->insert($this->donation(array(
			'campaign_id'		=> 7,
			'donation_amount'	=> 111,
			'donor_name'		=> 'Custom prefix donor',
		)));

		$this->assertSame('Custom prefix donor', $repository->find_by_id($id)['donor_name']);

		// The default-prefix table is untouched.
		$this->assertSame(3, $this->row_count());
		$this->assertSame(0, $this->repository->sum_by_campaign(7));
	}

	/**
	 * A complete donation row, so each test states only what it varies.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function donation(array $overrides = array())
	{
		return array_merge(array(
			'campaign_id'		=> 1,
			'donation_amount'	=> 100,
			'donor_name'		=> 'Donor',
			'donation_time'		=> 1700000400,
			'donation_public'	=> 1,
			'donation_created'	=> 1700000400,
			'donation_updated'	=> 1700000400,
		), $overrides);
	}

	// ----------------------------------------------------------- pagination

	/**
	 * A clearly named paginated read, rather than optional parameters bolted
	 * onto find_by_campaign(). The ACP donation list is the only consumer.
	 */
	public function test_find_page_by_campaign_returns_a_page()
	{
		$this->assertCount(2, $this->repository->find_page_by_campaign(1, 2, 0));
		$this->assertCount(1, $this->repository->find_page_by_campaign(1, 2, 2));
	}

	public function test_find_page_by_campaign_covers_every_row_across_pages()
	{
		$ids = array();

		foreach (array(0, 2) as $offset)
		{
			foreach ($this->repository->find_page_by_campaign(1, 2, $offset) as $row)
			{
				$ids[] = $row['donation_id'];
			}
		}

		sort($ids);

		$this->assertSame(array(1, 2, 3), $ids, 'Paging lost or repeated a row');
	}

	public function test_find_page_by_campaign_is_empty_beyond_the_end()
	{
		$this->assertSame(array(), $this->repository->find_page_by_campaign(1, 25, 100));
	}

	public function test_find_page_by_campaign_is_empty_for_a_campaign_without_donations()
	{
		$this->assertSame(array(), $this->repository->find_page_by_campaign(2, 25, 0));
	}

	public function test_find_page_by_campaign_orders_newest_first()
	{
		$rows = $this->repository->find_page_by_campaign(1, 25, 0);

		$this->assertSame(1700000300, $rows[0]['donation_time']);
		$this->assertSame(1700000100, $rows[2]['donation_time']);
	}

	/**
	 * Ordering must be TOTAL, or a paginated list can show one row twice and
	 * silently drop another. Equal timestamps are broken by donation_id.
	 */
	public function test_find_page_by_campaign_orders_deterministically_on_equal_timestamps()
	{
		$a = $this->repository->insert($this->donation(array('donation_time' => 1700009999)));
		$b = $this->repository->insert($this->donation(array('donation_time' => 1700009999)));
		$c = $this->repository->insert($this->donation(array('donation_time' => 1700009999)));

		$first = array_column($this->repository->find_page_by_campaign(1, 2, 0), 'donation_id');
		$second = array_column($this->repository->find_page_by_campaign(1, 2, 2), 'donation_id');

		$this->assertSame(array($c, $b), $first);
		$this->assertSame($a, $second[0]);

		// And stable across repeated reads.
		$this->assertSame($first, array_column($this->repository->find_page_by_campaign(1, 2, 0), 'donation_id'));
	}

	public function test_find_page_by_campaign_hydrates_rows()
	{
		$row = $this->repository->find_page_by_campaign(1, 1, 0)[0];

		$this->assertIsInt($row['donation_amount']);
		$this->assertIsBool($row['donation_public']);
		$this->assertIsString($row['donor_name']);
	}

	public function test_find_page_by_campaign_includes_non_public_rows()
	{
		$rows = $this->repository->find_page_by_campaign(1, 25, 0);

		$this->assertContains(false, array_column($rows, 'donation_public'), 'The ACP must list private donations too');
	}

	/**
	 * @dataProvider injection_payloads
	 */
	public function test_find_page_by_campaign_resists_sql_injection($payload)
	{
		$this->repository->find_page_by_campaign($payload, 25, 0);

		$this->assertSame(3, $this->row_count());
	}
}
