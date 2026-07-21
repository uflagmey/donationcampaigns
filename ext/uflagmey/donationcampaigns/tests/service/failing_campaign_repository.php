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
 * A campaign repository whose deletes fail.
 *
 * Used to prove that a cleanup step failing part-way leaves the database
 * exactly as it was. Reads still work, so the service reaches the delete
 * through its normal path rather than failing early for the wrong reason.
 */
class failing_campaign_repository extends campaign_repository
{
	public function delete_by_ids(array $campaign_ids)
	{
		throw new \RuntimeException('cleanup step failed');
	}

	public function delete_by_topic_ids(array $topic_ids)
	{
		throw new \RuntimeException('cleanup step failed');
	}
}
