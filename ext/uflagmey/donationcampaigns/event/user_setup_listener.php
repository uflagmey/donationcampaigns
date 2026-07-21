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
 * Loads the extension's audit-log language for every request.
 *
 * The log viewer translates a stored entry by reading $user->lang keyed on the
 * log operation, so the LOG_ keys must already be loaded wherever a viewer runs
 * — the ACP admin-log module AND the MCP moderator-log module. core.user_setup's
 * lang_set_ext is the core-documented hook that feeds add_lang_ext() during user
 * setup (see phpbb\user), and it is the single point that covers both viewers;
 * there is no narrower per-viewer language event.
 *
 * Only the small logs file is loaded here, not the whole common file, to keep
 * the per-request cost minimal. This class coordinates only.
 */
class user_setup_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup'	=> 'load_log_language',
		);
	}

	/**
	 * @param \phpbb\event\data $event
	 */
	public function load_log_language($event)
	{
		// Reassign the whole array: modifying the retrieved copy in place has no
		// effect, and merging into a fresh array would drop sets other
		// extensions have already queued.
		$lang_set_ext = $event['lang_set_ext'];

		$lang_set_ext[] = array(
			'ext_name'	=> 'uflagmey/donationcampaigns',
			'lang_set'	=> 'logs',
		);

		$event['lang_set_ext'] = $lang_set_ext;
	}
}
