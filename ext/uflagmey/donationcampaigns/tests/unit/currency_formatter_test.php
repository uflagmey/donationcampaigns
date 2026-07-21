<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\service\currency_formatter;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * The money path is the highest-risk pure logic in this extension.
 *
 * The obvious implementation, (int) ($input * 100), loses a cent on ordinary
 * values: '8.70' becomes 869. The data sets below include that case
 * deliberately, and the round-trip test would fail on roughly a third of all
 * values under a float implementation.
 */
class currency_formatter_test extends \phpbb_test_case
{
	/** @var currency_formatter */
	protected $formatter;

	public function setUp(): void
	{
		parent::setUp();

		$this->formatter = $this->formatter_for('en');
	}

	/**
	 * A formatter bound to one language's separators.
	 *
	 * The separators are language keys, so the formatter reads them from the
	 * language service rather than from the currency: a German board showing
	 * dollars still writes 1.234,56, because the reader is German.
	 *
	 * @param string $iso
	 * @return currency_formatter
	 */
	protected function formatter_for($iso)
	{
		global $phpbb_root_path, $phpEx;

		$separators = array(
			'en' => array('.', ','),
			'de' => array(',', '.'),
		);

		$language = $this->getMockBuilder('\\phpbb\\language\\language')
			->disableOriginalConstructor()
			->getMock();

		$language->method('lang')->willReturnCallback(function ($key) use ($separators, $iso) {
			if ($key === 'DONATIONCAMPAIGNS_DECIMAL_SEPARATOR')
			{
				return $separators[$iso][0];
			}

			if ($key === 'DONATIONCAMPAIGNS_THOUSANDS_SEPARATOR')
			{
				return $separators[$iso][1];
			}

			return $key;
		});

		return new currency_formatter($language);
	}

	public function parse_valid_data()
	{
		return array(
			// input, exponent, expected minor units
			array('10.00', 2, 1000),
			// The canonical float-failure case: (int) (8.70 * 100) === 869.
			array('8.70', 2, 870),
			array('0.01', 2, 1),
			array('0.10', 2, 10),
			array('10,50', 2, 1050),		// comma separator accepted
			array('10', 2, 1000),			// no fractional part
			array('10.5', 2, 1050),			// short fractional part, right-padded
			array('0', 2, 0),
			array('1234567.89', 2, 123456789),
			array(' 10.00 ', 2, 1000),		// surrounding whitespace tolerated
			array('100', 0, 100),			// JPY-style, no minor units
			array('1.234', 3, 1234),		// KWD-style, three minor digits
			array('1.2', 3, 1200),
		);
	}

	/**
	 * @dataProvider parse_valid_data
	 */
	public function test_parse_accepts_valid_input($input, $exponent, $expected)
	{
		$this->assertSame($expected, $this->formatter->parse($input, $exponent));
	}

	public function parse_invalid_data()
	{
		return array(
			array('', 2),
			array('   ', 2),
			array('abc', 2),
			array('-5', 2),					// negative rejected
			array('1e3', 2),				// scientific notation rejected
			array('10.001', 2),				// more fractional digits than allowed
			array('10.00.00', 2),			// malformed
			array('10.', 2),				// trailing separator
			array('.50', 2),				// missing major part
			array('10.5', 0),				// fractional part at exponent 0
			array('99999999999', 2),		// exceeds the column ceiling
			array('1 000.00', 2),			// thousands separator not supported
			array('+10.00', 2),
		);
	}

	/**
	 * @dataProvider parse_invalid_data
	 */
	public function test_parse_rejects_invalid_input($input, $exponent)
	{
		$this->expectException(donationcampaigns_exception::class);

		$this->formatter->parse($input, $exponent);
	}

	public function format_data()
	{
		return array(
			array(1000, 2, '10.00'),
			array(870, 2, '8.70'),
			array(1, 2, '0.01'),
			array(0, 2, '0.00'),
			array(123456789, 2, '1,234,567.89'),
			array(100, 0, '100'),
			array(1234, 3, '1.234'),
			array(1200, 3, '1.200'),
		);
	}

	/**
	 * @dataProvider format_data
	 */
	public function test_format($minor_units, $exponent, $expected)
	{
		$this->assertSame($expected, $this->formatter->format($minor_units, $exponent));
	}

	/**
	 * Parsing and formatting must round-trip exactly.
	 *
	 * This is the property a float-based implementation silently violates.
	 */
	public function test_round_trip_is_lossless()
	{
		for ($minor = 0; $minor <= 2000; $minor++)
		{
			$formatted = $this->formatter->format($minor, 2);

			$this->assertSame(
				$minor,
				$this->formatter->parse($formatted, 2),
				"Round trip failed for {$minor} minor units (formatted as '{$formatted}')"
			);
		}
	}

	/**
	 * The exception must carry a language key so the ACP can render it in the
	 * administrator's own language rather than a hard-coded English string.
	 */
	public function test_rejection_carries_a_language_key()
	{
		try
		{
			$this->formatter->parse('not a number', 2);
			$this->fail('Expected donationcampaigns_exception was not thrown');
		}
		catch (donationcampaigns_exception $e)
		{
			$this->assertStringStartsWith('DONATIONCAMPAIGNS_ERROR_', $e->get_language_key());
		}
	}

	// ------------------------------------------------ locale-aware output

	/**
	 * DISPLAY output: grouped, with the reader's decimal separator.
	 *
	 * @dataProvider display_data
	 */
	public function test_display_formatting_follows_the_language($iso, $minor, $exponent, $expected)
	{
		$this->assertSame($expected, $this->formatter_for($iso)->format($minor, $exponent));
	}

	public function display_data()
	{
		return array(
			// the two cases named in the brief
			'en 5.00'			=> array('en', 500, 2, '5.00'),
			'de 5,00'			=> array('de', 500, 2, '5,00'),
			'en 1,234.56'		=> array('en', 123456, 2, '1,234.56'),
			'de 1.234,56'		=> array('de', 123456, 2, '1.234,56'),

			// every exponent the settings allow
			'en exp 0'			=> array('en', 1234567, 0, '1,234,567'),
			'de exp 0'			=> array('de', 1234567, 0, '1.234.567'),
			'en exp 1'			=> array('en', 123456, 1, '12,345.6'),
			'de exp 1'			=> array('de', 123456, 1, '12.345,6'),
			'en exp 3'			=> array('en', 123456789, 3, '123,456.789'),
			'de exp 3'			=> array('de', 123456789, 3, '123.456,789'),
			'en exp 4'			=> array('en', 123456789, 4, '12,345.6789'),
			'de exp 4'			=> array('de', 123456789, 4, '12.345,6789'),

			// zero and values below one unit
			'en zero'			=> array('en', 0, 2, '0.00'),
			'de zero'			=> array('de', 0, 2, '0,00'),
			'en sub-unit'		=> array('en', 5, 2, '0.05'),
			'de sub-unit'		=> array('de', 5, 2, '0,05'),
			'de sub-unit exp 4'	=> array('de', 500, 4, '0,0500'),

			// grouping boundaries
			'en 999'			=> array('en', 99900, 2, '999.00'),
			'en 1000'			=> array('en', 100000, 2, '1,000.00'),
			'de 1000'			=> array('de', 100000, 2, '1.000,00'),
			'en millions'		=> array('en', 1234567890, 2, '12,345,678.90'),
			'de millions'		=> array('de', 1234567890, 2, '12.345.678,90'),
			'at the ceiling'	=> array('de', 4294967295, 2, '42.949.672,95'),
		);
	}

	/**
	 * EDITABLE output: the reader's decimal separator, and NO grouping --
	 * the parser deliberately refuses grouped input, so a grouped value in a
	 * form field would be rejected the moment the administrator pressed save.
	 *
	 * @dataProvider input_data
	 */
	public function test_editable_formatting_never_groups($iso, $minor, $exponent, $expected)
	{
		$this->assertSame($expected, $this->formatter_for($iso)->format_for_input($minor, $exponent));
	}

	public function input_data()
	{
		return array(
			'en small'		=> array('en', 500, 2, '5.00'),
			'de small'		=> array('de', 500, 2, '5,00'),
			'en thousands'	=> array('en', 123456, 2, '1234.56'),
			'de thousands'	=> array('de', 123456, 2, '1234,56'),
			'en millions'	=> array('en', 1234567890, 2, '12345678.90'),
			'de millions'	=> array('de', 1234567890, 2, '12345678,90'),
			'de exp 0'		=> array('de', 1234567, 0, '1234567'),
			'de zero'		=> array('de', 0, 2, '0,00'),
		);
	}

	/**
	 * @dataProvider input_data
	 */
	public function test_editable_output_carries_no_thousands_separator($iso, $minor, $exponent)
	{
		$separator = ($iso === 'de') ? '.' : ',';

		$this->assertStringNotContainsString(
			$separator,
			$this->formatter_for($iso)->format_for_input($minor, $exponent),
			'A grouped value in a form field would be rejected on save'
		);
	}

	/**
	 * THE ROUND TRIP that matters: whatever the form field shows must come
	 * back through the parser as the same integer.
	 *
	 * @dataProvider round_trip_data
	 */
	public function test_editable_output_parses_back_to_the_same_minor_units($iso, $exponent)
	{
		$formatter = $this->formatter_for($iso);

		foreach (array(0, 1, 5, 99, 100, 999, 1000, 123456, 1234567890) as $minor)
		{
			$shown = $formatter->format_for_input($minor, $exponent);

			$this->assertSame(
				$minor,
				$formatter->parse($shown, $exponent),
				"{$iso}: {$minor} at exponent {$exponent} did not survive the round trip via '{$shown}'"
			);
		}
	}

	public function round_trip_data()
	{
		$cases = array();

		foreach (array('en', 'de') as $iso)
		{
			foreach (range(0, 4) as $exponent)
			{
				$cases["{$iso} exp {$exponent}"] = array($iso, $exponent);
			}
		}

		return $cases;
	}

	/**
	 * Parsing stays language-agnostic and tolerant: both separators are
	 * accepted as the decimal mark whatever the reader's language.
	 *
	 * @dataProvider tolerant_input_data
	 */
	public function test_parsing_accepts_either_decimal_separator($iso, $input, $expected)
	{
		$this->assertSame($expected, $this->formatter_for($iso)->parse($input, 2));
	}

	public function tolerant_input_data()
	{
		return array(
			'en reads dot'		=> array('en', '1234.56', 123456),
			'en reads comma'	=> array('en', '1234,56', 123456),
			'de reads comma'	=> array('de', '1234,56', 123456),
			'de reads dot'		=> array('de', '1234.56', 123456),
		);
	}

	/**
	 * Grouped input stays refused. Guessing whether 1.234 means one thousand
	 * or one-point-two-three-four is how money goes missing.
	 *
	 * @dataProvider ambiguous_input_data
	 */
	public function test_grouped_input_is_still_refused($iso, $input)
	{
		$this->expectException('\\uflagmey\\donationcampaigns\\exception\\donationcampaigns_exception');

		$this->formatter_for($iso)->parse($input, 2);
	}

	public function ambiguous_input_data()
	{
		return array(
			'de grouped'		=> array('de', '1.234,56'),
			'en grouped'		=> array('en', '1,234.56'),
			'de grouped plain'	=> array('de', '1.234.567'),
			'en grouped plain'	=> array('en', '1,234,567'),
		);
	}

	/**
	 * A negative cannot reach storage -- the column is UINT -- but the
	 * formatter is a pure function and must not mangle one if handed it.
	 */
	public function test_a_negative_value_keeps_its_sign_and_grouping()
	{
		$this->assertSame('-5.00', $this->formatter_for('en')->format(-500, 2));
		$this->assertSame('-5,00', $this->formatter_for('de')->format(-500, 2));
		$this->assertSame('-12,345.67', $this->formatter_for('en')->format(-1234567, 2));
		$this->assertSame('-12.345,67', $this->formatter_for('de')->format(-1234567, 2));
	}

	/**
	 * The separators come from language keys, so they are translator-owned
	 * and a third language needs no code change.
	 */
	public function test_the_separators_come_from_language_keys()
	{
		$source = file_get_contents(dirname(dirname(__DIR__)) . '/service/currency_formatter.php');

		$this->assertStringContainsString('DONATIONCAMPAIGNS_DECIMAL_SEPARATOR', $source);
		$this->assertStringContainsString('DONATIONCAMPAIGNS_THOUSANDS_SEPARATOR', $source);
	}

	/**
	 * Grouping must not reach for number_format(), which routes through a
	 * float and is banned across this package for that reason.
	 */
	public function test_the_money_path_constructs_no_float()
	{
		$source = file_get_contents(dirname(dirname(__DIR__)) . '/service/currency_formatter.php');

		foreach (array('number_format', '(float)', '(double)', 'floatval', 'round(', 'sprintf') as $banned)
		{
			$this->assertStringNotContainsString($banned, $source, "currency_formatter uses {$banned}");
		}
	}
}
