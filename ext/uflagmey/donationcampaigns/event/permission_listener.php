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
 * Declares the extension's permission to the ACP permissions interface.
 *
 * The migration creates the ACL option, which makes the permission function.
 * This listener makes it VISIBLE. Ship only the migration and the permission
 * exists in the database and resolves correctly, but never appears in the
 * permissions UI, so an administrator cannot grant it through the interface.
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
		$permissions = $event['permissions'];

		$permissions['a_donationcampaigns'] = array(
			// A language key, never a display string, so the label is
			// translatable. Defined in language/en/permissions_donationcampaigns.php,
			// which core auto-discovers by its permissions_ filename prefix.
			'lang'	=> 'ACL_A_DONATIONCAMPAIGNS',
			'cat'	=> 'misc',
		);

		// The whole array must be reassigned. Modifying the retrieved copy in
		// place has no effect, and merging into a fresh array would discard
		// permissions other extensions have already registered.
		$event['permissions'] = $permissions;
	}
}
