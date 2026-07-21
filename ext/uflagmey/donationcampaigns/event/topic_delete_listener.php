<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use uflagmey\donationcampaigns\service\campaign_service;

/**
 * Cascade cleanup for the topic-deletion path.
 *
 * COORDINATION ONLY. This listener holds no SQL, calls no repository, and
 * knows nothing about the order cleanup must happen in. It normalises the
 * event's topic list and hands it to campaign_service, which owns the
 * ordering constraint and the transaction. See ADR-007.
 *
 * WHAT THIS COVERS. core.delete_topics_before_query fires inside
 * delete_topics() (functions_admin.php:833), which is the hard-deletion path
 * for topics. Forum deletion does NOT route through it — that path is handled
 * by forum_delete_listener.
 *
 * SOFT DELETES DO NOT REACH THIS EVENT, and must not. A soft-deleted topic is
 * recoverable, so its campaign and donations have to survive; restoring the
 * topic to find its donation history gone would be data loss with no undo.
 * Soft deletion routes through \phpbb\content_visibility, which never calls
 * delete_topics().
 *
 * TRANSACTION. Core opens its own transaction at functions_admin.php:817,
 * sixteen lines before this event, so the service's transaction nests inside
 * it. phpBB's rollback is deliberately not nest-aware: it unwinds the whole
 * outer transaction. That is the behaviour we want — if extension cleanup
 * fails, core's topic deletion is abandoned too, rather than leaving deleted
 * topics whose campaigns survive.
 *
 * table_ary IS NOT MODIFIED. Adding our tables to it would let core delete our
 * rows by topic_id, but donations key on campaign_id and would be orphaned,
 * and the ordering guarantee would move out of the service into core's loop.
 */
class topic_delete_listener implements EventSubscriberInterface
{
	/** @var campaign_service */
	protected $campaign_service;

	public function __construct(campaign_service $campaign_service)
	{
		$this->campaign_service = $campaign_service;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.delete_topics_before_query'	=> 'purge_campaigns',
		);
	}

	/**
	 * @param \phpbb\event\data $event
	 * @return void
	 */
	public function purge_campaigns($event)
	{
		$topic_ids = $this->topic_ids($event['topic_ids']);

		if (empty($topic_ids))
		{
			return;
		}

		// Exceptions are deliberately not caught. A cleanup failure must
		// abort core's deletion rather than let it proceed and strand our
		// rows behind a topic that no longer exists.
		$this->campaign_service->purge_for_topics($topic_ids);
	}

	/**
	 * Reduce the event payload to a list of usable topic ids.
	 *
	 * Ids are cast to int and non-positive values dropped. The cast alone
	 * would already stop a string reaching SQL, but a stray 0 or a negative
	 * value carries no meaning and is better removed than queried for.
	 * Duplicates are collapsed so a repeated id cannot widen anything.
	 *
	 * Only topic_ids is read. table_ary is present in the payload and is
	 * none of our business.
	 *
	 * @param mixed $raw
	 * @return int[]
	 */
	protected function topic_ids($raw)
	{
		if (!is_array($raw))
		{
			return array();
		}

		$topic_ids = array();

		foreach ($raw as $topic_id)
		{
			if (is_array($topic_id) || is_object($topic_id))
			{
				continue;
			}

			$topic_id = (int) $topic_id;

			if ($topic_id > 0)
			{
				$topic_ids[$topic_id] = $topic_id;
			}
		}

		return array_values($topic_ids);
	}
}
