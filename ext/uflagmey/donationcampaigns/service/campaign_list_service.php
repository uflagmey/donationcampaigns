<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

use uflagmey\donationcampaigns\repository\campaign_list_repository;
use uflagmey\donationcampaigns\repository\donation_repository;

/**
 * Which campaigns the board-wide list shows to the current viewer (ADR-018).
 *
 * READ ONLY. This service owns the permission half of the list's visibility
 * rule and no SQL; the repository applies the result in one query so the
 * page count stays right.
 *
 * A campaign is listed only if ALL hold — the same campaigns the viewer
 * would see as a box in their topics:
 *
 *   1. it is enabled;                                      (repository)
 *   2. its topic exists and is not a moved shadow;         (repository)
 *   3. the viewer has f_read on the topic's forum;         (here)
 *   4. the topic is visible to the viewer — core's
 *      content_visibility decides approval / soft delete;  (here → repository)
 *   5. the forum is not password-protected, or this
 *      session has unlocked it. viewtopic shows the
 *      forum login box instead of the topic otherwise.     (here)
 *
 * THE EMPTY-LIST TRAP. content_visibility::get_forums_visibility_sql()
 * intersects the viewer's m_approve forums with the given forum list only
 * when that list is non-empty; with an empty list every m_approve forum
 * would pass, readable or not. So when no forum is left after rules 3 and 5,
 * this service answers "nothing" before core's fragment is ever built.
 *
 * Kept apart from campaign_service on purpose: campaign_service holds the
 * campaign rules and no authorisation (that lives in access); this class
 * exists only for the list and needs the viewer's permissions.
 *
 * TRANSACTION BOUNDARIES: none — reads only.
 */
class campaign_list_service
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\content_visibility */
	protected $visibility;

	/** @var \phpbb\user */
	protected $user;

	/** @var campaign_list_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\content_visibility $visibility,
		\phpbb\user $user,
		campaign_list_repository $campaigns,
		donation_repository $donations
	)
	{
		$this->auth = $auth;
		$this->visibility = $visibility;
		$this->user = $user;
		$this->campaigns = $campaigns;
		$this->donations = $donations;
	}

	/**
	 * How many campaigns the viewer may see in the list.
	 *
	 * @return int
	 */
	public function count_public()
	{
		$forum_ids = $this->listable_forum_ids();

		if (empty($forum_ids))
		{
			return 0;
		}

		return $this->campaigns->count_listed($forum_ids, $this->visibility_sql($forum_ids));
	}

	/**
	 * A page of the campaigns the viewer may see, newest first.
	 *
	 * Each row is campaign_list_repository::find_listed()'s row plus
	 * 'donation_count' (int). The count is looked up only for campaigns that
	 * show it, and is 0 for the others, so a hidden count never leaves here.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function list_public($limit, $offset)
	{
		$forum_ids = $this->listable_forum_ids();

		if (empty($forum_ids))
		{
			return array();
		}

		$rows = $this->campaigns->find_listed($forum_ids, $this->visibility_sql($forum_ids), $limit, $offset);

		$counted = array();
		foreach ($rows as $row)
		{
			if ($row['show_donation_count'])
			{
				$counted[] = $row['campaign_id'];
			}
		}

		$counts = $this->donations->count_by_campaign_ids($counted);

		foreach ($rows as $index => $row)
		{
			$id = $row['campaign_id'];

			$rows[$index]['donation_count'] = ($row['show_donation_count'] && isset($counts[$id])) ? $counts[$id] : 0;
		}

		return $rows;
	}

	/**
	 * Forums the viewer may list from: f_read (rule 3) minus the password
	 * forums this session has not unlocked (rule 5).
	 *
	 * @return int[] Possibly empty
	 */
	protected function listable_forum_ids()
	{
		$readable = array_map('intval', array_keys($this->auth->acl_getf('f_read', true)));
		$locked = array_map('intval', array_keys($this->user->get_passworded_forums()));

		return array_values(array_diff($readable, $locked));
	}

	/**
	 * Rule 4, from core. Only ever called with a NON-EMPTY forum list.
	 *
	 * @param int[] $forum_ids
	 * @return string
	 */
	protected function visibility_sql(array $forum_ids)
	{
		return $this->visibility->get_forums_visibility_sql('topic', $forum_ids, 't.');
	}
}
