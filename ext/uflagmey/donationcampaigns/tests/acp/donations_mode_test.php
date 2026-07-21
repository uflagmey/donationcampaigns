<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * The ACP donations mode.
 *
 * A donation row is a CONFIRMED receipt: money an administrator has already
 * received. Nothing here initiates a payment, and no public user can reach it.
 *
 * The module coordinates only. Every mutation goes through donation_service,
 * which recalculates the campaign total from SUM() inside the same
 * transaction — so these tests assert, after every write, that the stored
 * total still equals the sum of the rows.
 */
class donations_mode_test extends campaign_acp_test_case
{
	public function setUp(): void
	{
		parent::setUp();

		// phpBB hands each mode its own action URL; U_BACK is derived from it,
		// so the fixture has to carry a realistic one.
		$this->module->u_action = 'index.php?i=-uflagmey-donationcampaigns-acp-main_module&mode=donations';
	}

	/**
	 * A complete, valid confirmed-donation submission.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function form(array $overrides = array())
	{
		return array_merge(array(
			'donation_amount'	=> '50.00',
			'donor_name'		=> 'Clara S.',
			'donation_time'		=> '2026-07-19',
			'donation_public'	=> 1,
		), $overrides);
	}

	/**
	 * @param array $values
	 * @return void
	 */
	protected function open(array $values = array())
	{
		$this->request(array_merge(array('campaign_id' => 1), $values));
		$this->module->main(1, 'donations');
	}

	/**
	 * @param string $action
	 * @param array $values
	 * @param int $donation_id
	 * @param bool $valid_token
	 * @return string
	 */
	protected function submit($action, array $values, $donation_id = 0, $valid_token = true)
	{
		$this->post_form('donationcampaigns_donation', array_merge(array(
			'action'		=> $action,
			'campaign_id'	=> 1,
			'donation_id'	=> $donation_id,
			'submit'		=> 'Submit',
		), $values), $valid_token);

		return $this->run_donations();
	}

	/**
	 * @return string
	 */
	protected function run_donations()
	{
		try
		{
			$this->module->main(1, 'donations');
		}
		catch (\Throwable $e)
		{
			return $e->getMessage();
		}

		return '';
	}

	/**
	 * @return array
	 */
	protected function listed()
	{
		return $this->template->block('donationcampaigns_donation');
	}

	/**
	 * THE invariant, asserted after every mutation: the stored total is a
	 * cache of SUM(donation_amount) and must never drift from it.
	 *
	 * @return void
	 */
	protected function assert_total_matches_sum()
	{
		foreach (array(1, 2) as $campaign_id)
		{
			$this->assertSame(
				$this->donations->sum_by_campaign($campaign_id),
				$this->campaigns->find_by_id($campaign_id)['collected_amount'],
				"Stored total drifted from SUM() for campaign {$campaign_id}"
			);
		}
	}

	// ------------------------------------------------------------------ ACL

	public function test_an_unauthorised_user_is_refused()
	{
		global $auth;

		$auth->granted = false;

		$this->request(array('campaign_id' => 1));

		$this->assertStringContainsString('NOT_AUTHORISED', $this->run_donations());
	}

	public function test_an_unauthorised_user_loads_no_donations()
	{
		global $auth;

		$auth->granted = false;
		$this->request(array('campaign_id' => 1));

		$this->run_donations();

		$this->assertSame(array(), $this->listed());
		$this->assertSame(array(), $this->template->vars);
	}

	public function test_an_unauthorised_user_cannot_add()
	{
		global $auth;

		$auth->granted = false;
		$before = $this->donations->count_by_campaign(1);

		$this->submit('add', $this->form());

		$this->assertSame($before, $this->donations->count_by_campaign(1));
	}

	public function test_an_unauthorised_user_cannot_delete()
	{
		global $auth;

		$auth->granted = false;
		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 1), true);

		$this->run_donations();

		$this->assertNotNull($this->donations->find_by_id(1));
	}

	// ------------------------------------------------------------- the list

	public function test_the_list_renders()
	{
		$this->open();

		$this->assertSame('acp_donationcampaigns_donations', $this->module->tpl_name);
		$this->assertNotEmpty($this->module->page_title);
	}

	public function test_every_donation_of_the_campaign_is_listed()
	{
		$this->open();

		$this->assertCount(3, $this->listed());
	}

	public function test_private_donations_are_listed_in_the_acp()
	{
		$this->open();

		$this->assertContains(false, array_column($this->listed(), 'S_PUBLIC'), 'The ACP must show private receipts too');
	}

	/**
	 * The campaign is identified by its title, not by a number an
	 * administrator would have to recognise.
	 */
	public function test_the_campaign_is_named_not_numbered()
	{
		$this->open();

		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	public function test_the_current_campaign_total_is_shown()
	{
		$this->open();

		$this->assertSame('25.00', $this->template->vars['DONATIONCAMPAIGNS_COLLECTED_AMOUNT']);
	}

	public function test_the_list_offers_a_way_back_to_the_campaigns()
	{
		$this->open();

		$this->assertStringContainsString('mode=campaigns', $this->template->vars['U_BACK']);
	}

	public function test_an_empty_campaign_lists_nothing()
	{
		// Campaign 2 has one receipt in the fixture; remove it so the empty
		// state is genuinely empty.
		$this->donations->delete_by_campaign_ids(array(2));

		$this->request(array('campaign_id' => 2));
		$this->run_donations();

		$this->assertSame(array(), $this->listed());
		$this->assertStringContainsString('{L_ACP_NO_ITEMS}', $this->render('donations'));
	}

	public function test_amounts_and_dates_are_formatted_for_display()
	{
		$this->open();

		foreach ($this->listed() as $row)
		{
			$this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $row['AMOUNT']);
			$this->assertNotEmpty($row['DONATED_AT']);
		}
	}

	public function test_an_empty_donor_name_is_listed_as_anonymous()
	{
		$this->donations->update(1, array('donor_name' => ''));

		$this->open();

		$this->assertContains('Anonymous', array_column($this->listed(), 'DONOR_NAME'));
	}

	public function test_each_row_offers_edit_and_delete()
	{
		$this->open();

		foreach ($this->listed() as $row)
		{
			$this->assertStringContainsString('action=edit', $row['U_EDIT']);
			$this->assertStringContainsString('action=delete', $row['U_DELETE']);
		}
	}

	public function test_the_list_is_paginated_deterministically()
	{
		$this->open(array('start' => 0));
		$first = array_column($this->listed(), 'DONATION_ID');

		$this->template->blocks = array();
		$this->open(array('start' => 0));

		$this->assertSame($first, array_column($this->listed(), 'DONATION_ID'));
	}

	public function test_an_unknown_campaign_is_refused()
	{
		$this->request(array('campaign_id' => 99999));

		$this->assertNotEmpty($this->run_donations());
		$this->assertSame(array(), $this->listed());
	}

	public function malformed_id_data()
	{
		return array(
			'zero'			=> array(0),
			'negative'		=> array(-1),
			'sql fragment'	=> array('1 OR 1=1'),
			'wildcard'		=> array('*'),
			'words'			=> array('all'),
		);
	}

	/**
	 * @dataProvider malformed_id_data
	 */
	public function test_a_malformed_campaign_id_fails_safely($campaign_id)
	{
		$this->request(array('campaign_id' => $campaign_id));

		$this->run_donations();

		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertSame(1, $this->donations->count_by_campaign(2));
	}

	// -------------------------------------------------------- adding receipts

	public function test_a_valid_confirmed_donation_is_recorded()
	{
		$this->submit('add', $this->form());

		$this->assertSame(4, $this->donations->count_by_campaign(1));
		$this->assert_total_matches_sum();
	}

	public function test_adding_a_donation_raises_the_campaign_total()
	{
		$this->submit('add', $this->form(array('donation_amount' => '50.00')));

		// 2500 + 5000
		$this->assertSame(7500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	/**
	 * The canonical float-failure case, through the real form.
	 */
	public function test_a_comma_decimal_amount_is_parsed_without_a_float()
	{
		$this->submit('add', $this->form(array('donation_amount' => '8,70')));

		$this->assertSame(2500 + 870, $this->campaigns->find_by_id(1)['collected_amount']);
		$this->assert_total_matches_sum();
	}

	public function test_an_anonymous_donation_may_have_an_empty_name()
	{
		$this->submit('add', $this->form(array('donor_name' => '')));

		$this->assertSame(4, $this->donations->count_by_campaign(1));
		$this->assert_total_matches_sum();
	}

	public function test_a_private_donation_still_counts_towards_the_total()
	{
		$values = $this->form(array('donation_amount' => '10.00'));
		unset($values['donation_public']);

		$this->submit('add', $values);

		$this->assertSame(3500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	public function test_adding_is_logged()
	{
		$this->submit('add', $this->form());

		$this->assertContains('LOG_DONATIONCAMPAIGNS_DONATION_ADDED', $this->log->operations);
	}

	public function test_adding_leaves_the_other_campaign_alone()
	{
		$this->submit('add', $this->form());

		$this->assertSame(1, $this->donations->count_by_campaign(2));
		$this->assertSame(700, $this->campaigns->find_by_id(2)['collected_amount']);
	}

	// ------------------------------------------------------------- editing

	public function test_editing_changes_the_amount_and_recalculates()
	{
		$this->submit('edit', $this->form(array('donation_amount' => '20.00')), 1);

		$this->assertSame(2000, $this->donations->find_by_id(1)['donation_amount']);
		// 2500 - 1000 + 2000, derived from SUM() rather than that arithmetic.
		$this->assertSame(3500, $this->campaigns->find_by_id(1)['collected_amount']);
		$this->assert_total_matches_sum();
	}

	public function test_editing_is_logged()
	{
		$this->submit('edit', $this->form(), 1);

		$this->assertContains('LOG_DONATIONCAMPAIGNS_DONATION_EDITED', $this->log->operations);
	}

	public function test_editing_an_unknown_donation_is_refused()
	{
		$this->submit('edit', $this->form(), 99999);

		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assert_total_matches_sum();
	}

	/**
	 * A donation belongs to the campaign that recorded it. Allowing an edit to
	 * move it would silently corrupt two totals at once, so the campaign is
	 * taken from the stored row and a posted campaign_id cannot redirect it.
	 */
	public function test_a_posted_campaign_id_cannot_move_a_donation()
	{
		$this->post_form('donationcampaigns_donation', array_merge(array(
			'action'		=> 'edit',
			'campaign_id'	=> 2,
			'donation_id'	=> 1,
			'submit'		=> 'Submit',
		), $this->form(array('donation_amount' => '20.00'))));

		$this->run_donations();

		$this->assertSame(1, $this->donations->find_by_id(1)['campaign_id'], 'A donation was moved between campaigns');
		$this->assert_total_matches_sum();
	}

	// ------------------------------------------------------------- deleting

	public function test_a_confirmed_delete_removes_the_donation_and_recalculates()
	{
		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 1), true);

		$this->run_donations();

		$this->assertNull($this->donations->find_by_id(1));
		$this->assertSame(1500, $this->campaigns->find_by_id(1)['collected_amount']);
		$this->assert_total_matches_sum();
	}

	public function test_an_unconfirmed_delete_removes_nothing()
	{
		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 1));

		$this->run_donations();

		$this->assertNotNull($this->donations->find_by_id(1));
		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	public function test_a_delete_with_a_forged_confirmation_removes_nothing()
	{
		global $language, $user;

		$this->request(array(
			'campaign_id'	=> 1,
			'action'		=> 'delete',
			'donation_id'	=> 1,
			'confirm'		=> $language->lang('YES'),
			'confirm_uid'	=> $user->data['user_id'],
			'sess'			=> $user->session_id,
			'confirm_key'	=> 'the_wrong_key',
		));

		$this->run_donations();

		$this->assertNotNull($this->donations->find_by_id(1));
	}

	public function test_deleting_an_unknown_donation_fails_safely()
	{
		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 99999), true);

		$this->run_donations();

		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assert_total_matches_sum();
	}

	public function test_deleting_is_logged()
	{
		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 1), true);

		$this->run_donations();

		$this->assertContains('LOG_DONATIONCAMPAIGNS_DONATION_DELETED', $this->log->operations);
	}

	public function test_deleting_leaves_unrelated_rows_alone()
	{
		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 1), true);

		$this->run_donations();

		$this->assertNotNull($this->donations->find_by_id(2));
		$this->assertSame(1, $this->donations->count_by_campaign(2));
		$this->assertNotNull($this->campaigns->find_by_id(1));
	}

	// ----------------------------------------------------------- validation

	public function invalid_amount_data()
	{
		return array(
			'zero'			=> array('0'),
			'negative'		=> array('-5'),
			'words'			=> array('lots'),
			'empty'			=> array(''),
			'too precise'	=> array('10.001'),
			'over limit'	=> array('99999999999'),
		);
	}

	/**
	 * @dataProvider invalid_amount_data
	 */
	public function test_an_invalid_amount_is_rejected($amount)
	{
		$this->submit('add', $this->form(array('donation_amount' => $amount)));

		$this->assertSame(3, $this->donations->count_by_campaign(1), "Amount '{$amount}' was accepted");
		$this->assertNotEmpty($this->errors());
		$this->assert_total_matches_sum();
	}

	public function test_an_overlong_donor_name_is_rejected()
	{
		$this->submit('add', $this->form(array('donor_name' => str_repeat('a', 256))));

		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertNotEmpty($this->errors());
	}

	public function test_an_invalid_date_is_rejected()
	{
		$this->submit('add', $this->form(array('donation_time' => 'not a date')));

		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertNotEmpty($this->errors());
	}

	public function test_errors_are_rendered_from_language_files()
	{
		$this->submit('add', $this->form(array('donation_amount' => '0')));

		foreach ($this->errors() as $message)
		{
			$this->assertDoesNotMatchRegularExpression('/^DONATIONCAMPAIGNS_/', $message, "Untranslated: {$message}");
			$this->assertNotEmpty($message);
		}
	}

	public function test_submitted_values_survive_a_rejection()
	{
		$this->submit('add', $this->form(array(
			'donor_name'		=> 'Kept name',
			'donation_amount'	=> '0',
		)));

		$this->assertSame('Kept name', $this->template->vars['DONATIONCAMPAIGNS_DONOR_NAME']);
		$this->assertSame('0', $this->template->vars['DONATIONCAMPAIGNS_DONATION_AMOUNT']);
	}

	public function test_the_public_flag_survives_a_rejection()
	{
		$values = $this->form(array('donation_amount' => '0'));
		unset($values['donation_public']);

		$this->submit('add', $values);

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_PUBLIC']);
	}

	// ------------------------------------------------------------------ CSRF

	public function test_a_submission_without_a_valid_form_key_is_refused()
	{
		$message = $this->submit('add', $this->form(), 0, false);

		$this->assertNotEmpty($message);
		$this->assertSame(3, $this->donations->count_by_campaign(1), 'A CSRF-failing submission was saved');
	}

	// ------------------------------------------- derived values are not input

	/**
	 * The campaign total is derived from SUM(). There is no request path by
	 * which one can be supplied, and posting one must change nothing.
	 */
	public function test_a_posted_collected_amount_is_ignored()
	{
		$this->submit('add', $this->form(array(
			'donation_amount'	=> '10.00',
			'collected_amount'	=> 999999,
		)));

		$this->assertSame(3500, $this->campaigns->find_by_id(1)['collected_amount']);
		$this->assert_total_matches_sum();
	}

	/**
	 * The repair property: a total corrupted out of band is corrected by the
	 * next successful mutation, because it is recomputed rather than adjusted.
	 */
	public function test_a_corrupted_total_is_repaired_by_the_next_mutation()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$this->submit('add', $this->form(array('donation_amount' => '10.00')));

		$this->assertSame(3500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	public function test_a_corrupted_total_is_repaired_by_a_delete()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 1), true);
		$this->run_donations();

		$this->assertSame(1500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	// -------------------------------------------------------------- escaping

	public function malicious_donor_name_data()
	{
		return array(
			'script'		=> array('<script>alert(1)</script>', '&lt;script&gt;'),
			'image'			=> array('<img src=x onerror=alert(1)>', '&lt;img'),
			'ampersand'		=> array('Rock & Roll', 'Rock &amp; Roll'),
			'quotes'		=> array('He said "hi"', '&quot;hi&quot;'),
			'entity like'	=> array('&amp; already', '&amp;amp; already'),
		);
	}

	/**
	 * Donor names are stored exactly as typed and escaped once, where they are
	 * rendered. Twig autoescaping is off in phpBB 3.3, so an unescaped
	 * assignment would reach the page verbatim.
	 *
	 * The entity-like case is the one that catches double escaping: a donor
	 * who literally types "&amp;" must see "&amp;amp;" in the source and
	 * "&amp;" on screen — escaped once, not twice.
	 *
	 * @dataProvider malicious_donor_name_data
	 */
	public function test_a_donor_name_is_stored_raw_and_escaped_once($name, $expected_fragment)
	{
		$this->submit('add', $this->form(array('donor_name' => $name)));

		$stored = $this->donations->find_page_by_campaign(1, 1, 0)[0];

		$this->assertSame($name, $stored['donor_name'], 'The name was escaped on the way into storage');

		$this->template->blocks = array();
		$this->template->vars = array();
		$this->open();

		// Assigned raw...
		$this->assertContains($name, array_column($this->listed(), 'DONOR_NAME'));

		// ...and escaped exactly once by the template.
		$html = $this->render('donations');

		$this->assertStringContainsString($expected_fragment, $html);
		$this->assertStringNotContainsString(htmlspecialchars($expected_fragment, ENT_QUOTES, 'UTF-8'), $html, 'The name was escaped twice');
	}

	public function test_a_malicious_donor_name_never_renders_as_markup()
	{
		$this->submit('add', $this->form(array('donor_name' => '"><script>alert(1)</script>')));

		$this->template->blocks = array();
		$this->template->vars = array();
		$this->open();

		$html = $this->render('donations');

		$this->assertStringNotContainsString('<script>alert(1)</script>', $html);
		$this->assertStringContainsString('&lt;script&gt;', $html);
	}

	public function test_the_campaign_title_is_escaped_in_the_rendered_donations_header()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_campaigns SET campaign_title = '<b>Bold</b>' WHERE campaign_id = 1");

		$this->open();

		$this->assertSame('<b>Bold</b>', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt;', $this->render('donations'));
	}

	// ------------------------------- escaping in messages and log entries

	/**
	 * REGRESSION — found on the live board during task 17.
	 *
	 * phpBB renders confirm_box's {MESSAGE_TEXT} and the ACP log viewer's
	 * entries as raw HTML, because Twig autoescaping is off board-wide. A
	 * donor name carrying markup therefore executes THERE even though the
	 * list and the public box escape it correctly.
	 *
	 * Values interpolated into a message must be escaped at the point they
	 * are put into it, exactly like every other output.
	 */
	public function test_a_donor_name_in_a_log_entry_is_escaped()
	{
		$this->submit('add', $this->form(array('donor_name' => '<script>alert(1)</script>')));

		$entry = end($this->log->entries);
		$data = implode(' ', (array) $entry[5]);

		$this->assertStringNotContainsString('<script>', $data, 'A log entry carried unescaped markup');
		$this->assertStringContainsString('&lt;script&gt;', $data);
	}

	public function test_a_donor_name_in_a_delete_log_entry_is_escaped()
	{
		$this->donations->update(1, array('donor_name' => '<script>alert(1)</script>'));

		$this->request(array('campaign_id' => 1, 'action' => 'delete', 'donation_id' => 1), true);
		$this->run_donations();

		$entry = end($this->log->entries);
		$data = implode(' ', (array) $entry[5]);

		$this->assertStringNotContainsString('<script>', $data);
	}

	/**
	 * Entering the mode with no campaign in the URL is a MISSING CONTEXT
	 * problem, not a missing campaign. Reporting "That campaign no longer
	 * exists." told administrators their data had been deleted when nothing
	 * had happened to it — the bug that made the old menu entry look like
	 * data loss.
	 *
	 * @dataProvider absent_campaign_ids
	 */
	public function test_an_absent_campaign_id_reports_that_none_was_selected($value)
	{
		$request = ($value === null) ? array() : array('campaign_id' => $value);

		$this->request($request);
		$message = $this->run_donations();

		$this->assertStringContainsString(
			'No campaign selected',
			$message,
			'A missing campaign_id must not be reported as a deleted campaign'
		);
		$this->assertStringNotContainsString('no longer exists', $message);
	}

	public function absent_campaign_ids()
	{
		return array(
			'omitted'			=> array(null),
			'zero'				=> array(0),
			'negative'			=> array(-1),
			'empty string'		=> array(''),
			'non-numeric'		=> array('abc'),
		);
	}

	/**
	 * A positive id that resolves to nothing is a genuinely missing campaign,
	 * and keeps the original message.
	 */
	public function test_an_unknown_positive_campaign_id_reports_that_it_no_longer_exists()
	{
		$this->request(array('campaign_id' => 4242));
		$message = $this->run_donations();

		$this->assertStringContainsString('no longer exists', $message);
		$this->assertStringNotContainsString('No campaign selected', $message);
	}

	/**
	 * Injection-shaped input must never reach SQL.
	 *
	 * Every payload here casts to (int) 1, a real campaign, so the correct
	 * outcome is not an error but an ORDINARY page for campaign 1: the cast
	 * discarded the payload rather than the database executing it. Asserting
	 * an error here would have been asserting the wrong thing — the earlier
	 * version of this test did, and failed against safe behaviour.
	 *
	 * @dataProvider hostile_campaign_ids
	 */
	public function test_a_hostile_campaign_id_never_reaches_sql($value)
	{
		$this->request(array('campaign_id' => $value));
		$hostile_message = $this->run_donations();
		$hostile_rows = $this->listed();

		// The table the payloads try to drop or read is intact.
		$this->assertNotNull($this->campaigns->find_by_id(1), 'The campaigns table did not survive');
		$this->assertNotNull($this->campaigns->find_by_id(2));

		// And the result is exactly what the bare integer produces.
		$this->setUp();
		$this->request(array('campaign_id' => 1));
		$plain_message = $this->run_donations();

		$this->assertSame($plain_message, $hostile_message, 'Hostile input took a different path from its integer value');
		$this->assertSame(
			array_column($this->listed(), 'DONATION_ID'),
			array_column($hostile_rows, 'DONATION_ID'),
			'Hostile input returned a different row set from its integer value'
		);
	}

	public function hostile_campaign_ids()
	{
		return array(
			'union select'	=> array('1 UNION SELECT 1'),
			'drop table'	=> array('1; DROP TABLE phpbb_ufdc_campaigns'),
			'comment'		=> array("1'--"),
			'or true'		=> array("1' OR '1'='1"),
		);
	}

	/**
	 * The mode is hidden from the ACP menu but must remain dispatchable
	 * through the campaign list's link, which carries a valid campaign_id.
	 * Deleting the module row instead of hiding it would break exactly this.
	 */
	public function test_the_hidden_mode_dispatches_with_a_valid_campaign_id()
	{
		$this->request(array('campaign_id' => 1));
		$message = $this->run_donations();

		$this->assertSame('', $message, 'Dispatching donations with a valid campaign_id raised an error');
		$this->assertNotSame(array(), $this->listed(), 'The donation list did not render');
	}

	/**
	 * The campaign title is the primary context on the donations page, since
	 * the mode is no longer reachable from a menu that would name it.
	 */
	public function test_the_selected_campaign_title_is_shown()
	{
		$this->request(array('campaign_id' => 1));
		$this->run_donations();

		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}
}
