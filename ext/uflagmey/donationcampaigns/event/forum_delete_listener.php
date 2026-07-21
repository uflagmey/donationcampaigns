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
 * Cascade cleanup for the forum-deletion path.
 *
 * WHY THIS EXISTS SEPARATELY FROM topic_delete_listener. Deleting a forum does
 * NOT call delete_topics(). acp_forums::delete_forum_content() removes the
 * forum's topics directly, with a DELETE over TOPICS_TABLE by forum_id, so
 * core.delete_topics_before_query never fires and the topic cascade never sees
 * those topics. Without this listener, deleting a forum would leave every
 * campaign in it behind, pointing at topics that no longer exist. See ADR-007.
 *
 * THE PAYLOAD'S topic_ids IS NOT USABLE FOR CLEANUP, and this is the whole
 * reason this listener passes a forum id rather than a topic list. Core builds
 * that array like this (acp_forums.php:1867):
 *
 *     SELECT a.topic_id FROM posts p, attachments a
 *      WHERE p.forum_id = $forum_id AND a.in_message = 0
 *        AND a.topic_id = p.topic_id
 *
 * It is a join against the ATTACHMENTS table, gathered so core can delete the
 * attachments. It therefore contains only topics that HAVE an attachment, and
 * repeats a topic once per attachment. A forum's ordinary topics are absent
 * from it entirely. Trusting it would silently leave most campaigns behind —
 * silently, because nothing errors and the remaining rows are invisible.
 *
 * The service resolves the real topic list itself through topic_repository.
 * See specification section 7.3.6.
 *
 * ORDERING. The event fires at acp_forums.php:2011, BEFORE core's
 * `DELETE FROM ... WHERE forum_id` loop at 2013. The forum's topics therefore
 * still exist when we resolve them. That ordering is load-bearing: after the
 * loop the topic rows are gone and the campaigns are unresolvable.
 *
 * TRANSACTION. Core opens one at acp_forums.php:1865, so the service's
 * transaction nests inside it and a cleanup failure aborts the forum deletion
 * as a whole.
 */
class forum_delete_listener implements EventSubscriberInterface
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
			'core.delete_forum_content_before_query'	=> 'purge_campaigns',
		);
	}

	/**
	 * @param \phpbb\event\data $event
	 * @return void
	 */
	public function purge_campaigns($event)
	{
		// forum_id is the ONLY field read. topic_ids, table_ary and post_counts
		// are all present in the payload and none of them are our business.
		$forum_id = (int) $event['forum_id'];

		if ($forum_id <= 0)
		{
			return;
		}

		// Exceptions are deliberately not caught: a cleanup failure must abort
		// core's forum deletion rather than let it proceed and strand our rows.
		$this->campaign_service->purge_for_forum($forum_id);
	}
}
