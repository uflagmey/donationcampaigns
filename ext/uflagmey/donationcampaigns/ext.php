<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns;

class ext extends \phpbb\extension\base
{
	/** Tested floor. Earlier 3.3.x releases are untested and unsupported. */
	const MIN_PHPBB_VERSION = '3.3.16';

	/** Exclusive upper bound. 'dev' sorts below 'alpha', so 4.0.0-a1 is out. */
	const MAX_PHPBB_VERSION = '4.0.0-dev';

	/**
	 * Minimum supported phpBB version.
	 *
	 * 3.3.16 is the tested floor. Earlier 3.3.x releases are untested and
	 * therefore unsupported, even though the events used are long-standing.
	 * The check is enforced here rather than left to the compatibility claim
	 * in composer.json, so that installing on an untested version fails
	 * cleanly instead of half-working.
	 */
	public function is_enableable()
	{
		$supported = phpbb_version_compare(PHPBB_VERSION, self::MIN_PHPBB_VERSION, '>=')
			&& phpbb_version_compare(PHPBB_VERSION, self::MAX_PHPBB_VERSION, '<');

		if ($supported)
		{
			return true;
		}

		// phpBB renders a string or array of strings as the reason
		// (acp_extensions.php check_is_enableable()); a bare false leaves the
		// administrator with "not enableable" and no idea that the board's
		// version is what is wrong. The language file may not be loaded at
		// this point, so the sentence is built here.
		$language = $this->container->get('language');
		$language->add_lang('common', 'uflagmey/donationcampaigns');

		return $language->lang(
			'DONATIONCAMPAIGNS_UNSUPPORTED_PHPBB',
			self::MIN_PHPBB_VERSION,
			PHPBB_VERSION
		);
	}
}
