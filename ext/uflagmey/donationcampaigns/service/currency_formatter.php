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
 * Converts between human-entered amount strings and integer minor units.
 *
 * All conversion is performed with string operations. No floating-point value
 * is ever constructed, because the obvious implementation loses money on
 * entirely ordinary input:
 *
 *     (int) ('8.70' * 100) === 869
 *     (int) ('0.29' * 100) === 28
 *
 * One cent short, silently, on values an administrator would type every day.
 */
class currency_formatter
{
	/**
	 * Ceiling imposed by the UINT storage column.
	 */
	const MAX_MINOR_UNITS = 4294967295;

	/**
	 * Digits per group, either side of the thousands separator.
	 */
	const GROUP_SIZE = 3;

	/** @var \phpbb\language\language */
	protected $language;

	/**
	 * Separators are LANGUAGE keys, not currency properties.
	 *
	 * A German board showing US dollars still writes 1.234,56, because the
	 * person reading it is German. Deriving the separators from the currency
	 * code would get that backwards, and deriving them from PHP's locale or
	 * intl would add a dependency this extension deliberately does not carry.
	 *
	 * @param \phpbb\language\language $language
	 */
	public function __construct(\phpbb\language\language $language)
	{
		$this->language = $language;
	}

	/**
	 * Parse a human-entered amount into integer minor units.
	 *
	 * @param string $input    Amount as typed, e.g. '10.00' or '10,50'
	 * @param int    $exponent Minor-unit digits (2 for EUR, 0 for JPY, 3 for KWD)
	 * @return int Minor units
	 * @throws donationcampaigns_exception When the input is not a valid amount
	 */
	public function parse($input, $exponent)
	{
		$exponent = (int) $exponent;
		$value = trim((string) $input);

		if ($value === '')
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_AMOUNT_EMPTY');
		}

		// Accept the comma as a decimal separator for European input.
		$value = str_replace(',', '.', $value);

		$pattern = ($exponent > 0)
			? '/^([0-9]{1,10})(?:\.([0-9]{1,' . $exponent . '}))?$/'
			: '/^([0-9]{1,10})$/';

		if (!preg_match($pattern, $value, $matches))
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_AMOUNT_INVALID');
		}

		$major = $matches[1];
		$minor = isset($matches[2]) ? $matches[2] : '';

		// Right-pad, so '10.5' at exponent 2 becomes '10' . '50', not '10' . '5'.
		$minor = str_pad($minor, $exponent, '0', STR_PAD_RIGHT);

		$combined = $major . $minor;

		// Compare digit counts as strings before casting: a value wider than
		// PHP_INT_MAX would otherwise wrap silently.
		if (strlen(ltrim($combined, '0')) > strlen((string) self::MAX_MINOR_UNITS))
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE');
		}

		$result = (int) $combined;

		if ($result > self::MAX_MINOR_UNITS)
		{
			throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE');
		}

		return $result;
	}

	/**
	 * Render integer minor units for DISPLAY, in the reader's language.
	 *
	 * Grouped, with the language's decimal separator: 1,234.56 in English and
	 * 1.234,56 in German. Use format_for_input() for anything that goes back
	 * into a form field.
	 *
	 * @param int $minor_units
	 * @param int $exponent
	 * @return string
	 */
	public function format($minor_units, $exponent)
	{
		return $this->render(
			$minor_units,
			$exponent,
			$this->separator('DONATIONCAMPAIGNS_DECIMAL_SEPARATOR'),
			$this->separator('DONATIONCAMPAIGNS_THOUSANDS_SEPARATOR')
		);
	}

	/**
	 * Render integer minor units for an EDITABLE field.
	 *
	 * The language's decimal separator, and no grouping at all. parse()
	 * deliberately refuses grouped input rather than guess whether 1.234 is a
	 * thousand or one-and-a-bit, so a grouped value in a form field would be
	 * rejected the moment the administrator pressed save.
	 *
	 * @param int $minor_units
	 * @param int $exponent
	 * @return string
	 */
	public function format_for_input($minor_units, $exponent)
	{
		return $this->render(
			$minor_units,
			$exponent,
			$this->separator('DONATIONCAMPAIGNS_DECIMAL_SEPARATOR'),
			''
		);
	}

	/**
	 * Uses intdiv() and string padding. No division into a float occurs, and
	 * grouping is done by reversing and chunking the digit string rather than
	 * with PHP's grouping helper, which routes through a float.
	 *
	 * @param int $minor_units
	 * @param int $exponent
	 * @param string $decimal
	 * @param string $thousands
	 * @return string
	 */
	protected function render($minor_units, $exponent, $decimal, $thousands)
	{
		$minor_units = (int) $minor_units;
		$exponent = (int) $exponent;

		// The sign is carried separately: grouping operates on digits, and a
		// leading minus would end up inside a group.
		$sign = ($minor_units < 0) ? '-' : '';
		$absolute = ($minor_units < 0) ? -$minor_units : $minor_units;

		if ($exponent <= 0)
		{
			return $sign . $this->group((string) $absolute, $thousands);
		}

		$divisor = (int) (10 ** $exponent);
		$major = intdiv($absolute, $divisor);
		$minor = $absolute - ($major * $divisor);

		return $sign
			. $this->group((string) $major, $thousands)
			. $decimal
			. str_pad((string) $minor, $exponent, '0', STR_PAD_LEFT);
	}

	/**
	 * Insert the thousands separator every three digits from the right.
	 *
	 * String operations only: reverse, chunk, join, reverse back. Nothing here
	 * converts to a number.
	 *
	 * @param string $digits
	 * @param string $separator
	 * @return string
	 */
	protected function group($digits, $separator)
	{
		if ($separator === '' || strlen($digits) <= self::GROUP_SIZE)
		{
			return $digits;
		}

		return strrev(implode($separator, str_split(strrev($digits), self::GROUP_SIZE)));
	}

	/**
	 * @param string $key
	 * @return string
	 */
	protected function separator($key)
	{
		// load_extension() early-returns once a component is loaded
		// (language.php), so asking again costs nothing and removes any
		// ordering assumption about who loaded the file first.
		$this->language->add_lang('common', 'uflagmey/donationcampaigns');

		return (string) $this->language->lang($key);
	}
}
