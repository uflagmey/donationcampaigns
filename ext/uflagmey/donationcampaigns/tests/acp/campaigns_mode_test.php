<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * The ACP campaign list, delete and recalculate actions.
 *
 * The module coordinates: it asks services for data and for operations, and
 * renders. These tests cover the parts that only exist at this layer — the
 * permission gate, the confirmation flow, what reaches the template, and what
 * is written to the admin log.
 */
class campaigns_mode_test extends campaign_acp_test_case
{
	// ------------------------------------------------------------------ ACL

	public function test_an_unauthorised_user_is_refused()
	{
		global $auth;

		$auth->granted = false;

		$message = $this->run_and_catch();

		$this->assertStringContainsString('NOT_AUTHORISED', $message);
	}

	/**
	 * Refused before anything is read. An unauthorised request must not reach
	 * the campaign data at all, not merely fail to render it.
	 */
	public function test_an_unauthorised_user_loads_no_campaigns()
	{
		global $auth;

		$auth->granted = false;

		$this->run_and_catch();

		$this->assertSame(array(), $this->rows());
		$this->assertSame(array(), $this->template->vars);
	}

	public function test_an_unauthorised_user_cannot_delete()
	{
		global $auth;

		$auth->granted = false;
		$this->request(array('action' => 'delete', 'campaign_id' => 1), true);

		$this->run_and_catch();

		$this->assertNotNull($this->campaigns->find_by_id(1), 'An unauthorised delete succeeded');
	}

	public function test_an_unauthorised_user_cannot_recalculate()
	{
		global $auth;

		$this->campaigns->set_collected_amount(1, 999999);
		$auth->granted = false;
		$this->request(array('action' => 'recalculate', 'campaign_id' => 1), true);

		$this->run_and_catch();

		$this->assertSame(999999, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	// ------------------------------------------------------------- the list

	public function test_the_list_page_is_rendered()
	{
		$this->run_campaigns();

		$this->assertSame('acp_donationcampaigns_campaigns', $this->module->tpl_name);
		$this->assertNotEmpty($this->module->page_title);
	}

	public function test_every_campaign_is_listed()
	{
		$this->run_campaigns();

		$this->assertCount(2, $this->rows());
	}

	public function test_disabled_campaigns_are_listed_too()
	{
		$this->run_campaigns();

		$states = array_column($this->rows(), 'S_ENABLED');

		$this->assertContains(true, $states);
		$this->assertContains(false, $states, 'A disabled campaign is missing from the ACP list');
	}

	public function test_an_empty_list_renders_without_rows()
	{
		$this->campaign_service->purge_for_topics(array(10, 20));

		$this->run_campaigns();

		$this->assertSame(array(), $this->rows());

		// And the page says where campaigns come from, rather than leaving an
		// administrator hunting for a button that is not there.
		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_LIST_EMPTY_EXPLAIN}', $this->render('campaigns'));
	}

	public function test_the_campaign_title_is_shown()
	{
		$this->run_campaigns();

		$this->assertContains('Server fund', array_column($this->rows(), 'TITLE'));
	}

	/**
	 * An administrator should recognise the topic, not decode its id.
	 */
	public function test_the_topic_title_is_shown()
	{
		$this->run_campaigns();

		$this->assertContains('Server replacement fund', array_column($this->rows(), 'TOPIC_TITLE'));
	}

	public function test_a_campaign_whose_topic_is_gone_still_lists()
	{
		$this->db->sql_query('DELETE FROM phpbb_topics WHERE topic_id = 10');

		$this->run_campaigns();

		$this->assertCount(2, $this->rows(), 'A campaign became invisible and therefore undeletable');
	}

	public function test_money_is_formatted_for_display()
	{
		$this->run_campaigns();

		$row = $this->row_for('Server fund');

		$this->assertSame('100.00', $row['TARGET']);
		$this->assertSame('25.00', $row['COLLECTED']);
	}

	public function test_the_percentage_is_shown()
	{
		$this->run_campaigns();

		$this->assertSame(25, $this->row_for('Server fund')['PERCENT']);
	}

	public function test_the_donation_count_is_shown()
	{
		$this->run_campaigns();

		$this->assertSame(3, $this->row_for('Server fund')['COUNT']);
	}


	public function test_each_row_offers_edit_delete_and_recalculate()
	{
		$this->run_campaigns();

		$row = $this->row_for('Server fund');

		// Edit moved to the frontend controller in the RC2 cutover; the list
		// links out to that route. Delete and recalculate stay in the ACP.
		$this->assertStringContainsString('uflagmey_donationcampaigns_campaign_edit', $row['U_EDIT']);
		$this->assertStringNotContainsString('action=edit', $row['U_EDIT']);
		$this->assertStringContainsString('action=delete', $row['U_DELETE']);
		$this->assertStringContainsString('action=recalculate', $row['U_RECALCULATE']);
	}

	/**
	 * This page manages what exists. Creation happens from the topic a
	 * campaign belongs to, so there is no create action here and nothing
	 * that would have to ask for a topic id.
	 */
	public function test_the_page_offers_no_creation_action()
	{
		$this->run_campaigns();

		$this->assertArrayNotHasKey('U_DONATIONCAMPAIGNS_ADD', $this->template->vars);
	}

	/**
	 * The campaign id is needed to build action links, but it must not be the
	 * label an administrator reads.
	 */
	public function test_no_row_uses_an_id_as_its_label()
	{
		$this->run_campaigns();

		foreach ($this->rows() as $row)
		{
			$this->assertNotSame((string) $row['CAMPAIGN_ID'], $row['TITLE']);
			$this->assertNotEmpty($row['TITLE']);
			$this->assertNotEmpty($row['TOPIC_TITLE']);
		}
	}

	/**
	 * The list is a summary. Descriptions and donor identities belong to the
	 * pages that exist for them.
	 */
	public function test_the_list_exposes_no_description_or_donor_detail()
	{
		$this->run_campaigns();

		foreach ($this->rows() as $row)
		{
			foreach (array_keys($row) as $key)
			{
				$this->assertStringNotContainsStringIgnoringCase('desc', $key);
				$this->assertStringNotContainsStringIgnoringCase('donor', $key);
				$this->assertStringNotContainsStringIgnoringCase('bbcode', $key);
			}
		}
	}

	/**
	 * @param string $title
	 * @return array
	 */
	protected function row_for($title)
	{
		foreach ($this->rows() as $row)
		{
			if ($row['TITLE'] === $title)
			{
				return $row;
			}
		}

		$this->fail("No row for campaign {$title}");
	}

	// -------------------------------------------------------------- escaping

	public function test_a_malicious_campaign_title_is_escaped_when_rendered()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_campaigns SET campaign_title = '<script>alert(1)</script>' WHERE campaign_id = 1");

		$this->run_campaigns();

		// Assigned raw, escaped by the template.
		$this->assertContains('<script>alert(1)</script>', array_column($this->rows(), 'TITLE'));

		$html = $this->render('campaigns');

		$this->assertStringNotContainsString('<script>alert(1)</script>', $html);
		$this->assertStringContainsString('&lt;script&gt;', $html);
	}

	public function test_a_malicious_topic_title_is_escaped_when_rendered()
	{
		$this->db->sql_query("UPDATE phpbb_topics SET topic_title = '<img src=x onerror=alert(1)>' WHERE topic_id = 10");

		$this->run_campaigns();

		$html = $this->render('campaigns');

		$this->assertStringNotContainsString('<img src=x', $html);
		$this->assertStringContainsString('&lt;img', $html);
	}

	public function test_an_attribute_breakout_in_a_title_is_escaped_when_rendered()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_campaigns SET campaign_title = '\" onmouseover=\"x' WHERE campaign_id = 1");

		$this->run_campaigns();

		$this->assertStringNotContainsString('onmouseover="x"', $this->render('campaigns'));
	}

	// -------------------------------------------------------------- deletion

	public function test_a_confirmed_delete_removes_the_campaign_and_its_donations()
	{
		$this->request(array('action' => 'delete', 'campaign_id' => 1), true);

		$this->run_and_catch();

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertSame(0, $this->donations->count_by_campaign(1));
	}

	public function test_a_delete_leaves_unrelated_records_alone()
	{
		$this->request(array('action' => 'delete', 'campaign_id' => 1), true);

		$this->run_and_catch();

		$this->assertNotNull($this->campaigns->find_by_id(2));
		$this->assertSame(1, $this->donations->count_by_campaign(2));
	}

	/**
	 * Without confirmation nothing is destroyed. phpBB renders its confirm
	 * page instead, which ends the request.
	 */
	public function test_an_unconfirmed_delete_destroys_nothing()
	{
		$this->request(array('action' => 'delete', 'campaign_id' => 1));

		$this->run_and_catch();

		$this->assertNotNull($this->campaigns->find_by_id(1), 'A campaign was deleted without confirmation');
		$this->assertSame(3, $this->donations->count_by_campaign(1));
	}

	public function test_a_cancelled_delete_destroys_nothing()
	{
		$this->request(array('action' => 'delete', 'campaign_id' => 1, 'cancel' => 'Cancel'));
		$_POST['cancel'] = 'Cancel';

		$this->run_and_catch();

		unset($_POST['cancel']);

		$this->assertNotNull($this->campaigns->find_by_id(1));
	}

	public function test_a_delete_with_a_wrong_confirm_key_destroys_nothing()
	{
		global $language, $user;

		$this->request(array(
			'action'		=> 'delete',
			'campaign_id'	=> 1,
			'confirm'		=> $language->lang('YES'),
			'confirm_uid'	=> $user->data['user_id'],
			'sess'			=> $user->session_id,
			'confirm_key'	=> 'the_wrong_key',
		));

		$this->run_and_catch();

		$this->assertNotNull($this->campaigns->find_by_id(1), 'A forged confirmation deleted a campaign');
	}

	public function test_deleting_an_unknown_campaign_fails_safely()
	{
		$this->request(array('action' => 'delete', 'campaign_id' => 99999), true);

		$this->run_and_catch();

		$this->assertSame(2, $this->campaigns->count_all(), 'An unknown id widened the deletion');
		$this->assertSame(4, $this->total_donations());
	}

	public function unusable_campaign_id_data()
	{
		return array(
			'zero'			=> array(0),
			'negative'		=> array(-1),
			'sql fragment'	=> array('1 OR 1=1'),
			'wildcard'		=> array('*'),
			'string'		=> array('all'),
		);
	}

	/**
	 * @dataProvider unusable_campaign_id_data
	 */
	public function test_an_unusable_campaign_id_never_widens_a_delete($campaign_id)
	{
		$this->request(array('action' => 'delete', 'campaign_id' => $campaign_id), true);

		$this->run_and_catch();

		$this->assertGreaterThanOrEqual(1, $this->campaigns->count_all(), 'An unusable id deleted everything');
		$this->assertNotNull($this->campaigns->find_by_id(2), 'An unrelated campaign was destroyed');
	}

	public function test_a_delete_is_logged()
	{
		$this->request(array('action' => 'delete', 'campaign_id' => 1), true);

		$this->run_and_catch();

		$this->assertContains('LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED', $this->log->operations);
	}

	public function test_a_delete_reports_success_from_a_language_file()
	{
		$this->request(array('action' => 'delete', 'campaign_id' => 1), true);

		$message = $this->run_and_catch();

		$this->assertNotEmpty($message);
		$this->assertStringNotContainsString('DONATIONCAMPAIGNS_', $message, 'A raw language key reached the page');
	}

	// ----------------------------------------------------------- recalculate

	public function test_a_confirmed_recalculation_repairs_a_wrong_total()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$this->request(array('action' => 'recalculate', 'campaign_id' => 1), true);

		$this->run_and_catch();

		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	/**
	 * THE derived-value guard at the ACP boundary. The total comes from
	 * SUM(donation_amount) and there is no path by which a request value can
	 * become it.
	 */
	public function test_a_total_supplied_in_the_request_is_ignored()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$this->request(array(
			'action'			=> 'recalculate',
			'campaign_id'		=> 1,
			'collected_amount'	=> 123456,
		), true);

		$this->run_and_catch();

		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount'], 'A request value reached the stored total');
	}

	public function test_an_unconfirmed_recalculation_changes_nothing()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$this->request(array('action' => 'recalculate', 'campaign_id' => 1));

		$this->run_and_catch();

		$this->assertSame(999999, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	public function test_a_recalculation_with_a_wrong_confirm_key_changes_nothing()
	{
		global $language, $user;

		$this->campaigns->set_collected_amount(1, 999999);

		$this->request(array(
			'action'		=> 'recalculate',
			'campaign_id'	=> 1,
			'confirm'		=> $language->lang('YES'),
			'confirm_uid'	=> $user->data['user_id'],
			'sess'			=> $user->session_id,
			'confirm_key'	=> 'the_wrong_key',
		));

		$this->run_and_catch();

		$this->assertSame(999999, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	public function test_recalculating_an_unknown_campaign_fails_safely()
	{
		$this->request(array('action' => 'recalculate', 'campaign_id' => 99999), true);

		$this->run_and_catch();

		$this->assertSame(2500, $this->campaigns->find_by_id(1)['collected_amount']);
		$this->assertSame(700, $this->campaigns->find_by_id(2)['collected_amount']);
	}

	public function test_a_recalculation_is_logged()
	{
		$this->request(array('action' => 'recalculate', 'campaign_id' => 1), true);

		$this->run_and_catch();

		$this->assertContains('LOG_DONATIONCAMPAIGNS_TOTAL_RECALCULATED', $this->log->operations);
	}

	public function test_a_recalculation_reports_both_values_from_a_language_file()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$this->request(array('action' => 'recalculate', 'campaign_id' => 1), true);

		$message = $this->run_and_catch();

		$this->assertStringNotContainsString('DONATIONCAMPAIGNS_', $message);
		$this->assertStringContainsString('25.00', $message, 'The repaired total should be reported');
	}

	/**
	 * @return int
	 */
	protected function total_donations()
	{
		$result = $this->db->sql_query('SELECT COUNT(*) AS total FROM phpbb_ufdc_donations');
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * REGRESSION — same defect class as the donation messages, found on the
	 * live board during task 17. confirm_box and the ACP log viewer render
	 * their text as raw HTML, so a campaign title carrying markup executes
	 * there unless it is escaped where the message is built.
	 */
	public function test_a_campaign_title_in_a_log_entry_is_escaped()
	{
		$this->db->sql_query("UPDATE phpbb_ufdc_campaigns SET campaign_title = '<script>alert(1)</script>' WHERE campaign_id = 1");

		$this->request(array('action' => 'delete', 'campaign_id' => 1), true);
		$this->run_and_catch();

		$entry = end($this->log->entries);
		$data = implode(' ', (array) $entry[5]);

		$this->assertStringNotContainsString('<script>', $data, 'A log entry carried unescaped markup');
		$this->assertStringContainsString('&lt;script&gt;', $data);
	}

	/**
	 * The campaign list is the ONLY way into donation management: the mode is
	 * hidden from the ACP menu because it is meaningless without a campaign.
	 * If these links break, donations become unreachable.
	 */
	public function test_every_campaign_row_offers_a_donations_link()
	{
		$this->realistic_action();
		$this->run_campaigns();

		$this->assertNotSame(array(), $this->rows());

		foreach ($this->rows() as $row)
		{
			$this->assertNotEmpty($row['U_DONATIONS'], 'A campaign row had no donations link');
		}
	}

	public function test_the_donations_link_targets_the_donations_mode()
	{
		$this->realistic_action();
		$this->run_campaigns();

		$url = $this->row_for('Server fund')['U_DONATIONS'];

		$this->assertStringContainsString('mode=donations', $url);
		$this->assertStringNotContainsString('mode=campaigns', $url, 'The link still points at the campaign list');
	}

	public function test_the_donations_link_carries_the_rows_own_campaign_id()
	{
		$this->realistic_action();
		$this->run_campaigns();

		foreach ($this->rows() as $row)
		{
			$this->assertStringContainsString(
				'campaign_id=' . $row['CAMPAIGN_ID'],
				$row['U_DONATIONS'],
				'A row linked to a campaign other than its own'
			);
		}
	}

	/**
	 * The id in the link comes from the campaign row, never from the request.
	 * Taking it from input would let a crafted campaigns URL rewrite every
	 * row's link to point somewhere else.
	 */
	public function test_the_donations_link_ignores_request_supplied_input()
	{
		$this->realistic_action();
		$this->request(array('campaign_id' => 9999, 'mode' => 'donations'));
		$this->run_campaigns();

		foreach ($this->rows() as $row)
		{
			$this->assertStringNotContainsString('campaign_id=9999', $row['U_DONATIONS']);
			$this->assertStringContainsString('campaign_id=' . $row['CAMPAIGN_ID'], $row['U_DONATIONS']);
		}
	}

	public function test_the_rendered_list_shows_a_donations_action()
	{
		$this->realistic_action();
		$this->run_campaigns();

		$html = $this->render('campaigns');

		$this->assertStringContainsString('mode=donations', $html, 'The rendered list offers no way to reach donations');
	}

	/**
	 * phpBB hands each mode its own action URL. The base fixture uses a
	 * placeholder, but the donations link is derived from this string, so
	 * these tests need the shape a real board supplies.
	 *
	 * @return void
	 */
	protected function realistic_action()
	{
		$this->module->u_action = 'index.php?i=-uflagmey-donationcampaigns-acp-main_module&mode=campaigns';
	}
}
