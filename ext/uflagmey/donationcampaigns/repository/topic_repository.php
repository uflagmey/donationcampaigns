<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\repository;

/**
 * Reads from phpBB's core topics table.
 *
 * READ ONLY. The extension never writes to a core table.
 *
 * Kept separate from campaign_repository because these queries touch a table
 * the extension does not own, and separate from campaign_service because
 * services contain no persistence.
 *
 * Not-found contract: topic_exists() returns bool;
 * find_topic_ids_by_forum() returns an empty array, never null.
 */
class topic_repository
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $topics_table;

	/**
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param string $topics_table Injected, never assembled from a hard-coded prefix
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, $topics_table)
	{
		$this->db = $db;
		$this->topics_table = $topics_table;
	}

	/**
	 * Whether a readable topic with this id exists.
	 *
	 * SHADOWS ARE EXCLUDED. A shadow is the stub phpBB leaves behind when a
	 * topic is moved: topic_moved_id points at the real topic, the row has no
	 * posts, and viewtopic.php answers 404 "The requested topic does not
	 * exist" for it — verified against 3.3.17. Since core treats a shadow
	 * exactly like a missing topic, so does this.
	 *
	 * That matters because this method's only caller is campaign validation,
	 * and a campaign on a shadow would be unreachable: the box renders on
	 * viewtopic, and viewtopic refuses to render the page at all.
	 *
	 * Cleanup deliberately does NOT share this rule — see
	 * find_topic_ids_by_forum(), which still returns shadows so that a
	 * campaign attached to one before this rule existed is still removed with
	 * its forum.
	 *
	 * @param int $topic_id
	 * @return bool
	 */
	public function topic_exists($topic_id)
	{
		$topic_id = (int) $topic_id;

		if ($topic_id <= 0)
		{
			return false;
		}

		// Cast inline as well as above, matching every other query here: the
		// safety is then visible on the line that builds the SQL rather than
		// several lines earlier.
		$sql = 'SELECT topic_id FROM ' . $this->topics_table . '
			WHERE topic_id = ' . (int) $topic_id . '
				AND topic_moved_id = 0';
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false;
	}

	/**
	 * A readable topic's id, current forum and title, or null.
	 *
	 * This is the authorization anchor for the frontend controllers: they load
	 * the topic here, derive its CURRENT forum_id from the loaded row (never
	 * from the request), and authorize against that forum. A moved shadow is
	 * excluded exactly as topic_exists() excludes it — core answers 404 for a
	 * shadow, so a campaign action on one is refused as not-found, and a topic
	 * that has been moved is authorized in its destination forum on the next
	 * request because forum_id is re-read here each time.
	 *
	 * @param int $topic_id
	 * @return array{topic_id:int,forum_id:int,topic_title:string}|null
	 */
	public function find($topic_id)
	{
		$topic_id = (int) $topic_id;

		if ($topic_id <= 0)
		{
			return null;
		}

		$sql = 'SELECT topic_id, forum_id, topic_title FROM ' . $this->topics_table . '
			WHERE topic_id = ' . (int) $topic_id . '
				AND topic_moved_id = 0';
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row === false)
		{
			return null;
		}

		return array(
			'topic_id'		=> (int) $row['topic_id'],
			'forum_id'		=> (int) $row['forum_id'],
			'topic_title'	=> (string) $row['topic_title'],
		);
	}

	/**
	 * Topic titles, keyed by topic id.
	 *
	 * The ACP campaign list names topics rather than making an administrator
	 * recognise numeric ids. Read here rather than joined into the campaign
	 * query, so that the public campaign read path — which never needs a title
	 * — stays a single-table lookup.
	 *
	 * One query for the whole page, not one per row.
	 *
	 * @param int[] $topic_ids
	 * @return array<int, string> Missing topics are absent, not empty strings
	 */
	public function find_titles_by_ids(array $topic_ids)
	{
		$topic_ids = array_unique(array_map('intval', $topic_ids));

		if (empty($topic_ids))
		{
			return array();
		}

		$sql = 'SELECT topic_id, topic_title FROM ' . $this->topics_table . '
			WHERE ' . $this->db->sql_in_set('topic_id', $topic_ids);
		$result = $this->db->sql_query($sql);

		$titles = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$titles[(int) $row['topic_id']] = (string) $row['topic_title'];
		}
		$this->db->sql_freeresult($result);

		return $titles;
	}

	/**
	 * The real topic list for a forum.
	 *
	 * The forum-deletion event supplies a topic_ids payload built from a join
	 * against the attachments table, so it lists only topics WITH attachments
	 * and is not deduplicated. It cannot be used for cleanup; this query is the
	 * correct source. See specification section 7.3.6.
	 *
	 * @param int $forum_id
	 * @return int[] Empty array when the forum holds no topics
	 */
	public function find_topic_ids_by_forum($forum_id)
	{
		$sql = 'SELECT topic_id FROM ' . $this->topics_table . '
			WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query($sql);

		$topic_ids = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$topic_ids[] = (int) $row['topic_id'];
		}
		$this->db->sql_freeresult($result);

		return $topic_ids;
	}
}
