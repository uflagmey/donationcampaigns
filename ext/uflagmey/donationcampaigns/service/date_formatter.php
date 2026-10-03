<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

/**
 * Renders a donation DATE.
 *
 * A donation date is a calendar day — the day the money arrived — stored as
 * midnight UTC of that day (donation_controller::parse_date()). It is not a
 * moment in time, so it must not be rendered like one: phpBB's
 * user::format_date() converts to the viewer's time zone and appends a time of
 * day, which shows a viewer west of UTC the previous day and everyone a
 * meaningless "00:00" or "2 am".
 *
 * This service renders the stored day in UTC, through phpbb\datetime so that
 * month names are translated by phpBB's own language pack, in a format the
 * translator owns (DONATIONCAMPAIGNS_DATE_FORMAT).
 */
class date_formatter
{
	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\user */
	protected $user;

	/**
	 * @param \phpbb\language\language $language
	 * @param \phpbb\user              $user
	 */
	public function __construct(\phpbb\language\language $language, $user)
	{
		$this->language = $language;
		$this->user = $user;
	}

	/**
	 * @param int $timestamp midnight UTC of the day
	 * @return string the day, or '' when no date is stored
	 */
	public function format_date($timestamp)
	{
		$timestamp = (int) $timestamp;

		if ($timestamp <= 0)
		{
			return '';
		}

		// load_extension() returns early once loaded, so this costs nothing
		// after the first call and removes any assumption about load order.
		$this->language->add_lang('common', 'uflagmey/donationcampaigns');

		$date = new \phpbb\datetime($this->user, '@' . $timestamp, new \DateTimeZone('UTC'));

		// '@' timestamps are created in UTC regardless of the zone argument;
		// set it explicitly so format() never sees the viewer's zone.
		$date->setTimezone(new \DateTimeZone('UTC'));

		// force_absolute: never "Today" / "Yesterday", which are relative to
		// the viewer's clock.
		return $date->format((string) $this->language->lang('DONATIONCAMPAIGNS_DATE_FORMAT'), true);
	}
}
