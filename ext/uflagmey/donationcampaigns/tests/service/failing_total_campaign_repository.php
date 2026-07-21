<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\repository\campaign_repository;

/**
 * A campaign repository that can be read but whose total cannot be written.
 *
 * The last step of every mutation is persisting the recomputed total. If that
 * fails, the donation change that preceded it must not survive.
 */
class failing_total_campaign_repository extends campaign_repository
{
	public function set_collected_amount($campaign_id, $amount)
	{
		throw new \RuntimeException('total persistence failed');
	}
}
