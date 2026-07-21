<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;

/**
 * Applies the schema migration to a real database through phpBB's own schema
 * API, then inspects the result.
 *
 * The migration is exercised end to end rather than asserted against its
 * returned array: an array can describe a schema that phpBB then refuses to
 * create. Only applying it proves the definition is one the DBAL accepts.
 */
class m1_initial_schema_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var string */
	protected $db_file;

	/** @var string */
	protected $campaigns_table = 'phpbb_ufdc_campaigns';

	/** @var string */
	protected $donations_table = 'phpbb_ufdc_donations';

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required for schema tests');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_schema_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);
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
	 * Apply the migration's schema changes through phpBB's schema API.
	 */
	protected function apply_migration()
	{
		$migration = $this->create_migration();
		$this->tools->perform_schema_changes($migration->update_schema());
	}

	protected function revert_migration()
	{
		$migration = $this->create_migration();
		$this->tools->perform_schema_changes($migration->revert_schema());
	}

	/**
	 * phpBB migrations take (config, db, db_tools, phpbb_root_path, php_ext,
	 * table_prefix). Only db_tools and the prefix matter for schema work.
	 */
	/**
	 * Read a column's physical type from the created table definition.
	 *
	 * @param string $table
	 * @param string $column
	 * @return string
	 */
	protected function column_type($table, $column)
	{
		$sql = "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '"
			. $this->db->sql_escape($table) . "'";
		$result = $this->db->sql_query($sql);
		$definition = (string) $this->db->sql_fetchfield('sql');
		$this->db->sql_freeresult($result);

		if (!preg_match('/\b' . preg_quote($column, '/') . '\s+([A-Za-z0-9_() ]+?)(?:NOT NULL|DEFAULT|,|\n|\))/i', $definition, $m))
		{
			$this->fail("Could not determine the type of {$table}.{$column} from:\n{$definition}");
		}

		return trim($m[1]);
	}

	protected function create_migration()
	{
		$config = new \phpbb\config\config(array());

		return new m1_initial_schema(
			$config,
			$this->db,
			$this->tools,
			'',
			'php',
			'phpbb_'
		);
	}

	public function test_migration_depends_on_the_330_baseline()
	{
		$this->assertSame(
			array('\phpbb\db\migration\data\v330\v330'),
			m1_initial_schema::depends_on()
		);
	}

	public function test_effectively_installed_is_false_before_the_migration_runs()
	{
		$this->assertFalse($this->create_migration()->effectively_installed());
	}

	public function test_effectively_installed_is_true_after_the_migration_runs()
	{
		$this->apply_migration();

		$this->assertTrue($this->create_migration()->effectively_installed());
	}

	public function test_both_tables_are_created()
	{
		$this->apply_migration();

		$this->assertTrue($this->tools->sql_table_exists($this->campaigns_table));
		$this->assertTrue($this->tools->sql_table_exists($this->donations_table));
	}

	public function campaign_columns()
	{
		return array(
			array('campaign_id'),
			array('topic_id'),
			array('campaign_title'),
			array('campaign_desc'),
			array('desc_bbcode_uid'),
			array('desc_bbcode_bitfield'),
			array('desc_bbcode_options'),
			array('target_amount'),
			array('collected_amount'),
			array('campaign_enabled'),
			array('show_donor_names'),
			array('show_donation_count'),
			array('external_url'),
			array('campaign_created'),
			array('campaign_updated'),
		);
	}

	/**
	 * @dataProvider campaign_columns
	 */
	public function test_campaign_column_exists($column)
	{
		$this->apply_migration();

		$this->assertTrue(
			$this->tools->sql_column_exists($this->campaigns_table, $column),
			"Column {$column} is missing from {$this->campaigns_table}"
		);
	}

	public function donation_columns()
	{
		return array(
			array('donation_id'),
			array('campaign_id'),
			array('donation_amount'),
			array('donor_name'),
			array('donation_time'),
			array('donation_public'),
			array('donation_created'),
			array('donation_updated'),
		);
	}

	/**
	 * @dataProvider donation_columns
	 */
	public function test_donation_column_exists($column)
	{
		$this->apply_migration();

		$this->assertTrue(
			$this->tools->sql_column_exists($this->donations_table, $column),
			"Column {$column} is missing from {$this->donations_table}"
		);
	}

	/**
	 * The index on topic_id must exist.
	 *
	 * Note: sql_unique_index_exists() is deliberately NOT used. On sqlite3 it
	 * reports false for a genuinely unique index, because phpBB's sqlite3
	 * sql_list_index() excludes unique indexes. Asserting through it would
	 * make this test driver-specific rather than a statement about the schema.
	 */
	public function test_topic_id_carries_an_index()
	{
		$this->apply_migration();

		$this->assertTrue(
			$this->tools->sql_index_exists($this->campaigns_table, 'dc_topic_id'),
			'The index on topic_id is missing'
		);
	}

	/**
	 * One campaign per topic, enforced by the DATABASE rather than only by
	 * application logic. An application-level check loses to concurrent
	 * requests; a unique index does not.
	 *
	 * This asserts the guarantee behaviourally — a second row for the same
	 * topic must be rejected — rather than trusting index metadata, which
	 * varies by driver and would not prove the constraint actually bites.
	 */
	public function test_a_second_campaign_on_the_same_topic_is_rejected()
	{
		$this->apply_migration();

		$this->db->sql_query(
			'INSERT INTO ' . $this->campaigns_table
			. ' (topic_id, campaign_title, target_amount) VALUES (42, \'First\', 1000)'
		);

		$this->db->sql_return_on_error(true);
		$second = $this->db->sql_query(
			'INSERT INTO ' . $this->campaigns_table
			. ' (topic_id, campaign_title, target_amount) VALUES (42, \'Second\', 2000)'
		);
		$this->db->sql_return_on_error(false);

		$this->assertFalse(
			$second,
			'A second campaign was accepted for a topic that already has one'
		);

		$sql = 'SELECT COUNT(*) AS total FROM ' . $this->campaigns_table . ' WHERE topic_id = 42';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$this->assertSame(1, $total, 'The duplicate campaign row was persisted');
	}

	/**
	 * Composite index serving both the recalculation SUM (leading column) and
	 * the ordered donor list (both columns, no filesort).
	 */
	public function test_donations_carry_the_composite_lookup_index()
	{
		$this->apply_migration();

		$this->assertTrue(
			$this->tools->sql_index_exists($this->donations_table, 'dc_campaign_time'),
			'The (campaign_id, donation_time) lookup index is missing'
		);
	}

	/**
	 * Money is stored as integers. A REAL/FLOAT/DECIMAL column would reintroduce
	 * the rounding problem that currency_formatter exists to avoid.
	 */
	public function money_columns()
	{
		return array(
			array('phpbb_ufdc_campaigns', 'target_amount'),
			array('phpbb_ufdc_campaigns', 'collected_amount'),
			array('phpbb_ufdc_donations', 'donation_amount'),
		);
	}

	/**
	 * @dataProvider money_columns
	 */
	public function test_money_columns_are_integer_typed($table, $column)
	{
		$this->apply_migration();

		$type = $this->column_type($table, $column);

		$this->assertMatchesRegularExpression(
			'/int/i',
			$type,
			"Money column {$table}.{$column} has type '{$type}', which is not an integer type"
		);
		$this->assertDoesNotMatchRegularExpression(
			'/real|float|double|decimal|numeric/i',
			$type,
			"Money column {$table}.{$column} has floating-point type '{$type}'"
		);
	}

	/**
	 * The extension must not create foreign keys. phpBB's schema tooling does
	 * not create them and core tables do not use them; introducing them would
	 * break the conventions reviewers expect.
	 */
	public function test_no_foreign_keys_are_created()
	{
		$this->apply_migration();

		foreach (array($this->campaigns_table, $this->donations_table) as $table)
		{
			$sql = "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '" . $this->db->sql_escape($table) . "'";
			$result = $this->db->sql_query($sql);
			$definition = (string) $this->db->sql_fetchfield('sql');
			$this->db->sql_freeresult($result);

			$this->assertStringNotContainsStringIgnoringCase(
				'FOREIGN KEY',
				$definition,
				"Table {$table} declares a foreign key"
			);
		}
	}

	/**
	 * Re-running must be a no-op rather than a duplicate-table error, so that
	 * a partially applied install can be repaired by re-running migrations.
	 */
	public function test_migration_is_idempotent_via_effectively_installed()
	{
		$this->apply_migration();

		$migration = $this->create_migration();
		$this->assertTrue($migration->effectively_installed());

		// A second apply must be guarded by effectively_installed(), never attempted.
		if (!$migration->effectively_installed())
		{
			$this->fail('effectively_installed() did not detect the applied migration');
		}

		$this->assertTrue($this->tools->sql_table_exists($this->campaigns_table));
	}

	/**
	 * Reverting must remove the extension's tables and nothing else.
	 */
	public function test_revert_removes_only_extension_tables()
	{
		// A table belonging to somebody else, which must survive the revert.
		$this->tools->perform_schema_changes(array('add_tables' => array(
			'phpbb_unrelated' => array(
				'COLUMNS'		=> array('id' => array('UINT', null, 'auto_increment')),
				'PRIMARY_KEY'	=> 'id',
			),
		)));

		$this->apply_migration();
		$this->assertTrue($this->tools->sql_table_exists($this->campaigns_table));

		$this->revert_migration();

		$this->assertFalse($this->tools->sql_table_exists($this->campaigns_table));
		$this->assertFalse($this->tools->sql_table_exists($this->donations_table));
		$this->assertTrue(
			$this->tools->sql_table_exists('phpbb_unrelated'),
			'The revert removed a table the extension does not own'
		);
	}

	/**
	 * Table names must honour the configured prefix rather than hard-coding
	 * phpbb_, so that boards with a custom prefix install correctly.
	 */
	public function test_table_names_honour_the_configured_prefix()
	{
		$config = new \phpbb\config\config(array());
		$migration = new m1_initial_schema($config, $this->db, $this->tools, '', 'php', 'custom_');

		$schema = $migration->update_schema();
		$tables = array_keys($schema['add_tables']);

		$this->assertContains('custom_ufdc_campaigns', $tables);
		$this->assertContains('custom_ufdc_donations', $tables);
	}

	/**
	 * Guards the portability decision recorded in ADR-012 and audited in
	 * spec section 4.6.8. Oracle before 12.2 caps identifiers at 30 bytes and
	 * phpBB 3.3 supports Oracle.
	 */
	public function test_physical_identifiers_fit_the_30_byte_limit()
	{
		$config = new \phpbb\config\config(array());
		$migration = new m1_initial_schema($config, $this->db, $this->tools, '', 'php', 'phpbb_');
		$schema = $migration->update_schema();

		foreach ($schema['add_tables'] as $table_name => $table_data)
		{
			$this->assertLessThanOrEqual(
				30,
				strlen($table_name),
				"Table name '{$table_name}' exceeds the 30-byte identifier limit"
			);

			foreach (array_keys($table_data['COLUMNS']) as $column_name)
			{
				$this->assertLessThanOrEqual(
					30,
					strlen($column_name),
					"Column name '{$column_name}' exceeds the 30-byte identifier limit"
				);
			}

			// PostgreSQL and MSSQL build a "{column}_gen" sequence for
			// auto-increment columns and cap the column name at 26.
			foreach ($table_data['COLUMNS'] as $column_name => $column_data)
			{
				if (isset($column_data[2]) && $column_data[2] === 'auto_increment')
				{
					$this->assertLessThanOrEqual(
						26,
						strlen($column_name),
						"Auto-increment column '{$column_name}' exceeds the 26-byte limit"
					);
				}
			}
		}
	}
}
