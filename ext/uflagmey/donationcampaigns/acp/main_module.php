<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\acp;

use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * ACP module for the extension's three modes.
 *
 * COORDINATION ONLY. This class reads a form, asks a service to validate and
 * store it, and renders a page. It holds no SQL, no rules and no thresholds.
 *
 * phpBB constructs ACP modules with no arguments, so collaborators come from
 * the container rather than the constructor. That is core's contract, not a
 * choice; the module fetches services and never builds them.
 *
 * ESCAPING. Template variables are assigned RAW and escaped in the template
 * with Twig's |e filter, the idiom core itself uses for user-controlled text.
 * Storage stays plain: request values are read with raw_variable() so nothing
 * is escaped on the way in, and nothing is ever marked safe.
 *
 * Two sinks have no template boundary and are escaped in PHP instead, with
 * phpBB's own utf8_htmlspecialchars():
 *
 *   confirm_box()  renders {MESSAGE_TEXT} as raw HTML.
 *   $phpbb_log     stores parameters serialised and the ACP log viewer
 *                  sprintf()s them into a language string and prints the
 *                  result unescaped (phpbb/log/log.php:665-710, rendered by
 *                  {log.ACTION} in adm/style/acp_logs.html). Verified against
 *                  3.3.17: core does NOT escape them.
 *
 * That is sink-level escaping only. The escaped value is never written back to
 * the database.
 */
class main_module
{
	/** @var string */
	public $u_action;

	/** @var string */
	public $tpl_name;

	/** @var string */
	public $page_title;

	/** How many campaigns the ACP list shows per page. */
	const CAMPAIGNS_PER_PAGE = 25;

	/** How many donations the ACP list shows per page. */
	const DONATIONS_PER_PAGE = 25;

	/**
	 * @param int $id
	 * @param string $mode
	 * @return void
	 */
	public function main($id, $mode)
	{
		global $auth, $language;

		$language->add_lang(array('common', 'info_acp_donationcampaigns'), 'uflagmey/donationcampaigns');

		// The module info auth string governs MENU VISIBILITY. It is not an
		// access control on direct invocation, so the permission is checked
		// here as well — this is the one that actually protects the page.
		if (!$auth->acl_get('a_donationcampaigns'))
		{
			trigger_error('NOT_AUTHORISED', E_USER_WARNING);
		}

		switch ($mode)
		{
			case 'settings':
				$this->settings_mode();
			break;

			case 'campaigns':
				$this->campaigns_mode();
			break;

			case 'donations':
				$this->donations_mode();
			break;

			default:
				trigger_error('NO_MODE', E_USER_WARNING);
		}
	}

	/**
	 * The currency and display settings form.
	 *
	 * @return void
	 */
	protected function settings_mode()
	{
		global $request, $template, $language, $phpbb_log, $user;

		$settings = $this->settings_service();

		$this->tpl_name = 'acp_donationcampaigns_settings';
		$this->page_title = $language->lang('ACP_DONATIONCAMPAIGNS_SETTINGS');

		$form_key = 'donationcampaigns_settings';
		add_form_key($form_key);

		$errors = array();
		// What the form shows: the stored values, unless a submission failed,
		// in which case the administrator's own input is shown back so nothing
		// they typed is lost.
		$values = $settings->current();

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$submitted = $this->submitted_settings();
			$values = array_merge($values, $submitted);

			try
			{
				$settings->save($submitted);

				$phpbb_log->add(
					'admin',
					$user->data['user_id'],
					$user->ip,
					'LOG_DONATIONCAMPAIGNS_SETTINGS_UPDATED',
					time()
				);

				trigger_error($language->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
			}
			catch (donationcampaigns_exception $e)
			{
				// Every failure, not just the first, so the form can be fixed
				// in one pass.
				$errors = $e->get_parameters();
			}
		}

		foreach ($errors as $error)
		{
			$template->assign_block_vars('donationcampaigns_error', array(
				'MESSAGE'	=> $language->lang($error),
			));
		}

		$exponent = (int) $values['donationcampaigns_currency_exponent'];

		$template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,

			'S_DONATIONCAMPAIGNS_ERROR'				=> !empty($errors),
			// Shown whenever the board holds amounts, so the consequence is
			// visible BEFORE the exponent is touched, not only after.
			'S_DONATIONCAMPAIGNS_HAS_AMOUNTS'		=> $settings->has_stored_amounts(),
			'S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT'	=> $settings->exponent_change_needs_confirmation($exponent),

			// Escaped here: see the class docblock.
			'DONATIONCAMPAIGNS_CURRENCY_CODE'		=> $values['donationcampaigns_currency_code'],
			'DONATIONCAMPAIGNS_CURRENCY_SYMBOL'		=> $values['donationcampaigns_currency_symbol'],
			'DONATIONCAMPAIGNS_CURRENCY_EXPONENT'	=> $exponent,
			'DONATIONCAMPAIGNS_DONOR_LIST_LIMIT'	=> (int) $values['donationcampaigns_donor_list_limit'],
		));
	}

	/**
	 * The campaign list, plus the delete and recalculate actions.
	 *
	 * Both actions go through phpBB's confirm_box, which is the board's own
	 * confirmation and CSRF flow: it posts a per-session key back and refuses
	 * anything that does not match. Neither action does its own work — delete
	 * goes to campaign_service, recalculate to donation_service.
	 *
	 * TEMPORARY STATE, identified in the plan: add and edit are registered as
	 * links here and implemented in task 16. Until then they refuse cleanly.
	 *
	 * @return void
	 */
	protected function campaigns_mode()
	{
		global $request, $template, $language, $config, $phpbb_log, $user, $phpbb_container;

		$this->tpl_name = 'acp_donationcampaigns_campaigns';
		$this->page_title = $language->lang('ACP_DONATIONCAMPAIGNS_CAMPAIGNS');

		$campaign_service = $phpbb_container->get('uflagmey.donationcampaigns.campaign_service');

		// TOPIC CONTEXT TAKES PRECEDENCE, AND SUPPRESSES THE ACTION ENTIRELY.
		//
		// A topic in the URL means the administrator arrived from that topic,
		// and the module resolves what to do from the database rather than
		// from anything the link claimed. Ignoring the action here is not
		// tidiness: it is what makes t=10&action=delete&campaign_id=1
		// impossible to express, rather than something a guard has to catch.
		//
		// Anything that is not a positive integer is not a context at all and
		// falls through to the list below.
		$topic_id = $this->requested_topic_id();

		if ($topic_id > 0)
		{
			$this->topic_context_form($campaign_service, $topic_id);

			return;
		}

		$action = (string) $request->variable('action', '');
		// Cast explicitly rather than relying on variable() to do it: an id
		// that is not an id must become 0 and match nothing, never reach a
		// service as a string.
		$campaign_id = (int) $request->variable('campaign_id', 0);

		switch ($action)
		{
			case 'delete':
				$this->delete_campaign_action($campaign_service, $campaign_id);
			break;

			case 'recalculate':
				$this->recalculate_action($campaign_service, $campaign_id);
			break;

			// NO 'add' CASE. A campaign is created only from the topic it
			// belongs to, so there is no context-free creation route to reach
			// — and therefore no form that would have to ask for a topic id.
			case 'edit':
				$this->campaign_edit_form($campaign_service, $action, $campaign_id);
			return;
		}

		$this->assign_campaign_list($campaign_service);
	}

	/**
	 * Where a saved campaign form sends the administrator back to.
	 *
	 * A form opened from a topic returns to that topic; one opened from the
	 * campaign list returns to the list. Same shape as adm_back_link(), which
	 * is what core uses, but with wording that names where it goes — "back to
	 * previous page" is not an answer when the previous page was a forum
	 * topic.
	 *
	 * THE URL IS BUILT FROM THE VALIDATED INTEGER AND NOTHING ELSE. No
	 * request parameter contributes to it, so there is no destination an
	 * attacker could supply and nothing here to redirect.
	 *
	 * @param array|null $topic_context
	 * @return string
	 */
	protected function campaign_return_link(?array $topic_context)
	{
		global $language;

		if ($topic_context === null)
		{
			return adm_back_link($this->u_action);
		}

		$url = $this->topic_url($topic_context['topic_id']);

		return '<br /><br /><a href="' . $url . '">&laquo; '
			. $language->lang('DONATIONCAMPAIGNS_BACK_TO_TOPIC') . '</a>';
	}

	/**
	 * The topic named in the request, or 0 when none usable was.
	 *
	 * Guarded with is_scalar() before casting, matching raw_text() above. An
	 * array reaching (int) becomes 1 — a valid-looking topic id nobody asked
	 * for — so the shape is checked before the value is trusted. 0 is not an
	 * error: it means the page was opened without topic context, and the
	 * campaign list is the correct answer.
	 *
	 * @return int
	 */
	protected function requested_topic_id()
	{
		global $request;

		$value = $request->variable('t', 0);

		return is_scalar($value) ? max(0, (int) $value) : 0;
	}

	/**
	 * The campaign form for one topic, in whichever mode the DATABASE says.
	 *
	 * THE AUTHORITATIVE RESOLUTION. Everything about this method exists so
	 * that the answer comes from current state rather than from a request:
	 *
	 *   no campaign for this topic  -> create
	 *   a campaign, enabled or not  -> edit that campaign
	 *
	 * Disabled campaigns are included deliberately. get_campaign_for_topic()
	 * hides them because the PUBLIC box must not show them, which is a
	 * different question from whether one exists. Asking the repository keeps
	 * the two apart; using the service's public reader here would offer to
	 * create a second campaign on a topic that already has one, and the
	 * unique index would then refuse it.
	 *
	 * Because the answer is recomputed on every request, GET and POST alike,
	 * a page that has gone stale simply resolves to the truth when it is
	 * submitted. There is no verb to disagree with.
	 *
	 * @param \uflagmey\donationcampaigns\service\campaign_service $campaign_service
	 * @param int $topic_id Already cast; still untrusted
	 * @return void
	 */
	protected function topic_context_form($campaign_service, $topic_id)
	{
		global $language, $phpbb_container;

		$topics = $phpbb_container->get('uflagmey.donationcampaigns.topic_repository');

		// The topic is the key to everything below, so it is checked before
		// anything is read or shown. Shadows are excluded by topic_exists():
		// viewtopic answers 404 for one, so a campaign there could never be
		// seen.
		if (!$topics->topic_exists($topic_id))
		{
			trigger_error(
				$language->lang('DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND') . adm_back_link($this->campaigns_url()),
				E_USER_WARNING
			);
		}

		$existing = $phpbb_container
			->get('uflagmey.donationcampaigns.campaign_repository')
			->find_by_topic_id($topic_id);

		$titles = $topics->find_titles_by_ids(array($topic_id));

		$this->campaign_edit_form(
			$campaign_service,
			($existing === null) ? 'add' : 'edit',
			($existing === null) ? 0 : $existing['campaign_id'],
			array(
				'topic_id'		=> $topic_id,
				'topic_title'	=> isset($titles[$topic_id]) ? $titles[$topic_id] : '',
			)
		);
	}

	/**
	 * @param \uflagmey\donationcampaigns\service\campaign_service $campaign_service
	 * @param int $campaign_id
	 * @return void
	 */
	protected function delete_campaign_action($campaign_service, $campaign_id)
	{
		global $language, $phpbb_log, $user;

		$campaign = $campaign_service->get_campaign($campaign_id);

		if ($campaign === null)
		{
			// Unknown, or already deleted by someone else. Refused before any
			// confirmation is offered, so no destructive path is ever entered
			// with an id that resolves to nothing.
			trigger_error(
				$language->lang('DONATIONCAMPAIGNS_ERROR_CAMPAIGN_NOT_FOUND') . adm_back_link($this->u_action),
				E_USER_WARNING
			);
		}

		$title = $this->escape_for_message($campaign['campaign_title']);

		if (!confirm_box(true))
		{
			// Names the campaign and says how many donation records go with
			// it, because "are you sure" answers nothing.
			confirm_box(false, $language->lang(
				'DONATIONCAMPAIGNS_CONFIRM_DELETE_CAMPAIGN',
				$title,
				$campaign_service->count_donations($campaign_id)
			), build_hidden_fields(array(
				'action'		=> 'delete',
				'campaign_id'	=> $campaign_id,
			)));

			return;
		}

		$campaign_service->delete_campaign($campaign_id);

		$phpbb_log->add(
			'admin',
			$user->data['user_id'],
			$user->ip,
			'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED',
			time(),
			array($title)
		);

		trigger_error($language->lang('DONATIONCAMPAIGNS_CAMPAIGN_DELETED') . adm_back_link($this->u_action));
	}

	/**
	 * Rebuild a campaign's stored total from its donation rows.
	 *
	 * The new total is computed by donation_service from SUM(donation_amount).
	 * Nothing here reads an amount from the request, and there is no parameter
	 * by which one could be supplied.
	 *
	 * @param \uflagmey\donationcampaigns\service\campaign_service $campaign_service
	 * @param int $campaign_id
	 * @return void
	 */
	protected function recalculate_action($campaign_service, $campaign_id)
	{
		global $language, $config, $phpbb_log, $user, $phpbb_container;

		$campaign = $campaign_service->get_campaign($campaign_id);

		if ($campaign === null)
		{
			trigger_error(
				$language->lang('DONATIONCAMPAIGNS_ERROR_CAMPAIGN_NOT_FOUND') . adm_back_link($this->u_action),
				E_USER_WARNING
			);
		}

		if (!confirm_box(true))
		{
			confirm_box(false, $language->lang(
				'DONATIONCAMPAIGNS_CONFIRM_RECALCULATE',
				$this->escape_for_message($campaign['campaign_title'])
			), build_hidden_fields(array(
				'action'		=> 'recalculate',
				'campaign_id'	=> $campaign_id,
			)));

			return;
		}

		$formatter = $phpbb_container->get('uflagmey.donationcampaigns.currency_formatter');
		$exponent = (int) $config['donationcampaigns_currency_exponent'];

		$before = $campaign['collected_amount'];
		$after = $phpbb_container->get('uflagmey.donationcampaigns.donation_service')->recalculate($campaign_id);

		$phpbb_log->add(
			'admin',
			$user->data['user_id'],
			$user->ip,
			'LOG_DONATIONCAMPAIGNS_TOTAL_RECALCULATED',
			time(),
			array($this->escape_for_message($campaign['campaign_title']))
		);

		trigger_error($language->lang(
			'DONATIONCAMPAIGNS_RECALCULATED',
			$formatter->format($before, $exponent),
			$formatter->format($after, $exponent)
		) . adm_back_link($this->u_action));
	}

	/**
	 * The add and edit form.
	 *
	 * Reads a form, hands it to campaign_service, renders the result. Every
	 * rule — title, target, topic, URL, description encoding — belongs to the
	 * service; nothing is decided here.
	 *
	 * TRANSACTION BOUNDARY: none. create_campaign() and update_campaign() are
	 * single statements.
	 *
	 * @param \uflagmey\donationcampaigns\service\campaign_service $campaign_service
	 * @param string $action add or edit
	 * @param int $campaign_id
	 * @param array|null $topic_context topic_id and topic_title when the form
	 *                                  was opened from a topic; null when it
	 *                                  was reached from the campaign list
	 * @return void
	 */
	protected function campaign_edit_form($campaign_service, $action, $campaign_id, ?array $topic_context = null)
	{
		global $request, $template, $language, $config, $phpbb_log, $user, $phpbb_container;

		$this->tpl_name = 'acp_donationcampaigns_campaign_edit';
		$this->page_title = $language->lang('ACP_DONATIONCAMPAIGNS_CAMPAIGNS');

		$formatter = $phpbb_container->get('uflagmey.donationcampaigns.currency_formatter');
		$exponent = (int) $config['donationcampaigns_currency_exponent'];
		$is_new = ($action === 'add');

		$form_key = 'donationcampaigns_campaign';
		add_form_key($form_key);

		$collected = 0;

		if ($is_new)
		{
			$values = array(
				'topic_id'				=> ($topic_context !== null) ? $topic_context['topic_id'] : 0,
				'campaign_title'		=> '',
				'campaign_desc'			=> '',
				'target_amount'			=> '',
				'external_url'			=> '',
				// A new campaign starts with a usable label rather than an
				// empty button. Generic on purpose: the extension ships no
				// opinion about where a board takes money.
				'external_link_text'	=> $language->lang('DONATIONCAMPAIGNS_LINK_TEXT_DEFAULT'),
				'campaign_enabled'		=> true,
				'show_donor_names'		=> true,
				'show_donation_count'	=> true,
			);
		}
		else
		{
			$campaign = $campaign_service->get_campaign($campaign_id);

			if ($campaign === null)
			{
				trigger_error(
					$language->lang('DONATIONCAMPAIGNS_ERROR_CAMPAIGN_NOT_FOUND') . adm_back_link($this->u_action),
					E_USER_WARNING
				);
			}

			$collected = $campaign['collected_amount'];

			$values = array(
				'topic_id'				=> $campaign['topic_id'],
				'campaign_title'		=> $campaign['campaign_title'],
				// Decoded back to what was typed: the textarea shows source,
				// not storage, or an edited campaign accumulates escaping.
				'campaign_desc'			=> $campaign_service->decode_description($campaign),
				// Into a form field: no grouping, or the parser refuses it on save.
				'target_amount'			=> $formatter->format_for_input($campaign['target_amount'], $exponent),
				'external_link_text'	=> $campaign['external_link_text'],
				'external_url'			=> $campaign['external_url'],
				'campaign_enabled'		=> $campaign['campaign_enabled'],
				'show_donor_names'		=> $campaign['show_donor_names'],
				'show_donation_count'	=> $campaign['show_donation_count'],
			);
		}

		$errors = array();
		$notice = '';

		// THE EXPECTED-STATE GUARD.
		//
		// The form records which campaign it was drawn for; resolution above
		// says which one exists now. When they disagree the world moved while
		// the administrator was typing, and NOTHING is written — the form is
		// redrawn from stored data with an explanation.
		//
		// Without this, the resolve-and-dispatch rule would silently turn a
		// lost create race into an edit, overwriting the campaign that won
		// with values typed for a campaign that did not exist.
		//
		// expected_campaign_id is untrusted, but it can only ever DOWNGRADE a
		// write to a re-render. What gets written is always the RESOLVED
		// campaign, never this number, so forging it cannot redirect a write,
		// widen one, or cause one that would not otherwise have happened.
		$state_mismatch = false;

		if ($request->is_set_post('submit'))
		{
			// Checked on EVERY submission, including one that is about to be
			// refused by the guard below: a stale form is still a form, and
			// the token is what proves it came from this administrator.
			if (!check_form_key($form_key))
			{
				trigger_error($language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if ($topic_context !== null)
			{
				$expected = (int) $request->variable('expected_campaign_id', -1);

				if ($expected !== (int) $campaign_id)
				{
					$state_mismatch = true;
					$notice = $is_new
						? 'DONATIONCAMPAIGNS_NOTICE_CAMPAIGN_GONE'
						: 'DONATIONCAMPAIGNS_NOTICE_CAMPAIGN_EXISTS_NOW';
				}
			}
		}

		if ($request->is_set_post('submit') && !$state_mismatch)
		{
			$values = $this->submitted_campaign();

			// The topic is decided by the page this form was opened from, and
			// is re-resolved on every request. Whatever the submission carried
			// is overwritten here rather than validated, because there is no
			// legitimate way for a topic to arrive in the body at all.
			//
			// On an EDIT the association comes from the stored campaign, not
			// from the context, so a tampered t can never move a campaign —
			// it can only choose which topic's campaign is being looked at.
			// A create is only reachable from a topic, so the context is
			// always there when one is needed.
			$values['topic_id'] = $is_new ? $topic_context['topic_id'] : $campaign['topic_id'];

			// Money is parsed from a string by the formatter, never by
			// arithmetic. A parse failure is reported in the amount's own
			// words rather than as a generic "must be positive".
			$amount_error = '';
			$target = 0;

			try
			{
				$target = $formatter->parse($values['target_amount'], $exponent);
			}
			catch (donationcampaigns_exception $e)
			{
				$amount_error = $e->get_language_key();
			}

			$input = array_merge($values, array('target_amount' => $target));

			$errors = $campaign_service->validate($input, $is_new ? 0 : $campaign_id);

			if ($amount_error !== '')
			{
				// Drop the service's generic complaint about the same field.
				$errors = array_values(array_diff($errors, array('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE')));
				array_unshift($errors, $amount_error);
			}

			if (empty($errors))
			{
				try
				{
					if ($is_new)
					{
						$campaign_service->create_campaign($input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED';
					}
					else
					{
						$campaign_service->update_campaign($campaign_id, $input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED';
					}

					$phpbb_log->add(
						'admin',
						$user->data['user_id'],
						$user->ip,
						$log_key,
						time(),
						array($this->escape_for_message($input['campaign_title']))
					);

					trigger_error(
						$language->lang('DONATIONCAMPAIGNS_CAMPAIGN_SAVED')
						. $this->campaign_return_link($topic_context)
					);
				}
				catch (donationcampaigns_exception $e)
				{
					// Reachable when another administrator claimed the topic
					// between validation and the insert: the unique index
					// rejects this one and the service reports it as the
					// duplicate it is.
					$errors = $e->get_parameters() ?: array($e->get_language_key());
				}
			}
		}

		foreach ($errors as $error)
		{
			$template->assign_block_vars('donationcampaigns_error', array(
				'MESSAGE'	=> $language->lang($error),
			));
		}

		// Posting back into the topic context rather than to a verb is what
		// makes a resubmission re-resolve: the second request asks the same
		// question of the database as the first, and gets the current answer.
		$action_url = ($topic_context !== null)
			? $this->u_action . '&amp;t=' . (int) $topic_context['topic_id']
			: $this->u_action . '&amp;action=' . ($is_new ? 'add' : 'edit') . '&amp;campaign_id=' . (int) $campaign_id;

		// THE TOPIC IS ALWAYS SHOWN, AND NEVER EDITABLE.
		//
		// Which topic that is comes from the authoritative source for the
		// mode: a campaign being edited names its own stored topic, so the
		// association cannot be re-pointed even from a tampered context; a
		// campaign being created names the topic it was opened from.
		$display_topic_id = $is_new
			? (int) $values['topic_id']
			: (int) $campaign['topic_id'];

		$titles = $phpbb_container
			->get('uflagmey.donationcampaigns.topic_repository')
			->find_titles_by_ids(array($display_topic_id));

		$template->assign_vars(array(
			// Shown as text, never as an input. Escaped in the template with
			// |e like every other administrator-controlled value. The title
			// is read from the topics table, never from the request.
			'DONATIONCAMPAIGNS_TOPIC_TITLE'	=> isset($titles[$display_topic_id]) ? $titles[$display_topic_id] : '',
			// Built from the validated integer alone. No URL is ever taken
			// from the request; there is nothing here to redirect.
			'U_DONATIONCAMPAIGNS_TOPIC'		=> $this->topic_url($display_topic_id),
		));

		if ($topic_context !== null)
		{
			$template->assign_vars(array(
				// What this form was drawn for, posted back so the next
				// request can tell whether the world moved in between.
				'DONATIONCAMPAIGNS_EXPECTED_CAMPAIGN_ID'	=> (int) $campaign_id,
				'DONATIONCAMPAIGNS_NOTICE'					=> ($notice !== '') ? $language->lang($notice) : '',
			));
		}

		$template->assign_vars(array(
			'U_ACTION'	=> $action_url,
			'U_BACK'	=> ($topic_context !== null)
				? $this->topic_url($topic_context['topic_id'])
				: $this->u_action,

			'S_DONATIONCAMPAIGNS_TOPIC_CONTEXT'	=> ($topic_context !== null),
			'S_DONATIONCAMPAIGNS_ADD'			=> $is_new,
			'S_DONATIONCAMPAIGNS_ERROR'			=> !empty($errors),
			'S_DONATIONCAMPAIGNS_ENABLED'		=> (bool) $values['campaign_enabled'],
			'S_DONATIONCAMPAIGNS_SHOW_DONORS'	=> (bool) $values['show_donor_names'],
			'S_DONATIONCAMPAIGNS_SHOW_COUNT'	=> (bool) $values['show_donation_count'],

			// Plain fields are escaped here; the description is NOT, because
			// it has already been through the storage encoder and is rendered
			// into a textarea as source. See description_formatter.
			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'		=> $values['campaign_title'],
			'DONATIONCAMPAIGNS_DESC'				=> $values['campaign_desc'],
			'DONATIONCAMPAIGNS_TARGET_AMOUNT'		=> $values['target_amount'],
			// A label beside the amount field, so the administrator can see
			// which currency they are typing. Read from the board's existing
			// setting — the currency is configured in one place and this is
			// not a second copy of it. The stored value stays integer minor
			// units and the parser never sees this.
			'DONATIONCAMPAIGNS_CURRENCY_SYMBOL'		=> (string) $config['donationcampaigns_currency_symbol'],
			'DONATIONCAMPAIGNS_EXTERNAL_URL'		=> $values['external_url'],
			'DONATIONCAMPAIGNS_LINK_TEXT'			=> $values['external_link_text'],
			// Derived. Rendered as text, never as an input.
			'DONATIONCAMPAIGNS_COLLECTED_AMOUNT'	=> $formatter->format($collected, $exponent),
		));

	}

	/**
	 * The campaign form as submitted.
	 *
	 * Plain text is read with raw_variable() so it is stored exactly as typed
	 * and escaped at each output point. The DESCRIPTION is read with
	 * variable(), which escapes it, because it then goes through phpBB's
	 * BBCode storage encoder — that pair is what makes it safe to render
	 * without escaping later.
	 *
	 * collected_amount and the BBCode metadata are absent by construction:
	 * they are derived, and nothing here reads them from the request.
	 *
	 * @return array
	 */
	protected function submitted_campaign()
	{
		global $request;

		return array(
			// NO topic_id. The topic is never submitted: it is resolved from
			// the request context for a create and read from storage for an
			// edit. A field that does not exist cannot be tampered with,
			// which is stronger than validating one that does.
			'campaign_title'		=> $this->raw_text('campaign_title'),
			'campaign_desc'			=> $request->variable('campaign_desc', '', true),
			'target_amount'			=> $this->raw_text('target_amount'),
			'external_url'			=> $this->raw_text('external_url'),
			'external_link_text'	=> $this->raw_text('external_link_text'),
			// An unchecked checkbox is absent from the request, which means
			// off. A present-but-zero value means off too, so the state is
			// read by value rather than by presence alone.
			'campaign_enabled'		=> (bool) $request->variable('campaign_enabled', 0),
			'show_donor_names'		=> (bool) $request->variable('show_donor_names', 0),
			'show_donation_count'	=> (bool) $request->variable('show_donation_count', 0),
		);
	}

	/**
	 * @param string $key
	 * @return string
	 */
	protected function raw_text($key)
	{
		global $request;

		$value = $request->raw_variable($key, '');

		return is_scalar($value) ? (string) $value : '';
	}

	/**
	 * @param \uflagmey\donationcampaigns\service\campaign_service $campaign_service
	 * @return void
	 */
	protected function assign_campaign_list($campaign_service)
	{
		global $request, $template, $config, $phpbb_container;

		$formatter = $phpbb_container->get('uflagmey.donationcampaigns.currency_formatter');
		$exponent = (int) $config['donationcampaigns_currency_exponent'];

		$start = max(0, $request->variable('start', 0));
		$total = $campaign_service->count_campaigns();

		foreach ($campaign_service->list_campaigns(self::CAMPAIGNS_PER_PAGE, $start) as $campaign)
		{
			$campaign_id = $campaign['campaign_id'];
			$target = $campaign['target_amount'];
			$collected = $campaign['collected_amount'];

			$template->assign_block_vars('donationcampaigns_row', array(
				'CAMPAIGN_ID'	=> $campaign_id,
				// Escaped here: phpBB disables Twig autoescaping.
				'TITLE'			=> $campaign['campaign_title'],
				'TOPIC_TITLE'	=> $campaign['topic_title'],
				'TOPIC_ID'		=> $campaign['topic_id'],
				'TARGET'		=> $formatter->format($target, $exponent),
				'COLLECTED'		=> $formatter->format($collected, $exponent),
				'PERCENT'		=> ($target > 0) ? intdiv($collected * 100, $target) : 0,
				'COUNT'			=> $campaign_service->count_donations($campaign_id),
				'S_ENABLED'		=> $campaign['campaign_enabled'],

				'U_TOPIC'		=> $this->topic_url($campaign['topic_id']),
				'U_EDIT'		=> $this->u_action . '&amp;action=edit&amp;campaign_id=' . $campaign_id,
				'U_DELETE'		=> $this->u_action . '&amp;action=delete&amp;campaign_id=' . $campaign_id,
				'U_RECALCULATE'	=> $this->u_action . '&amp;action=recalculate&amp;campaign_id=' . $campaign_id,

				// The ONLY route into donation management: that mode is hidden
				// from the ACP menu because it is meaningless without a
				// campaign. The id comes from the row, never from the request.
				'U_DONATIONS'	=> $this->donations_url() . '&amp;campaign_id=' . $campaign_id,
			));
		}

		$phpbb_container->get('pagination')->generate_template_pagination(
			$this->u_action,
			'pagination',
			'start',
			$total,
			self::CAMPAIGNS_PER_PAGE,
			$start
		);

		// NO ADD LINK. This page lists and manages what exists; campaigns are
		// created from the topic they belong to. The empty state explains
		// where to go rather than leaving an administrator looking for a
		// button that is not there.
		$template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
		));
	}

	/**
	 * The campaign list's URL, whichever mode is currently running.
	 *
	 * phpBB hands each mode its own u_action, so switching modes is string
	 * surgery on it. This mirrors the existing U_BACK idiom rather than
	 * inventing a second way to do the same thing.
	 *
	 * @return string
	 */
	protected function campaigns_url()
	{
		return str_replace('mode=donations', 'mode=campaigns', $this->u_action);
	}

	/**
	 * The donations mode's URL. Callers must append a campaign_id: the mode
	 * refuses to run without one.
	 *
	 * @return string
	 */
	protected function donations_url()
	{
		return str_replace('mode=campaigns', 'mode=donations', $this->u_action);
	}

	/**
	 * @return string
	 */
	protected function php_ext()
	{
		global $phpEx;

		return $phpEx;
	}

	/**
	 * The public URL of a topic, from the ACP.
	 *
	 * THE ROOT PATH IS NOT OPTIONAL. This code runs inside adm/, so a bare
	 * 'viewtopic.php' resolves against the ACP directory and produces
	 * /adm/viewtopic.php, which does not exist. $phpbb_root_path is './../'
	 * there, and append_sid() then rewrites it into a correct board-relative
	 * URL. This is core's own idiom — see acp_attachments.php and
	 * acp_users.php, both of which build U_VIEW_TOPIC exactly this way.
	 *
	 * Built from the integer alone, so there is nothing here to redirect.
	 *
	 * @param int $topic_id
	 * @return string
	 */
	protected function topic_url($topic_id)
	{
		global $phpbb_root_path;

		return append_sid($phpbb_root_path . 'viewtopic.' . $this->php_ext(), 't=' . (int) $topic_id);
	}

	/**
	 * Confirmed donations for one campaign: list, add, edit, delete.
	 *
	 * A row here is money an administrator has ALREADY received and verified.
	 * Nothing in this extension initiates a payment, and no public user can
	 * reach this mode. See the Donation Model section of CLAUDE.md.
	 *
	 * Every mutation goes through donation_service, which recomputes the
	 * campaign total from SUM(donation_amount) inside the same transaction.
	 * This module never touches collected_amount.
	 *
	 * TRANSACTION BOUNDARY: none of its own; owned by donation_service.
	 *
	 * @return void
	 */
	protected function donations_mode()
	{
		global $request, $template, $language, $config, $phpbb_log, $user, $phpbb_container;

		$this->tpl_name = 'acp_donationcampaigns_donations';
		$this->page_title = $language->lang('ACP_DONATIONCAMPAIGNS_DONATIONS');

		$campaign_service = $phpbb_container->get('uflagmey.donationcampaigns.campaign_service');
		$donation_service = $phpbb_container->get('uflagmey.donationcampaigns.donation_service');
		$donations = $phpbb_container->get('uflagmey.donationcampaigns.donation_repository');
		$formatter = $phpbb_container->get('uflagmey.donationcampaigns.currency_formatter');

		$campaign_id = (int) $request->variable('campaign_id', 0);
		$action = (string) $request->variable('action', '');

		// Two distinct failures, and conflating them is what made this mode
		// look like data loss. No campaign in the URL means the page was
		// opened without context — nothing has been deleted. The cast also
		// reduces anything malformed or injection-shaped to a number here,
		// before it can reach a query.
		if ($campaign_id <= 0)
		{
			trigger_error(
				$language->lang('DONATIONCAMPAIGNS_ERROR_NO_CAMPAIGN_SELECTED') . adm_back_link($this->campaigns_url()),
				E_USER_WARNING
			);
		}

		$campaign = $campaign_service->get_campaign($campaign_id);

		// A positive id that resolves to nothing IS a missing campaign.
		if ($campaign === null)
		{
			trigger_error(
				$language->lang('DONATIONCAMPAIGNS_ERROR_CAMPAIGN_NOT_FOUND') . adm_back_link($this->campaigns_url()),
				E_USER_WARNING
			);
		}

		$base = $this->u_action . '&amp;campaign_id=' . $campaign_id;

		switch ($action)
		{
			case 'delete':
				$this->delete_donation_action($donation_service, $donations, $formatter, $campaign_id, $base);
			break;

			case 'add':
			case 'edit':
				$this->donation_edit_form($donation_service, $donations, $campaign, $action, $base);
			return;
		}

		$this->assign_donation_list($donations, $formatter, $campaign, $campaign_id, $base);
	}

	/**
	 * @param \uflagmey\donationcampaigns\service\donation_service $donation_service
	 * @param \uflagmey\donationcampaigns\repository\donation_repository $donations
	 * @param \uflagmey\donationcampaigns\service\currency_formatter $formatter
	 * @param int $campaign_id
	 * @param string $base
	 * @return void
	 */
	protected function delete_donation_action($donation_service, $donations, $formatter, $campaign_id, $base)
	{
		global $request, $language, $config, $phpbb_log, $user;

		$donation_id = (int) $request->variable('donation_id', 0);
		$donation = $donations->find_by_id($donation_id);

		// Refused before any confirmation is offered, so a destructive path is
		// never entered with an id that resolves to nothing. Also refuses a
		// donation belonging to a different campaign than the one in the URL.
		if ($donation === null || $donation['campaign_id'] !== $campaign_id)
		{
			trigger_error(
				$language->lang('DONATIONCAMPAIGNS_ERROR_DONATION_NOT_FOUND') . adm_back_link($base),
				E_USER_WARNING
			);
		}

		// Escaped HERE, not at the list boundary: phpBB renders confirm_box's
		// message text and the ACP log viewer's entries as raw HTML, so a
		// donor name carrying markup would execute in either place.
		$label = $this->escape_for_message($this->donor_label($donation['donor_name']));
		$amount = $this->escape_for_message($formatter->format($donation['donation_amount'], (int) $config['donationcampaigns_currency_exponent']));

		if (!confirm_box(true))
		{
			// Names the receipt being destroyed: "are you sure" answers nothing.
			confirm_box(false, $language->lang('DONATIONCAMPAIGNS_CONFIRM_DELETE_DONATION', $amount, $label), build_hidden_fields(array(
				'action'		=> 'delete',
				'campaign_id'	=> $campaign_id,
				'donation_id'	=> $donation_id,
			)));

			return;
		}

		$donation_service->delete_donation($donation_id);

		$phpbb_log->add(
			'admin',
			$user->data['user_id'],
			$user->ip,
			'LOG_DONATIONCAMPAIGNS_DONATION_DELETED',
			time(),
			array($amount, $label)
		);

		trigger_error($language->lang('DONATIONCAMPAIGNS_DONATION_DELETED') . adm_back_link($base));
	}

	/**
	 * @param \uflagmey\donationcampaigns\service\donation_service $donation_service
	 * @param \uflagmey\donationcampaigns\repository\donation_repository $donations
	 * @param array $campaign
	 * @param string $action
	 * @param string $base
	 * @return void
	 */
	protected function donation_edit_form($donation_service, $donations, array $campaign, $action, $base)
	{
		global $request, $template, $language, $config, $phpbb_log, $user, $phpbb_container;

		$this->tpl_name = 'acp_donationcampaigns_donation_edit';
		$this->page_title = $language->lang('ACP_DONATIONCAMPAIGNS_DONATIONS');

		$formatter = $phpbb_container->get('uflagmey.donationcampaigns.currency_formatter');
		$exponent = (int) $config['donationcampaigns_currency_exponent'];
		$is_new = ($action === 'add');

		$form_key = 'donationcampaigns_donation';
		add_form_key($form_key);

		$donation_id = (int) $request->variable('donation_id', 0);

		if ($is_new)
		{
			$values = array(
				'donation_amount'	=> '',
				'donor_name'		=> '',
				'donation_time'		=> gmdate('Y-m-d'),
				'donation_public'	=> true,
			);
		}
		else
		{
			$donation = $donations->find_by_id($donation_id);

			// A donation belongs to the campaign that recorded it. Refusing a
			// mismatch stops a crafted URL from editing another campaign's
			// receipt through this one's page.
			if ($donation === null || $donation['campaign_id'] !== $campaign['campaign_id'])
			{
				trigger_error(
					$language->lang('DONATIONCAMPAIGNS_ERROR_DONATION_NOT_FOUND') . adm_back_link($base),
					E_USER_WARNING
				);
			}

			$values = array(
				// Into a form field: no grouping, or the parser refuses it on save.
				'donation_amount'	=> $formatter->format_for_input($donation['donation_amount'], $exponent),
				'donor_name'		=> $donation['donor_name'],
				'donation_time'		=> gmdate('Y-m-d', $donation['donation_time']),
				'donation_public'	=> $donation['donation_public'],
			);
		}

		$errors = array();

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($language->lang('FORM_INVALID') . adm_back_link($base), E_USER_WARNING);
			}

			$values = array(
				'donation_amount'	=> $this->raw_text('donation_amount'),
				'donor_name'		=> $this->raw_text('donor_name'),
				'donation_time'		=> $this->raw_text('donation_time'),
				'donation_public'	=> (bool) $request->variable('donation_public', 0),
			);

			$input = array(
				'donor_name'		=> $values['donor_name'],
				'donation_public'	=> $values['donation_public'],
			);

			try
			{
				$input['donation_amount'] = $formatter->parse($values['donation_amount'], $exponent);
			}
			catch (donationcampaigns_exception $e)
			{
				$errors[] = $e->get_language_key();
			}

			$timestamp = $this->parse_date($values['donation_time']);

			if ($timestamp === null)
			{
				$errors[] = 'DONATIONCAMPAIGNS_ERROR_TIME_INVALID';
			}
			else
			{
				$input['donation_time'] = $timestamp;
			}

			if (empty($errors))
			{
				try
				{
					if ($is_new)
					{
						// The campaign comes from the verified page context,
						// never from the request body.
						$donation_service->add_donation($campaign['campaign_id'], $input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_DONATION_ADDED';
					}
					else
					{
						$donation_service->edit_donation($donation_id, $input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_DONATION_EDITED';
					}

					$phpbb_log->add(
						'admin',
						$user->data['user_id'],
						$user->ip,
						$log_key,
						time(),
						array(
							$this->escape_for_message($formatter->format($input['donation_amount'], $exponent)),
							$this->escape_for_message($this->donor_label($input['donor_name'])),
						)
					);

					trigger_error($language->lang('DONATIONCAMPAIGNS_DONATION_SAVED') . adm_back_link($base));
				}
				catch (donationcampaigns_exception $e)
				{
					$errors[] = $e->get_language_key();
				}
			}
		}

		foreach ($errors as $error)
		{
			$template->assign_block_vars('donationcampaigns_error', array(
				'MESSAGE'	=> $language->lang($error),
			));
		}

		$template->assign_vars(array(
			'U_ACTION'	=> $base . '&amp;action=' . ($is_new ? 'add' : 'edit') . '&amp;donation_id=' . $donation_id,
			'U_BACK'	=> $base,

			'S_DONATIONCAMPAIGNS_ADD'		=> $is_new,
			'S_DONATIONCAMPAIGNS_ERROR'		=> !empty($errors),
			'S_DONATIONCAMPAIGNS_PUBLIC'	=> (bool) $values['donation_public'],

			// Escaped here: phpBB disables Twig autoescaping.
			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'	=> $campaign['campaign_title'],
			'DONATIONCAMPAIGNS_DONATION_AMOUNT'	=> $values['donation_amount'],
			'DONATIONCAMPAIGNS_DONOR_NAME'		=> $values['donor_name'],
			'DONATIONCAMPAIGNS_DONATION_TIME'	=> $values['donation_time'],
		));
	}

	/**
	 * @param \uflagmey\donationcampaigns\repository\donation_repository $donations
	 * @param \uflagmey\donationcampaigns\service\currency_formatter $formatter
	 * @param array $campaign
	 * @param int $campaign_id
	 * @param string $base
	 * @return void
	 */
	protected function assign_donation_list($donations, $formatter, array $campaign, $campaign_id, $base)
	{
		global $request, $template, $config, $user, $phpbb_container;

		$exponent = (int) $config['donationcampaigns_currency_exponent'];
		$start = max(0, (int) $request->variable('start', 0));
		$total = $donations->count_by_campaign($campaign_id);

		foreach ($donations->find_page_by_campaign($campaign_id, self::DONATIONS_PER_PAGE, $start) as $donation)
		{
			$donation_id = $donation['donation_id'];

			$template->assign_block_vars('donationcampaigns_donation', array(
				'DONATION_ID'	=> $donation_id,
				'AMOUNT'		=> $formatter->format($donation['donation_amount'], $exponent),
				'DONOR_NAME'	=> $this->donor_label($donation['donor_name']),
				'DONATED_AT'	=> $user->format_date($donation['donation_time']),
				'RECORDED_AT'	=> $user->format_date($donation['donation_created']),
				'S_PUBLIC'		=> $donation['donation_public'],

				'U_EDIT'		=> $base . '&amp;action=edit&amp;donation_id=' . $donation_id,
				'U_DELETE'		=> $base . '&amp;action=delete&amp;donation_id=' . $donation_id,
			));
		}

		$phpbb_container->get('pagination')->generate_template_pagination(
			$base,
			'pagination',
			'start',
			$total,
			self::DONATIONS_PER_PAGE,
			$start
		);

		$template->assign_vars(array
		(
			'U_ACTION'						=> $base,
			'U_DONATIONCAMPAIGNS_ADD'		=> $base . '&amp;action=add',
			'U_BACK'						=> $this->campaigns_url(),

			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'		=> $campaign['campaign_title'],
			// Derived from the receipts below. Displayed, never editable.
			'DONATIONCAMPAIGNS_COLLECTED_AMOUNT'	=> $formatter->format($campaign['collected_amount'], $exponent),
		));
	}

	/**
	 * A donation with no name is shown as "Anonymous" rather than as a blank.
	 *
	 * @param string $donor_name
	 * @return string
	 */
	protected function donor_label($donor_name)
	{
		global $language;

		$donor_name = trim((string) $donor_name);

		return ($donor_name !== '') ? $donor_name : $language->lang('DONATIONCAMPAIGNS_ANONYMOUS');
	}

	/**
	 * A date as typed, in the ISO form the date input produces.
	 *
	 * Interpreted as UTC midnight, matching how the board stores timestamps.
	 * Returns null rather than a fallback, so an unusable date is reported as
	 * an error instead of silently becoming today.
	 *
	 * @param string $value
	 * @return int|null
	 */
	protected function parse_date($value)
	{
		$value = trim((string) $value);

		if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts))
		{
			return null;
		}

		if (!checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]))
		{
			return null;
		}

		$timestamp = gmmktime(0, 0, 0, (int) $parts[2], (int) $parts[3], (int) $parts[1]);

		return ($timestamp > 0) ? $timestamp : null;
	}

	/**
	 * The four settings as submitted, unescaped.
	 *
	 * raw_variable() rather than variable(): variable() applies
	 * htmlspecialchars to every string, which would store '&amp;' for an
	 * administrator who typed '&'. Values are stored exactly as entered and
	 * escaped when rendered. Trimming, casting and validation all belong to
	 * settings_service, so nothing is decided here.
	 *
	 * @return array
	 */
	protected function submitted_settings()
	{
		global $request;

		$submitted = array();

		foreach (array('donationcampaigns_currency_code', 'donationcampaigns_currency_symbol') as $key)
		{
			$value = $request->raw_variable($key, '');

			// raw_variable returns whatever was posted, which may be an array.
			$submitted[$key] = is_scalar($value) ? (string) $value : '';
		}

		foreach (array('donationcampaigns_currency_exponent', 'donationcampaigns_donor_list_limit') as $key)
		{
			$value = $request->raw_variable($key, '');

			$submitted[$key] = is_scalar($value) ? (string) $value : '';
		}

		$submitted['donationcampaigns_confirm_exponent'] = $request->is_set_post('donationcampaigns_confirm_exponent');

		return $submitted;
	}

	/**
	 * @return \uflagmey\donationcampaigns\service\settings_service
	 */
	protected function settings_service()
	{
		global $phpbb_container;

		return $phpbb_container->get('uflagmey.donationcampaigns.settings_service');
	}

	/**
	 * Escape a value for a sink that has no template boundary.
	 *
	 * ONLY for confirm_box() messages and admin-log parameters, both of which
	 * phpBB renders as raw HTML. Everything with a template goes out raw and
	 * is escaped there with |e.
	 *
	 * utf8_htmlspecialchars() is phpBB's own wrapper and is what core uses for
	 * this; it applies ENT_COMPAT with UTF-8.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function escape_for_message($value)
	{
		return utf8_htmlspecialchars((string) $value);
	}
}
