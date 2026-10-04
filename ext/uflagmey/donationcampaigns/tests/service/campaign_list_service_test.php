<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\repository\campaign_list_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\service\campaign_list_service;
use uflagmey\donationcampaigns\tests\repository\campaign_list_test_case;
use uflagmey\donationcampaigns\tests\unit\forum_scoped_auth;

/**
 * Which forums a viewer may list from — the permission half of the board
 * list's visibility filter. The SQL half is campaign_list_repository_test.
 *
 * Real repositories, a real content_visibility; only the permissions and the
 * session's unlocked password forums are doubles.
 */
class campaign_list_service_test extends campaign_list_test_case
{
	/** @var forum_scoped_auth */
	protected $auth;

	/** @var passworded_user */
	protected $user;

	/**
	 * @param array $grants
	 * @param int[] $locked Password forums this session has NOT unlocked
	 * @return campaign_list_service
	 */
	protected function service(array $grants, array $locked = array())
	{
		global $phpbb_root_path, $phpEx;

		$this->auth = new forum_scoped_auth($grants);

		$language = new \phpbb\language\language(new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx));
		$this->user = new passworded_user($language, '\phpbb\datetime');
		$this->user->locked = array_combine($locked, $locked) ?: array();

		return new campaign_list_service(
			$this->auth,
			$this->content_visibility($this->auth),
			$this->user,
			new campaign_list_repository($this->db, 'phpbb_ufdc_campaigns', 'phpbb_topics', 'phpbb_forums'),
			new donation_repository($this->db, 'phpbb_ufdc_donations')
		);
	}

	protected function listed(campaign_list_service $service)
	{
		return $this->ids($service->list_public(25, 0));
	}

	protected function add_donation($campaign_id)
	{
		$this->db->sql_query('INSERT INTO phpbb_ufdc_donations ' . $this->db->sql_build_array('INSERT', array(
			'campaign_id' => $campaign_id, 'donation_amount' => 500, 'donor_name' => 'Secret Donor',
			'donation_time' => 1700000100, 'donation_public' => 1,
			'donation_created' => 1700000100, 'donation_updated' => 1700000100,
		)));
	}

	// ------------------------------------------------------------- f_read

	public function test_lists_only_forums_with_f_read()
	{
		$this->assertSame(array(1), $this->listed($this->service(array('f_read' => array(self::FORUM_A)))));
		$this->assertSame(array(3, 1), $this->listed($this->service(array('f_read' => array(self::FORUM_A, self::FORUM_B)))));
	}

	/**
	 * A guest is just a viewer with the guest group's f_read set.
	 */
	public function test_a_guest_sees_only_guest_readable_forums()
	{
		$service = $this->service(array('f_read' => array(self::FORUM_B)));

		$this->assertSame(array(3), $this->listed($service));
		$this->assertSame(1, $service->count_public());
	}

	// ----------------------------------------------------- password forums (rule 5)

	public function test_excludes_a_password_forum_this_session_has_not_unlocked()
	{
		$service = $this->service(array('f_read' => true), array(self::FORUM_LOCKED));

		$this->assertNotContains(8, $this->listed($service));
		$this->assertSame(2, $service->count_public());
	}

	public function test_includes_a_password_forum_this_session_has_unlocked()
	{
		$service = $this->service(array('f_read' => true), array());

		$this->assertContains(8, $this->listed($service));
	}

	public function test_only_locked_forums_readable_means_nothing_listed()
	{
		$service = $this->service(array('f_read' => array(self::FORUM_LOCKED)), array(self::FORUM_LOCKED));

		$this->assertSame(array(), $service->list_public(25, 0));
		$this->assertSame(0, $service->count_public());
		$this->assertSame(array(), $this->db->queries);
	}

	// ------------------------------------------------- m_approve without f_read

	/**
	 * core's get_forums_visibility_sql() intersects the m_approve forums with
	 * the given list only when that list is non-empty. Here it is not empty:
	 * m_approve on forum A must not reveal forum A to someone who cannot read it.
	 */
	public function test_m_approve_without_f_read_reveals_nothing_beside_readable_forums()
	{
		$service = $this->service(array('f_read' => array(self::FORUM_B), 'm_approve' => array(self::FORUM_A)));

		$this->assertSame(array(3), $this->listed($service));
		$this->assertSame(1, $service->count_public());
	}

	/**
	 * The dangerous case: an EMPTY readable list would make core's fragment
	 * open every m_approve forum. The service must never get that far.
	 */
	public function test_no_readable_forum_lists_nothing_and_never_asks_for_visibility()
	{
		$service = $this->service(array('m_approve' => array(self::FORUM_A)));

		$this->assertSame(array(), $service->list_public(25, 0));
		$this->assertSame(0, $service->count_public());
		$this->assertNotContains('m_approve', $this->auth->checked_getf, 'Core visibility was evaluated for an empty forum list');
		$this->assertSame(array(), $this->db->queries);
	}

	public function test_a_moderator_who_reads_the_forum_sees_unapproved_topics()
	{
		$service = $this->service(array('f_read' => array(self::FORUM_A), 'm_approve' => array(self::FORUM_A)));

		$this->assertSame(array(6, 5, 1), $this->listed($service));
	}

	// ------------------------------------------------------- donation counts

	public function test_the_donation_count_is_given_only_where_the_campaign_shows_it()
	{
		$this->add_donation(1);
		$this->add_donation(1);
		$this->add_donation(3);
		$this->db->sql_query('UPDATE phpbb_ufdc_campaigns SET show_donation_count = 0 WHERE campaign_id = 3');
		$this->db->forget();

		$rows = $this->service(array('f_read' => array(self::FORUM_A, self::FORUM_B)))->list_public(25, 0);
		$counts = array_column($rows, 'donation_count', 'campaign_id');

		$this->assertSame(array(3 => 0, 1 => 2), $counts);
	}

	public function test_a_campaign_without_donations_counts_zero()
	{
		$rows = $this->service(array('f_read' => array(self::FORUM_A)))->list_public(25, 0);

		$this->assertSame(0, $rows[0]['donation_count']);
	}

	public function test_rows_carry_no_donor_data()
	{
		$this->add_donation(1);

		$rows = $this->service(array('f_read' => array(self::FORUM_A)))->list_public(25, 0);

		$this->assertStringNotContainsString('Secret Donor', serialize($rows));
	}

	// ---------------------------------------------------------- pagination

	public function test_count_and_list_agree()
	{
		$this->seed_many(30);
		$service = $this->service(array('f_read' => array(self::FORUM_A)));

		$this->assertSame(31, $service->count_public());
		$this->assertCount(25, $service->list_public(25, 0));
		$this->assertCount(6, $service->list_public(25, 25));
	}
}
