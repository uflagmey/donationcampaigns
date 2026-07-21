<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\currency_formatter;
use uflagmey\donationcampaigns\acp\main_module as acp_main_module;

/**
 * Renders the campaign box above the first post of a topic.
 *
 * COORDINATION ONLY. This listener contains no SQL and no business rules: it
 * asks campaign_service whether the topic has a publicly visible campaign,
 * formats what it gets for display, and assigns template variables. Whether a
 * campaign is visible, what a donation counts for, and who may be named are
 * all decided in the service layer.
 *
 * Two integration points, both verified against core source rather than
 * inferred from their names:
 *
 *   PHP      core.viewtopic_assign_template_vars_before — viewtopic.php:775.
 *            topic_id is in scope, core's forum-access checks have already
 *            passed, and it is early enough to assign template variables.
 *
 *   Template viewtopic_body_poll_before — viewtopic_body.html:77. Despite the
 *            name it sits OUTSIDE the S_HAS_POLL conditional, which opens two
 *            lines later, so the box renders on every topic and not only on
 *            ones with a poll. See ADR-006.
 *
 * ESCAPING. Values are assigned RAW and escaped in the template with Twig's
 * |e filter — the same idiom core uses for user-controlled text (see
 * prosilver's attachment.html). Escaping here instead would put presentation
 * concerns in PHP and, because phpBB runs Twig with autoescape disabled, would
 * be invisible to anyone reading the template.
 *
 * The description is the exception in the other direction: it must NOT carry
 * |e, because it has already been through phpBB's text-formatting path, which
 * is what decides the markup it may contain.
 */
class viewtopic_listener implements EventSubscriberInterface
{
	/**
	 * Granularity of the progress bar, in percent.
	 *
	 * The width comes from a stylesheet class rather than an inline style
	 * (ADR-013 forbids inline CSS), so there is one class per step and the
	 * emitted value must always land on one of them.
	 */
	const PERCENT_STEP = 5;

	/** @var campaign_service */
	protected $campaign_service;

	/** @var currency_formatter */
	protected $formatter;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $admin_path;

	/** @var string */
	protected $php_ext;

	public function __construct(
		campaign_service $campaign_service,
		currency_formatter $formatter,
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\language\language $language,
		\phpbb\auth\auth $auth,
		$user,
		$admin_path,
		$php_ext
	)
	{
		$this->campaign_service = $campaign_service;
		$this->formatter = $formatter;
		$this->config = $config;
		$this->template = $template;
		$this->language = $language;
		$this->auth = $auth;
		$this->user = $user;
		$this->admin_path = $admin_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_assign_template_vars_before'	=> 'assign_campaign_vars',
		);
	}

	/**
	 * @param \phpbb\event\data $event
	 * @return void
	 */
	public function assign_campaign_vars($event)
	{
		$topic_id = (int) $event['topic_id'];

		// FIRST, and deliberately before the early return below: the
		// administrator's entry point must appear on topics that have NO
		// campaign, because that is the only way one is ever created.
		$this->assign_topic_tools_link($topic_id);

		$campaign = $this->campaign_service->get_campaign_for_topic($topic_id);

		if ($campaign === null)
		{
			// No campaign, or it is disabled. Assign nothing at all: this runs
			// on every topic view on the board, and the template block is
			// guarded by S_DONATIONCAMPAIGNS_SHOW.
			return;
		}

		$this->language->add_lang('common', 'uflagmey/donationcampaigns');

		$exponent = (int) $this->config['donationcampaigns_currency_exponent'];
		$symbol = (string) $this->config['donationcampaigns_currency_symbol'];

		$target = $campaign['target_amount'];
		$collected = $campaign['collected_amount'];
		$percent = $this->percentage($collected, $target);

		$this->template->assign_vars(array(
			'S_DONATIONCAMPAIGNS_SHOW'			=> true,
			'S_DONATIONCAMPAIGNS_SHOW_DONORS'	=> $campaign['show_donor_names'],
			'S_DONATIONCAMPAIGNS_SHOW_COUNT'	=> $campaign['show_donation_count'],
			'S_DONATIONCAMPAIGNS_REACHED'		=> ($collected >= $target),

			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'	=> $campaign['campaign_title'],
			'DONATIONCAMPAIGNS_DESC'			=> $this->render_description($campaign),

			// Display strings. The stored values stay integer minor units.
			'DONATIONCAMPAIGNS_TARGET'			=> $this->money($target, $exponent, $symbol),
			'DONATIONCAMPAIGNS_COLLECTED'		=> $this->money($collected, $exponent, $symbol),

			// The real figure, which may exceed 100. It is what the page shows
			// and what aria-valuetext announces, so sighted and screen-reader
			// users hear the same number.
			'DONATIONCAMPAIGNS_PERCENT_RAW'		=> $percent,
			// ...and the clamped one, for aria-valuenow. ARIA requires
			// valuenow to sit within valuemin/valuemax, so an over-target
			// campaign cannot report 250 against a max of 100.
			'DONATIONCAMPAIGNS_PERCENT'			=> min(100, $percent),
			'DONATIONCAMPAIGNS_PERCENT_STEP'	=> $this->bar_step($percent),

			'DONATIONCAMPAIGNS_URL'				=> $this->safe_url($campaign['external_url']),
			// The button's label. Plain text the administrator chose, and
			// nothing more: no markup pipeline, no provider meaning. The
			// service refuses an empty label whenever a URL is set, so the
			// button never renders without one.
			'DONATIONCAMPAIGNS_LINK_TEXT'		=> $campaign['external_link_text'],
		));

		if ($campaign['show_donation_count'])
		{
			$this->template->assign_var(
				'DONATIONCAMPAIGNS_COUNT',
				$this->language->lang(
					'DONATIONCAMPAIGNS_COUNT',
					$this->campaign_service->count_donations($campaign['campaign_id'])
				)
			);
		}

		if ($campaign['show_donor_names'])
		{
			$this->assign_donor_list($campaign['campaign_id'], $exponent, $symbol);
		}
	}

	/**
	 * The administrator's entry point into campaign management for this topic.
	 *
	 * NEUTRAL BY DESIGN. The link reads the same whether the topic already has
	 * a campaign or not, and carries no action verb. That is not a cosmetic
	 * choice: this page is rendered once and may be clicked much later, by
	 * which time a campaign can have been created, deleted or disabled. Any
	 * verb decided here would be a guess about the future. The ACP resolves
	 * the real state when the request arrives (ADR-014).
	 *
	 * The consequence is that this method issues NO query. It runs on every
	 * topic view on the board, and the neutral label is what keeps it free.
	 *
	 * TWO permissions, not one. a_ is a real ACL option, not a prefix
	 * wildcard: an administrator holding a_donationcampaigns without a_ is
	 * refused by adm/index.php with a 403, so showing them a link would be
	 * showing them a dead end. Both checks are VISIBILITY only — the module
	 * enforces access itself, and this grants nothing.
	 *
	 * @param int $topic_id
	 * @return void
	 */
	protected function assign_topic_tools_link($topic_id)
	{
		if (empty($this->user->data['is_registered'])
			|| !$this->auth->acl_get('a_')
			|| !$this->auth->acl_get('a_donationcampaigns'))
		{
			return;
		}

		$this->language->add_lang('common', 'uflagmey/donationcampaigns');

		$this->template->assign_vars(array(
			'S_DONATIONCAMPAIGNS_TOPIC_LINK'	=> true,
			'U_DONATIONCAMPAIGNS_TOPIC_LINK'	=> $this->acp_campaign_url($topic_id),

			// Core references this in viewtopic_topic_tools.html but assigns it
			// nowhere: it exists so an extension can force the wrench dropdown
			// to render when no core tool would have opened it.
			'S_DISPLAY_TOPIC_TOOLS'				=> true,
		));
	}

	/**
	 * The ACP campaign URL for one topic.
	 *
	 * Modelled on core's only precedent for linking from the board into a
	 * specific ACP module with parameters — U_USER_ADMIN in memberlist.php.
	 * Four properties are copied from it deliberately: the admin path is
	 * injected rather than hard-coded, the parameters are joined with &amp;,
	 * the session id is passed explicitly because the ACP defines NEED_SID,
	 * and the link is emitted only for a specifically authorised user.
	 *
	 * The module identifier is DERIVED, never written out. phpBB maps a class
	 * name to a URL token by replacing backslashes with dashes
	 * (functions_module.php::get_module_identifier), so the class constant is
	 * the source of truth and a rename cannot leave a stale string behind.
	 *
	 * @param int $topic_id
	 * @return string
	 */
	protected function acp_campaign_url($topic_id)
	{
		$identifier = str_replace('\\', '-', '\\' . ltrim(acp_main_module::class, '\\'));

		return append_sid(
			$this->admin_path . 'index.' . $this->php_ext,
			'i=' . $identifier . '&amp;mode=campaigns&amp;t=' . (int) $topic_id,
			true,
			$this->user->session_id
		);
	}

	/**
	 * Assign the donor list, and a summary of any donations that did not fit.
	 *
	 * Every confirmed donation the campaign may show is listed, up to the
	 * configured limit and in the same order as the ACP, each as a display name
	 * and its amount. The public/private flag decides ONLY whether the donor is
	 * named: a private donation, or one with no name, is shown as the localised
	 * "Anonymous" with its amount intact. It is never hidden, and — crucially —
	 * a private donor's stored name is discarded here and never assigned, so it
	 * cannot reach the public template.
	 *
	 * The amount is public here because the list now reconciles with the
	 * collected total: with nothing truncated, the listed amounts sum to it.
	 *
	 * @param int $campaign_id
	 * @param int $exponent
	 * @param string $symbol
	 * @return void
	 */
	protected function assign_donor_list($campaign_id, $exponent, $symbol)
	{
		$limit = (int) $this->config['donationcampaigns_donor_list_limit'];

		$donations = $this->campaign_service->get_donor_list($campaign_id, $limit);

		foreach ($donations as $donation)
		{
			$named = $donation['donation_public'] && trim($donation['donor_name']) !== '';

			// A private or nameless donation is listed as Anonymous. The stored
			// name is read into $name only when it may be shown; otherwise it is
			// dropped here and never assigned to the template.
			$name = $named
				? $donation['donor_name']
				: $this->language->lang('DONATIONCAMPAIGNS_ANONYMOUS');

			$amount = $this->money($donation['donation_amount'], $exponent, $symbol);

			$this->template->assign_block_vars('donationcampaigns_donor', array(
				'NAME'		=> $name,
				'AMOUNT'	=> $amount,
			));
		}

		$remaining = $this->campaign_service->count_donations($campaign_id) - count($donations);

		if ($remaining > 0)
		{
			$this->template->assign_var(
				'DONATIONCAMPAIGNS_AND_OTHERS',
				$this->language->lang('DONATIONCAMPAIGNS_AND_OTHERS', $remaining)
			);
		}
	}

	/**
	 * Integer percentage of the target that has been collected.
	 *
	 * Integer arithmetic throughout: these are money values, and money never
	 * touches a float in this extension. intdiv() truncates, which is the
	 * honest direction — 99.9% of a target should not read as complete.
	 *
	 * The zero-target guard exists even though validation forbids a zero
	 * target, because a hand-edited or pre-upgrade row must not produce a
	 * division by zero on a public page.
	 *
	 * @param int $collected
	 * @param int $target
	 * @return int May exceed 100
	 */
	protected function percentage($collected, $target)
	{
		if ($target <= 0)
		{
			return 0;
		}

		return intdiv($collected * 100, $target);
	}

	/**
	 * The bar's width class, rounded DOWN to the nearest step so that the bar
	 * never claims more progress than has been made.
	 *
	 * @param int $percent
	 * @return int A multiple of PERCENT_STEP, between 0 and 100
	 */
	protected function bar_step($percent)
	{
		$capped = min(100, max(0, $percent));

		return intdiv($capped, self::PERCENT_STEP) * self::PERCENT_STEP;
	}

	/**
	 * @param int $minor_units
	 * @param int $exponent
	 * @param string $symbol
	 * @return string
	 */
	protected function money($minor_units, $exponent, $symbol)
	{
		return $this->formatter->format($minor_units, $exponent) . ' ' . $symbol;
	}

	/**
	 * Render the description through phpBB's own text-formatting path.
	 *
	 * That path owns the decision about which markup is permitted, so the
	 * description is NOT escaped here — doing both would render an
	 * administrator's intended formatting as visible tags.
	 *
	 * @param array $campaign
	 * @return string
	 */
	protected function render_description(array $campaign)
	{
		if ($campaign['campaign_desc'] === '')
		{
			return '';
		}

		return generate_text_for_display(
			$campaign['campaign_desc'],
			$campaign['desc_bbcode_uid'],
			$campaign['desc_bbcode_bitfield'],
			$campaign['desc_bbcode_options']
		);
	}

	/**
	 * The external link, or an empty string when it is not one we will render.
	 *
	 * campaign_service rejects anything outside the scheme allowlist on the
	 * way in. This re-checks on the way out, because a row written before an
	 * upgrade — or edited directly in the database — must not be able to turn
	 * into a live javascript: link on a public page. The check is cheap and
	 * the failure it prevents is stored XSS.
	 *
	 * @param string $url
	 * @return string
	 */
	protected function safe_url($url)
	{
		$url = trim($url);

		if ($url === '' || strpos($url, '//') === 0)
		{
			return '';
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);

		if ($scheme === false || $scheme === null || !in_array(strtolower($scheme), array('http', 'https'), true))
		{
			return '';
		}

		return $url;
	}
}
