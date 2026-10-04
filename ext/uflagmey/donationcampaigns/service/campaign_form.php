<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * Reads the campaign fields from a request — for both places a campaign can
 * be created: the frontend form (campaign_controller) and the posting form
 * (posting_listener, ADR-019).
 *
 * NO RULES, NO SQL. This class turns the posted fields into the raw values and
 * the integer target that campaign_service validates; what is allowed is
 * still decided there. It exists so the two entry points cannot read the
 * same form in two slightly different ways.
 *
 * Plain text is read with raw_variable() and escaped at output — variable()
 * would store "&amp;" for someone who typed "&". The description is the one
 * exception: it is read through variable() because phpBB's BBCode storage
 * encoder follows (see campaign_service::encode_description()).
 *
 * THE PREFIX. The frontend form uses the bare names (campaign_title, …). The
 * posting form is the busiest shared namespace among extensions, so there
 * every field carries "donationcampaigns_" (WD4); the same reader serves both.
 */
class campaign_form
{
	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var currency_formatter */
	protected $formatter;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;

	public function __construct(
		\phpbb\request\request_interface $request,
		currency_formatter $formatter,
		\phpbb\config\config $config,
		\phpbb\language\language $language
	)
	{
		$this->request = $request;
		$this->formatter = $formatter;
		$this->config = $config;
		$this->language = $language;
	}

	/**
	 * The submitted fields, as typed.
	 *
	 * @param string $prefix '' for the frontend form, 'donationcampaigns_' for the posting form
	 * @return array{campaign_title:string, campaign_desc:string, target_amount:string, external_url:string, external_link_text:string, show_donor_names:bool, show_donation_count:bool, show_donation_date:bool}
	 */
	public function submitted_values($prefix = '')
	{
		return array(
			'campaign_title'		=> $this->raw_text($prefix . 'campaign_title'),
			'campaign_desc'			=> $this->request->variable($prefix . 'campaign_desc', '', true),
			'target_amount'			=> $this->raw_text($prefix . 'target_amount'),
			'external_url'			=> $this->raw_text($prefix . 'external_url'),
			'external_link_text'	=> $this->raw_text($prefix . 'external_link_text'),
			'show_donor_names'		=> (bool) $this->request->variable($prefix . 'show_donor_names', 0),
			'show_donation_count'	=> (bool) $this->request->variable($prefix . 'show_donation_count', 0),
			'show_donation_date'	=> (bool) $this->request->variable($prefix . 'show_donation_date', 0),
		);
	}

	/**
	 * The target in minor units, or the formatter's reason it is not one.
	 *
	 * @param string $raw As typed, localized decimal separator, no grouping
	 * @return array{0:int, 1:string} Minor units (0 on error) and a language key ('' when valid)
	 */
	public function parse_target($raw)
	{
		try
		{
			return array($this->formatter->parse($raw, (int) $this->config['donationcampaigns_currency_exponent']), '');
		}
		catch (donationcampaigns_exception $e)
		{
			return array(0, $e->get_language_key());
		}
	}

	/**
	 * Put the formatter's specific amount error in place of the generic one.
	 *
	 * A target that could not be parsed reaches validation as 0 and earns
	 * TARGET_POSITIVE, which would mislead someone who typed "1,000.00". The
	 * formatter's own key replaces it and comes first.
	 *
	 * @param string[] $errors From campaign_service
	 * @param string $amount_error From parse_target(); '' when the target parsed
	 * @return string[]
	 */
	public function merge_amount_error(array $errors, $amount_error)
	{
		if ($amount_error === '')
		{
			return $errors;
		}

		$errors = array_values(array_diff($errors, array('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE')));
		array_unshift($errors, $amount_error);

		return $errors;
	}

	/**
	 * What a NEW campaign's form starts with (D4: the same in both forms).
	 *
	 * The donation date is proposed for new campaigns only; existing ones keep
	 * the schema default until someone ticks it (ADR-017).
	 *
	 * @return array
	 */
	public function defaults()
	{
		return array(
			'campaign_title'		=> '',
			'campaign_desc'			=> '',
			'target_amount'			=> '',
			'external_url'			=> '',
			'external_link_text'	=> $this->language->lang('DONATIONCAMPAIGNS_LINK_TEXT_DEFAULT'),
			'show_donor_names'		=> true,
			'show_donation_count'	=> true,
			'show_donation_date'	=> true,
		);
	}

	/**
	 * raw_variable() returns whatever was posted, which may be an array.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function raw_text($key)
	{
		$value = $this->request->raw_variable($key, '');

		return is_scalar($value) ? (string) $value : '';
	}
}
