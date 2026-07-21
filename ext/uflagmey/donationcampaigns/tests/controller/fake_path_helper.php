<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\controller;

/**
 * A path_helper whose web root is a fixed, recognisable prefix.
 *
 * The real path_helper derives the web root from the live request URI, which a
 * unit test has no meaningful value for. This double returns a constant prefix
 * so a test can assert that the controller builds topic links FROM the web root
 * — the fix for the links that resolved relative to app.php/... and 404'd —
 * without reproducing phpBB's request plumbing.
 */
class fake_path_helper extends \phpbb\path_helper
{
	const WEB_ROOT = './../../../';

	public function __construct()
	{
	}

	public function get_web_root_path()
	{
		return self::WEB_ROOT;
	}
}
