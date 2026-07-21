<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Gives each campaign its own text for the public action button.
 *
 * external_url was already an optional destination the administrator chooses;
 * the button pointing at it carried a fixed "Donate" label, which is wrong for
 * most of the things a board actually links to. A campaign can now say "How to
 * donate", "Request bank details", "Über PayPal spenden" or whatever fits.
 *
 * THIS IS A LABEL, NOT AN INTEGRATION. The extension processes no payments,
 * embeds no provider markup, loads no provider script and imports no
 * transactions. Nothing here is provider-specific: the column holds plain text
 * and the destination stays a scheme-validated URL. Money is still confirmed
 * by hand in the ACP.
 *
 * VCHAR:100 rather than the 255 used elsewhere in this table. This is a button
 * label; 100 characters is already far past anything that renders sensibly,
 * and the narrower column is a cheap guard on the public layout. Characters,
 * not bytes -- "Über PayPal spenden" must fit as 19, not 20.
 *
 * The default is deliberately generic English. A provider-specific default
 * would ship an opinion about where boards take money.
 */
class m6_campaign_link_text extends \phpbb\db\migration\migration
{
	const COLUMN = 'external_link_text';

	const MAX_LENGTH = 100;

	const DEFAULT_TEXT = 'How to donate';

	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m5_hide_donations_module');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'ufdc_campaigns'	=> array(
					self::COLUMN	=> array('VCHAR:' . self::MAX_LENGTH, self::DEFAULT_TEXT),
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

	/**
	 * Backfill.
	 *
	 * A column default covers rows written after the ALTER, and most engines
	 * apply it to existing rows as well -- but that is engine behaviour rather
	 * than something phpBB's schema API guarantees, and a campaign with a URL
	 * and no button text would render a button with no label. Setting it
	 * explicitly costs one statement and removes the doubt.
	 */
	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'set_default_link_text'))),
		);
	}

	/**
	 * @return void
	 */
	public function set_default_link_text()
	{
		// Only rows that have no text. Re-running must never overwrite what an
		// administrator has since configured, and phpBB will re-run a data
		// step whenever a migration is reapplied.
		$sql = 'UPDATE ' . $this->table_prefix . 'ufdc_campaigns
			SET ' . self::COLUMN . " = '" . $this->db->sql_escape(self::DEFAULT_TEXT) . "'
			WHERE " . self::COLUMN . " = ''
				OR " . self::COLUMN . ' IS NULL';

		$this->db->sql_query($sql);
	}
}
