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
	// Number formatting belongs to the READER's language, not to the
	// currency: a German board showing dollars still writes 1.234,56.
	'DONATIONCAMPAIGNS_DECIMAL_SEPARATOR'	=> ',',
	'DONATIONCAMPAIGNS_THOUSANDS_SEPARATOR'	=> '.',
	// Wird im ACP angezeigt, wenn die Version des Boards zu alt oder zu neu ist.
	'DONATIONCAMPAIGNS_UNSUPPORTED_PHPBB'	=> 'Spendenkampagnen benötigt phpBB %1$s oder neuer. Dieses Board läuft mit %2$s.',
	'DONATIONCAMPAIGNS_TARGET'			=> 'Spendenziel',
	'DONATIONCAMPAIGNS_COLLECTED'		=> 'Gesammelt',
	'DONATIONCAMPAIGNS_TARGET_REACHED'	=> 'Spendenziel erreicht — vielen Dank!',
	'DONATIONCAMPAIGNS_DONORS'			=> 'Spender',
	'DONATIONCAMPAIGNS_ANONYMOUS'		=> 'Anonym',
	'DONATIONCAMPAIGNS_COUNT'			=> array(
		1	=> '%d Spende',
		2	=> '%d Spenden',
	),
	'DONATIONCAMPAIGNS_AND_OTHERS'		=> array(
		1	=> 'und %d weiterer',
		2	=> 'und %d weitere',
	),
	// Benennt den Fortschrittsbalken für Screenreader, die den Balken selbst
	// nicht sehen und sonst nur eine nackte Zahl vorlesen würden.
	'DONATIONCAMPAIGNS_PROGRESS_LABEL'	=> 'Spendenfortschritt',

	// Der Eintrag in den Themen-Werkzeugen. BEWUSST NEUTRAL: Er lautet gleich,
	// egal ob das Thema bereits eine Kampagne hat oder nicht, denn die Seite
	// kann das zum Zeitpunkt des Klicks nicht wissen. Das ACP ermittelt den
	// wirklichen Zustand beim Aufruf. Ein Verb ("Anlegen", "Verwalten") würde
	// veralten, sobald zwischen Aufbau der Seite und Klick eine Kampagne
	// angelegt, gelöscht oder deaktiviert wird. Siehe ADR-014.
	'DONATIONCAMPAIGNS_TOPIC_TOOLS_LINK'	=> 'Spendenkampagne',

	// Validierungsfehler. Die Services werfen Sprachschlüssel statt fertiger
	// Texte, damit der Aufrufer sie in der Sprache des Benutzers ausgibt.
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_EMPTY'		=> 'Bitte gib einen Betrag ein.',
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_INVALID'	=> 'Der Betrag ist ungültig. Gib eine Zahl wie 10,00 ein.',
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE'	=> 'Der Betrag ist zu groß.',

	'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED'		=> 'Bitte gib einen Titel für die Kampagne ein.',
	'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG'		=> 'Der Titel der Kampagne darf höchstens 255 Zeichen lang sein.',
	'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE'		=> 'Das Spendenziel muss größer als null sein.',
	'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED'		=> 'Bitte gib die ID des Themas an, zu dem diese Kampagne gehört.',
	'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND'		=> 'Es gibt kein Thema mit dieser ID.',
	'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN'	=> 'Dieses Thema hat bereits eine Spendenkampagne.',
	'DONATIONCAMPAIGNS_ERROR_URL_INVALID'			=> 'Der Spendenlink muss eine vollständige http://- oder https://-Adresse sein.',
	'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG'			=> 'Der Spendenlink darf höchstens 255 Zeichen lang sein.',
	'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED'	=> 'Gib den Text für die Schaltfläche an, wenn ein Spendenlink gesetzt ist.',
	'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG'	=> 'Der Text der Schaltfläche darf höchstens 100 Zeichen lang sein.',
	// Zwei verschiedene Fehler. Die Spendenverwaltung ohne Kampagne in der
	// Adresse zu öffnen ist ein fehlender ZUSAMMENHANG, keine gelöschte
	// Kampagne; die zweite Meldung sagte Administratoren, ihre Daten seien weg.
	'DONATIONCAMPAIGNS_ERROR_NO_CAMPAIGN_SELECTED'	=> 'Keine Kampagne ausgewählt. Öffne die Spenden über eine Kampagne in der Kampagnenliste.',
	'DONATIONCAMPAIGNS_ERROR_CAMPAIGN_NOT_FOUND'	=> 'Diese Kampagne existiert nicht mehr.',
	'DONATIONCAMPAIGNS_ERROR_DONATION_NOT_FOUND'	=> 'Diese Spende existiert nicht mehr.',
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE'		=> 'Der Spendenbetrag muss größer als null sein.',
	'DONATIONCAMPAIGNS_ERROR_DONOR_NAME_TOO_LONG'	=> 'Der Name des Spenders darf höchstens 255 Zeichen lang sein.',
	'DONATIONCAMPAIGNS_ERROR_TIME_INVALID'			=> 'Das Datum der Spende ist ungültig.',
	'DONATIONCAMPAIGNS_ERROR_TOTAL_OVERFLOW'		=> 'Die Summe der Kampagne würde den größten speicherbaren Betrag überschreiten.',

	'DONATIONCAMPAIGNS_ERROR_CURRENCY_CODE'			=> 'Der Währungscode muss aus genau drei Buchstaben bestehen, zum Beispiel EUR oder USD.',
	'DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL'		=> 'Bitte gib ein Währungssymbol ein.',
	'DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL_LENGTH'=> 'Das Währungssymbol darf höchstens 10 Zeichen lang sein.',
	'DONATIONCAMPAIGNS_ERROR_EXPONENT_RANGE'		=> 'Die Anzahl der Dezimalstellen muss zwischen 0 und 4 liegen.',
	'DONATIONCAMPAIGNS_ERROR_EXPONENT_CONFIRM'		=> 'Für dieses Board sind bereits Beträge erfasst. Bestätige, dass dir klar ist, wie sich eine Änderung der Dezimalstellen auf sie auswirkt.',
	'DONATIONCAMPAIGNS_ERROR_DONOR_LIMIT_RANGE'		=> 'Die Anzahl angezeigter Spender muss zwischen 1 und 500 liegen.',

	// Frontend-Kampagnenverwaltung, erreichbar über die Themenwerkzeuge. Die
	// Formularbeschriftungen selbst werden aus info_acp_donationcampaigns
	// wiederverwendet, das der Controller zusammen mit common lädt; hier stehen
	// nur die für den Frontend-Ablauf spezifischen Texte.
	'DONATIONCAMPAIGNS_MANAGE_CAMPAIGN'				=> 'Spendenkampagne verwalten',
	'DONATIONCAMPAIGNS_MANAGE_EXPLAIN'				=> 'Verwalte die Spendenkampagne für dieses Thema.',
	'DONATIONCAMPAIGNS_NOT_AVAILABLE'				=> 'Die angeforderte Seite ist nicht verfügbar.',
	'DONATIONCAMPAIGNS_NO_CAMPAIGN_YET'				=> 'Für dieses Thema besteht noch keine Spendenkampagne.',
	'DONATIONCAMPAIGNS_RETURN_TO_TOPIC'				=> '%sZurück zum Thema%s',
	// 'BACK' ist kein phpBB-Kernschlüssel; {L_BACK} würde den rohen Schlüssel
	// anzeigen. Dies ist das eigene übersetzte Label der Erweiterung.
	'DONATIONCAMPAIGNS_BACK_TO_TOPIC'				=> 'Zurück zum Thema',
	'DONATIONCAMPAIGNS_CAMPAIGN_SAVED_RETURN'		=> 'Die Kampagne wurde gespeichert.<br /><br />%sZurück zum Thema%s',
	'DONATIONCAMPAIGNS_CAMPAIGN_DELETED_RETURN'		=> 'Die Kampagne wurde gelöscht.<br /><br />%sZurück zum Thema%s',
	'DONATIONCAMPAIGNS_DONATION_SAVED_RETURN'		=> 'Die bestätigte Spende wurde gespeichert und die Kampagnensumme neu berechnet.<br /><br />%sZurück zum Thema%s',
	'DONATIONCAMPAIGNS_DONATION_DELETED_RETURN'		=> 'Die bestätigte Spende wurde gelöscht und die Kampagnensumme neu berechnet.<br /><br />%sZurück zum Thema%s',

	'DONATIONCAMPAIGNS_STATUS'						=> 'Status',
	'DONATIONCAMPAIGNS_EDIT'						=> 'Kampagne bearbeiten',
	'DONATIONCAMPAIGNS_ENABLE'						=> 'Aktivieren',
	'DONATIONCAMPAIGNS_DISABLE'						=> 'Deaktivieren',
	'DONATIONCAMPAIGNS_DELETE'						=> 'Löschen',
	'DONATIONCAMPAIGNS_NO_DONATIONS_YET'			=> 'Es wurden noch keine bestätigten Spenden erfasst.',

	'DONATIONCAMPAIGNS_CONFIRM_DISABLE'				=> 'Soll diese Kampagne wirklich deaktiviert werden? Sie wird im Thema ausgeblendet; alle Spenden bleiben erhalten.',
	'DONATIONCAMPAIGNS_CONFIRM_DELETE_EMPTY'		=> 'Soll diese Kampagne wirklich gelöscht werden? Sie enthält keine Spenden und dies kann nicht rückgängig gemacht werden.',
	'DONATIONCAMPAIGNS_DELETE_NON_EMPTY_REFUSED'	=> 'Diese Kampagne enthält bestätigte Spenden und kann hier nicht gelöscht werden. Deaktiviere sie stattdessen oder bitte einen Administrator, sie zu löschen.',
));
