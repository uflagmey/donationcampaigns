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
 * presentations cannot drift apart. Every rule runs against both templates
 * from one data provider, rendered by phpBB's engine in both layouts, so a
 * difference between the two forms fails here whichever template drifted.
 */
class amount_currency_parity_test extends \phpbb_test_case
{
	/**
	 * The label every amount input must carry beside it: the board's one
	 * configured symbol, escaped, as a sibling of the input. Not a second
	 * configuration key, and never part of the input value. Rendered here for
	 * the symbol '<€>' below.
	 */
	const CURRENCY_LABEL = '<strong>&lt;€&gt;</strong>';

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
	 * A form rendered with phpBB's engine.
	 *
	 * @param string $path Relative to the extension package root
	 * @param bool $symbol_before
	 * @return string
	 */
	private function render($path, $symbol_before)
	{
		return \uflagmey\donationcampaigns\tests\template_renderer::render(
			file_get_contents(dirname(dirname(__DIR__)) . '/' . $path),
			array(
				'S_DONATIONCAMPAIGNS_SYMBOL_BEFORE'	=> $symbol_before,
				'DONATIONCAMPAIGNS_CURRENCY_SYMBOL'	=> '<€>',
				'DONATIONCAMPAIGNS_TARGET_AMOUNT'	=> 'AMOUNT_MARKER',
				'DONATIONCAMPAIGNS_DONATION_AMOUNT'	=> 'AMOUNT_MARKER',
			)
		);
	}

	/**
	 * The label sits on the side the board puts the symbol of every displayed
	 * amount (ADR-017): after the field by default, before it when "symbol
	 * before the amount" is set. Exactly once, beside the same input.
	 *
	 * @dataProvider amount_inputs
	 */
	public function test_the_currency_label_sits_on_the_configured_side_of_the_input($file, $input_name)
	{
		$input = '<input id="' . preg_quote($input_name, '#') . '"[^>]*name="' . preg_quote($input_name, '#') . '"[^>]*>';
		$label = preg_quote(self::CURRENCY_LABEL, '#');

		$before = $this->render($file, true);
		$this->assertMatchesRegularExpression(
			'#' . $label . '\s*' . $input . '#',
			$before,
			"{$input_name} in {$file} has no currency label before it for the symbol-before layout"
		);
		$this->assertSame(1, substr_count($before, self::CURRENCY_LABEL));

		$after = $this->render($file, false);
		$this->assertMatchesRegularExpression(
			'#' . $input . '\s*' . $label . '#',
			$after,
			"{$input_name} in {$file} has no currency label after it for the default layout"
		);
		$this->assertSame(1, substr_count($after, self::CURRENCY_LABEL));
	}

	/**
	 * The symbol is a label, not a value: no amount input may carry the symbol
	 * inside its own value attribute, or the parser would be handed more than
	 * the number.
	 *
	 * @dataProvider amount_inputs
	 */
	public function test_the_amount_value_never_carries_the_symbol($file, $input_name)
	{
		foreach (array(true, false) as $symbol_before)
		{
			preg_match('#name="' . preg_quote($input_name, '#') . '"[^>]*value="([^"]*)"#', $this->render($file, $symbol_before), $m);

			$this->assertNotEmpty($m, "Could not find the {$input_name} value attribute in {$file}");
			$this->assertSame('AMOUNT_MARKER', $m[1], 'The input value must be the amount and nothing else');
		}
	}
}
