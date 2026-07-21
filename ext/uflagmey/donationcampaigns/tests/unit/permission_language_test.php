<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

/**
 * The permission labels shown in the ACP permissions UI.
 *
 * Every permission and the dedicated category must have a translated label in
 * both shipped languages, and — because granting the donation permission
 * exposes donor privacy — its description must say so, in both languages, so a
 * board owner grants it knowingly.
 */
class permission_language_test extends \phpbb_test_case
{
	const REQUIRED = array(
		'ACL_CAT_DONATIONCAMPAIGNS',
		'ACL_A_DONATIONCAMPAIGNS',
		'ACL_M_DONATIONCAMPAIGNS_MANAGE',
		'ACL_M_DONATIONCAMPAIGNS_DONATIONS',
	);

	/**
	 * @param string $iso
	 * @return array
	 */
	protected function lang($iso)
	{
		if (!defined('IN_PHPBB'))
		{
			define('IN_PHPBB', true);
		}

		$lang = array();
		include dirname(dirname(__DIR__)) . '/language/' . $iso . '/permissions_donationcampaigns.php';

		return $lang;
	}

	public function iso_provider()
	{
		return array('en' => array('en'), 'de' => array('de'));
	}

	/**
	 * @dataProvider iso_provider
	 */
	public function test_all_required_permission_keys_are_defined($iso)
	{
		$lang = $this->lang($iso);

		foreach (self::REQUIRED as $key)
		{
			$this->assertArrayHasKey($key, $lang, "{$iso}: missing {$key}");
			$this->assertNotSame('', trim((string) $lang[$key]), "{$iso}: {$key} is empty");
		}
	}

	/**
	 * @dataProvider iso_provider
	 */
	public function test_the_category_is_named_for_donation_campaigns($iso)
	{
		$expected = ($iso === 'en') ? 'Donation Campaigns' : 'Spendenkampagnen';

		$this->assertSame($expected, $this->lang($iso)['ACL_CAT_DONATIONCAMPAIGNS']);
	}

	/**
	 * The donation permission must warn, in both languages, that its holder can
	 * see donor names, private donor identities and confirmed amounts.
	 */
	public function test_the_donation_permission_warns_about_donor_privacy()
	{
		$terms = array(
			'en' => array('donor', 'private', 'amount'),
			'de' => array('spender', 'privat', 'betrag'),
		);

		foreach ($terms as $iso => $required)
		{
			$description = strtolower($this->lang($iso)['ACL_M_DONATIONCAMPAIGNS_DONATIONS']);

			foreach ($required as $term)
			{
				$this->assertStringContainsString(
					$term,
					$description,
					"{$iso}: the donations permission description must mention '{$term}'"
				);
			}
		}
	}
}
