<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use uflagmey\donationcampaigns\service\access;
use uflagmey\donationcampaigns\service\campaign_form;
use uflagmey\donationcampaigns\service\campaign_service;

/**
 * Create a campaign together with a new topic, from the posting form (ADR-019).
 *
 * COORDINATION ONLY. The fields are read by campaign_form, checked by
 * campaign_service::validate_fields(), and the campaign is created by
 * campaign_service::create_campaign() — the same method, rules and log entry
 * as the frontend form. This is not a second write path; it is a second
 * place from which the one write path is reached.
 *
 * WHEN IT APPLIES — checked in EVERY event, never inferred from the panel
 * having been shown, because the fields can be posted by hand:
 *   - mode === 'post' (a new topic; D1 — editing stays with the landing);
 *   - access::can_manage() in the posting forum (D2);
 *   - and, for validation and creation, the "attach" checkbox is ticked (D3).
 *
 * Every field is posted with the prefix "donationcampaigns_": the posting
 * form is the most shared field namespace among extensions (WD4).
 */
class posting_listener implements EventSubscriberInterface
{
	/** Field-name prefix in the posting form. */
	const PREFIX = 'donationcampaigns_';

	/** @var access */
	protected $access;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var campaign_service */
	protected $campaigns;

	/** @var campaign_form */
	protected $form;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\controller\helper */
	protected $helper;

	public function __construct(
		access $access,
		\phpbb\auth\auth $auth,
		campaign_service $campaigns,
		campaign_form $form,
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\language\language $language,
		\phpbb\request\request_interface $request,
		\phpbb\log\log_interface $log,
		$user,
		\phpbb\controller\helper $helper
	)
	{
		$this->access = $access;
		$this->auth = $auth;
		$this->campaigns = $campaigns;
		$this->form = $form;
		$this->config = $config;
		$this->template = $template;
		$this->language = $language;
		$this->request = $request;
		$this->log = $log;
		$this->user = $user;
		$this->helper = $helper;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.posting_modify_template_vars'		=> 'assign_panel',
		);
	}

	/**
	 * Show the panel (core.posting_modify_template_vars, posting.php:2089).
	 *
	 * First display: the new-campaign defaults, unticked. Any redisplay —
	 * preview, an error, an attachment refresh — shows what was entered; the
	 * hidden "donationcampaigns_panel" field tells the two apart. The title is
	 * NOT prefilled from the subject (WD1): an empty title becomes the subject
	 * only when the topic is submitted.
	 *
	 * @param \phpbb\event\data $event
	 * @return void
	 */
	public function assign_panel($event)
	{
		if (!$this->applies($event['mode'], $event['forum_id']))
		{
			return;
		}

		// common: panel strings; info_acp: the field labels the frontend form
		// shares, reused rather than duplicated.
		$this->language->add_lang(array('common', 'info_acp_donationcampaigns'), 'uflagmey/donationcampaigns');

		$redisplay = $this->request->is_set_post(self::PREFIX . 'panel');
		$values = $redisplay ? $this->form->submitted_values(self::PREFIX) : $this->form->defaults();

		$this->template->assign_vars(array(
			'S_DONATIONCAMPAIGNS_POSTING'			=> true,
			'S_DONATIONCAMPAIGNS_ATTACH'			=> $redisplay && $this->attached(),
			// The shared field include shows the "leave empty" hint instead
			// of the frontend's "required" explanation.
			'S_DONATIONCAMPAIGNS_TITLE_FROM_SUBJECT'	=> true,

			'S_DONATIONCAMPAIGNS_SHOW_DONORS'		=> (bool) $values['show_donor_names'],
			'S_DONATIONCAMPAIGNS_SHOW_COUNT'		=> (bool) $values['show_donation_count'],
			'S_DONATIONCAMPAIGNS_SHOW_DATE'			=> (bool) $values['show_donation_date'],

			// Raw; escaped in the template. The description is the exception,
			// exactly as in the frontend form: read through variable(), it is
			// already escaped once and goes into the textarea as is.
			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'		=> $values['campaign_title'],
			'DONATIONCAMPAIGNS_DESC'				=> $values['campaign_desc'],
			'DONATIONCAMPAIGNS_TARGET_AMOUNT'		=> $values['target_amount'],
			'DONATIONCAMPAIGNS_CURRENCY_SYMBOL'		=> (string) $this->config['donationcampaigns_currency_symbol'],
			'S_DONATIONCAMPAIGNS_SYMBOL_BEFORE'		=> !empty($this->config['donationcampaigns_currency_symbol_before']),
			'DONATIONCAMPAIGNS_EXTERNAL_URL'		=> $values['external_url'],
			'DONATIONCAMPAIGNS_LINK_TEXT'			=> $values['external_link_text'],
		));
	}

	/**
	 * A new topic, in a forum where the viewer may manage campaigns.
	 *
	 * @param string $mode
	 * @param int $forum_id
	 * @return bool
	 */
	protected function applies($mode, $forum_id)
	{
		return $mode === 'post' && (int) $forum_id > 0 && $this->access->can_manage((int) $forum_id);
	}

	/**
	 * @return bool The "attach a donation campaign" checkbox is ticked
	 */
	protected function attached()
	{
		return (int) $this->request->variable(self::PREFIX . 'attach', 0) === 1;
	}
}
