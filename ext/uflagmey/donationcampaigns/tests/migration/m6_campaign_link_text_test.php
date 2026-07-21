<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text;

/**
 * The campaign action button gained a configurable label.
 *
 * Version 1.0 processes no payments and knows nothing about any provider.
 * external_url is a destination the administrator chooses, and this column is
 * the text on the button pointing at it — "How to donate", "Über PayPal
 * spenden", "Request bank details". Nothing here is provider-specific and
 * nothing about the payment flow changes: money is still confirmed by hand.
 *
 * A board that is already installed runs this as an upgrade, so the column
 * has to arrive on a populated table without disturbing what is there.
 */
class m6_campaign_link_text_test extends \phpbb_test_case
{
	const COLUMN = 'external_link_text';

	const DEFAULT_TEXT = 'How to donate';

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var string */
	protected $db_file;

	/** @var string */
	protected $prefix = 'phpbb_';

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required for schema tests');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_m6_' . getmypid() . '_' . uniqid() . '.sqlite3';

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
	 * @param string $class
	 * @return \phpbb\db\migration\migration
	 */
	protected function make($class)
	{
		global $phpbb_root_path;

		return new $class(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			$phpbb_root_path,
			'php',
			$this->prefix
		);
	}

	/**
	 * The board as it stands before this migration: the original schema.
	 */
	protected function install_baseline()
	{
		$this->tools->perform_schema_changes(
			$this->make('\uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema')->update_schema()
		);
	}

	/**
	 * The baseline columns without the indexes. See the prefix test for why.
	 *
	 * @return void
	 */
	protected function install_baseline_without_indexes()
	{
		$schema = $this->make('\uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema')->update_schema();

		foreach ($schema['add_tables'] as $table => $definition)
		{
			unset($schema['add_tables'][$table]['KEYS']);
		}

		$this->tools->perform_schema_changes($schema);
	}

	protected function apply()
	{
		$migration = $this->make(self::class_name());
		$this->tools->perform_schema_changes($migration->update_schema());

		foreach ($migration->update_data() as $step)
		{
			list($call, $arguments) = $step;
			$this->assertSame('custom', $call, "Unexpected migration step '{$call}'");
			call_user_func($arguments[0]);
		}
	}

	protected function revert()
	{
		$migration = $this->make(self::class_name());
		$this->tools->perform_schema_changes($migration->revert_schema());
	}

	protected static function class_name()
	{
		return '\uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text';
	}

	protected function campaigns_table()
	{
		return $this->prefix . 'ufdc_campaigns';
	}

	/**
	 * @param array $overrides
	 * @return void
	 */
	protected function insert_campaign(array $overrides = array())
	{
		$row = array_merge(array(
			'topic_id'				=> 10,
			'campaign_title'		=> 'Server fund',
			'campaign_desc'			=> '',
			'desc_bbcode_uid'		=> '',
			'desc_bbcode_bitfield'	=> '',
			'desc_bbcode_options'	=> 7,
			'target_amount'			=> 10000,
			'collected_amount'		=> 2500,
			'campaign_enabled'		=> 1,
			'show_donor_names'		=> 1,
			'show_donation_count'	=> 1,
			'external_url'			=> 'https://example.org/donate',
			'campaign_created'		=> 1700000000,
			'campaign_updated'		=> 1700000000,
		), $overrides);

		$this->db->sql_query('INSERT INTO ' . $this->campaigns_table() . ' ' . $this->db->sql_build_array('INSERT', $row));
	}

	/**
	 * @return array
	 */
	protected function campaigns()
	{
		$result = $this->db->sql_query('SELECT * FROM ' . $this->campaigns_table() . ' ORDER BY campaign_id');
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	protected function column_exists()
	{
		return $this->tools->sql_column_exists($this->campaigns_table(), self::COLUMN);
	}

	// ------------------------------------------------------------------

	public function test_the_migration_runs_after_the_previous_one()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m5_hide_donations_module'),
			m6_campaign_link_text::depends_on()
		);
	}

	public function test_the_column_does_not_exist_before_the_migration()
	{
		$this->install_baseline();

		$this->assertFalse($this->column_exists());
	}

	public function test_a_clean_install_ends_with_the_column()
	{
		$this->install_baseline();
		$this->apply();

		$this->assertTrue($this->column_exists());
	}

	/**
	 * The upgrade case: a board that already holds campaigns.
	 */
	public function test_an_existing_campaign_receives_the_default_text()
	{
		$this->install_baseline();
		$this->insert_campaign();

		$this->apply();

		$rows = $this->campaigns();

		$this->assertCount(1, $rows);
		$this->assertSame(self::DEFAULT_TEXT, $rows[0][self::COLUMN], 'An existing campaign was left with no button text');
	}

	public function test_the_column_is_never_null()
	{
		$this->install_baseline();
		$this->insert_campaign();
		$this->apply();

		foreach ($this->campaigns() as $row)
		{
			$this->assertNotNull($row[self::COLUMN]);
			$this->assertIsString($row[self::COLUMN]);
		}
	}

	/**
	 * Everything else about the campaign has to come through untouched.
	 */
	public function test_unrelated_campaign_data_survives_the_upgrade()
	{
		$this->install_baseline();
		$this->insert_campaign(array(
			'campaign_title'	=> 'Archive restoration',
			'target_amount'		=> 55500,
			'collected_amount'	=> 12300,
			'external_url'		=> 'https://example.org/give?a=1&b=2',
			'campaign_enabled'	=> 0,
		));

		$this->apply();

		$row = $this->campaigns()[0];

		$this->assertSame('Archive restoration', $row['campaign_title']);
		$this->assertSame('55500', (string) $row['target_amount']);
		$this->assertSame('12300', (string) $row['collected_amount']);
		$this->assertSame('https://example.org/give?a=1&b=2', $row['external_url']);
		$this->assertSame('0', (string) $row['campaign_enabled']);
	}

	/**
	 * PINNED phpBB BEHAVIOUR: check_index_name_length() shortens an over-long
	 * index name by stripping the prefix it reads from the CONFIG_TABLE
	 * CONSTANT (tools.php:1502), not from the migration's table_prefix. A test
	 * that changes one without the other gets no shortening and a spurious
	 * failure from the BASELINE schema, before this migration even runs.
	 *
	 * The indexes are what carry that coupling, and this migration creates
	 * none, so the fixture builds the columns without them. What is under test
	 * is this migration composing its table name from its own prefix.
	 */
	public function test_a_custom_table_prefix_is_honoured()
	{
		$this->prefix = 'brd7_';

		$this->install_baseline_without_indexes();
		$this->insert_campaign();
		$this->apply();

		$this->assertTrue($this->tools->sql_column_exists('brd7_ufdc_campaigns', self::COLUMN));
		$this->assertSame(self::DEFAULT_TEXT, $this->campaigns()[0][self::COLUMN]);
	}

	public function test_revert_removes_only_this_column()
	{
		$this->install_baseline();
		$this->insert_campaign();
		$this->apply();

		$this->revert();

		$this->assertFalse($this->column_exists(), 'The column survived the revert');

		// Every other column, and the row itself, is still there.
		$row = $this->campaigns()[0];

		$this->assertSame('Server fund', $row['campaign_title']);
		$this->assertSame('https://example.org/donate', $row['external_url']);
		$this->assertTrue($this->tools->sql_column_exists($this->campaigns_table(), 'collected_amount'));
		$this->assertTrue($this->tools->sql_column_exists($this->campaigns_table(), 'external_url'));
	}

	/**
	 * Purge then install again, which is what an administrator does when an
	 * upgrade goes wrong.
	 */
	public function test_purge_and_reinstall_restores_the_default()
	{
		$this->install_baseline();
		$this->insert_campaign();
		$this->apply();

		$this->revert();
		$this->apply();

		$this->assertTrue($this->column_exists());
		$this->assertSame(self::DEFAULT_TEXT, $this->campaigns()[0][self::COLUMN]);
	}

	/**
	 * Disable and re-enable runs no migration at all, so a configured value
	 * has to still be there afterwards. Re-running the data step models the
	 * worst case: it must not overwrite what the administrator chose.
	 */
	public function test_reapplying_the_data_step_preserves_a_configured_value()
	{
		$this->install_baseline();
		$this->insert_campaign();
		$this->apply();

		$this->db->sql_query(
			'UPDATE ' . $this->campaigns_table() . " SET " . self::COLUMN . " = 'Über PayPal spenden'"
		);

		$migration = $this->make(self::class_name());

		foreach ($migration->update_data() as $step)
		{
			call_user_func($step[1][0]);
		}

		$this->assertSame(
			'Über PayPal spenden',
			$this->campaigns()[0][self::COLUMN],
			'Re-running the migration overwrote the administrator\'s text'
		);
	}

	public function test_the_column_holds_the_full_documented_length()
	{
		$this->install_baseline();
		$this->apply();

		$longest = str_repeat('a', m6_campaign_link_text::MAX_LENGTH);

		$this->insert_campaign(array(self::COLUMN => $longest));

		$this->assertSame($longest, $this->campaigns()[0][self::COLUMN]);
	}

	public function test_multibyte_text_survives_a_round_trip()
	{
		$this->install_baseline();
		$this->apply();

		$this->insert_campaign(array(self::COLUMN => 'Über PayPal spenden'));

		$this->assertSame('Über PayPal spenden', $this->campaigns()[0][self::COLUMN]);
	}

	public function test_the_identifier_fits_oracle()
	{
		$this->assertLessThanOrEqual(
			30,
			strlen(self::COLUMN),
			'The column name exceeds the identifier limit phpBB supports'
		);
	}
}
