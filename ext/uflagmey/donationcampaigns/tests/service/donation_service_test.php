<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\service\donation_service;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * Donation mutations and campaign-total integrity.
 *
 * The load-bearing rule: after every successful mutation the stored total is
 * recomputed from SUM(donation_amount) inside the same transaction. Never a
 * delta. Every test here that touches a total asserts against a freshly
 * computed sum rather than an expected arithmetic result alone.
 */
class donation_service_test extends donation_test_case
{
	// -------------------------------------------------------------- creation

	public function test_add_donation_returns_the_new_id_and_persists()
	{
		$id = $this->service->add_donation(1, $this->donation());

		$this->assertIsInt($id);
		$this->assertGreaterThan(0, $id);
		$this->assertSame('Clara S.', $this->donations->find_by_id($id)['donor_name']);
	}

	public function test_add_donation_updates_the_stored_total()
	{
		$this->service->add_donation(1, $this->donation(array('donation_amount' => 500)));

		$this->assertSame(3000, $this->stored_total(1));
		$this->assert_totals_match_sum();
	}

	public function test_add_donation_stamps_the_timestamps()
	{
		$donation = $this->donations->find_by_id($this->service->add_donation(1, $this->donation()));

		$this->assertGreaterThan(0, $donation['donation_created']);
		$this->assertSame($donation['donation_created'], $donation['donation_updated']);
	}

	public function test_add_donation_to_a_second_campaign_leaves_the_first_alone()
	{
		$this->service->add_donation(2, $this->donation(array('donation_amount' => 700)));

		$this->assertSame(2500, $this->stored_total(1));
		$this->assertSame(700, $this->stored_total(2));
		$this->assert_totals_match_sum();
	}

	/**
	 * The public flag governs whether a donor is named, never whether the
	 * money counted.
	 */
	public function test_a_non_public_donation_still_counts_toward_the_total()
	{
		$this->service->add_donation(1, $this->donation(array(
			'donation_amount'	=> 700,
			'donation_public'	=> 0,
		)));

		$this->assertSame(3200, $this->stored_total(1));
	}

	public function test_add_donation_rejects_an_unknown_campaign()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->add_donation(99999, $this->donation());
	}

	public function test_add_donation_writes_nothing_for_an_unknown_campaign()
	{
		try
		{
			$this->service->add_donation(99999, $this->donation());
		}
		catch (donationcampaigns_exception $e)
		{
			// expected
		}

		$this->assertSame(0, $this->donations->count_by_campaign(99999));
		$this->assert_totals_match_sum();
	}

	// ------------------------------------------------------------ validation

	public function invalid_amount_data()
	{
		return array(
			'zero'			=> array(0, 'DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE'),
			'negative'		=> array(-1, 'DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE'),
			'non numeric'	=> array('abc', 'DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE'),
			'empty string'	=> array('', 'DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE'),
			'null'			=> array(null, 'DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE'),
			'over limit'	=> array(donation_service::MAX_AMOUNT + 1, 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE'),
		);
	}

	/**
	 * @dataProvider invalid_amount_data
	 */
	public function test_add_donation_rejects_an_invalid_amount($amount, $expected_key)
	{
		try
		{
			$this->service->add_donation(1, $this->donation(array('donation_amount' => $amount)));
			$this->fail('An invalid amount was accepted');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertSame($expected_key, $e->get_language_key());
		}

		$this->assertSame(3, $this->donations->count_by_campaign(1), 'A rejected donation was written');
		$this->assert_totals_match_sum();
	}

	public function test_add_donation_accepts_the_maximum_amount()
	{
		// Campaign 2 is empty, so the maximum amount cannot overflow the total.
		$id = $this->service->add_donation(2, $this->donation(array(
			'donation_amount' => donation_service::MAX_AMOUNT,
		)));

		$this->assertSame(donation_service::MAX_AMOUNT, $this->donations->find_by_id($id)['donation_amount']);
	}

	/**
	 * A total that would not fit the column must be refused rather than
	 * silently truncated — the headline figure would otherwise be wrong with
	 * nothing to indicate it.
	 */
	public function test_add_donation_refuses_to_overflow_the_stored_total()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->add_donation(1, $this->donation(array(
			'donation_amount' => donation_service::MAX_AMOUNT,
		)));
	}

	public function test_an_overflowing_donation_leaves_no_partial_data()
	{
		try
		{
			$this->service->add_donation(1, $this->donation(array(
				'donation_amount' => donation_service::MAX_AMOUNT,
			)));
		}
		catch (donationcampaigns_exception $e)
		{
			// expected
		}

		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertSame(2500, $this->stored_total(1));
		$this->assert_totals_match_sum();
	}

	/**
	 * An empty donor name is valid: it is how an anonymous donation is
	 * recorded, and the front end renders it as "Anonymous".
	 */
	public function test_an_empty_donor_name_is_accepted_as_anonymous()
	{
		$id = $this->service->add_donation(1, $this->donation(array('donor_name' => '')));

		$this->assertSame('', $this->donations->find_by_id($id)['donor_name']);
	}

	public function test_a_whitespace_only_donor_name_is_normalised_to_empty()
	{
		$id = $this->service->add_donation(1, $this->donation(array('donor_name' => '   ')));

		$this->assertSame('', $this->donations->find_by_id($id)['donor_name']);
	}

	public function test_a_donor_name_is_trimmed()
	{
		$id = $this->service->add_donation(1, $this->donation(array('donor_name' => '  Clara S.  ')));

		$this->assertSame('Clara S.', $this->donations->find_by_id($id)['donor_name']);
	}

	public function test_add_donation_rejects_an_overlong_donor_name()
	{
		try
		{
			$this->service->add_donation(1, $this->donation(array('donor_name' => str_repeat('a', 256))));
			$this->fail('An overlong donor name was accepted');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertSame('DONATIONCAMPAIGNS_ERROR_DONOR_NAME_TOO_LONG', $e->get_language_key());
		}
	}

	public function test_a_donor_name_is_measured_in_characters_not_bytes()
	{
		$id = $this->service->add_donation(1, $this->donation(array('donor_name' => str_repeat('ä', 200))));

		$this->assertNotNull($this->donations->find_by_id($id));
	}

	public function test_add_donation_accepts_a_donor_name_of_exactly_the_column_width()
	{
		$id = $this->service->add_donation(1, $this->donation(array('donor_name' => str_repeat('a', 255))));

		$this->assertNotNull($this->donations->find_by_id($id));
	}

	public function invalid_time_data()
	{
		return array(
			'zero'		=> array(0),
			'negative'	=> array(-1),
			'non numeric' => array('yesterday'),
		);
	}

	/**
	 * @dataProvider invalid_time_data
	 */
	public function test_add_donation_rejects_an_invalid_time($time)
	{
		try
		{
			$this->service->add_donation(1, $this->donation(array('donation_time' => $time)));
			$this->fail('An invalid donation time was accepted');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertSame('DONATIONCAMPAIGNS_ERROR_TIME_INVALID', $e->get_language_key());
		}
	}

	/**
	 * An absent time is not an error — it means "now", which is what the ACP
	 * form defaults to.
	 */
	public function test_an_absent_time_defaults_to_now()
	{
		$input = $this->donation();
		unset($input['donation_time']);

		$donation = $this->donations->find_by_id($this->service->add_donation(1, $input));

		$this->assertGreaterThan(0, $donation['donation_time']);
	}

	public function public_flag_data()
	{
		return array(
			'int one'		=> array(1, true),
			'string one'	=> array('1', true),
			'true'			=> array(true, true),
			'on'			=> array('on', true),
			'int zero'		=> array(0, false),
			'string zero'	=> array('0', false),
			'false'			=> array(false, false),
			'empty string'	=> array('', false),
		);
	}

	/**
	 * The flag arrives from an HTML checkbox, so it may be absent, '0', '1' or
	 * 'on'. It is normalised to a real boolean at this boundary.
	 *
	 * @dataProvider public_flag_data
	 */
	public function test_the_public_flag_is_normalised($supplied, $expected)
	{
		$id = $this->service->add_donation(1, $this->donation(array('donation_public' => $supplied)));

		$this->assertSame($expected, $this->donations->find_by_id($id)['donation_public']);
	}

	public function test_an_absent_public_flag_defaults_to_not_public()
	{
		$input = $this->donation();
		unset($input['donation_public']);

		$id = $this->service->add_donation(1, $input);

		$this->assertFalse(
			$this->donations->find_by_id($id)['donation_public'],
			'An unchecked checkbox is absent from the request and must not default to public'
		);
	}

	public function test_add_donation_ignores_unknown_and_derived_fields()
	{
		$id = $this->service->add_donation(1, $this->donation(array(
			'donation_id'	=> 4242,
			'campaign_id'	=> 2,
			'not_a_column'	=> 'x',
		)));

		$this->assertNotSame(4242, $id);
		$this->assertSame(
			1,
			$this->donations->find_by_id($id)['campaign_id'],
			'campaign_id came from the input rather than the argument'
		);
	}

	/**
	 * A key with no string renders as the raw key, which reads as a crash.
	 * The keys are gathered by provoking each failure rather than listed by
	 * hand, so this cannot go stale when a rule is added.
	 */
	public function test_every_error_key_has_an_english_string()
	{
		$provocations = array(
			array(1, $this->donation(array('donation_amount' => 0))),
			array(1, $this->donation(array('donation_amount' => donation_service::MAX_AMOUNT + 1))),
			array(1, $this->donation(array('donor_name' => str_repeat('a', 256)))),
			array(1, $this->donation(array('donation_time' => 0))),
			array(1, $this->donation(array('donation_amount' => donation_service::MAX_AMOUNT))),
			array(99999, $this->donation()),
		);

		$keys = array();

		foreach ($provocations as $provocation)
		{
			try
			{
				$this->service->add_donation($provocation[0], $provocation[1]);
			}
			catch (donationcampaigns_exception $e)
			{
				$keys[] = $e->get_language_key();
			}
		}

		try
		{
			$this->service->delete_donation(99999);
		}
		catch (donationcampaigns_exception $e)
		{
			$keys[] = $e->get_language_key();
		}

		$lang = array();
		include __DIR__ . '/../../language/en/common.php';

		$this->assertCount(7, $keys, 'A provocation did not raise');

		foreach (array_unique($keys) as $key)
		{
			$this->assertArrayHasKey($key, $lang, "No English string for {$key}");
		}
	}

	// ---------------------------------------------------------------- update

	public function test_edit_donation_changes_the_row()
	{
		$this->service->edit_donation(1, $this->donation(array('donation_amount' => 2000)));

		$this->assertSame(2000, $this->donations->find_by_id(1)['donation_amount']);
	}

	public function test_edit_donation_updates_the_stored_total()
	{
		$this->service->edit_donation(1, $this->donation(array('donation_amount' => 2000)));

		// 2500 - 1000 + 2000, but derived from SUM(), not from that arithmetic.
		$this->assertSame(3500, $this->stored_total(1));
		$this->assert_totals_match_sum();
	}

	public function test_edit_donation_rejects_an_unknown_donation()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->edit_donation(99999, $this->donation());
	}

	public function test_edit_donation_rejects_an_invalid_amount()
	{
		try
		{
			$this->service->edit_donation(1, $this->donation(array('donation_amount' => 0)));
			$this->fail('An invalid amount was accepted');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertSame('DONATIONCAMPAIGNS_ERROR_AMOUNT_POSITIVE', $e->get_language_key());
		}

		$this->assertSame(1000, $this->donations->find_by_id(1)['donation_amount']);
		$this->assert_totals_match_sum();
	}

	public function test_edit_donation_cannot_move_a_donation_between_campaigns()
	{
		$this->service->edit_donation(1, $this->donation(array('campaign_id' => 2)));

		$this->assertSame(1, $this->donations->find_by_id(1)['campaign_id']);
		$this->assert_totals_match_sum();
	}

	// -------------------------------------------------------------- deletion

	public function test_delete_donation_removes_the_row_and_updates_the_total()
	{
		$this->service->delete_donation(1);

		$this->assertNull($this->donations->find_by_id(1));
		$this->assertSame(1500, $this->stored_total(1));
		$this->assert_totals_match_sum();
	}

	public function test_deleting_every_donation_returns_the_total_to_integer_zero()
	{
		$this->service->delete_donation(1);
		$this->service->delete_donation(2);
		$this->service->delete_donation(3);

		$total = $this->stored_total(1);

		$this->assertSame(0, $total);
		$this->assertIsInt($total);
	}

	public function test_delete_donation_rejects_an_unknown_donation()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->delete_donation(99999);
	}

	public function test_delete_donations_removes_several_at_once()
	{
		$this->service->delete_donations(array(1, 3));

		$this->assertNull($this->donations->find_by_id(1));
		$this->assertNotNull($this->donations->find_by_id(2));
		$this->assertSame(1200, $this->stored_total(1));
		$this->assert_totals_match_sum();
	}

	/**
	 * A bulk delete may span campaigns, and every campaign it touched must be
	 * recalculated — not only the first one found.
	 */
	public function test_delete_donations_recalculates_every_affected_campaign()
	{
		$second = $this->service->add_donation(2, $this->donation(array('donation_amount' => 800)));

		$this->service->delete_donations(array(1, $second));

		$this->assertSame(1500, $this->stored_total(1));
		$this->assertSame(0, $this->stored_total(2));
		$this->assert_totals_match_sum();
	}

	public function test_delete_donations_with_an_empty_list_is_a_noop()
	{
		$this->service->delete_donations(array());

		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertSame(2500, $this->stored_total(1));
	}

	public function test_delete_donations_ignores_unknown_ids()
	{
		$this->service->delete_donations(array(1, 99999));

		$this->assertSame(1500, $this->stored_total(1));
		$this->assert_totals_match_sum();
	}

	// ----------------------------------------------------------- recalculate

	public function test_recalculate_returns_the_authoritative_total()
	{
		$this->assertSame(2500, $this->service->recalculate(1));
	}

	public function test_recalculate_repairs_a_corrupted_total()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$repaired = $this->service->recalculate(1);

		$this->assertSame(2500, $repaired);
		$this->assertSame(2500, $this->stored_total(1));
	}

	public function test_recalculate_is_idempotent()
	{
		$this->assertSame($this->service->recalculate(1), $this->service->recalculate(1));
	}

	public function test_recalculate_on_a_campaign_with_no_donations_is_integer_zero()
	{
		$total = $this->service->recalculate(2);

		$this->assertSame(0, $total);
		$this->assertIsInt($total);
	}

	public function test_recalculate_rejects_an_unknown_campaign()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->service->recalculate(99999);
	}

	// ------------------------------------------------- the no-delta property

	/**
	 * THE regression test for ADR-003.
	 *
	 * Seed a deliberately wrong total, then perform one UNRELATED edit. A
	 * delta implementation carries the corruption forward; a SUM()-based one
	 * self-heals.
	 *
	 * If this test is ever weakened to accommodate an implementation, the
	 * implementation is wrong, not the test.
	 */
	public function test_an_unrelated_mutation_repairs_a_pre_existing_wrong_total()
	{
		$this->campaigns->set_collected_amount(1, 123456);

		$this->service->edit_donation(3, $this->donation(array(
			'donation_amount'	=> 400,
			'donation_time'		=> 1700000300,
		)));

		$this->assertSame(
			2600,
			$this->stored_total(1),
			'The total was derived from the previous stored value instead of SUM(). '
			. 'That is delta arithmetic, and it is forbidden — see ADR-003.'
		);
	}

	public function test_an_add_repairs_a_pre_existing_wrong_total()
	{
		$this->campaigns->set_collected_amount(1, 7);

		$this->service->add_donation(1, $this->donation(array('donation_amount' => 500)));

		$this->assertSame(3000, $this->stored_total(1));
	}

	public function test_a_delete_repairs_a_pre_existing_wrong_total()
	{
		$this->campaigns->set_collected_amount(1, 999999);

		$this->service->delete_donation(3);

		$this->assertSame(2200, $this->stored_total(1));
	}

	/**
	 * Asserted on the statements actually issued: every mutation must read the
	 * authoritative SUM before writing the total. A delta implementation would
	 * never issue this query.
	 *
	 * @dataProvider mutating_operation_data
	 */
	public function test_every_mutation_reads_the_sum($method, $arguments)
	{
		$this->db->forget();

		call_user_func_array(array($this->service, $method), $arguments);

		$this->assertNotNull(
			$this->db->first_query_matching('/SELECT SUM\(donation_amount\)/'),
			"{$method} did not recompute the total from SUM()"
		);
	}

	public function mutating_operation_data()
	{
		return array(
			'add'			=> array('add_donation', array(1, array('donation_amount' => 500, 'donor_name' => 'X', 'donation_time' => 1700000400, 'donation_public' => 1))),
			'edit'			=> array('edit_donation', array(1, array('donation_amount' => 500, 'donor_name' => 'X', 'donation_time' => 1700000400, 'donation_public' => 1))),
			'delete'		=> array('delete_donation', array(1)),
			'delete many'	=> array('delete_donations', array(array(1, 2))),
			'recalculate'	=> array('recalculate', array(1)),
		);
	}

	// --------------------------------------------------- transaction boundaries

	/**
	 * @dataProvider mutating_operation_data
	 */
	public function test_every_mutation_commits_exactly_one_transaction($method, $arguments)
	{
		$this->db->forget();

		call_user_func_array(array($this->service, $method), $arguments);

		$this->assertSame(array('begin', 'commit'), $this->db->transaction_log);
	}

	public function test_an_empty_bulk_delete_opens_no_transaction()
	{
		$this->db->forget();

		$this->service->delete_donations(array());

		$this->assertSame(array(), $this->db->transaction_log);
	}

	/**
	 * Validation runs before the transaction opens, so a rejected input costs
	 * no round trip at all.
	 */
	public function test_a_rejected_input_opens_no_transaction()
	{
		$this->db->forget();

		try
		{
			$this->service->add_donation(1, $this->donation(array('donation_amount' => 0)));
		}
		catch (donationcampaigns_exception $e)
		{
			// expected
		}

		$this->assertSame(array(), $this->db->transaction_log);
	}

	// -------------------------------------------------------------- rollback

	/**
	 * The donation row cannot be written. Nothing else may survive.
	 */
	public function test_rollback_when_donation_persistence_fails()
	{
		$service = new donation_service(
			$this->db,
			$this->campaigns,
			new failing_donation_repository($this->db, 'phpbb_ufdc_donations')
		);

		$this->db->forget();

		try
		{
			$service->add_donation(1, $this->donation());
			$this->fail('The failing repository did not surface its exception');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('donation persistence failed', $e->getMessage());
		}

		$this->assertSame(array('begin', 'rollback'), $this->db->transaction_log);
		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assertSame(2500, $this->stored_total(1));
		$this->assert_totals_match_sum('after a failed insert');
	}

	/**
	 * The row is written and the SUM then fails. This is the dangerous case:
	 * without a rollback the donation would exist while the stored total still
	 * described the world before it.
	 */
	public function test_rollback_when_the_sum_calculation_fails()
	{
		$service = new donation_service(
			$this->db,
			$this->campaigns,
			new failing_sum_donation_repository($this->db, 'phpbb_ufdc_donations')
		);

		$this->db->forget();

		try
		{
			$service->add_donation(1, $this->donation());
			$this->fail('The failing sum did not surface its exception');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('sum failed', $e->getMessage());
		}

		$this->assertSame(array('begin', 'rollback'), $this->db->transaction_log);
		$this->assertSame(3, $this->donations->count_by_campaign(1), 'The inserted row survived a failed recalculation');
		$this->assertSame(2500, $this->stored_total(1));
		$this->assert_totals_match_sum('after a failed sum');
	}

	/**
	 * The row is written, the SUM succeeds, and persisting the total fails.
	 */
	public function test_rollback_when_campaign_total_persistence_fails()
	{
		$service = new donation_service(
			$this->db,
			new failing_total_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			$this->donations
		);

		$this->db->forget();

		try
		{
			$service->add_donation(1, $this->donation());
			$this->fail('The failing total write did not surface its exception');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('total persistence failed', $e->getMessage());
		}

		$this->assertSame(array('begin', 'rollback'), $this->db->transaction_log);
		$this->assertSame(3, $this->donations->count_by_campaign(1));
		$this->assert_totals_match_sum('after a failed total write');
	}

	public function test_rollback_leaves_no_partial_data_on_edit()
	{
		$service = new donation_service(
			$this->db,
			new failing_total_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			$this->donations
		);

		try
		{
			$service->edit_donation(1, $this->donation(array('donation_amount' => 9999)));
		}
		catch (\RuntimeException $e)
		{
			// expected
		}

		$this->assertSame(1000, $this->donations->find_by_id(1)['donation_amount'], 'The edit survived a rolled-back transaction');
		$this->assert_totals_match_sum('after a failed edit');
	}

	public function test_rollback_leaves_no_partial_data_on_delete()
	{
		$service = new donation_service(
			$this->db,
			new failing_total_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			$this->donations
		);

		try
		{
			$service->delete_donation(1);
		}
		catch (\RuntimeException $e)
		{
			// expected
		}

		$this->assertNotNull($this->donations->find_by_id(1), 'The delete survived a rolled-back transaction');
		$this->assert_totals_match_sum('after a failed delete');
	}
}
