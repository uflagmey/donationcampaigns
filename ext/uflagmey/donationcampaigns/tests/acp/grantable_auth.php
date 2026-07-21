<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * An auth object whose answer a test can set, and which records what was asked.
 *
 * Recording matters: asserting that the module refuses an unauthorised user
 * does not prove it asked about the RIGHT permission.
 */
class grantable_auth extends \phpbb\auth\auth
{
	/** @var bool */
	public $granted = false;

	/** @var string[] Every permission the module asked about */
	public $checked = array();

	public function acl_get($opt, $f = 0)
	{
		$this->checked[] = $opt;

		return $this->granted ? 1 : 0;
	}
}
