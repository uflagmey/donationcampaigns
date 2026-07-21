<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Audit-log messages. Kept in their own file and loaded globally through the
 * core.user_setup listener (lang_set_ext), because the log VIEWER translates an
 * entry by reading $user->lang[$log_operation] — so the key must be present in
 * every context a viewer runs: the ACP admin-log module AND the MCP moderator-
 * log module. Frontend campaign/donation actions log to the moderator log;
 * ACP maintenance logs to the admin log.
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
	'LOG_DONATIONCAMPAIGNS_SETTINGS_UPDATED'	=> '<strong>Donation campaigns settings updated</strong>',

	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED'		=> '<strong>Donation campaign created</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED'		=> '<strong>Donation campaign edited</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ENABLED'	=> '<strong>Donation campaign enabled</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DISABLED'	=> '<strong>Donation campaign disabled</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED'	=> '<strong>Donation campaign deleted</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_TOTAL_RECALCULATED'	=> '<strong>Donation campaign total recalculated</strong><br />&raquo; %s',

	'LOG_DONATIONCAMPAIGNS_DONATION_ADDED'		=> '<strong>Confirmed donation recorded</strong><br />&raquo; %1$s from %2$s',
	'LOG_DONATIONCAMPAIGNS_DONATION_EDITED'		=> '<strong>Confirmed donation edited</strong><br />&raquo; %1$s from %2$s',
	'LOG_DONATIONCAMPAIGNS_DONATION_DELETED'	=> '<strong>Confirmed donation deleted</strong><br />&raquo; %1$s from %2$s',
));
