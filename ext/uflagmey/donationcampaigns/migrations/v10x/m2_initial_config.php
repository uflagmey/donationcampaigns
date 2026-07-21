<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Creates the extension's four configuration values.
 *
 * This migration establishes STORAGE DEFAULTS ONLY. Range checking, ISO code
 * validation and administrator-facing warnings belong to the ACP settings
 * form, not here — a migration that validated its own defaults would be
 * validating values no user supplied.
 *
 * Config keys use the full donationcampaigns_ prefix. Unlike physical database
 * identifiers (ADR-012 amendment), the config namespace has no length limit,
 * so there is no reason to abbreviate.
 *
 * IMMUTABLE once released: a migration that has run on any board must never be
 * edited. Changing a default, adding a key or correcting a value requires a new
 * migration in the chain. See specification section 8.
 */
class m2_initial_config extends \phpbb\db\migration\migration
{
	/**
	 * Guards against a re-run on an installation that already carries the
	 * extension's configuration.
	 *
	 * Keys on the currency code, the first value this migration adds. There is
	 * deliberately no extension version config key to check: phpBB reads an
	 * extension's version from composer.json, and migration state lives in the
	 * migrations table. See the ADR note in specification section 4.4.
	 */
	public function effectively_installed()
	{
		return isset($this->config['donationcampaigns_currency_code']);
	}

	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema');
	}

	public function update_data()
	{
		return array(
			// ISO 4217 alphabetic code, three uppercase letters. Display only;
			// no currency conversion is performed anywhere in this extension.
			array('config.add', array('donationcampaigns_currency_code', 'EUR')),

			// Symbol rendered beside amounts. Separate from the code so that a
			// board can show "€" while recording "EUR".
			array('config.add', array('donationcampaigns_currency_symbol', '€')),

			// Number of minor-unit digits: 2 for EUR/USD/GBP, 0 for JPY, 3 for
			// KWD. Stored as an integer count of decimal places, never as a
			// divisor and never as a float. currency_formatter uses it for
			// string padding only.
			//
			// Safe range for later ACP validation: 0..4.
			array('config.add', array('donationcampaigns_currency_exponent', 2)),

			// Maximum donor names rendered in the campaign box; the remainder
			// are summarised as "and N others".
			//
			// Safe range for later ACP validation: 1..500. The upper bound
			// exists because every rendered name costs a row read on a public
			// page; the lower bound because 0 would silently disable the donor
			// list while leaving its campaign toggle switched on.
			array('config.add', array('donationcampaigns_donor_list_limit', 25)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('donationcampaigns_currency_code')),
			array('config.remove', array('donationcampaigns_currency_symbol')),
			array('config.remove', array('donationcampaigns_currency_exponent')),
			array('config.remove', array('donationcampaigns_donor_list_limit')),
		);
	}
}
