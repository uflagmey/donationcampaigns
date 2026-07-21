<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Removes the Donations entry from the ACP menu without unregistering it.
 *
 * Donation management is a sub-view of a SELECTED campaign: it needs a
 * campaign_id to mean anything. m4 registered it as a third menu item
 * alongside Settings and Campaigns, and a menu link carries no campaign_id,
 * so clicking it reported "That campaign no longer exists." — a data-loss
 * message for a page that had simply been opened without context.
 *
 * WHY HIDE RATHER THAN REMOVE. phpBB resolves a mode from the modules table:
 * p_master::list_modules() reads the rows and set_active() matches against
 * them. Deleting the row makes the mode undispatchable and load_active()
 * raises MODULE_NOT_ACCESS (functions_module.php:573), which would break the
 * campaign list's new Donations link — the very entry point this fix adds.
 *
 * module_display separates the two concerns exactly, and this is what core
 * itself does for ACP_USER_PROFILE, ACP_PERMISSION_TRACE and UCP_PM_VIEW,
 * all sub-views reached from a parent page with an id in the URL:
 *
 *   functions_module.php:842  menu builder  skips !display  -> hidden
 *   functions_module.php:521  set_active()  no display test -> dispatchable
 *
 * The module tool has no method for this column, so the change is made with a
 * custom callable. Migrations are the one layer permitted to hold SQL.
 *
 * The UPDATE is idempotent, and matches on the extension's own module class
 * as well as the mode, so a foreign module that happens to use the mode name
 * "donations" is left alone.
 */
class m5_hide_donations_module extends \phpbb\db\migration\migration
{
	const MODULE_CLASS = '\uflagmey\donationcampaigns\acp\main_module';

	const MODE = 'donations';

	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m4_initial_module');
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'hide_donations_module'))),
		);
	}

	/**
	 * phpBB does not reverse a 'custom' step automatically — the migrator
	 * returns false for it when reversing (migrator.php:782) — so the inverse
	 * is declared explicitly here.
	 *
	 * It matters: on purge this runs before m4's revert, which removes the
	 * three module rows it created. Restoring the flag first leaves m4 the
	 * state it expects instead of a half-modified one.
	 */
	public function revert_data()
	{
		return array(
			array('custom', array(array($this, 'show_donations_module'))),
		);
	}

	/**
	 * @return void
	 */
	public function hide_donations_module()
	{
		$this->set_module_display(0);
	}

	/**
	 * @return void
	 */
	public function show_donations_module()
	{
		$this->set_module_display(1);
	}

	/**
	 * @param int $display
	 * @return void
	 */
	protected function set_module_display($display)
	{
		$sql = 'UPDATE ' . $this->table_prefix . "modules
			SET module_display = " . (int) $display . "
			WHERE module_class = 'acp'
				AND module_basename = '" . $this->db->sql_escape(self::MODULE_CLASS) . "'
				AND module_mode = '" . $this->db->sql_escape(self::MODE) . "'";

		$this->db->sql_query($sql);
	}
}
