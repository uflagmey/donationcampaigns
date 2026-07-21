<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\repository;

/**
 * Donation persistence.
 *
 * Persistence ONLY. No validation, no transactions, no authorisation and no
 * total bookkeeping — those belong to donation_service.
 *
 * sum_by_campaign() is the authoritative source of a campaign's total. Every
 * other representation of that number, including campaigns.collected_amount,
 * is a cache of this one query and is recomputed from it, never adjusted by
 * delta arithmetic.
 *
 * Not-found contract, matching campaign_repository:
 *   - single-row reads return null
 *   - list reads return an empty array
 *   - count and sum reads return int 0
 *
 * Values are cast to their intended PHP types at this boundary, so no consumer
 * has to remember that the database hands back strings.
 */
class donation_repository
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $donations_table;

	/**
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param string $donations_table Injected, never assembled from a hard-coded prefix
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, $donations_table)
	{
		$this->db = $db;
		$this->donations_table = $donations_table;
	}

	/**
	 * @param int $donation_id
	 * @return array|null Hydrated donation, or null when it does not exist
	 */
	public function find_by_id($donation_id)
	{
		$sql = 'SELECT * FROM ' . $this->donations_table . '
			WHERE donation_id = ' . (int) $donation_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return ($row === false) ? null : $this->hydrate($row);
	}

	/**
	 * Every donation belonging to a campaign, newest first.
	 *
	 * Visibility is expressed by choosing a method rather than by a boolean
	 * argument. An earlier tri-state parameter left the meaning of false
	 * undefined — it behaved as "no filter", which is not what a caller
	 * passing false would expect.
	 *
	 * @param int $campaign_id
	 * @return array Hydrated donations; empty array when none match
	 */
	public function find_by_campaign($campaign_id)
	{
		$sql = $this->select_for_campaign($campaign_id);
		$result = $this->db->sql_query($sql);

		return $this->hydrate_all($result);
	}

	/**
	 * The donations a campaign may list by name, newest first.
	 *
	 * donation_public governs name visibility only. These rows are a subset of
	 * find_by_campaign() for display; they are NEVER the basis of a total.
	 *
	 * @param int $campaign_id
	 * @param int $limit
	 * @return array Hydrated donations; empty array when none match
	 */
	public function find_public_by_campaign($campaign_id, $limit)
	{
		$sql = $this->select_for_campaign($campaign_id, true);
		$result = $this->db->sql_query_limit($sql, (int) $limit, 0);

		return $this->hydrate_all($result);
	}

	/**
	 * One page of a campaign's donations, newest first.
	 *
	 * Named explicitly rather than adding optional parameters to
	 * find_by_campaign(), so no caller has to guess what a bare limit means.
	 * The ACP donation list is the consumer; the public donor list uses
	 * find_public_by_campaign().
	 *
	 * Private donations ARE included: the ACP manages every receipt, and
	 * donation_public governs only what the front end names.
	 *
	 * @param int $campaign_id
	 * @param int $limit
	 * @param int $offset
	 * @return array Hydrated donations; empty array beyond the last page
	 */
	public function find_page_by_campaign($campaign_id, $limit, $offset = 0)
	{
		$sql = $this->select_for_campaign($campaign_id);
		$result = $this->db->sql_query_limit($sql, (int) $limit, max(0, (int) $offset));

		return $this->hydrate_all($result);
	}

	/**
	 * The donation_id tie-break makes the ordering total. Without it two
	 * donations recorded in the same second have no defined relative order, and
	 * a limited list can show one row while silently dropping another.
	 *
	 * @param int $campaign_id
	 * @param bool $public_only
	 * @return string
	 */
	protected function select_for_campaign($campaign_id, $public_only = false)
	{
		$sql = 'SELECT * FROM ' . $this->donations_table . '
			WHERE campaign_id = ' . (int) $campaign_id;

		if ($public_only)
		{
			$sql .= ' AND donation_public = 1';
		}

		return $sql . ' ORDER BY donation_time DESC, donation_id DESC';
	}

	/**
	 * @param mixed $result
	 * @return array
	 */
	protected function hydrate_all($result)
	{
		$donations = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$donations[] = $this->hydrate($row);
		}
		$this->db->sql_freeresult($result);

		return $donations;
	}

	/**
	 * How many donations a campaign has, public or not.
	 *
	 * donation_public governs whether a donor's name is shown, never whether
	 * the donation happened, so it plays no part in this count.
	 *
	 * @param int $campaign_id
	 * @return int
	 */
	public function count_by_campaign($campaign_id)
	{
		$sql = 'SELECT COUNT(donation_id) AS total FROM ' . $this->donations_table . '
			WHERE campaign_id = ' . (int) $campaign_id;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * How many donations exist on the whole board.
	 *
	 * Used only to decide whether a settings change would reinterpret stored
	 * money. A COUNT rather than a fetch, because the answer is a yes/no.
	 *
	 * @return int
	 */
	public function count_all()
	{
		$sql = 'SELECT COUNT(donation_id) AS total FROM ' . $this->donations_table;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * How many of a campaign's donations may be listed by name.
	 *
	 * Used to work out how many donors were truncated from a limited display
	 * list. A COUNT rather than counting a fetched set, so a campaign with
	 * thousands of donations does not load them all to render "and 12 others".
	 *
	 * @param int $campaign_id
	 * @return int
	 */
	public function count_public_by_campaign($campaign_id)
	{
		$sql = 'SELECT COUNT(donation_id) AS total FROM ' . $this->donations_table . '
			WHERE campaign_id = ' . (int) $campaign_id . '
				AND donation_public = 1';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * The authoritative campaign total, in integer minor units.
	 *
	 * SUM() returns NULL when no row matches; the cast makes that int 0. A null
	 * propagating into collected_amount would render as an empty progress bar
	 * rather than a zeroed one.
	 *
	 * Non-public donations are included, for the same reason as in
	 * count_by_campaign().
	 *
	 * @param int $campaign_id
	 * @return int Minor units
	 */
	public function sum_by_campaign($campaign_id)
	{
		$sql = 'SELECT SUM(donation_amount) AS total FROM ' . $this->donations_table . '
			WHERE campaign_id = ' . (int) $campaign_id;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * @param array $data
	 * @return int New donation id
	 */
	public function insert(array $data)
	{
		$sql = 'INSERT INTO ' . $this->donations_table . ' '
			. $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	/**
	 * @param int $donation_id
	 * @param array $data
	 * @return void
	 */
	public function update($donation_id, array $data)
	{
		$sql = 'UPDATE ' . $this->donations_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			WHERE donation_id = ' . (int) $donation_id;
		$this->db->sql_query($sql);
	}

	/**
	 * @param int[] $donation_ids
	 * @return void
	 */
	public function delete_by_ids(array $donation_ids)
	{
		$donation_ids = $this->to_int_list($donation_ids);

		if (empty($donation_ids))
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->donations_table . '
			WHERE ' . $this->db->sql_in_set('donation_id', $donation_ids);
		$this->db->sql_query($sql);
	}

	/**
	 * Bulk delete for the deletion cascade.
	 *
	 * Must run BEFORE the campaign rows are removed: afterwards the campaign
	 * ids are unresolvable and these donations are orphaned permanently. See
	 * specification section 7.3.4.
	 *
	 * @param int[] $campaign_ids
	 * @return void
	 */
	public function delete_by_campaign_ids(array $campaign_ids)
	{
		$campaign_ids = $this->to_int_list($campaign_ids);

		if (empty($campaign_ids))
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->donations_table . '
			WHERE ' . $this->db->sql_in_set('campaign_id', $campaign_ids);
		$this->db->sql_query($sql);
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
			'donation_id'		=> (int) $row['donation_id'],
			'campaign_id'		=> (int) $row['campaign_id'],
			// Money: integer minor units, never float.
			'donation_amount'	=> (int) $row['donation_amount'],
			'donor_name'		=> (string) $row['donor_name'],
			'donation_time'		=> (int) $row['donation_time'],
			'donation_public'	=> (bool) $row['donation_public'],
			'donation_created'	=> (int) $row['donation_created'],
			'donation_updated'	=> (int) $row['donation_updated'],
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
