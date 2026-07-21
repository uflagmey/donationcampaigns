<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * Randomised property test for the denormalised campaign total.
 *
 * The example-based tests each pin one path. This one asserts the invariant
 * itself: after EVERY committed mutation, the stored collected_amount of
 * EVERY campaign equals SUM(donation_amount) for that campaign.
 *
 * The sequence is randomised but seeded, so a failure is reproducible: the
 * message prints the seed, and DONATIONCAMPAIGNS_TEST_SEED replays it.
 */
class total_integrity_test extends donation_test_case
{
	const OPERATIONS = 200;

	public function test_the_total_stays_consistent_across_random_operations()
	{
		$seed = (int) getenv('DONATIONCAMPAIGNS_TEST_SEED') ?: 20260718;
		mt_srand($seed);

		$campaign_ids = array(1, 2);
		$donation_ids = array(1, 2, 3);
		$performed = 0;

		$this->assert_totals_match_sum("Seed {$seed}, before any operation");

		for ($i = 1; $i <= self::OPERATIONS; $i++)
		{
			$choice = empty($donation_ids) ? 1 : mt_rand(1, 3);

			if ($choice === 1)
			{
				$campaign_id = $campaign_ids[array_rand($campaign_ids)];

				$donation_ids[] = $this->service->add_donation($campaign_id, array(
					// Bounded so that 200 additions cannot approach the column
					// ceiling; overflow is covered by its own explicit test.
					'donation_amount'	=> mt_rand(1, 100000),
					'donor_name'		=> 'Donor ' . $i,
					'donation_time'		=> 1700000000 + $i,
					'donation_public'	=> mt_rand(0, 1),
				));

				$description = 'add to campaign ' . $campaign_id;
			}
			else if ($choice === 2)
			{
				$key = array_rand($donation_ids);

				$this->service->edit_donation($donation_ids[$key], array(
					'donation_amount'	=> mt_rand(1, 100000),
					'donor_name'		=> 'Edited ' . $i,
					'donation_time'		=> 1700000000 + $i,
					'donation_public'	=> mt_rand(0, 1),
				));

				$description = 'edit donation ' . $donation_ids[$key];
			}
			else
			{
				$key = array_rand($donation_ids);
				$description = 'delete donation ' . $donation_ids[$key];

				$this->service->delete_donation($donation_ids[$key]);

				unset($donation_ids[$key]);
				$donation_ids = array_values($donation_ids);
			}

			$performed++;

			$this->assert_totals_match_sum(sprintf(
				"Seed: %d\nOperation %d of %d: %s\n"
				. 'Replay with DONATIONCAMPAIGNS_TEST_SEED=%d',
				$seed,
				$i,
				self::OPERATIONS,
				$description,
				$seed
			));
		}

		$this->assertSame(self::OPERATIONS, $performed);
	}

	/**
	 * The randomised sequence above only ever performs valid operations. This
	 * interleaves rejected ones, so the invariant is also asserted across
	 * failed mutations rather than only successful ones.
	 */
	public function test_the_total_stays_consistent_when_mutations_are_rejected()
	{
		$seed = (int) getenv('DONATIONCAMPAIGNS_TEST_SEED') ?: 20260718;
		mt_srand($seed);

		$rejected = 0;

		for ($i = 1; $i <= 50; $i++)
		{
			try
			{
				$this->service->add_donation(1, array(
					// Half of these are invalid amounts.
					'donation_amount'	=> mt_rand(0, 1) ? mt_rand(1, 1000) : 0,
					'donor_name'		=> 'Donor ' . $i,
					'donation_time'		=> 1700000000 + $i,
					'donation_public'	=> 1,
				));
			}
			catch (donationcampaigns_exception $e)
			{
				$rejected++;
			}

			$this->assert_totals_match_sum("Seed {$seed}, operation {$i}");
		}

		$this->assertGreaterThan(0, $rejected, 'No operation was rejected, so nothing was proven');
	}
}
