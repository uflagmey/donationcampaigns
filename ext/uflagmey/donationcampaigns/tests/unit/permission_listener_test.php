<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

use uflagmey\donationcampaigns\event\permission_listener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use phpbb\event\data as phpbb_event_data;

/**
 * The listener that makes the permissions visible in the ACP permissions UI.
 *
 * Without it the permissions exist in the database and function correctly but
 * never appear in the interface, so an administrator cannot grant them. For
 * RC2 the listener declares three permissions — the global admin one and the
 * two forum-scoped moderator ones — all filed under a dedicated "Donation
 * Campaigns" category it also registers.
 */
class permission_listener_test extends \phpbb_test_case
{
	const EXPECTED = array(
		'a_donationcampaigns',
		'm_donationcampaigns_manage',
		'm_donationcampaigns_donations',
	);

	public function test_it_subscribes_to_core_permissions()
	{
		$this->assertArrayHasKey('core.permissions', permission_listener::getSubscribedEvents());
	}

	public function test_it_is_a_valid_event_subscriber()
	{
		$this->assertInstanceOf(
			'Symfony\Component\EventDispatcher\EventSubscriberInterface',
			new permission_listener()
		);
	}

	/**
	 * Dispatched through a real Symfony dispatcher with a real phpBB event
	 * carrying both permissions and categories, so the test exercises the same
	 * path core uses. Returns the event so a caller can read either array.
	 *
	 * @param array $permissions
	 * @param array $categories
	 * @return phpbb_event_data
	 */
	protected function dispatch(array $permissions = array(), array $categories = array())
	{
		$dispatcher = new EventDispatcher();
		$dispatcher->addSubscriber(new permission_listener());

		$event = new phpbb_event_data(array(
			'permissions'	=> $permissions,
			'categories'	=> $categories,
		));
		// phpBB 3.3 ships Symfony 3.4, whose dispatcher takes (name, event).
		$dispatcher->dispatch('core.permissions', $event);

		return $event;
	}

	// ------------------------------------------------------------- permissions

	public function test_it_registers_the_admin_permission()
	{
		$this->assertArrayHasKey('a_donationcampaigns', $this->dispatch()['permissions']);
	}

	public function test_it_registers_both_moderator_permissions()
	{
		$permissions = $this->dispatch()['permissions'];

		$this->assertArrayHasKey('m_donationcampaigns_manage', $permissions);
		$this->assertArrayHasKey('m_donationcampaigns_donations', $permissions);
	}

	/**
	 * Every label must be a language key, not a display string.
	 */
	public function test_the_labels_are_language_keys()
	{
		$permissions = $this->dispatch()['permissions'];

		$this->assertSame('ACL_A_DONATIONCAMPAIGNS', $permissions['a_donationcampaigns']['lang']);
		$this->assertSame('ACL_M_DONATIONCAMPAIGNS_MANAGE', $permissions['m_donationcampaigns_manage']['lang']);
		$this->assertSame('ACL_M_DONATIONCAMPAIGNS_DONATIONS', $permissions['m_donationcampaigns_donations']['lang']);
	}

	// -------------------------------------------------------------- category

	public function test_it_registers_the_dedicated_category()
	{
		$categories = $this->dispatch()['categories'];

		$this->assertArrayHasKey('donationcampaigns', $categories);
		$this->assertSame('ACL_CAT_DONATIONCAMPAIGNS', $categories['donationcampaigns']);
	}

	/**
	 * All three permissions are filed under the dedicated category, so they
	 * appear together as "Donation Campaigns" rather than buried under Misc.
	 */
	public function test_all_three_permissions_use_the_dedicated_category()
	{
		$permissions = $this->dispatch()['permissions'];

		foreach (self::EXPECTED as $option)
		{
			$this->assertSame('donationcampaigns', $permissions[$option]['cat'], "{$option} is not in the dedicated category");
		}
	}

	// -------------------------------------------------------- non-destructive

	public function test_it_preserves_permissions_registered_by_others()
	{
		$permissions = $this->dispatch(array(
			'a_other_extension'	=> array('lang' => 'ACL_A_OTHER', 'cat' => 'misc'),
			'u_something'		=> array('lang' => 'ACL_U_SOMETHING', 'cat' => 'post'),
		))['permissions'];

		$this->assertArrayHasKey('a_other_extension', $permissions);
		$this->assertArrayHasKey('u_something', $permissions);
		// 2 pre-existing + our 3.
		$this->assertCount(5, $permissions);
	}

	public function test_it_preserves_categories_registered_by_others()
	{
		$categories = $this->dispatch(array(), array(
			'misc'	=> 'ACL_CAT_MISC',
			'post'	=> 'ACL_CAT_POST',
		))['categories'];

		$this->assertArrayHasKey('misc', $categories);
		$this->assertArrayHasKey('post', $categories);
		$this->assertArrayHasKey('donationcampaigns', $categories);
		$this->assertCount(3, $categories);
	}

	/**
	 * Exactly the three approved permissions. Anything else is scope creep, and
	 * the moderator permissions must never leak into another extension's set.
	 */
	public function test_it_registers_exactly_the_three_expected_permissions()
	{
		$before = array('a_existing' => array('lang' => 'ACL_A_EXISTING', 'cat' => 'misc'));
		$after = $this->dispatch($before)['permissions'];

		$added = array_keys(array_diff_key($after, $before));
		sort($added);

		$expected = self::EXPECTED;
		sort($expected);

		$this->assertSame($expected, $added);
	}

	/**
	 * The listener coordinates only. Persistence belongs to the migration.
	 */
	public function test_the_listener_contains_no_persistence_logic()
	{
		$source = file_get_contents(__DIR__ . '/../../event/permission_listener.php');

		foreach (array('sql_query', 'INSERT INTO', 'UPDATE ', 'DELETE FROM', 'SELECT ', '$db', 'dbal') as $needle)
		{
			$this->assertStringNotContainsString(
				$needle,
				$source,
				"The permission listener contains persistence logic ('{$needle}'). Listeners coordinate; repositories persist."
			);
		}
	}
}
