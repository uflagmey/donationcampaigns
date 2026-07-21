<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\event\user_setup_listener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use phpbb\event\data as phpbb_event_data;

/**
 * Loads the extension's audit-log language globally.
 *
 * The log viewer translates an entry by reading $user->lang[$log_operation], so
 * the LOG_ keys must be present in $user->lang wherever a viewer runs — the ACP
 * admin log and the MCP moderator log alike. core.user_setup's lang_set_ext is
 * the core-documented hook for that (phpbb\user), and it is the single point
 * covering both viewers.
 */
class user_setup_listener_test extends \phpbb_test_case
{
	public function test_it_subscribes_to_core_user_setup()
	{
		$this->assertArrayHasKey('core.user_setup', user_setup_listener::getSubscribedEvents());
	}

	public function test_it_is_a_valid_event_subscriber()
	{
		$this->assertInstanceOf(
			'Symfony\Component\EventDispatcher\EventSubscriberInterface',
			new user_setup_listener()
		);
	}

	/**
	 * @param array $existing
	 * @return array
	 */
	protected function dispatch(array $existing = array())
	{
		$dispatcher = new EventDispatcher();
		$dispatcher->addSubscriber(new user_setup_listener());

		$event = new phpbb_event_data(array('lang_set_ext' => $existing));
		$dispatcher->dispatch('core.user_setup', $event);

		return $event['lang_set_ext'];
	}

	public function test_it_appends_the_logs_language_set()
	{
		$this->assertContains(
			array('ext_name' => 'uflagmey/donationcampaigns', 'lang_set' => 'logs'),
			$this->dispatch()
		);
	}

	/**
	 * It must not discard language sets other extensions have already queued.
	 */
	public function test_it_preserves_existing_language_sets()
	{
		$result = $this->dispatch(array(
			array('ext_name' => 'other/ext', 'lang_set' => 'common'),
		));

		$this->assertContains(array('ext_name' => 'other/ext', 'lang_set' => 'common'), $result);
		$this->assertContains(array('ext_name' => 'uflagmey/donationcampaigns', 'lang_set' => 'logs'), $result);
		$this->assertCount(2, $result);
	}

	/**
	 * Only the small logs file is loaded on every request — not the whole common
	 * file — to keep the per-request cost minimal.
	 */
	public function test_it_loads_only_the_logs_language_file()
	{
		foreach ($this->dispatch() as $entry)
		{
			if ($entry['ext_name'] === 'uflagmey/donationcampaigns')
			{
				$this->assertSame('logs', $entry['lang_set'], 'The listener loads more than the logs file');
			}
		}
	}
}
