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
 * Reproduces the one-campaign-per-topic race.
 *
 * Between validate() and insert(), a competing request claims the topic. This
 * double does exactly that: it inserts the rival row immediately before the
 * real insert runs, so the UNIQUE index rejects ours — which is what the
 * losing request experiences on a live board.
 */
class racing_campaign_repository extends campaign_repository
{
	/** @var int Topic a rival request claims first */
	public $occupy_topic = 0;

	public function insert(array $data)
	{
		if ($this->occupy_topic > 0)
		{
			$rival = array_merge($data, array('topic_id' => $this->occupy_topic, 'campaign_title' => 'Rival'));

			// Claim it through the parent, so the row is genuinely there.
			$this->occupy_topic = 0;
			parent::insert($rival);
		}

		return parent::insert($data);
	}
}
