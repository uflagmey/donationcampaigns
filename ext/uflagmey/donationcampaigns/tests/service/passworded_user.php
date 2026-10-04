<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

/**
 * A real \phpbb\user whose locked password forums are set by the test.
 *
 * Core's get_passworded_forums() queries the forums and forum-access tables
 * through the global $db and the session id. What the list service relies on
 * is only its answer — "these forums are locked for this session" — so that
 * answer is what a test controls.
 */
class passworded_user extends \phpbb\user
{
	/** @var array<int, int> forum_id => forum_id, as core returns it */
	public $locked = array();

	public function get_passworded_forums()
	{
		return $this->locked;
	}
}
