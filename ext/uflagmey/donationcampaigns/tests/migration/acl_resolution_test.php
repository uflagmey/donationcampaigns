<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

/**
 * Closes the end-to-end ACL gap left open by task 5.
 *
 * Task 5 verified that the permission is persisted and that grants resolve
 * through phpBB's ACL tables. It did NOT verify the thing the ACP module will
 * actually call: $auth->acl_get('a_donationcampaigns').
 *
 * This test builds a real ACL environment, grants the permission to a real
 * user row, runs phpBB's own auth::acl() to compile the permission cache, and
 * then asks acl_get() the same question the ACP module asks. It is the
 * strongest realistic check available without a full board and session.
 */
class acl_resolution_test extends \phpbb_test_case
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
			$this->markTestSkipped('sqlite3 extension is required for ACL tests');
		}

		global $phpbb_root_path;
		$this->phpbb_root_path = $phpbb_root_path;

		$this->db_file = sys_get_temp_dir() . '/ufdc_acl_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema();

		$cache = new \phpbb\cache\service(
			new \phpbb\cache\driver\dummy(),
			new \phpbb\config\config(array()),
			$this->db,
			$this->create_dispatcher(),
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

		// A forum-local permission, so that acl_options carries both a 'global'
		// and a 'local' set. auth::acl() indexes both unconditionally, and
		// every real board has forum permissions.
		$this->tool->add('f_read', false);

		// Install the permission exactly as the migration does.
		$this->tool->add('a_donationcampaigns', true);
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
		$dispatcher = $this->getMockBuilder('\phpbb\event\dispatcher')
			->disableOriginalConstructor()
			->getMock();

		$dispatcher->method('trigger_event')
			->willReturnCallback(function ($name, $data = array()) {
				return $data;
			});

		return $dispatcher;
	}

	protected function create_schema()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$baseline = $reflection->newInstanceWithoutConstructor();
		$schema = $baseline->update_schema();

		$add = array();

		foreach (array('acl_options', 'acl_groups', 'acl_users', 'acl_roles', 'acl_roles_data', 'groups', 'users', 'user_group') as $table)
		{
			$add['phpbb_' . $table] = $schema['add_tables'][$table];
		}

		$this->tools->perform_schema_changes(array('add_tables' => $add));

		// auth::acl() joins on group_skip_auth, which release_3_0_6_rc1 adds
		// after the 3.0.0 baseline. Applying the whole migration chain here
		// would be far more machinery than the one column is worth.
		$this->tools->perform_schema_changes(array('add_columns' => array(
			'phpbb_groups' => array(
				'group_skip_auth' => array('BOOL', 0),
			),
		)));
	}

	/**
	 * @return int the new user's id
	 */
	protected function create_user_row($user_id)
	{
		$sql = 'INSERT INTO phpbb_users ' . $this->db->sql_build_array('INSERT', array(
			'user_id'			=> (int) $user_id,
			'username'			=> 'admin' . $user_id,
			'username_clean'	=> 'admin' . $user_id,
			'user_permissions'	=> '',
			'user_sig'			=> '',
			'user_occ'			=> '',
			'user_interests'	=> '',
		));
		$this->db->sql_query($sql);

		return (int) $user_id;
	}

	protected function auth_option_id($auth_option)
	{
		$sql = "SELECT auth_option_id FROM phpbb_acl_options
			WHERE auth_option = '" . $this->db->sql_escape($auth_option) . "'";
		$result = $this->db->sql_query($sql);
		$id = (int) $this->db->sql_fetchfield('auth_option_id');
		$this->db->sql_freeresult($result);

		return $id;
	}

	protected function grant_to_user($user_id, $auth_option, $setting = ACL_YES)
	{
		$sql = 'INSERT INTO phpbb_acl_users ' . $this->db->sql_build_array('INSERT', array(
			'user_id'			=> (int) $user_id,
			'forum_id'			=> 0,
			'auth_option_id'	=> $this->auth_option_id($auth_option),
			'auth_role_id'		=> 0,
			'auth_setting'		=> (int) $setting,
		));
		$this->db->sql_query($sql);
	}

	/**
	 * Compile the user's permissions through phpBB's own auth object, exactly
	 * as a real request does.
	 *
	 * @return \phpbb\auth\auth
	 */
	protected function resolve_for_user($user_id)
	{
		$sql = 'SELECT * FROM phpbb_users WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$userdata = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$auth = new \phpbb\auth\auth();
		$auth->acl($userdata);

		return $auth;
	}

	/**
	 * THE TEST TASK 5 COULD NOT RUN.
	 *
	 * A user granted a_donationcampaigns must have acl_get() return true —
	 * the exact call the ACP module makes to authorise a request.
	 */
	public function test_a_granted_user_resolves_the_permission_through_acl_get()
	{
		$user_id = $this->create_user_row(2);
		$this->grant_to_user($user_id, 'a_donationcampaigns');

		$auth = $this->resolve_for_user($user_id);

		$this->assertTrue(
			(bool) $auth->acl_get('a_donationcampaigns'),
			'A user granted the permission does not resolve it through acl_get()'
		);
	}

	/**
	 * The negative case matters more than the positive one: a permission that
	 * always returns true would pass the test above and authorise everybody.
	 */
	public function test_a_user_without_the_grant_is_denied()
	{
		$user_id = $this->create_user_row(3);

		$auth = $this->resolve_for_user($user_id);

		$this->assertFalse(
			(bool) $auth->acl_get('a_donationcampaigns'),
			'A user with no grant resolves the permission as allowed'
		);
	}

	/**
	 * An explicit NEVER must beat the absence of a grant, and must not be
	 * confused with "not set".
	 */
	public function test_an_explicitly_denied_user_is_denied()
	{
		$user_id = $this->create_user_row(4);
		$this->grant_to_user($user_id, 'a_donationcampaigns', ACL_NEVER);

		$auth = $this->resolve_for_user($user_id);

		$this->assertFalse(
			(bool) $auth->acl_get('a_donationcampaigns'),
			'A user explicitly denied the permission resolves it as allowed'
		);
	}

	/**
	 * Granting this extension's permission must not grant any other.
	 */
	public function test_the_grant_does_not_leak_into_other_permissions()
	{
		$this->tool->add('a_other_extension', true);

		$user_id = $this->create_user_row(5);
		$this->grant_to_user($user_id, 'a_donationcampaigns');

		$auth = $this->resolve_for_user($user_id);

		$this->assertTrue((bool) $auth->acl_get('a_donationcampaigns'));
		$this->assertFalse(
			(bool) $auth->acl_get('a_other_extension'),
			"Granting this extension's permission also granted another extension's"
		);
	}
}
