<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\controller;

use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * Frontend donation-ledger management, reached from a topic's campaign.
 *
 * COORDINATION ONLY. Every business rule — validation, the integer-minor-units
 * money, storage, and the recalculated campaign total — lives in
 * donation_service and is reused unchanged. This is the ledger counterpart of
 * campaign_controller and follows the same discipline.
 *
 * THE AUTHORIZATION CHAIN, on every action without exception:
 *   1. resolve the anchor server-side — the campaign (add) or the donation and
 *      then its campaign (edit/delete);
 *   2. reject anything that resolves to nothing (uniform denial);
 *   3. load the campaign's topic and verify the campaign <-> topic pair;
 *   4. derive the CURRENT forum_id from that loaded topic;
 *   5. authorise donations against that forum (can_manage_donations);
 *   6. only then render or mutate.
 *
 * SEPARATE PERMISSION. Managing donations requires m_donationcampaigns_donations
 * (or the admin override), NOT m_donationcampaigns_manage. A shell manager who
 * lacks the donations permission is refused here, and vice versa — the two
 * capabilities are deliberately independent, because donations expose donor
 * names and private donor identities that campaign management does not.
 *
 * UNIFORM DENIAL. Every refusal raises the same 404 not-available response, so a
 * probe cannot learn whether a foreign campaign or donation exists.
 *
 * NO MUTATING GET. GET renders a form; every write is POST behind a form key.
 * The forum id is never taken from the request.
 */
class donation_controller
{
	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var \phpbb\user */
	protected $user;

	/** @var \uflagmey\donationcampaigns\service\access */
	protected $access;

	/** @var \uflagmey\donationcampaigns\service\donation_service */
	protected $donation_service;

	/** @var \uflagmey\donationcampaigns\repository\donation_repository */
	protected $donations;

	/** @var \uflagmey\donationcampaigns\service\campaign_service */
	protected $campaign_service;

	/** @var \uflagmey\donationcampaigns\repository\topic_repository */
	protected $topics;

	/** @var \uflagmey\donationcampaigns\service\currency_formatter */
	protected $formatter;

	public function __construct(
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\phpbb\language\language $language,
		\phpbb\config\config $config,
		\phpbb\request\request_interface $request,
		\phpbb\log\log_interface $log,
		$user,
		\uflagmey\donationcampaigns\service\access $access,
		\uflagmey\donationcampaigns\service\donation_service $donation_service,
		\uflagmey\donationcampaigns\repository\donation_repository $donations,
		\uflagmey\donationcampaigns\service\campaign_service $campaign_service,
		\uflagmey\donationcampaigns\repository\topic_repository $topics,
		\uflagmey\donationcampaigns\service\currency_formatter $formatter
	)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->language = $language;
		$this->config = $config;
		$this->request = $request;
		$this->log = $log;
		$this->user = $user;
		$this->access = $access;
		$this->donation_service = $donation_service;
		$this->donations = $donations;
		$this->campaign_service = $campaign_service;
		$this->topics = $topics;
		$this->formatter = $formatter;
	}

	/**
	 * Record a confirmed donation against a campaign.
	 *
	 * The campaign is the anchor: it is loaded by id, its topic verified, and the
	 * caller authorised for donations in that topic's current forum. The campaign
	 * comes from the URL and is re-resolved here; there is no campaign field to
	 * tamper with on the form.
	 *
	 * @param int $campaign_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function add($campaign_id)
	{
		$this->load_language();

		list($campaign, $topic) = $this->load_campaign_in_topic((int) $campaign_id);
		$this->require_donations($topic['forum_id']);

		return $this->render_form($campaign, $topic, null);
	}

	/**
	 * Edit a confirmed donation.
	 *
	 * The donation is the anchor: loaded by id, then its campaign and that
	 * campaign's topic. Authorisation is against that topic's current forum. The
	 * donation cannot be re-pointed to another campaign — the association comes
	 * from the stored row, so a tampered id can only choose (and be refused for) a
	 * donation the caller may not touch.
	 *
	 * @param int $donation_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function edit($donation_id)
	{
		$this->load_language();

		list($donation, $campaign, $topic) = $this->load_donation_in_topic((int) $donation_id);
		$this->require_donations($topic['forum_id']);

		return $this->render_form($campaign, $topic, $donation);
	}

	// --------------------------------------------------------- the shared form

	/**
	 * Render, and on POST process, the add or edit donation form. $donation null
	 * means add. The validation, money parsing and the recalculated total are
	 * donation_service's, untouched; only the presentation and the auth surface
	 * are the frontend's.
	 *
	 * @param array $campaign
	 * @param array $topic
	 * @param array|null $donation
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function render_form(array $campaign, array $topic, $donation)
	{
		$is_new = ($donation === null);
		$exponent = (int) $this->config['donationcampaigns_currency_exponent'];

		$form_key = 'donationcampaigns_donation';
		add_form_key($form_key);

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
			$values = array(
				// Into a form field: no grouping, or the parser refuses it on save.
				'donation_amount'	=> $this->formatter->format_for_input($donation['donation_amount'], $exponent),
				'donor_name'		=> $donation['donor_name'],
				'donation_time'		=> gmdate('Y-m-d', $donation['donation_time']),
				'donation_public'	=> $donation['donation_public'],
			);
		}

		$errors = array();

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				throw $this->not_available();
			}

			$values = array(
				'donation_amount'	=> $this->raw_text('donation_amount'),
				'donor_name'		=> $this->raw_text('donor_name'),
				'donation_time'		=> $this->raw_text('donation_time'),
				'donation_public'	=> (bool) $this->request->variable('donation_public', 0),
			);

			$input = array(
				'donor_name'		=> $values['donor_name'],
				'donation_public'	=> $values['donation_public'],
			);

			try
			{
				$input['donation_amount'] = $this->formatter->parse($values['donation_amount'], $exponent);
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
						// The campaign comes from the verified page context, never
						// from the request body.
						$this->donation_service->add_donation($campaign['campaign_id'], $input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_DONATION_ADDED';
					}
					else
					{
						$this->donation_service->edit_donation($donation['donation_id'], $input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_DONATION_EDITED';
					}

					$this->log_donation($log_key, $topic['forum_id'], $topic['topic_id'], $input['donation_amount'], $input['donor_name'], $exponent);

					return $this->message($this->language->lang(
						'DONATIONCAMPAIGNS_DONATION_SAVED_RETURN',
						'<a href="' . $this->topic_url($topic['topic_id']) . '">',
						'</a>'
					));
				}
				catch (donationcampaigns_exception $e)
				{
					$errors[] = $e->get_language_key();
				}
			}
		}

		foreach ($errors as $error)
		{
			$this->template->assign_block_vars('donationcampaigns_error', array(
				'MESSAGE'	=> $this->language->lang($error),
			));
		}

		$this->template->assign_vars(array(
			'S_DONATIONCAMPAIGNS_ADD'		=> $is_new,
			'S_DONATIONCAMPAIGNS_ERROR'		=> !empty($errors),
			'S_DONATIONCAMPAIGNS_PUBLIC'	=> (bool) $values['donation_public'],

			'U_ACTION'	=> $is_new
				? $this->helper->route('uflagmey_donationcampaigns_donation_add', array('campaign_id' => $campaign['campaign_id']))
				: $this->helper->route('uflagmey_donationcampaigns_donation_edit', array('donation_id' => $donation['donation_id'])),
			'U_BACK'	=> $this->topic_url($topic['topic_id']),

			// The campaign title is shown as trusted text, escaped in the template.
			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'	=> $campaign['campaign_title'],
			'DONATIONCAMPAIGNS_DONATION_AMOUNT'	=> $values['donation_amount'],
			// A label beside the amount field, read from the board's existing
			// setting — the same currency the campaign form shows. The stored value
			// stays integer minor units and the parser never sees this.
			'DONATIONCAMPAIGNS_CURRENCY_SYMBOL'	=> (string) $this->config['donationcampaigns_currency_symbol'],
			'DONATIONCAMPAIGNS_DONOR_NAME'		=> $values['donor_name'],
			'DONATIONCAMPAIGNS_DONATION_TIME'	=> $values['donation_time'],
		));

		return $this->helper->render('donationcampaigns_donation_form.html', $this->language->lang(
			$is_new ? 'DONATIONCAMPAIGNS_ADD_DONATION' : 'DONATIONCAMPAIGNS_EDIT_DONATION'
		));
	}

	// -------------------------------------------------- the authorization chain

	/**
	 * Load a campaign and its topic, verifying the pair. Uniform denial hides
	 * whether the campaign, the topic, or a matching pair exists.
	 *
	 * @param int $campaign_id
	 * @return array{0:array,1:array} the campaign and its topic
	 */
	protected function load_campaign_in_topic($campaign_id)
	{
		$campaign = $this->campaign_service->get_campaign($campaign_id);

		if ($campaign === null)
		{
			throw $this->not_available();
		}

		$topic = $this->topics->find($campaign['topic_id']);

		if ($topic === null || $topic['topic_id'] !== (int) $campaign['topic_id'])
		{
			throw $this->not_available();
		}

		return array($campaign, $topic);
	}

	/**
	 * Load a donation, its campaign and that campaign's topic, verifying each
	 * link. A donation belongs to the campaign that recorded it; refusing a
	 * mismatch stops a crafted id from reaching another campaign's receipt.
	 *
	 * @param int $donation_id
	 * @return array{0:array,1:array,2:array} the donation, its campaign, its topic
	 */
	protected function load_donation_in_topic($donation_id)
	{
		$donation = $this->donations->find_by_id($donation_id);

		if ($donation === null)
		{
			throw $this->not_available();
		}

		list($campaign, $topic) = $this->load_campaign_in_topic((int) $donation['campaign_id']);

		if ((int) $donation['campaign_id'] !== (int) $campaign['campaign_id'])
		{
			throw $this->not_available();
		}

		return array($donation, $campaign, $topic);
	}

	/**
	 * @param int $forum_id
	 * @return void
	 */
	protected function require_donations($forum_id)
	{
		if (!$this->access->can_manage_donations($forum_id))
		{
			throw $this->not_available();
		}
	}

	/**
	 * The one denial for every refusal: a 404 that reveals nothing.
	 *
	 * @return \phpbb\exception\http_exception
	 */
	protected function not_available()
	{
		return new \phpbb\exception\http_exception(404, 'DONATIONCAMPAIGNS_NOT_AVAILABLE');
	}

	// --------------------------------------------------------------- helpers

	/**
	 * @param string $html
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function message($html)
	{
		return $this->helper->message($html);
	}

	/**
	 * A donation action in the MODERATOR log, scoped to the forum and topic.
	 *
	 * forum_id and topic_id are keyed so phpBB files the entry against them; the
	 * amount and donor label are the message arguments, escaped because the log
	 * viewer renders raw HTML. The amount is formatted from integer minor units,
	 * never taken from the field.
	 *
	 * @param string $log_key
	 * @param int $forum_id
	 * @param int $topic_id
	 * @param int $amount_minor_units
	 * @param string $donor_name
	 * @param int $exponent
	 * @return void
	 */
	protected function log_donation($log_key, $forum_id, $topic_id, $amount_minor_units, $donor_name, $exponent)
	{
		$this->log->add(
			'mod',
			$this->user->data['user_id'],
			$this->user->ip,
			$log_key,
			time(),
			array(
				'forum_id'	=> (int) $forum_id,
				'topic_id'	=> (int) $topic_id,
				$this->escape_for_message($this->formatter->format($amount_minor_units, $exponent)),
				$this->escape_for_message($this->donor_label($donor_name)),
			)
		);
	}

	/**
	 * A donation with no donor name is a real, countable donation from someone who
	 * asked not to be named. The log and the confirm dialog say "Anonymous" rather
	 * than leaving the arg blank.
	 *
	 * @param string $donor_name
	 * @return string
	 */
	protected function donor_label($donor_name)
	{
		$donor_name = trim((string) $donor_name);

		return ($donor_name !== '') ? $donor_name : $this->language->lang('DONATIONCAMPAIGNS_ANONYMOUS');
	}

	/**
	 * @param string $value
	 * @return string
	 */
	protected function escape_for_message($value)
	{
		return utf8_htmlspecialchars((string) $value);
	}

	/**
	 * @param string $key
	 * @return string
	 */
	protected function raw_text($key)
	{
		$value = $this->request->raw_variable($key, '');

		return is_scalar($value) ? (string) $value : '';
	}

	/**
	 * A date as typed, in the ISO form the date input produces, to a UTC midnight
	 * timestamp — or null when it is not a real calendar date.
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
	 * @return void
	 */
	protected function load_language()
	{
		// common carries the shared errors and workflow strings;
		// info_acp_donationcampaigns carries the donation form's field labels,
		// reused rather than duplicated.
		$this->language->add_lang(array('common', 'info_acp_donationcampaigns'), 'uflagmey/donationcampaigns');
	}

	/**
	 * The public URL of a topic. Built from the integer alone, so there is nothing
	 * here to redirect.
	 *
	 * @param int $topic_id
	 * @return string
	 */
	protected function topic_url($topic_id)
	{
		return append_sid('viewtopic.' . $this->php_ext(), 't=' . (int) $topic_id);
	}

	/**
	 * @return string
	 */
	protected function php_ext()
	{
		global $phpEx;

		return $phpEx;
	}
}
