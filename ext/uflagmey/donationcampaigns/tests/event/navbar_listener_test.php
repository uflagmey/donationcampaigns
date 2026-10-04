<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\event\navbar_listener;
use uflagmey\donationcampaigns\tests\controller\recording_helper;

/**
 * The quick-links entry for the board-wide list (C4): present exactly when
 * the ACP switch is on, and free when it is off — this runs on every page.
 */
class navbar_listener_test extends \phpbb_test_case
{
	/** @var recording_template */
	protected $template;

	/** @var \phpbb\language\language|\PHPUnit\Framework\MockObject\MockObject */
	protected $language;

	/** @var recording_helper */
	protected $helper;

	/** @var string */
	protected $package;

	public function setUp(): void
	{
		parent::setUp();

		$this->package = dirname(dirname(__DIR__));
		$this->template = new recording_template();
		$this->language = $this->createMock('\phpbb\language\language');
		$this->helper = new recording_helper();
	}

	protected function dispatch(array $config)
	{
		$listener = new navbar_listener(new \phpbb\config\config($config), $this->template, $this->language, $this->helper);
		$listener->assign_list_link(new \phpbb\event\data(array()));
	}

	public function test_it_subscribes_to_core_page_header()
	{
		$this->assertSame(array('core.page_header' => 'assign_list_link'), navbar_listener::getSubscribedEvents());
	}

	public function test_switched_off_it_assigns_nothing_and_loads_no_language()
	{
		$this->language->expects($this->never())->method('add_lang');

		$this->dispatch(array('donationcampaigns_list_enabled' => 0));

		$this->assertSame(array(), $this->template->vars);
		$this->assertSame(array(), $this->helper->routed);
	}

	/**
	 * During an update the code can be live before m10 has added the key.
	 */
	public function test_a_missing_switch_counts_as_off()
	{
		$this->language->expects($this->never())->method('add_lang');

		$this->dispatch(array());

		$this->assertSame(array(), $this->template->vars);
	}

	public function test_switched_on_it_links_the_list_route()
	{
		$this->language->expects($this->once())->method('add_lang')->with('common', 'uflagmey/donationcampaigns');

		$this->dispatch(array('donationcampaigns_list_enabled' => 1));

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_LIST_LINK']);
		$this->assertSame('uflagmey_donationcampaigns_list', $this->template->vars['U_DONATIONCAMPAIGNS_LIST']);
		$this->assertSame(array(array('uflagmey_donationcampaigns_list', array())), $this->helper->routed);
	}

	// ---------------------------------------------------------------- template

	public function test_the_entry_is_guarded_and_uses_core_quick_links_markup()
	{
		$file = $this->package . '/styles/prosilver/template/event/navbar_header_quick_links_after.html';
		$this->assertFileExists($file);

		$template = trim(file_get_contents($file));

		$this->assertStringStartsWith('<!-- IF S_DONATIONCAMPAIGNS_LIST_LINK -->', $template);
		$this->assertStringEndsWith('<!-- ENDIF -->', $template);
		$this->assertStringContainsString('href="{U_DONATIONCAMPAIGNS_LIST}" role="menuitem"', $template);
		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_PUBLIC_LIST_TITLE}', $template);
	}

	/**
	 * A template event file whose name matches no core event is silently
	 * never rendered. Checked for every shipped event template.
	 */
	public function test_every_event_template_names_a_real_core_event()
	{
		global $phpbb_root_path;

		$core = '';
		foreach (glob($phpbb_root_path . 'styles/prosilver/template/*.html') as $file)
		{
			$core .= file_get_contents($file);
		}

		foreach (glob($this->package . '/styles/prosilver/template/event/*.html') as $file)
		{
			$event = basename($file, '.html');

			$this->assertStringContainsString('<!-- EVENT ' . $event . ' -->', $core, "{$event} is not an event in core's prosilver templates");
		}
	}
}
