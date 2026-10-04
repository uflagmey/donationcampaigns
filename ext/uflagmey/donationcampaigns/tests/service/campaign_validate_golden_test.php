<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\tests\repository\campaign_list_test_case;

/**
 * Characterisation of campaign_service::validate() (beta3, Block D).
 *
 * The expected arrays in fixtures/validate_golden.php were recorded from the
 * implementation BEFORE validate() was split into field and topic rules. The
 * split must reproduce every one of them exactly — including the ORDER of the
 * errors, because assert_valid() throws the first one.
 *
 * Topics (campaign_list_test_case plus topic 70): 70 is free, 10 carries
 * campaign 1, 30 is a moved shadow, 99999 does not exist.
 */
class campaign_validate_golden_test extends campaign_list_test_case
{
	/** @var campaign_service */
	protected $service;

	public function setUp(): void
	{
		parent::setUp();

		$this->insert_topic(70, self::FORUM_A);

		$this->service = new campaign_service(
			$this->db,
			new campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			new donation_repository($this->db, 'phpbb_ufdc_donations'),
			new topic_repository($this->db, 'phpbb_topics'),
			new fake_description_formatter()
		);
	}

	/**
	 * The input matrix: every field valid or broken in each way validate()
	 * distinguishes. 3 × 3 × 6 × 4 × 3 = 648 cases.
	 *
	 * @return array<string, array{0:array,1:int}>
	 */
	public static function matrix()
	{
		$titles = array('ok' => 'Server fund', 'empty' => '   ', 'long' => str_repeat('ä', 256));
		$targets = array('ok' => 5000, 'zero' => 0, 'huge' => campaign_service::MAX_TARGET_AMOUNT + 1);
		$topics = array(
			'free'		=> array(70, 0),
			'none'		=> array(0, 0),
			'missing'	=> array(99999, 0),
			'shadow'	=> array(30, 0),
			'taken'		=> array(10, 0),
			'own'		=> array(10, 1),
		);
		$urls = array('none' => '', 'ok' => 'https://example.org/donate', 'js' => 'javascript:alert(1)', 'long' => 'https://example.org/' . str_repeat('a', 240));
		$links = array('ok' => 'Donate', 'empty' => '', 'long' => str_repeat('x', 101));

		$cases = array();
		foreach ($titles as $tk => $title)
		{
			foreach ($targets as $gk => $target)
			{
				foreach ($topics as $pk => $topic)
				{
					foreach ($urls as $uk => $url)
					{
						foreach ($links as $lk => $link)
						{
							$cases["title:$tk target:$gk topic:$pk url:$uk link:$lk"] = array(
								array(
									'campaign_title'		=> $title,
									'target_amount'			=> $target,
									'topic_id'				=> $topic[0],
									'external_url'			=> $url,
									'external_link_text'	=> $link,
								),
								$topic[1],
							);
						}
					}
				}
			}
		}

		return $cases;
	}

	public function test_validate_reproduces_every_recorded_result()
	{
		$golden = include __DIR__ . '/fixtures/validate_golden.php';
		$cases = self::matrix();

		$this->assertCount(count($cases), $golden, 'The golden file does not cover the matrix');

		foreach ($cases as $name => $case)
		{
			$this->assertSame($golden[$name], $this->service->validate($case[0], $case[1]), $name);
		}
	}

	/**
	 * validate_fields() is validate() without the three topic rules, in the
	 * same order — for every recorded case.
	 */
	public function test_validate_fields_is_validate_without_the_topic_rules()
	{
		$golden = include __DIR__ . '/fixtures/validate_golden.php';
		$topic_keys = array(
			'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
			'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
			'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
		);

		foreach (self::matrix() as $name => $case)
		{
			$expected = array_values(array_diff($golden[$name], $topic_keys));

			$this->assertSame($expected, $this->service->validate_fields($case[0]), $name);
		}
	}

	/**
	 * The posting form validates before its topic exists, so the field rules
	 * must not touch the database at all.
	 */
	public function test_validate_fields_issues_no_query()
	{
		$this->db->forget();

		$this->service->validate_fields(array('campaign_title' => 'x', 'target_amount' => 100, 'topic_id' => 10));

		$this->assertSame(array(), $this->db->queries);
	}
}
