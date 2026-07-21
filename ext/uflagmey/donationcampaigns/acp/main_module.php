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

		// Campaign creation and editing moved to the topic frontend (RC2). The
		// ACP list keeps only the global maintenance actions below; the row's
		// Edit link points at the frontend controller.
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
		}

		$this->assign_campaign_list($campaign_service);
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
				// Editing moved to the frontend controller (RC2 cutover); the ACP
				// list links out to it rather than hosting a competing form.
				'U_EDIT'		=> $phpbb_container->get('controller.helper')->route(
					'uflagmey_donationcampaigns_campaign_edit',
					array('campaign_id' => $campaign_id)
				),
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
			// A label beside the amount field, read from the board's existing
			// setting — the same currency the campaign form shows, not a second
			// copy. The stored value stays integer minor units and the parser
			// never sees this.
			'DONATIONCAMPAIGNS_CURRENCY_SYMBOL'	=> (string) $config['donationcampaigns_currency_symbol'],
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
