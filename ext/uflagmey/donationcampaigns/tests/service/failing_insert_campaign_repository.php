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
 * An insert that fails for a reason that is NOT a duplicate topic.
 *
 * The service must not blame every failed insert on the unique index: a
 * connection error reported as "that topic already has a campaign" would send
 * an administrator hunting for a campaign that does not exist.
 */
class failing_insert_campaign_repository extends campaign_repository
{
	public function insert(array $data)
	{
		throw new \RuntimeException('the database went away');
	}
}
