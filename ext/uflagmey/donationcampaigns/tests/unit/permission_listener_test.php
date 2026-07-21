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
 * The listener that makes the permission visible in the ACP permissions UI.
 *
 * Without it the permission exists in the database and functions correctly but
 * never appears in the interface, so an administrator cannot grant it — an
 * extension that looks broken while every other check passes.
 */
class permission_listener_test extends \phpbb_test_case
{
	public function test_it_subscribes_to_core_permissions()
	{
		$this->assertArrayHasKey(
			'core.permissions',
			permission_listener::getSubscribedEvents()
		);
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
	 * object, so the test exercises the same path core uses rather than
	 * calling the handler directly.
	 */
	protected function dispatch(array $existing = array())
	{
		$dispatcher = new EventDispatcher();
		$dispatcher->addSubscriber(new permission_listener());

		$event = new phpbb_event_data(array('permissions' => $existing));
		// phpBB 3.3 ships Symfony 3.4, whose dispatcher takes (name, event).
		$dispatcher->dispatch('core.permissions', $event);

		return $event['permissions'];
	}

	public function test_it_registers_the_permission()
	{
		$permissions = $this->dispatch();

		$this->assertArrayHasKey('a_donationcampaigns', $permissions);
	}

	/**
	 * The label must be a language key, not a display string, so every visible
	 * permission label comes from a language file.
	 */
	public function test_the_label_is_a_language_key()
	{
		$permissions = $this->dispatch();

		$this->assertSame(
			'ACL_A_DONATIONCAMPAIGNS',
			$permissions['a_donationcampaigns']['lang']
		);
	}

	/**
	 * The permission is an administrative one and belongs in a category the
	 * ACP permissions UI actually renders.
	 */
	public function test_it_is_filed_under_a_real_permission_category()
	{
		$permissions = $this->dispatch();

		$this->assertSame('misc', $permissions['a_donationcampaigns']['cat']);
	}

	/**
	 * The handler must not discard permissions other extensions have already
	 * registered. Reassigning a fresh array instead of merging would silently
	 * remove them.
	 */
	public function test_it_preserves_permissions_registered_by_others()
	{
		$permissions = $this->dispatch(array(
			'a_other_extension'	=> array('lang' => 'ACL_A_OTHER', 'cat' => 'misc'),
			'u_something'		=> array('lang' => 'ACL_U_SOMETHING', 'cat' => 'post'),
		));

		$this->assertArrayHasKey('a_other_extension', $permissions);
		$this->assertArrayHasKey('u_something', $permissions);
		$this->assertArrayHasKey('a_donationcampaigns', $permissions);
		$this->assertCount(3, $permissions);
	}

	/**
	 * Exactly one permission. The approved architecture specifies a single
	 * administrative permission (ADR-008); anything else is scope creep.
	 */
	public function test_it_registers_exactly_one_permission()
	{
		$before = array('a_existing' => array('lang' => 'ACL_A_EXISTING', 'cat' => 'misc'));
		$after = $this->dispatch($before);

		$added = array_diff_key($after, $before);

		$this->assertCount(1, $added);
		$this->assertSame(array('a_donationcampaigns'), array_keys($added));
	}

	/**
	 * The listener coordinates only. Persistence belongs to the migration.
	 */
	public function test_the_listener_contains_no_persistence_logic()
	{
		$source = file_get_contents(
			__DIR__ . '/../../event/permission_listener.php'
		);

		foreach (array('sql_query', 'INSERT INTO', 'UPDATE ', 'DELETE FROM', 'SELECT ', '$db', 'dbal') as $needle)
		{
			$this->assertStringNotContainsString(
				$needle,
				$source,
				"The permission listener contains persistence logic ('{$needle}'). "
				. 'Listeners coordinate; repositories persist.'
			);
		}
	}
}
