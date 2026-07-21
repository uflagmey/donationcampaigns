<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

/**
 * Presentation language keys must have a consumer.
 *
 * FORM_* and NOTICE_* keys are always referenced LITERALLY — as {L_KEY} in a
 * template or lang('KEY') in PHP — so one with no such reference is dead weight.
 * The RC2 cutover retired the ACP campaign form and orphaned several of these;
 * this guard stops that class of cruft from silently returning.
 *
 * Deliberately narrow. LOG_, ACL_ and ERROR_ keys are composed or looked up
 * dynamically (from a stored operation, a permission option, a caught
 * exception's key), so a literal-reference search cannot see their consumers
 * and would false-positive; they are out of scope. The check makes no
 * assumption about file ordering or formatting — it extracts the defined keys
 * by name and searches the shipped templates and PHP for each.
 */
class orphan_language_test extends \phpbb_test_case
{
	/**
	 * @return string extension package root
	 */
	protected function package()
	{
		return dirname(dirname(__DIR__));
	}

	/**
	 * Every FORM_ and NOTICE_ presentation key defined in any English file.
	 *
	 * @return string[]
	 */
	protected function presentation_keys()
	{
		$keys = array();

		foreach (glob($this->package() . '/language/en/*.php') as $file)
		{
			preg_match_all("/'(DONATIONCAMPAIGNS_(?:FORM|NOTICE)_[A-Z_]+)'/", file_get_contents($file), $matches);

			foreach ($matches[1] as $key)
			{
				$keys[$key] = true;
			}
		}

		return array_keys($keys);
	}

	/**
	 * The contents of every shipped template and production PHP file (language
	 * files and tests excluded — those define keys, they do not consume them).
	 *
	 * @return string[]
	 */
	protected function consumer_sources()
	{
		$sources = array();

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->package()));

		foreach ($iterator as $file)
		{
			$path = $file->getPathname();

			if (!in_array(pathinfo($path, PATHINFO_EXTENSION), array('html', 'php'), true))
			{
				continue;
			}

			if (strpos($path, '/language/') !== false || strpos($path, '/tests/') !== false)
			{
				continue;
			}

			$sources[] = file_get_contents($path);
		}

		return $sources;
	}

	public function test_every_presentation_key_has_a_consumer()
	{
		$keys = $this->presentation_keys();

		$this->assertNotEmpty($keys, 'No FORM_/NOTICE_ presentation keys were found to check');

		$sources = $this->consumer_sources();

		foreach ($keys as $key)
		{
			// The negative lookahead stops a key from being counted as used only
			// because it is a prefix of a longer key (FORM_TOPIC vs
			// FORM_TOPIC_EXPLAIN_FIXED).
			$pattern = '/' . preg_quote($key, '/') . '(?![A-Z_])/';

			$found = false;

			foreach ($sources as $contents)
			{
				if (preg_match($pattern, $contents))
				{
					$found = true;
					break;
				}
			}

			$this->assertTrue(
				$found,
				"Language key {$key} is defined but has no template or PHP consumer — remove it or wire it up."
			);
		}
	}
}
