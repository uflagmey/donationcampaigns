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

	/** @var int[] the forums a GLOBAL grant expands to in acl_getf() */
	public $known_forums = array(2, 3, 4);

	/** @var string[] every option asked through acl_getf(), in order */
	public $checked_getf = array();

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

	/**
	 * The forum list form of a grant, as core returns it with $clean = true:
	 * forum_id => array(option => 1), only for forums where it is granted.
	 *
	 * A global grant (true) expands to $known_forums, the board's forums in
	 * the fixture, because core answers per forum, never "everywhere".
	 *
	 * @param string $opt
	 * @param bool $clean
	 * @return array<int, array<string, int>>
	 */
	public function acl_getf($opt, $clean = false)
	{
		$this->checked_getf[] = $opt;

		if (!array_key_exists($opt, $this->grants))
		{
			return array();
		}

		$forums = ($this->grants[$opt] === true) ? $this->known_forums : $this->grants[$opt];

		$result = array();
		foreach ($forums as $forum_id)
		{
			$result[(int) $forum_id] = array($opt => 1);
		}

		return $result;
	}
}
