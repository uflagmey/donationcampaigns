<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\acp;

/**
 * Declares the extension's ACP module and its three modes.
 *
 * The module migration reads this file to derive each mode's langname, mode
 * and auth expression, so these values exist in one place rather than being
 * duplicated into the migration where the two copies would drift.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\uflagmey\donationcampaigns\acp\main_module',
			'title'		=> 'ACP_DONATIONCAMPAIGNS',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_DONATIONCAMPAIGNS_SETTINGS',
					// The ext_ prefix makes the entry disappear when the
					// extension is disabled, rather than leaving a dead menu
					// item that errors when clicked.
					'auth'	=> 'ext_uflagmey/donationcampaigns && acl_a_donationcampaigns',
					'cat'	=> array('ACP_DONATIONCAMPAIGNS'),
				),
				'campaigns'	=> array(
					'title'	=> 'ACP_DONATIONCAMPAIGNS_CAMPAIGNS',
					'auth'	=> 'ext_uflagmey/donationcampaigns && acl_a_donationcampaigns',
					'cat'	=> array('ACP_DONATIONCAMPAIGNS'),
				),
				'donations'	=> array(
					'title'	=> 'ACP_DONATIONCAMPAIGNS_DONATIONS',
					'auth'	=> 'ext_uflagmey/donationcampaigns && acl_a_donationcampaigns',
					'cat'	=> array('ACP_DONATIONCAMPAIGNS'),
				),
			),
		);
	}
}
