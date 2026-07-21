<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\repository\donation_repository;

/**
 * A donation repository that can write but cannot sum.
 *
 * The row change succeeds and the recalculation then fails, which is the
 * dangerous case: without a rollback the donation would exist while the
 * stored total still described the world before it.
 */
class failing_sum_donation_repository extends donation_repository
{
	public function sum_by_campaign($campaign_id)
	{
		throw new \RuntimeException('sum failed');
	}
}
