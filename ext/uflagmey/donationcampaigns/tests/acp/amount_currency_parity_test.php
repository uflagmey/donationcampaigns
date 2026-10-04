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
		// Shared includes pasted in (the campaign fields moved into one in beta3).
		return \uflagmey\donationcampaigns\tests\template_renderer::inline_partials(
			file_get_contents(dirname(dirname(__DIR__)) . '/' . $path)
		);
	}

	/**
	 * The label sits on the side the board puts the symbol of every displayed
	 * amount (ADR-017): after the field by default, before it when "symbol
	 * before the amount" is set. Both branches must exist, keyed on the same
	 * flag, around the same input.
	 *
	 * @dataProvider amount_inputs
	 */
	public function test_the_currency_label_sits_on_the_configured_side_of_the_input($file, $input_name)
	{
		$markup = $this->template($file);
		$input = '<input id="' . preg_quote($input_name, '#') . '"[^>]*name="' . preg_quote($input_name, '#') . '"[^>]*>';
		$label = preg_quote(self::CURRENCY_LABEL, '#');

		$this->assertMatchesRegularExpression(
			'#<!-- IF S_DONATIONCAMPAIGNS_SYMBOL_BEFORE -->' . $label . '\s*<!-- ENDIF -->' . $input . '#',
			$markup,
			"{$input_name} in {$file} has no currency label before it for the symbol-before layout"
		);
		$this->assertMatchesRegularExpression(
			'#' . $input . '<!-- IF not S_DONATIONCAMPAIGNS_SYMBOL_BEFORE -->\s*' . $label . '<!-- ENDIF -->#',
			$markup,
			"{$input_name} in {$file} has no currency label after it for the default layout"
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
