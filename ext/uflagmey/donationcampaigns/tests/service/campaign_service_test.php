<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;
use uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema;

/**
 * Campaign business rules and cleanup coordination.
 *
 * The service owns validation, writable-field policy, transaction boundaries
 * and cleanup ordering. It owns no SQL — every persistence operation goes
 * through a repository, and these tests use the real repositories against a
 * real database so that the ordering guarantees are genuinely exercised.
 */
class campaign_service_test extends \phpbb_test_case
{
	/** @var recording_driver */
	protected $db;

	/** @var \phpbb\db\tools\tools */
	protected $tools;

	/** @var campaign_service */
	protected $service;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	/** @var topic_repository */
	protected $topics;

	/** @var fake_description_formatter */
	protected $formatter;

	/** @var string */
	protected $db_file;

	public function setUp(): void
	{
		parent::setUp();

		if (!extension_loaded('sqlite3'))
		{
			$this->markTestSkipped('sqlite3 extension is required');
		}

		$this->db_file = sys_get_temp_dir() . '/ufdc_campaign_service_' . getmypid() . '_' . uniqid() . '.sqlite3';

		$this->db = new recording_driver();
		$this->db->sql_connect($this->db_file, '', '', '', '', false, false);
		$this->tools = new \phpbb\db\tools\tools($this->db);

		$this->create_schema();
		$this->seed();

		$this->campaigns = new campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$this->donations = new donation_repository($this->db, 'phpbb_ufdc_donations');
		$this->topics = new topic_repository($this->db, 'phpbb_topics');

		$this->formatter = new fake_description_formatter();

		$this->service = new campaign_service(
			$this->db,
			$this->campaigns,
			$this->donations,
			$this->topics,
			$this->formatter
		);

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
	 * The extension's two tables plus phpBB's real topics table, the latter
	 * built from core's own baseline migration rather than approximated.
	 */
	protected function create_schema()
	{
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

		$reflection = new \ReflectionClass('\phpbb\db\migration\data\v30x\release_3_0_0');
		$baseline = $reflection->newInstanceWithoutConstructor();
		$core_schema = $baseline->update_schema();

		$this->tools->perform_schema_changes(array('add_tables' => array(
			'phpbb_topics' => $core_schema['add_tables']['topics'],
		)));
	}

	/**
	 * Forum 2 holds topics 10 and 20; forum 3 holds topics 30 and 40.
	 * Campaign 1 is enabled on topic 10 and has three donations summing to
	 * 2500; campaign 2 is disabled on topic 20 and has none. Topics 30 and 40
	 * are free, so creation tests have somewhere to go.
	 */
	protected function seed()
	{
		$topics = array(
			array('topic_id' => 10, 'forum_id' => 2),
			array('topic_id' => 20, 'forum_id' => 2),
			array('topic_id' => 30, 'forum_id' => 3),
			array('topic_id' => 40, 'forum_id' => 3),
			// A shadow left behind by moving topic 10. Not a campaign host.
			array('topic_id' => 50, 'forum_id' => 3, 'topic_moved_id' => 10),
		);

		foreach ($topics as $topic)
		{
			// topic_visibility does not exist in the 3.0.0 baseline this schema
			// is built from — it replaced topic_approved in 3.1. Neither is
			// needed here: topic_repository reads topic_id and forum_id only.
			$this->db->sql_query('INSERT INTO phpbb_topics ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'topic_title'		=> 'Topic ' . $topic['topic_id'],
				'topic_poster'		=> 2,
				'topic_time'		=> 1700000000,
				'topic_moved_id'	=> 0,
			), $topic)));
		}

		$campaigns = array(
			array('campaign_id' => 1, 'topic_id' => 10, 'campaign_title' => 'Server fund', 'target_amount' => 100000, 'collected_amount' => 2500, 'campaign_enabled' => 1),
			array('campaign_id' => 2, 'topic_id' => 20, 'campaign_title' => 'Archive restoration', 'target_amount' => 50000, 'collected_amount' => 0, 'campaign_enabled' => 0),
		);

		foreach ($campaigns as $campaign)
		{
			$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array_merge(array(
				'campaign_desc'			=> '',
				'desc_bbcode_uid'		=> '',
				'desc_bbcode_bitfield'	=> '',
				'desc_bbcode_options'	=> 7,
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
	 * A complete, valid creation input, so each test states only what it varies.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function input(array $overrides = array())
	{
		return array_merge(array(
			'topic_id'			=> 30,
			'campaign_title'	=> 'Legal fund',
			'target_amount'		=> 25000,
			'campaign_enabled'	=> 1,
		), $overrides);
	}

	protected function orphaned_donations()
	{
		$sql = 'SELECT COUNT(d.donation_id) AS orphans
			FROM phpbb_ufdc_donations d
			LEFT JOIN phpbb_ufdc_campaigns c ON c.campaign_id = d.campaign_id
			WHERE c.campaign_id IS NULL';
		$result = $this->db->sql_query($sql);
		$orphans = (int) $this->db->sql_fetchfield('orphans');
		$this->db->sql_freeresult($result);

		return $orphans;
	}

	// ------------------------------------------------------------ validation

	public function test_validate_accepts_a_complete_valid_input()
	{
		$this->assertSame(array(), $this->service->validate($this->input()));
	}

	public function test_validate_rejects_an_empty_title()
	{
		$errors = $this->service->validate($this->input(array('campaign_title' => '   ')));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED', $errors);
	}

	public function test_validate_rejects_an_overlong_title()
	{
		$errors = $this->service->validate($this->input(array('campaign_title' => str_repeat('a', 256))));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG', $errors);
	}

	public function test_validate_accepts_a_title_of_exactly_the_column_width()
	{
		$errors = $this->service->validate($this->input(array('campaign_title' => str_repeat('a', 255))));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG', $errors);
	}

	/**
	 * The column is 255 characters, and utf8_strlen counts characters rather
	 * than bytes. A multibyte title must not be rejected for being long in
	 * bytes when it is short in characters.
	 */
	public function test_validate_measures_the_title_in_characters_not_bytes()
	{
		$errors = $this->service->validate($this->input(array('campaign_title' => str_repeat('ä', 200))));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG', $errors);
	}

	public function target_rejection_data()
	{
		return array(
			'zero'				=> array(0),
			'negative'			=> array(-1),
			'large negative'	=> array(-100000),
			'missing'			=> array(null),
			'non numeric'		=> array('abc'),
		);
	}

	/**
	 * @dataProvider target_rejection_data
	 */
	public function test_validate_rejects_a_non_positive_target($target)
	{
		$input = $this->input();
		$input['target_amount'] = $target;

		$errors = $this->service->validate($input);

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE', $errors);
	}

	public function test_validate_accepts_the_maximum_target()
	{
		$errors = $this->service->validate(
			$this->input(array('target_amount' => campaign_service::MAX_TARGET_AMOUNT)),
			true
		);

		$this->assertSame(array(), $errors);
	}

	public function test_validate_rejects_a_target_above_the_column_ceiling()
	{
		$errors = $this->service->validate(
			$this->input(array('target_amount' => campaign_service::MAX_TARGET_AMOUNT + 1)),
			true
		);

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE', $errors);
	}

	public function test_validate_rejects_a_nonexistent_topic()
	{
		$errors = $this->service->validate($this->input(array('topic_id' => 99999)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND', $errors);
	}

	public function test_validate_rejects_a_missing_topic_id()
	{
		$errors = $this->service->validate($this->input(array('topic_id' => 0)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED', $errors);
	}

	/**
	 * One campaign per topic. The unique index enforces this in the database;
	 * this check exists so the administrator sees a sentence rather than a
	 * raw constraint violation.
	 */
	public function test_validate_rejects_a_topic_that_already_has_a_campaign()
	{
		$errors = $this->service->validate($this->input(array('topic_id' => 10)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN', $errors);
	}

	/**
	 * Editing a campaign without moving it must not report the campaign as a
	 * duplicate of itself — otherwise no campaign could ever be edited.
	 */
	public function test_validate_does_not_report_a_campaign_as_its_own_duplicate()
	{
		$errors = $this->service->validate(
			$this->input(array('topic_id' => 10, 'campaign_title' => 'Renamed')),
			1
		);

		$this->assertSame(array(), $errors);
	}

	public function test_validate_still_rejects_moving_a_campaign_onto_an_occupied_topic()
	{
		// Campaign 1 tries to move onto topic 20, which campaign 2 holds.
		$errors = $this->service->validate($this->input(array('topic_id' => 20)), 1);

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN', $errors);
	}

	public function test_validate_reports_every_error_at_once()
	{
		$errors = $this->service->validate(array(
			'campaign_title'	=> '',
			'topic_id'			=> 99999,
			'target_amount'		=> 0,
			'external_url'		=> 'javascript:alert(1)',
		));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED', $errors);
		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE', $errors);
		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND', $errors);
		$this->assertContains('DONATIONCAMPAIGNS_ERROR_URL_INVALID', $errors);
	}

	/**
	 * Errors are language keys, never display strings, so the ACP can render
	 * them in the administrator's own language.
	 */
	public function test_validation_errors_are_language_keys()
	{
		$errors = $this->service->validate($this->input(array('campaign_title' => '')));

		foreach ($errors as $error)
		{
			$this->assertMatchesRegularExpression('/^DONATIONCAMPAIGNS_ERROR_[A-Z_]+$/', $error);
		}
	}

	/**
	 * A key with no string renders as the raw key in the ACP, which looks like
	 * a crash. The keys are gathered by actually provoking each failure rather
	 * than listed by hand, so this cannot go stale when a rule is added.
	 */
	public function test_every_validation_error_key_has_an_english_string()
	{
		$emitted = array_merge(
			$this->service->validate(array(
				'campaign_title'	=> '',
				'topic_id'			=> 0,
				'target_amount'		=> 0,
				'external_url'		=> 'javascript:alert(1)',
			)),
			$this->service->validate($this->input(array('topic_id' => 99999))),
			$this->service->validate($this->input(array('topic_id' => 10))),
			$this->service->validate($this->input(array('campaign_title' => str_repeat('a', 256)))),
			$this->service->validate($this->input(array('target_amount' => campaign_service::MAX_TARGET_AMOUNT + 1)))
		);

		$lang = array();
		include __DIR__ . '/../../language/en/common.php';

		$this->assertNotEmpty($emitted);

		foreach (array_unique($emitted) as $key)
		{
			$this->assertArrayHasKey($key, $lang, "No English string for {$key}");
		}
	}

	// ------------------------------------------------------------------ URLs

	public function accepted_url_data()
	{
		return array(
			'https'					=> array('https://example.org/donate'),
			'http'					=> array('http://example.org/donate'),
			'uppercase scheme'		=> array('HTTPS://example.org/donate'),
			'with query string'		=> array('https://example.org/d?campaign=1&amp;x=2'),
			'with port'				=> array('https://example.org:8443/donate'),
		);
	}

	/**
	 * @dataProvider accepted_url_data
	 */
	public function test_validate_accepts_allowlisted_url_schemes($url)
	{
		$errors = $this->service->validate($this->input(array('external_url' => $url)));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_URL_INVALID', $errors);
	}

	public function rejected_url_data()
	{
		return array(
			'javascript'			=> array('javascript:alert(1)'),
			'mixed case javascript'	=> array('JaVaScRiPt:alert(1)'),
			'leading whitespace'	=> array("  javascript:alert(1)"),
			'embedded tab'			=> array("java\tscript:alert(1)"),
			'data'					=> array('data:text/html;base64,PHN2Zz4='),
			'vbscript'				=> array('vbscript:msgbox(1)'),
			'file'					=> array('file:///etc/passwd'),
			'ftp'					=> array('ftp://example.org/donate'),
			'protocol relative'		=> array('//example.org/donate'),
			'schemeless'			=> array('example.org/donate'),
			'relative path'			=> array('/donate'),
		);
	}

	/**
	 * @dataProvider rejected_url_data
	 */
	public function test_validate_rejects_url_schemes_outside_the_allowlist($url)
	{
		$errors = $this->service->validate($this->input(array('external_url' => $url)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_URL_INVALID', $errors);
	}

	/**
	 * The link is optional. Absent and empty are both acceptable, and neither
	 * may be confused with an invalid one.
	 */
	public function test_validate_accepts_a_missing_url()
	{
		$this->assertSame(array(), $this->service->validate($this->input()));
	}

	public function test_validate_accepts_an_empty_url()
	{
		$this->assertSame(array(), $this->service->validate($this->input(array('external_url' => ''))));
	}

	public function test_validate_accepts_a_whitespace_only_url_as_absent()
	{
		$this->assertSame(array(), $this->service->validate($this->input(array('external_url' => '   '))));
	}

	// -------------------------------------------------------------- creation

	public function test_create_campaign_persists_and_returns_the_new_id()
	{
		$id = $this->service->create_campaign($this->input());

		$this->assertIsInt($id);
		$this->assertGreaterThan(0, $id);

		$campaign = $this->campaigns->find_by_id($id);
		$this->assertSame('Legal fund', $campaign['campaign_title']);
		$this->assertSame(30, $campaign['topic_id']);
		$this->assertSame(25000, $campaign['target_amount']);
	}

	public function test_create_campaign_stamps_the_timestamps()
	{
		$campaign = $this->campaigns->find_by_id($this->service->create_campaign($this->input()));

		$this->assertGreaterThan(0, $campaign['campaign_created']);
		$this->assertSame($campaign['campaign_created'], $campaign['campaign_updated']);
	}

	/**
	 * The derived-value tampering guard. collected_amount is absent from the
	 * writable-field list, so it cannot reach the repository even when a
	 * caller supplies it. See specification section 9.2.
	 */
	public function test_create_campaign_ignores_a_supplied_collected_amount()
	{
		$id = $this->service->create_campaign($this->input(array('collected_amount' => 999999)));

		$this->assertSame(0, $this->campaigns->find_by_id($id)['collected_amount']);
	}

	public function test_create_campaign_ignores_unknown_fields()
	{
		$id = $this->service->create_campaign($this->input(array(
			'campaign_id'	=> 4242,
			'not_a_column'	=> 'x',
		)));

		$this->assertNotSame(4242, $id);
		$this->assertNotNull($this->campaigns->find_by_id($id));
	}

	public function test_create_campaign_normalises_the_title_and_url()
	{
		$id = $this->service->create_campaign($this->input(array(
			'campaign_title'		=> '  Legal fund  ',
			'external_url'			=> '  https://example.org/donate  ',
			// A URL now requires a label; both are normalised the same way.
			'external_link_text'	=> '  How to donate  ',
		)));

		$campaign = $this->campaigns->find_by_id($id);
		$this->assertSame('Legal fund', $campaign['campaign_title']);
		$this->assertSame('https://example.org/donate', $campaign['external_url']);
		$this->assertSame('How to donate', $campaign['external_link_text']);
	}

	/**
	 * Validation is not merely available to the caller, it is enforced here.
	 * An ACP module that forgot to call validate() would otherwise write an
	 * invalid row, and the rules would live in exactly one place that anyone
	 * could bypass.
	 */
	public function test_create_campaign_rejects_invalid_input()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->create_campaign($this->input(array('topic_id' => 99999)));
	}

	public function test_create_campaign_rejection_carries_a_language_key()
	{
		try
		{
			$this->service->create_campaign($this->input(array('target_amount' => 0)));
			$this->fail('An invalid target was accepted');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertSame('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE', $e->get_language_key());
			$this->assertContains('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE', $e->get_parameters());
		}
	}

	public function test_create_campaign_writes_nothing_when_rejected()
	{
		$before = $this->campaigns->count_all();

		try
		{
			$this->service->create_campaign($this->input(array('topic_id' => 10)));
		}
		catch (donationcampaigns_exception $e)
		{
			// expected
		}

		$this->assertSame($before, $this->campaigns->count_all());
	}

	// ---------------------------------------------------------------- update

	public function test_update_campaign_changes_the_named_row_only()
	{
		$this->service->update_campaign(1, $this->input(array(
			'topic_id'			=> 10,
			'campaign_title'	=> 'Renamed',
			'target_amount'		=> 100000,
		)));

		$this->assertSame('Renamed', $this->campaigns->find_by_id(1)['campaign_title']);
		$this->assertSame('Archive restoration', $this->campaigns->find_by_id(2)['campaign_title']);
	}

	public function test_update_campaign_ignores_a_supplied_collected_amount()
	{
		$before = $this->campaigns->find_by_id(1)['collected_amount'];

		$this->service->update_campaign(1, $this->input(array(
			'topic_id'			=> 10,
			'campaign_title'	=> 'Renamed',
			'target_amount'		=> 100000,
			'collected_amount'	=> 999999,
		)));

		$this->assertSame($before, $this->campaigns->find_by_id(1)['collected_amount']);
	}

	public function test_update_campaign_refreshes_only_the_updated_timestamp()
	{
		$before = $this->campaigns->find_by_id(1);

		$this->service->update_campaign(1, $this->input(array(
			'topic_id'			=> 10,
			'campaign_title'	=> 'Renamed',
			'target_amount'		=> 100000,
		)));

		$after = $this->campaigns->find_by_id(1);
		$this->assertSame($before['campaign_created'], $after['campaign_created']);
		$this->assertGreaterThanOrEqual($before['campaign_updated'], $after['campaign_updated']);
	}

	public function test_update_campaign_allows_keeping_its_own_topic()
	{
		$this->service->update_campaign(1, $this->input(array(
			'topic_id'			=> 10,
			'campaign_title'	=> 'Still on topic 10',
			'target_amount'		=> 100000,
		)));

		$this->assertSame('Still on topic 10', $this->campaigns->find_by_id(1)['campaign_title']);
	}

	public function test_update_campaign_rejects_moving_onto_an_occupied_topic()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->update_campaign(1, $this->input(array('topic_id' => 20)));
	}

	public function test_update_campaign_rejects_invalid_input()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->update_campaign(1, $this->input(array('topic_id' => 10, 'target_amount' => 0)));
	}

	// ----------------------------------------------------------------- reads

	public function test_get_campaign_returns_an_enabled_campaign_for_the_acp()
	{
		$campaign = $this->service->get_campaign(1);

		$this->assertNotNull($campaign);
		$this->assertSame('Server fund', $campaign['campaign_title']);
	}

	/**
	 * The ACP must be able to see and edit a disabled campaign — otherwise
	 * disabling one would make it uneditable, and re-enabling impossible.
	 */
	public function test_get_campaign_returns_a_disabled_campaign_for_the_acp()
	{
		$campaign = $this->service->get_campaign(2);

		$this->assertNotNull($campaign);
		$this->assertFalse($campaign['campaign_enabled']);
	}

	public function test_get_campaign_returns_null_when_absent()
	{
		$this->assertNull($this->service->get_campaign(99999));
	}

	public function test_get_campaign_for_topic_returns_an_enabled_campaign()
	{
		$campaign = $this->service->get_campaign_for_topic(10);

		$this->assertNotNull($campaign);
		$this->assertSame('Server fund', $campaign['campaign_title']);
	}

	/**
	 * The public visibility rule. A disabled campaign is invisible on the
	 * front end even though the row still exists.
	 */
	public function test_get_campaign_for_topic_hides_a_disabled_campaign()
	{
		$this->assertNull($this->service->get_campaign_for_topic(20));
		$this->assertNotNull($this->campaigns->find_by_topic_id(20), 'The row must still exist');
	}

	public function test_get_campaign_for_topic_returns_null_for_a_topic_without_a_campaign()
	{
		$this->assertNull($this->service->get_campaign_for_topic(30));
	}

	public function test_get_campaign_for_topic_preserves_the_repository_typing()
	{
		$campaign = $this->service->get_campaign_for_topic(10);

		$this->assertIsInt($campaign['target_amount']);
		$this->assertIsInt($campaign['collected_amount']);
		$this->assertIsBool($campaign['campaign_enabled']);
		$this->assertIsString($campaign['campaign_title']);
	}

	public function test_get_public_donor_list_excludes_non_public_donations()
	{
		$donors = $this->service->get_public_donor_list(1, 25);

		$this->assertCount(2, $donors);

		foreach ($donors as $donor)
		{
			$this->assertNotSame('Bernd K.', $donor['donor_name']);
			$this->assertTrue($donor['donation_public']);
		}
	}

	public function test_get_public_donor_list_honours_the_limit()
	{
		$this->assertCount(1, $this->service->get_public_donor_list(1, 1));
	}

	public function test_get_public_donor_list_is_empty_for_a_campaign_without_donations()
	{
		$this->assertSame(array(), $this->service->get_public_donor_list(2, 25));
	}

	/**
	 * The donor list returns every donation, public and private alike — the
	 * flag names the donor, it does not hide the donation. Anonymising a private
	 * name is the listener's job, not this method's; the private row is returned
	 * here with its stored name intact.
	 */
	public function test_get_donor_list_includes_public_and_private_donations()
	{
		$donors = $this->service->get_donor_list(1, 25);

		$this->assertCount(3, $donors);
		$this->assertContains('Bernd K.', array_column($donors, 'donor_name'));
	}

	public function test_get_donor_list_honours_the_limit()
	{
		$this->assertCount(1, $this->service->get_donor_list(1, 1));
	}

	public function test_get_donor_list_is_empty_for_a_campaign_without_donations()
	{
		$this->assertSame(array(), $this->service->get_donor_list(2, 25));
	}

	// -------------------------------------------------------------- deletion

	public function test_delete_campaign_removes_the_campaign_and_its_donations()
	{
		$this->service->delete_campaign(1);

		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertSame(0, $this->donations->count_by_campaign(1));
		$this->assertSame(0, $this->orphaned_donations());
	}

	public function test_delete_campaign_leaves_other_campaigns_alone()
	{
		$this->service->delete_campaign(1);

		$this->assertNotNull($this->campaigns->find_by_id(2));
	}

	public function test_delete_campaign_of_an_absent_campaign_is_harmless()
	{
		$this->service->delete_campaign(99999);

		$this->assertSame(2, $this->campaigns->count_all());
		$this->assertSame(3, $this->donations->count_by_campaign(1));
	}

	// --------------------------------------------------------------- cleanup

	public function test_purge_for_topics_removes_campaigns_and_donations()
	{
		$removed = $this->service->purge_for_topics(array(10));

		$this->assertSame(1, $removed);
		$this->assertNull($this->campaigns->find_by_id(1));
		$this->assertSame(0, $this->donations->count_by_campaign(1));
	}

	public function test_purge_for_topics_handles_multiple_topics()
	{
		$removed = $this->service->purge_for_topics(array(10, 20));

		$this->assertSame(2, $removed);
		$this->assertSame(0, $this->campaigns->count_all());
	}

	public function test_purge_for_topics_ignores_topics_without_campaigns()
	{
		$removed = $this->service->purge_for_topics(array(10, 99999));

		$this->assertSame(1, $removed);
		$this->assertNotNull($this->campaigns->find_by_id(2));
	}

	public function test_purge_for_topics_with_an_empty_set_is_a_noop()
	{
		$removed = $this->service->purge_for_topics(array());

		$this->assertSame(0, $removed);
		$this->assertSame(2, $this->campaigns->count_all());
		$this->assertSame(3, $this->donations->count_by_campaign(1));
	}

	public function test_purge_for_topics_with_only_unknown_topics_is_a_noop()
	{
		$removed = $this->service->purge_for_topics(array(99998, 99999));

		$this->assertSame(0, $removed);
		$this->assertSame(2, $this->campaigns->count_all());
	}

	public function test_purge_for_topics_leaves_no_orphaned_donations()
	{
		$this->service->purge_for_topics(array(10, 20));

		$this->assertSame(0, $this->orphaned_donations(), 'Donation rows were orphaned by the purge');
	}

	/**
	 * The ordering constraint, asserted on the statements actually issued
	 * rather than only on the end state. Donations key on campaign_id, so if
	 * the campaign rows go first their ids become unresolvable and the
	 * donation rows are orphaned permanently. See specification section 7.3.4.
	 */
	public function test_purge_for_topics_deletes_donations_before_campaigns()
	{
		$this->db->forget();

		$this->service->purge_for_topics(array(10));

		$donations_at = $this->db->first_query_matching('/DELETE FROM phpbb_ufdc_donations/');
		$campaigns_at = $this->db->first_query_matching('/DELETE FROM phpbb_ufdc_campaigns/');

		$this->assertNotNull($donations_at, 'No donation delete was issued');
		$this->assertNotNull($campaigns_at, 'No campaign delete was issued');
		$this->assertLessThan(
			$campaigns_at,
			$donations_at,
			'Campaigns were deleted before their donations, which orphans the donation rows'
		);
	}

	public function test_delete_campaign_deletes_donations_before_the_campaign()
	{
		$this->db->forget();

		$this->service->delete_campaign(1);

		$donations_at = $this->db->first_query_matching('/DELETE FROM phpbb_ufdc_donations/');
		$campaigns_at = $this->db->first_query_matching('/DELETE FROM phpbb_ufdc_campaigns/');

		$this->assertNotNull($donations_at);
		$this->assertNotNull($campaigns_at);
		$this->assertLessThan($campaigns_at, $donations_at);
	}

	// ---------------------------------------------------------- forum cleanup

	/**
	 * The forum listener passes only a forum id. The service resolves the real
	 * topic list itself, because the core event's topic_ids payload lists only
	 * topics WITH attachments. See specification section 7.3.6.
	 */
	public function test_purge_for_forum_resolves_its_topic_ids_internally()
	{
		$removed = $this->service->purge_for_forum(2);

		$this->assertSame(2, $removed);
		$this->assertSame(0, $this->campaigns->count_all());
		$this->assertSame(0, $this->orphaned_donations());
	}

	public function test_purge_for_forum_leaves_other_forums_alone()
	{
		$id = $this->service->create_campaign($this->input(array('topic_id' => 30)));

		$this->service->purge_for_forum(2);

		$this->assertNotNull($this->campaigns->find_by_id($id), 'A campaign in forum 3 was removed');
	}

	public function test_purge_for_forum_with_no_topics_is_a_noop()
	{
		$removed = $this->service->purge_for_forum(9999);

		$this->assertSame(0, $removed);
		$this->assertSame(2, $this->campaigns->count_all());
	}

	public function test_purge_for_forum_with_topics_but_no_campaigns_is_a_noop()
	{
		// Forum 3 holds topics 30 and 40, neither of which has a campaign.
		$removed = $this->service->purge_for_forum(3);

		$this->assertSame(0, $removed);
		$this->assertSame(2, $this->campaigns->count_all());
	}

	// --------------------------------------------------- transaction boundaries

	public function test_delete_campaign_runs_in_one_transaction()
	{
		$this->db->forget();

		$this->service->delete_campaign(1);

		$this->assertSame(array('begin', 'commit'), $this->db->transaction_log);
	}

	public function test_purge_for_topics_runs_in_one_transaction()
	{
		$this->db->forget();

		$this->service->purge_for_topics(array(10, 20));

		$this->assertSame(array('begin', 'commit'), $this->db->transaction_log);
	}

	public function test_purge_for_forum_opens_exactly_one_transaction()
	{
		$this->db->forget();

		$this->service->purge_for_forum(2);

		$this->assertSame(
			array('begin', 'commit'),
			$this->db->transaction_log,
			'purge_for_forum must delegate its boundary, not open a second one'
		);
	}

	/**
	 * A no-op purge must not open a transaction at all. Opening and committing
	 * an empty one on every topic deletion would be pure overhead on the most
	 * common path.
	 */
	public function test_an_empty_purge_opens_no_transaction()
	{
		$this->db->forget();

		$this->service->purge_for_topics(array());

		$this->assertSame(array(), $this->db->transaction_log);
	}

	/**
	 * Single-statement writes are atomic by themselves, so wrapping them adds
	 * a round trip and buys nothing.
	 *
	 * @dataProvider single_statement_write_data
	 */
	public function test_single_statement_writes_open_no_transaction($method, $arguments)
	{
		$this->db->forget();

		call_user_func_array(array($this->service, $method), $arguments);

		$this->assertSame(array(), $this->db->transaction_log);
	}

	public function single_statement_write_data()
	{
		return array(
			'create' => array('create_campaign', array(array(
				'topic_id'			=> 30,
				'campaign_title'	=> 'Legal fund',
				'target_amount'		=> 25000,
			))),
			'update' => array('update_campaign', array(1, array(
				'topic_id'			=> 10,
				'campaign_title'	=> 'Renamed',
				'target_amount'		=> 100000,
			))),
		);
	}

	/**
	 * @dataProvider read_method_data
	 */
	public function test_read_methods_open_no_transaction($method, $arguments)
	{
		$this->db->forget();

		call_user_func_array(array($this->service, $method), $arguments);

		$this->assertSame(array(), $this->db->transaction_log);
	}

	public function read_method_data()
	{
		return array(
			'get_campaign'				=> array('get_campaign', array(1)),
			'get_campaign_for_topic'	=> array('get_campaign_for_topic', array(10)),
			'get_public_donor_list'		=> array('get_public_donor_list', array(1, 25)),
			'get_donor_list'			=> array('get_donor_list', array(1, 25)),
			'validate'					=> array('validate', array(array('campaign_title' => 'x', 'topic_id' => 30, 'target_amount' => 1))),
		);
	}

	// -------------------------------------------------------------- rollback

	/**
	 * If a dependent cleanup step fails, everything the transaction has
	 * already done must be undone. A half-applied purge is the worst possible
	 * outcome: the donations are gone and the campaign still claims them.
	 */
	public function test_purge_for_topics_rolls_back_when_the_campaign_delete_fails()
	{
		$service = new campaign_service(
			$this->db,
			new failing_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			$this->donations,
			$this->topics,
			$this->formatter
		);

		$this->db->forget();

		try
		{
			$service->purge_for_topics(array(10));
			$this->fail('The failing repository did not surface its exception');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('cleanup step failed', $e->getMessage());
		}

		$this->assertSame(array('begin', 'rollback'), $this->db->transaction_log);
		$this->assertSame(3, $this->donations->count_by_campaign(1), 'The donation deletes were not rolled back');
		$this->assertNotNull($this->campaigns->find_by_id(1));
	}

	public function test_delete_campaign_rolls_back_when_the_campaign_delete_fails()
	{
		$service = new campaign_service(
			$this->db,
			new failing_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			$this->donations,
			$this->topics,
			$this->formatter
		);

		$this->db->forget();

		try
		{
			$service->delete_campaign(1);
			$this->fail('The failing repository did not surface its exception');
		}
		catch (\RuntimeException $e)
		{
			// expected
		}

		$this->assertSame(array('begin', 'rollback'), $this->db->transaction_log);
		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertNotNull($this->campaigns->find_by_id(1));
	}

	/**
	 * The failure must not be swallowed. A listener that saw a silent success
	 * would let phpBB finish deleting the topic, leaving the extension's rows
	 * behind with nothing pointing at them.
	 */
	public function test_a_failed_purge_rethrows_rather_than_reporting_success()
	{
		$service = new campaign_service(
			$this->db,
			new failing_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			$this->donations,
			$this->topics,
			$this->formatter
		);

		$this->expectException(\RuntimeException::class);

		$service->purge_for_topics(array(10));
	}

	// ------------------------------------------------------- the ACP listing

	/**
	 * The ACP list needs the topic's title, which lives in a core table. The
	 * service composes the two repositories rather than either of them
	 * learning about the other, and rather than the module doing it.
	 */
	public function test_list_campaigns_returns_campaigns_with_their_topic_titles()
	{
		$rows = $this->service->list_campaigns(25, 0);

		$this->assertCount(2, $rows);

		$titles = array_column($rows, 'topic_title');
		$this->assertContains('Topic 10', $titles);
		$this->assertContains('Topic 20', $titles);
	}

	public function test_list_campaigns_preserves_the_campaign_fields_and_typing()
	{
		$row = $this->service->list_campaigns(25, 0)[0];

		$this->assertIsInt($row['campaign_id']);
		$this->assertIsInt($row['target_amount']);
		$this->assertIsInt($row['collected_amount']);
		$this->assertIsBool($row['campaign_enabled']);
		$this->assertIsString($row['campaign_title']);
		$this->assertIsString($row['topic_title']);
	}

	public function test_list_campaigns_includes_disabled_campaigns()
	{
		$titles = array_column($this->service->list_campaigns(25, 0), 'campaign_title');

		$this->assertContains('Archive restoration', $titles, 'The ACP must show disabled campaigns');
	}

	/**
	 * A campaign whose topic has been removed out of band must still be listed
	 * and therefore still be deletable. Hiding it would leave a row an
	 * administrator can neither see nor remove.
	 */
	public function test_list_campaigns_survives_a_missing_topic()
	{
		$this->db->sql_query('DELETE FROM phpbb_topics WHERE topic_id = 10');

		$rows = $this->service->list_campaigns(25, 0);

		$this->assertCount(2, $rows);

		foreach ($rows as $row)
		{
			$this->assertIsString($row['topic_title']);
		}
	}

	public function test_list_campaigns_is_empty_when_there_are_none()
	{
		$this->service->purge_for_topics(array(10, 20));

		$this->assertSame(array(), $this->service->list_campaigns(25, 0));
	}

	public function test_list_campaigns_honours_limit_and_offset()
	{
		$this->assertCount(1, $this->service->list_campaigns(1, 0));
		$this->assertCount(1, $this->service->list_campaigns(1, 1));
	}

	public function test_list_campaigns_reads_each_topic_title_once()
	{
		$this->db->forget();

		$this->service->list_campaigns(25, 0);

		$title_queries = 0;

		foreach ($this->db->queries as $query)
		{
			if (strpos($query, 'topic_title') !== false)
			{
				$title_queries++;
			}
		}

		$this->assertSame(1, $title_queries, 'Topic titles must be fetched in one query, not one per campaign');
	}

	public function test_count_campaigns_counts_every_campaign()
	{
		$this->assertSame(2, $this->service->count_campaigns());
	}

	public function test_list_campaigns_opens_no_transaction()
	{
		$this->db->forget();

		$this->service->list_campaigns(25, 0);

		$this->assertSame(array(), $this->db->transaction_log);
	}

	// ------------------------------------------- the description contract

	/**
	 * ISSUE D / C — the BBCode description contract.
	 *
	 * The description is the only field with markup, and it is the only field
	 * that must NOT be escaped at output: it goes out through
	 * generate_text_for_display(), which does not sanitise. Safety therefore
	 * has to come from the way it is STORED, and the service is what
	 * guarantees that. A caller cannot store a description any other way.
	 */
	public function test_a_description_is_encoded_for_storage_on_create()
	{
		$id = $this->service->create_campaign($this->input(array(
			'campaign_desc' => 'Help <b>us</b> buy a server',
		)));

		$stored = $this->campaigns->find_by_id($id);

		// What reaches the column is the FORMATTER's output, never the raw
		// submission. The exact encoding is phpBB's business and is verified
		// against the real pipeline on the board; what the service owes is
		// that the text always goes through it, and that its metadata comes
		// back with it.
		$this->assertNotEmpty($this->formatter->storage_calls, 'The description bypassed the formatter');
		$this->assertSame('Help <b>us</b> buy a server', $this->formatter->storage_calls[0][0]);
		$this->assertSame('uid1234', $stored['desc_bbcode_uid']);
		$this->assertSame('QQ==', $stored['desc_bbcode_bitfield']);
	}

	public function test_a_description_is_encoded_for_storage_on_update()
	{
		$this->service->update_campaign(1, $this->input(array(
			'topic_id'		=> 10,
			'campaign_desc'	=> 'New <i>text</i>',
		)));

		$stored = $this->campaigns->find_by_id(1);

		$this->assertNotEmpty($this->formatter->storage_calls, 'The description bypassed the formatter');
		$this->assertSame('New <i>text</i>', end($this->formatter->storage_calls)[0]);
		$this->assertSame('uid1234', $stored['desc_bbcode_uid']);
	}

	/**
	 * The metadata is produced BY the encoder, never accepted from input.
	 * A caller who supplies a uid and bitfield is describing markup the stored
	 * text does not contain, which is how a crafted payload would be smuggled
	 * past the display path.
	 */
	public function test_bbcode_metadata_cannot_be_supplied_by_the_caller()
	{
		$id = $this->service->create_campaign($this->input(array(
			'campaign_desc'			=> 'Plain text',
			'desc_bbcode_uid'		=> 'attacker',
			'desc_bbcode_bitfield'	=> 'ZZZZ',
			'desc_bbcode_options'	=> 999,
		)));

		$stored = $this->campaigns->find_by_id($id);

		$this->assertSame('uid1234', $stored['desc_bbcode_uid']);
		$this->assertSame('QQ==', $stored['desc_bbcode_bitfield']);
		$this->assertSame(7, $stored['desc_bbcode_options']);
	}

	public function test_an_empty_description_stores_empty_metadata()
	{
		$stored = $this->campaigns->find_by_id($this->service->create_campaign($this->input()));

		$this->assertSame('', $stored['campaign_desc']);
		$this->assertSame('', $stored['desc_bbcode_uid']);
		$this->assertSame('', $stored['desc_bbcode_bitfield']);
	}

	/**
	 * The edit form needs the source an administrator typed, not the stored
	 * representation. Round-tripping through the encoder and back must not
	 * accumulate escaping.
	 */
	/**
	 * generate_text_for_edit() hands back text HTML-escaped exactly once, for
	 * a textarea to emit raw. So the value is NOT the plain source -- it is
	 * the source with entities, which the browser decodes on display.
	 */
	public function test_a_description_decodes_back_for_editing()
	{
		$id = $this->service->create_campaign($this->input(array(
			'campaign_desc' => 'Help <b>us</b> & thanks',
		)));

		$editable = $this->service->decode_description($this->campaigns->find_by_id($id));

		$this->assertSame('Help &lt;b&gt;us&lt;/b&gt; &amp; thanks', $editable);

		// Emitted raw into a textarea, that is exactly what was typed.
		$this->assertSame('Help <b>us</b> & thanks', html_entity_decode($editable, ENT_QUOTES, 'UTF-8'));
	}

	public function test_decoding_an_empty_description_is_an_empty_string()
	{
		$this->assertSame('', $this->service->decode_description($this->campaigns->find_by_id(2)));
	}

	// ------------------------------------------- the duplicate-topic race

	/**
	 * ISSUE B — the unique index is the authoritative protection.
	 *
	 * validate() checks for an existing campaign, but two concurrent requests
	 * can both pass that check. The UNIQUE index on topic_id then rejects one
	 * of them, and this test pins that the rejection surfaces as the SAME
	 * language key the pre-check uses, rather than as a raw driver error.
	 *
	 * Simulated by inserting the row directly between validation and insert,
	 * which is exactly what the losing request experiences.
	 */
	public function test_a_concurrent_duplicate_is_reported_as_a_duplicate_not_a_driver_error()
	{
		$racing = new racing_campaign_repository($this->db, 'phpbb_ufdc_campaigns');
		$racing->occupy_topic = 30;

		$service = new campaign_service($this->db, $racing, $this->donations, $this->topics, $this->formatter);

		try
		{
			$service->create_campaign($this->input(array('topic_id' => 30)));
			$this->fail('The duplicate insert was not rejected');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertSame('DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN', $e->get_language_key());
		}
	}

	public function test_a_failed_insert_that_is_not_a_duplicate_is_not_disguised()
	{
		$failing = new failing_insert_campaign_repository($this->db, 'phpbb_ufdc_campaigns');

		$service = new campaign_service($this->db, $failing, $this->donations, $this->topics, $this->formatter);

		$this->expectException(\RuntimeException::class);

		$service->create_campaign($this->input(array('topic_id' => 30)));
	}

	// --------------------------------------------------------- shadow topics

	/**
	 * A campaign cannot be attached to a moved-topic shadow.
	 *
	 * phpBB answers 404 "The requested topic does not exist" for a shadow —
	 * verified against 3.3.17 — so a campaign there would render nowhere and
	 * be reachable by nobody, while still holding donation rows and counting
	 * in the ACP. It is reported with the SAME key as a missing topic, because
	 * that is exactly what core considers it.
	 */
	public function test_a_shadow_topic_is_rejected_as_a_campaign_host()
	{
		$errors = $this->service->validate($this->input(array('topic_id' => 50)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND', $errors);
	}

	public function test_a_campaign_cannot_be_created_on_a_shadow_topic()
	{
		$before = $this->campaigns->count_all();

		try
		{
			$this->service->create_campaign($this->input(array('topic_id' => 50)));
			$this->fail('A campaign was created on a shadow topic');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertSame('DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND', $e->get_language_key());
		}

		$this->assertSame($before, $this->campaigns->count_all());
	}

	public function test_a_campaign_cannot_be_moved_onto_a_shadow_topic()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->update_campaign(1, $this->input(array('topic_id' => 50)));
	}

	/**
	 * The rule prevents NEW campaigns on shadows. One attached before the rule
	 * existed must still be cleaned up with its forum, or it would be
	 * unreachable AND undeletable.
	 */
	public function test_a_pre_existing_campaign_on_a_shadow_is_still_purged_with_its_forum()
	{
		$this->db->sql_query('INSERT INTO phpbb_ufdc_campaigns ' . $this->db->sql_build_array('INSERT', array(
			'campaign_id' => 9, 'topic_id' => 50, 'campaign_title' => 'Legacy shadow campaign',
			'campaign_desc' => '', 'desc_bbcode_uid' => '', 'desc_bbcode_bitfield' => '',
			'desc_bbcode_options' => 7, 'target_amount' => 1000, 'collected_amount' => 0,
			'campaign_enabled' => 1, 'show_donor_names' => 1, 'show_donation_count' => 1,
			'external_url' => '', 'campaign_created' => 1700000000, 'campaign_updated' => 1700000000,
		)));

		$removed = $this->service->purge_for_forum(3);

		$this->assertSame(1, $removed);
		$this->assertNull($this->campaigns->find_by_id(9), 'A campaign on a shadow survived its forum being deleted');
	}

	// ------------------------------------------------- the button's label

	/**
	 * external_url is a destination the administrator chooses; this is the
	 * text on the button pointing at it. Nothing here knows about any payment
	 * provider, and nothing about the confirmed-donation flow changes.
	 */
	public function test_a_custom_link_text_is_stored()
	{
		$id = $this->service->create_campaign($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'Request bank details',
		)));

		$this->assertSame('Request bank details', $this->campaigns->find_by_id($id)['external_link_text']);
	}

	public function test_link_text_is_trimmed_before_storage()
	{
		$id = $this->service->create_campaign($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> "  Über PayPal spenden \t",
		)));

		$this->assertSame('Über PayPal spenden', $this->campaigns->find_by_id($id)['external_link_text']);
	}

	public function test_link_text_at_the_maximum_length_is_accepted()
	{
		$errors = $this->service->validate($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> str_repeat('a', campaign_service::MAX_LINK_TEXT_LENGTH),
		)));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG', $errors);
	}

	public function test_link_text_one_character_over_the_maximum_is_rejected()
	{
		$errors = $this->service->validate($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> str_repeat('a', campaign_service::MAX_LINK_TEXT_LENGTH + 1),
		)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG', $errors);
	}

	/**
	 * Characters, not bytes: a multibyte label that fits the column must not
	 * be rejected for being wide.
	 */
	public function test_the_length_limit_counts_characters_not_bytes()
	{
		$errors = $this->service->validate($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> str_repeat('ü', campaign_service::MAX_LINK_TEXT_LENGTH),
		)));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG', $errors);
	}

	public function test_the_service_limit_matches_the_column_width()
	{
		$this->assertSame(
			\uflagmey\donationcampaigns\migrations\v10x\m6_campaign_link_text::MAX_LENGTH,
			campaign_service::MAX_LINK_TEXT_LENGTH,
			'Validation and the column disagree about how long a label may be'
		);
	}

	/**
	 * A URL with no label would render a button with nothing on it. Rather
	 * than substituting something at render time -- which hides that the
	 * administrator submitted nothing -- it is refused here.
	 *
	 * @dataProvider blank_link_text_data
	 */
	public function test_a_url_without_link_text_is_rejected($text)
	{
		$errors = $this->service->validate($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> $text,
		)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED', $errors);
	}

	public function blank_link_text_data()
	{
		return array(
			'empty'			=> array(''),
			'spaces'		=> array('   '),
			'tab'			=> array("\t"),
			'newline'		=> array("\n"),
			'missing'		=> array(null),
		);
	}

	/**
	 * With no URL there is no button, so there is nothing to label.
	 */
	public function test_a_blank_link_text_is_accepted_when_there_is_no_url()
	{
		$errors = $this->service->validate($this->input(array(
			'external_url'			=> '',
			'external_link_text'	=> '',
		)));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED', $errors);
		$this->assertSame(array(), $errors);
	}

	/**
	 * Raw storage, escaped at output. Escaping on the way in would store the
	 * entities and then escape them again for display.
	 */
	public function test_markup_in_link_text_is_stored_raw()
	{
		$hostile = '<script>alert(1)</script> & "quotes" &amp;';

		$id = $this->service->create_campaign($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> $hostile,
		)));

		$this->assertSame($hostile, $this->campaigns->find_by_id($id)['external_link_text']);
	}

	public function test_bbcode_in_link_text_is_not_interpreted()
	{
		$id = $this->service->create_campaign($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> '[b]Donate[/b]',
		)));

		// Stored verbatim: this field has no BBCode pipeline, unlike the
		// description, so nothing parses it and nothing renders it as markup.
		$campaign = $this->campaigns->find_by_id($id);

		$this->assertSame('[b]Donate[/b]', $campaign['external_link_text']);
		$this->assertStringNotContainsString('<strong', $campaign['external_link_text']);
	}

	public function test_a_tampered_field_alongside_link_text_is_ignored()
	{
		$id = $this->service->create_campaign($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'How to donate',
			'collected_amount'		=> 999999,
			'external_link_html'	=> '<img src=x onerror=alert(1)>',
		)));

		$campaign = $this->campaigns->find_by_id($id);

		$this->assertSame(0, $campaign['collected_amount'], 'A request value reached a derived column');
		$this->assertArrayNotHasKey('external_link_html', $campaign);
	}

	public function test_link_text_survives_an_edit()
	{
		$id = $this->service->create_campaign($this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'How to donate',
		)));

		$this->service->update_campaign($id, $this->input(array(
			'external_url'			=> 'https://example.org/give',
			'external_link_text'	=> 'Über PayPal spenden',
		)));

		$this->assertSame('Über PayPal spenden', $this->campaigns->find_by_id($id)['external_link_text']);
	}

	// ------------------------------------------------ external_url length

	/**
	 * The column is VCHAR:255 and maxlength on the input is client-side only.
	 * Without a server check a longer POST is silently truncated by a
	 * non-strict database -- producing a broken link nobody notices -- or
	 * throws a raw driver error on a strict one.
	 */
	public function test_a_url_at_the_maximum_length_is_accepted()
	{
		$url = 'https://example.org/' . str_repeat('a', campaign_service::MAX_URL_LENGTH - 20);

		$this->assertSame(campaign_service::MAX_URL_LENGTH, utf8_strlen($url));

		$errors = $this->service->validate($this->input(array(
			'external_url'			=> $url,
			'external_link_text'	=> 'How to donate',
		)));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG', $errors);
	}

	public function test_a_url_one_character_over_the_maximum_is_rejected()
	{
		$url = 'https://example.org/' . str_repeat('a', campaign_service::MAX_URL_LENGTH - 19);

		$errors = $this->service->validate($this->input(array(
			'external_url'			=> $url,
			'external_link_text'	=> 'How to donate',
		)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG', $errors);
	}

	public function test_the_url_limit_matches_the_column_width()
	{
		$schema = file_get_contents(dirname(dirname(__DIR__)) . '/migrations/v10x/m1_initial_schema.php');

		$this->assertMatchesRegularExpression(
			"/'external_url'\s*=> array\('VCHAR:" . campaign_service::MAX_URL_LENGTH . "'/",
			$schema,
			'Validation and the column disagree about how long a URL may be'
		);
	}

	/**
	 * An over-long URL must never reach storage, so the database is never the
	 * thing that decides what fits.
	 */
	public function test_an_over_long_url_is_not_persisted()
	{
		$url = 'https://example.org/' . str_repeat('a', 400);
		$before = $this->campaigns->count_all();

		try
		{
			$this->service->create_campaign($this->input(array(
				'external_url'			=> $url,
				'external_link_text'	=> 'How to donate',
			)));
			$this->fail('An over-long URL was accepted');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertContains('DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG', $e->get_parameters() ?: array($e->get_language_key()));
		}

		$this->assertSame($before, $this->campaigns->count_all(), 'An over-long URL reached storage');
	}

	/**
	 * Length is counted in characters. A percent-encoded multibyte URL that
	 * fits the column must not be rejected for being wide.
	 */
	public function test_the_url_limit_counts_characters_not_bytes()
	{
		$errors = $this->service->validate($this->input(array(
			'external_url'			=> 'https://example.org/' . str_repeat('ü', 100),
			'external_link_text'	=> 'How to donate',
		)));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG', $errors);
	}
}
