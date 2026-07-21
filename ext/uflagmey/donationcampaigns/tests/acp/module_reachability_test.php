<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * Registration and reachability are different facts, and the gap between them
 * is where a real bug lived.
 *
 * The migration tests asserted that all three modes were registered, enabled,
 * and resolved to a loadable class. Every donations test supplied a
 * campaign_id before dispatching. So nothing exercised the ONE entry point the
 * ACP menu actually produced — mode=donations with no campaign_id — which
 * failed immediately with "That campaign no longer exists."
 *
 * These tests close that gap by dispatching each mode the way its own entry
 * point does, and nothing else.
 */
class module_reachability_test extends campaign_acp_test_case
{
	/**
	 * Modes deliberately absent from the ACP menu, with the parameter each one
	 * needs. A hidden mode is reached from a link elsewhere in the ACP, so it
	 * is exempt from the menu-reachability rule but must still dispatch.
	 */
	const HIDDEN_MODES = array(
		'donations' => 'campaign_id',
	);

	/**
	 * The shared ACP fixture registers what the campaign and donation modes
	 * need. The settings mode needs one more service, and this test is the
	 * only one that dispatches all three, so it is registered here rather
	 * than widening the fixture for everyone.
	 */
	public function setUp(): void
	{
		parent::setUp();

		global $phpbb_container;

		$phpbb_container->set(
			'uflagmey.donationcampaigns.settings_service',
			new \uflagmey\donationcampaigns\service\settings_service(
				$GLOBALS['config'],
				$this->campaigns,
				$this->donations
			)
		);
	}

	/**
	 * @return string[] every mode the extension registers
	 */
	protected function registered_modes()
	{
		$info = new \uflagmey\donationcampaigns\acp\main_info();
		$module = $info->module();

		return array_keys($module['modes']);
	}

	/**
	 * Dispatch a mode with a given request and report any error raised.
	 *
	 * @param string $mode
	 * @param array $values
	 * @return string
	 */
	protected function dispatch($mode, array $values = array())
	{
		$this->module->u_action = 'index.php?i=-uflagmey-donationcampaigns-acp-main_module&mode=' . $mode;
		$this->request($values);

		try
		{
			$this->module->main(1, $mode);
		}
		catch (\Throwable $e)
		{
			return $e->getMessage();
		}

		return '';
	}

	public function test_the_extension_registers_the_three_expected_modes()
	{
		$modes = $this->registered_modes();
		sort($modes);

		$this->assertSame(array('campaigns', 'donations', 'settings'), $modes);
	}

	/**
	 * THE REGRESSION TEST.
	 *
	 * A mode that appears in the ACP menu must survive being clicked, with
	 * only the parameters that menu link carries — which is to say none.
	 */
	public function test_every_visible_mode_is_reachable_from_its_own_menu_url()
	{
		$visible = array_diff($this->registered_modes(), array_keys(self::HIDDEN_MODES));

		$this->assertNotEmpty($visible, 'No visible modes to check');

		foreach ($visible as $mode)
		{
			$this->setUp();

			$this->assertSame(
				'',
				$this->dispatch($mode),
				"Visible mode '{$mode}' raised an error when reached from its own menu entry, "
					. 'which is exactly what an administrator clicking it would see'
			);
		}
	}

	/**
	 * The counterpart: a hidden mode is NOT expected to work bare, but must
	 * work through the link that exists for it. Hiding the module rather than
	 * deleting its row is what keeps this true — set_active() matches on an
	 * explicit id and mode without consulting module_display, whereas a
	 * deleted row would raise MODULE_NOT_ACCESS.
	 */
	public function test_every_hidden_mode_dispatches_through_its_explicit_link()
	{
		foreach (self::HIDDEN_MODES as $mode => $parameter)
		{
			$this->setUp();

			$this->assertSame(
				'',
				$this->dispatch($mode, array($parameter => 1)),
				"Hidden mode '{$mode}' could not be dispatched with a valid {$parameter}"
			);
		}
	}

	/**
	 * And a hidden mode reached without its parameter must say what is
	 * actually wrong, rather than claiming the campaign was deleted.
	 */
	public function test_a_hidden_mode_reached_bare_explains_the_missing_context()
	{
		$message = $this->dispatch('donations');

		$this->assertStringContainsString('No campaign selected', $message);
		$this->assertStringNotContainsString('no longer exists', $message);
	}
}
