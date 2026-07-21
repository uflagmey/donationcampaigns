<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Adds the two forum-scoped moderator permissions for the frontend workflow.
 *
 * RC2 moves campaign management out of the ACP to the topic, so authorization
 * becomes forum-scoped. This adds:
 *
 *   - m_donationcampaigns_manage      the campaign shell
 *   - m_donationcampaigns_donations   the confirmed-donation ledger
 *
 * Both are LOCAL (forum-scoped) moderator permissions, added with the global
 * flag false. They are deliberately granted to NOTHING: there is no
 * permission.role_add or permission.permission_set step. A board owner opts in
 * per forum, so applying this migration on an existing board changes no board's
 * behaviour and gives no moderator a capability they did not have. The existing
 * a_donationcampaigns permission and its grants are untouched and remain the
 * global override.
 *
 * The permission tool creates the 'm_' flag option if the board has no m_*
 * permission yet; that option is core-owned infrastructure and the revert does
 * not remove it, exactly as m3 leaves the 'a_' flag alone.
 */
class m7_manage_permissions extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text');
	}

	/**
	 * Both options already present means the migration's whole effect is in
	 * place, so a partially-applied or manually-seeded board is repaired by a
	 * re-run rather than skipped half-done.
	 */
	public function effectively_installed()
	{
		$sql = 'SELECT auth_option FROM ' . $this->table_prefix . 'acl_options
			WHERE ' . $this->db->sql_in_set('auth_option', array(
				'm_donationcampaigns_manage',
				'm_donationcampaigns_donations',
			));
		$result = $this->db->sql_query($sql);

		$found = 0;
		while ($this->db->sql_fetchrow($result))
		{
			$found++;
		}
		$this->db->sql_freeresult($result);

		return $found === 2;
	}

	public function update_data()
	{
		return array(
			// false = local: these are forum-scoped moderator permissions.
			array('permission.add', array('m_donationcampaigns_manage', false)),
			array('permission.add', array('m_donationcampaigns_donations', false)),

			// No grant step, deliberately. The permissions ship ungranted; a
			// board owner assigns them per forum through the ACP.
		);
	}

	public function revert_data()
	{
		return array(
			// false = local, matching how they were added: permission.remove
			// defaults to global and would silently miss a forum-scoped option.
			// Removing an option also removes any grants referencing it, so a
			// purge leaves no orphaned role, group or user permission rows.
			array('permission.remove', array('m_donationcampaigns_manage', false)),
			array('permission.remove', array('m_donationcampaigns_donations', false)),
		);
	}
}
