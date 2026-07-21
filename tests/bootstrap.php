<?php
/**
 * PHPUnit bootstrap for the Donation Campaigns extension.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * phpBB is treated as READ-ONLY test infrastructure. This bootstrap loads
 * phpBB's test framework from a disposable source tree; it never writes to
 * that tree, never copies phpBB files into the extension package, and never
 * modifies phpBB core.
 *
 * Point PHPBB_TEST_PATH at a phpBB source tree to override the default.
 */

$dev_autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($dev_autoload))
{
	fwrite(STDERR, "Development dependencies are not installed.\nRun: php composer.phar install\n");
	exit(1);
}

require_once $dev_autoload;

$phpbb_test_path = getenv('PHPBB_TEST_PATH')
	?: __DIR__ . '/../.phpbb-test/phpbb-release-3.3.17';

if (!is_dir($phpbb_test_path . '/tests/test_framework'))
{
	fwrite(STDERR, sprintf(
		"phpBB test framework not found at:\n  %s\n\n"
		. "Fetch a disposable phpBB source tree, or set PHPBB_TEST_PATH.\n",
		$phpbb_test_path
	));
	exit(1);
}

// Constants and globals phpBB's test framework expects to already exist.
if (!defined('IN_PHPBB'))
{
	define('IN_PHPBB', true);
}

if (!defined('PHPBB_ENVIRONMENT'))
{
	define('PHPBB_ENVIRONMENT', 'test');
}

global $phpbb_root_path, $phpEx, $table_prefix;

$phpbb_root_path = $phpbb_test_path . '/phpBB/';
$phpEx = 'php';
$table_prefix = 'phpbb_';

require_once $phpbb_root_path . 'includes/startup.php';
require_once $phpbb_root_path . 'includes/constants.php';
require_once $phpbb_root_path . 'phpbb/class_loader.php';

$phpbb_class_loader = new \phpbb\class_loader('phpbb\\', $phpbb_root_path . 'phpbb/', $phpEx);
$phpbb_class_loader->register();

require_once $phpbb_root_path . 'includes/utf/utf_tools.php';
require_once $phpbb_root_path . 'includes/functions.php';
// generate_text_for_display() lives here. It is the approved path for
// rendering stored user text, so the listener tests exercise the real one
// rather than a stand-in.
require_once $phpbb_root_path . 'includes/functions_content.php';

require_once $phpbb_test_path . '/tests/test_framework/phpbb_test_case_helpers.php';
require_once $phpbb_test_path . '/tests/test_framework/phpbb_test_case.php';

// phpBB's own test doubles, used to satisfy the globals that core's content
// helpers reach for. Preferred over hand-rolled stubs: they track core.
require_once $phpbb_test_path . '/tests/mock/user.php';
require_once $phpbb_test_path . '/tests/mock/cache.php';
require_once $phpbb_test_path . '/tests/mock/event_dispatcher.php';
require_once $phpbb_test_path . '/tests/mock/extension_manager.php';

// Autoloader for the extension under test. Mirrors phpBB's own extension
// autoloading: namespace uflagmey\donationcampaigns maps to the package root.
spl_autoload_register(function ($class) {
	$prefix = 'uflagmey\\donationcampaigns\\';

	if (strpos($class, $prefix) !== 0)
	{
		return;
	}

	$relative = substr($class, strlen($prefix));
	$file = __DIR__ . '/../ext/uflagmey/donationcampaigns/'
		. str_replace('\\', '/', $relative) . '.php';

	if (file_exists($file))
	{
		require_once $file;
	}
});
