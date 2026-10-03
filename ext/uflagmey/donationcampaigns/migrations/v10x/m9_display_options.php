<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Display options requested in the 1.0.0-beta1 review.
 *
 * Two board settings for the currency symbol and one per-campaign flag for the
 * donation date (ADR-017):
 *
 *   donationcampaigns_currency_symbol_before   0 = "10,00 €" (beta1), 1 = "€ 10,00"
 *   donationcampaigns_currency_symbol_space    1 = separated (beta1), 0 = "$10.00"
 *   ufdc_campaigns.show_donation_date          show the date in the public donor list
 *
 * NOTHING CHANGES ON UPGRADE. Both settings default to the beta1 layout, and
 * the column defaults to 0, so every existing campaign keeps its public page
 * exactly as it was: a donation date is visible only once an administrator or
 * campaign manager ticks the box. The create form proposes the box ticked for
 * NEW campaigns; that is a form default, not a schema default.
 */
class m9_display_options extends \phpbb\db\migration\migration
{
	const COLUMN = 'show_donation_date';

	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m8_forum_permissions');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'ufdc_campaigns'	=> array(
					self::COLUMN	=> array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'ufdc_campaigns'	=> array(self::COLUMN),
			),
		);
	}

	public function update_data()
	{
		return array(
			// config.add never overwrites an existing value, so a re-run keeps
			// what the administrator has since chosen.
			array('config.add', array('donationcampaigns_currency_symbol_before', 0)),
			array('config.add', array('donationcampaigns_currency_symbol_space', 1)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('donationcampaigns_currency_symbol_before')),
			array('config.remove', array('donationcampaigns_currency_symbol_space')),
		);
	}
}
