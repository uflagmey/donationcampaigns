<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\repository;

use uflagmey\donationcampaigns\repository\campaign_list_repository;
use uflagmey\donationcampaigns\tests\unit\forum_scoped_auth;

/**
 * The SQL half of the board list's visibility filter.
 *
 * The repository is handed the forums the viewer may list from and core's
 * visibility fragment; it must apply both, plus the rules that belong to the
 * rows themselves (enabled, real topic, not a shadow). One test per rule.
 */
class campaign_list_repository_test extends campaign_list_test_case
{
	/** @var campaign_list_repository */
	protected $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new campaign_list_repository($this->db, 'phpbb_ufdc_campaigns', 'phpbb_topics', 'phpbb_forums');
	}

	/**
	 * What a plain reader of the given forums is handed by the service.
	 *
	 * @param int[] $forum_ids
	 * @param array $grants Extra grants, e.g. m_approve
	 * @return array{0:int[],1:string}
	 */
	protected function reader(array $forum_ids, array $grants = array())
	{
		$auth = new forum_scoped_auth($grants + array('f_read' => $forum_ids));
		$sql = $this->content_visibility($auth)->get_forums_visibility_sql('topic', $forum_ids, 't.');

		return array($forum_ids, $sql);
	}

	protected function listed(array $forum_ids, array $grants = array(), $limit = 25, $offset = 0)
	{
		list($forums, $sql) = $this->reader($forum_ids, $grants);

		return $this->ids($this->repository->find_listed($forums, $sql, $limit, $offset));
	}

	// ------------------------------------------------------------ one rule each

	public function test_lists_an_enabled_campaign_on_a_visible_topic()
	{
		$this->assertContains(1, $this->listed(array(self::FORUM_A)));
	}

	public function test_excludes_a_disabled_campaign()
	{
		$this->assertNotContains(2, $this->listed(array(self::FORUM_A)));
	}

	public function test_excludes_a_campaign_in_a_forum_not_in_the_list()
	{
		$this->assertNotContains(3, $this->listed(array(self::FORUM_A)));
		$this->assertContains(3, $this->listed(array(self::FORUM_A, self::FORUM_B)));
	}

	/**
	 * Even if a visibility fragment were too generous, the forum list itself
	 * is applied: the repository does not rely on the fragment alone.
	 */
	public function test_the_forum_list_is_applied_independently_of_the_fragment()
	{
		$rows = $this->repository->find_listed(array(self::FORUM_A), '1 = 1', 25, 0);

		$this->assertNotContains(3, $this->ids($rows));
		$this->assertNotContains(8, $this->ids($rows));
	}

	public function test_excludes_a_campaign_on_a_moved_shadow()
	{
		$this->assertNotContains(4, $this->listed(array(self::FORUM_A)));
	}

	public function test_excludes_an_unapproved_topic_for_a_reader()
	{
		$this->assertNotContains(5, $this->listed(array(self::FORUM_A)));
	}

	public function test_excludes_a_soft_deleted_topic_for_a_reader()
	{
		$this->assertNotContains(6, $this->listed(array(self::FORUM_A)));
	}

	public function test_includes_unapproved_and_soft_deleted_topics_for_m_approve()
	{
		$ids = $this->listed(array(self::FORUM_A), array('m_approve' => array(self::FORUM_A)));

		$this->assertContains(5, $ids);
		$this->assertContains(6, $ids);
		// The other rules still hold for a moderator.
		$this->assertNotContains(2, $ids);
		$this->assertNotContains(4, $ids);
	}

	public function test_excludes_an_orphaned_campaign_without_its_topic()
	{
		$this->assertNotContains(7, $this->listed(array(self::FORUM_A, self::FORUM_B, self::FORUM_LOCKED)));
	}

	public function test_an_empty_forum_list_returns_nothing_without_querying()
	{
		$this->assertSame(array(), $this->repository->find_listed(array(), '1 = 1', 25, 0));
		$this->assertSame(0, $this->repository->count_listed(array(), '1 = 1'));
		$this->assertSame(array(), $this->db->queries, 'An empty forum list must not reach the database');
	}

	// ------------------------------------------------------- order and paging

	public function test_newest_first()
	{
		$this->assertSame(array(8, 3, 1), $this->listed(array(self::FORUM_A, self::FORUM_B, self::FORUM_LOCKED)));
	}

	public function test_the_campaign_id_breaks_a_tie_in_creation_time()
	{
		$this->insert_topic(70, self::FORUM_A);
		$this->insert_campaign(9, 70, 1, array('campaign_created' => 1700000001));

		// Campaign 1 and 9 share the creation time; the higher id is newer.
		$this->assertSame(array(9, 1), $this->listed(array(self::FORUM_A)));
	}

	public function test_limit_and_offset_page_through_the_filtered_set()
	{
		$this->seed_many(30);

		$first = $this->listed(array(self::FORUM_A), array(), 25, 0);
		$second = $this->listed(array(self::FORUM_A), array(), 25, 25);

		$this->assertCount(25, $first);
		// 30 extra + campaign 1 = 31 listable rows in forum A.
		$this->assertCount(6, $second);
		$this->assertSame(array(), array_intersect($first, $second));
		$this->assertSame(1, end($second));
	}

	public function test_the_count_matches_the_filtered_set_not_the_table()
	{
		$this->seed_many(30);

		list($forums, $sql) = $this->reader(array(self::FORUM_A));

		$this->assertSame(31, $this->repository->count_listed($forums, $sql));
		$this->assertCount(31, $this->repository->find_listed($forums, $sql, 100, 0));
	}

	// ------------------------------------------------------------- the rows

	public function test_rows_carry_the_forum_and_typed_values()
	{
		list($forums, $sql) = $this->reader(array(self::FORUM_A));
		$rows = $this->repository->find_listed($forums, $sql, 25, 0);

		$this->assertSame(array(
			'campaign_id'			=> 1,
			'topic_id'				=> 10,
			'campaign_title'		=> 'Campaign 1',
			'target_amount'			=> 10000,
			'collected_amount'		=> 2500,
			'show_donation_count'	=> true,
			'campaign_created'		=> 1700000001,
			'forum_id'				=> self::FORUM_A,
			// As core stored it; the template prints it without |e.
			'forum_name'			=> 'Spenden &amp; Hilfe',
		), $rows[0]);
	}

	/**
	 * The list never sees the description, the link or anything about donors:
	 * what is not selected cannot leak into the page.
	 */
	public function test_rows_carry_no_description_link_or_donor_settings()
	{
		list($forums, $sql) = $this->reader(array(self::FORUM_A));
		$row = $this->repository->find_listed($forums, $sql, 25, 0)[0];

		foreach (array('campaign_desc', 'external_url', 'external_link_text', 'show_donor_names', 'show_donation_date', 'desc_bbcode_uid') as $field)
		{
			$this->assertArrayNotHasKey($field, $row);
		}
	}

	public function test_table_names_are_injected()
	{
		$repository = new campaign_list_repository($this->db, 'custom_campaigns', 'custom_topics', 'custom_forums');

		// The tables do not exist; what matters is the statement issued.
		$this->db->sql_return_on_error(true);
		$repository->count_listed(array(self::FORUM_A), '1 = 1');
		$this->db->sql_return_on_error(false);

		$this->assertStringContainsString('custom_campaigns', end($this->db->queries));
		$this->assertStringContainsString('custom_topics', end($this->db->queries));
		$this->assertStringContainsString('custom_forums', end($this->db->queries));
	}
}
