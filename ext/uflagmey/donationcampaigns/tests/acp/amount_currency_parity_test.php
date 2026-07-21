<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * Amount inputs and their currency label, across every ACP form that has one.
 *
 * The campaign target field gained a currency label beside its input during
 * the RC polish; the donation amount field was missed and shipped in
 * 1.0.0-rc1 without one. This reads the shipped templates as files and asserts
 * that every editable amount input is followed by the SAME currency label, so
 * the two presentations cannot drift apart again.
 *
 * It is deliberately a file-level test: the drift is a template inconsistency,
 * and a rendered-output test driving one mode cannot see the other template.
 */
class amount_currency_parity_test extends \phpbb_test_case
{
	/**
	 * The label every amount input must carry beside it: the board's one
	 * configured symbol, escaped, as a sibling of the input. Not a second
	 * configuration key, and never part of the input value.
	 */
	const CURRENCY_LABEL = '<strong>{DONATIONCAMPAIGNS_CURRENCY_SYMBOL|e}</strong>';

	/**
	 * @return array label => [template file, amount input name]
	 */
	public function amount_inputs()
	{
		return array(
			'campaign target'	=> array('acp_donationcampaigns_campaign_edit.html', 'target_amount'),
			'donation amount'	=> array('acp_donationcampaigns_donation_edit.html', 'donation_amount'),
		);
	}

	/**
	 * @param string $file
	 * @return string
	 */
	private function template($file)
	{
		return file_get_contents(dirname(dirname(__DIR__)) . '/adm/style/' . $file);
	}

	/**
	 * @dataProvider amount_inputs
	 */
	public function test_the_amount_input_is_followed_by_the_currency_label($file, $input_name)
	{
		$markup = $this->template($file);

		$this->assertMatchesRegularExpression(
			'#name="' . preg_quote($input_name, '#') . '"[^>]*/>\s*' . preg_quote(self::CURRENCY_LABEL, '#') . '#',
			$markup,
			"{$input_name} in {$file} is not followed by the shared currency label"
		);
	}

	/**
	 * The symbol is a label, not a value: no amount input may carry the symbol
	 * variable inside its own value attribute, or the parser would be handed
	 * more than the number.
	 *
	 * @dataProvider amount_inputs
	 */
	public function test_the_amount_value_never_carries_the_symbol($file, $input_name)
	{
		$markup = $this->template($file);

		preg_match('#name="' . preg_quote($input_name, '#') . '"[^>]*value="([^"]*)"#', $markup, $m);

		$this->assertNotEmpty($m, "Could not find the {$input_name} value attribute in {$file}");
		$this->assertStringNotContainsString('CURRENCY_SYMBOL', $m[1], 'The currency symbol must not be inside the input value');
	}
}
