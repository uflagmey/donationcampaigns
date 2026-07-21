<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

/**
 * Shared harness for migrations that touch the ACP modules table.
 *
 * Stands up a real SQLite modules table, a real phpBB module migration tool
 * and a real finder pointed at the extension, so migrations run against
 * phpBB's own machinery rather than against assertions about their step
 * arrays. Extracted from m4's test when m5 needed the same fixture; it holds
 * no assertions of its own.
 */
abstract class module_migration_test_case extends \phpbb_test_case
{
	const CATEGORY_LANGNAME = 'ACP_DONATIONCAMPAIGNS';

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var \phpbb\db\migration\tool\module */
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
			$this->markTestSkipped('sqlite3 extension is required for module tests');
		}

		global $phpbb_root_path;
		$this->phpbb_root_path = $phpbb_root_path;

		$this->db_file = sys_get_temp_dir() . '/ufdc_mod_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new \phpbb\db\driver\sqlite3();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_modules_table();

		$this->tool = $this->build_tool();

		// Every real board has the "Extensions" ACP category; core creates it.
		// The migration adds its category as a child of this one, so an empty
		// modules table is not a realistic starting state.
		$this->tool->add('acp', 0, 'ACP_CAT_DOT_MODS');
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

	protected function create_modules_table()
	{
		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$baseline = $reflection->newInstanceWithoutConstructor();
		$schema = $baseline->update_schema();

		$this->assertArrayHasKey('modules', $schema['add_tables']);

		$this->tools->perform_schema_changes(array('add_tables' => array(
			'phpbb_modules' => $schema['add_tables']['modules'],
		)));
	}

	protected function create_cache_service($driver)
	{
		return new \phpbb\cache\service(
			$driver,
			new \phpbb\config\config(array()),
			$this->db,
			$this->getMockBuilder('\phpbb\event\dispatcher')->disableOriginalConstructor()->getMock(),
			$this->phpbb_root_path,
			'php'
		);
	}

	protected function create_user()
	{
		$user = $this->getMockBuilder('\phpbb\user')
			->disableOriginalConstructor()
			->getMock();
		$user->method('lang')->willReturnArgument(0);
		$user->data = array('user_id' => 2);
		$user->ip = '127.0.0.1';
		$user->lang = array();

		return $user;
	}

	/**
	 * The module tool resolves an extension's *_info.php through the extension
	 * manager's finder.
	 *
	 * A REAL phpBB finder is used, pointed at the disposable phpBB tree where
	 * the extension is symlinked into ext/. Only the manager itself is mocked,
	 * because constructing one needs a full service container. This means the
	 * real acp/main_info.php is discovered and parsed, so a malformed info file
	 * fails these tests rather than passing them.
	 */
	protected function create_extension_manager()
	{
		$finder = new \phpbb\finder(
			new \phpbb\filesystem\filesystem(),
			$this->phpbb_root_path,
			null,
			'php'
		);
		$finder->set_extensions(array('uflagmey/donationcampaigns'));

		$manager = $this->getMockBuilder('\phpbb\extension\manager')
			->disableOriginalConstructor()
			->getMock();

		$manager->method('get_finder')->willReturn($finder);
		$manager->method('all_enabled')->willReturn(array(
			'uflagmey/donationcampaigns' => $this->phpbb_root_path . 'ext/uflagmey/donationcampaigns/',
		));

		return $manager;
	}
	/**
	 * @param string $class Migration class name
	 * @return \phpbb\db\migration\migration
	 */
	protected function make_migration($class)
	{
		return new $class(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			$this->phpbb_root_path,
			'php',
			'phpbb_'
		);
	}

	/**
	 * Execute migration steps the way phpBB's migrator does.
	 *
	 * Handles both the module tool and 'custom' callables, because m5 changes
	 * a column the module tool has no method for.
	 *
	 * @param array $steps
	 * @return void
	 */
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

			$this->assertStringStartsWith(
				'module.',
				$call,
				"Migration step '{$call}' uses neither the module tool nor a custom callable."
			);

			call_user_func_array(
				array($this->tool, substr($call, strlen('module.'))),
				$arguments
			);
		}
	}

	/**
	 * @return array rows from the modules table
	 */
	protected function modules($where = '1=1')
	{
		$sql = "SELECT * FROM phpbb_modules WHERE {$where} ORDER BY module_id";
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	protected function category_row()
	{
		$rows = $this->modules("module_langname = '" . self::CATEGORY_LANGNAME . "'");

		return isset($rows[0]) ? $rows[0] : null;
	}

	/**
	 * A fresh module tool.
	 *
	 * PINNED phpBB BEHAVIOUR: the tool is NOT reusable across an uninstall
	 * and a reinstall. get_categories_list() appends to $module_categories
	 * and never clears it, so after a purge the removed category's old id is
	 * still in the map. get_parent_module_id() then finds two ids for the
	 * same langname, returns both, and add() fails with MODULE_NOT_EXIST on
	 * the stale one.
	 *
	 * A real board never hits this — each migration run is its own request or
	 * CLI process with a new tool. A test that reuses one instance is
	 * modelling reinstall incorrectly, so reset_tool() exists to model it
	 * correctly.
	 *
	 * @return \phpbb\db\migration\tool\module
	 */
	protected function build_tool()
	{
		$cache_driver = new \phpbb\cache\driver\dummy();

		$module_manager = new \phpbb\module\module_manager(
			$cache_driver,
			$this->db,
			$this->create_extension_manager(),
			'phpbb_modules',
			$this->phpbb_root_path,
			'php'
		);

		$user = $this->create_user();

		// phpBB's module tool writes an admin log entry and reads the acting
		// user from globals rather than taking them as arguments.
		$GLOBALS['user'] = $user;
		$GLOBALS['phpbb_log'] = $this->getMockBuilder('\phpbb\log\log_interface')->getMock();

		return new \phpbb\db\migration\tool\module(
			$this->db,
			$this->create_cache_service($cache_driver),
			$user,
			$module_manager,
			$this->phpbb_root_path,
			'php',
			'phpbb_modules'
		);
	}

	/**
	 * Model the process boundary between one migration run and the next.
	 *
	 * @return void
	 */
	protected function reset_tool()
	{
		$this->tool = $this->build_tool();
	}
}
