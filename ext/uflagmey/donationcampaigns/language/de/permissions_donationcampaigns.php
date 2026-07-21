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
	'ACL_CAT_DONATIONCAMPAIGNS'			=> 'Spendenkampagnen',

	'ACL_A_DONATIONCAMPAIGNS'			=> 'Kann Spendenkampagnen global verwalten',

	'ACL_M_DONATIONCAMPAIGNS_MANAGE'	=> 'Kann Spendenkampagnen verwalten (erstellen, bearbeiten, aktivieren/deaktivieren, leere Kampagnen löschen)',

	// Die Beschreibung muss die Datenschutz-Reichweite deutlich machen: Zugriff
	// auf Spendernamen, private Spenderidentitäten und bestätigte Beträge.
	'ACL_M_DONATIONCAMPAIGNS_DONATIONS'	=> 'Kann bestätigte Spenden verwalten (erfassen, bearbeiten, löschen). Gewährt Zugriff auf Spendernamen, private Spenderidentitäten und den bestätigten Betrag jeder Spende.',
));
