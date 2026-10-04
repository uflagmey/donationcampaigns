<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The "Donation campaigns" entry in prosilver's quick links (C4, ADR-018).
 *
 * COORDINATION ONLY: one config check, one route, two template variables.
 *
 *   PHP      core.page_header — fires for every frontend page, before the
 *            header is rendered.
 *   Template navbar_header_quick_links_after — the end of the quick-links
 *            menu in navbar_header.html.
 *
 * This runs on EVERY page, so with the switch off (the default) it does
 * nothing at all: no language file, no route generation. Whether the viewer
 * can see any campaign is deliberately not checked here — that would cost a
 * query on every page; the list itself says when it is empty.
 */
class navbar_listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\controller\helper */
	protected $helper;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\language\language $language,
		\phpbb\controller\helper $helper
	)
	{
		$this->config = $config;
		$this->template = $template;
		$this->language = $language;
		$this->helper = $helper;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.page_header'	=> 'assign_list_link',
		);
	}

	/**
	 * @param \phpbb\event\data $event
	 * @return void
	 */
	public function assign_list_link($event)
	{
		// empty() also covers a missing key: before m10 has run, the list is off.
		if (empty($this->config['donationcampaigns_list_enabled']))
		{
			return;
		}

		$this->language->add_lang('common', 'uflagmey/donationcampaigns');

		$this->template->assign_vars(array(
			'S_DONATIONCAMPAIGNS_LIST_LINK'	=> true,
			'U_DONATIONCAMPAIGNS_LIST'		=> $this->helper->route('uflagmey_donationcampaigns_list'),
		));
	}
}
