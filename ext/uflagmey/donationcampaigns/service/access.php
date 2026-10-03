<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

/**
 * The single source of truth for who may manage campaigns and donations.
 *
 * Campaign management is reached from the topic, so authorization is
 * forum-scoped rather than a global ACP gate. This service encodes that rule
 * once, and every controller and the topic-tools link consult it. Nothing here
 * handles a request, renders a template or reads input — it answers yes/no
 * questions about the current user and a forum.
 *
 * The rule (ADR-016):
 *   - is_administrator()        a_donationcampaigns, the global override and the
 *                               ACP gate.
 *   - can_manage($forum_id)     f_read in that forum, AND administrator OR
 *                               f_donationcampaigns_manage in that forum.
 *                               Governs the campaign shell.
 *   - can_manage_donations()    f_read in that forum, AND administrator OR
 *                               f_donationcampaigns_donations in that forum.
 *                               Governs the money ledger, and is deliberately
 *                               independent of can_manage().
 *
 * FORUM permissions, not moderator permissions. An f_* grant has no side effect
 * beyond itself, so a board can hand campaign management to any group without
 * making it a moderator group (no MCP access, no "Moderator" listing).
 *
 * READ ACCESS IS REQUIRED, for everyone including the administrator override:
 * nobody manages from the frontend a topic they could not open. phpBB's forum
 * roles make a "manage but not read" combination easy to create by accident;
 * this closes it in one place.
 *
 * The forum id is cast to int at this boundary. The caller derives it from the
 * server-loaded topic and never from the request, so a manager's reach cannot
 * be widened by a forged forum id; the cast is the last line of that defence.
 */
class access
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/**
	 * @param \phpbb\auth\auth $auth
	 */
	public function __construct(\phpbb\auth\auth $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * The global override and ACP gate.
	 *
	 * @return bool
	 */
	public function is_administrator()
	{
		return (bool) $this->auth->acl_get('a_donationcampaigns');
	}

	/**
	 * May the current user manage the campaign shell in this forum?
	 *
	 * @param int $forum_id The topic's current forum, derived server-side.
	 * @return bool
	 */
	public function can_manage($forum_id)
	{
		return $this->can_read($forum_id)
			&& ($this->is_administrator()
				|| (bool) $this->auth->acl_get('f_donationcampaigns_manage', (int) $forum_id));
	}

	/**
	 * May the current user manage the confirmed-donation ledger in this forum?
	 *
	 * Independent of can_manage(): a board owner may grant one without the other,
	 * and the money permission is the stricter of the two.
	 *
	 * @param int $forum_id The topic's current forum, derived server-side.
	 * @return bool
	 */
	public function can_manage_donations($forum_id)
	{
		return $this->can_read($forum_id)
			&& ($this->is_administrator()
				|| (bool) $this->auth->acl_get('f_donationcampaigns_donations', (int) $forum_id));
	}

	/**
	 * phpBB's own read permission for the forum: the precondition for every
	 * frontend management action.
	 *
	 * @param int $forum_id
	 * @return bool
	 */
	protected function can_read($forum_id)
	{
		return (bool) $this->auth->acl_get('f_read', (int) $forum_id);
	}
}
