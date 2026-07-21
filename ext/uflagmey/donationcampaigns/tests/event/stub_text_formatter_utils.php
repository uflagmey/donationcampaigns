<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

/**
 * Stands in for phpBB's text_formatter.utils during post deletion.
 *
 * Core reaches for it while unindexing removed posts. It plays no part in the
 * campaign cascade, so it is stubbed rather than stood up.
 */
class stub_text_formatter_utils
{
	public function clean_formatting($text)
	{
		return $text;
	}

	public function remove_bbcode($text, $bbcode_name, $depth = 0)
	{
		return $text;
	}

	public function unparse($text)
	{
		return $text;
	}
}
