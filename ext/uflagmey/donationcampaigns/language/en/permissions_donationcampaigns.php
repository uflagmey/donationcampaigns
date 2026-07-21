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
	'ACL_A_DONATIONCAMPAIGNS'	=> 'Can manage donation campaigns',
));
