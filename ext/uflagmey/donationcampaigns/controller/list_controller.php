<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\controller;

use uflagmey\donationcampaigns\service\campaign_list_service;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\currency_formatter;

/**
 * The board-wide campaign list, app.php/donationcampaigns (ADR-018).
 *
 * READ ONLY. This is the third controller, and it exists although the
 * architecture says "avoid additional controllers or duplicated write
 * paths" because it is not a write path: one GET action, no form, no form
 * key, no write method called. architecture_test fails the build if any of
 * that changes.
 *
 * COORDINATION ONLY. Which campaigns the viewer may see is decided by
 * campaign_list_service; the progress figures by campaign_service::progress();
 * money by currency_formatter. This class pages, formats and assigns.
 *
 * SWITCHED OFF → 404 PAGE_NOT_FOUND. While the ACP switch is off the page
 * does not exist, so the answer is core's "not found", not the extension's
 * "not available" used for permission refusals.
 *
 * NO DONOR DATA. A row carries the campaign title, its forum, the figures and
 * at most a donation COUNT. Names stay in the topic box (C5).
 */
class list_controller
{
	/** Rows per page (C6). */
	const PER_PAGE = 25;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\path_helper */
	protected $path_helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var campaign_list_service */
	protected $list;

	/** @var campaign_service */
	protected $campaigns;

	/** @var currency_formatter */
	protected $formatter;

	/** @var string phpBB's PHP file extension (%core.php_ext%) */
	protected $php_ext;

	public function __construct(
		\phpbb\controller\helper $helper,
		\phpbb\path_helper $path_helper,
		\phpbb\template\template $template,
		\phpbb\language\language $language,
		\phpbb\config\config $config,
		\phpbb\request\request_interface $request,
		\phpbb\pagination $pagination,
		campaign_list_service $list,
		campaign_service $campaigns,
		currency_formatter $formatter,
		$php_ext
	)
	{
		$this->helper = $helper;
		$this->path_helper = $path_helper;
		$this->template = $template;
		$this->language = $language;
		$this->config = $config;
		$this->request = $request;
		$this->pagination = $pagination;
		$this->list = $list;
		$this->campaigns = $campaigns;
		$this->formatter = $formatter;
		$this->php_ext = $php_ext;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \phpbb\exception\http_exception 404 while the list is switched off
	 */
	public function display()
	{
		// empty() also covers a missing key: during an update the code can
		// run before m10 has added it, and then the list is off.
		if (empty($this->config['donationcampaigns_list_enabled']))
		{
			throw new \phpbb\exception\http_exception(404, 'PAGE_NOT_FOUND');
		}

		$this->language->add_lang('common', 'uflagmey/donationcampaigns');

		$total = $this->list->count_public();

		// A tampered ?start= (negative, past the end, not a number) lands on
		// a real page; core's validate_start() does the clamping.
		$start = $this->request->variable('start', 0);
		$start = is_scalar($start) ? (int) $start : 0;
		$start = (int) $this->pagination->validate_start($start, self::PER_PAGE, $total);

		$exponent = (int) $this->config['donationcampaigns_currency_exponent'];

		foreach ($this->list->list_public(self::PER_PAGE, $start) as $row)
		{
			$progress = $this->campaigns->progress($row['collected_amount'], $row['target_amount']);

			$this->template->assign_block_vars('donationcampaigns_list', array(
				// Stored raw by the extension: escaped with |e in the template.
				'TITLE'			=> $row['campaign_title'],
				'U_TOPIC'		=> $this->board_url('viewtopic', 't=' . (int) $row['topic_id']),
				// Stored escaped by core: printed WITHOUT |e (see F1, beta3).
				'FORUM_NAME'	=> $row['forum_name'],
				'U_FORUM'		=> $this->board_url('viewforum', 'f=' . (int) $row['forum_id']),

				'COLLECTED'		=> $this->formatter->format_money($row['collected_amount'], $exponent),
				'TARGET'		=> $this->formatter->format_money($row['target_amount'], $exponent),
				'PERCENT'		=> $progress['percent_capped'],
				'PERCENT_RAW'	=> $progress['percent'],
				'STEP'			=> $progress['step'],
				'S_REACHED'		=> $progress['reached'],

				// Empty unless the campaign shows its count; the service has
				// already withheld the number in that case.
				'COUNT'			=> $row['show_donation_count'] ? $this->language->lang('DONATIONCAMPAIGNS_COUNT', $row['donation_count']) : '',
			));
		}

		$u_list = $this->helper->route('uflagmey_donationcampaigns_list');

		$this->pagination->generate_template_pagination($u_list, 'pagination', 'start', $total, self::PER_PAGE, $start);

		$this->template->assign_block_vars('navlinks', array(
			'BREADCRUMB_NAME'	=> $this->language->lang('DONATIONCAMPAIGNS_PUBLIC_LIST_TITLE'),
			'U_BREADCRUMB'		=> $u_list,
		));

		$this->template->assign_var('DONATIONCAMPAIGNS_PUBLIC_LIST_TOTAL', $this->language->lang('DONATIONCAMPAIGNS_PUBLIC_LIST_TOTAL', $total));

		return $this->helper->render('donationcampaigns_list.html', $this->language->lang('DONATIONCAMPAIGNS_PUBLIC_LIST_TITLE'));
	}

	/**
	 * A board page URL from the web root, not relative to app.php/…, where a
	 * bare "viewtopic.php" would resolve against the route path and 404.
	 *
	 * @param string $page   viewtopic or viewforum
	 * @param string $params Built from integers only
	 * @return string
	 */
	protected function board_url($page, $params)
	{
		return append_sid($this->path_helper->get_web_root_path() . $page . '.' . $this->php_ext, $params);
	}
}
