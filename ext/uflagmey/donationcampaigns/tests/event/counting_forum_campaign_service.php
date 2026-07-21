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
 * Records how the forum listener delegates.
 *
 * The listener must pass a forum id and let the service resolve the topics.
 * Handing over a topic list would move that resolution to the caller, which is
 * exactly where it gets the attachment-derived payload wrong.
 */
class counting_forum_campaign_service extends campaign_service
{
	/** @var int */
	public $forum_calls = 0;

	/** @var array */
	public $forum_arguments = array();

	/** @var int Calls to purge_for_topics that did NOT come from purge_for_forum */
	public $topic_calls_from_outside = 0;

	/** @var bool */
	protected $inside_forum_purge = false;

	public function purge_for_forum($forum_id)
	{
		$this->forum_calls++;
		$this->forum_arguments[] = $forum_id;

		$this->inside_forum_purge = true;

		try
		{
			return parent::purge_for_forum($forum_id);
		}
		finally
		{
			$this->inside_forum_purge = false;
		}
	}

	public function purge_for_topics(array $topic_ids)
	{
		if (!$this->inside_forum_purge)
		{
			$this->topic_calls_from_outside++;
		}

		return parent::purge_for_topics($topic_ids);
	}
}
