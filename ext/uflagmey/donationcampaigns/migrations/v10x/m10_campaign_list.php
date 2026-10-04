<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * The switch for the board-wide campaign list (1.0.0-beta3, ADR-018).
 *
 *   donationcampaigns_list_enabled   0 = no list page, no quick-links entry
 *                                    1 = app.php/donationcampaigns is public
 *
 * OFF BY DEFAULT. An update must not publish a page the administrator never
 * asked for, so the route answers 404 until the setting is switched on in the
 * ACP. The same principle as m9: nothing visible changes on upgrade.
 *
 * Config only — the list reads existing tables and needs no schema change.
 */
class m10_campaign_list extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m9_display_options');
	}

	public function update_data()
	{
		return array(
			// config.add never overwrites an existing value, so a re-run keeps
			// a list the administrator has since switched on.
			array('config.add', array('donationcampaigns_list_enabled', 0)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('donationcampaigns_list_enabled')),
		);
	}
}
