<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\controller;

/**
 * The frontend donation controller: add and edit confirmed donations.
 *
 * Managing donations requires m_donationcampaigns_donations (or the admin
 * override) in the campaign's current forum — deliberately NOT the shell-manage
 * permission, because donations expose donor names and private donor identities.
 * These tests assert that separation across the whole actor x forum matrix, that
 * the total is recomputed by the unchanged service on every write, that each
 * write files a scoped moderator-log entry, and that a refusal reveals nothing.
 *
 * Fixture (from controller_test_case): campaign 1 lives on topic 10 in forum A
 * and has one donation (id 1, 1000 minor units, "Donor"); campaign 2 lives on
 * topic 20 in forum B and has none.
 */
class donation_controller_test extends controller_test_case
{
	// Actors, by grant map (forum A = 2, forum B = 3).

	protected function as_admin()
	{
		$this->as_actor(array('a_donationcampaigns' => true));
	}

	protected function as_manage_a()
	{
		$this->as_actor(array('m_donationcampaigns_manage' => array(self::FORUM_A)));
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
	 * A valid add/edit submission.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function donation_form(array $overrides = array())
	{
		return array_merge(array(
			'donation_amount'	=> '5.00',
			'donor_name'		=> 'Alice',
			'donation_time'		=> '2026-01-15',
			'donation_public'	=> 1,
		), $overrides);
	}

	/**
	 * @param int $campaign_id
	 * @return int
	 */
	protected function collected($campaign_id)
	{
		return (int) $this->campaigns->find_by_id($campaign_id)['collected_amount'];
	}

	// ---------------------------------------------------------------- add: behaviour

	public function test_a_donations_holder_records_a_confirmed_donation()
	{
		$this->as_donations_a();
		$before = $this->donations->count_by_campaign(1);

		$this->post_donation($this->donation_form());
		$this->donation_controller->add(1);

		$this->assertSame($before + 1, $this->donations->count_by_campaign(1), 'The donation was not persisted');
		$this->assertNotNull($this->helper->message, 'The saved message was not shown');
	}

	public function test_recording_a_donation_recomputes_the_campaign_total()
	{
		$this->as_donations_a();

		// Campaign 1 seeds 1000 collected from its one donation. A 500 receipt
		// makes the total 1500, computed by the service, not by adding in the view.
		$this->post_donation($this->donation_form(array('donation_amount' => '5.00')));
		$this->donation_controller->add(1);

		$this->assertSame(1500, $this->collected(1));
	}

	public function test_recording_a_donation_is_logged_to_the_moderator_log_with_context()
	{
		$this->as_donations_a();

		$this->post_donation($this->donation_form(array('donation_amount' => '5.00', 'donor_name' => 'Alice')));
		$this->donation_controller->add(1);

		$entry = end($this->log->entries);
		$this->assertSame('mod', $entry[0], 'Frontend donation actions log to the moderator log');
		$this->assertSame('LOG_DONATIONCAMPAIGNS_DONATION_ADDED', $entry[3]);
		$this->assertSame(self::FORUM_A, $entry[5]['forum_id']);
		$this->assertSame(10, $entry[5]['topic_id']);
		// The amount is formatted from stored minor units and the donor name is the
		// message argument; both are present for the log viewer.
		$this->assertStringContainsString('Alice', $entry[5][1]);
	}

	public function test_add_renders_the_form_on_a_get()
	{
		$this->as_donations_a();
		$this->request();

		$this->donation_controller->add(1);

		$this->assertSame('donationcampaigns_donation_form.html', $this->helper->rendered['template']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
	}

	public function test_add_parses_a_comma_decimal_amount_without_float()
	{
		$this->as_donations_a();

		// 12,50 in European input is 1250 minor units. Added to the seeded 1000,
		// the recomputed total is 2250 — exact integer arithmetic, no float.
		$this->post_donation($this->donation_form(array('donation_amount' => '12,50')));
		$this->donation_controller->add(1);

		$this->assertSame(2250, $this->collected(1));
	}

	public function test_add_records_an_anonymous_donation_and_labels_it_in_the_log()
	{
		$this->as_donations_a();
		$before = $this->donations->count_by_campaign(1);

		// A blank donor name is a real donation from someone who asked not to be
		// named — it must still be recorded and counted, and labelled in the log.
		$this->post_donation($this->donation_form(array('donor_name' => '')));
		$this->donation_controller->add(1);

		$this->assertSame($before + 1, $this->donations->count_by_campaign(1));

		$entry = end($this->log->entries);
		$this->assertSame('Anonymous', $entry[5][1]);
	}

	public function test_add_rejects_an_invalid_amount_and_keeps_the_entered_values()
	{
		$this->as_donations_a();
		$before = $this->donations->count_by_campaign(1);

		$this->post_donation($this->donation_form(array('donation_amount' => 'not-a-number', 'donor_name' => 'Keep me')));
		$this->donation_controller->add(1);

		$this->assertSame('donationcampaigns_donation_form.html', $this->helper->rendered['template']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ERROR']);
		$this->assertSame('Keep me', $this->template->vars['DONATIONCAMPAIGNS_DONOR_NAME']);
		$this->assertSame($before, $this->donations->count_by_campaign(1), 'Nothing may be written on an invalid amount');
	}

	public function test_add_rejects_an_invalid_date_and_writes_nothing()
	{
		$this->as_donations_a();
		$before = $this->donations->count_by_campaign(1);

		$this->post_donation($this->donation_form(array('donation_time' => 'yesterday')));
		$this->donation_controller->add(1);

		$this->assertSame('donationcampaigns_donation_form.html', $this->helper->rendered['template']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ERROR']);
		$this->assertSame($before, $this->donations->count_by_campaign(1));
	}

	public function test_add_fixes_the_campaign_from_the_url_ignoring_a_posted_campaign_id()
	{
		$this->as_actor(array('m_donationcampaigns_donations' => array(self::FORUM_A, self::FORUM_B)));

		// A crafted campaign_id in the body must not redirect the donation to
		// another campaign; the URL's campaign 1 is the only anchor.
		$this->post_donation($this->donation_form(array('campaign_id' => 2)));
		$this->donation_controller->add(1);

		$this->assertSame(0, $this->donations->count_by_campaign(2), 'The donation must not land on the posted campaign');
		$this->assertSame(2, $this->donations->count_by_campaign(1));
	}

	// ---------------------------------------------------------------- add: authorisation

	public function test_add_admin_may_record_a_donation()
	{
		$this->as_admin();
		$before = $this->donations->count_by_campaign(1);

		$this->post_donation($this->donation_form());
		$this->donation_controller->add(1);

		$this->assertSame($before + 1, $this->donations->count_by_campaign(1));
	}

	public function test_add_is_denied_to_a_manage_only_holder()
	{
		$this->as_manage_a();
		$before = $this->donations->count_by_campaign(1);

		$this->post_donation($this->donation_form());
		$this->assert_denied(function () {
			$this->donation_controller->add(1);
		});

		$this->assertSame($before, $this->donations->count_by_campaign(1));
	}

	public function test_add_is_denied_to_a_donations_holder_of_another_forum()
	{
		$this->as_donations_b();
		$before = $this->donations->count_by_campaign(1);

		$this->post_donation($this->donation_form());
		$this->assert_denied(function () {
			$this->donation_controller->add(1);
		});

		$this->assertSame($before, $this->donations->count_by_campaign(1));
	}

	public function test_add_is_denied_to_a_user_with_no_permission()
	{
		$this->as_nobody();

		$this->post_donation($this->donation_form());
		$this->assert_denied(function () {
			$this->donation_controller->add(1);
		});
	}

	public function test_add_without_a_valid_form_key_is_denied_and_writes_nothing()
	{
		$this->as_donations_a();
		$before = $this->donations->count_by_campaign(1);

		$this->post_donation($this->donation_form(), false);
		$this->assert_denied(function () {
			$this->donation_controller->add(1);
		});

		$this->assertSame($before, $this->donations->count_by_campaign(1));
	}

	public function test_add_on_a_missing_campaign_is_denied()
	{
		$this->as_donations_a();

		$this->post_donation($this->donation_form());
		$this->assert_denied(function () {
			$this->donation_controller->add(999);
		});
	}

	// ---------------------------------------------------------------- edit: behaviour

	public function test_a_donations_holder_edits_a_donation()
	{
		$this->as_donations_a();

		$this->post_donation($this->donation_form(array('donation_amount' => '9.99', 'donor_name' => 'Bob')));
		$this->donation_controller->edit(1);

		$donation = $this->donations->find_by_id(1);
		$this->assertSame(999, $donation['donation_amount']);
		$this->assertSame('Bob', $donation['donor_name']);
		// Campaign 1's only donation is now 999, so the recomputed total is 999.
		$this->assertSame(999, $this->collected(1));
	}

	public function test_edit_renders_the_form_with_stored_values_on_a_get()
	{
		$this->as_donations_a();
		$this->request();

		$this->donation_controller->edit(1);

		$this->assertSame('donationcampaigns_donation_form.html', $this->helper->rendered['template']);
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertSame('Donor', $this->template->vars['DONATIONCAMPAIGNS_DONOR_NAME']);
	}

	public function test_edit_is_logged_to_the_moderator_log()
	{
		$this->as_donations_a();

		$this->post_donation($this->donation_form(array('donation_amount' => '9.99', 'donor_name' => 'Bob')));
		$this->donation_controller->edit(1);

		$entry = end($this->log->entries);
		$this->assertSame('mod', $entry[0]);
		$this->assertSame('LOG_DONATIONCAMPAIGNS_DONATION_EDITED', $entry[3]);
		$this->assertSame(self::FORUM_A, $entry[5]['forum_id']);
		$this->assertSame(10, $entry[5]['topic_id']);
	}

	public function test_edit_cannot_move_a_donation_to_another_campaign()
	{
		$this->as_donations_a();

		// campaign_id is not a writable field on a donation; a posted one is ignored.
		$this->post_donation($this->donation_form(array('campaign_id' => 2)));
		$this->donation_controller->edit(1);

		$this->assertSame(1, $this->donations->find_by_id(1)['campaign_id']);
	}

	// ---------------------------------------------------------------- edit: authorisation

	public function test_edit_admin_may_edit_a_donation()
	{
		$this->as_admin();

		$this->post_donation($this->donation_form(array('donation_amount' => '9.99')));
		$this->donation_controller->edit(1);

		$this->assertSame(999, $this->donations->find_by_id(1)['donation_amount']);
	}

	public function test_edit_is_denied_to_a_manage_only_holder()
	{
		$this->as_manage_a();

		$this->post_donation($this->donation_form(array('donation_amount' => '9.99')));
		$this->assert_denied(function () {
			$this->donation_controller->edit(1);
		});

		$this->assertSame(1000, $this->donations->find_by_id(1)['donation_amount'], 'Nothing may be written');
	}

	public function test_edit_is_denied_to_a_donations_holder_of_another_forum()
	{
		$this->as_donations_b();

		$this->post_donation($this->donation_form(array('donation_amount' => '9.99')));
		$this->assert_denied(function () {
			$this->donation_controller->edit(1);
		});

		$this->assertSame(1000, $this->donations->find_by_id(1)['donation_amount']);
	}

	public function test_edit_is_denied_to_a_user_with_no_permission()
	{
		$this->as_nobody();

		$this->post_donation($this->donation_form());
		$this->assert_denied(function () {
			$this->donation_controller->edit(1);
		});
	}

	public function test_edit_without_a_valid_form_key_is_denied_and_writes_nothing()
	{
		$this->as_donations_a();

		$this->post_donation($this->donation_form(array('donation_amount' => '9.99')), false);
		$this->assert_denied(function () {
			$this->donation_controller->edit(1);
		});

		$this->assertSame(1000, $this->donations->find_by_id(1)['donation_amount']);
	}

	public function test_edit_on_a_missing_donation_is_denied()
	{
		$this->as_donations_a();

		$this->post_donation($this->donation_form());
		$this->assert_denied(function () {
			$this->donation_controller->edit(999);
		});
	}

	// ---------------------------------------------------------------- delete

	public function test_a_donations_holder_deletes_a_donation_after_confirming()
	{
		$this->as_donations_a();
		$this->confirmed();

		$this->donation_controller->delete(1);

		$this->assertNull($this->donations->find_by_id(1), 'The donation was not deleted');
		$this->assertContains('LOG_DONATIONCAMPAIGNS_DONATION_DELETED', $this->log->operations);
	}

	public function test_deleting_a_donation_recomputes_the_campaign_total()
	{
		$this->as_donations_a();
		$this->confirmed();

		// Campaign 1's only donation is removed, so the recomputed total is 0.
		$this->donation_controller->delete(1);

		$this->assertSame(0, $this->collected(1));
	}

	public function test_deleting_a_donation_is_logged_to_the_moderator_log_with_context()
	{
		$this->as_donations_a();
		$this->confirmed();

		$this->donation_controller->delete(1);

		$entry = end($this->log->entries);
		$this->assertSame('mod', $entry[0]);
		$this->assertSame('LOG_DONATIONCAMPAIGNS_DONATION_DELETED', $entry[3]);
		$this->assertSame(self::FORUM_A, $entry[5]['forum_id']);
		$this->assertSame(10, $entry[5]['topic_id']);
	}

	public function test_delete_without_confirmation_changes_nothing()
	{
		$this->as_donations_a();
		$this->request();

		$this->swallow_dialog(function () {
			$this->donation_controller->delete(1);
		});

		$this->assertNotNull($this->donations->find_by_id(1), 'An unconfirmed delete removed the donation');
		$this->assertNotContains('LOG_DONATIONCAMPAIGNS_DONATION_DELETED', $this->log->operations);
	}

	public function test_delete_with_a_forged_confirmation_deletes_nothing()
	{
		$this->as_donations_a();

		// A confirmation whose key does not match the session is not a confirmation:
		// confirm_box(true) returns false and only the dialog is offered.
		global $request;
		$request = new \phpbb_mock_request(array(), array(
			'confirm'		=> 'YES',
			'confirm_key'	=> 'a_forged_key',
		));
		$this->rebuild();

		$this->swallow_dialog(function () {
			$this->donation_controller->delete(1);
		});

		$this->assertNotNull($this->donations->find_by_id(1), 'A forged confirmation deleted the donation');
	}

	public function test_delete_admin_may_delete_a_donation()
	{
		$this->as_admin();
		$this->confirmed();

		$this->donation_controller->delete(1);

		$this->assertNull($this->donations->find_by_id(1));
	}

	public function test_delete_is_denied_to_a_manage_only_holder()
	{
		$this->as_manage_a();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->donation_controller->delete(1);
		});

		$this->assertNotNull($this->donations->find_by_id(1), 'Nothing may be deleted');
	}

	public function test_delete_is_denied_to_a_donations_holder_of_another_forum()
	{
		$this->as_donations_b();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->donation_controller->delete(1);
		});

		$this->assertNotNull($this->donations->find_by_id(1));
	}

	public function test_delete_is_denied_to_a_user_with_no_permission()
	{
		$this->as_nobody();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->donation_controller->delete(1);
		});

		$this->assertNotNull($this->donations->find_by_id(1));
	}

	public function test_delete_on_a_missing_donation_is_denied()
	{
		$this->as_donations_a();
		$this->confirmed();

		$this->assert_denied(function () {
			$this->donation_controller->delete(999);
		});
	}

	// ---------------------------------------------------------------- log escaping

	/**
	 * REGRESSION. The mod-log viewer renders entries as raw HTML (Twig
	 * autoescaping is off board-wide), so a donor name carrying markup would
	 * execute there. Every value interpolated into a log entry is escaped at the
	 * point it is put in, exactly like every other output.
	 */
	public function test_a_donor_name_in_an_add_log_entry_is_escaped()
	{
		$this->as_donations_a();

		$this->post_donation($this->donation_form(array('donor_name' => '<script>alert(1)</script>')));
		$this->donation_controller->add(1);

		$data = implode(' ', (array) end($this->log->entries)[5]);

		$this->assertStringNotContainsString('<script>', $data, 'A log entry carried unescaped markup');
		$this->assertStringContainsString('&lt;script&gt;', $data);
	}

	// ---------------------------------------------------------------- web-root links

	/**
	 * REGRESSION. Like the campaign controller, this runs under app.php/... so a
	 * bare "viewtopic.php" 404s; the topic link is built from the web root.
	 */
	public function test_the_form_back_link_is_built_from_the_web_root()
	{
		$this->as_donations_a();
		$this->request();

		$this->donation_controller->add(1);

		$back = $this->template->vars['U_BACK'];
		$this->assertStringStartsWith(fake_path_helper::WEB_ROOT, $back, 'The topic link is not resolved from the web root');
		$this->assertStringContainsString('viewtopic.', $back);
		$this->assertStringContainsString('t=10', $back);
	}

	public function test_a_donor_name_in_a_delete_log_entry_is_escaped()
	{
		$this->donations->update(1, array('donor_name' => '<script>alert(1)</script>'));

		$this->as_donations_a();
		$this->confirmed();
		$this->donation_controller->delete(1);

		$data = implode(' ', (array) end($this->log->entries)[5]);

		$this->assertStringNotContainsString('<script>', $data);
		$this->assertStringContainsString('&lt;script&gt;', $data);
	}
}
