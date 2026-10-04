<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m10_campaign_list;

/**
 * The beta3 switch for the board-wide campaign list. The decisive property is
 * that an update publishes nothing new: the page stays off until an
 * administrator switches it on.
 */
class m10_campaign_list_test extends \phpbb_test_case
{
	/** @var \phpbb\config\config */
	protected $config;

	public function setUp(): void
	{
		parent::setUp();

		$this->config = new \phpbb\config\config(array());
	}

	protected function make()
	{
		global $phpbb_root_path;

		// Config only: no schema change, so the database is never touched.
		return new m10_campaign_list(
			$this->config,
			$this->createMock('\phpbb\db\driver\driver_interface'),
			$this->createMock('\phpbb\db\tools\tools_interface'),
			$phpbb_root_path,
			'php',
			'phpbb_'
		);
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

	public function test_it_runs_after_the_display_options()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m9_display_options'),
			m10_campaign_list::depends_on()
		);
	}

	public function test_the_list_is_off_after_an_update()
	{
		$this->run_data($this->make()->update_data());

		$this->assertTrue(isset($this->config['donationcampaigns_list_enabled']));
		$this->assertSame(0, (int) $this->config['donationcampaigns_list_enabled']);
	}

	public function test_reapplying_keeps_a_switched_on_list()
	{
		$this->run_data($this->make()->update_data());
		$this->config->set('donationcampaigns_list_enabled', 1);

		$this->run_data($this->make()->update_data());

		$this->assertSame(1, (int) $this->config['donationcampaigns_list_enabled']);
	}

	public function test_revert_removes_the_switch()
	{
		$this->run_data($this->make()->update_data());
		$this->run_data($this->make()->revert_data());

		$this->assertFalse(isset($this->config['donationcampaigns_list_enabled']));
	}

	public function test_it_changes_no_schema()
	{
		$this->assertSame(array(), $this->make()->update_schema());
		$this->assertSame(array(), $this->make()->revert_schema());
	}
}
