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
	'ACP_DONATIONCAMPAIGNS'				=> 'Donation campaigns',
	'ACP_DONATIONCAMPAIGNS_SETTINGS'	=> 'Settings',
	'ACP_DONATIONCAMPAIGNS_CAMPAIGNS'	=> 'Campaigns',
	'ACP_DONATIONCAMPAIGNS_DONATIONS'	=> 'Donations',

	'DONATIONCAMPAIGNS_SETTINGS_EXPLAIN'	=> 'These settings control how donation amounts are displayed across the board.',

	'DONATIONCAMPAIGNS_SETTINGS_CURRENCY'			=> 'Currency',
	'DONATIONCAMPAIGNS_SETTINGS_CODE'				=> 'Currency code',
	'DONATIONCAMPAIGNS_SETTINGS_CODE_EXPLAIN'		=> 'The three-letter ISO code for the currency, for example EUR, USD or GBP.',
	'DONATIONCAMPAIGNS_SETTINGS_SYMBOL'				=> 'Currency symbol',
	'DONATIONCAMPAIGNS_SETTINGS_SYMBOL_EXPLAIN'		=> 'Shown next to every amount, for example € or $. At most 10 characters.',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT'			=> 'Decimal places',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_EXPLAIN'	=> 'How many digits follow the decimal separator: 2 for most currencies, 0 for yen, 3 for dinar.',

	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_WARNING_TITLE'	=> 'This board already has recorded amounts',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_WARNING'		=> 'Changing these currency settings affects how existing amounts are displayed.',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_CONFIRM'		=> 'Confirm the change',
	'DONATIONCAMPAIGNS_SETTINGS_EXPONENT_CONFIRM_EXPLAIN'=> 'Tick this box to confirm that you understand stored values are not converted, only displayed differently. An amount stored as 1000 is shown as 10.00 with two decimal places, and as 1.000 with three.',

	'DONATIONCAMPAIGNS_SETTINGS_DISPLAY'				=> 'Display',
	'DONATIONCAMPAIGNS_SETTINGS_DONOR_LIMIT'			=> 'Donors listed',
	'DONATIONCAMPAIGNS_SETTINGS_DONOR_LIMIT_EXPLAIN'	=> 'How many donor names the campaign box lists before summarising the rest. Between 1 and 500.',

	// Campaign list
	'DONATIONCAMPAIGNS_LIST_EXPLAIN'		=> 'Every donation campaign on this board. A campaign is attached to one topic and is shown above that topic\'s first post.',
	'DONATIONCAMPAIGNS_LIST_CAMPAIGN'		=> 'Campaign',
	'DONATIONCAMPAIGNS_LIST_TOPIC'			=> 'Topic',
	'DONATIONCAMPAIGNS_LIST_PROGRESS'		=> 'Progress',
	'DONATIONCAMPAIGNS_LIST_DONATIONS'		=> 'Donations',
	'DONATIONCAMPAIGNS_LIST_STATUS'			=> 'Status',
	'DONATIONCAMPAIGNS_LIST_ACTIONS'		=> 'Actions',
	'DONATIONCAMPAIGNS_ENABLED'				=> 'Enabled',
	'DONATIONCAMPAIGNS_DISABLED'			=> 'Disabled',
	'DONATIONCAMPAIGNS_ADD_CAMPAIGN'		=> 'New donation campaign',
	'DONATIONCAMPAIGNS_RECALCULATE'			=> 'Recalculate total',
	// Shown instead of a bare "no items": an empty list is not a fault, and
	// the one thing the administrator needs here is where creation lives.
	'DONATIONCAMPAIGNS_LIST_EMPTY_EXPLAIN'	=> 'Donation campaigns are created from the topic tools menu of the topic they belong to.',

	// Actions
	'DONATIONCAMPAIGNS_CAMPAIGN_DELETED'		=> 'The campaign and its donation records have been deleted.',
	'DONATIONCAMPAIGNS_CONFIRM_DELETE_CAMPAIGN'	=> 'Are you sure you want to delete the campaign &ldquo;%1$s&rdquo;? This permanently destroys %2$d donation record(s) and cannot be undone.',
	'DONATIONCAMPAIGNS_CONFIRM_RECALCULATE'		=> 'Recalculate the collected total for &ldquo;%1$s&rdquo; from its donation records?',
	'DONATIONCAMPAIGNS_RECALCULATED'			=> 'The collected total has been recalculated. Previous value: %1$s. New value: %2$s.',

	// Add / edit form
	'DONATIONCAMPAIGNS_EDIT_CAMPAIGN'			=> 'Edit campaign',
	'DONATIONCAMPAIGNS_FORM_EXPLAIN'			=> 'A campaign is attached to one topic and is shown above that topic\'s first post.',
	'DONATIONCAMPAIGNS_FORM_TITLE'				=> 'Title',
	'DONATIONCAMPAIGNS_FORM_TITLE_EXPLAIN'		=> 'Shown as the heading of the campaign box. At most 255 characters.',
	'DONATIONCAMPAIGNS_FORM_TOPIC'				=> 'Topic',
	// The topic is fixed by the page the form was opened from and is shown as
	// text, not as an input. A campaign cannot be moved to another topic.
	'DONATIONCAMPAIGNS_FORM_TOPIC_EXPLAIN_FIXED'	=> 'This campaign belongs to this topic and cannot be moved to another one.',
	'DONATIONCAMPAIGNS_FORM_DESC'				=> 'Description',
	'DONATIONCAMPAIGNS_FORM_DESC_EXPLAIN'		=> 'Shown beneath the title. BBCode, smilies and links are permitted.',

	'DONATIONCAMPAIGNS_FORM_TARGET'				=> 'Target amount',
	'DONATIONCAMPAIGNS_FORM_TARGET_EXPLAIN'		=> 'The amount being raised, for example 250.00 or 250,00. Must be greater than zero.',
	'DONATIONCAMPAIGNS_FORM_COLLECTED'			=> 'Collected',
	'DONATIONCAMPAIGNS_FORM_URL'				=> 'Donation link',
	'DONATIONCAMPAIGNS_FORM_URL_EXPLAIN'		=> 'Optional. A full http:// or https:// address where people can donate. Leave empty for no link.',
	'DONATIONCAMPAIGNS_FORM_LINK_TEXT'			=> 'Link text',
	'DONATIONCAMPAIGNS_FORM_LINK_TEXT_EXPLAIN'	=> 'Text shown on the public campaign button, for example “How to donate” or “Donate via PayPal”.',
	// Offered for a new campaign. Deliberately not provider-specific.
	'DONATIONCAMPAIGNS_LINK_TEXT_DEFAULT'		=> 'How to donate',

	'DONATIONCAMPAIGNS_SHOW_DONOR_NAMES'		=> 'Show donor names',
	'DONATIONCAMPAIGNS_DONOR_PRIVACY_WARNING'	=> 'Donor names on a publicly readable topic are visible to guests and to search engines. You are responsible for having each donor’s consent before publishing their name. Individual donations can be marked private, and a donation with an empty name is shown as “Anonymous”.',
	'DONATIONCAMPAIGNS_FORM_SHOW_COUNT'			=> 'Show donation count',
	'DONATIONCAMPAIGNS_FORM_SHOW_COUNT_EXPLAIN'	=> 'Shows how many donations have been recorded, without naming anyone.',

	'DONATIONCAMPAIGNS_CAMPAIGN_SAVED'			=> 'The campaign has been saved.',

	// Donations. Every row is a CONFIRMED receipt: money already received.
	'DONATIONCAMPAIGNS_DONATIONS_EXPLAIN'		=> 'Confirmed donations for this campaign. Record a donation only after the money has actually been received — the public total is calculated from these entries alone. Nobody but an administrator can create or confirm a donation here.',
	// The donations mode is a read-only oversight view: recording, editing and
	// deleting a donation happen on the topic, behind the forum-scoped donations
	// permission, so these strings link there rather than offering a form here.
	'DONATIONCAMPAIGNS_DONATIONS_OVERSIGHT_EXPLAIN'	=> 'Confirmed donations for this campaign. The public total is calculated from these entries alone. Recording, editing and deleting a donation happens on the topic — this view is read-only oversight.',
	'DONATIONCAMPAIGNS_MANAGE_ON_TOPIC'			=> 'Manage donations on the topic',
	'DONATIONCAMPAIGNS_EDIT_ON_TOPIC'			=> 'Edit on the topic',
	'DONATIONCAMPAIGNS_ADD_DONATION'			=> 'Add confirmed donation',
	'DONATIONCAMPAIGNS_EDIT_DONATION'			=> 'Edit confirmed donation',
	'DONATIONCAMPAIGNS_RECEIPT'					=> 'Donation',
	'DONATIONCAMPAIGNS_DONOR'					=> 'Donor',
	'DONATIONCAMPAIGNS_DONOR_EXPLAIN'			=> 'The name to show publicly. Leave empty to record the donation as “Anonymous”.',
	'DONATIONCAMPAIGNS_AMOUNT'					=> 'Amount',
	'DONATIONCAMPAIGNS_AMOUNT_EXPLAIN'			=> 'The amount actually received, for example 50.00 or 50,00.',
	'DONATIONCAMPAIGNS_RECEIVED_ON'				=> 'Received on',
	'DONATIONCAMPAIGNS_RECEIVED_ON_EXPLAIN'		=> 'The date the money arrived, not the date you are entering it.',
	'DONATIONCAMPAIGNS_RECORDED_ON'				=> 'Recorded on',
	'DONATIONCAMPAIGNS_VISIBILITY'				=> 'Visibility',
	// The state a row is in, shown in the Visibility column...
	'DONATIONCAMPAIGNS_VISIBILITY_PUBLIC'		=> 'Public',
	// ...and the instruction on the checkbox that sets it.
	'DONATIONCAMPAIGNS_SHOW_DONOR_PUBLICLY'		=> 'Show donor publicly',
	'DONATIONCAMPAIGNS_VISIBILITY_ANONYMOUS'	=> 'Anonymous',
	'DONATIONCAMPAIGNS_PUBLIC_EXPLAIN'			=> 'When off, the donation still counts towards the total but the donor is shown as “Anonymous”. Only publish a name with that donor’s consent.',
	'DONATIONCAMPAIGNS_DONATION_FORM_EXPLAIN'	=> 'Record a payment that has already been received and verified. This extension does not process payments and never contacts a payment provider.',

	'DONATIONCAMPAIGNS_CONFIRM_DELETE_DONATION'	=> 'Are you sure you want to delete the confirmed donation of %1$s from %2$s? The campaign total will be recalculated. This cannot be undone.',

	// LOG_ keys moved to language/en/logs.php so they resolve in the MCP
	// moderator-log viewer as well as the ACP admin-log viewer.
));
