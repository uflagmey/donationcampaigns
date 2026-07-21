<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\service\access;

/**
 * The single source of the campaign authorization rule.
 *
 * access answers three questions and nothing else: is the caller a donation
 * campaigns administrator, may they manage a campaign shell in a given forum,
 * and may they manage the confirmed-donation ledger in a given forum. It holds
 * no request handling and no UI logic; every controller and the topic-tools
 * link consult it rather than calling acl_get directly.
 *
 * The rule (frozen):
 *   is_administrator()          = a_donationcampaigns (global)
 *   can_manage(f)               = admin OR m_donationcampaigns_manage on f
 *   can_manage_donations(f)     = admin OR m_donationcampaigns_donations on f
 *
 * Forum A is 2, forum B is 5 throughout.
 */
class access_test extends \phpbb_test_case
{
	const FORUM_A = 2;
	const FORUM_B = 5;

	/**
	 * @param array<string, true|int[]> $grants
	 * @return access
	 */
	protected function access_for(array $grants)
	{
		return new access(new forum_scoped_auth($grants));
	}

	// ------------------------------------------------------------ administrator

	public function test_the_admin_permission_makes_is_administrator_true()
	{
		$this->assertTrue($this->access_for(array('a_donationcampaigns' => true))->is_administrator());
	}

	public function test_without_the_admin_permission_is_administrator_is_false()
	{
		$this->assertFalse($this->access_for(array())->is_administrator());
		$this->assertFalse($this->access_for(array('m_donationcampaigns_manage' => array(self::FORUM_A)))->is_administrator());
	}

	/**
	 * The global override reaches every forum, including an invalid or zero one.
	 */
	public function test_the_admin_override_grants_both_capabilities_in_any_forum()
	{
		$access = $this->access_for(array('a_donationcampaigns' => true));

		foreach (array(self::FORUM_A, self::FORUM_B, 0, -1, 99999) as $forum)
		{
			$this->assertTrue($access->can_manage($forum), "admin can_manage($forum)");
			$this->assertTrue($access->can_manage_donations($forum), "admin can_manage_donations($forum)");
		}
	}

	// ------------------------------------------------------------------ manager

	public function test_a_manager_may_manage_only_their_own_forum()
	{
		$access = $this->access_for(array('m_donationcampaigns_manage' => array(self::FORUM_A)));

		$this->assertTrue($access->can_manage(self::FORUM_A));
		$this->assertFalse($access->can_manage(self::FORUM_B), 'a manager reached into another forum');
	}

	/**
	 * The manage permission does NOT confer donation-ledger access. This is the
	 * separation the whole permission split exists to guarantee.
	 */
	public function test_the_manage_permission_does_not_grant_donation_access()
	{
		$access = $this->access_for(array('m_donationcampaigns_manage' => array(self::FORUM_A)));

		$this->assertFalse($access->can_manage_donations(self::FORUM_A));
	}

	// ---------------------------------------------------------------- donations

	public function test_a_donation_manager_may_manage_donations_only_in_their_forum()
	{
		$access = $this->access_for(array('m_donationcampaigns_donations' => array(self::FORUM_A)));

		$this->assertTrue($access->can_manage_donations(self::FORUM_A));
		$this->assertFalse($access->can_manage_donations(self::FORUM_B));
	}

	/**
	 * The donation permission does NOT confer campaign-shell management, so a
	 * donations-only holder can never create or edit a campaign.
	 */
	public function test_the_donation_permission_does_not_grant_shell_management()
	{
		$access = $this->access_for(array('m_donationcampaigns_donations' => array(self::FORUM_A)));

		$this->assertFalse($access->can_manage(self::FORUM_A));
	}

	// ------------------------------------------------------------------- neither

	public function test_a_user_with_neither_permission_is_refused_everywhere()
	{
		$access = $this->access_for(array());

		$this->assertFalse($access->is_administrator());
		$this->assertFalse($access->can_manage(self::FORUM_A));
		$this->assertFalse($access->can_manage_donations(self::FORUM_A));
	}

	// --------------------------------------------------- invalid / zero forum id

	/**
	 * A forum-scoped grant never applies to forum 0 (or a negative id). Only the
	 * global admin override reaches such a value, so a malformed forum cannot
	 * accidentally widen a moderator's reach.
	 */
	public function test_a_zero_or_invalid_forum_id_is_not_a_broad_grant()
	{
		$manager = $this->access_for(array('m_donationcampaigns_manage' => array(self::FORUM_A)));

		foreach (array(0, -1) as $forum)
		{
			$this->assertFalse($manager->can_manage($forum), "manager can_manage($forum) must be false");
			$this->assertFalse($manager->can_manage_donations($forum));
		}

		// The admin override, by contrast, still applies.
		$this->assertTrue($this->access_for(array('a_donationcampaigns' => true))->can_manage(0));
	}

	// ---------------------------------------- no accidental broad moderator power

	/**
	 * Holding both permissions on forum A grants both there and neither on B —
	 * proof the checks are independent and forum-scoped, never a blanket
	 * "is a moderator somewhere" test.
	 */
	public function test_permissions_are_independent_and_forum_scoped()
	{
		$access = $this->access_for(array(
			'm_donationcampaigns_manage'	=> array(self::FORUM_A),
			'm_donationcampaigns_donations'	=> array(self::FORUM_A),
		));

		$this->assertTrue($access->can_manage(self::FORUM_A));
		$this->assertTrue($access->can_manage_donations(self::FORUM_A));
		$this->assertFalse($access->can_manage(self::FORUM_B));
		$this->assertFalse($access->can_manage_donations(self::FORUM_B));
	}

	/**
	 * The rule consults exactly the intended ACL options, forum-scoped. A rule
	 * that asked about the wrong option could pass every behavioural test above
	 * by coincidence of the fixture.
	 */
	public function test_it_consults_the_expected_forum_scoped_options()
	{
		$auth = new forum_scoped_auth(array());
		$access = new access($auth);

		$access->can_manage(self::FORUM_A);
		$access->can_manage_donations(self::FORUM_B);

		$this->assertContains(array('a_donationcampaigns', 0), $auth->checked);
		$this->assertContains(array('m_donationcampaigns_manage', self::FORUM_A), $auth->checked);
		$this->assertContains(array('m_donationcampaigns_donations', self::FORUM_B), $auth->checked);
	}
}
