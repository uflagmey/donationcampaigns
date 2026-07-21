<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * The ACP campaign add and edit form.
 *
 * The module coordinates only: it reads a form, hands it to campaign_service
 * and renders. These tests cover what only exists at this layer — the form
 * key, what reaches the template, what comes back after a rejection, and that
 * no derived value can be submitted.
 */
class campaign_form_test extends campaign_acp_test_case
{
	/**
	 * A complete, valid submission.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function form(array $overrides = array())
	{
		return array_merge(array(
			'topic_id'				=> 30,
			'campaign_title'		=> 'Legal fund',
			'campaign_desc'			=> 'Help us cover legal costs.',
			'target_amount'			=> '250.00',
			'external_url'			=> '',
			'external_link_text'	=> 'How to donate',
			'campaign_enabled'		=> 1,
			'show_donor_names'		=> 1,
			'show_donation_count'	=> 1,
		), $overrides);
	}

	/** The seeded topic with no campaign, where creation tests go. */
	const FREE_TOPIC = 30;

	/**
	 * Open the form in create or edit mode.
	 *
	 * There is no longer an "add" action to request: a campaign is created
	 * only from the topic it belongs to, so creating means opening a free
	 * topic and letting the module resolve. Editing from the campaign list is
	 * unchanged. Both routes are kept behind this one helper so the tests
	 * below stay about the form rather than about how it was reached.
	 *
	 * @param string $action add or edit
	 * @param int $campaign_id
	 * @return void
	 */
	protected function open($action, $campaign_id = 0)
	{
		$this->request(($action === 'add')
			? array('t' => self::FREE_TOPIC)
			: array('action' => 'edit', 'campaign_id' => $campaign_id));

		$this->module->main(1, 'campaigns');
	}

	/**
	 * @param string $action
	 * @param array $values
	 * @param int $campaign_id
	 * @param bool $valid_token
	 * @return string The message raised, if any
	 */
	protected function submit($action, array $values, $campaign_id = 0, $valid_token = true)
	{
		$context = ($action === 'add')
			// Creating: topic context, expecting no campaign there yet.
			? array('t' => self::FREE_TOPIC, 'expected_campaign_id' => 0)
			// Editing from the campaign list, exactly as before.
			: array('action' => 'edit', 'campaign_id' => $campaign_id);

		$this->post_form('donationcampaigns_campaign', array_merge($context, array(
			'submit'	=> 'Submit',
		), $values), $valid_token);

		return $this->run_and_catch();
	}

	// ------------------------------------------------------------- rendering

	public function test_the_add_form_renders()
	{
		$this->open('add');

		$this->assertSame('acp_donationcampaigns_campaign_edit', $this->module->tpl_name);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
	}

	public function test_the_add_form_starts_empty_with_sane_defaults()
	{
		$this->open('add');

		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_DESC']);
		// The topic is fixed by the page this was opened from, and named
		// rather than numbered.
		$this->assertSame('Legal costs topic', $this->template->vars['DONATIONCAMPAIGNS_TOPIC_TITLE']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_ENABLED']);
	}

	public function test_the_edit_form_loads_the_existing_campaign()
	{
		$this->open('edit', 1);

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	/**
	 * A campaign belongs to its topic for life. The edit form names that
	 * topic so the administrator can see which one they are changing, but
	 * offers no way to point the campaign somewhere else.
	 */
	public function test_the_edit_form_names_the_topic_read_only()
	{
		$this->open('edit', 1);

		$this->assertSame('Server replacement fund', $this->template->vars['DONATIONCAMPAIGNS_TOPIC_TITLE']);
		$this->assertStringContainsString('t=10', $this->template->vars['U_DONATIONCAMPAIGNS_TOPIC']);
	}

	public function test_the_edit_form_offers_no_editable_topic_field()
	{
		$this->open('edit', 1);

		$this->assertArrayNotHasKey('DONATIONCAMPAIGNS_TOPIC_ID', $this->template->vars);
	}

	/**
	 * Reached from the campaign list, it must still go back to the list.
	 * Only a form opened from a topic returns to that topic.
	 */
	public function test_an_edit_reached_from_the_list_returns_to_the_list()
	{
		$this->open('edit', 1);

		$this->assertStringNotContainsString('viewtopic', $this->template->vars['U_BACK']);
	}

	/**
	 * The amount field gave no clue which currency it meant. The board already
	 * knows, so the form says — read from the existing setting, not stored a
	 * second time here.
	 */
	public function test_the_form_names_the_configured_currency()
	{
		$this->open('add');

		$this->assertSame('€', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_SYMBOL']);
	}

	public function test_the_currency_follows_the_board_setting()
	{
		$this->config->set('donationcampaigns_currency_symbol', 'CHF');

		$this->open('add');

		$this->assertSame('CHF', $this->template->vars['DONATIONCAMPAIGNS_CURRENCY_SYMBOL']);
	}

	/**
	 * The currency is a label beside the field, not a value in it. Storage and
	 * parsing are untouched: the amount still arrives as the administrator
	 * typed it.
	 */
	public function test_the_currency_is_not_mixed_into_the_amount()
	{
		$this->submit('add', $this->form(array('target_amount' => '250,00')));

		$created = $this->campaigns->find_by_topic_id(30);

		$this->assertSame(25000, $created['target_amount']);
		$this->assertStringNotContainsString('€', (string) $created['target_amount']);
	}

	public function test_the_edit_form_formats_the_target_for_display()
	{
		$this->open('edit', 1);

		// 10000 minor units at exponent 2.
		$this->assertSame('100.00', $this->template->vars['DONATIONCAMPAIGNS_TARGET_AMOUNT']);
	}

	/**
	 * The collected total is shown so an administrator can see it, but it is
	 * a derived value and must never be offered as an input.
	 */
	public function test_the_collected_total_is_shown_read_only()
	{
		$this->open('edit', 1);

		$this->assertSame('25.00', $this->template->vars['DONATIONCAMPAIGNS_COLLECTED_AMOUNT']);
	}

	public function test_editing_an_unknown_campaign_is_refused()
	{
		$this->request(array('action' => 'edit', 'campaign_id' => 99999));

		$this->assertNotEmpty($this->run_and_catch());
		$this->assertSame(2, $this->campaigns->count_all());
	}

	public function test_the_form_shows_the_disabled_state_of_a_disabled_campaign()
	{
		$this->open('edit', 2);

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ENABLED']);
	}

	// -------------------------------------------------------------- creation

	public function test_a_valid_submission_creates_the_campaign()
	{
		$this->submit('add', $this->form());

		$created = $this->campaigns->find_by_topic_id(30);

		$this->assertNotNull($created);
		$this->assertSame('Legal fund', $created['campaign_title']);
		$this->assertSame(25000, $created['target_amount']);
	}

	/**
	 * The money path, end to end through the form. A comma decimal is what a
	 * German administrator types, and the naive implementation loses a cent.
	 */
	public function test_a_comma_decimal_target_is_parsed_without_a_float()
	{
		$this->submit('add', $this->form(array('target_amount' => '8,70')));

		$this->assertSame(870, $this->campaigns->find_by_topic_id(30)['target_amount']);
	}

	public function test_a_target_with_no_fractional_part_is_accepted()
	{
		$this->submit('add', $this->form(array('target_amount' => '250')));

		$this->assertSame(25000, $this->campaigns->find_by_topic_id(30)['target_amount']);
	}

	public function test_a_new_campaign_starts_with_a_zero_total()
	{
		$this->submit('add', $this->form());

		$this->assertSame(0, $this->campaigns->find_by_topic_id(30)['collected_amount']);
	}

	public function test_a_creation_is_logged()
	{
		$this->submit('add', $this->form());

		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED', $this->log->operations);
	}

	// ---------------------------------------------------------------- update

	public function test_a_valid_edit_updates_the_campaign()
	{
		$this->submit('edit', $this->form(array(
			'topic_id'			=> 10,
			'campaign_title'	=> 'Renamed fund',
			'target_amount'		=> '500.00',
		)), 1);

		$updated = $this->campaigns->find_by_id(1);

		$this->assertSame('Renamed fund', $updated['campaign_title']);
		$this->assertSame(50000, $updated['target_amount']);
	}

	public function test_an_edit_does_not_disturb_the_collected_total()
	{
		$this->submit('edit', $this->form(array('topic_id' => 10)), 1);

		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	public function test_an_edit_keeping_its_own_topic_is_not_a_duplicate()
	{
		$message = $this->submit('edit', $this->form(array('topic_id' => 10)), 1);

		$this->assertStringNotContainsStringIgnoringCase('already has', $message);
		$this->assertSame('Legal fund', $this->campaigns->find_by_id(1)['campaign_title']);
	}

	public function test_an_edit_is_logged()
	{
		$this->submit('edit', $this->form(array('topic_id' => 10)), 1);

		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED', $this->log->operations);
	}

	// ------------------------------------------------------------ validation

	/**
	 * Creating a second campaign on a taken topic is no longer something the
	 * ACP can be asked to do: the topic decides the mode, so a taken topic
	 * resolves to its existing campaign instead of a create form. The rule
	 * itself still lives in campaign_service and is tested there.
	 */
	public function test_a_taken_topic_cannot_be_asked_to_create_a_second_campaign()
	{
		$before = $this->campaigns->count_all();

		// Topic 10 already has campaign 1.
		$this->request(array('t' => 10));
		$this->run_and_catch();

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ADD']);
		$this->assertSame($before, $this->campaigns->count_all());
	}

	public function test_a_nonexistent_topic_is_refused_before_a_form_is_drawn()
	{
		$this->request(array('t' => 99999));
		$message = $this->run_and_catch();

		$this->assertNotSame('', $message);
		$this->assertNull($this->campaigns->find_by_topic_id(99999));
	}

	public function test_an_empty_title_is_rejected()
	{
		$this->submit('add', $this->form(array('campaign_title' => '   ')));

		$this->assertNull($this->campaigns->find_by_topic_id(30));
	}

	public function invalid_target_data()
	{
		return array(
			'zero'			=> array('0'),
			'negative'		=> array('-5'),
			'words'			=> array('lots'),
			'empty'			=> array(''),
			'too precise'	=> array('10.001'),
			'thousands sep'	=> array('1 000.00'),
		);
	}

	/**
	 * @dataProvider invalid_target_data
	 */
	public function test_an_invalid_target_is_rejected($target)
	{
		$this->submit('add', $this->form(array('target_amount' => $target)));

		$this->assertNull($this->campaigns->find_by_topic_id(30), "Target '{$target}' was accepted");
		$this->assertNotEmpty($this->errors());
	}

	public function unsafe_url_data()
	{
		return array(
			'javascript'	=> array('javascript:alert(1)'),
			'mixed case'	=> array('JaVaScRiPt:alert(1)'),
			'data'			=> array('data:text/html;base64,PHN2Zz4='),
			'vbscript'		=> array('vbscript:msgbox(1)'),
			'protocol rel'	=> array('//evil.example'),
			'schemeless'	=> array('evil.example/donate'),
		);
	}

	/**
	 * @dataProvider unsafe_url_data
	 */
	public function test_an_unsafe_external_url_is_rejected($url)
	{
		$this->submit('add', $this->form(array('external_url' => $url)));

		$this->assertNull($this->campaigns->find_by_topic_id(30), "URL '{$url}' was accepted");
	}

	public function test_a_safe_external_url_is_stored()
	{
		$this->submit('add', $this->form(array('external_url' => 'https://example.org/donate', 'external_link_text' => 'How to donate')));

		$this->assertSame('https://example.org/donate', $this->campaigns->find_by_topic_id(30)['external_url']);
	}

	public function test_an_empty_external_url_is_allowed()
	{
		$this->submit('add', $this->form(array('external_url' => '')));

		$this->assertNotNull($this->campaigns->find_by_topic_id(30));
	}

	public function test_every_failure_is_reported_at_once()
	{
		$this->submit('add', $this->form(array(
			'campaign_title'	=> '',
			'target_amount'		=> '0',
			'external_url'		=> 'javascript:alert(1)',
		)));

		$this->assertGreaterThanOrEqual(3, count($this->errors()));
	}

	public function test_errors_are_rendered_from_language_files()
	{
		$this->submit('add', $this->form(array('campaign_title' => '')));

		foreach ($this->errors() as $message)
		{
			$this->assertDoesNotMatchRegularExpression('/^DONATIONCAMPAIGNS_/', $message, "Untranslated: {$message}");
		}
	}

	/**
	 * A rejected form comes back with what was typed. Retyping six fields
	 * because one was wrong is how people stop using a form.
	 */
	public function test_submitted_values_survive_a_rejection()
	{
		$this->submit('add', $this->form(array(
			'campaign_title'	=> 'Kept title',
			'external_url'		=> 'https://example.org/donate',
			'target_amount'		=> '0',
		)));

		$this->assertSame('Kept title', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertSame('https://example.org/donate', $this->template->vars['DONATIONCAMPAIGNS_EXTERNAL_URL']);
		// The topic context survives a rejection, so the administrator does
		// not have to start again from the topic page.
		$this->assertSame('Legal costs topic', $this->template->vars['DONATIONCAMPAIGNS_TOPIC_TITLE']);
	}

	public function test_checkbox_states_survive_a_rejection()
	{
		$this->submit('add', $this->form(array(
			'target_amount'			=> '0',
			'campaign_enabled'		=> 0,
			'show_donor_names'		=> 0,
			'show_donation_count'	=> 1,
		)));

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ENABLED']);
		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_SHOW_DONORS']);
		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_SHOW_COUNT']);
	}

	// ------------------------------------------------------------ checkboxes

	/**
	 * An unchecked checkbox is absent from the request, which must mean off.
	 */
	public function test_absent_checkboxes_are_stored_as_off()
	{
		$values = $this->form();
		unset($values['campaign_enabled'], $values['show_donor_names'], $values['show_donation_count']);

		$this->submit('add', $values);

		$created = $this->campaigns->find_by_topic_id(30);

		$this->assertFalse($created['campaign_enabled']);
		$this->assertFalse($created['show_donor_names']);
		$this->assertFalse($created['show_donation_count']);
	}

	public function test_checked_checkboxes_are_stored_as_on()
	{
		$this->submit('add', $this->form());

		$created = $this->campaigns->find_by_topic_id(30);

		$this->assertTrue($created['campaign_enabled']);
		$this->assertTrue($created['show_donor_names']);
		$this->assertTrue($created['show_donation_count']);
	}

	// ------------------------------------------------------------------ CSRF

	public function test_a_submission_without_a_valid_form_key_is_refused()
	{
		$message = $this->submit('add', $this->form(), 0, false);

		$this->assertNotEmpty($message);
		$this->assertNull($this->campaigns->find_by_topic_id(30), 'A CSRF-failing submission was saved');
	}

	// ------------------------------------------------- derived value defence

	/**
	 * collected_amount is not a form field and must not become one by being
	 * posted. This is the tampering guard from specification section 9.2, at
	 * the boundary where a request could actually carry it.
	 */
	public function test_a_posted_collected_amount_is_ignored_on_create()
	{
		$this->submit('add', $this->form(array('collected_amount' => 999999)));

		$this->assertSame(0, $this->campaigns->find_by_topic_id(30)['collected_amount']);
	}

	public function test_a_posted_collected_amount_is_ignored_on_edit()
	{
		$this->submit('edit', $this->form(array(
			'topic_id'			=> 10,
			'collected_amount'	=> 999999,
		)), 1);

		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	/**
	 * Nor may BBCode metadata be posted: it describes the stored text and is
	 * produced from it, never accepted alongside it.
	 */
	public function test_posted_bbcode_metadata_is_ignored()
	{
		$this->submit('add', $this->form(array(
			'campaign_desc'			=> 'Plain',
			'desc_bbcode_uid'		=> 'attacker',
			'desc_bbcode_bitfield'	=> 'ZZZZ',
		)));

		$created = $this->campaigns->find_by_topic_id(30);

		$this->assertNotSame('attacker', $created['desc_bbcode_uid']);
		$this->assertNotSame('ZZZZ', $created['desc_bbcode_bitfield']);
	}

	public function test_a_posted_campaign_id_cannot_redirect_the_write()
	{
		$this->submit('add', $this->form(array('campaign_id' => 2)));

		// Campaign 2 must be untouched; a new row is created instead.
		$this->assertSame('Archive fund', $this->campaigns->find_by_id(2)['campaign_title']);
	}

	// -------------------------------------------------------------- escaping

	public function test_a_title_is_stored_raw_and_escaped_once_when_rendered()
	{
		$this->submit('add', $this->form(array('campaign_title' => 'R&D "quoted"')));

		// Stored exactly as typed, and assigned to the template unchanged...
		$this->assertSame('R&D "quoted"', $this->campaigns->find_by_topic_id(30)['campaign_title']);

		$this->open('edit', $this->campaigns->find_by_topic_id(30)['campaign_id']);

		$this->assertSame('R&D "quoted"', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);

		// ...and escaped exactly once by the template.
		$html = $this->render('campaign_edit');

		$this->assertStringContainsString('R&amp;D &quot;quoted&quot;', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html);
	}

	public function test_a_malicious_title_cannot_break_out_of_the_form_field()
	{
		$this->submit('add', $this->form(array('campaign_title' => '"><script>alert(1)</script>')));

		$this->open('edit', $this->campaigns->find_by_topic_id(30)['campaign_id']);

		$html = $this->render('campaign_edit');

		$this->assertStringNotContainsString('<script>alert(1)</script>', $html);
		$this->assertStringContainsString('&lt;script&gt;', $html);
	}

	public function test_a_url_is_stored_raw_and_escaped_once_when_rendered()
	{
		$this->submit('add', $this->form(array('external_url' => 'https://example.org/d?a=1&b=2', 'external_link_text' => 'How to donate')));

		$this->assertSame('https://example.org/d?a=1&b=2', $this->campaigns->find_by_topic_id(30)['external_url']);

		$this->open('edit', $this->campaigns->find_by_topic_id(30)['campaign_id']);

		$this->assertSame('https://example.org/d?a=1&b=2', $this->template->vars['DONATIONCAMPAIGNS_EXTERNAL_URL']);

		$html = $this->render('campaign_edit');

		$this->assertStringContainsString('https://example.org/d?a=1&amp;b=2', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html);
	}

	/**
	 * The description takes the OPPOSITE contract: it is decoded back to
	 * source for the textarea, not escaped again, because it has already been
	 * through the storage encoder.
	 */
	public function test_the_description_is_decoded_for_the_textarea()
	{
		$this->submit('add', $this->form(array('campaign_desc' => 'Help <b>us</b>')));

		$this->open('edit', $this->campaigns->find_by_topic_id(30)['campaign_id']);

		// Escaped once, for a textarea that emits it raw -- so the browser
		// shows the source and nothing can close the element early.
		$this->assertSame('Help &lt;b&gt;us&lt;/b&gt;', $this->template->vars['DONATIONCAMPAIGNS_DESC']);
		$this->assertSame('Help <b>us</b>', html_entity_decode($this->template->vars['DONATIONCAMPAIGNS_DESC'], ENT_QUOTES, 'UTF-8'));
	}

	// ------------------------------------------------- the button's label

	public function test_the_form_offers_a_link_text_field()
	{
		$this->open('edit', 1);

		$this->assertArrayHasKey('DONATIONCAMPAIGNS_LINK_TEXT', $this->template->vars);
	}

	public function test_a_saved_link_text_is_reloaded_into_the_form()
	{
		$this->campaigns->update(1, array('external_link_text' => 'Request bank details'));

		$this->open('edit', 1);

		$this->assertSame('Request bank details', $this->template->vars['DONATIONCAMPAIGNS_LINK_TEXT']);
	}

	/**
	 * A new campaign starts with something usable rather than an empty button.
	 */
	public function test_a_new_campaign_is_prefilled_with_the_default()
	{
		$this->open('add');

		$this->assertSame('How to donate', $this->template->vars['DONATIONCAMPAIGNS_LINK_TEXT']);
	}

	/**
	 * What the administrator typed comes back after a validation failure, so a
	 * rejected form does not silently discard their wording.
	 */
	public function test_a_submitted_link_text_survives_a_validation_error()
	{
		$this->submit('add', $this->form(array(
			'campaign_title'		=> '',
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'Über PayPal spenden',
		)));

		$this->assertSame('Über PayPal spenden', $this->template->vars['DONATIONCAMPAIGNS_LINK_TEXT']);
	}

	public function test_a_url_without_link_text_reports_the_dependency()
	{
		$this->submit('add', $this->form(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> '   ',
		)));

		$lang = array();
		include dirname(dirname(__DIR__)) . '/language/en/common.php';

		$this->assertContains(
			$lang['DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED'],
			array_column($this->template->block('donationcampaigns_error'), 'MESSAGE'),
			'The URL/label dependency was not reported to the administrator'
		);
	}

	public function test_the_link_text_reaches_storage()
	{
		$this->submit('edit', $this->form(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'Über PayPal spenden',
		)), 1);

		$this->assertSame('Über PayPal spenden', $this->campaigns->find_by_id(1)['external_link_text']);
	}

	// ------------------------------------- the description edit/save cycle

	/**
	 * One full trip through the ACP: load the campaign into the form, render
	 * the textarea, let the browser decode it, and post what the browser would
	 * have sent. Anything that escapes twice grows here and only here.
	 *
	 * @param int $id
	 * @return string what the browser would submit from the textarea
	 */
	protected function edit_cycle($id)
	{
		$this->open('edit', $id);

		$html = $this->render('campaign_edit');

		preg_match('#<textarea[^>]*name="campaign_desc"[^>]*>(.*?)</textarea>#s', $html, $m);
		$this->assertNotEmpty($m, 'The description textarea did not render');

		// The browser decodes entities once when it parses the textarea, and
		// submits what it displayed.
		$submitted = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');

		$this->submit('edit', $this->form(array(
			'campaign_title'	=> 'Server fund',
			'topic_id'			=> 10,
			'campaign_desc'		=> $submitted,
		)), $id);

		return $submitted;
	}

	/**
	 * REGRESSION 1. An ampersand survives repeated edit/save cycles.
	 *
	 * generate_text_for_edit() returns text already escaped once, for a
	 * textarea to emit raw. The template escaped it a second time; the browser
	 * undid only one layer, so every save stored one more &amp; than the last
	 * and the description silently filled with entities.
	 */
	public function test_an_ampersand_survives_repeated_edit_cycles()
	{
		$this->submit('add', $this->form(array(
			'topic_id'		=> 30,
			'campaign_desc'	=> 'Help us replace it & keep the board fast.',
		)));

		$id = $this->campaigns->find_by_topic_id(30)['campaign_id'];

		for ($i = 1; $i <= 4; $i++)
		{
			$shown = $this->edit_cycle($id);

			$this->assertSame(
				'Help us replace it & keep the board fast.',
				$shown,
				"The description gained escaping on edit cycle {$i}"
			);
		}
	}

	/**
	 * REGRESSION 2. BBCode survives the same treatment.
	 */
	public function test_bbcode_survives_repeated_edit_cycles()
	{
		$this->submit('add', $this->form(array(
			'topic_id'		=> 30,
			'campaign_desc'	=> 'Our server is [b]five years old[/b] & tired.',
		)));

		$id = $this->campaigns->find_by_topic_id(30)['campaign_id'];

		for ($i = 1; $i <= 3; $i++)
		{
			$this->assertSame(
				'Our server is [b]five years old[/b] & tired.',
				$this->edit_cycle($id),
				"BBCode or the ampersand changed on cycle {$i}"
			);
		}
	}

	/**
	 * REGRESSION 4. The textarea shows the source the administrator typed.
	 */
	public function test_the_textarea_shows_the_original_editable_bbcode()
	{
		$typed = 'Tea & Coffee [b]bold[/b] <b>literal</b>';

		$this->submit('add', $this->form(array(
			'topic_id'		=> 30,
			'campaign_desc'	=> $typed,
		)));

		$id = $this->campaigns->find_by_topic_id(30)['campaign_id'];
		$this->open('edit', $id);

		$html = $this->render('campaign_edit');
		preg_match('#<textarea[^>]*name="campaign_desc"[^>]*>(.*?)</textarea>#s', $html, $m);

		$this->assertSame($typed, html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));

		// And the markup itself carries entities, so nothing can close the
		// textarea early or be read as a tag.
		$this->assertStringNotContainsString('<b>literal</b>', $m[1]);
		$this->assertStringContainsString('&lt;b&gt;', $m[1]);
	}

	public function test_the_textarea_is_not_double_escaped_in_the_markup()
	{
		$this->submit('add', $this->form(array(
			'topic_id'		=> 30,
			'campaign_desc'	=> 'A & B',
		)));

		$this->open('edit', $this->campaigns->find_by_topic_id(30)['campaign_id']);

		$html = $this->render('campaign_edit');
		preg_match('#<textarea[^>]*name="campaign_desc"[^>]*>(.*?)</textarea>#s', $html, $m);

		$this->assertStringContainsString('A &amp; B', $m[1]);
		$this->assertStringNotContainsString('&amp;amp;', $m[1], 'The textarea is escaping text that was already escaped');
	}
}
