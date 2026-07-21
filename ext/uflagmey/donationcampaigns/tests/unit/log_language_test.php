<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

/**
 * The audit-log language file.
 *
 * Every LOG_ key the code writes must be defined here, in both languages, and
 * nowhere else — so a moderator- or admin-log entry is never rendered as a raw
 * key, and the keys are not duplicated in info_acp where they would only resolve
 * in the ACP.
 */
class log_language_test extends \phpbb_test_case
{
	const REQUIRED = array(
		'LOG_DONATIONCAMPAIGNS_SETTINGS_UPDATED',
		'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED',
		'LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED',
		'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ENABLED',
		'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DISABLED',
		'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED',
		'LOG_DONATIONCAMPAIGNS_TOTAL_RECALCULATED',
		'LOG_DONATIONCAMPAIGNS_DONATION_ADDED',
		'LOG_DONATIONCAMPAIGNS_DONATION_EDITED',
		'LOG_DONATIONCAMPAIGNS_DONATION_DELETED',
	);

	/**
	 * @param string $iso
	 * @param string $file
	 * @return array
	 */
	protected function lang($iso, $file)
	{
		if (!defined('IN_PHPBB'))
		{
			define('IN_PHPBB', true);
		}

		$lang = array();
		include dirname(dirname(__DIR__)) . '/language/' . $iso . '/' . $file . '.php';

		return $lang;
	}

	public function iso_provider()
	{
		return array('en' => array('en'), 'de' => array('de'));
	}

	/**
	 * @dataProvider iso_provider
	 */
	public function test_logs_defines_every_required_key($iso)
	{
		$lang = $this->lang($iso, 'logs');

		foreach (self::REQUIRED as $key)
		{
			$this->assertArrayHasKey($key, $lang, "{$iso}/logs.php is missing {$key}");
			$this->assertNotSame('', trim((string) $lang[$key]));
		}
	}

	/**
	 * The two languages must define the same set of log keys.
	 */
	public function test_the_two_languages_define_the_same_log_keys()
	{
		$en = array_keys($this->lang('en', 'logs'));
		$de = array_keys($this->lang('de', 'logs'));
		sort($en);
		sort($de);

		$this->assertSame($en, $de, 'The English and German log files define different keys');
	}

	/**
	 * The keys live in logs.php only. Leaving them in info_acp would resolve them
	 * in the ACP log viewer but not the MCP one, and duplicate the source.
	 *
	 * @dataProvider iso_provider
	 */
	public function test_the_log_keys_are_not_duplicated_in_info_acp($iso)
	{
		$info = $this->lang($iso, 'info_acp_donationcampaigns');

		foreach (self::REQUIRED as $key)
		{
			$this->assertArrayNotHasKey($key, $info, "{$iso}: {$key} must live in logs.php, not info_acp");
		}
	}
}
