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
 * Campaign management moved out of the ACP: it is now reached from the topic,
 * so authorization is forum-scoped rather than a global ACP gate. This service
 * encodes that rule once, and every controller and the topic-tools link consult
 * it. Nothing here handles a request, renders a template or reads input — it
 * answers three yes/no questions about the current user and a forum.
 *
 * The rule:
 *   - is_administrator()        a_donationcampaigns, the global override and the
 *                               ACP gate.
 *   - can_manage($forum_id)     administrator, OR m_donationcampaigns_manage in
 *                               that forum. Governs the campaign shell.
 *   - can_manage_donations()    administrator, OR m_donationcampaigns_donations
 *                               in that forum. Governs the money ledger, and is
 *                               deliberately independent of can_manage().
 *
 * The forum id is cast to int at this boundary. The caller derives it from the
 * server-loaded topic and never from the request, so a moderator's reach cannot
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
		return $this->is_administrator()
			|| (bool) $this->auth->acl_get('m_donationcampaigns_manage', (int) $forum_id);
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
		return $this->is_administrator()
			|| (bool) $this->auth->acl_get('m_donationcampaigns_donations', (int) $forum_id);
	}
}
