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
	'DONATIONCAMPAIGNS_DECIMAL_SEPARATOR'	=> '.',
	'DONATIONCAMPAIGNS_THOUSANDS_SEPARATOR'	=> ',',
	// Shown by the ACP when the board's own version is too old or too new.
	'DONATIONCAMPAIGNS_UNSUPPORTED_PHPBB'	=> 'Donation Campaigns requires phpBB %1$s or later. This board runs %2$s.',
	'DONATIONCAMPAIGNS_TARGET'			=> 'Target',
	'DONATIONCAMPAIGNS_COLLECTED'		=> 'Collected',
	'DONATIONCAMPAIGNS_TARGET_REACHED'	=> 'Target reached — thank you!',
	'DONATIONCAMPAIGNS_DONORS'			=> 'Donors',
	'DONATIONCAMPAIGNS_ANONYMOUS'		=> 'Anonymous',
	'DONATIONCAMPAIGNS_COUNT'			=> array(
		1	=> '%d donation',
		2	=> '%d donations',
	),
	'DONATIONCAMPAIGNS_AND_OTHERS'		=> array(
		1	=> 'and %d other',
		2	=> 'and %d others',
	),
	// Names the progress bar for a screen reader, which cannot see the bar
	// itself and would otherwise hear a bare number.
	'DONATIONCAMPAIGNS_PROGRESS_LABEL'	=> 'Donation progress',

	// The topic tools entry. DELIBERATELY NEUTRAL: it reads the same whether
	// the topic already has a campaign or not, because the page cannot know
	// which is true by the time the administrator clicks. The ACP resolves the
	// real state at request time. A verb here ("Add", "Manage") would go stale
	// whenever a campaign is created, deleted or disabled between render and
	// click. See ADR-014.
	'DONATIONCAMPAIGNS_TOPIC_TOOLS_LINK'	=> 'Donation campaign',

	// Validation errors. Services throw language keys rather than display
	// strings so that callers render them in the user's own language.
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_EMPTY'		=> 'Please enter an amount.',
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_INVALID'	=> 'The amount is not valid. Enter a number such as 10.00.',
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE'	=> 'The amount is too large.',

	'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED'		=> 'Please enter a campaign title.',
	'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG'		=> 'The campaign title may be at most 255 characters long.',
	'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE'		=> 'The target amount must be greater than zero.',
	'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED'		=> 'Please enter the ID of the topic this campaign belongs to.',
	'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND'		=> 'No topic with that ID exists.',
	'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN'	=> 'That topic already has a donation campaign.',
	'DONATIONCAMPAIGNS_ERROR_URL_INVALID'			=> 'The donation link must be a full http:// or https:// address.',
	'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG'			=> 'The donation link may be at most 255 characters long.',
	'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED'	=> 'Enter the text for the button when a donation link is set.',
	'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG'	=> 'The button text may be at most 100 characters long.',
	// The campaign form has no context-free mode: a campaign belongs to the
	// topic it was opened from, and there is no field in which to name one.
	'DONATIONCAMPAIGNS_ERROR_NO_TOPIC_CONTEXT'		=> 'This form must be opened from the topic the campaign belongs to.',

	// Two different failures. Reaching donation management without a
	// campaign in the URL is missing CONTEXT, not a deleted campaign;
	// reporting the latter told administrators their records were gone.
	'DONATIONCAMPAIGNS_ERROR_NO_CAMPAIGN_SELECTED'	=> 'No campaign selected. Open donations from a campaign in the campaign list.',
	'DONATIONCAMPAIGNS_ERROR_CAMPAIGN_NOT_FOUND'	=> 'That campaign no longer exists.',
	'DONATIONCAMPAIGNS_ERROR_DONATION_NOT_FOUND'	=> 'That donation no longer exists.',
	'DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE'		=> 'The donation amount must be greater than zero.',
	'DONATIONCAMPAIGNS_ERROR_DONOR_NAME_TOO_LONG'	=> 'The donor name may be at most 255 characters long.',
	'DONATIONCAMPAIGNS_ERROR_TIME_INVALID'			=> 'The donation date is not valid.',
	'DONATIONCAMPAIGNS_ERROR_TOTAL_OVERFLOW'		=> 'The campaign total would exceed the maximum storable amount.',

	'DONATIONCAMPAIGNS_ERROR_CURRENCY_CODE'			=> 'The currency code must be exactly three letters, such as EUR or USD.',
	'DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL'		=> 'Please enter a currency symbol.',
	'DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL_LENGTH'=> 'The currency symbol may be at most 10 characters long.',
	'DONATIONCAMPAIGNS_ERROR_EXPONENT_RANGE'		=> 'The number of decimal places must be between 0 and 4.',
	'DONATIONCAMPAIGNS_ERROR_EXPONENT_CONFIRM'		=> 'This board already has recorded amounts. Confirm that you understand how changing the number of decimal places affects them.',
	'DONATIONCAMPAIGNS_ERROR_DONOR_LIMIT_RANGE'		=> 'The donor list limit must be between 1 and 500.',

	// Frontend campaign management, reached from the topic tools. The campaign
	// form labels themselves are reused from info_acp_donationcampaigns, which
	// the controller loads alongside common; only the strings unique to the
	// frontend workflow live here.
	'DONATIONCAMPAIGNS_MANAGE_CAMPAIGN'				=> 'Manage donation campaign',
	'DONATIONCAMPAIGNS_MANAGE_EXPLAIN'				=> 'Manage the donation campaign for this topic.',
	'DONATIONCAMPAIGNS_NOT_AVAILABLE'				=> 'The requested page is not available.',
	'DONATIONCAMPAIGNS_NO_CAMPAIGN_YET'				=> 'This topic has no donation campaign yet.',
	'DONATIONCAMPAIGNS_RETURN_TO_TOPIC'				=> '%sReturn to the topic%s',
	'DONATIONCAMPAIGNS_CAMPAIGN_SAVED_RETURN'		=> 'The campaign has been saved.<br /><br />%sReturn to the topic%s',
	'DONATIONCAMPAIGNS_CAMPAIGN_DELETED_RETURN'		=> 'The campaign has been deleted.<br /><br />%sReturn to the topic%s',
	'DONATIONCAMPAIGNS_DONATION_SAVED_RETURN'		=> 'The confirmed donation has been saved and the campaign total recalculated.<br /><br />%sReturn to the topic%s',
	'DONATIONCAMPAIGNS_DONATION_DELETED_RETURN'		=> 'The confirmed donation has been deleted and the campaign total recalculated.<br /><br />%sReturn to the topic%s',

	'DONATIONCAMPAIGNS_STATUS'						=> 'Status',
	'DONATIONCAMPAIGNS_EDIT'						=> 'Edit campaign',
	'DONATIONCAMPAIGNS_ENABLE'						=> 'Enable',
	'DONATIONCAMPAIGNS_DISABLE'						=> 'Disable',
	'DONATIONCAMPAIGNS_DELETE'						=> 'Delete',
	'DONATIONCAMPAIGNS_MANAGE_DONATIONS'			=> 'Manage donations',
	'DONATIONCAMPAIGNS_NO_DONATIONS_YET'			=> 'No confirmed donations have been recorded yet.',

	'DONATIONCAMPAIGNS_CONFIRM_DISABLE'				=> 'Are you sure you want to disable this campaign? It will be hidden from the topic; all donation records are kept.',
	'DONATIONCAMPAIGNS_CONFIRM_DELETE_EMPTY'		=> 'Are you sure you want to delete this campaign? It has no donations and this cannot be undone.',
	'DONATIONCAMPAIGNS_DELETE_NON_EMPTY_REFUSED'	=> 'This campaign has confirmed donations and cannot be deleted here. Disable it instead, or ask an administrator to delete it.',
));
