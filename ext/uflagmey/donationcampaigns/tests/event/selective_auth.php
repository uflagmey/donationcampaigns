<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

/**
 * An auth object that grants a named SET of permissions, and records asks.
 *
 * The ACP tests have grantable_auth, which is a single boolean. That cannot
 * express the case this listener has to get right: an administrator holding
 * a_donationcampaigns but NOT a_, who would see a link leading straight to a
 * 403. Distinguishing the two permissions is the whole point, so the double
 * must be able to grant them independently.
 *
 * Recording matters for the same reason it does in grantable_auth: proving
 * the link is hidden does not prove the listener asked about the right
 * permissions.
 */
class selective_auth extends \phpbb\auth\auth
{
	/** @var string[] Options that are granted; everything else is refused */
	public $granted = array();

	/** @var string[] Every permission the listener asked about, in order */
	public $checked = array();

	/**
	 * @param string[] $granted
	 */
	public function __construct(array $granted = array())
	{
		$this->granted = $granted;
	}

	public function acl_get($opt, $f = 0)
	{
		$this->checked[] = $opt;

		return in_array($opt, $this->granted, true) ? 1 : 0;
	}
}
