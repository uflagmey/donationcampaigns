<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\repository;

/**
 * Campaign persistence.
 *
 * Persistence ONLY. This class contains no validation, no URL rules, no
 * transaction handling, no progress calculation, no template preparation and
 * no authorisation. Those belong to campaign_service and donation_service.
 *
 * Not-found contract:
 *   - single-row reads return null
 *   - list reads return an empty array
 *   - count reads return int 0
 *   - existence checks return bool
 *
 * Values are cast to their intended PHP types at this boundary, so no consumer
 * has to remember that the database hands back strings.
 */
class campaign_repository
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $campaigns_table;

	/**
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param string $campaigns_table Injected, never assembled from a hard-coded prefix
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, $campaigns_table)
	{
		$this->db = $db;
		$this->campaigns_table = $campaigns_table;
	}

	/**
	 * @param int $campaign_id
	 * @return array|null Hydrated campaign, or null when it does not exist
	 */
	public function find_by_id($campaign_id)
	{
		$sql = 'SELECT * FROM ' . $this->campaigns_table . '
			WHERE campaign_id = ' . (int) $campaign_id;

		return $this->fetch_one($sql);
	}

	/**
	 * The campaign attached to a topic, enabled or not.
	 *
	 * Filtering on campaign_enabled is a business rule and belongs to
	 * campaign_service; a repository that hid disabled rows could not serve the
	 * ACP, which must show them.
	 *
	 * @param int $topic_id
	 * @return array|null
	 */
	public function find_by_topic_id($topic_id)
	{
		$sql = 'SELECT * FROM ' . $this->campaigns_table . '
			WHERE topic_id = ' . (int) $topic_id;

		return $this->fetch_one($sql);
	}

	/**
	 * @param int $topic_id
	 * @return bool
	 */
	public function exists_for_topic($topic_id)
	{
		$sql = 'SELECT campaign_id FROM ' . $this->campaigns_table . '
			WHERE topic_id = ' . (int) $topic_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return array Hydrated campaigns, newest first; empty array when none
	 */
	public function find_all($limit = 25, $offset = 0)
	{
		$sql = 'SELECT * FROM ' . $this->campaigns_table . '
			ORDER BY campaign_created DESC, campaign_id DESC';
		$result = $this->db->sql_query_limit($sql, (int) $limit, (int) $offset);

		$campaigns = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$campaigns[] = $this->hydrate($row);
		}
		$this->db->sql_freeresult($result);

		return $campaigns;
	}

	/**
	 * @return int
	 */
	public function count_all()
	{
		$sql = 'SELECT COUNT(campaign_id) AS total FROM ' . $this->campaigns_table;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * @param array $data
	 * @return int New campaign id
	 */
	public function insert(array $data)
	{
		$sql = 'INSERT INTO ' . $this->campaigns_table . ' '
			. $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	/**
	 * @param int $campaign_id
	 * @param array $data
	 * @return void
	 */
	public function update($campaign_id, array $data)
	{
		$sql = 'UPDATE ' . $this->campaigns_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			WHERE campaign_id = ' . (int) $campaign_id;
		$this->db->sql_query($sql);
	}

	/**
	 * @param int[] $campaign_ids
	 * @return void
	 */
	public function delete_by_ids(array $campaign_ids)
	{
		$campaign_ids = $this->to_int_list($campaign_ids);

		if (empty($campaign_ids))
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->campaigns_table . '
			WHERE ' . $this->db->sql_in_set('campaign_id', $campaign_ids);
		$this->db->sql_query($sql);
	}

	/**
	 * @param int[] $topic_ids
	 * @return void
	 */
	public function delete_by_topic_ids(array $topic_ids)
	{
		$topic_ids = $this->to_int_list($topic_ids);

		if (empty($topic_ids))
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->campaigns_table . '
			WHERE ' . $this->db->sql_in_set('topic_id', $topic_ids);
		$this->db->sql_query($sql);
	}

	/**
	 * Resolve campaign ids from topic ids, for the deletion cascade.
	 *
	 * The cascade must obtain these BEFORE the campaign rows are removed:
	 * afterwards they are unresolvable and the donation rows are orphaned
	 * permanently. See specification section 7.3.4.
	 *
	 * @param int[] $topic_ids
	 * @return int[] Empty array when nothing matches
	 */
	public function find_campaign_ids_for_topics(array $topic_ids)
	{
		$topic_ids = $this->to_int_list($topic_ids);

		if (empty($topic_ids))
		{
			return array();
		}

		$sql = 'SELECT campaign_id FROM ' . $this->campaigns_table . '
			WHERE ' . $this->db->sql_in_set('topic_id', $topic_ids);
		$result = $this->db->sql_query($sql);

		$ids = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['campaign_id'];
		}
		$this->db->sql_freeresult($result);

		return $ids;
	}

	/**
	 * Write the denormalised total.
	 *
	 * Deliberately the ONLY way collected_amount can be written. It takes an
	 * already-computed integer; computing it is donation_service's job.
	 *
	 * @param int $campaign_id
	 * @param int $amount Minor units
	 * @return void
	 */
	public function set_collected_amount($campaign_id, $amount)
	{
		$sql = 'UPDATE ' . $this->campaigns_table . '
			SET collected_amount = ' . (int) $amount . '
			WHERE campaign_id = ' . (int) $campaign_id;
		$this->db->sql_query($sql);
	}

	/**
	 * @param string $sql
	 * @return array|null
	 */
	protected function fetch_one($sql)
	{
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return ($row === false) ? null : $this->hydrate($row);
	}

	/**
	 * Cast a raw database row to the documented PHP types.
	 *
	 * @param array $row
	 * @return array
	 */
	protected function hydrate(array $row)
	{
		return array(
			'campaign_id'			=> (int) $row['campaign_id'],
			'topic_id'				=> (int) $row['topic_id'],
			'campaign_title'		=> (string) $row['campaign_title'],
			'campaign_desc'			=> (string) $row['campaign_desc'],
			'desc_bbcode_uid'		=> (string) $row['desc_bbcode_uid'],
			'desc_bbcode_bitfield'	=> (string) $row['desc_bbcode_bitfield'],
			'desc_bbcode_options'	=> (int) $row['desc_bbcode_options'],
			// Money: integer minor units, never float.
			'target_amount'			=> (int) $row['target_amount'],
			'collected_amount'		=> (int) $row['collected_amount'],
			'campaign_enabled'		=> (bool) $row['campaign_enabled'],
			'show_donor_names'		=> (bool) $row['show_donor_names'],
			'show_donation_count'	=> (bool) $row['show_donation_count'],
			'external_url'			=> (string) $row['external_url'],
			// The label on the button pointing at external_url. Plain text:
			// no markup pipeline, no provider semantics.
			'external_link_text'	=> (string) $row['external_link_text'],
			'campaign_created'		=> (int) $row['campaign_created'],
			'campaign_updated'		=> (int) $row['campaign_updated'],
		);
	}

	/**
	 * Cast every element to int, so no caller can smuggle a string into SQL.
	 *
	 * @param array $values
	 * @return int[]
	 */
	protected function to_int_list(array $values)
	{
		return array_map('intval', $values);
	}
}
