<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Replaces the two forum-scoped MODERATOR permissions with FORUM permissions.
 *
 * m7 shipped m_donationcampaigns_manage and m_donationcampaigns_donations. In
 * phpBB any m_* grant is more than the permission itself: acl_set() also sets
 * the m_ any-flag, which opens the MCP (mcp.php), shows the MCP link and topic
 * logs on viewtopic, and phpbb_cache_moderators() lists the holder as a
 * moderator of the forum. Communities that want a non-moderator group to run
 * donation campaigns could not have that without making the group moderators.
 *
 * Forum permissions (f_*) carry none of those side effects. They are the family
 * phpBB uses for feature rights inside a forum — f_poll is the direct analogue —
 * and are assigned per group and per forum on the Forum permissions tab. See
 * ADR-016.
 *
 * CLEAN BREAK (owner decision, 1.0.0-beta2). Existing m_ grants are NOT carried
 * over: the board owner re-assigns the new f_ permissions. The new options are
 * granted to nothing, exactly like m7's.
 *
 * THE ORPHANED FLAG. permission.remove deletes only the rows of the option it
 * removes (migration/tool/permission.php). It never touches the m_ any-flag that
 * acl_set() wrote alongside our option, so without help every former holder
 * would KEEP MCP access and stay listed as a moderator. clear_orphaned_flags()
 * removes that flag wherever our options were its only reason to exist. It runs
 * BEFORE the removal, while the grants still show who held our options, which
 * keeps every step stateless: migration steps may run in separate requests.
 *
 * m7 is a shipped migration and is deliberately left unchanged.
 */
class m8_forum_permissions extends \phpbb\db\migration\container_aware_migration
{
	/** The moderator permissions being retired. */
	const OLD_OPTIONS = array('m_donationcampaigns_manage', 'm_donationcampaigns_donations');

	/** Their forum-permission replacements. */
	const NEW_OPTIONS = array('f_donationcampaigns_manage', 'f_donationcampaigns_donations');

	public static function depends_on()
	{
		return array('\uflagmey\donationcampaigns\migrations\v10x\m7_manage_permissions');
	}

	/**
	 * Installed means both new options exist and neither old one does, so a
	 * partially applied run is completed rather than skipped.
	 */
	public function effectively_installed()
	{
		$present = $this->existing_options(array_merge(self::OLD_OPTIONS, self::NEW_OPTIONS));

		return !array_intersect(self::OLD_OPTIONS, $present)
			&& !array_diff(self::NEW_OPTIONS, $present);
	}

	public function update_data()
	{
		return array(
			// false = local: forum-scoped, like the options they replace.
			array('permission.add', array('f_donationcampaigns_manage', false)),
			array('permission.add', array('f_donationcampaigns_donations', false)),

			// Must precede the removal: it reads the grants that are about to go.
			array('custom', array(array($this, 'clear_orphaned_flags'))),

			array('permission.remove', array('m_donationcampaigns_manage', false)),
			array('permission.remove', array('m_donationcampaigns_donations', false)),

			// The moderator list is a cache table; rebuild it so former holders
			// stop appearing as moderators immediately, not at the next
			// permission change.
			array('custom', array(array($this, 'rebuild_moderator_cache'))),
		);
	}

	/**
	 * Restores m7's shape: the m_ options exist again, ungranted, so m7's own
	 * revert finds what it expects. Grants removed on update are not restored —
	 * the clean break is one-way by design.
	 */
	public function revert_data()
	{
		return array(
			array('permission.remove', array('f_donationcampaigns_manage', false)),
			array('permission.remove', array('f_donationcampaigns_donations', false)),
			array('permission.add', array('m_donationcampaigns_manage', false)),
			array('permission.add', array('m_donationcampaigns_donations', false)),
		);
	}

	/**
	 * Delete every m_ any-flag that is set to YES only because of our options.
	 *
	 * A flag survives when the same subject still holds another m_* permission
	 * set to YES in the same scope. NEVER and NO flags are left alone: they grant
	 * nothing. Covers direct group grants, direct user grants and role data.
	 *
	 * @return void
	 */
	public function clear_orphaned_flags()
	{
		$ids = $this->moderator_option_ids();

		if ($ids['flag'] === 0 || !$ids['ours'])
		{
			return;
		}

		foreach (array('acl_groups' => 'group_id', 'acl_users' => 'user_id') as $table => $subject)
		{
			$this->clear_subject_flags($this->table_prefix . $table, $subject, $ids);
		}

		$this->clear_role_flags($ids);
	}

	/**
	 * @return void
	 */
	public function rebuild_moderator_cache()
	{
		if (!function_exists('phpbb_cache_moderators'))
		{
			include($this->phpbb_root_path . 'includes/functions_admin.' . $this->php_ext);
		}

		phpbb_cache_moderators($this->db, $this->container->get('cache'), $this->container->get('auth'));
	}

	/**
	 * @param string $table   acl_groups or acl_users, prefixed
	 * @param string $subject group_id or user_id
	 * @param array  $ids     from moderator_option_ids()
	 * @return void
	 */
	protected function clear_subject_flags($table, $subject, array $ids)
	{
		// Every (subject, forum) that holds one of our options directly.
		$sql = 'SELECT DISTINCT ' . $subject . ' AS subject_id, forum_id
			FROM ' . $table . '
			WHERE auth_role_id = 0
				AND ' . $this->db->sql_in_set('auth_option_id', $ids['ours']);
		$result = $this->db->sql_query($sql);
		$scopes = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach ($scopes as $scope)
		{
			$where = $subject . ' = ' . (int) $scope['subject_id'] . '
				AND forum_id = ' . (int) $scope['forum_id'] . '
				AND auth_role_id = 0';

			if ($this->has_other_yes($table, $where, $ids['others']))
			{
				continue;
			}

			$this->delete_yes_flag($table, $where, $ids['flag']);
		}
	}

	/**
	 * @param array $ids from moderator_option_ids()
	 * @return void
	 */
	protected function clear_role_flags(array $ids)
	{
		$table = $this->table_prefix . 'acl_roles_data';

		$sql = 'SELECT DISTINCT role_id
			FROM ' . $table . '
			WHERE ' . $this->db->sql_in_set('auth_option_id', $ids['ours']);
		$result = $this->db->sql_query($sql);
		$role_ids = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$role_ids[] = (int) $row['role_id'];
		}
		$this->db->sql_freeresult($result);

		foreach ($role_ids as $role_id)
		{
			$where = 'role_id = ' . $role_id;

			if ($this->has_other_yes($table, $where, $ids['others']))
			{
				continue;
			}

			$this->delete_yes_flag($table, $where, $ids['flag']);
		}
	}

	/**
	 * @param string $table
	 * @param string $where   scope condition, built from integers only
	 * @param int[]  $others  ids of every other m_* option
	 * @return bool
	 */
	protected function has_other_yes($table, $where, array $others)
	{
		if (!$others)
		{
			return false;
		}

		$sql = 'SELECT auth_option_id
			FROM ' . $table . '
			WHERE ' . $where . '
				AND auth_setting = ' . ACL_YES . '
				AND ' . $this->db->sql_in_set('auth_option_id', $others);
		$result = $this->db->sql_query_limit($sql, 1);
		$found = (bool) $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $found;
	}

	/**
	 * @param string $table
	 * @param string $where   scope condition, built from integers only
	 * @param int    $flag_id the m_ any-flag option id
	 * @return void
	 */
	protected function delete_yes_flag($table, $where, $flag_id)
	{
		$sql = 'DELETE FROM ' . $table . '
			WHERE ' . $where . '
				AND auth_option_id = ' . (int) $flag_id . '
				AND auth_setting = ' . ACL_YES;
		$this->db->sql_query($sql);
	}

	/**
	 * Classify the moderator options in one read. Filtering by prefix in PHP
	 * avoids LIKE, whose '_' wildcard would need escaping on every DBMS.
	 *
	 * @return array{flag:int,ours:int[],others:int[]}
	 */
	protected function moderator_option_ids()
	{
		$ids = array('flag' => 0, 'ours' => array(), 'others' => array());

		$sql = 'SELECT auth_option_id, auth_option
			FROM ' . $this->table_prefix . 'acl_options';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$option = $row['auth_option'];
			$id = (int) $row['auth_option_id'];

			if ($option === 'm_')
			{
				$ids['flag'] = $id;
			}
			else if (in_array($option, self::OLD_OPTIONS, true))
			{
				$ids['ours'][] = $id;
			}
			else if (strpos($option, 'm_') === 0)
			{
				$ids['others'][] = $id;
			}
		}
		$this->db->sql_freeresult($result);

		return $ids;
	}

	/**
	 * @param string[] $options
	 * @return string[] the subset that exists in acl_options
	 */
	protected function existing_options(array $options)
	{
		$sql = 'SELECT auth_option
			FROM ' . $this->table_prefix . 'acl_options
			WHERE ' . $this->db->sql_in_set('auth_option', $options);
		$result = $this->db->sql_query($sql);
		$present = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$present[] = $row['auth_option'];
		}
		$this->db->sql_freeresult($result);

		return $present;
	}
}
