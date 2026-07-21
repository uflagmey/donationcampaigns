<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Adds the extension's single administrative permission.
 *
 * This is only half of what the permission needs. Creating the ACL option here
 * makes the permission function; it does NOT make it visible in the ACP
 * permissions interface. That requires the core.permissions listener, without
 * which an administrator can never grant the permission through the UI — an
 * extension that appears broken while every automated check passes.
 *
 * ADR-008: one global administrative permission. Viewing is governed entirely
 * by phpBB's existing forum and topic read permissions, so there is
 * deliberately no user-side permission to add.
 */
class m3_initial_permission extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m2_initial_config');
	}

	public function update_data()
	{
		return array(
			// Global rather than forum-local: this governs ACP access, which
			// has no per-forum dimension.
			array('permission.add', array('a_donationcampaigns', true)),

			// Each grant is guarded by role_exists so that the migration
			// survives boards whose administrative roles have been renamed or
			// removed. An unguarded permission_set() would abort the whole
			// installation on such a board.
			array('if', array(
				array('permission.role_exists', array('ROLE_ADMIN_FULL')),
				array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_donationcampaigns')),
			)),
			array('if', array(
				array('permission.role_exists', array('ROLE_ADMIN_STANDARD')),
				array('permission.permission_set', array('ROLE_ADMIN_STANDARD', 'a_donationcampaigns')),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			// Removing the option also removes every grant referencing it, so
			// a purge leaves no orphaned role or group permission rows.
			array('permission.remove', array('a_donationcampaigns')),
		);
	}
}
