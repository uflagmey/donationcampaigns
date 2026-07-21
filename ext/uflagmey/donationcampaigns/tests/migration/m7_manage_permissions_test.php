<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m7_manage_permissions;

/**
 * Exercises the RC2 moderator-permission migration against a real ACL schema.
 *
 * Two forum-scoped (local) moderator permissions are added and granted to
 * nothing: a board owner opts in per forum, so an upgrade changes no board's
 * behaviour. These tests run the migration's steps through phpBB's real
 * permission tool — the same harness as m3 — because asserting the returned
 * array would prove only intent.
 */
class m7_manage_permissions_test extends \phpbb_test_case
{
	const OPTIONS = array('m_donationcampaigns_manage', 'm_donationcampaigns_donations');

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

		$this->db_file = sys_get_temp_dir() . '/ufdc_m7_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_acl_schema();

		$cache = new \phpbb\cache\service(
			new \phpbb\cache\driver\dummy(),
			new \phpbb\config\config(array()),
			$this->db,
			$this->getMockBuilder('\phpbb\event\dispatcher')->disableOriginalConstructor()->getMock(),
			$this->phpbb_root_path,
			'php'
		);

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

	protected function create_dispatcher()
	{
		$dispatcher = $this->getMockBuilder('\phpbb\event\dispatcher')->disableOriginalConstructor()->getMock();
		$dispatcher->method('trigger_event')->willReturnCallback(function ($name, $data = array()) {
			return $data;
		});

		return $dispatcher;
	}

	protected function create_acl_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$schema = $reflection->newInstanceWithoutConstructor()->update_schema();

		$needed = array('acl_options', 'acl_groups', 'acl_users', 'acl_roles', 'acl_roles_data', 'groups', 'users', 'user_group');
		$add = array();

		foreach ($needed as $table)
		{
			$this->assertArrayHasKey($table, $schema['add_tables'], "phpBB baseline no longer defines {$table}");
			$add['phpbb_' . $table] = $schema['add_tables'][$table];
		}

		$this->tools->perform_schema_changes(array('add_tables' => $add));
	}

	protected function create_migration()
	{
		return new m7_manage_permissions(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			$this->phpbb_root_path,
			'php',
			'phpbb_'
		);
	}

	protected function run_steps(array $steps)
	{
		foreach ($steps as $step)
		{
			list($call, $arguments) = $step;
			$this->assertStringStartsWith('permission.', $call, "Step '{$call}' does not use the permission tool.");
			$method = substr($call, strlen('permission.'));
			call_user_func_array(array($this->tool, $method), $arguments);
		}
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

	/**
	 * @param string $option
	 * @return array{is_global:int,is_local:int}|false
	 */
	protected function option_scope($option)
	{
		$sql = "SELECT is_global, is_local FROM phpbb_acl_options WHERE auth_option = '" . $this->db->sql_escape($option) . "'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	/**
	 * @param string $option
	 * @return int number of role-data + group grant rows referencing the option
	 */
	protected function grant_count($option)
	{
		$total = 0;

		foreach (array('phpbb_acl_roles_data', 'phpbb_acl_groups', 'phpbb_acl_users') as $table)
		{
			$sql = "SELECT COUNT(*) AS total FROM $table d
				JOIN phpbb_acl_options o ON o.auth_option_id = d.auth_option_id
				WHERE o.auth_option = '" . $this->db->sql_escape($option) . "'";
			$result = $this->db->sql_query($sql);
			$total += (int) $this->db->sql_fetchfield('total');
			$this->db->sql_freeresult($result);
		}

		return $total;
	}

	// ---------------------------------------------------------------- ordering

	public function test_migration_depends_on_the_link_text_migration()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text'),
			m7_manage_permissions::depends_on()
		);
	}

	public function test_migration_performs_no_schema_changes()
	{
		$this->assertSame(array(), $this->create_migration()->update_schema());
	}

	// -------------------------------------------------------------- installation

	public function test_the_permissions_are_absent_before_the_migration()
	{
		foreach (self::OPTIONS as $option)
		{
			$this->assertNotContains($option, $this->acl_options());
			$this->assertFalse($this->tool->exists($option, false));
		}
	}

	public function test_both_permissions_exist_after_the_migration()
	{
		$this->apply_migration();

		foreach (self::OPTIONS as $option)
		{
			$this->assertContains($option, $this->acl_options());
			$this->assertTrue($this->tool->exists($option, false), "{$option} was not added as a local permission");
		}
	}

	/**
	 * They are forum-scoped (local) moderator permissions, not global ones.
	 */
	public function test_both_permissions_are_registered_as_local()
	{
		$this->apply_migration();

		foreach (self::OPTIONS as $option)
		{
			$scope = $this->option_scope($option);
			$this->assertSame(0, (int) $scope['is_global'], "{$option} must not be global");
			$this->assertSame(1, (int) $scope['is_local'], "{$option} must be forum-local");
		}
	}

	public function test_it_adds_exactly_the_two_moderator_permissions()
	{
		$before = $this->acl_options();
		$this->apply_migration();
		$added = array_values(array_diff($this->acl_options(), $before));

		// The 'm_' flag option is created by core's acl_add_option when the
		// first m_* permission is added; filter it out to compare our options.
		$added = array_values(array_diff($added, array('m_')));
		sort($added);

		$this->assertSame(array('m_donationcampaigns_donations', 'm_donationcampaigns_manage'), $added);
	}

	// ------------------------------------------------------------- ungranted

	public function test_neither_permission_is_granted_to_anything()
	{
		// Give the board real roles a careless migration might have targeted.
		$this->tool->role_add('ROLE_MOD_FULL', 'm_', 'Full moderator');
		$this->tool->role_add('ROLE_ADMIN_FULL', 'a_', 'Full admin');

		$this->apply_migration();

		foreach (self::OPTIONS as $option)
		{
			$this->assertSame(0, $this->grant_count($option), "{$option} was granted to a role/group/user on install");
		}
	}

	/**
	 * The migration definition contains no grant step at all — the strongest
	 * guard against silently handing moderators a broad capability on upgrade.
	 */
	public function test_the_migration_contains_no_grant_step()
	{
		foreach ($this->create_migration()->update_data() as $step)
		{
			$this->assertNotContains(
				$step[0],
				array('permission.role_add', 'permission.permission_set', 'permission.role_set'),
				"The migration grants a permission via '{$step[0]}'; the new permissions must ship ungranted"
			);
		}
	}

	// ----------------------------------------------- existing admin untouched

	public function test_it_leaves_the_existing_admin_permission_and_its_grants_intact()
	{
		$this->tool->add('a_donationcampaigns', true);
		$this->tool->role_add('ROLE_ADMIN_FULL', 'a_', 'Full admin');
		$this->tool->permission_set('ROLE_ADMIN_FULL', 'a_donationcampaigns', 'role', true);

		$this->apply_migration();

		$this->assertTrue($this->tool->exists('a_donationcampaigns', true), 'The admin permission disappeared');

		$sql = "SELECT rd.auth_setting
			FROM phpbb_acl_roles r
			JOIN phpbb_acl_roles_data rd ON rd.role_id = r.role_id
			JOIN phpbb_acl_options o ON o.auth_option_id = rd.auth_option_id
			WHERE r.role_name = 'ROLE_ADMIN_FULL' AND o.auth_option = 'a_donationcampaigns'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$this->assertNotFalse($row, 'The existing admin grant was destroyed');
		$this->assertSame(ACL_YES, (int) $row['auth_setting']);
	}

	// ------------------------------------------------ effectively_installed

	public function test_effectively_installed_reflects_the_actual_state()
	{
		$this->assertFalse($this->create_migration()->effectively_installed(), 'Reported installed before running');

		$this->apply_migration();

		$this->assertTrue($this->create_migration()->effectively_installed(), 'Not reported installed after running');
	}

	// -------------------------------------------------- revert / reinstall

	public function test_revert_removes_both_permissions()
	{
		$this->apply_migration();
		$this->revert_migration();

		foreach (self::OPTIONS as $option)
		{
			$this->assertFalse($this->tool->exists($option, false), "{$option} survived the revert");
		}
	}

	public function test_revert_removes_only_this_extensions_permissions()
	{
		$this->tool->add('a_donationcampaigns', true);
		$this->tool->add('m_other_extension', false);
		$this->tool->add('u_readpm', true);

		$this->apply_migration();
		$this->revert_migration();

		foreach (array('a_donationcampaigns', 'm_other_extension', 'u_readpm') as $option)
		{
			$this->assertTrue($this->tool->exists($option, $option[0] !== 'm'), "The revert removed '{$option}', which the extension does not own");
		}
	}

	public function test_reapplying_does_not_duplicate_the_permissions()
	{
		$this->apply_migration();
		$this->apply_migration();

		foreach (self::OPTIONS as $option)
		{
			$matches = array_filter($this->acl_options(), function ($o) use ($option) {
				return $o === $option;
			});
			$this->assertCount(1, $matches, "{$option} was added more than once");
		}
	}

	public function test_it_can_be_reinstalled_after_a_revert()
	{
		$this->apply_migration();
		$this->revert_migration();
		$this->apply_migration();

		foreach (self::OPTIONS as $option)
		{
			$this->assertTrue($this->tool->exists($option, false), "{$option} did not reinstall after a revert");
		}
	}

	/**
	 * The migration names no table itself, so it is prefix-agnostic: on a board
	 * with a custom table prefix the permission tool resolves the ACL tables.
	 * effectively_installed is the one place a table is referenced, and it uses
	 * the injected prefix.
	 */
	public function test_the_migration_definition_hardcodes_no_table_prefix()
	{
		$source = file_get_contents(dirname(dirname(__DIR__)) . '/migrations/v10x/m7_manage_permissions.php');

		$this->assertStringNotContainsString('phpbb_acl', $source, 'The migration hardcodes a phpbb_ table name');
		$this->assertStringContainsString('$this->table_prefix', $source, 'effectively_installed must use the injected prefix');
	}
}
