<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\service\campaign_service;

/**
 * A campaign service that counts how it was called.
 *
 * The listener must batch: one call carrying every topic id, not one call per
 * topic. Per-topic calls would open a transaction each and multiply the work
 * on a mass delete.
 */
class counting_campaign_service extends campaign_service
{
	/** @var int */
	public $purge_calls = 0;

	/** @var array */
	public $purge_arguments = array();

	public function purge_for_topics(array $topic_ids)
	{
		$this->purge_calls++;
		$this->purge_arguments[] = $topic_ids;

		return parent::purge_for_topics($topic_ids);
	}
}
