<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
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
	'ACP_DONATIONCAMPAIGNS'				=> 'Spendenkampagnen',
	'ACP_DONATIONCAMPAIGNS_SETTINGS'	=> 'Einstellungen',
	'ACP_DONATIONCAMPAIGNS_CAMPAIGNS'	=> 'Kampagnen',
	'ACP_DONATIONCAMPAIGNS_DONATIONS'	=> 'Spenden',

	'DONATIONCAMPAIGNS_SETTINGS_EXPLAIN'	=> 'Diese Einstellungen bestimmen, wie Spendenbeträge im gesamten Board dargestellt werden.',

	'DONATIONCAMPAIGNS_SETTINGS_CURRENCY'			=> 'Währung',
	'DONATIONCAMPAIGNS_SETTINGS_CODE'				=> 'Währungscode',
	'DONATIONCAMPAIGNS_SETTINGS_CODE_EXPLAIN'		=> 'Der dreibuchstabige ISO-Code der Währung, zum Beispiel EUR, USD oder GBP.',
	'DONATIONCAMPAIGNS_SETTINGS_SYMBOL'				=> 'Währungssymbol',
	'DONATIONCAMPAIGNS_SETTINGS_SYMBOL_EXPLAIN'		=> 'Wird neben jedem Betrag angezeigt, zum Beispiel € oder $. Höchstens 10 Zeichen.',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT'			=> 'Dezimalstellen',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_EXPLAIN'	=> 'Wie viele Stellen hinter dem Dezimaltrennzeichen stehen: 2 für die meisten Währungen, 0 für Yen, 3 für Dinar.',

	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_WARNING_TITLE'	=> 'Für dieses Board sind bereits Beträge erfasst',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_WARNING'		=> 'Eine Änderung dieser Währungseinstellungen wirkt sich darauf aus, wie vorhandene Beträge angezeigt werden.',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_CONFIRM'		=> 'Änderung bestätigen',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_CONFIRM_EXPLAIN'=> 'Setze dieses Häkchen, um zu bestätigen, dass gespeicherte Werte nicht umgerechnet, sondern nur anders angezeigt werden. Ein als 1000 gespeicherter Betrag erscheint mit zwei Dezimalstellen als 10,00 und mit drei als 1,000.',

	'DONATIONCAMPAIGNS_SETTINGS_DISPLAY'				=> 'Anzeige',
	'DONATIONCAMPAIGNS_SETTINGS_DONOR_LIMIT'			=> 'Angezeigte Spender',
	'DONATIONCAMPAIGNS_SETTINGS_DONOR_LIMIT_EXPLAIN'	=> 'Wie viele Spendernamen die Kampagnenbox nennt, bevor sie den Rest zusammenfasst. Zwischen 1 und 500.',

	// Kampagnenliste
	'DONATIONCAMPAIGNS_LIST_EXPLAIN'		=> 'Alle Spendenkampagnen dieses Boards. Eine Kampagne gehört zu genau einem Thema und wird über dessen erstem Beitrag angezeigt.',
	'DONATIONCAMPAIGNS_LIST_CAMPAIGN'		=> 'Kampagne',
	'DONATIONCAMPAIGNS_LIST_TOPIC'			=> 'Thema',
	'DONATIONCAMPAIGNS_LIST_PROGRESS'		=> 'Fortschritt',
	'DONATIONCAMPAIGNS_LIST_DONATIONS'		=> 'Spenden',
	'DONATIONCAMPAIGNS_LIST_STATUS'			=> 'Status',
	'DONATIONCAMPAIGNS_LIST_ACTIONS'		=> 'Aktionen',
	'DONATIONCAMPAIGNS_ENABLED'				=> 'Aktiv',
	'DONATIONCAMPAIGNS_DISABLED'			=> 'Deaktiviert',
	'DONATIONCAMPAIGNS_ADD_CAMPAIGN'		=> 'Neue Spendenkampagne',
	// Statt eines nackten „keine Einträge“: Eine leere Liste ist kein Fehler,
	// und das Einzige, was hier fehlt, ist der Hinweis, wo angelegt wird.
	'DONATIONCAMPAIGNS_LIST_EMPTY_EXPLAIN'	=> 'Spendenkampagnen werden über das Themenwerkzeug des zugehörigen Themas angelegt.',
	'DONATIONCAMPAIGNS_RECALCULATE'			=> 'Summe neu berechnen',

	// Aktionen
	'DONATIONCAMPAIGNS_CAMPAIGN_DELETED'		=> 'Die Kampagne und ihre Spendeneinträge wurden gelöscht.',
	'DONATIONCAMPAIGNS_CONFIRM_DELETE_CAMPAIGN'	=> 'Soll die Kampagne &bdquo;%1$s&ldquo; wirklich gelöscht werden? Dabei werden %2$d Spendeneinträge unwiderruflich vernichtet.',
	'DONATIONCAMPAIGNS_CONFIRM_RECALCULATE'		=> 'Die gesammelte Summe für &bdquo;%1$s&ldquo; aus den Spendeneinträgen neu berechnen?',
	'DONATIONCAMPAIGNS_RECALCULATED'			=> 'Die gesammelte Summe wurde neu berechnet. Vorheriger Wert: %1$s. Neuer Wert: %2$s.',

	// Formular zum Anlegen und Bearbeiten
	'DONATIONCAMPAIGNS_EDIT_CAMPAIGN'			=> 'Kampagne bearbeiten',
	'DONATIONCAMPAIGNS_FORM_EXPLAIN'			=> 'Eine Kampagne gehört zu genau einem Thema und wird über dessen erstem Beitrag angezeigt.',
	'DONATIONCAMPAIGNS_FORM_CAMPAIGN'			=> 'Kampagne',
	'DONATIONCAMPAIGNS_FORM_TITLE'				=> 'Titel',
	'DONATIONCAMPAIGNS_FORM_TITLE_EXPLAIN'		=> 'Wird als Überschrift der Kampagnenbox angezeigt. Höchstens 255 Zeichen.',
	'DONATIONCAMPAIGNS_FORM_TOPIC'				=> 'Thema',
	// Das Thema steht durch die Seite fest, aus der das Formular geöffnet
	// wurde, und wird als Text angezeigt, nicht als Eingabefeld. Eine Kampagne
	// kann nicht auf ein anderes Thema verschoben werden.
	'DONATIONCAMPAIGNS_FORM_TOPIC_EXPLAIN_FIXED'	=> 'Diese Kampagne gehört zu diesem Thema und kann nicht auf ein anderes verschoben werden.',
	'DONATIONCAMPAIGNS_BACK_TO_TOPIC'				=> 'Zurück zum Thema',
	// Beide Hinweise melden, dass sich zwischen Öffnen und Absenden des
	// Formulars etwas geändert hat. In beiden Fällen wurde nichts geschrieben.
	'DONATIONCAMPAIGNS_NOTICE_CAMPAIGN_EXISTS_NOW'	=> 'Für dieses Thema existiert bereits eine Spendenkampagne. Du bearbeitest jetzt die vorhandene Kampagne; deine Eingaben wurden nicht gespeichert.',
	'DONATIONCAMPAIGNS_NOTICE_CAMPAIGN_GONE'		=> 'Die Kampagne für dieses Thema existiert nicht mehr. Du kannst unten eine neue erstellen.',
	'DONATIONCAMPAIGNS_FORM_DESC'				=> 'Beschreibung',
	'DONATIONCAMPAIGNS_FORM_DESC_EXPLAIN'		=> 'Wird unter dem Titel angezeigt. BBCode, Smilies und Links sind erlaubt.',

	'DONATIONCAMPAIGNS_FORM_MONEY'				=> 'Beträge',
	'DONATIONCAMPAIGNS_FORM_TARGET'				=> 'Spendenziel',
	'DONATIONCAMPAIGNS_FORM_TARGET_EXPLAIN'		=> 'Der angestrebte Betrag, zum Beispiel 250,00 oder 250.00. Muss größer als null sein.',
	'DONATIONCAMPAIGNS_FORM_COLLECTED'			=> 'Gesammelt',
	'DONATIONCAMPAIGNS_FORM_COLLECTED_EXPLAIN'	=> 'Wird aus den erfassten Spenden berechnet. Der Wert lässt sich hier nicht bearbeiten; nutze &bdquo;Summe neu berechnen&ldquo; in der Kampagnenliste, falls er nicht stimmt.',
	'DONATIONCAMPAIGNS_FORM_URL'				=> 'Spendenlink',
	'DONATIONCAMPAIGNS_FORM_URL_EXPLAIN'		=> 'Optional. Eine vollständige http://- oder https://-Adresse, unter der gespendet werden kann. Leer lassen, wenn keine Schaltfläche erscheinen soll.',
	'DONATIONCAMPAIGNS_FORM_LINK_TEXT'			=> 'Linktext',
	'DONATIONCAMPAIGNS_FORM_LINK_TEXT_EXPLAIN'	=> 'Text auf der öffentlichen Schaltfläche der Kampagne, zum Beispiel &bdquo;So kannst du spenden&ldquo; oder &bdquo;Über PayPal spenden&ldquo;.',
	// Vorschlag für eine neue Kampagne. Bewusst ohne Anbieterbezug.
	'DONATIONCAMPAIGNS_LINK_TEXT_DEFAULT'		=> 'So kannst du spenden',

	'DONATIONCAMPAIGNS_FORM_DISPLAY'			=> 'Anzeige',
	'DONATIONCAMPAIGNS_FORM_ENABLED'			=> 'Aktiv',
	'DONATIONCAMPAIGNS_FORM_ENABLED_EXPLAIN'	=> 'Eine deaktivierte Kampagne wird im Thema nicht angezeigt, behält aber alle ihre Spendeneinträge.',
	'DONATIONCAMPAIGNS_SHOW_DONOR_NAMES'		=> 'Spendernamen anzeigen',
	'DONATIONCAMPAIGNS_DONOR_PRIVACY_WARNING'	=> 'Spendernamen in einem öffentlich lesbaren Thema sind für Gäste und für Suchmaschinen sichtbar. Du bist dafür verantwortlich, dass jeder Spender der Veröffentlichung seines Namens zugestimmt hat. Einzelne Spenden lassen sich als nicht öffentlich kennzeichnen, und eine Spende ohne Namen wird als &bdquo;Anonym&ldquo; angezeigt.',
	'DONATIONCAMPAIGNS_FORM_SHOW_COUNT'			=> 'Anzahl der Spenden anzeigen',
	'DONATIONCAMPAIGNS_FORM_SHOW_COUNT_EXPLAIN'	=> 'Zeigt, wie viele Spenden erfasst wurden, ohne jemanden namentlich zu nennen.',

	'DONATIONCAMPAIGNS_CAMPAIGN_SAVED'			=> 'Die Kampagne wurde gespeichert.',

	// Spenden. Jede Zeile ist ein BESTÄTIGTER Eingang: bereits erhaltenes Geld.
	'DONATIONCAMPAIGNS_DONATIONS_EXPLAIN'		=> 'Bestätigte Spenden für diese Kampagne. Erfasse eine Spende erst, wenn das Geld tatsächlich eingegangen ist — die öffentliche Summe wird ausschließlich aus diesen Einträgen berechnet. Nur Administratoren können hier eine Spende anlegen oder bestätigen.',
	'DONATIONCAMPAIGNS_ADD_DONATION'			=> 'Bestätigte Spende erfassen',
	'DONATIONCAMPAIGNS_EDIT_DONATION'			=> 'Bestätigte Spende bearbeiten',
	'DONATIONCAMPAIGNS_RECEIPT'					=> 'Spende',
	'DONATIONCAMPAIGNS_DONOR'					=> 'Spender',
	'DONATIONCAMPAIGNS_DONOR_EXPLAIN'			=> 'Der Name, der öffentlich angezeigt wird. Leer lassen, um die Spende als &bdquo;Anonym&ldquo; zu erfassen.',
	'DONATIONCAMPAIGNS_AMOUNT'					=> 'Betrag',
	'DONATIONCAMPAIGNS_AMOUNT_EXPLAIN'			=> 'Der tatsächlich eingegangene Betrag, zum Beispiel 50,00 oder 50.00.',
	'DONATIONCAMPAIGNS_RECEIVED_ON'				=> 'Eingegangen am',
	'DONATIONCAMPAIGNS_RECEIVED_ON_EXPLAIN'		=> 'Das Datum des Zahlungseingangs, nicht das Datum der Erfassung.',
	'DONATIONCAMPAIGNS_RECORDED_ON'				=> 'Erfasst am',
	'DONATIONCAMPAIGNS_VISIBILITY'				=> 'Sichtbarkeit',
	// Der Zustand einer Zeile, angezeigt in der Spalte &bdquo;Sichtbarkeit&ldquo;...
	'DONATIONCAMPAIGNS_VISIBILITY_PUBLIC'		=> 'Öffentlich',
	// ...und die Anweisung auf dem Häkchen, das ihn setzt.
	'DONATIONCAMPAIGNS_SHOW_DONOR_PUBLICLY'		=> 'Spender öffentlich anzeigen',
	'DONATIONCAMPAIGNS_VISIBILITY_ANONYMOUS'	=> 'Anonym',
	'DONATIONCAMPAIGNS_PUBLIC_EXPLAIN'			=> 'Ohne Häkchen zählt die Spende weiterhin zur Summe, der Spender wird aber als &bdquo;Anonym&ldquo; angezeigt. Veröffentliche einen Namen nur mit Zustimmung des Spenders.',
	'DONATIONCAMPAIGNS_DONATION_FORM_EXPLAIN'	=> 'Erfasse eine Zahlung, die bereits eingegangen und geprüft ist. Diese Erweiterung wickelt keine Zahlungen ab und nimmt nie Kontakt zu einem Zahlungsanbieter auf.',

	'DONATIONCAMPAIGNS_DONATION_SAVED'			=> 'Die bestätigte Spende wurde gespeichert und die Summe der Kampagne neu berechnet.',
	'DONATIONCAMPAIGNS_DONATION_DELETED'		=> 'Die Spende wurde gelöscht und die Summe der Kampagne neu berechnet.',
	'DONATIONCAMPAIGNS_CONFIRM_DELETE_DONATION'	=> 'Soll die bestätigte Spende über %1$s von %2$s wirklich gelöscht werden? Die Summe der Kampagne wird neu berechnet. Das lässt sich nicht rückgängig machen.',

	'LOG_DONATIONCAMPAIGNS_SETTINGS_UPDATED'	=> '<strong>Einstellungen für Spendenkampagnen geändert</strong>',
	'LOG_DONATIONCAMPAIGNS_DONATION_ADDED'		=> '<strong>Bestätigte Spende erfasst</strong><br />&raquo; %1$s von %2$s',
	'LOG_DONATIONCAMPAIGNS_DONATION_EDITED'		=> '<strong>Bestätigte Spende bearbeitet</strong><br />&raquo; %1$s von %2$s',
	'LOG_DONATIONCAMPAIGNS_DONATION_DELETED'	=> '<strong>Bestätigte Spende gelöscht</strong><br />&raquo; %1$s von %2$s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED'		=> '<strong>Spendenkampagne angelegt</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED'		=> '<strong>Spendenkampagne bearbeitet</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED'	=> '<strong>Spendenkampagne gelöscht</strong><br />&raquo; %s',
	'LOG_DONATIONCAMPAIGNS_TOTAL_RECALCULATED'	=> '<strong>Summe einer Spendenkampagne neu berechnet</strong><br />&raquo; %s',
));
