<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m3_initial_permission;

/**
 * Exercises the permission migration against a real ACL schema.
 *
 * The ACL tables are built from phpBB's own baseline migration rather than
 * hand-copied, and the migration's steps run through phpBB's real permission
 * tool. Asserting against the array returned by update_data() would prove only
 * that the migration intends to create a permission.
 */
class m3_initial_permission_test extends \phpbb_test_case
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var \phpbb\db\migration\tool\permission */
	protected $tool;

	/** @var string */
	protected $db_file;

	/** @var string */
	protected $phpbb_root_path;

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required for permission tests');
		}

		global $phpbb_root_path;
		$this->phpbb_root_path = $phpbb_root_path;

		$this->db_file = sys_get_temp_dir() . '/ufdc_perm_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_acl_schema();

		$cache = new \phpbb\cache\service(
			new \phpbb\cache\driver\dummy(),
			new \phpbb\config\config(array()),
			$this->db,
			$this->getMockBuilder('\phpbb\event\dispatcher')
				->disableOriginalConstructor()
				->getMock(),
			$this->phpbb_root_path,
			'php'
		);

		// phpBB's auth_admin and auth::acl_clear_prefetch() read these from
		// globals rather than taking them as arguments.
		$GLOBALS['db'] = $this->db;
		$GLOBALS['cache'] = $cache;
		$GLOBALS['phpbb_dispatcher'] = $this->create_dispatcher();

		$this->tool = new \phpbb\db\migration\tool\permission(
			$this->db,
			$cache,
			new \phpbb\auth\auth(),
			$this->phpbb_root_path,
			'php'
		);
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
	 * A dispatcher whose trigger_event() returns its payload unchanged.
	 *
	 * phpBB's auth::acl_clear_prefetch() dispatches core.acl_clear_prefetch_after
	 * through a global dispatcher. The extension does not listen to it; this
	 * exists only so the real permission tool can run.
	 *
	 * @return \phpbb\event\dispatcher
	 */
	protected function create_dispatcher()
	{
		$dispatcher = $this->getMockBuilder('\phpbb\event\dispatcher')
			->disableOriginalConstructor()
			->getMock();

		$dispatcher->method('trigger_event')
			->willReturnCallback(function ($name, $data = array()) {
				return $data;
			});

		return $dispatcher;
	}

	/**
	 * Build the ACL tables from phpBB's own baseline migration, so the schema
	 * under test is genuinely phpBB's rather than an approximation of it.
	 */
	protected function create_acl_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$baseline = $reflection->newInstanceWithoutConstructor();
		$schema = $baseline->update_schema();

		$needed = array(
			'acl_options',
			'acl_groups',
			'acl_users',
			'acl_roles',
			'acl_roles_data',
			'groups',
			// permission_set() clears phpBB's ACL prefetch cache, which reads
			// these two.
			'users',
			'user_group',
		);

		$add = array();

		foreach ($needed as $table)
		{
			$this->assertArrayHasKey(
				$table,
				$schema['add_tables'],
				"phpBB's baseline migration no longer defines {$table}"
			);

			$add['phpbb_' . $table] = $schema['add_tables'][$table];
		}

		$this->tools->perform_schema_changes(array('add_tables' => $add));
	}

	protected function create_migration()
	{
		return new m3_initial_permission(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			$this->phpbb_root_path,
			'php',
			'phpbb_'
		);
	}

	/**
	 * Run the migration's steps through phpBB's real permission tool,
	 * interpreting the 'if' conditional steps the way the migrator does.
	 */
	protected function run_steps(array $steps)
	{
		foreach ($steps as $step)
		{
			$this->run_step($step);
		}
	}

	protected function run_step(array $step)
	{
		list($call, $arguments) = $step;

		if ($call === 'if')
		{
			list($condition, $inner) = $arguments;

			if ($this->evaluate($condition))
			{
				$this->run_step($inner);
			}

			return;
		}

		$this->assertStringStartsWith(
			'permission.',
			$call,
			"Migration step '{$call}' does not use the permission tool."
		);

		$method = substr($call, strlen('permission.'));
		call_user_func_array(array($this->tool, $method), $arguments);
	}

	protected function evaluate(array $condition)
	{
		list($call, $arguments) = $condition;
		$method = substr($call, strlen('permission.'));

		return (bool) call_user_func_array(array($this->tool, $method), $arguments);
	}

	protected function apply_migration()
	{
		$this->run_steps($this->create_migration()->update_data());
	}

	protected function revert_migration()
	{
		$this->run_steps($this->create_migration()->revert_data());
	}

	/**
	 * @return string[] auth_option values currently in the ACL options table
	 */
	protected function acl_options()
	{
		$sql = 'SELECT auth_option FROM phpbb_acl_options ORDER BY auth_option';
		$result = $this->db->sql_query($sql);

		$options = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$options[] = $row['auth_option'];
		}
		$this->db->sql_freeresult($result);

		return $options;
	}

	public function test_migration_depends_on_the_config_migration()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m2_initial_config'),
			m3_initial_permission::depends_on()
		);
	}

	public function test_permission_is_absent_before_the_migration_runs()
	{
		$this->assertNotContains('a_donationcampaigns', $this->acl_options());
		$this->assertFalse($this->tool->exists('a_donationcampaigns', true));
	}

	public function test_permission_exists_in_acl_options_after_the_migration()
	{
		$this->apply_migration();

		$this->assertContains('a_donationcampaigns', $this->acl_options());
		$this->assertTrue($this->tool->exists('a_donationcampaigns', true));
	}

	/**
	 * It is a global administrative permission, not a forum-local one.
	 */
	public function test_permission_is_registered_as_global()
	{
		$this->apply_migration();

		$sql = "SELECT is_global, is_local FROM phpbb_acl_options
			WHERE auth_option = 'a_donationcampaigns'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$this->assertSame(1, (int) $row['is_global']);
		$this->assertSame(0, (int) $row['is_local']);
	}

	/**
	 * On a real board the migration adds exactly one ACL option.
	 *
	 * The 'a_' flag option is seeded first because every real board already has
	 * it — core creates it for its own a_* permissions. See
	 * test_the_flag_option_is_created_by_core_not_by_this_extension for why
	 * that matters.
	 */
	public function test_exactly_one_permission_is_added_on_a_realistic_board()
	{
		$this->tool->add('a_board', true);

		$before = $this->acl_options();
		$this->apply_migration();
		$added = array_values(array_diff($this->acl_options(), $before));

		$this->assertSame(array('a_donationcampaigns'), $added);
	}

	/**
	 * Documents phpBB behaviour discovered while writing these tests.
	 *
	 * auth_admin::acl_add_option() derives the flag prefix from an option name
	 * ('a_' from 'a_donationcampaigns') and creates it when absent. So on a
	 * board with no a_* permission at all, installing this extension also
	 * creates 'a_'.
	 *
	 * That option is core-owned infrastructure, not ours. The revert
	 * deliberately does not remove it: another extension's a_* permission may
	 * depend on it, and phpBB's own permissions certainly do.
	 */
	public function test_the_flag_option_is_created_by_core_not_by_this_extension()
	{
		$this->assertNotContains('a_', $this->acl_options());

		$this->apply_migration();

		$this->assertContains(
			'a_',
			$this->acl_options(),
			"phpBB's acl_add_option() no longer creates the flag option"
		);

		$this->revert_migration();

		$this->assertContains(
			'a_',
			$this->acl_options(),
			'The revert removed the core-owned a_ flag option, which other '
			. 'permissions depend on'
		);
	}

	/**
	 * Granting the permission to a role must produce a resolvable grant in
	 * phpBB's ACL data model — an actual row joining the role to the option
	 * with a positive auth_setting, not merely an array entry.
	 */
	public function test_the_permission_can_be_granted_to_a_role_and_resolved()
	{
		$this->apply_migration();

		$this->tool->role_add('ROLE_TEST_ADMIN', 'a_', 'Test admin role');
		$this->tool->permission_set('ROLE_TEST_ADMIN', 'a_donationcampaigns', 'role', true);

		$sql = "SELECT rd.auth_setting
			FROM phpbb_acl_roles r
			JOIN phpbb_acl_roles_data rd ON rd.role_id = r.role_id
			JOIN phpbb_acl_options o ON o.auth_option_id = rd.auth_option_id
			WHERE r.role_name = 'ROLE_TEST_ADMIN'
				AND o.auth_option = 'a_donationcampaigns'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$this->assertNotFalse($row, 'The grant did not resolve through the ACL tables');
		$this->assertSame(
			ACL_YES,
			(int) $row['auth_setting'],
			'The permission was granted but does not resolve as allowed'
		);
	}

	/**
	 * Revoking must flip the resolved setting, not merely delete a row.
	 */
	public function test_a_granted_permission_can_be_revoked()
	{
		$this->apply_migration();

		$this->tool->role_add('ROLE_TEST_ADMIN', 'a_', 'Test admin role');
		$this->tool->permission_set('ROLE_TEST_ADMIN', 'a_donationcampaigns', 'role', true);
		$this->tool->permission_unset('ROLE_TEST_ADMIN', 'a_donationcampaigns', 'role');

		$sql = "SELECT rd.auth_setting
			FROM phpbb_acl_roles r
			JOIN phpbb_acl_roles_data rd ON rd.role_id = r.role_id
			JOIN phpbb_acl_options o ON o.auth_option_id = rd.auth_option_id
			WHERE r.role_name = 'ROLE_TEST_ADMIN'
				AND o.auth_option = 'a_donationcampaigns'
				AND rd.auth_setting = " . ACL_YES;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$this->assertFalse($row, 'The permission still resolves as allowed after being unset');
	}

	/**
	 * The migration grants to the standard admin roles when they exist, and
	 * survives boards where they have been renamed or removed.
	 */
	public function test_it_grants_to_admin_roles_when_they_exist()
	{
		$this->tool->role_add('ROLE_ADMIN_FULL', 'a_', 'Full admin');
		$this->tool->role_add('ROLE_ADMIN_STANDARD', 'a_', 'Standard admin');

		$this->apply_migration();

		foreach (array('ROLE_ADMIN_FULL', 'ROLE_ADMIN_STANDARD') as $role)
		{
			$sql = "SELECT rd.auth_setting
				FROM phpbb_acl_roles r
				JOIN phpbb_acl_roles_data rd ON rd.role_id = r.role_id
				JOIN phpbb_acl_options o ON o.auth_option_id = rd.auth_option_id
				WHERE r.role_name = '" . $this->db->sql_escape($role) . "'
					AND o.auth_option = 'a_donationcampaigns'";
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$this->assertNotFalse($row, "The migration did not grant the permission to {$role}");
			$this->assertSame(ACL_YES, (int) $row['auth_setting']);
		}
	}

	/**
	 * Boards whose admin roles have been renamed or deleted must still install.
	 */
	public function test_it_installs_on_a_board_with_no_standard_admin_roles()
	{
		$this->apply_migration();

		$this->assertTrue($this->tool->exists('a_donationcampaigns', true));
	}

	/**
	 * Disabling an extension reverts no migrations (phpBB's disable_step is a
	 * no-op), so the permission and its grants survive a disable/re-enable
	 * cycle. Re-applying must also be safe.
	 */
	public function test_reapplying_does_not_duplicate_the_permission()
	{
		$this->tool->role_add('ROLE_ADMIN_FULL', 'a_', 'Full admin');

		$this->apply_migration();
		$this->apply_migration();

		$options = array_filter($this->acl_options(), function ($option) {
			return $option === 'a_donationcampaigns';
		});

		$this->assertCount(1, $options, 'The permission was added more than once');

		$sql = "SELECT COUNT(*) AS total
			FROM phpbb_acl_roles r
			JOIN phpbb_acl_roles_data rd ON rd.role_id = r.role_id
			JOIN phpbb_acl_options o ON o.auth_option_id = rd.auth_option_id
			WHERE r.role_name = 'ROLE_ADMIN_FULL'
				AND o.auth_option = 'a_donationcampaigns'";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$this->assertSame(1, $total, 'The grant was duplicated on re-apply');
	}

	/**
	 * Purge must remove this extension's permission and nothing else.
	 */
	public function test_revert_removes_only_the_extension_permission()
	{
		// Options belonging to core and to another extension.
		$this->tool->add('a_board', true);
		$this->tool->add('a_other_extension', true);
		$this->tool->add('u_readpm', true);

		$this->apply_migration();
		$this->assertTrue($this->tool->exists('a_donationcampaigns', true));

		$this->revert_migration();

		$this->assertFalse(
			$this->tool->exists('a_donationcampaigns', true),
			'The extension permission survived the revert'
		);

		foreach (array('a_board', 'a_other_extension', 'u_readpm') as $option)
		{
			$this->assertTrue(
				$this->tool->exists($option, true),
				"The revert removed '{$option}', which the extension does not own"
			);
		}
	}

	/**
	 * Grants belonging to other permissions must survive the purge.
	 */
	public function test_revert_preserves_unrelated_grants()
	{
		$this->tool->add('a_other_extension', true);
		$this->tool->role_add('ROLE_ADMIN_FULL', 'a_', 'Full admin');
		$this->tool->permission_set('ROLE_ADMIN_FULL', 'a_other_extension', 'role', true);

		$this->apply_migration();
		$this->revert_migration();

		$sql = "SELECT rd.auth_setting
			FROM phpbb_acl_roles r
			JOIN phpbb_acl_roles_data rd ON rd.role_id = r.role_id
			JOIN phpbb_acl_options o ON o.auth_option_id = rd.auth_option_id
			WHERE r.role_name = 'ROLE_ADMIN_FULL'
				AND o.auth_option = 'a_other_extension'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$this->assertNotFalse($row, 'The revert destroyed an unrelated grant');
		$this->assertSame(ACL_YES, (int) $row['auth_setting']);
	}

	/**
	 * Reverting must also clear the extension's own grants, so that no
	 * dangling role data survives a purge.
	 */
	public function test_revert_removes_the_extension_grants()
	{
		$this->tool->role_add('ROLE_ADMIN_FULL', 'a_', 'Full admin');

		$this->apply_migration();
		$this->revert_migration();

		$sql = "SELECT COUNT(*) AS total
			FROM phpbb_acl_roles_data rd
			LEFT JOIN phpbb_acl_options o ON o.auth_option_id = rd.auth_option_id
			WHERE o.auth_option_id IS NULL";
		$result = $this->db->sql_query($sql);
		$orphans = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$this->assertSame(0, $orphans, 'Purge left orphaned role-permission rows behind');
	}

	/**
	 * ADR-008: one administrative permission. No user-side permission, no
	 * split between settings and management.
	 */
	public function test_it_adds_no_permission_beyond_the_approved_one()
	{
		$added = array();

		foreach ($this->create_migration()->update_data() as $step)
		{
			if ($step[0] === 'permission.add')
			{
				$added[] = $step[1][0];
			}
		}

		$this->assertSame(array('a_donationcampaigns'), $added);
	}

	/**
	 * This migration touches permissions only.
	 */
	public function test_migration_performs_no_schema_changes()
	{
		$this->assertSame(array(), $this->create_migration()->update_schema());
	}
}
