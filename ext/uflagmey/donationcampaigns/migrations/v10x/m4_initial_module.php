<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Registers the ACP category and its three modes.
 *
 * Uses the module_basename + modes form rather than a fully manual array, so
 * that langname, mode, auth and display are read from acp/main_info.php.
 * The manual form duplicates that metadata into the migration, where the two
 * copies drift apart the first time a mode's auth expression changes.
 */
class m4_initial_module extends \phpbb\db\migration\migration
{
	const MODULE_BASENAME = '\uflagmey\donationcampaigns\acp\main_module';

	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m3_initial_permission');
	}

	/**
	 * Already installed when the category AND all three modes are present.
	 *
	 * phpBB's module tool THROWS MODULE_EXISTS when asked to add a module that
	 * is already there (tool/module.php:238), where the config and permission
	 * tools return quietly. Without this probe, a board whose module rows
	 * survived without their phpbb_migrations row -- a restored partial
	 * backup, an interrupted purge -- aborted the whole install with an
	 * untranslated exception instead of resuming.
	 *
	 * The modes are checked as well as the category deliberately: a category
	 * with nothing under it is not an installation, and reporting it as one
	 * would leave an ACP entry that opens nothing.
	 *
	 * @return bool
	 */
	public function effectively_installed()
	{
		// The category lives under ACP_CAT_DOT_MODS, whose id varies by board,
		// so it is found by its own langname rather than by parent.
		$category_id = $this->get_category_id();

		if ($category_id === 0)
		{
			return false;
		}

		foreach (array('settings', 'campaigns', 'donations') as $mode)
		{
			if ($this->get_module_id(self::MODULE_BASENAME, $category_id, $mode) === 0)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @return int
	 */
	protected function get_category_id()
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_basename = ''
				AND module_langname = 'ACP_DONATIONCAMPAIGNS'";

		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return (int) $module_id;
	}

	/**
	 * The id of one acp module row, or 0.
	 *
	 * A migration is handed the database but not the module tool, so this
	 * asks directly rather than reaching for a service it does not have.
	 *
	 * @param string $basename
	 * @param int $parent_id
	 * @param string $mode
	 * @return int
	 */
	protected function get_module_id($basename, $parent_id, $mode)
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND parent_id = " . (int) $parent_id . "
				AND module_basename = '" . $this->db->sql_escape($basename) . "'
				AND module_mode = '" . $this->db->sql_escape($mode) . "'";

		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return (int) $module_id;
	}

	public function update_data()
	{
		return array(
			// The category, under ACP -> Extensions.
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_DONATIONCAMPAIGNS',
			)),

			// The three modes, derived from acp/main_info.php.
			array('module.add', array(
				'acp',
				'ACP_DONATIONCAMPAIGNS',
				array(
					'module_basename'	=> '\uflagmey\donationcampaigns\acp\main_module',
					'modes'				=> array('settings', 'campaigns', 'donations'),
				),
			)),
		);
	}

	/**
	 * Mirrors update_data() in reverse, and the ORDER MATTERS.
	 *
	 * phpBB's module_manager::delete_module() throws CANNOT_REMOVE_MODULE when
	 * a module still has children, so the three modes must go before the
	 * category that contains them. Removing the category first does not
	 * cascade — it fails, and the purge leaves the extension's ACP entries
	 * behind on the board.
	 */
	public function revert_data()
	{
		return array(
			// Children first.
			array('module.remove', array(
				'acp',
				'ACP_DONATIONCAMPAIGNS',
				array(
					'module_basename'	=> '\uflagmey\donationcampaigns\acp\main_module',
					'modes'				=> array('settings', 'campaigns', 'donations'),
				),
			)),

			// Then the now-empty category.
			array('module.remove', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_DONATIONCAMPAIGNS')),
		);
	}
}
