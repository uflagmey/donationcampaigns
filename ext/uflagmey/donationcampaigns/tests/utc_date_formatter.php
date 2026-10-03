<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests;

/**
 * A real date_formatter over a minimal English user, for fixtures that only
 * need donation dates to render: "3 Oct 2026".
 */
class utc_date_formatter
{
	/**
	 * @param \phpbb\language\language $language
	 * @return \uflagmey\donationcampaigns\service\date_formatter
	 */
	public static function create(\phpbb\language\language $language)
	{
		$user = new \phpbb_mock_user();
		$user->lang = array('datetime' => array('May_short' => 'May'));
		$user->lang_name = 'en';
		$user->timezone = new \DateTimeZone('UTC');

		return new \uflagmey\donationcampaigns\service\date_formatter($language, $user);
	}
}
