<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m5_hide_donations_module;

/**
 * Donations is a per-campaign sub-view, not a top-level ACP destination.
 *
 * It needs a campaign_id to mean anything, and the ACP menu cannot supply
 * one — clicking the menu entry produced "That campaign no longer exists."
 * because campaign_id defaulted to 0.
 *
 * The fix HIDES the module rather than removing it. phpBB resolves a mode
 * from the modules table: p_master::list_modules() reads the rows and
 * set_active() matches against them, so deleting the row makes the mode
 * undispatchable and load_active() raises MODULE_NOT_ACCESS. The campaign
 * list's Donations link would then land on a fatal error.
 *
 * module_display = 0 separates the two concerns exactly, and is what core
 * itself uses for ACP_USER_PROFILE, ACP_PERMISSION_TRACE and UCP_PM_VIEW —
 * all sub-views reached from a parent page with an id in the URL:
 *
 *   functions_module.php:842  menu builder   skips !display  -> hidden
 *   functions_module.php:521  set_active()   no display test -> dispatchable
 */
class m5_hide_donations_module_test extends module_migration_test_case
{
	const MODULE_CLASS = '\uflagmey\donationcampaigns\migrations\v10x\m5_hide_donations_module';

	protected function apply_m4()
	{
		$this->run_steps(
			$this->make_migration('\uflagmey\donationcampaigns\migrations\v10x\m4_initial_module')->update_data()
		);
	}

	protected function revert_m4()
	{
		$this->run_steps(
			$this->make_migration('\uflagmey\donationcampaigns\migrations\v10x\m4_initial_module')->revert_data()
		);
	}

	protected function apply_m5()
	{
		$this->run_steps($this->make_migration(self::MODULE_CLASS)->update_data());
	}

	protected function revert_m5()
	{
		$this->run_steps($this->make_migration(self::MODULE_CLASS)->revert_data());
	}

	/**
	 * The full chain as a clean installation runs it.
	 *
	 * @return void
	 */
	protected function install()
	{
		$this->apply_m4();
		$this->apply_m5();
	}

	/**
	 * @param string $mode
	 * @return array|null
	 */
	protected function mode_row($mode)
	{
		$rows = $this->modules("module_mode = '" . $mode . "'");

		return isset($rows[0]) ? $rows[0] : null;
	}

	/**
	 * @return string[] modes whose row is visible in the ACP menu
	 */
	protected function visible_modes()
	{
		$modes = array();

		foreach ($this->modules("module_mode <> '' AND module_display = 1") as $row)
		{
			$modes[] = $row['module_mode'];
		}

		sort($modes);

		return $modes;
	}

	public function test_migration_depends_on_the_module_migration()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m4_initial_module'),
			m5_hide_donations_module::depends_on(),
			'm5 must run after the modules it hides have been registered'
		);
	}

	public function test_exactly_one_module_becomes_hidden()
	{
		$this->apply_m4();

		$before = count($this->modules('module_display = 0'));

		$this->apply_m5();

		$this->assertSame(
			$before + 1,
			count($this->modules('module_display = 0')),
			'The migration hid a number of modules other than one'
		);
	}

	public function test_the_hidden_module_is_donations()
	{
		$this->install();

		$hidden = $this->modules('module_display = 0');

		$this->assertCount(1, $hidden);
		$this->assertSame('donations', $hidden[0]['module_mode']);
	}

	public function test_the_module_row_count_is_unchanged()
	{
		$this->apply_m4();

		$before = count($this->modules());

		$this->apply_m5();

		$this->assertSame(
			$before,
			count($this->modules()),
			'The migration added or deleted a module row; it must only change display state'
		);
	}

	public function test_donations_remains_registered_and_dispatchable()
	{
		$this->install();

		$row = $this->mode_row('donations');

		$this->assertNotNull($row, 'The donations module row was deleted, so the mode can no longer be dispatched');
		$this->assertSame(
			'\uflagmey\donationcampaigns\acp\main_module',
			$row['module_basename'],
			'The row must still resolve to the module class for an explicit URL to dispatch'
		);
		$this->assertNotSame('', $row['module_auth'], 'The permission gate must survive hiding');
	}

	public function test_settings_and_campaigns_stay_visible()
	{
		$this->install();

		foreach (array('settings', 'campaigns') as $mode)
		{
			$row = $this->mode_row($mode);

			$this->assertNotNull($row);
			$this->assertSame('1', (string) $row['module_display'], "Mode '{$mode}' was hidden but must stay in the menu");
		}
	}

	public function test_a_clean_install_ends_with_exactly_two_visible_modes()
	{
		$this->install();

		$this->assertSame(array('campaigns', 'settings'), $this->visible_modes());
	}

	public function test_the_category_stays_visible()
	{
		$this->install();

		$category = $this->category_row();

		$this->assertNotNull($category);
		$this->assertSame(
			'1',
			(string) $category['module_display'],
			'Hiding a child must not hide the category that holds the visible modes'
		);
	}

	public function test_unrelated_modules_are_untouched()
	{
		// A foreign module alongside the extension's, as a real board has.
		$this->tool->add('acp', 'ACP_CAT_DOT_MODS', array(
			'module_basename'	=> 'acp_foreign',
			'module_langname'	=> 'ACP_FOREIGN_THING',
			'module_mode'		=> 'donations',
			'module_auth'		=> '',
		));

		$this->install();

		$foreign = $this->modules("module_langname = 'ACP_FOREIGN_THING'");

		$this->assertCount(1, $foreign);
		$this->assertSame(
			'1',
			(string) $foreign[0]['module_display'],
			'A foreign module sharing the mode name "donations" was hidden; the migration must match on the extension class too'
		);
	}

	public function test_revert_restores_visibility()
	{
		$this->install();
		$this->revert_m5();

		$this->assertSame(
			array('campaigns', 'donations', 'settings'),
			$this->visible_modes(),
			'Reverting must undo the hiding, so m4 revert then sees the state it created'
		);
	}

	public function test_applying_twice_changes_nothing_further()
	{
		$this->install();

		$after_first = $this->modules();

		$this->apply_m5();

		$this->assertEquals(
			$after_first,
			$this->modules(),
			'Re-applying the migration was not idempotent'
		);
	}

	public function test_purge_removes_every_extension_module()
	{
		$this->install();

		// Purge order: newest migration reverted first.
		$this->revert_m5();
		$this->revert_m4();

		$this->assertCount(
			0,
			$this->modules("module_langname LIKE 'ACP_DONATIONCAMPAIGNS%'"),
			'Purge left extension modules behind'
		);
	}

	public function test_reinstall_after_purge_ends_with_two_visible_modes()
	{
		$this->install();
		$this->revert_m5();
		$this->revert_m4();

		// A reinstall is a separate migration run. See build_tool().
		$this->reset_tool();

		$this->install();

		$this->assertSame(array('campaigns', 'settings'), $this->visible_modes());
		$this->assertNotNull($this->mode_row('donations'), 'Donations was not re-registered on reinstall');
	}

	public function test_migration_performs_no_schema_changes()
	{
		$migration = $this->make_migration(self::MODULE_CLASS);

		$this->assertSame(array(), $migration->update_schema());
		$this->assertSame(array(), $migration->revert_schema());
	}
}
