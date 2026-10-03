<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\service\date_formatter;

/**
 * A donation date is a calendar DATE, not a moment.
 *
 * It is stored as midnight UTC of the day the money arrived
 * (donation_controller::parse_date()). Rendering it like a timestamp — in the
 * viewer's time zone, with a time of day — shows a viewer west of UTC the
 * previous day. These tests pin the date to its own day for every viewer.
 */
class date_formatter_test extends \phpbb_test_case
{
	/** 2026-10-03 00:00:00 UTC */
	const OCT_3 = 1790985600;

	/**
	 * @param string $iso
	 * @param string $viewer_zone
	 * @return date_formatter
	 */
	protected function formatter_for($iso, $viewer_zone = 'UTC')
	{
		$formats = array('de' => 'd.m.Y', 'en' => 'j M Y');
		$months = array(
			'de' => array('October' => 'Oktober', 'Oct' => 'Okt', 'May_short' => 'Mai'),
			'en' => array('October' => 'October', 'Oct' => 'Oct', 'May_short' => 'May'),
		);

		$language = $this->getMockBuilder('\phpbb\language\language')->disableOriginalConstructor()->getMock();
		$language->method('lang')->willReturnCallback(function ($key) use ($formats, $iso) {
			return ($key === 'DONATIONCAMPAIGNS_DATE_FORMAT') ? $formats[$iso] : $key;
		});

		$user = new \phpbb_mock_user();
		$user->lang = array('datetime' => $months[$iso]);
		$user->lang_name = $iso;
		$user->timezone = new \DateTimeZone($viewer_zone);
		$user->date_format = 'D M d, Y g:i a';

		return new date_formatter($language, $user);
	}

	public function test_it_renders_the_stored_day_in_the_language_format()
	{
		$this->assertSame('03.10.2026', $this->formatter_for('de')->format_date(self::OCT_3));
		$this->assertSame('3 Oct 2026', $this->formatter_for('en')->format_date(self::OCT_3));
	}

	/**
	 * THE regression: midnight UTC rendered in Honolulu (UTC-10) is 14:00 on
	 * the previous day. A date-only value must not move.
	 */
	public function test_a_viewer_west_of_utc_sees_the_same_day()
	{
		$this->assertSame('03.10.2026', $this->formatter_for('de', 'Pacific/Honolulu')->format_date(self::OCT_3));
	}

	public function test_a_viewer_east_of_utc_sees_the_same_day()
	{
		$this->assertSame('03.10.2026', $this->formatter_for('de', 'Pacific/Kiritimati')->format_date(self::OCT_3));
	}

	/**
	 * Month names come from phpBB's own datetime translations, so a format
	 * with a month name is localised without the extension carrying any.
	 */
	public function test_month_names_are_translated()
	{
		$language = $this->getMockBuilder('\phpbb\language\language')->disableOriginalConstructor()->getMock();
		$language->method('lang')->willReturn('j. F Y');

		$user = new \phpbb_mock_user();
		$user->lang = array('datetime' => array('October' => 'Oktober', 'May_short' => 'Mai'));
		$user->lang_name = 'de';
		$user->timezone = new \DateTimeZone('UTC');

		$this->assertSame('3. Oktober 2026', (new date_formatter($language, $user))->format_date(self::OCT_3));
	}

	/**
	 * No time of day ever appears, whatever the board's own date format is.
	 */
	public function test_no_time_of_day_is_shown()
	{
		$this->assertDoesNotMatchRegularExpression('/\d{1,2}:\d{2}/', $this->formatter_for('en')->format_date(self::OCT_3));
	}

	public function test_an_unset_date_renders_as_empty()
	{
		$this->assertSame('', $this->formatter_for('en')->format_date(0));
	}
}
