<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * The permissions_ filename prefix is required: core's
 * add_permission_language() discovers files matching it automatically. This
 * file must never be loaded manually.
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACL_CAT_DONATIONCAMPAIGNS'			=> 'Donation Campaigns',

	'ACL_A_DONATIONCAMPAIGNS'			=> 'Can globally administer donation campaigns',

	'ACL_M_DONATIONCAMPAIGNS_MANAGE'	=> 'Can manage donation campaigns (create, edit, enable/disable, delete empty campaigns)',

	// The description must make the privacy reach explicit: granting this lets
	// the holder see donor names, private donor identities and confirmed amounts.
	'ACL_M_DONATIONCAMPAIGNS_DONATIONS'	=> 'Can manage confirmed donations (add, edit and delete receipts). Grants access to donor names, private donor identities and the confirmed amount of each donation.',
));
