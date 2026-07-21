<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\service\donation_service;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;

/**
 * Shared fixture for the donation-service tests.
 *
 * Both the behavioural tests and the randomised property test need the same
 * two campaigns, the same donation rows, and the same real database, so the
 * setup lives here once.
 */
abstract class donation_test_case extends \phpbb_test_case
{
	/** @var recording_driver */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var donation_service */
	protected $service;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	/** @var string */
	protected $db_file;

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_donation_service_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new recording_driver();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$migration = new m1_initial_schema(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			'',
			'php',
			'phpbb_'
		);
		$this->tools->perform_schema_changes($migration->update_schema());

		// The campaign button's label arrived in m6. Fixtures build the schema
		// from the migrations rather than a hand-written copy, so they have to
		// walk the same chain a real board does.
		$link_text = new \uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text(
			new \phpbb\config\config(array()),
			$this->db,
			$this->tools,
			'',
			'php',
			'phpbb_'
		);
		$this->tools->perform_schema_changes($link_text->update_schema());

		$this->seed();

		$this->campaigns = new campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$this->donations = new donation_repository($this->db, 'phpbb_ufdc_donations');
		$this->service = new donation_service($this->db, $this->campaigns, $this->donations);

		$this->db->forget();
	}

	public function tearDown(): void
	{
		if ($this->db)
		{
			$this->db->sql_close();
		}

		if ($this->db_file && file_exists($this->db_file))
		{
			unlink($this->db_file);
		}

		parent::tearDown();
	}

	/**
	 * Campaign 1 holds three donations summing to 2500, one of them non-public,
	 * and its stored total already agrees. Campaign 2 holds none.
	 */
	protected function seed()
	{
		$campaigns = array(
			array('campaign_id' => 1, 'topic_id' => 10, 'campaign_title' => 'Server fund', 'target_amount' => 100000, 'collected_amount' => 2500),
			array('campaign_id' => 2, 'topic_id' => 20, 'campaign_title' => 'Archive restoration', 'target_amount' => 50000, 'collected_amount' => 0),
		);

		foreach ($campaigns as $campaign)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_desc'			=> '',
				'desc_bbcode_uid'		=> '',
				'desc_bbcode_bitfield'	=> '',
				'desc_bbcode_options'	=> 7,
				'campaign_enabled'		=> 1,
				'show_donor_names'		=> 1,
				'show_donation_count'	=> 1,
				'external_url'			=> '',
				'campaign_created'		=> 1700000000,
				'campaign_updated'		=> 1700000000,
			), $campaign)));
		}

		$donations = array(
			array('donation_amount' => 1000, 'donor_name' => 'Anna M.', 'donation_time' => 1700000100, 'donation_public' => 1),
			array('donation_amount' => 1200, 'donor_name' => 'Bernd K.', 'donation_time' => 1700000200, 'donation_public' => 0),
			array('donation_amount' => 300, 'donor_name' => 'Chris T.', 'donation_time' => 1700000300, 'donation_public' => 1),
		);

		foreach ($donations as $donation)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_id'		=> 1,
				'donation_created'	=> 1700000000,
				'donation_updated'	=> 1700000000,
			), $donation)));
		}
	}

	/**
	 * @param int $campaign_id
	 * @return int The denormalised total as stored
	 */
	protected function stored_total($campaign_id)
	{
		return $this->campaigns->find_by_id($campaign_id)['collected_amount'];
	}

	/**
	 * A complete, valid donation input.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function donation(array $overrides = array())
	{
		return array_merge(array(
			'donation_amount'	=> 500,
			'donor_name'		=> 'Clara S.',
			'donation_time'		=> 1700000400,
			'donation_public'	=> 1,
		), $overrides);
	}

	/**
	 * THE invariant: for every campaign, the stored total must equal a freshly
	 * computed SUM() over its donation rows.
	 *
	 * Checked across ALL campaigns rather than only the one just touched — a
	 * mutation that wrote to the wrong campaign would otherwise go unseen.
	 *
	 * @param string $context
	 * @return void
	 */
	protected function assert_totals_match_sum($context = '')
	{
		$sql = 'SELECT c.campaign_id, c.collected_amount,
				COALESCE((SELECT SUM(d.donation_amount)
					FROM phpbb_ufdc_donations d
					WHERE d.campaign_id = c.campaign_id), 0) AS actual_sum
			FROM phpbb_ufdc_campaigns c';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach ($rows as $row)
		{
			$this->assertSame(
				(int) $row['actual_sum'],
				(int) $row['collected_amount'],
				sprintf(
					"Total integrity violated for campaign %d.\nStored: %d\nActual SUM: %d\n%s",
					(int) $row['campaign_id'],
					(int) $row['collected_amount'],
					(int) $row['actual_sum'],
					$context
				)
			);
		}
	}
}
