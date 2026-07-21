<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\repository\donation_repository;

/**
 * A donation repository whose cascade delete fails, so the first step of the
 * cleanup can be made to break on the real deletion path.
 */
class failing_donation_delete_repository extends donation_repository
{
	public function delete_by_campaign_ids(array $campaign_ids)
	{
		throw new \RuntimeException('donation delete failed');
	}
}
