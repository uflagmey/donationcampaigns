<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

/**
 * An auth double that grants permissions per forum, and records every ask.
 *
 * The access rule under test is forum-scoped: a manager granted on forum A must
 * be refused on forum B, while a global administrative grant applies to every
 * forum. selective_auth (tests/event) grants by option name regardless of
 * forum, which cannot express that distinction — so this double grants an
 * option either globally (true) or on a specific set of forum ids.
 */
class forum_scoped_auth extends \phpbb\auth\auth
{
	/** @var array<string, true|int[]> option => true (global) or a list of forum ids */
	public $grants;

	/** @var array<int, array{0:string,1:int}> every [option, forum_id] asked, in order */
	public $checked = array();

	/**
	 * @param array<string, true|int[]> $grants
	 */
	public function __construct(array $grants = array())
	{
		$this->grants = $grants;
	}

	public function acl_get($opt, $f = 0)
	{
		$this->checked[] = array($opt, (int) $f);

		if (!array_key_exists($opt, $this->grants))
		{
			return 0;
		}

		$grant = $this->grants[$opt];

		if ($grant === true)
		{
			return 1;
		}

		return in_array((int) $f, $grant, true) ? 1 : 0;
	}
}
