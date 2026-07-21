<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\service\settings_service;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * The board's currency and display settings.
 *
 * Validation lives in a service rather than in the ACP module so it can be
 * tested without a request, a session or a template — and so the rules exist
 * in one place rather than being re-derived by any future caller.
 */
class settings_service_test extends donation_test_case
{
	/** @var settings_service */
	protected $settings;

	/** @var \phpbb\config\config */
	protected $config;

	public function setUp(): void
	{
		parent::setUp();

		$this->config = new \phpbb\config\config(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 25,
			'an_unrelated_key'						=> 'untouched',
		));

		$this->settings = new settings_service($this->config, $this->campaigns, $this->donations);
	}

	/**
	 * A complete, valid input, so each test states only what it varies.
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function input(array $overrides = array())
	{
		return array_merge(array(
			'donationcampaigns_currency_code'		=> 'USD',
			'donationcampaigns_currency_symbol'		=> '$',
			'donationcampaigns_currency_exponent'	=> 2,
			'donationcampaigns_donor_list_limit'	=> 50,
		), $overrides);
	}

	// ------------------------------------------------------------- reading

	public function test_it_exposes_the_current_values()
	{
		$current = $this->settings->current();

		$this->assertSame('EUR', $current['donationcampaigns_currency_code']);
		$this->assertSame('€', $current['donationcampaigns_currency_symbol']);
		$this->assertSame(2, $current['donationcampaigns_currency_exponent']);
		$this->assertSame(25, $current['donationcampaigns_donor_list_limit']);
	}

	public function test_the_current_numeric_values_are_integers()
	{
		$current = $this->settings->current();

		$this->assertIsInt($current['donationcampaigns_currency_exponent']);
		$this->assertIsInt($current['donationcampaigns_donor_list_limit']);
	}

	// ---------------------------------------------------------- currency code

	public function invalid_code_data()
	{
		return array(
			'empty'			=> array(''),
			'two letters'	=> array('EU'),
			'four letters'	=> array('EURO'),
			'digits'		=> array('E1R'),
			'symbols'		=> array('EU$'),
			'space'			=> array('E R'),
			'non ascii'		=> array('EÜR'),
			'whitespace'	=> array('   '),
		);
	}

	/**
	 * @dataProvider invalid_code_data
	 */
	public function test_it_rejects_an_invalid_currency_code($code)
	{
		$errors = $this->settings->validate($this->input(array('donationcampaigns_currency_code' => $code)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_CURRENCY_CODE', $errors);
	}

	public function test_it_accepts_a_three_letter_code()
	{
		$this->assertSame(array(), $this->settings->validate($this->input(array('donationcampaigns_currency_code' => 'GBP'))));
	}

	public function test_a_lowercase_code_is_normalised_to_uppercase()
	{
		$this->settings->save($this->input(array('donationcampaigns_currency_code' => 'gbp')));

		$this->assertSame('GBP', $this->config['donationcampaigns_currency_code']);
	}

	public function test_a_padded_code_is_trimmed_and_accepted()
	{
		$this->settings->save($this->input(array('donationcampaigns_currency_code' => '  chf  ')));

		$this->assertSame('CHF', $this->config['donationcampaigns_currency_code']);
	}

	// -------------------------------------------------------- currency symbol

	public function test_it_rejects_an_empty_symbol()
	{
		$errors = $this->settings->validate($this->input(array('donationcampaigns_currency_symbol' => '')));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL', $errors);
	}

	public function test_it_rejects_a_whitespace_only_symbol()
	{
		$errors = $this->settings->validate($this->input(array('donationcampaigns_currency_symbol' => '    ')));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL', $errors);
	}

	public function test_it_rejects_an_overlong_symbol()
	{
		$errors = $this->settings->validate($this->input(array(
			'donationcampaigns_currency_symbol' => str_repeat('x', settings_service::MAX_SYMBOL_LENGTH + 1),
		)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL_LENGTH', $errors);
	}

	public function test_it_accepts_a_symbol_of_exactly_the_maximum_length()
	{
		$errors = $this->settings->validate($this->input(array(
			'donationcampaigns_currency_symbol' => str_repeat('x', settings_service::MAX_SYMBOL_LENGTH),
		)));

		$this->assertSame(array(), $errors);
	}

	/**
	 * The limit is in characters, not bytes. '€' is three bytes; a symbol of
	 * multibyte characters that fits must not be rejected for its byte count.
	 */
	public function test_the_symbol_length_is_measured_in_characters_not_bytes()
	{
		$errors = $this->settings->validate($this->input(array(
			'donationcampaigns_currency_symbol' => str_repeat('€', settings_service::MAX_SYMBOL_LENGTH),
		)));

		$this->assertSame(array(), $errors);
	}

	public function test_a_padded_symbol_is_trimmed()
	{
		$this->settings->save($this->input(array('donationcampaigns_currency_symbol' => '  kr.  ')));

		$this->assertSame('kr.', $this->config['donationcampaigns_currency_symbol']);
	}

	// ------------------------------------------------------------- exponent

	public function out_of_range_exponent_data()
	{
		return array(
			'negative'		=> array(-1),
			'far negative'	=> array(-100),
			'five'			=> array(5),
			'large'			=> array(99),
			'non numeric'	=> array('abc'),
		);
	}

	/**
	 * @dataProvider out_of_range_exponent_data
	 */
	public function test_it_rejects_an_out_of_range_exponent($exponent)
	{
		$errors = $this->settings->validate($this->input(array('donationcampaigns_currency_exponent' => $exponent)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_EXPONENT_RANGE', $errors);
	}

	public function in_range_exponent_data()
	{
		return array(array(0), array(1), array(2), array(3), array(4));
	}

	/**
	 * Range only. The fixture holds amounts, so every value other than the
	 * stored 2 is also an exponent CHANGE and separately requires
	 * confirmation — that rule has its own tests below, and is satisfied here
	 * so this one isolates the range check.
	 *
	 * @dataProvider in_range_exponent_data
	 */
	public function test_it_accepts_every_exponent_in_range($exponent)
	{
		$errors = $this->settings->validate($this->input(array(
			'donationcampaigns_currency_exponent'	=> $exponent,
			'donationcampaigns_confirm_exponent'	=> true,
		)));

		$this->assertNotContains('DONATIONCAMPAIGNS_ERROR_EXPONENT_RANGE', $errors);
		$this->assertSame(array(), $errors);
	}

	// ------------------------------------------------------ donor list limit

	public function out_of_range_limit_data()
	{
		return array(
			'zero'			=> array(0),
			'negative'		=> array(-1),
			'above'			=> array(501),
			'far above'		=> array(100000),
			'non numeric'	=> array('many'),
		);
	}

	/**
	 * @dataProvider out_of_range_limit_data
	 */
	public function test_it_rejects_an_out_of_range_donor_limit($limit)
	{
		$errors = $this->settings->validate($this->input(array('donationcampaigns_donor_list_limit' => $limit)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_DONOR_LIMIT_RANGE', $errors);
	}

	public function test_it_accepts_the_donor_limit_bounds()
	{
		$this->assertSame(array(), $this->settings->validate($this->input(array('donationcampaigns_donor_list_limit' => 1))));
		$this->assertSame(array(), $this->settings->validate($this->input(array('donationcampaigns_donor_list_limit' => 500))));
	}

	// -------------------------------------------------------------- saving

	public function test_a_valid_save_writes_all_four_values()
	{
		$this->settings->save($this->input());

		$this->assertSame('USD', $this->config['donationcampaigns_currency_code']);
		$this->assertSame('$', $this->config['donationcampaigns_currency_symbol']);
		$this->assertSame(2, (int) $this->config['donationcampaigns_currency_exponent']);
		$this->assertSame(50, (int) $this->config['donationcampaigns_donor_list_limit']);
	}

	public function test_saving_leaves_unrelated_config_keys_alone()
	{
		$this->settings->save($this->input());

		$this->assertSame('untouched', $this->config['an_unrelated_key']);
	}

	public function test_an_invalid_save_throws()
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->settings->save($this->input(array('donationcampaigns_currency_code' => 'nope')));
	}

	/**
	 * The whole form is rejected or none of it is. A partial write would leave
	 * the board with a currency code from the new submission and an exponent
	 * from the old one.
	 */
	public function test_an_invalid_save_writes_nothing_at_all()
	{
		try
		{
			$this->settings->save(array(
				'donationcampaigns_currency_code'		=> 'GBP',
				'donationcampaigns_currency_symbol'		=> '£',
				// Only this one is invalid.
				'donationcampaigns_currency_exponent'	=> 9,
				'donationcampaigns_donor_list_limit'	=> 10,
			));
			$this->fail('An invalid settings save was accepted');
		}
		catch (donationcampaigns_exception $e)
		{
			// expected
		}

		$this->assertSame('EUR', $this->config['donationcampaigns_currency_code'], 'A partial write happened');
		$this->assertSame('€', $this->config['donationcampaigns_currency_symbol']);
		$this->assertSame(2, (int) $this->config['donationcampaigns_currency_exponent']);
		$this->assertSame(25, (int) $this->config['donationcampaigns_donor_list_limit']);
	}

	public function test_every_error_is_reported_together()
	{
		$errors = $this->settings->validate(array(
			'donationcampaigns_currency_code'		=> 'nope',
			'donationcampaigns_currency_symbol'		=> '',
			'donationcampaigns_currency_exponent'	=> 9,
			'donationcampaigns_donor_list_limit'	=> 0,
		));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_CURRENCY_CODE', $errors);
		$this->assertContains('DONATIONCAMPAIGNS_ERROR_CURRENCY_SYMBOL', $errors);
		$this->assertContains('DONATIONCAMPAIGNS_ERROR_EXPONENT_RANGE', $errors);
		$this->assertContains('DONATIONCAMPAIGNS_ERROR_DONOR_LIMIT_RANGE', $errors);
		$this->assertCount(4, $errors);
	}

	public function test_errors_are_language_keys()
	{
		$errors = $this->settings->validate($this->input(array('donationcampaigns_currency_code' => '')));

		foreach ($errors as $error)
		{
			$this->assertMatchesRegularExpression('/^DONATIONCAMPAIGNS_ERROR_[A-Z_]+$/', $error);
		}
	}

	public function test_every_error_key_has_an_english_string()
	{
		$errors = array_merge(
			$this->settings->validate(array(
				'donationcampaigns_currency_code'		=> 'nope',
				'donationcampaigns_currency_symbol'		=> '',
				'donationcampaigns_currency_exponent'	=> 9,
				'donationcampaigns_donor_list_limit'	=> 0,
			)),
			$this->settings->validate($this->input(array(
				'donationcampaigns_currency_symbol' => str_repeat('x', settings_service::MAX_SYMBOL_LENGTH + 1),
			)))
		);

		$lang = array();
		include __DIR__ . '/../../language/en/common.php';

		foreach (array_unique($errors) as $key)
		{
			$this->assertArrayHasKey($key, $lang, "No English string for {$key}");
		}
	}

	// ------------------------------------------------- values stored plainly

	/**
	 * THE ESCAPING CONTRACT for plain settings fields.
	 *
	 * The value is stored exactly as typed — not pre-escaped. Escaping happens
	 * where the value is rendered, because that is where the output context is
	 * known. Storing escaped text means every consumer has to guess whether it
	 * is looking at markup or at characters.
	 */
	public function test_a_symbol_is_stored_exactly_as_entered()
	{
		$this->settings->save($this->input(array('donationcampaigns_currency_symbol' => '<b>')));

		$this->assertSame(
			'<b>',
			$this->config['donationcampaigns_currency_symbol'],
			'The value was escaped on the way into storage'
		);
	}

	public function test_an_ampersand_survives_a_save_round_trip_unchanged()
	{
		$this->settings->save($this->input(array('donationcampaigns_currency_symbol' => 'R&D')));

		$this->assertSame('R&D', $this->config['donationcampaigns_currency_symbol']);
		$this->assertSame('R&D', $this->settings->current()['donationcampaigns_currency_symbol']);
	}

	// --------------------------------------------------- the exponent warning

	public function test_no_stored_data_means_no_warning_is_needed()
	{
		$this->donations->delete_by_campaign_ids(array(1, 2));
		$this->campaigns->delete_by_ids(array(1, 2));

		$this->assertFalse($this->settings->has_stored_amounts());
	}

	public function test_an_existing_campaign_means_a_warning_is_needed()
	{
		$this->donations->delete_by_campaign_ids(array(1, 2));

		$this->assertSame(0, $this->donations->count_all());
		$this->assertTrue($this->settings->has_stored_amounts(), 'A campaign alone already carries a target amount');
	}

	public function test_an_existing_donation_means_a_warning_is_needed()
	{
		$this->assertGreaterThan(0, $this->donations->count_all());
		$this->assertTrue($this->settings->has_stored_amounts());
	}

	public function test_changing_the_exponent_with_no_data_needs_no_confirmation()
	{
		$this->donations->delete_by_campaign_ids(array(1, 2));
		$this->campaigns->delete_by_ids(array(1, 2));

		$this->assertFalse($this->settings->exponent_change_needs_confirmation(4));
	}

	public function test_changing_the_exponent_with_data_needs_confirmation()
	{
		$this->assertTrue($this->settings->exponent_change_needs_confirmation(4));
	}

	public function test_keeping_the_same_exponent_never_needs_confirmation()
	{
		$this->assertFalse(
			$this->settings->exponent_change_needs_confirmation(2),
			'Saving other settings must not demand confirmation of an unchanged exponent'
		);
	}

	/**
	 * Changing the exponent reinterprets every stored amount. 1000 minor units
	 * is 10.00 at exponent 2 and 1.000 at exponent 3 — the same integer, a
	 * different sum of money. The rows themselves must never be rewritten:
	 * silently multiplying an administrator's stored donations is worse than
	 * refusing.
	 */
	public function test_changing_the_exponent_never_rewrites_stored_amounts()
	{
		$before_campaign = $this->campaigns->find_by_id(1);
		$before_donations = $this->donations->find_by_campaign(1);

		$this->settings->save($this->input(array(
			'donationcampaigns_currency_exponent'	=> 3,
			'donationcampaigns_confirm_exponent'	=> true,
		)));

		$this->assertSame(3, (int) $this->config['donationcampaigns_currency_exponent']);
		$this->assertEquals($before_campaign, $this->campaigns->find_by_id(1), 'A campaign amount was rewritten');
		$this->assertEquals($before_donations, $this->donations->find_by_campaign(1), 'A donation amount was rewritten');
	}

	public function test_an_unconfirmed_exponent_change_is_rejected_when_data_exists()
	{
		$errors = $this->settings->validate($this->input(array(
			'donationcampaigns_currency_exponent' => 3,
		)));

		$this->assertContains('DONATIONCAMPAIGNS_ERROR_EXPONENT_CONFIRM', $errors);
	}

	public function test_a_confirmed_exponent_change_is_accepted()
	{
		$errors = $this->settings->validate($this->input(array(
			'donationcampaigns_currency_exponent'	=> 3,
			'donationcampaigns_confirm_exponent'	=> true,
		)));

		$this->assertSame(array(), $errors);
	}

	public function test_an_unconfirmed_exponent_change_writes_nothing()
	{
		try
		{
			$this->settings->save($this->input(array('donationcampaigns_currency_exponent' => 3)));
		}
		catch (donationcampaigns_exception $e)
		{
			// expected
		}

		$this->assertSame(2, (int) $this->config['donationcampaigns_currency_exponent']);
		$this->assertSame('EUR', $this->config['donationcampaigns_currency_code'], 'Other fields were written anyway');
	}

	public function test_confirmation_is_not_required_when_there_is_no_data()
	{
		$this->donations->delete_by_campaign_ids(array(1, 2));
		$this->campaigns->delete_by_ids(array(1, 2));

		$this->assertSame(array(), $this->settings->validate($this->input(array(
			'donationcampaigns_currency_exponent' => 4,
		))));
	}
}
