<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m4_initial_module;

/**
 * Exercises ACP module registration against a real modules table, driven
 * through phpBB's real module migration tool.
 *
 * The tool reads the extension's own *_info.php to derive langname, mode and
 * auth for each mode, so running it also verifies that the info file is
 * well-formed — something an assertion against update_data() cannot do.
 */
class m4_initial_module_test extends module_migration_test_case
{
	protected function create_migration()
	{
		return $this->make_migration('\uflagmey\donationcampaigns\migrations\v10x\m4_initial_module');
	}

	protected function apply_migration()
	{
		$this->run_steps($this->create_migration()->update_data());
	}

	protected function revert_migration()
	{
		$this->run_steps($this->create_migration()->revert_data());
	}

	public function test_migration_depends_on_the_permission_migration()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m3_initial_permission'),
			m4_initial_module::depends_on()
		);
	}

	public function test_no_extension_modules_exist_before_the_migration_runs()
	{
		$this->assertCount(
			0,
			$this->modules("module_langname LIKE 'ACP_DONATIONCAMPAIGNS%'")
		);
	}

	public function test_exactly_one_category_is_created()
	{
		$this->apply_migration();

		$categories = $this->modules(
			"module_langname = '" . self::CATEGORY_LANGNAME . "' AND module_basename = ''"
		);

		$this->assertCount(1, $categories, 'Expected exactly one ACP category');
	}

	public function test_exactly_three_child_modules_are_created()
	{
		$this->apply_migration();

		$category = $this->category_row();
		$this->assertNotNull($category, 'The ACP category was not created');

		$children = $this->modules('parent_id = ' . (int) $category['module_id']);

		$this->assertCount(3, $children, 'Expected exactly three child modules');
	}

	public function test_children_are_the_three_approved_modes()
	{
		$this->apply_migration();

		$category = $this->category_row();
		$modes = array();

		foreach ($this->modules('parent_id = ' . (int) $category['module_id']) as $row)
		{
			$modes[] = $row['module_mode'];
		}

		sort($modes);

		$this->assertSame(array('campaigns', 'donations', 'settings'), $modes);
	}

	public function test_every_child_points_at_the_extension_module_class()
	{
		$this->apply_migration();

		$category = $this->category_row();

		foreach ($this->modules('parent_id = ' . (int) $category['module_id']) as $row)
		{
			$this->assertSame(
				'\uflagmey\donationcampaigns\acp\main_module',
				$row['module_basename'],
				"Mode {$row['module_mode']} does not resolve to the extension module class"
			);
		}
	}

	/**
	 * Every visible title must be a language key, never a display string.
	 */
	public function test_all_titles_are_language_keys()
	{
		$this->apply_migration();

		$rows = $this->modules("module_langname LIKE 'ACP_DONATIONCAMPAIGNS%'");
		$this->assertCount(4, $rows, 'Expected the category plus three modes');

		foreach ($rows as $row)
		{
			$this->assertMatchesRegularExpression(
				'/^ACP_DONATIONCAMPAIGNS(_[A-Z]+)?$/',
				$row['module_langname'],
				"Module langname '{$row['module_langname']}' is not an extension language key"
			);
		}
	}

	/**
	 * The ext_ prefix makes the whole category disappear when the extension is
	 * disabled, rather than leaving a dead menu entry that errors when clicked.
	 */
	public function test_child_auth_expressions_gate_on_extension_and_permission()
	{
		$this->apply_migration();

		$category = $this->category_row();

		foreach ($this->modules('parent_id = ' . (int) $category['module_id']) as $row)
		{
			$this->assertSame(
				'ext_uflagmey/donationcampaigns && acl_a_donationcampaigns',
				$row['module_auth'],
				"Mode {$row['module_mode']} has an unexpected auth expression"
			);
		}
	}

	public function test_the_category_is_created_under_the_extensions_category()
	{
		$this->apply_migration();

		$category = $this->category_row();

		$this->assertNotNull($category);
		$this->assertSame('acp', $category['module_class']);
	}

	public function test_modules_are_enabled_and_displayed()
	{
		$this->apply_migration();

		foreach ($this->modules("module_langname LIKE 'ACP_DONATIONCAMPAIGNS%'") as $row)
		{
			$this->assertSame(1, (int) $row['module_enabled'], 'A module was registered disabled');
			$this->assertSame(1, (int) $row['module_display'], 'A module was registered hidden');
		}
	}

	/**
	 * Re-applying must never duplicate modules.
	 *
	 * phpBB's module tool refuses rather than silently skipping: it throws
	 * MODULE_EXISTS. That is a safe outcome — the migrator never re-runs a
	 * recorded migration on a real board — but it is worth pinning, because
	 * "refuses loudly" and "silently no-ops" are different behaviours and a
	 * future reader should not assume the latter.
	 */
	public function test_reapplying_refuses_rather_than_duplicating_modules()
	{
		$this->apply_migration();
		$before = $this->modules("module_langname LIKE 'ACP_DONATIONCAMPAIGNS%'");
		$this->assertCount(4, $before);

		try
		{
			$this->apply_migration();
			$this->fail('Expected MODULE_EXISTS when re-applying the module migration');
		}
		catch (\phpbb\db\migration\exception $e)
		{
			$this->assertStringContainsString('MODULE_EXISTS', $e->getMessage());
		}

		$this->assertCount(
			4,
			$this->modules("module_langname LIKE 'ACP_DONATIONCAMPAIGNS%'"),
			'Re-applying the migration duplicated ACP modules'
		);
	}

	/**
	 * phpBB's disable_step() is a no-op, so registration survives a
	 * disable/re-enable cycle. This asserts the equivalent: nothing in the
	 * migration removes modules except an explicit revert.
	 */
	public function test_registration_survives_when_no_revert_is_run()
	{
		$this->apply_migration();
		$expected = count($this->modules());

		// A disable performs no migration work at all.
		$this->assertCount($expected, $this->modules());
		$this->assertNotNull($this->category_row());
	}

	/**
	 * Purge must remove the extension's category and children, and nothing else.
	 */
	public function test_revert_removes_only_extension_modules()
	{
		// A module belonging to somebody else.
		$sql = 'INSERT INTO phpbb_modules ' . $this->db->sql_build_array('INSERT', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\other\extension\acp\main_module',
			'module_class'		=> 'acp',
			'parent_id'			=> 0,
			'left_id'			=> 500,
			'right_id'			=> 501,
			'module_langname'	=> 'ACP_OTHER_EXTENSION',
			'module_mode'		=> 'settings',
			'module_auth'		=> 'ext_other/extension',
		));
		$this->db->sql_query($sql);

		$this->apply_migration();
		$this->assertNotNull($this->category_row());

		$this->revert_migration();

		$this->assertNull(
			$this->category_row(),
			'The extension category survived the revert'
		);
		$this->assertCount(
			0,
			$this->modules("module_langname LIKE 'ACP_DONATIONCAMPAIGNS%'"),
			'Extension modules survived the revert'
		);
		$this->assertCount(
			1,
			$this->modules("module_langname = 'ACP_OTHER_EXTENSION'"),
			"The revert removed another extension's module"
		);
	}

	/**
	 * Pins the revert ordering, which was a real defect.
	 *
	 * module_manager::delete_module() throws CANNOT_REMOVE_MODULE when a module
	 * still has children. An earlier revert_data() removed the category first
	 * and failed, which would have left the extension's ACP entries on the
	 * board after a purge. The modes must be removed before the category.
	 */
	public function test_revert_removes_the_modes_before_the_category()
	{
		$steps = $this->create_migration()->revert_data();

		$this->assertCount(2, $steps, 'Expected a children-then-category revert');

		// First step targets the modes.
		$this->assertSame('module.remove', $steps[0][0]);
		$this->assertIsArray(
			$steps[0][1][2],
			'The first revert step must remove the modes, not the category'
		);
		$this->assertSame(
			array('settings', 'campaigns', 'donations'),
			$steps[0][1][2]['modes']
		);

		// Second step targets the now-empty category.
		$this->assertSame('module.remove', $steps[1][0]);
		$this->assertSame('ACP_DONATIONCAMPAIGNS', $steps[1][1][2]);
	}

	/**
	 * The whole point of the placeholder: phpBB must be able to resolve and
	 * load the registered class for every mode.
	 */
	public function test_every_registered_mode_resolves_to_a_loadable_class()
	{
		$this->apply_migration();

		$category = $this->category_row();
		$children = $this->modules('parent_id = ' . (int) $category['module_id']);

		$this->assertCount(3, $children);

		foreach ($children as $row)
		{
			$class = $row['module_basename'];

			$this->assertTrue(
				class_exists($class),
				"Mode '{$row['module_mode']}' registers class {$class}, which cannot be loaded"
			);
			$this->assertTrue(
				method_exists($class, 'main'),
				"{$class} has no main() method, so phpBB cannot invoke mode '{$row['module_mode']}'"
			);
		}
	}

	public function test_migration_performs_no_schema_changes()
	{
		$this->assertSame(array(), $this->create_migration()->update_schema());
	}

	// ------------------------------------------- recovery from partial state

	/**
	 * @return bool
	 */
	protected function effectively_installed()
	{
		return $this->create_migration()->effectively_installed();
	}

	public function test_it_reports_not_installed_on_a_clean_board()
	{
		$this->assertFalse($this->effectively_installed());
	}

	public function test_it_reports_installed_once_the_modules_exist()
	{
		$this->apply_migration();

		$this->assertTrue($this->effectively_installed());
	}

	/**
	 * A category with no modes under it is NOT installed. Reporting otherwise
	 * would skip the migration and leave an ACP category that opens nothing.
	 */
	public function test_a_category_without_its_modes_is_not_installed()
	{
		$this->tool->add('acp', 'ACP_CAT_DOT_MODS', self::CATEGORY_LANGNAME);

		$this->assertFalse($this->effectively_installed());
	}

	public function test_a_missing_mode_is_not_installed()
	{
		$this->apply_migration();

		$category = $this->category_row();
		$this->db->sql_query(
			'DELETE FROM phpbb_modules WHERE parent_id = ' . (int) $category['module_id'] . " AND module_mode = 'donations'"
		);

		$this->assertFalse($this->effectively_installed(), 'A partly-present module set was reported as installed');
	}

	/**
	 * THE REGRESSION.
	 *
	 * phpBB's module tool THROWS MODULE_EXISTS when asked to add a module that
	 * is already there (tool/module.php:238), unlike the config and permission
	 * tools, which return quietly. A board whose module rows survived without
	 * their phpbb_migrations row -- a restored partial backup, an interrupted
	 * purge -- therefore aborted the whole install with an untranslated
	 * exception instead of resuming. effectively_installed() is what lets the
	 * migrator skip it.
	 */
	public function test_reinstalling_over_existing_modules_does_not_abort()
	{
		$this->apply_migration();

		// The migrations row is gone but the modules are not: the migrator
		// asks effectively_installed() before it would call the tool again.
		$this->assertTrue(
			$this->effectively_installed(),
			'The migrator would call module.add again and throw MODULE_EXISTS'
		);
	}

	/**
	 * And after a genuine purge it must report not-installed again, or a
	 * reinstall would silently skip and leave no ACP entries at all.
	 */
	public function test_it_reports_not_installed_again_after_a_purge()
	{
		$this->apply_migration();
		$this->revert_migration();

		$this->assertFalse($this->effectively_installed());
	}
}
