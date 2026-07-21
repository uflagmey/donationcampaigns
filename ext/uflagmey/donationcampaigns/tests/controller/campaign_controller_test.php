<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The frontend campaign controller: manage, create and edit.
 *
 * Every action runs the same authorisation chain — load topic, derive its
 * current forum, load and verify the campaign, authorise against that forum —
 * and refuses everything else with one uniform 404. These tests assert that
 * chain across the whole actor x state matrix, and that a refusal never reveals
 * whether a foreign object exists.
 */
class campaign_controller_test extends controller_test_case
{
	// Actors, by grant map (forum A = 2, forum B = 3).

	protected function as_admin()
	{
		$this->as_actor(array('a_donationcampaigns' => true));
	}

	protected function as_manager_a()
	{
		$this->as_actor(array('m_donationcampaigns_manage' => array(self::FORUM_A)));
	}

	protected function as_manager_b()
	{
		$this->as_actor(array('m_donationcampaigns_manage' => array(self::FORUM_B)));
	}

	protected function as_donations_a()
	{
		$this->as_actor(array('m_donationcampaigns_donations' => array(self::FORUM_A)));
	}

	protected function as_donations_b()
	{
		$this->as_actor(array('m_donationcampaigns_donations' => array(self::FORUM_B)));
	}

	protected function as_nobody()
	{
		$this->as_actor(array());
	}

	/**
	 * A valid create/edit submission.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function form(array $overrides = array())
	{
		return array_merge(array(
			'campaign_title'		=> 'Legal fund',
			'campaign_desc'			=> 'Help cover legal costs.',
			'target_amount'			=> '250.00',
			'external_url'			=> '',
			'external_link_text'	=> 'How to donate',
			'show_donor_names'		=> 1,
			'show_donation_count'	=> 1,
		), $overrides);
	}

	// ---------------------------------------------------------- landing access

	public function test_a_manager_sees_the_landing_for_an_existing_campaign()
	{
		$this->as_manager_a();
		$this->request();
		$this->controller->manage(10);

		$this->assertSame('donationcampaigns_manage.html', $this->helper->rendered['template']);
		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_CAN_MANAGE']);
	}

	public function test_a_donations_only_user_may_open_the_landing()
	{
		$this->as_donations_a();
		$this->request();
		$this->controller->manage(10);

		$this->assertSame('donationcampaigns_manage.html', $this->helper->rendered['template']);
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_CAN_MANAGE']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_CAN_DONATIONS']);
	}

	public function test_an_admin_may_open_any_landing_including_a_disabled_campaign()
	{
		$this->as_admin();
		$this->request();
		$this->controller->manage(20);

		$this->assertSame('donationcampaigns_manage.html', $this->helper->rendered['template']);
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ENABLED'], 'A disabled campaign must still open its landing');
	}

	public function test_a_manager_of_another_forum_is_denied_the_landing()
	{
		$this->as_manager_b();
		$this->assert_denied(function () {
			$this->controller->manage(10);
		});
	}

	public function test_a_user_with_no_permission_is_denied_the_landing()
	{
		$this->as_nobody();
		$this->assert_denied(function () {
			$this->controller->manage(10);
		});
	}

	// -------------------------------------------------------- landing / no campaign

	public function test_manage_offers_the_create_form_to_a_manager_on_a_free_topic()
	{
		$this->as_manager_a();
		$this->request();
		$this->controller->manage(11);

		$this->assertSame('donationcampaigns_campaign_form.html', $this->helper->rendered['template']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
	}

	public function test_manage_refuses_a_donations_only_user_on_a_free_topic()
	{
		$this->as_donations_a();
		$this->request();
		$this->controller->manage(11);

		$this->assertNull($this->helper->rendered, 'No form must be rendered');
		$this->assertNotNull($this->helper->message);
		$this->assertStringContainsString('no donation campaign', $this->helper->message);
	}

	// ------------------------------------------------------------ invalid topics

	public function test_manage_on_a_missing_topic_is_denied()
	{
		$this->as_admin();
		$this->assert_denied(function () {
			$this->controller->manage(99999);
		});
	}

	public function test_manage_on_a_moved_shadow_topic_is_denied()
	{
		$this->as_admin();
		$this->assert_denied(function () {
			$this->controller->manage(40);
		});
	}

	public function test_manage_on_a_zero_topic_is_denied()
	{
		$this->as_admin();
		$this->assert_denied(function () {
			$this->controller->manage(0);
		});
	}

	// -------------------------------------------------------------------- create

	public function test_a_manager_creates_a_campaign_on_a_free_topic()
	{
		$this->as_manager_a();
		$this->post($this->form(array('campaign_title' => 'Legal fund')));
		$this->controller->create(11);

		$created = $this->campaigns->find_by_topic_id(11);
		$this->assertNotNull($created);
		$this->assertSame('Legal fund', $created['campaign_title']);
		$this->assertSame(25000, $created['target_amount']);
		$this->assertNotNull($this->helper->message, 'The saved message was not shown');
	}

	public function test_creating_a_campaign_is_logged_to_the_moderator_log_with_context()
	{
		$this->as_manager_a();
		$this->post($this->form());
		$this->controller->create(11);

		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED', $this->log->operations);

		$entry = end($this->log->entries);
		$this->assertSame('mod', $entry[0], 'Frontend actions log to the moderator log');
		$this->assertSame(self::FORUM_A, $entry[5]['forum_id']);
		$this->assertSame(11, $entry[5]['topic_id']);
	}

	public function test_create_renders_the_form_on_a_get()
	{
		$this->as_manager_a();
		$this->request();
		$this->controller->create(11);

		$this->assertSame('donationcampaigns_campaign_form.html', $this->helper->rendered['template']);
		$this->assertNull($this->campaigns->find_by_topic_id(11), 'A GET must not create anything');
	}

	public function test_create_is_denied_to_a_manager_of_another_forum()
	{
		$this->as_manager_b();
		$this->post($this->form());

		$this->assert_denied(function () {
			$this->controller->create(11);
		});
		$this->assertNull($this->campaigns->find_by_topic_id(11));
	}

	public function test_create_is_denied_to_a_donations_only_user()
	{
		$this->as_donations_a();
		$this->post($this->form());

		$this->assert_denied(function () {
			$this->controller->create(11);
		});
		$this->assertNull($this->campaigns->find_by_topic_id(11), 'A donations-only user must never create a campaign');
	}

	public function test_create_without_a_valid_form_key_is_denied_and_writes_nothing()
	{
		$this->as_manager_a();
		$this->post($this->form(), false);

		$this->assert_denied(function () {
			$this->controller->create(11);
		});
		$this->assertNull($this->campaigns->find_by_topic_id(11));
	}

	public function test_create_on_a_taken_topic_redirects_to_the_landing()
	{
		$this->as_manager_a();
		$before = $this->campaigns->count_all();

		$this->request();
		$response = $this->controller->create(10);

		$this->assertInstanceOf(RedirectResponse::class, $response);
		$this->assertContains(array('uflagmey_donationcampaigns_manage', array('topic_id' => 10)), $this->helper->routed);
		$this->assertSame($before, $this->campaigns->count_all(), 'No second campaign was created');
	}

	public function test_create_rejects_an_invalid_target_and_keeps_the_entered_values()
	{
		$this->as_manager_a();
		$this->post($this->form(array('campaign_title' => 'Kept title', 'target_amount' => '0')));
		$this->controller->create(11);

		$this->assertNull($this->campaigns->find_by_topic_id(11));
		$this->assertSame('donationcampaigns_campaign_form.html', $this->helper->rendered['template']);
		$this->assertSame('Kept title', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ERROR']);
	}

	public function test_create_fixes_the_topic_from_the_url_ignoring_a_posted_topic_id()
	{
		$this->as_manager_a();
		$this->post($this->form(array('topic_id' => 20)));
		$this->controller->create(11);

		$this->assertNotNull($this->campaigns->find_by_topic_id(11), 'The campaign must be created on the URL topic');
		$this->assertSame(11, $this->campaigns->find_by_topic_id(11)['topic_id']);
	}

	// ---------------------------------------------------------------------- edit

	public function test_a_manager_edits_a_campaign()
	{
		$this->as_manager_a();
		$this->post($this->form(array('campaign_title' => 'Renamed fund', 'target_amount' => '500.00')));
		$this->controller->edit(1);

		$updated = $this->campaigns->find_by_id(1);
		$this->assertSame('Renamed fund', $updated['campaign_title']);
		$this->assertSame(50000, $updated['target_amount']);
		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED', $this->log->operations);
	}

	public function test_edit_renders_the_form_with_stored_values_on_a_get()
	{
		$this->as_manager_a();
		$this->request();
		$this->controller->edit(1);

		$this->assertSame('donationcampaigns_campaign_form.html', $this->helper->rendered['template']);
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	public function test_edit_is_denied_to_a_manager_of_another_forum()
	{
		$this->as_manager_b();
		$this->assert_denied(function () {
			$this->controller->edit(1);
		});
	}

	public function test_edit_is_denied_to_a_donations_only_user()
	{
		$this->as_donations_a();
		$this->assert_denied(function () {
			$this->controller->edit(1);
		});
	}

	public function test_edit_of_an_unknown_campaign_is_denied()
	{
		$this->as_admin();
		$this->assert_denied(function () {
			$this->controller->edit(99999);
		});
	}

	public function test_edit_cannot_reassign_the_topic()
	{
		$this->as_manager_a();
		$this->post($this->form(array('topic_id' => 20)));
		$this->controller->edit(1);

		$this->assertSame(10, $this->campaigns->find_by_id(1)['topic_id'], 'A campaign must not be movable to another topic');
	}

	public function test_edit_preserves_the_enabled_flag_which_is_not_on_the_form()
	{
		$this->as_manager_a();
		$this->post($this->form());
		$this->controller->edit(1);

		$this->assertTrue($this->campaigns->find_by_id(1)['campaign_enabled'], 'Editing must not disable an enabled campaign');
	}

	public function test_admin_may_edit_a_disabled_campaign_in_another_forum()
	{
		$this->as_admin();
		$this->request();
		$this->controller->edit(2);

		$this->assertSame('donationcampaigns_campaign_form.html', $this->helper->rendered['template']);
	}

	// ------------------------------------------------------------- forum moves

	public function test_moving_a_topic_moves_the_authorisation_to_the_new_forum()
	{
		// Campaign 1's topic 10 starts in forum A.
		$this->as_manager_b();
		$this->assert_denied(function () {
			$this->controller->edit(1);
		});

		// Move topic 10 into forum B.
		$this->db->sql_query('UPDATE phpbb_topics SET forum_id = ' . self::FORUM_B . ' WHERE topic_id = 10');

		// Now the forum-B manager may edit it, and the forum-A manager may not.
		$this->as_manager_b();
		$this->request();
		$this->controller->edit(1);
		$this->assertSame('donationcampaigns_campaign_form.html', $this->helper->rendered['template']);

		$this->as_manager_a();
		$this->assert_denied(function () {
			$this->controller->edit(1);
		});
	}

	// -------------------------------------------- no object-existence disclosure

	public function test_a_foreign_campaign_and_a_missing_one_produce_identical_denials()
	{
		$this->as_manager_b();

		$foreign = $this->denial(function () {
			$this->controller->edit(1);      // exists, in forum A — not ours
		});
		$missing = $this->denial(function () {
			$this->controller->edit(99999);  // does not exist
		});

		$this->assertNotNull($foreign);
		$this->assertNotNull($missing);
		$this->assertSame($missing->getStatusCode(), $foreign->getStatusCode());
		$this->assertSame($missing->getMessage(), $foreign->getMessage());
	}

	// ------------------------------------------------- the admin override limits

	public function test_the_admin_override_does_not_bypass_topic_validation()
	{
		// An administrator is still refused on a missing topic and a shadow: the
		// override is a forum check, not a licence to skip steps 1-5.
		$this->as_admin();

		$this->assert_denied(function () {
			$this->controller->manage(99999);
		});
		$this->assert_denied(function () {
			$this->controller->manage(40);
		});
	}

	// ------------------------------------------------------------------ enable

	public function test_a_manager_enables_a_disabled_campaign()
	{
		// Campaign 2 is disabled, on topic 20 in forum B.
		$this->as_manager_b();
		$this->post_toggle();
		$this->controller->enable(2);

		$this->assertTrue($this->campaigns->find_by_id(2)['campaign_enabled']);

		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_ENABLED', $this->log->operations);
		$entry = end($this->log->entries);
		$this->assertSame('mod', $entry[0]);
		$this->assertSame(self::FORUM_B, $entry[5]['forum_id']);
		$this->assertSame(20, $entry[5]['topic_id']);
	}

	public function test_enable_without_a_valid_form_key_is_denied()
	{
		$this->as_manager_b();
		$this->post_toggle(false);

		$this->assert_denied(function () {
			$this->controller->enable(2);
		});
		$this->assertFalse($this->campaigns->find_by_id(2)['campaign_enabled'], 'Enable ran without a valid form key');
	}

	public function test_enable_is_denied_to_a_manager_of_another_forum()
	{
		$this->as_manager_a();
		$this->post_toggle();

		$this->assert_denied(function () {
			$this->controller->enable(2);
		});
		$this->assertFalse($this->campaigns->find_by_id(2)['campaign_enabled']);
	}

	public function test_enable_is_denied_to_a_donations_only_user()
	{
		$this->as_donations_b();
		$this->post_toggle();

		$this->assert_denied(function () {
			$this->controller->enable(2);
		});
		$this->assertFalse($this->campaigns->find_by_id(2)['campaign_enabled']);
	}

	// ----------------------------------------------------------------- disable

	public function test_a_manager_disables_a_campaign_after_confirming()
	{
		// Campaign 1 is enabled, on topic 10 in forum A.
		$this->as_manager_a();
		$this->confirmed();
		$this->controller->disable(1);

		$this->assertFalse($this->campaigns->find_by_id(1)['campaign_enabled']);

		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_DISABLED', $this->log->operations);
		$entry = end($this->log->entries);
		$this->assertSame('mod', $entry[0]);
		$this->assertSame(self::FORUM_A, $entry[5]['forum_id']);
		$this->assertSame(10, $entry[5]['topic_id']);
	}

	public function test_disable_without_confirmation_changes_nothing()
	{
		$this->as_manager_a();
		$this->request();
		$this->swallow_dialog(function () {
			$this->controller->disable(1);
		});

		$this->assertTrue($this->campaigns->find_by_id(1)['campaign_enabled'], 'An unconfirmed disable changed the campaign');
		$this->assertNotContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_DISABLED', $this->log->operations);
	}

	public function test_disable_with_a_forged_confirmation_changes_nothing()
	{
		global $request, $user, $language;

		$this->as_manager_a();
		$request = new \phpbb_mock_request(array(), array(
			'confirm'		=> $language->lang('YES'),
			'confirm_uid'	=> $user->data['user_id'],
			'sess'			=> $user->session_id,
			'confirm_key'	=> 'the_wrong_key',
		));
		$this->rebuild();

		$this->swallow_dialog(function () {
			$this->controller->disable(1);
		});

		$this->assertTrue($this->campaigns->find_by_id(1)['campaign_enabled'], 'A forged confirmation disabled the campaign');
	}

	public function test_disable_is_denied_to_a_manager_of_another_forum()
	{
		$this->as_manager_b();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->controller->disable(1);
		});
		$this->assertTrue($this->campaigns->find_by_id(1)['campaign_enabled']);
	}

	public function test_disable_is_denied_to_a_donations_only_user()
	{
		$this->as_donations_a();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->controller->disable(1);
		});
		$this->assertTrue($this->campaigns->find_by_id(1)['campaign_enabled']);
	}

	// ------------------------------------------------------------------ delete

	public function test_a_manager_deletes_an_empty_campaign_after_confirming()
	{
		// Campaign 2 has no donations.
		$this->as_manager_b();
		$this->confirmed();
		$this->controller->delete(2);

		$this->assertNull($this->campaigns->find_by_id(2), 'The empty campaign was not deleted');
		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED', $this->log->operations);
		$this->assertSame(self::FORUM_B, end($this->log->entries)[5]['forum_id']);
	}

	public function test_delete_without_confirmation_changes_nothing()
	{
		$this->as_manager_b();
		$this->request();
		$this->swallow_dialog(function () {
			$this->controller->delete(2);
		});

		$this->assertNotNull($this->campaigns->find_by_id(2), 'An unconfirmed delete removed the campaign');
	}

	public function test_deleting_a_non_empty_campaign_is_refused_and_keeps_the_campaign()
	{
		// Campaign 1 has a donation.
		$this->as_manager_a();
		$this->confirmed();
		$this->controller->delete(1);

		$this->assertNotNull($this->campaigns->find_by_id(1), 'A non-empty campaign was hard-deleted from the frontend');
		$this->assertNotNull($this->helper->message);
		$this->assertStringContainsString('cannot be deleted here', $this->helper->message);
		$this->assertNotContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED', $this->log->operations);
	}

	public function test_delete_is_denied_to_a_manager_of_another_forum()
	{
		$this->as_manager_a();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->controller->delete(2);
		});
		$this->assertNotNull($this->campaigns->find_by_id(2));
	}

	public function test_delete_is_denied_to_a_donations_only_user()
	{
		$this->as_donations_b();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->controller->delete(2);
		});
		$this->assertNotNull($this->campaigns->find_by_id(2), 'A donations-only user must never delete a campaign');
	}

	public function test_delete_of_an_unknown_campaign_is_denied()
	{
		$this->as_admin();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->controller->delete(99999);
		});
	}
}
