<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\repository;

/**
 * The campaigns the board-wide list may show (ADR-018).
 *
 * Persistence ONLY. Which forums a viewer may list from, and how core decides
 * topic visibility, are decided by campaign_list_service; this class applies
 * what it is handed, plus the rules that belong to the rows themselves.
 *
 * WHY A SEPARATE CLASS. The list must join three tables — ours, and core's
 * topics and forums — because the visibility filter has to run in SQL:
 * filtering in PHP after a LIMIT would make the page count include rows the
 * viewer may not see. campaign_repository is single-table by design, and
 * topic_repository is read-only access to one core table. A read model for
 * one page keeps both as they are.
 *
 * A campaign is returned only if ALL hold:
 *   - campaign_enabled = 1;
 *   - its topic row exists (INNER JOIN: an orphaned campaign is not listed);
 *   - the topic is not a moved shadow (topic_moved_id = 0) — viewtopic
 *     answers 404 for a shadow, so its box is never seen either;
 *   - the topic's forum is in $forum_ids;
 *   - $visibility_sql holds for the topic (approval / soft delete).
 *
 * Not-found contract: find_listed() returns an empty array, count_listed()
 * int 0. Both answer an empty forum list WITHOUT a query, which also means
 * core's visibility fragment is never evaluated for "no forums" — see
 * campaign_list_service for why that case is dangerous.
 *
 * Only the columns the list renders are selected. The description, the
 * external link and every donor setting never leave the database here, so
 * none of them can leak into the public page by mistake.
 */
class campaign_list_repository
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $campaigns_table;

	/** @var string */
	protected $topics_table;

	/** @var string */
	protected $forums_table;

	/**
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param string $campaigns_table Injected, never assembled from a hard-coded prefix
	 * @param string $topics_table
	 * @param string $forums_table
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, $campaigns_table, $topics_table, $forums_table)
	{
		$this->db = $db;
		$this->campaigns_table = $campaigns_table;
		$this->topics_table = $topics_table;
		$this->forums_table = $forums_table;
	}

	/**
	 * A page of listable campaigns, newest first.
	 *
	 * @param int[]  $forum_ids      Forums the viewer may list from
	 * @param string $visibility_sql Built by \phpbb\content_visibility::get_forums_visibility_sql()
	 *                               for these forums with the alias 't.'. Core
	 *                               SQL, never request data.
	 * @param int    $limit
	 * @param int    $offset
	 * @return array<int, array{campaign_id:int, topic_id:int, campaign_title:string, target_amount:int, collected_amount:int, show_donation_count:bool, campaign_created:int, forum_id:int, forum_name:string}>
	 */
	public function find_listed(array $forum_ids, $visibility_sql, $limit, $offset)
	{
		$forum_ids = array_map('intval', $forum_ids);

		if (empty($forum_ids))
		{
			return array();
		}

		$sql = 'SELECT c.campaign_id, c.topic_id, c.campaign_title, c.target_amount, c.collected_amount,
				c.show_donation_count, c.campaign_created, t.forum_id, f.forum_name
			FROM ' . $this->from() . '
			WHERE ' . $this->where($forum_ids, $visibility_sql) . '
			ORDER BY c.campaign_created DESC, c.campaign_id DESC';
		$result = $this->db->sql_query_limit($sql, (int) $limit, (int) $offset);

		$rows = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = array(
				'campaign_id'			=> (int) $row['campaign_id'],
				'topic_id'				=> (int) $row['topic_id'],
				'campaign_title'		=> (string) $row['campaign_title'],
				// Money: integer minor units, never float.
				'target_amount'			=> (int) $row['target_amount'],
				'collected_amount'		=> (int) $row['collected_amount'],
				'show_donation_count'	=> (bool) $row['show_donation_count'],
				'campaign_created'		=> (int) $row['campaign_created'],
				'forum_id'				=> (int) $row['forum_id'],
				// Stored HTML-escaped by core; handed on exactly as stored.
				'forum_name'			=> (string) $row['forum_name'],
			);
		}
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * How many campaigns find_listed() would return without a limit.
	 *
	 * Built from the same FROM and WHERE, so the page count can never disagree
	 * with the pages.
	 *
	 * @param int[]  $forum_ids
	 * @param string $visibility_sql
	 * @return int
	 */
	public function count_listed(array $forum_ids, $visibility_sql)
	{
		$forum_ids = array_map('intval', $forum_ids);

		if (empty($forum_ids))
		{
			return 0;
		}

		$sql = 'SELECT COUNT(c.campaign_id) AS total
			FROM ' . $this->from() . '
			WHERE ' . $this->where($forum_ids, $visibility_sql);
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * @return string
	 */
	protected function from()
	{
		return $this->campaigns_table . ' c, ' . $this->topics_table . ' t, ' . $this->forums_table . ' f';
	}

	/**
	 * The shared filter. The forum list is applied here even though the
	 * visibility fragment restricts forums too: the repository must not
	 * depend on the fragment alone for the forum boundary.
	 *
	 * @param int[]  $forum_ids Non-empty, already cast
	 * @param string $visibility_sql
	 * @return string
	 */
	protected function where(array $forum_ids, $visibility_sql)
	{
		return 't.topic_id = c.topic_id
				AND f.forum_id = t.forum_id
				AND c.campaign_enabled = 1
				AND t.topic_moved_id = 0
				AND ' . $this->db->sql_in_set('t.forum_id', $forum_ids) . '
				AND ' . $visibility_sql;
	}
}
