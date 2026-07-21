<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey (c)
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * The ACP campaign form reached from a topic.
 *
 * THE INVARIANT UNDER TEST: given a topic, the module decides for itself
 * whether a campaign exists and dispatches accordingly. It never believes an
 * action verb in the URL.
 *
 * That is not a convenience. The topic page is rendered once and may be
 * clicked much later, so any verb it could have carried is a claim about the
 * past. Resolving at request time is what makes stale pages, disabled
 * campaigns, concurrent creation and hand-edited URLs all behave — not five
 * separate guards, one rule.
 *
 * Fixture (from campaign_acp_test_case): topic 10 has enabled campaign 1,
 * topic 20 has DISABLED campaign 2, topic 30 has none.
 */
class campaign_topic_context_test extends campaign_acp_test_case
{
	/**
	 * Open the campaign mode with a topic in the URL and nothing else.
	 *
	 * @param mixed $topic_id
	 * @param array $extra Anything a crafted URL might also carry
	 * @return string The message raised, if any
	 */
	protected function open_topic($topic_id, array $extra = array())
	{
		$this->request(array_merge(array('t' => $topic_id), $extra));

		return $this->run_and_catch();
	}

	// ------------------------------------------------------------- dispatch

	public function test_a_topic_without_a_campaign_opens_the_create_form()
	{
		$this->open_topic(30);

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
	}

	public function test_a_topic_with_an_enabled_campaign_opens_its_edit_form()
	{
		$this->open_topic(10);

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	/**
	 * A disabled campaign still exists, so the topic must edit it rather than
	 * offer to create a second one. Getting this wrong produces a create form
	 * that then refuses itself with "that topic already has a campaign".
	 */
	public function test_a_topic_with_a_disabled_campaign_opens_its_edit_form()
	{
		$this->open_topic(20);

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertSame('Archive fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	// -------------------------------------------------- the verb is ignored

	/**
	 * The decisive test. A topic in the URL means the module resolves; an
	 * action alongside it is noise from a crafted or stale link.
	 */
	public function test_an_action_verb_cannot_turn_a_topic_view_into_a_delete()
	{
		$before = $this->campaigns->count_all();

		$this->open_topic(10, array('action' => 'delete', 'campaign_id' => 1, 'confirm' => 'Yes'));

		$this->assertSame($before, $this->campaigns->count_all(), 'A crafted action deleted a campaign');
		$this->assertNotNull($this->campaigns->find_by_id(1));
	}

	public function test_an_add_verb_cannot_force_a_create_form_for_a_taken_topic()
	{
		// Topic 10 is taken. The verb says otherwise; the database wins.
		$this->open_topic(10, array('action' => 'add'));

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
	}

	public function test_an_edit_verb_cannot_force_an_edit_form_for_a_free_topic()
	{
		$this->open_topic(30, array('action' => 'edit', 'campaign_id' => 1));

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
	}

	/**
	 * A campaign_id alongside a topic must not select the campaign. The topic
	 * is the key; anything else would let one topic's page edit another
	 * topic's campaign.
	 */
	public function test_a_campaign_id_alongside_a_topic_is_not_what_gets_edited()
	{
		$this->open_topic(10, array('campaign_id' => 2));

		// Campaign 1 belongs to topic 10; campaign 2 belongs to topic 20.
		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	// ------------------------------------------------------ untrusted input

	/**
	 * Anything that is not a positive integer is not a topic context at all.
	 * The mode falls back to the campaign list, which is the page the
	 * administrator asked for; it is not an error and must not destroy
	 * anything. Casting is what makes this safe — a value shaped like an
	 * injection becomes 0 here, before it can reach a query.
	 *
	 * @dataProvider topic_ids_that_are_not_a_context
	 */
	public function test_a_topic_id_that_is_not_one_falls_back_to_the_list($topic_id)
	{
		$before = $this->campaigns->count_all();

		$message = $this->open_topic($topic_id);

		$this->assertSame('', $message, 'A malformed topic id raised an error instead of listing');
		$this->assertArrayNotHasKey('S_DONATIONCAMPAIGNS_ADD', $this->template->vars, 'A form was rendered without a topic');
		$this->assertSame($before, $this->campaigns->count_all());
	}

	public function topic_ids_that_are_not_a_context()
	{
		return array(
			'zero'			=> array(0),
			'negative'		=> array(-1),
			'not a number'	=> array('abc'),
			'an array'		=> array(array(5)),
		);
	}

	/**
	 * A positive id that resolves to no topic IS a failure: the administrator
	 * followed a link to something that is gone.
	 *
	 * @dataProvider topic_ids_that_resolve_to_nothing
	 */
	public function test_a_topic_that_does_not_exist_is_refused($topic_id)
	{
		$before = $this->campaigns->count_all();

		$message = $this->open_topic($topic_id);

		$this->assertNotSame('', $message, 'A missing topic rendered a form');
		$this->assertSame($before, $this->campaigns->count_all());
	}

	public function topic_ids_that_resolve_to_nothing()
	{
		return array(
			'nonexistent'	=> array(99999),
			'far out'		=> array(2147483000),
		);
	}

	/**
	 * A shadow is the stub phpBB leaves when a topic is moved. viewtopic
	 * answers 404 for one, so a campaign attached to it could never be seen.
	 */
	public function test_a_shadow_topic_is_refused()
	{
		$this->db->sql_query('INSERT INTO phpbb_topics (topic_id, forum_id, topic_title, topic_moved_id) VALUES (77, 2, \'Moved away\', 10)');

		$message = $this->open_topic(77);

		$this->assertNotSame('', $message, 'A campaign was offered on a shadow topic');
	}

	// -------------------------------------------------- what the form shows

	public function test_the_create_form_names_the_topic_it_will_attach_to()
	{
		$this->open_topic(30);

		$this->assertSame('Legal costs topic', $this->template->vars['DONATIONCAMPAIGNS_TOPIC_TITLE']);
	}

	public function test_the_edit_form_names_the_topic_the_campaign_belongs_to()
	{
		$this->open_topic(10);

		$this->assertSame('Server replacement fund', $this->template->vars['DONATIONCAMPAIGNS_TOPIC_TITLE']);
	}

	public function test_the_form_links_to_the_topic_it_belongs_to()
	{
		$this->open_topic(30);

		$this->assertStringContainsString('t=30', $this->template->vars['U_DONATIONCAMPAIGNS_TOPIC']);
	}

	/**
	 * The topic is decided by the page the form was opened from. There is no
	 * field in which to name a different one — a field that does not exist
	 * cannot be tampered with.
	 */
	public function test_the_form_offers_no_editable_topic_field()
	{
		$this->open_topic(30);

		$this->assertArrayNotHasKey('DONATIONCAMPAIGNS_TOPIC_ID', $this->template->vars);
	}

	/**
	 * The form must post back into the topic context, or the resolution would
	 * have to be repeated from a verb — which is what this design removes.
	 */
	public function test_the_form_posts_back_into_the_topic_context()
	{
		$this->open_topic(30);

		$this->assertStringContainsString('t=30', $this->template->vars['U_ACTION']);
		$this->assertStringNotContainsString('action=', $this->template->vars['U_ACTION']);
	}

	// --------------------------------------------------------- submissions

	/**
	 * A complete, valid submission in topic context. The topic is NOT among
	 * the fields: it travels in the form action, never in the body.
	 *
	 * @param int $topic_id
	 * @param int $expected
	 * @param array $overrides
	 * @return string The message raised, if any
	 */
	protected function submit_topic($topic_id, $expected, array $overrides = array())
	{
		$this->post_form('donationcampaigns_campaign', array_merge(array(
			't'						=> $topic_id,
			'expected_campaign_id'	=> $expected,
			'campaign_title'		=> 'Legal fund',
			'campaign_desc'			=> 'Help us cover legal costs.',
			'target_amount'			=> '250.00',
			'external_url'			=> '',
			'external_link_text'	=> 'How to donate',
			'campaign_enabled'		=> 1,
			'show_donor_names'		=> 1,
			'show_donation_count'	=> 1,
			'submit'				=> 'Submit',
		), $overrides));

		return $this->run_and_catch();
	}

	public function test_a_valid_submission_creates_the_campaign_on_its_own_topic()
	{
		$this->submit_topic(30, 0);

		$created = $this->campaigns->find_by_topic_id(30);

		$this->assertNotNull($created);
		$this->assertSame('Legal fund', $created['campaign_title']);
	}

	/**
	 * The topic must come from the resolved context and nowhere else. A body
	 * field naming another topic is not validated, it is overwritten.
	 */
	public function test_a_topic_id_in_the_body_cannot_redirect_the_create()
	{
		$this->submit_topic(30, 0, array('topic_id' => 10));

		$this->assertNotNull($this->campaigns->find_by_topic_id(30), 'The campaign did not land on its own topic');
		// Topic 10 still has only its original campaign.
		$this->assertSame('Server fund', $this->campaigns->find_by_topic_id(10)['campaign_title']);
	}

	/**
	 * Editing derives the association from the stored campaign. A tampered t
	 * selects which campaign is edited, but can never move one.
	 */
	public function test_an_edit_cannot_reassign_a_campaign_to_another_topic()
	{
		$this->submit_topic(10, 1, array('topic_id' => 30, 'campaign_title' => 'Renamed'));

		$campaign = $this->campaigns->find_by_id(1);

		$this->assertSame(10, $campaign['topic_id'], 'A campaign was moved to another topic');
		$this->assertSame('Renamed', $campaign['campaign_title']);
		$this->assertNull($this->campaigns->find_by_topic_id(30), 'A campaign appeared on the tampered topic');
	}

	/**
	 * A rejected submission must come back still attached to its topic, or
	 * the administrator would have to start again from the topic page.
	 */
	public function test_a_validation_failure_keeps_the_topic_context()
	{
		$this->submit_topic(30, 0, array('campaign_title' => ''));

		$this->assertStringContainsString('t=30', $this->template->vars['U_ACTION']);
		$this->assertSame('Legal costs topic', $this->template->vars['DONATIONCAMPAIGNS_TOPIC_TITLE']);
		$this->assertNull($this->campaigns->find_by_topic_id(30), 'An invalid submission was stored');
	}

	// ------------------------------------------------ the expected-state guard

	/**
	 * The destructive case this guard exists for. Two tabs both opened the
	 * create form for topic 30; the first created a campaign. The second must
	 * NOT overwrite it with values its administrator typed for a campaign
	 * that did not exist when they started.
	 */
	public function test_a_lost_create_race_does_not_overwrite_the_winner()
	{
		// The other tab got there first.
		$this->campaign_service->create_campaign(array(
			'topic_id' => 30, 'campaign_title' => 'Created by someone else',
			'campaign_desc' => '', 'target_amount' => 5000,
			'external_url' => '', 'external_link_text' => '',
			'campaign_enabled' => 1, 'show_donor_names' => 1, 'show_donation_count' => 1,
		));

		$this->submit_topic(30, 0, array('campaign_title' => 'My stale attempt'));

		$this->assertSame(
			'Created by someone else',
			$this->campaigns->find_by_topic_id(30)['campaign_title'],
			'A stale create overwrote the campaign that won the race'
		);
	}

	public function test_a_lost_create_race_reports_what_happened()
	{
		$this->campaign_service->create_campaign(array(
			'topic_id' => 30, 'campaign_title' => 'Created by someone else',
			'campaign_desc' => '', 'target_amount' => 5000,
			'external_url' => '', 'external_link_text' => '',
			'campaign_enabled' => 1, 'show_donor_names' => 1, 'show_donation_count' => 1,
		));

		$this->submit_topic(30, 0);

		// Now editing the campaign that exists, and told so.
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertSame('Created by someone else', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertNotSame('', $this->template->vars['DONATIONCAMPAIGNS_NOTICE']);
	}

	/**
	 * The mirror case: the form was opened on an existing campaign, which was
	 * deleted before it was submitted. Nothing may be written, and the
	 * administrator is offered the create form instead of an error.
	 */
	public function test_a_submission_for_a_campaign_that_has_gone_writes_nothing()
	{
		$before = $this->campaigns->count_all();

		// Campaign 1 is on topic 10; delete it behind the form's back.
		$this->campaign_service->delete_campaign(1);

		$this->submit_topic(10, 1);

		$this->assertSame($before - 1, $this->campaigns->count_all(), 'A vanished campaign was recreated or restored');
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertNotSame('', $this->template->vars['DONATIONCAMPAIGNS_NOTICE']);
	}

	public function test_an_edit_whose_expectation_matches_is_saved()
	{
		$this->submit_topic(10, 1, array('campaign_title' => 'Renamed properly'));

		$this->assertSame('Renamed properly', $this->campaigns->find_by_id(1)['campaign_title']);
	}

	/**
	 * The expectation is untrusted input, but it can only ever DOWNGRADE a
	 * write to a re-render: the campaign acted on is always the resolved one.
	 */
	public function test_a_forged_expectation_cannot_write_to_another_campaign()
	{
		// Claim to be editing campaign 2 (topic 20) while in topic 10.
		$this->submit_topic(10, 2, array('campaign_title' => 'Injected'));

		$this->assertSame('Archive fund', $this->campaigns->find_by_id(2)['campaign_title'], 'Another campaign was written');
		$this->assertSame('Server fund', $this->campaigns->find_by_id(1)['campaign_title'], 'The resolved campaign was written on a mismatch');
	}

	// ------------------------------------------------- return navigation

	public function test_a_successful_create_offers_the_way_back_to_its_topic()
	{
		$message = $this->submit_topic(30, 0);

		$this->assertStringContainsString('viewtopic', $message);
		$this->assertStringContainsString('t=30', $message);
	}

	public function test_a_successful_edit_offers_the_way_back_to_its_topic()
	{
		$message = $this->submit_topic(10, 1, array('campaign_title' => 'Renamed'));

		$this->assertStringContainsString('t=10', $message);
	}

	/**
	 * The return target is built from the validated integer alone. There is
	 * no parameter by which another destination could be supplied, which is
	 * what keeps this off the open-redirect list.
	 */
	public function test_no_return_url_can_be_supplied_by_the_request()
	{
		$message = $this->submit_topic(30, 0, array(
			'redirect'	=> 'http://evil.example/steal',
			'return'	=> 'http://evil.example/steal',
			'u_action'	=> 'http://evil.example/steal',
		));

		$this->assertStringNotContainsString('evil.example', $message);
		$this->assertStringContainsString('t=30', $message);
	}

	/**
	 * REGRESSION. Every one of these links is built inside adm/, where a bare
	 * "viewtopic.php" resolves against the ACP directory and produces
	 * /adm/viewtopic.php — a 404. It looked right in every unit test, because
	 * asserting "contains t=30" passes either way, and only showed up when the
	 * link was actually followed on a running board.
	 *
	 * @dataProvider links_that_leave_the_acp
	 */
	public function test_a_link_out_of_the_acp_is_not_relative_to_the_adm_directory($var)
	{
		global $phpbb_root_path;

		$this->open_topic(30);

		$url = $this->template->vars[$var];

		$this->assertStringContainsString($phpbb_root_path, $url, "{$var} is missing the board root path");
		$this->assertStringStartsNotWith('viewtopic.', $url, "{$var} resolves against adm/");
	}

	public function links_that_leave_the_acp()
	{
		return array(
			'the read-only topic'	=> array('U_DONATIONCAMPAIGNS_TOPIC'),
			'the back link'			=> array('U_BACK'),
		);
	}

	public function test_the_saved_message_link_is_not_relative_to_the_adm_directory()
	{
		global $phpbb_root_path;

		$message = $this->submit_topic(30, 0);

		$this->assertStringContainsString($phpbb_root_path . 'viewtopic.', $message);
	}

	public function test_the_back_link_on_the_form_goes_to_the_topic()
	{
		$this->open_topic(30);

		$this->assertStringContainsString('viewtopic', $this->template->vars['U_BACK']);
		$this->assertStringContainsString('t=30', $this->template->vars['U_BACK']);
	}

	public function test_a_submission_without_a_valid_form_key_is_refused()
	{
		$this->post_form('donationcampaigns_campaign', array(
			't'						=> 30,
			'expected_campaign_id'	=> 0,
			'campaign_title'		=> 'Legal fund',
			'target_amount'			=> '250.00',
			'external_link_text'	=> 'How to donate',
			'submit'				=> 'Submit',
		), false);

		$message = $this->run_and_catch();

		$this->assertNotSame('', $message);
		$this->assertNull($this->campaigns->find_by_topic_id(30));
	}
}
