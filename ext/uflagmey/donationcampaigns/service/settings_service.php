<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * The board's currency and display settings.
 *
 * Validation lives here rather than in the ACP module so that it can be tested
 * without a request, a session or a template, and so the rules exist in one
 * place. The module reads a form and renders a page; it decides nothing.
 *
 * STORAGE CONTRACT. Values are stored EXACTLY as entered — never pre-escaped.
 * Escaping belongs where the value is rendered, because only there is the
 * output context known. Storing escaped text forces every consumer to guess
 * whether it holds markup or characters, and guarantees that one of them
 * eventually guesses wrong.
 *
 * THE EXPONENT IS THE DANGEROUS FIELD. It does not convert anything: it
 * changes how every stored integer is READ. 1000 minor units is 10.00 at
 * exponent 2 and 1.000 at exponent 3 — the same integer, a different sum of
 * money. Stored rows are never rewritten, because silently multiplying an
 * administrator's recorded donations would be worse than refusing. Instead a
 * change is refused unless explicitly confirmed, and only when there is data
 * whose meaning would shift.
 */
class settings_service
{
	/** Minor-unit digits: 0 for JPY, 2 for EUR and USD, 3 for KWD. */
	const MIN_EXPONENT = 0;
	const MAX_EXPONENT = 4;

	/** How many donors the public box may list. */
	const MIN_DONOR_LIMIT = 1;
	const MAX_DONOR_LIMIT = 500;

	/**
	 * Symbol length, in CHARACTERS. Long enough for every real currency
	 * symbol and short abbreviation ('€', 'CHF', 'kr.', 'zł'), short enough
	 * that it cannot become a sentence in the campaign box.
	 */
	const MAX_SYMBOL_LENGTH = 10;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	public function __construct(
		\phpbb\config\config $config,
		campaign_repository $campaigns,
		donation_repository $donations
	)
	{
		$this->config = $config;
		$this->campaigns = $campaigns;
		$this->donations = $donations;
	}

	/**
	 * The stored settings, typed.
	 *
	 * @return array
	 */
	public function current()
	{
		return array(
			'donationcampaigns_currency_code'		=> (string) $this->config['donationcampaigns_currency_code'],
			'donationcampaigns_currency_symbol'		=> (string) $this->config['donationcampaigns_currency_symbol'],
			'donationcampaigns_currency_exponent'	=> (int) $this->config['donationcampaigns_currency_exponent'],
			'donationcampaigns_donor_list_limit'	=> (int) $this->config['donationcampaigns_donor_list_limit'],
		);
	}

	/**
	 * Check a submitted settings array against every rule.
	 *
	 * Returns ALL failures rather than stopping at the first, so an
	 * administrator sees everything wrong with the form in one pass.
	 *
	 * @param array $input
	 * @return string[] Language keys; empty array when the input is valid
	 */
	public function validate(array $input)
	{
		$errors = array();

		$code = $this->text($input, 'donationcampaigns_currency_code');

		// Exactly three ASCII letters, as ISO 4217 defines them.
		if (!preg_match('/^[A-Za-z]{3}$/', $code))
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_CURRENCY_CODE';
		}

		$symbol = $this->text($input, 'donationcampaigns_currency_symbol');

		if ($symbol === '')
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL';
		}
		else if (utf8_strlen($symbol) > self::MAX_SYMBOL_LENGTH)
		{
			// Characters, not bytes: '€' is three bytes and one symbol.
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL_LENGTH';
		}

		$exponent = $this->number($input, 'donationcampaigns_currency_exponent');

		if ($exponent < self::MIN_EXPONENT || $exponent > self::MAX_EXPONENT)
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_EXPONENT_RANGE';
		}
		else if ($this->exponent_change_needs_confirmation($exponent) && empty($input['donationcampaigns_confirm_exponent']))
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_EXPONENT_CONFIRM';
		}

		$limit = $this->number($input, 'donationcampaigns_donor_list_limit');

		if ($limit < self::MIN_DONOR_LIMIT || $limit > self::MAX_DONOR_LIMIT)
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_DONOR_LIMIT_RANGE';
		}

		return $errors;
	}

	/**
	 * Validate and store.
	 *
	 * All four values are validated BEFORE any is written, so an invalid form
	 * leaves the board exactly as it was. A partial write would pair a new
	 * currency code with the old exponent, which is a wrong amount on every
	 * page rather than an obvious error.
	 *
	 * @param array $input
	 * @return void
	 * @throws donationcampaigns_exception Carrying the first failure as its
	 *         language key, and every failure in its parameters
	 */
	public function save(array $input)
	{
		$errors = $this->validate($input);

		if (!empty($errors))
		{
			throw new donationcampaigns_exception($errors[0], $errors);
		}

		$values = $this->normalise($input);

		foreach ($values as $key => $value)
		{
			$this->config->set($key, $value);
		}
	}

	/**
	 * Reduce a submitted array to the four stored values, normalised.
	 *
	 * @param array $input
	 * @return array
	 */
	public function normalise(array $input)
	{
		return array(
			// Uppercased so 'eur' and 'EUR' are one setting, not two.
			'donationcampaigns_currency_code'		=> utf8_strtoupper($this->text($input, 'donationcampaigns_currency_code')),
			'donationcampaigns_currency_symbol'		=> $this->text($input, 'donationcampaigns_currency_symbol'),
			'donationcampaigns_currency_exponent'	=> $this->number($input, 'donationcampaigns_currency_exponent'),
			'donationcampaigns_donor_list_limit'	=> $this->number($input, 'donationcampaigns_donor_list_limit'),
		);
	}

	/**
	 * Whether the board holds any amount whose meaning an exponent change
	 * would alter.
	 *
	 * A campaign counts even with no donations: it carries a target amount,
	 * which is money and would be reinterpreted just the same.
	 *
	 * @return bool
	 */
	public function has_stored_amounts()
	{
		return $this->campaigns->count_all() > 0 || $this->donations->count_all() > 0;
	}

	/**
	 * @param int $exponent The submitted exponent
	 * @return bool
	 */
	public function exponent_change_needs_confirmation($exponent)
	{
		if ((int) $exponent === (int) $this->config['donationcampaigns_currency_exponent'])
		{
			return false;
		}

		return $this->has_stored_amounts();
	}

	/**
	 * A trimmed string from the input, without assuming the key exists or
	 * that its value is a scalar.
	 *
	 * @param array $input
	 * @param string $key
	 * @return string
	 */
	protected function text(array $input, $key)
	{
		if (!isset($input[$key]) || is_array($input[$key]) || is_object($input[$key]))
		{
			return '';
		}

		return trim((string) $input[$key]);
	}

	/**
	 * An integer from the input. A non-numeric value becomes a value outside
	 * every valid range rather than a silent zero, so "abc" is reported as an
	 * error instead of being accepted as 0.
	 *
	 * @param array $input
	 * @param string $key
	 * @return int
	 */
	protected function number(array $input, $key)
	{
		$value = isset($input[$key]) ? $input[$key] : null;

		if (is_bool($value) || is_array($value) || is_object($value) || !is_numeric($value))
		{
			return PHP_INT_MIN;
		}

		return (int) $value;
	}
}
