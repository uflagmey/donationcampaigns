<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * Donation mutations and campaign-total integrity.
 *
 * Every mutator has the same shape: validate, open a transaction, change the
 * donation row, recompute the campaign total from SUM(donation_amount), write
 * it, commit.
 *
 * There is NO delta arithmetic anywhere in this class — no total + amount, no
 * total - amount, no total + (new - old). That is deliberate and it is the
 * single most important rule in the extension. A delta compounds every past
 * error forward: one missed rollback, one row changed out of band, and the
 * stored figure is permanently wrong with nothing to detect it. Recomputing
 * from the authoritative rows is self-healing instead — any one successful
 * mutation repairs whatever drift preceded it. See ADR-003.
 *
 * collected_amount is a cache. The donation rows are the truth.
 *
 * TRANSACTION BOUNDARIES, per public method:
 *
 *   add_donation()      OWN — insert + recalculate
 *   edit_donation()     OWN — update + recalculate
 *   delete_donation()   OWN — delete + recalculate
 *   delete_donations()  OWN — one transaction for the whole batch; none when
 *                             the batch resolves to nothing
 *   recalculate()       OWN — read the sum and store it
 *
 * Validation runs BEFORE the transaction opens, so a rejected input costs no
 * round trip.
 */
class donation_service
{
	/**
	 * Fields an administrator may set on a donation row.
	 *
	 * campaign_id is absent: it is fixed at creation from the method argument,
	 * never taken from input. Allowing it here would let an edit silently move
	 * money between campaigns, corrupting two totals at once.
	 */
	const WRITABLE_FIELDS = array(
		'donation_amount',
		'donor_name',
		'donation_time',
		'donation_public',
	);

	/**
	 * Ceiling imposed by the UINT storage column.
	 */
	const MAX_AMOUNT = currency_formatter::MAX_MINOR_UNITS;

	/**
	 * Width of the donor_name column, in characters.
	 */
	const MAX_DONOR_NAME_LENGTH = 255;

	/** @var \phpbb\db\driver\driver_interface Injected ONLY to open transactions */
	protected $db;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		campaign_repository $campaigns,
		donation_repository $donations
	)
	{
		$this->db = $db;
		$this->campaigns = $campaigns;
		$this->donations = $donations;
	}

	/**
	 * Record a donation against a campaign.
	 *
	 * TRANSACTION BOUNDARY: own. The insert and the recalculation must both
	 * land or neither, otherwise the total describes a different set of rows
	 * than the table holds.
	 *
	 * @param int $campaign_id
	 * @param array $input
	 * @return int New donation id
	 * @throws donationcampaigns_exception When the campaign is unknown or the
	 *         input is invalid
	 */
	public function add_donation($campaign_id, array $input)
	{
		$campaign_id = (int) $campaign_id;

		$this->assert_campaign_exists($campaign_id);

		$now = time();

		$data = $this->prepare($input);
		// From the argument, never the input.
		$data['campaign_id'] = $campaign_id;
		$data['donation_created'] = $now;
		$data['donation_updated'] = $now;

		return $this->in_transaction(function () use ($data, $campaign_id) {
			$donation_id = $this->donations->insert($data);

			$this->write_total($campaign_id);

			return $donation_id;
		});
	}

	/**
	 * Change an existing donation.
	 *
	 * The donation cannot be moved to another campaign — campaign_id is not a
	 * writable field.
	 *
	 * TRANSACTION BOUNDARY: own.
	 *
	 * @param int $donation_id
	 * @param array $input
	 * @return void
	 * @throws donationcampaigns_exception When the donation is unknown or the
	 *         input is invalid
	 */
	public function edit_donation($donation_id, array $input)
	{
		$donation_id = (int) $donation_id;
		$donation = $this->donations->find_by_id($donation_id);

		if ($donation === null)
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_DONATION_NOT_FOUND');
		}

		$campaign_id = $donation['campaign_id'];

		$data = $this->prepare($input);
		$data['donation_updated'] = time();

		$this->in_transaction(function () use ($donation_id, $data, $campaign_id) {
			$this->donations->update($donation_id, $data);

			$this->write_total($campaign_id);
		});
	}

	/**
	 * Remove one donation.
	 *
	 * TRANSACTION BOUNDARY: own.
	 *
	 * @param int $donation_id
	 * @return void
	 * @throws donationcampaigns_exception When the donation is unknown
	 */
	public function delete_donation($donation_id)
	{
		$donation_id = (int) $donation_id;

		if ($this->donations->find_by_id($donation_id) === null)
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_DONATION_NOT_FOUND');
		}

		$this->delete_donations(array($donation_id));
	}

	/**
	 * Remove several donations, which may belong to different campaigns.
	 *
	 * Unknown ids are ignored rather than rejected: a bulk action races against
	 * anyone else deleting the same rows, and failing the whole batch because
	 * one row already went is worse than completing the rest.
	 *
	 * EVERY campaign the batch touched is recalculated, not only the first.
	 *
	 * TRANSACTION BOUNDARY: own, one for the whole batch. None at all when the
	 * batch resolves to no existing rows.
	 *
	 * @param int[] $donation_ids
	 * @return void
	 */
	public function delete_donations(array $donation_ids)
	{
		$affected = array();
		$existing = array();

		foreach ($donation_ids as $donation_id)
		{
			$donation = $this->donations->find_by_id((int) $donation_id);

			if ($donation === null)
			{
				continue;
			}

			$existing[] = $donation['donation_id'];
			$affected[$donation['campaign_id']] = true;
		}

		if (empty($existing))
		{
			return;
		}

		$campaign_ids = array_keys($affected);

		$this->in_transaction(function () use ($existing, $campaign_ids) {
			$this->donations->delete_by_ids($existing);

			foreach ($campaign_ids as $campaign_id)
			{
				$this->write_total($campaign_id);
			}
		});
	}

	/**
	 * Recompute and store a campaign's total from the authoritative rows.
	 *
	 * Idempotent, and the repair path: an administrator can run it against a
	 * campaign whose figure looks wrong, and the ACP exposes it as an action.
	 * The mutators reach the same code, so there is one implementation of the
	 * rule rather than four.
	 *
	 * TRANSACTION BOUNDARY: own.
	 *
	 * @param int $campaign_id
	 * @return int The recomputed total, in minor units
	 * @throws donationcampaigns_exception When the campaign is unknown
	 */
	public function recalculate($campaign_id)
	{
		$campaign_id = (int) $campaign_id;

		$this->assert_campaign_exists($campaign_id);

		return $this->in_transaction(function () use ($campaign_id) {
			return $this->write_total($campaign_id);
		});
	}

	// ---------------------------------------------------------------- helpers

	/**
	 * Run a unit of work inside one transaction, rolling back on any failure.
	 *
	 * Written once so that no mutator can forget the rollback, and so that
	 * every one of them has provably the same boundary.
	 *
	 * The exception is rethrown rather than swallowed: a caller that saw a
	 * silent success would report a mutation that did not happen.
	 *
	 * @param callable $work
	 * @return mixed Whatever $work returned
	 */
	protected function in_transaction($work)
	{
		$this->db->sql_transaction('begin');

		try
		{
			$result = $work();

			$this->db->sql_transaction('commit');

			return $result;
		}
		catch (\Exception $e)
		{
			$this->db->sql_transaction('rollback');

			throw $e;
		}
	}

	/**
	 * Read the authoritative sum and store it.
	 *
	 * MUST be called inside a transaction opened by the caller: on its own the
	 * read and the write are two statements, and a concurrent mutation between
	 * them would store a total that was never true.
	 *
	 * @param int $campaign_id
	 * @return int
	 * @throws donationcampaigns_exception When the sum exceeds the column width
	 */
	protected function write_total($campaign_id)
	{
		$total = $this->donations->sum_by_campaign($campaign_id);

		if ($total > self::MAX_AMOUNT)
		{
			// Writing this would truncate, and a silently wrong headline figure
			// is worse than a refused operation.
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_TOTAL_OVERFLOW');
		}

		$this->campaigns->set_collected_amount($campaign_id, $total);

		return $total;
	}

	/**
	 * @param int $campaign_id
	 * @return void
	 * @throws donationcampaigns_exception
	 */
	protected function assert_campaign_exists($campaign_id)
	{
		if ($this->campaigns->find_by_id($campaign_id) === null)
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_CAMPAIGN_NOT_FOUND');
		}
	}

	/**
	 * Validate an input array and reduce it to the writable donation fields.
	 *
	 * Throws on the first failure rather than collecting every error, unlike
	 * campaign validation: a donation form has four fields, and the ACP shows
	 * one message above it.
	 *
	 * @param array $input
	 * @return array
	 * @throws donationcampaigns_exception
	 */
	protected function prepare(array $input)
	{
		$data = array_intersect_key($input, array_flip(self::WRITABLE_FIELDS));

		$amount = isset($input['donation_amount']) ? (int) $input['donation_amount'] : 0;

		if ($amount <= 0)
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE');
		}

		if ($amount > self::MAX_AMOUNT)
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE');
		}

		$donor_name = isset($input['donor_name']) ? trim((string) $input['donor_name']) : '';

		// An empty name is valid: it is how an anonymous donation is recorded,
		// and the front end renders it as "Anonymous".
		if (utf8_strlen($donor_name) > self::MAX_DONOR_NAME_LENGTH)
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_DONOR_NAME_TOO_LONG');
		}

		// Absent means "now", which is what the ACP form defaults to. Present
		// but unusable is an error rather than a silent fallback to now.
		if (!isset($input['donation_time']))
		{
			$time = time();
		}
		else
		{
			$time = (int) $input['donation_time'];

			if ($time <= 0)
			{
				throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_TIME_INVALID');
			}
		}

		$data['donation_amount'] = $amount;
		$data['donor_name'] = $donor_name;
		$data['donation_time'] = $time;

		// The flag arrives from an HTML checkbox, so it may be absent, '0',
		// '1' or 'on'. Absent means unchecked, which means not public — the
		// safer default, since it under-discloses rather than over-discloses.
		$data['donation_public'] = empty($input['donation_public']) ? 0 : 1;

		return $data;
	}
}
