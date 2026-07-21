<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * The ACP donations mode — READ-ONLY oversight (decision 1).
 *
 * A donation row is a CONFIRMED receipt. Recording, editing and deleting a
 * receipt now happen on the topic, behind the forum-scoped
 * m_donationcampaigns_donations permission (see the frontend
 * donation_controller and its tests). This mode only shows the current stored
 * state and links to the topic, so an administrator who is not a moderator of
 * the forum does not silently gain the donation-management power here.
 *
 * These tests assert the list, its campaign-context resolution, the safe
 * handling of a malformed or hostile campaign_id, and that the view offers no
 * write path of its own.
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
	 * @param array $values
	 * @return void
	 */
	protected function open(array $values = array())
	{
		$this->request(array_merge(array('campaign_id' => 1), $values));
		$this->module->main(1, 'donations');
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

	// ------------------------------------------------- read-only, not a write path

	/**
	 * Each row links to the topic's frontend edit route — not to an ACP
	 * action=edit or action=delete, which no longer exist. Editing a receipt is
	 * a forum-scoped moderator act, gated on the topic, not an admin act here.
	 */
	public function test_each_row_links_to_the_topic_for_editing()
	{
		$this->open();

		foreach ($this->listed() as $row)
		{
			$this->assertStringContainsString('uflagmey_donationcampaigns_donation_edit', $row['U_EDIT']);
			$this->assertArrayNotHasKey('U_DELETE', $row, 'The read-only ACP list must offer no delete');
		}
	}

	/**
	 * The list links to the topic's management landing for recording a donation;
	 * it exposes no add form of its own.
	 */
	public function test_the_list_links_to_the_topic_for_management()
	{
		$this->open();

		$this->assertStringContainsString('uflagmey_donationcampaigns_manage', $this->template->vars['U_DONATIONCAMPAIGNS_MANAGE']);
		$this->assertArrayNotHasKey('U_DONATIONCAMPAIGNS_ADD', $this->template->vars, 'The read-only ACP list must offer no add form');
	}

	/**
	 * The rendered oversight page carries no add/edit/delete action URLs of its
	 * own — the only affordances are links to the topic routes.
	 */
	public function test_the_rendered_page_exposes_no_acp_write_actions()
	{
		$this->open();

		$html = $this->render('donations');

		$this->assertStringNotContainsString('action=add', $html);
		$this->assertStringNotContainsString('action=delete', $html);
	}

	// -------------------------------------------------------------- escaping

	/**
	 * Donor names are stored exactly as typed and escaped once, where they are
	 * rendered. Twig autoescaping is off in phpBB 3.3, so an unescaped
	 * assignment would reach the page verbatim. The receipt is seeded directly,
	 * because recording it is the frontend controller's job now.
	 *
	 * The entity-like case is the one that catches double escaping: a donor who
	 * literally typed "&amp;" must see "&amp;amp;" in the source and "&amp;" on
	 * screen — escaped once, not twice.
	 *
	 * @dataProvider malicious_donor_name_data
	 */
	public function test_a_donor_name_is_listed_raw_and_escaped_once($name, $expected_fragment)
	{
		$this->donations->update(1, array('donor_name' => $name));

		$this->open();

		// Assigned raw...
		$this->assertContains($name, array_column($this->listed(), 'DONOR_NAME'));

		// ...and escaped exactly once by the template.
		$html = $this->render('donations');

		$this->assertStringContainsString($expected_fragment, $html);
		$this->assertStringNotContainsString(htmlspecialchars($expected_fragment, ENT_QUOTES, 'UTF-8'), $html, 'The name was escaped twice');
	}

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

	public function test_a_malicious_donor_name_never_renders_as_markup()
	{
		$this->donations->update(1, array('donor_name' => '"><script>alert(1)</script>'));

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

	// ------------------------------------------------ campaign-context resolution

	/**
	 * Entering the mode with no campaign in the URL is a MISSING CONTEXT
	 * problem, not a missing campaign. Reporting "That campaign no longer
	 * exists." told administrators their data had been deleted when nothing had
	 * happened to it — the bug that made the old menu entry look like data loss.
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
	 * discarded the payload rather than the database executing it.
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
	 * The mode is hidden from the ACP menu but must remain dispatchable through
	 * the campaign list's link, which carries a valid campaign_id. Deleting the
	 * module row instead of hiding it would break exactly this.
	 */
	public function test_the_hidden_mode_dispatches_with_a_valid_campaign_id()
	{
		$this->request(array('campaign_id' => 1));
		$message = $this->run_donations();

		$this->assertSame('', $message, 'Dispatching donations with a valid campaign_id raised an error');
		$this->assertNotSame(array(), $this->listed(), 'The donation list did not render');
	}

	/**
	 * The campaign title is the primary context on the donations page, since the
	 * mode is no longer reachable from a menu that would name it.
	 */
	public function test_the_selected_campaign_title_is_shown()
	{
		$this->request(array('campaign_id' => 1));
		$this->run_donations();

		$this->assertSame('Server fund', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}
}
