<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * A mock user that can also format a date.
 *
 * phpBB's own mock omits format_date(), but the donation list renders receipt
 * dates through it — the board's timezone and format settings belong to the
 * user, not to this extension, so the call has to stay.
 */
class formatting_user extends \phpbb_mock_user
{
	/**
	 * @param int $gmepoch
	 * @param string|bool $format
	 * @param bool $forcedate
	 * @return string
	 */
	public function format_date($gmepoch, $format = false, $forcedate = false)
	{
		return gmdate('Y-m-d H:i', (int) $gmepoch);
	}
}
