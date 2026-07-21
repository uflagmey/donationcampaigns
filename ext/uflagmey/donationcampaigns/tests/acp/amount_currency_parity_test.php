<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * Amount inputs and their currency label, across every form that has one.
 *
 * The campaign target field and the donation amount field — both now on the
 * frontend forms — must show the configured currency the same way, so the two
 * presentations cannot drift apart. This reads the shipped templates as files,
 * wherever they live.
 *
 * It is deliberately a file-level test: the drift is a template inconsistency,
 * and a rendered-output test driving one surface cannot see the other template.
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
	 * @return array label => [template path relative to the package, amount input name]
	 */
	public function amount_inputs()
	{
		return array(
			'campaign target'	=> array('styles/prosilver/template/donationcampaigns_campaign_form.html', 'target_amount'),
			'donation amount'	=> array('styles/prosilver/template/donationcampaigns_donation_form.html', 'donation_amount'),
		);
	}

	/**
	 * @param string $path Relative to the extension package root
	 * @return string
	 */
	private function template($path)
	{
		return file_get_contents(dirname(dirname(__DIR__)) . '/' . $path);
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
