<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

/**
 * Keeps the shipped language packs in step.
 *
 * A translation drifts silently: an English key added later simply falls back
 * to English on a German board, and nothing fails. These tests make that a
 * build error instead, and check the mechanical properties a translator can
 * get wrong without noticing — a lost placeholder, a plural array flattened to
 * a string, a stray BOM.
 */
class language_pack_test extends \phpbb_test_case
{
	/** @var string */
	protected $package;

	/** Every language shipped besides the English original. */
	const TRANSLATIONS = array('de');

	const FILES = array(
		'common.php',
		'info_acp_donationcampaigns.php',
		'permissions_donationcampaigns.php',
	);

	public function setUp(): void
	{
		parent::setUp();

		$this->package = dirname(dirname(__DIR__));
	}

	/**
	 * @param string $iso
	 * @param string $file
	 * @return array
	 */
	protected function strings($iso, $file)
	{
		$lang = array();

		include $this->package . '/language/' . $iso . '/' . $file;

		return $lang;
	}

	public function language_files()
	{
		$cases = array();

		foreach (self::TRANSLATIONS as $iso)
		{
			foreach (self::FILES as $file)
			{
				$cases[$iso . ':' . $file] = array($iso, $file);
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider language_files
	 */
	public function test_the_translation_file_exists($iso, $file)
	{
		$this->assertFileExists($this->package . '/language/' . $iso . '/' . $file);
	}

	/**
	 * @dataProvider language_files
	 */
	public function test_the_key_set_matches_english_exactly($iso, $file)
	{
		$en = array_keys($this->strings('en', $file));
		$translated = array_keys($this->strings($iso, $file));

		sort($en);
		sort($translated);

		$this->assertSame(
			$en,
			$translated,
			"The {$iso} pack does not define the same keys as English for {$file}"
		);
	}

	/**
	 * A plural array flattened to a single string breaks phpBB's plural
	 * selection silently — every count renders the one form.
	 *
	 * @dataProvider language_files
	 */
	public function test_plural_forms_stay_plural_forms($iso, $file)
	{
		$en = $this->strings('en', $file);
		$translated = $this->strings($iso, $file);
		$checked = 0;

		foreach ($en as $key => $value)
		{
			if (!is_array($value))
			{
				continue;
			}

			$checked++;

			$this->assertIsArray($translated[$key], "{$iso}: {$key} lost its plural forms");
			$this->assertSame(
				array_keys($value),
				array_keys($translated[$key]),
				"{$iso}: {$key} declares different plural categories from English"
			);
		}

		// Not every file has plural forms; recording that is the assertion.
		$this->assertSame(
			count(array_filter($en, 'is_array')),
			$checked,
			'A plural form was skipped'
		);
	}

	/**
	 * A dropped or renumbered placeholder produces a message with a literal
	 * %s in it, or a PHP warning, at the moment something has gone wrong.
	 *
	 * @dataProvider language_files
	 */
	public function test_placeholders_survive_translation($iso, $file)
	{
		$en = $this->strings('en', $file);
		$translated = $this->strings($iso, $file);

		foreach ($en as $key => $value)
		{
			foreach ((array) $value as $form => $english)
			{
				$expected = $this->placeholders($english);
				$actual = $this->placeholders(is_array($value) ? $translated[$key][$form] : $translated[$key]);

				$this->assertSame($expected, $actual, "{$iso}: {$key} changed its placeholders");
			}
		}
	}

	/**
	 * @param string $text
	 * @return array
	 */
	protected function placeholders($text)
	{
		preg_match_all('/%\d+\$[sd]|%[sd]/', $text, $matches);

		$found = $matches[0];
		sort($found);

		return $found;
	}

	/**
	 * @dataProvider language_files
	 */
	public function test_the_file_is_utf8_without_a_bom($iso, $file)
	{
		$contents = file_get_contents($this->package . '/language/' . $iso . '/' . $file);

		$this->assertStringNotContainsString("\xEF\xBB\xBF", $contents, "{$iso}/{$file} carries a BOM");
		$this->assertTrue(mb_check_encoding($contents, 'UTF-8'), "{$iso}/{$file} is not valid UTF-8");
		$this->assertStringNotContainsString("\r\n", $contents, "{$iso}/{$file} uses CRLF line endings");
		$this->assertDoesNotMatchRegularExpression('/[ \t]+\n/', $contents, "{$iso}/{$file} has trailing whitespace");
	}

	/**
	 * @dataProvider language_files
	 */
	public function test_the_file_guards_against_direct_access($iso, $file)
	{
		$contents = file_get_contents($this->package . '/language/' . $iso . '/' . $file);

		$this->assertStringContainsString("if (!defined('IN_PHPBB'))", $contents);
	}

	/**
	 * Nothing may be left as the English original except where the English IS
	 * the correct German — proper nouns and technical identifiers.
	 *
	 * @dataProvider language_files
	 */
	public function test_no_string_is_left_untranslated($iso, $file)
	{
		$allowed = array(
			// Identical in both languages, correctly.
			'Anonym', 'Status', 'PayPal',
		);

		$en = $this->strings('en', $file);
		$translated = $this->strings($iso, $file);
		$copied = array();

		foreach ($en as $key => $value)
		{
			foreach ((array) $value as $form => $english)
			{
				$other = is_array($value) ? $translated[$key][$form] : $translated[$key];

				if ($english === $other && !in_array($english, $allowed, true))
				{
					$copied[] = $key;
				}
			}
		}

		$this->assertSame(array(), $copied, "{$iso}: these strings are still the English original");
	}
}
