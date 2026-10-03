<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m7_manage_permissions;
use uflagmey\donationcampaigns\migrations\v10x\m8_forum_permissions;

/**
 * Exercises the m_ -> f_ permission migration against a real ACL schema.
 *
 * The steps run through phpBB's real permission tool and the migration's own
 * custom callables, starting from a board on which m7 has been applied and its
 * moderator permissions granted the way the ACP grants them: the option row AND
 * the m_ any-flag row. The interesting assertions are about that flag — the one
 * piece of state permission.remove leaves behind.
 */
class m8_forum_permissions_test extends \phpbb_test_case
{
	const OLD = array('m_donationcampaigns_manage', 'm_donationcampaigns_donations');

	const NEW = array('f_donationcampaigns_manage', 'f_donationcampaigns_donations');

	const GROUP = 10;

	const OTHER_GROUP = 11;

	const USER = 20;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var \phpbb\db\migration\tool\permission */
	protected $tool;

	/** @var \phpbb\cache\service */
	protected $cache;

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

		$this->db_file = sys_get_temp_dir() . '/ufdc_m8_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema();

		$this->cache = new \phpbb\cache\service(
			new \phpbb\cache\driver\dummy(),
			new \phpbb\config\config(array()),
			$this->db,
			$this->getMockBuilder('\phpbb\event\dispatcher')->disableOriginalConstructor()->getMock(),
			$this->phpbb_root_path,
			'php'
		);

		$GLOBALS['db'] = $this->db;
		$GLOBALS['cache'] = $this->cache;
		$GLOBALS['phpbb_dispatcher'] = $this->create_dispatcher();

		$this->tool = new \phpbb\db\migration\tool\permission(
			$this->db,
			$this->cache,
			new \phpbb\auth\auth(),
			$this->phpbb_root_path,
			'php'
		);

		$this->seed_users_and_groups();
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

	protected function create_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$schema = $reflection->newInstanceWithoutConstructor()->update_schema();

		$needed = array(
			'acl_options', 'acl_groups', 'acl_users', 'acl_roles', 'acl_roles_data',
			'groups', 'users', 'user_group', 'moderator_cache',
		);
		$add = array();

		foreach ($needed as $table)
		{
			$this->assertArrayHasKey($table, $schema['add_tables'], "phpBB baseline no longer defines {$table}");
			$add['phpbb_' . $table] = $schema['add_tables'][$table];
		}

		$this->tools->perform_schema_changes(array('add_tables' => $add));
	}

	protected function seed_users_and_groups()
	{
		$this->db->sql_multi_insert('phpbb_groups', array(
			array('group_id' => self::GROUP, 'group_name' => 'Campaign helpers', 'group_type' => GROUP_OPEN, 'group_desc' => ''),
			array('group_id' => self::OTHER_GROUP, 'group_name' => 'Real moderators', 'group_type' => GROUP_OPEN, 'group_desc' => ''),
		));
		$this->db->sql_multi_insert('phpbb_users', array(
			array('user_id' => self::USER, 'username' => 'helper', 'username_clean' => 'helper', 'user_permissions' => '', 'user_sig' => ''),
		));
	}

	protected function create_migration($class)
	{
		$migration = new $class(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			$this->phpbb_root_path,
			'php',
			'phpbb_'
		);

		if ($migration instanceof \phpbb\db\migration\container_aware_migration)
		{
			$services = array('cache' => $this->cache, 'auth' => new \phpbb\auth\auth());
			$container = $this->createMock('\Symfony\Component\DependencyInjection\ContainerInterface');
			$container->method('get')->willReturnCallback(function ($id) use ($services) {
				return $services[$id];
			});
			$migration->setContainer($container);
		}

		return $migration;
	}

	protected function run_steps(array $steps)
	{
		foreach ($steps as $step)
		{
			list($call, $arguments) = $step;

			if ($call === 'custom')
			{
				call_user_func($arguments[0]);
				continue;
			}

			$this->assertStringStartsWith('permission.', $call, "Step '{$call}' uses neither the permission tool nor a custom callable.");
			call_user_func_array(array($this->tool, substr($call, strlen('permission.'))), $arguments);
		}
	}

	/** The board as beta1 left it: m7 applied. */
	protected function apply_m7()
	{
		$this->run_steps($this->create_migration(m7_manage_permissions::class)->update_data());
	}

	protected function apply_m8()
	{
		$this->run_steps($this->create_migration(m8_forum_permissions::class)->update_data());
	}

	protected function revert_m8()
	{
		$this->run_steps($this->create_migration(m8_forum_permissions::class)->revert_data());
	}

	protected function option_id($option)
	{
		$sql = "SELECT auth_option_id FROM phpbb_acl_options WHERE auth_option = '" . $this->db->sql_escape($option) . "'";
		$result = $this->db->sql_query($sql);
		$id = (int) $this->db->sql_fetchfield('auth_option_id');
		$this->db->sql_freeresult($result);

		return $id;
	}

	/**
	 * Grant options the way the ACP does: one row per YES option plus the m_
	 * any-flag (auth_admin::acl_set()).
	 */
	protected function grant($table, $subject_column, $subject_id, $forum_id, array $options)
	{
		$rows = array();
		foreach (array_merge(array('m_'), $options) as $option)
		{
			$rows[] = array(
				$subject_column		=> $subject_id,
				'forum_id'			=> $forum_id,
				'auth_option_id'	=> $this->option_id($option),
				'auth_role_id'		=> 0,
				'auth_setting'		=> ACL_YES,
			);
		}
		$this->db->sql_multi_insert($table, $rows);
	}

	protected function grant_group($group_id, $forum_id, array $options)
	{
		$this->grant('phpbb_acl_groups', 'group_id', $group_id, $forum_id, $options);
	}

	protected function create_role($name, array $options)
	{
		$this->tool->role_add($name, 'm_', $name);
		$role_id = $this->tool->role_exists($name);

		$rows = array();
		foreach (array_merge(array('m_'), $options) as $option)
		{
			$rows[] = array('role_id' => $role_id, 'auth_option_id' => $this->option_id($option), 'auth_setting' => ACL_YES);
		}
		$this->db->sql_multi_insert('phpbb_acl_roles_data', $rows);

		return $role_id;
	}

	protected function has_flag($table, $where)
	{
		$sql = "SELECT COUNT(*) AS total FROM $table WHERE $where AND auth_option_id = " . $this->option_id('m_');
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total > 0;
	}

	protected function group_has_flag($group_id, $forum_id)
	{
		return $this->has_flag('phpbb_acl_groups', 'group_id = ' . (int) $group_id . ' AND forum_id = ' . (int) $forum_id);
	}

	protected function grant_rows($option)
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

	public function test_it_depends_on_the_moderator_permission_migration()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m7_manage_permissions'),
			m8_forum_permissions::depends_on()
		);
	}

	public function test_it_performs_no_schema_changes()
	{
		$this->assertSame(array(), $this->create_migration(m8_forum_permissions::class)->update_schema());
	}

	/**
	 * The flag cleanup reads the grants that permission.remove deletes, so it
	 * must run first; the cache rebuild must see the final state, so it runs last.
	 */
	public function test_the_flag_cleanup_runs_before_the_removal_and_the_cache_rebuild_last()
	{
		$calls = array();
		foreach ($this->create_migration(m8_forum_permissions::class)->update_data() as $step)
		{
			$calls[] = ($step[0] === 'custom') ? $step[1][0][1] : $step[0] . ':' . $step[1][0];
		}

		$cleanup = array_search('clear_orphaned_flags', $calls, true);
		$this->assertNotFalse($cleanup);
		$this->assertLessThan(array_search('permission.remove:m_donationcampaigns_manage', $calls, true), $cleanup);
		$this->assertLessThan(array_search('permission.remove:m_donationcampaigns_donations', $calls, true), $cleanup);
		$this->assertSame('rebuild_moderator_cache', end($calls));
	}

	// -------------------------------------------------------------- options

	public function test_it_replaces_the_moderator_options_with_local_forum_options()
	{
		$this->apply_m7();
		$this->apply_m8();

		foreach (self::NEW as $option)
		{
			$this->assertTrue($this->tool->exists($option, false), "{$option} was not added as a local permission");
			$this->assertFalse($this->tool->exists($option, true), "{$option} must not be global");
		}

		foreach (self::OLD as $option)
		{
			$this->assertFalse($this->tool->exists($option, false), "{$option} survived the migration");
		}
	}

	public function test_the_new_options_ship_ungranted()
	{
		$this->apply_m7();
		$this->grant_group(self::GROUP, 5, array('m_donationcampaigns_manage'));

		$this->apply_m8();

		foreach (self::NEW as $option)
		{
			$this->assertSame(0, $this->grant_rows($option), "{$option} was granted on upgrade (clean break expected)");
		}
	}

	public function test_it_leaves_the_admin_permission_and_its_grants_intact()
	{
		$this->tool->add('a_donationcampaigns', true);
		$this->tool->role_add('ROLE_ADMIN_FULL', 'a_', 'Full admin');
		$this->tool->permission_set('ROLE_ADMIN_FULL', 'a_donationcampaigns', 'role', true);
		$this->apply_m7();

		$this->apply_m8();

		$this->assertTrue($this->tool->exists('a_donationcampaigns', true));
		$this->assertSame(1, $this->grant_rows('a_donationcampaigns'));
	}

	// ------------------------------------------------------- orphaned flag

	public function test_a_group_that_only_held_our_permission_loses_the_moderator_flag()
	{
		$this->apply_m7();
		$this->grant_group(self::GROUP, 5, array('m_donationcampaigns_manage', 'm_donationcampaigns_donations'));

		$this->apply_m8();

		$this->assertFalse($this->group_has_flag(self::GROUP, 5), 'The m_ flag survived: the group would keep MCP access');
	}

	public function test_a_group_that_also_holds_a_core_moderator_permission_keeps_the_flag()
	{
		$this->tool->add('m_edit', false);
		$this->apply_m7();
		$this->grant_group(self::OTHER_GROUP, 5, array('m_donationcampaigns_manage', 'm_edit'));

		$this->apply_m8();

		$this->assertTrue($this->group_has_flag(self::OTHER_GROUP, 5), 'A real moderator lost the m_ flag');
		$this->assertSame(1, $this->grant_rows('m_edit'), 'A core moderator permission was touched');
	}

	public function test_the_flag_is_decided_per_forum()
	{
		$this->tool->add('m_edit', false);
		$this->apply_m7();
		$this->grant_group(self::GROUP, 5, array('m_donationcampaigns_manage'));
		$this->grant_group(self::GROUP, 6, array('m_donationcampaigns_manage', 'm_edit'));

		$this->apply_m8();

		$this->assertFalse($this->group_has_flag(self::GROUP, 5));
		$this->assertTrue($this->group_has_flag(self::GROUP, 6));
	}

	public function test_a_moderator_flag_without_our_permission_is_never_touched()
	{
		$this->tool->add('m_edit', false);
		$this->apply_m7();
		$this->grant_group(self::OTHER_GROUP, 7, array('m_edit'));

		$this->apply_m8();

		$this->assertTrue($this->group_has_flag(self::OTHER_GROUP, 7));
	}

	public function test_a_never_flag_is_left_alone()
	{
		$this->apply_m7();
		$this->db->sql_multi_insert('phpbb_acl_groups', array(
			array('group_id' => self::GROUP, 'forum_id' => 5, 'auth_option_id' => $this->option_id('m_'), 'auth_role_id' => 0, 'auth_setting' => ACL_NEVER),
			array('group_id' => self::GROUP, 'forum_id' => 5, 'auth_option_id' => $this->option_id('m_donationcampaigns_manage'), 'auth_role_id' => 0, 'auth_setting' => ACL_NEVER),
		));

		$this->apply_m8();

		$this->assertTrue($this->group_has_flag(self::GROUP, 5), 'A NEVER flag grants nothing and must not be deleted');
	}

	public function test_direct_user_grants_are_cleaned_too()
	{
		$this->apply_m7();
		$this->grant('phpbb_acl_users', 'user_id', self::USER, 5, array('m_donationcampaigns_donations'));

		$this->apply_m8();

		$this->assertFalse($this->has_flag('phpbb_acl_users', 'user_id = ' . self::USER . ' AND forum_id = 5'));
	}

	public function test_a_role_holding_only_our_permissions_loses_its_flag()
	{
		$this->apply_m7();
		$role_id = $this->create_role('ROLE_CAMPAIGN_HELPER', self::OLD);

		$this->apply_m8();

		$this->assertFalse($this->has_flag('phpbb_acl_roles_data', 'role_id = ' . $role_id));
	}

	public function test_a_role_with_a_core_moderator_permission_keeps_its_flag()
	{
		$this->tool->add('m_edit', false);
		$this->apply_m7();
		$role_id = $this->create_role('ROLE_EDITOR', array('m_donationcampaigns_manage', 'm_edit'));

		$this->apply_m8();

		$this->assertTrue($this->has_flag('phpbb_acl_roles_data', 'role_id = ' . $role_id));
	}

	// ----------------------------------------------------- moderator cache

	public function test_former_holders_disappear_from_the_moderator_list()
	{
		$this->tool->add('m_edit', false);
		$this->apply_m7();
		$this->grant_group(self::GROUP, 5, array('m_donationcampaigns_manage'));
		$this->grant_group(self::OTHER_GROUP, 5, array('m_edit'));
		$this->db->sql_multi_insert('phpbb_moderator_cache', array(
			array('forum_id' => 5, 'group_id' => self::GROUP, 'group_name' => 'Campaign helpers', 'user_id' => 0, 'username' => '', 'display_on_index' => 1),
		));

		$this->apply_m8();

		$sql = 'SELECT group_id FROM phpbb_moderator_cache WHERE forum_id = 5 ORDER BY group_id';
		$result = $this->db->sql_query($sql);
		$groups = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		$this->assertSame(array(self::OTHER_GROUP), $groups, 'The moderator list was not rebuilt from the cleaned grants');
	}

	// ------------------------------------------------ effectively_installed

	public function test_effectively_installed_reflects_the_actual_state()
	{
		$this->apply_m7();
		$this->assertFalse($this->create_migration(m8_forum_permissions::class)->effectively_installed());

		$this->apply_m8();
		$this->assertTrue($this->create_migration(m8_forum_permissions::class)->effectively_installed());
	}

	public function test_a_half_applied_state_is_not_reported_installed()
	{
		$this->apply_m7();
		$this->tool->add('f_donationcampaigns_manage', false);
		$this->tool->add('f_donationcampaigns_donations', false);

		$this->assertFalse(
			$this->create_migration(m8_forum_permissions::class)->effectively_installed(),
			'The old options still exist; the migration must run'
		);
	}

	public function test_reapplying_is_harmless()
	{
		$this->apply_m7();
		$this->apply_m8();
		$this->apply_m8();

		foreach (self::NEW as $option)
		{
			$this->assertTrue($this->tool->exists($option, false));
		}
	}

	// -------------------------------------------------------------- revert

	public function test_revert_restores_the_shape_m7_expects()
	{
		$this->apply_m7();
		$this->apply_m8();
		$this->revert_m8();

		foreach (self::NEW as $option)
		{
			$this->assertFalse($this->tool->exists($option, false), "{$option} survived the revert");
		}
		foreach (self::OLD as $option)
		{
			$this->assertTrue($this->tool->exists($option, false), "{$option} was not restored for m7's revert");
			$this->assertSame(0, $this->grant_rows($option), "{$option} came back granted");
		}

		// The full purge chain completes: m7's own revert now finds its options.
		$this->run_steps($this->create_migration(m7_manage_permissions::class)->revert_data());
		foreach (self::OLD as $option)
		{
			$this->assertFalse($this->tool->exists($option, false));
		}
	}

	public function test_the_migration_hardcodes_no_table_prefix()
	{
		$source = file_get_contents(dirname(dirname(__DIR__)) . '/migrations/v10x/m8_forum_permissions.php');

		$this->assertStringNotContainsString('phpbb_acl', $source);
		$this->assertStringNotContainsString('phpbb_moderator', $source);
	}
}
