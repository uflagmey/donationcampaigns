<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

/**
 * The auth double's forum-list answer must match core's shape, or the
 * visibility tests built on it would prove nothing about the real board.
 */
class forum_scoped_auth_test extends \phpbb_test_case
{
	public function test_a_forum_grant_lists_exactly_those_forums()
	{
		$auth = new forum_scoped_auth(array('f_read' => array(2, 4)));

		$this->assertSame(array(2 => array('f_read' => 1), 4 => array('f_read' => 1)), $auth->acl_getf('f_read', true));
	}

	public function test_a_global_grant_expands_to_the_known_forums()
	{
		$auth = new forum_scoped_auth(array('m_approve' => true));
		$auth->known_forums = array(2, 3);

		$this->assertSame(array(2, 3), array_keys($auth->acl_getf('m_approve', true)));
	}

	public function test_no_grant_is_an_empty_list()
	{
		$auth = new forum_scoped_auth(array());

		$this->assertSame(array(), $auth->acl_getf('f_read', true));
		$this->assertSame(array('f_read'), $auth->checked_getf);
	}
}
