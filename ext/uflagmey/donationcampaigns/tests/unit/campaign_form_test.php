<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\service\campaign_form;
use uflagmey\donationcampaigns\service\currency_formatter;

/**
 * Reading the campaign form — shared by the frontend form and the posting
 * panel (beta3). The behaviour moved here unchanged from campaign_controller;
 * the controller's tests remain its regression net, these pin the pieces.
 */
class campaign_form_test extends \phpbb_test_case
{
	/** @var \phpbb\language\language */
	protected $language;

	public function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path;
		require_once $phpbb_root_path . '../tests/mock/request.php';

		$this->language = $this->getMockBuilder('\\phpbb\\language\\language')->disableOriginalConstructor()->getMock();
		$this->language->method('lang')->willReturnCallback(function ($key) {
			$map = array(
				'DONATIONCAMPAIGNS_DECIMAL_SEPARATOR'	=> '.',
				'DONATIONCAMPAIGNS_THOUSANDS_SEPARATOR'	=> ',',
				'DONATIONCAMPAIGNS_LINK_TEXT_DEFAULT'	=> 'How to donate',
			);

			return isset($map[$key]) ? $map[$key] : $key;
		});
	}

	protected function form(array $post = array())
	{
		$config = new \phpbb\config\config(array('donationcampaigns_currency_symbol' => '€', 'donationcampaigns_currency_exponent' => 2));

		return new campaign_form(
			new \phpbb_mock_request(array(), $post),
			new currency_formatter($this->language, $config),
			$config,
			$this->language
		);
	}

	public function test_values_are_read_raw()
	{
		$values = $this->form(array(
			'campaign_title' => 'Kosten & "Miete" <2026>',
			'target_amount' => '250.00',
			'external_url' => 'https://example.org/?a=1&b=2',
			'external_link_text' => 'Spenden & helfen',
			'show_donor_names' => '1',
		))->submitted_values();

		$this->assertSame('Kosten & "Miete" <2026>', $values['campaign_title']);
		$this->assertSame('250.00', $values['target_amount']);
		$this->assertSame('https://example.org/?a=1&b=2', $values['external_url']);
		$this->assertSame('Spenden & helfen', $values['external_link_text']);
		$this->assertTrue($values['show_donor_names']);
		$this->assertFalse($values['show_donation_count']);
		$this->assertFalse($values['show_donation_date']);
	}

	public function test_an_array_where_text_is_expected_reads_as_empty()
	{
		$this->assertSame('', $this->form(array('campaign_title' => array('x')))->submitted_values()['campaign_title']);
	}

	/**
	 * The posting form reads the same fields under a prefix (WD4) and
	 * ignores the unprefixed names another extension may use.
	 */
	public function test_a_prefix_selects_the_prefixed_fields_only()
	{
		$values = $this->form(array(
			'campaign_title'						=> 'Someone else',
			'donationcampaigns_campaign_title'		=> 'Ours',
			'donationcampaigns_target_amount'		=> '10.00',
			'donationcampaigns_show_donation_date'	=> '1',
		))->submitted_values('donationcampaigns_');

		$this->assertSame('Ours', $values['campaign_title']);
		$this->assertSame('10.00', $values['target_amount']);
		$this->assertTrue($values['show_donation_date']);
		$this->assertFalse($values['show_donor_names']);
	}

	public function test_the_target_is_parsed_to_minor_units()
	{
		$this->assertSame(array(25000, ''), $this->form()->parse_target('250.00'));
	}

	public function test_a_grouped_target_is_refused_with_the_formatter_key()
	{
		list($minor, $error) = $this->form()->parse_target('1,000.00');

		$this->assertSame(0, $minor);
		$this->assertNotSame('', $error);
	}

	public function test_an_amount_error_replaces_the_generic_target_error_and_comes_first()
	{
		$this->assertSame(
			array('DONATIONCAMPAIGNS_ERROR_AMOUNT_FORMAT', 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED'),
			$this->form()->merge_amount_error(
				array('DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED', 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE'),
				'DONATIONCAMPAIGNS_ERROR_AMOUNT_FORMAT'
			)
		);
	}

	public function test_no_amount_error_leaves_the_errors_alone()
	{
		$errors = array('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE');

		$this->assertSame($errors, $this->form()->merge_amount_error($errors, ''));
	}

	public function test_new_campaign_defaults()
	{
		$this->assertSame(array(
			'campaign_title'		=> '',
			'campaign_desc'			=> '',
			'target_amount'			=> '',
			'external_url'			=> '',
			'external_link_text'	=> 'How to donate',
			'show_donor_names'		=> true,
			'show_donation_count'	=> true,
			'show_donation_date'	=> true,
		), $this->form()->defaults());
	}
}
