<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m9_display_options;

/**
 * The beta2 display options: two currency settings and the per-campaign
 * donation-date flag. The decisive property is that an upgrade changes no
 * public page — every default reproduces the beta1 output.
 */
class m9_display_options_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var string */
	protected $db_file;

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required for schema tests');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_m9_' . getmypid() . '_' . uniqid() . '.sqlite3';
		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);
		$this->config = new \phpbb\config\config(array());

		// The board as m8 left it: the campaign schema including m6's column.
		$this->tools->perform_schema_changes($this->make('\uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema')->update_schema());
		$this->tools->perform_schema_changes($this->make('\uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text')->update_schema());
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

	protected function make($class)
	{
		global $phpbb_root_path;

		return new $class($this->config, $this->db, $this->tools, $phpbb_root_path, 'php', 'phpbb_');
	}

	protected function run_data(array $steps)
	{
		$tool = new \phpbb\db\migration\tool\config($this->config);

		foreach ($steps as $step)
		{
			list($call, $arguments) = $step;
			$this->assertStringStartsWith('config.', $call, "Unexpected step '{$call}'");
			call_user_func_array(array($tool, substr($call, strlen('config.'))), $arguments);
		}
	}

	protected function apply()
	{
		$migration = $this->make(m9_display_options::class);
		$this->tools->perform_schema_changes($migration->update_schema());
		$this->run_data($migration->update_data());
	}

	protected function revert()
	{
		$migration = $this->make(m9_display_options::class);
		$this->run_data($migration->revert_data());
		$this->tools->perform_schema_changes($migration->revert_schema());
	}

	protected function insert_campaign()
	{
		$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array(
			'topic_id'			=> 10,
			'campaign_title'	=> 'Server fund',
			'campaign_desc'		=> '',
			'target_amount'		=> 10000,
			'show_donor_names'	=> 1,
		)));
	}

	public function test_it_runs_after_the_permission_migration()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m8_forum_permissions'),
			m9_display_options::depends_on()
		);
	}

	public function test_the_currency_defaults_reproduce_the_beta1_layout()
	{
		$this->apply();

		$this->assertSame(0, (int) $this->config['donationcampaigns_currency_symbol_before'], 'Symbol must stay after the amount');
		$this->assertSame(1, (int) $this->config['donationcampaigns_currency_symbol_space'], 'Symbol must stay separated');
	}

	/**
	 * An existing campaign must not start publishing donation dates because
	 * the board was updated.
	 */
	public function test_an_existing_campaign_does_not_start_showing_dates()
	{
		$this->insert_campaign();
		$this->apply();

		$result = $this->db->sql_query('SELECT show_donation_date FROM phpbb_ufdc_campaigns');
		$this->assertSame(0, (int) $this->db->sql_fetchfield('show_donation_date'));
		$this->db->sql_freeresult($result);
	}

	public function test_reapplying_keeps_a_configured_value()
	{
		$this->apply();
		$this->config->set('donationcampaigns_currency_symbol_before', 1);
		$this->config->set('donationcampaigns_currency_symbol_space', 0);

		$this->run_data($this->make(m9_display_options::class)->update_data());

		$this->assertSame(1, (int) $this->config['donationcampaigns_currency_symbol_before']);
		$this->assertSame(0, (int) $this->config['donationcampaigns_currency_symbol_space']);
	}

	public function test_revert_removes_exactly_what_it_added()
	{
		$this->insert_campaign();
		$this->apply();
		$this->revert();

		$this->assertFalse(isset($this->config['donationcampaigns_currency_symbol_before']));
		$this->assertFalse(isset($this->config['donationcampaigns_currency_symbol_space']));
		$this->assertFalse($this->tools->sql_column_exists('phpbb_ufdc_campaigns', m9_display_options::COLUMN));
		$this->assertTrue($this->tools->sql_column_exists('phpbb_ufdc_campaigns', 'show_donor_names'), 'The revert dropped a column it does not own');

		$result = $this->db->sql_query('SELECT COUNT(*) AS total FROM phpbb_ufdc_campaigns');
		$this->assertSame(1, (int) $this->db->sql_fetchfield('total'), 'The revert lost campaign data');
		$this->db->sql_freeresult($result);
	}

	public function test_it_can_be_reinstalled_after_a_revert()
	{
		$this->apply();
		$this->revert();
		$this->apply();

		$this->assertTrue($this->tools->sql_column_exists('phpbb_ufdc_campaigns', m9_display_options::COLUMN));
	}
}
