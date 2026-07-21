<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Declares the extension's permissions to the ACP permissions interface.
 *
 * The migrations create the ACL options, which make the permissions function.
 * This listener makes them VISIBLE. Ship only the migrations and the options
 * exist in the database and resolve correctly, but never appear in the
 * permissions UI, so an administrator cannot grant them through the interface.
 *
 * RC2 declares three permissions — the global admin one and the two
 * forum-scoped moderator ones — under a dedicated "Donation Campaigns" category
 * it also registers, so they appear together rather than buried under Misc. The
 * `categories` array is provided by the same `core.permissions` event.
 *
 * This class coordinates only: it holds no persistence logic and touches no
 * database.
 */
class permission_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return array(
			'core.permissions'	=> 'add_permission',
		);
	}

	/**
	 * @param \phpbb\event\data $event
	 */
	public function add_permission($event)
	{
		// A dedicated category groups the three permissions under one heading.
		// Adding to the event's `categories` array is exactly how core declares
		// its own categories (see phpbb\permissions); no extra machinery.
		$categories = $event['categories'];
		$categories['donationcampaigns'] = 'ACL_CAT_DONATIONCAMPAIGNS';
		$event['categories'] = $categories;

		$permissions = $event['permissions'];

		// Labels are language keys, never display strings, so every visible
		// label comes from language/*/permissions_donationcampaigns.php, which
		// core auto-discovers by its permissions_ filename prefix.
		$permissions['a_donationcampaigns'] = array(
			'lang'	=> 'ACL_A_DONATIONCAMPAIGNS',
			'cat'	=> 'donationcampaigns',
		);
		$permissions['m_donationcampaigns_manage'] = array(
			'lang'	=> 'ACL_M_DONATIONCAMPAIGNS_MANAGE',
			'cat'	=> 'donationcampaigns',
		);
		$permissions['m_donationcampaigns_donations'] = array(
			'lang'	=> 'ACL_M_DONATIONCAMPAIGNS_DONATIONS',
			'cat'	=> 'donationcampaigns',
		);

		// The whole array must be reassigned. Modifying the retrieved copy in
		// place has no effect, and merging into a fresh array would discard
		// permissions other extensions have already registered.
		$event['permissions'] = $permissions;
	}
}
