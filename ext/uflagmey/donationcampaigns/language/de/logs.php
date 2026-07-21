<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Audit-log-Meldungen. In einer eigenen Datei und global über den
 * core.user_setup-Listener (lang_set_ext) geladen, damit der Log-Betrachter die
 * Schlüssel sowohl im ACP-Admin-Log als auch im MCP-Moderator-Log auflösen kann.
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
	'LOG_DONATIONCAMPAIGNS_SETTINGS_UPDATED'	=> '<strong>Einstellungen für Spendenkampagnen geändert</strong>',

	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED'		=> '<strong>Spendenkampagne angelegt</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED'		=> '<strong>Spendenkampagne bearbeitet</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ENABLED'	=> '<strong>Spendenkampagne aktiviert</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DISABLED'	=> '<strong>Spendenkampagne deaktiviert</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED'	=> '<strong>Spendenkampagne gelöscht</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_TOTAL_RECALCULATED'	=> '<strong>Summe einer Spendenkampagne neu berechnet</strong><br />&raquo; %s',

	'LOG_DONATIONCAMPAIGNS_DONATION_ADDED'		=> '<strong>Bestätigte Spende erfasst</strong><br />&raquo; %1$s von %2$s',
	'LOG_DONATIONCAMPAIGNS_DONATION_EDITED'		=> '<strong>Bestätigte Spende bearbeitet</strong><br />&raquo; %1$s von %2$s',
	'LOG_DONATIONCAMPAIGNS_DONATION_DELETED'	=> '<strong>Bestätigte Spende gelöscht</strong><br />&raquo; %1$s von %2$s',
));
