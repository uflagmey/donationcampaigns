<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\currency_formatter;

/**
 * The one progress calculation shared by the topic box and the board list.
 *
 * Pure integer arithmetic: no database is touched, so the collaborators are
 * inert doubles and only progress() is exercised.
 */
class progress_test extends \phpbb_test_case
{
	/** @var campaign_service */
	protected $service;

	public function setUp(): void
	{
		parent::setUp();

		$this->service = new campaign_service(
			$this->createMock('\phpbb\db\driver\driver_interface'),
			$this->createMock('\uflagmey\donationcampaigns\repository\campaign_repository'),
			$this->createMock('\uflagmey\donationcampaigns\repository\donation_repository'),
			$this->createMock('\uflagmey\donationcampaigns\repository\topic_repository'),
			$this->createMock('\uflagmey\donationcampaigns\service\description_formatter')
		);
	}

	/**
	 * @return array
	 */
	public function progress_cases()
	{
		return array(
			//                         collected, target, percent, capped, step, reached
			'nothing yet'			=> array(0, 10000, 0, 0, 0, false),
			'under one step'		=> array(400, 10000, 4, 4, 0, false),
			'truncates'				=> array(999, 10000, 9, 9, 5, false),
			'never rounds up'		=> array(9999, 10000, 99, 99, 95, false),
			'exactly reached'		=> array(10000, 10000, 100, 100, 100, true),
			'over target'			=> array(25000, 10000, 250, 100, 100, true),
			'thirds'				=> array(1, 3, 33, 33, 30, false),
			// The guard (owner decision Q2): a zero target cannot be saved,
			// but a hand-edited row must neither divide by zero nor read as
			// "target reached".
			'zero target, nothing'	=> array(0, 0, 0, 0, 0, false),
			'zero target, money'	=> array(500, 0, 0, 0, 0, false),
		);
	}

	/**
	 * @dataProvider progress_cases
	 */
	public function test_progress($collected, $target, $percent, $capped, $step, $reached)
	{
		$this->assertSame(
			array('percent' => $percent, 'percent_capped' => $capped, 'step' => $step, 'reached' => $reached),
			$this->service->progress($collected, $target)
		);
	}

	/**
	 * The largest storable amount times 100 must still be an integer, or
	 * intdiv() would receive a float on the way.
	 */
	public function test_the_largest_storable_amount_does_not_overflow()
	{
		$max = currency_formatter::MAX_MINOR_UNITS;

		$this->assertIsInt($max * 100);
		$this->assertSame(
			array('percent' => 100, 'percent_capped' => 100, 'step' => 100, 'reached' => true),
			$this->service->progress($max, $max)
		);
		$this->assertSame(0, $this->service->progress(1, $max)['percent']);
	}

	public function test_every_step_is_a_multiple_of_the_step_width()
	{
		for ($collected = 0; $collected <= 120; $collected++)
		{
			$step = $this->service->progress($collected, 100)['step'];

			$this->assertSame(0, $step % campaign_service::PERCENT_STEP);
			$this->assertLessThanOrEqual(100, $step);
		}
	}
}
