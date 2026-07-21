<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * Frontend campaign-shell management, reached from a topic.
 *
 * COORDINATION ONLY. Every business rule — validation, money, storage, the
 * total — lives in campaign_service and is reused unchanged. This controller
 * loads the topic, derives its forum, authorises against that forum through the
 * access service, and renders or delegates. It writes nothing of its own.
 *
 * THE AUTHORIZATION CHAIN, applied to every action without exception:
 *   1. load the topic server-side (topic_repository::find);
 *   2. reject an invalid, deleted or moved-shadow topic (find returns null);
 *   3. derive the CURRENT forum_id from that loaded topic;
 *   4. load the campaign where the action needs one;
 *   5. verify campaign <-> topic;
 *   6. authorise against the derived forum;
 *   7. only then render or mutate.
 *
 * The administrator override (a_donationcampaigns) is applied in step 6 only. It
 * does NOT bypass steps 1-5: an administrator acting on a missing topic or a
 * mismatched campaign is refused exactly like anyone else.
 *
 * UNIFORM DENIAL. Every refusal — unknown topic, unknown campaign, mismatched
 * pair, wrong forum, missing permission — raises the same 404 not-available
 * response, so a probe cannot learn whether a foreign campaign exists. The
 * distinctions are kept in tests, not in user-visible messages.
 *
 * NO MUTATING GET. GET renders a form; every write is POST behind a form key.
 * The forum id is never taken from the request.
 */
class campaign_controller
{
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

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var \phpbb\user */
	protected $user;

	/** @var \uflagmey\donationcampaigns\service\access */
	protected $access;

	/** @var \uflagmey\donationcampaigns\service\campaign_service */
	protected $campaign_service;

	/** @var \uflagmey\donationcampaigns\repository\campaign_repository */
	protected $campaigns;

	/** @var \uflagmey\donationcampaigns\repository\donation_repository */
	protected $donations;

	/** @var \uflagmey\donationcampaigns\repository\topic_repository */
	protected $topics;

	/** @var \uflagmey\donationcampaigns\service\currency_formatter */
	protected $formatter;

	public function __construct(
		\phpbb\controller\helper $helper,
		\phpbb\path_helper $path_helper,
		\phpbb\template\template $template,
		\phpbb\language\language $language,
		\phpbb\config\config $config,
		\phpbb\request\request_interface $request,
		\phpbb\log\log_interface $log,
		$user,
		\uflagmey\donationcampaigns\service\access $access,
		\uflagmey\donationcampaigns\service\campaign_service $campaign_service,
		\uflagmey\donationcampaigns\repository\campaign_repository $campaigns,
		\uflagmey\donationcampaigns\repository\donation_repository $donations,
		\uflagmey\donationcampaigns\repository\topic_repository $topics,
		\uflagmey\donationcampaigns\service\currency_formatter $formatter
	)
	{
		$this->helper = $helper;
		$this->path_helper = $path_helper;
		$this->template = $template;
		$this->language = $language;
		$this->config = $config;
		$this->request = $request;
		$this->log = $log;
		$this->user = $user;
		$this->access = $access;
		$this->campaign_service = $campaign_service;
		$this->campaigns = $campaigns;
		$this->donations = $donations;
		$this->topics = $topics;
		$this->formatter = $formatter;
	}

	/**
	 * The management landing for a topic.
	 *
	 * Openable by anyone who can manage the shell OR the donations in the topic's
	 * forum. It resolves the current database state: no campaign and the caller
	 * may manage -> the creation form; no campaign and the caller may only manage
	 * donations -> a localised "nothing here yet" with a way back; a campaign
	 * exists -> the landing, showing only the actions the caller is authorised
	 * for.
	 *
	 * @param int $topic_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function manage($topic_id)
	{
		$this->load_language();

		$topic = $this->load_topic((int) $topic_id);
		$forum_id = $topic['forum_id'];

		$can_manage = $this->access->can_manage($forum_id);
		$can_donations = $this->access->can_manage_donations($forum_id);

		if (!$can_manage && !$can_donations)
		{
			throw $this->not_available();
		}

		$campaign = $this->campaign_service->get_campaign_by_topic($topic['topic_id']);

		if ($campaign === null)
		{
			// A campaign is only ever created by someone who may manage the
			// shell. A donations-only holder is not silently upgraded — the whole
			// point of the split — so they get a clear, safe dead end.
			if (!$can_manage)
			{
				return $this->message(
					$this->language->lang('DONATIONCAMPAIGNS_NO_CAMPAIGN_YET')
					. '<br /><br />' . $this->return_link($topic['topic_id'])
				);
			}

			return $this->render_form($topic, null, $forum_id);
		}

		return $this->render_landing($topic, $campaign, $forum_id, $can_manage, $can_donations);
	}

	/**
	 * Create the campaign for a topic.
	 *
	 * Reachable only by a shell manager. The topic is fixed by the URL and
	 * re-resolved here; there is no topic field to tamper with. If a campaign
	 * already exists on the topic — a stale link, or a create race another
	 * request won — the caller is sent to the landing rather than shown a form
	 * that could never succeed against the unique index.
	 *
	 * @param int $topic_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function create($topic_id)
	{
		$this->load_language();

		$topic = $this->load_topic((int) $topic_id);
		$this->require_manage($topic['forum_id']);

		if ($this->campaign_service->get_campaign_by_topic($topic['topic_id']) !== null)
		{
			return new RedirectResponse($this->helper->route(
				'uflagmey_donationcampaigns_manage',
				array('topic_id' => $topic['topic_id'])
			));
		}

		return $this->render_form($topic, null, $topic['forum_id']);
	}

	/**
	 * Edit an existing campaign.
	 *
	 * The campaign is loaded by id, its topic is loaded and verified against it,
	 * and authorisation is against that topic's current forum. The topic is never
	 * re-pointed: the association comes from the stored campaign, so a tampered id
	 * cannot move a campaign, only choose (and be refused for) one the caller may
	 * not touch.
	 *
	 * @param int $campaign_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function edit($campaign_id)
	{
		$this->load_language();

		list($campaign, $topic) = $this->load_campaign_in_topic((int) $campaign_id);
		$this->require_manage($topic['forum_id']);

		return $this->render_form($topic, $campaign, $topic['forum_id']);
	}

	/**
	 * Enable a campaign. A direct POST behind a form key — no confirmation, and
	 * no GET path (the route is POST-only).
	 *
	 * @param int $campaign_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function enable($campaign_id)
	{
		$this->load_language();

		list($campaign, $topic) = $this->load_campaign_in_topic((int) $campaign_id);
		$this->require_manage($topic['forum_id']);

		if (!check_form_key('donationcampaigns_toggle'))
		{
			throw $this->not_available();
		}

		$this->set_enabled($campaign, $topic, true, 'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ENABLED');

		return $this->message($this->language->lang(
			'DONATIONCAMPAIGNS_CAMPAIGN_SAVED_RETURN',
			'<a href="' . $this->topic_url($topic['topic_id']) . '">',
			'</a>'
		));
	}

	/**
	 * Disable a campaign. Requires an explicit confirmation; the confirmed step
	 * is a POST carrying confirm_box's key. Nothing is written until then.
	 *
	 * @param int $campaign_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function disable($campaign_id)
	{
		$this->load_language();

		list($campaign, $topic) = $this->load_campaign_in_topic((int) $campaign_id);
		$this->require_manage($topic['forum_id']);

		if (confirm_box(true))
		{
			$this->set_enabled($campaign, $topic, false, 'LOG_DONATIONCAMPAIGNS_CAMPAIGN_DISABLED');

			return $this->message($this->language->lang(
				'DONATIONCAMPAIGNS_CAMPAIGN_SAVED_RETURN',
				'<a href="' . $this->topic_url($topic['topic_id']) . '">',
				'</a>'
			));
		}

		confirm_box(false, $this->language->lang('DONATIONCAMPAIGNS_CONFIRM_DISABLE'), '', 'confirm_body.html', $this->helper->route(
			'uflagmey_donationcampaigns_campaign_disable',
			array('campaign_id' => $campaign['campaign_id'])
		));

		// In production confirm_box(false) renders the dialog and exits; this is
		// the unreachable safety net that keeps the return type honest.
		return $this->empty_response();
	}

	/**
	 * Delete an EMPTY campaign. A non-empty one is refused here — disable it, or
	 * an administrator hard-deletes it in the ACP. The empty check runs before
	 * the confirmation, so a refused delete never even offers a dialog. A
	 * donations-only holder never reaches here: delete requires can_manage.
	 *
	 * @param int $campaign_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function delete($campaign_id)
	{
		$this->load_language();

		list($campaign, $topic) = $this->load_campaign_in_topic((int) $campaign_id);
		$this->require_manage($topic['forum_id']);

		if ($this->campaign_service->count_donations($campaign['campaign_id']) > 0)
		{
			return $this->message(
				$this->language->lang('DONATIONCAMPAIGNS_DELETE_NON_EMPTY_REFUSED')
				. '<br /><br />' . $this->return_link($topic['topic_id'])
			);
		}

		if (confirm_box(true))
		{
			$this->campaign_service->delete_campaign($campaign['campaign_id']);
			$this->log_campaign('LOG_DONATIONCAMPAIGNS_CAMPAIGN_DELETED', $topic['forum_id'], $topic['topic_id'], $campaign['campaign_title']);

			return $this->message($this->language->lang(
				'DONATIONCAMPAIGNS_CAMPAIGN_DELETED_RETURN',
				'<a href="' . $this->topic_url($topic['topic_id']) . '">',
				'</a>'
			));
		}

		confirm_box(false, $this->language->lang('DONATIONCAMPAIGNS_CONFIRM_DELETE_EMPTY'), '', 'confirm_body.html', $this->helper->route(
			'uflagmey_donationcampaigns_campaign_delete',
			array('campaign_id' => $campaign['campaign_id'])
		));

		return $this->empty_response();
	}

	/**
	 * Flip a campaign's enabled flag WITHOUT touching its description.
	 *
	 * Going through campaign_service::update_campaign() would decode and then
	 * re-encode the description with no browser in between, double-encoding it —
	 * the RC1 escaping-accumulation hazard. Enabling is a one-column change, so
	 * it is written directly through the repository; the service is untouched and
	 * the stored description is left exactly as it is.
	 *
	 * @param array $campaign
	 * @param array $topic
	 * @param bool $enabled
	 * @param string $log_key
	 * @return void
	 */
	protected function set_enabled(array $campaign, array $topic, $enabled, $log_key)
	{
		$this->campaigns->update($campaign['campaign_id'], array(
			'campaign_enabled'	=> $enabled ? 1 : 0,
			'campaign_updated'	=> time(),
		));

		$this->log_campaign($log_key, $topic['forum_id'], $topic['topic_id'], $campaign['campaign_title']);
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function empty_response()
	{
		return new \Symfony\Component\HttpFoundation\Response('');
	}

	// ------------------------------------------------------- the shared form

	/**
	 * Render, and on POST process, the create or edit form. $campaign null means
	 * create. The validation, money parsing and storage are campaign_service's,
	 * untouched; only the presentation and the auth surface are the frontend's.
	 *
	 * @param array $topic
	 * @param array|null $campaign
	 * @param int $forum_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function render_form(array $topic, $campaign, $forum_id)
	{
		$is_new = ($campaign === null);
		$exponent = (int) $this->config['donationcampaigns_currency_exponent'];

		$form_key = 'donationcampaigns_campaign';
		add_form_key($form_key);

		if ($is_new)
		{
			$values = array(
				'campaign_title'		=> '',
				'campaign_desc'			=> '',
				'target_amount'			=> '',
				'external_url'			=> '',
				'external_link_text'	=> $this->language->lang('DONATIONCAMPAIGNS_LINK_TEXT_DEFAULT'),
				'show_donor_names'		=> true,
				'show_donation_count'	=> true,
			);
		}
		else
		{
			$values = array(
				'campaign_title'		=> $campaign['campaign_title'],
				// Decoded back to source for the textarea; storage is re-encoded
				// on save. Escaping it again would accumulate entities.
				'campaign_desc'			=> $this->campaign_service->decode_description($campaign),
				// No grouping in the field, or the parser refuses it on save.
				'target_amount'			=> $this->formatter->format_for_input($campaign['target_amount'], $exponent),
				'external_url'			=> $campaign['external_url'],
				'external_link_text'	=> $campaign['external_link_text'],
				'show_donor_names'		=> $campaign['show_donor_names'],
				'show_donation_count'	=> $campaign['show_donation_count'],
			);
		}

		// Cancel is a submit button, like phpBB's own forms: it abandons the form
		// and returns to the topic. It writes nothing, so it is handled before —
		// and independently of — the form-key check.
		if ($this->request->is_set_post('cancel'))
		{
			return new RedirectResponse($this->topic_url($topic['topic_id']));
		}

		$errors = array();

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				throw $this->not_available();
			}

			$values = $this->submitted_values();

			$amount_error = '';
			$target = 0;

			try
			{
				$target = $this->formatter->parse($values['target_amount'], $exponent);
			}
			catch (donationcampaigns_exception $e)
			{
				$amount_error = $e->get_language_key();
			}

			// The topic is fixed: from the loaded topic on create and from the
			// stored campaign on edit, never from the body. The enabled flag is
			// not on this form — enable/disable are their own actions — so a
			// create defaults it on and an edit preserves the stored value.
			$input = array_merge($values, array(
				'topic_id'			=> $topic['topic_id'],
				'target_amount'		=> $target,
				'campaign_enabled'	=> $is_new ? true : (bool) $campaign['campaign_enabled'],
			));

			$errors = $this->campaign_service->validate($input, $is_new ? 0 : $campaign['campaign_id']);

			if ($amount_error !== '')
			{
				$errors = array_values(array_diff($errors, array('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE')));
				array_unshift($errors, $amount_error);
			}

			if (empty($errors))
			{
				try
				{
					if ($is_new)
					{
						$this->campaign_service->create_campaign($input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED';
					}
					else
					{
						$this->campaign_service->update_campaign($campaign['campaign_id'], $input);
						$log_key = 'LOG_DONATIONCAMPAIGNS_CAMPAIGN_EDITED';
					}

					$this->log_campaign($log_key, $forum_id, $topic['topic_id'], $input['campaign_title']);

					return $this->message($this->language->lang(
						'DONATIONCAMPAIGNS_CAMPAIGN_SAVED_RETURN',
						'<a href="' . $this->topic_url($topic['topic_id']) . '">',
						'</a>'
					));
				}
				catch (donationcampaigns_exception $e)
				{
					// The unique index rejected a campaign another request created
					// first. Report it as the duplicate it is.
					$errors = $e->get_parameters() ?: array($e->get_language_key());
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
			'S_DONATIONCAMPAIGNS_ADD'			=> $is_new,
			'S_DONATIONCAMPAIGNS_ERROR'			=> !empty($errors),
			'S_DONATIONCAMPAIGNS_SHOW_DONORS'	=> (bool) $values['show_donor_names'],
			'S_DONATIONCAMPAIGNS_SHOW_COUNT'	=> (bool) $values['show_donation_count'],

			'U_ACTION'	=> $is_new
				? $this->helper->route('uflagmey_donationcampaigns_campaign_create', array('topic_id' => $topic['topic_id']))
				: $this->helper->route('uflagmey_donationcampaigns_campaign_edit', array('campaign_id' => $campaign['campaign_id'])),

			// The topic is shown as trusted text and is never an input: read from
			// the loaded topic, escaped in the template like every other value.
			'DONATIONCAMPAIGNS_TOPIC_TITLE'		=> $topic['topic_title'],
			'U_DONATIONCAMPAIGNS_TOPIC'			=> $this->topic_url($topic['topic_id']),

			// Plain fields escaped in the template; the description is not,
			// because it is rendered into a textarea as already-encoded source.
			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'	=> $values['campaign_title'],
			'DONATIONCAMPAIGNS_DESC'			=> $values['campaign_desc'],
			'DONATIONCAMPAIGNS_TARGET_AMOUNT'	=> $values['target_amount'],
			'DONATIONCAMPAIGNS_CURRENCY_SYMBOL'	=> (string) $this->config['donationcampaigns_currency_symbol'],
			'DONATIONCAMPAIGNS_EXTERNAL_URL'	=> $values['external_url'],
			'DONATIONCAMPAIGNS_LINK_TEXT'		=> $values['external_link_text'],
		));

		return $this->helper->render('donationcampaigns_campaign_form.html', $this->language->lang(
			$is_new ? 'DONATIONCAMPAIGNS_ADD_CAMPAIGN' : 'DONATIONCAMPAIGNS_EDIT_CAMPAIGN'
		));
	}

	/**
	 * The campaign form as submitted, minus everything derived or fixed.
	 *
	 * No topic_id, no enabled flag, no collected total, no BBCode metadata: those
	 * are resolved, decided elsewhere, or derived. Plain text is read raw and
	 * escaped at output; the description is read escaped because it then goes
	 * through the BBCode storage encoder.
	 *
	 * @return array
	 */
	protected function submitted_values()
	{
		return array(
			'campaign_title'		=> $this->raw_text('campaign_title'),
			'campaign_desc'			=> $this->request->variable('campaign_desc', '', true),
			'target_amount'			=> $this->raw_text('target_amount'),
			'external_url'			=> $this->raw_text('external_url'),
			'external_link_text'	=> $this->raw_text('external_link_text'),
			'show_donor_names'		=> (bool) $this->request->variable('show_donor_names', 0),
			'show_donation_count'	=> (bool) $this->request->variable('show_donation_count', 0),
		);
	}

	// -------------------------------------------------------- landing render

	/**
	 * @param array $topic
	 * @param array $campaign
	 * @param int $forum_id
	 * @param bool $can_manage
	 * @param bool $can_donations
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function render_landing(array $topic, array $campaign, $forum_id, $can_manage, $can_donations)
	{
		$exponent = (int) $this->config['donationcampaigns_currency_exponent'];

		// Enable is a direct POST from a button on this page, so it carries a
		// form key. Disable and delete go through confirm_box, which supplies its
		// own key, so their affordances are plain links.
		add_form_key('donationcampaigns_toggle');
		$campaign_id = $campaign['campaign_id'];

		$this->template->assign_vars(array(
			// Only-authorised affordances. The template shows an action only when
			// its flag is set, and the action itself re-checks server-side, so a
			// hidden action is not merely invisible but unreachable.
			'S_DONATIONCAMPAIGNS_CAN_MANAGE'	=> (bool) $can_manage,
			'S_DONATIONCAMPAIGNS_CAN_DONATIONS'	=> (bool) $can_donations,
			'S_DONATIONCAMPAIGNS_ENABLED'		=> (bool) $campaign['campaign_enabled'],

			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE'	=> $campaign['campaign_title'],
			'DONATIONCAMPAIGNS_TOPIC_TITLE'		=> $topic['topic_title'],
			'DONATIONCAMPAIGNS_TARGET'			=> $this->formatter->format($campaign['target_amount'], $exponent),
			'DONATIONCAMPAIGNS_COLLECTED'		=> $this->formatter->format($campaign['collected_amount'], $exponent),
			'DONATIONCAMPAIGNS_COUNT'			=> $this->campaign_service->count_donations($campaign['campaign_id']),

			// The topic-title link is a plain <a>. The Back button is a GET form
			// submit (classic phpBB button styling needs an <input>), so its target
			// is split into a query-free action and a hidden topic id — a GET form
			// discards a query string on its action.
			'DONATIONCAMPAIGNS_TOPIC_ID'	=> (int) $topic['topic_id'],
			'U_VIEWTOPIC'				=> $this->path_helper->get_web_root_path() . 'viewtopic.' . $this->php_ext(),
			'U_DONATIONCAMPAIGNS_TOPIC'	=> $this->topic_url($topic['topic_id']),
			'U_EDIT'					=> $this->helper->route('uflagmey_donationcampaigns_campaign_edit', array('campaign_id' => $campaign_id)),
			'U_ENABLE'					=> $this->helper->route('uflagmey_donationcampaigns_campaign_enable', array('campaign_id' => $campaign_id)),
			'U_DISABLE'					=> $this->helper->route('uflagmey_donationcampaigns_campaign_disable', array('campaign_id' => $campaign_id)),
			'U_DELETE'					=> $this->helper->route('uflagmey_donationcampaigns_campaign_delete', array('campaign_id' => $campaign_id)),
			'U_ADD_DONATION'			=> $this->helper->route('uflagmey_donationcampaigns_donation_add', array('campaign_id' => $campaign_id)),
		));

		// The donation ledger is shown, and its per-row edit/delete offered, only
		// to a donations holder. Each donation action re-checks the permission
		// server-side, so these rows are an affordance, not the authority.
		if ($can_donations)
		{
			$this->assign_donation_ledger($campaign, $exponent);
		}

		return $this->helper->render('donationcampaigns_manage.html', $this->language->lang('DONATIONCAMPAIGNS_MANAGE_CAMPAIGN'));
	}

	/**
	 * The campaign's confirmed donations, newest first, each with a link to its
	 * frontend edit and delete routes. Display only: donor names are labelled
	 * ("Anonymous" when blank) and escaped in the template like every other
	 * value, and the amount is formatted from stored minor units.
	 *
	 * @param array $campaign
	 * @param int $exponent
	 * @return void
	 */
	protected function assign_donation_ledger(array $campaign, $exponent)
	{
		foreach ($this->donations->find_by_campaign($campaign['campaign_id']) as $donation)
		{
			$donation_id = $donation['donation_id'];

			$this->template->assign_block_vars('donationcampaigns_donation', array(
				'AMOUNT'		=> $this->formatter->format($donation['donation_amount'], $exponent),
				'DONOR_NAME'	=> $this->donor_label($donation['donor_name']),
				'S_PUBLIC'		=> (bool) $donation['donation_public'],

				'U_EDIT'		=> $this->helper->route('uflagmey_donationcampaigns_donation_edit', array('donation_id' => $donation_id)),
				'U_DELETE'		=> $this->helper->route('uflagmey_donationcampaigns_donation_delete', array('donation_id' => $donation_id)),
			));
		}
	}

	/**
	 * A donation with no donor name is a real, countable donation from someone
	 * who asked not to be named; it is labelled rather than left blank.
	 *
	 * @param string $donor_name
	 * @return string
	 */
	protected function donor_label($donor_name)
	{
		$donor_name = trim((string) $donor_name);

		return ($donor_name !== '') ? $donor_name : $this->language->lang('DONATIONCAMPAIGNS_ANONYMOUS');
	}

	// -------------------------------------------------- the authorization chain

	/**
	 * @param int $topic_id
	 * @return array{topic_id:int,forum_id:int,topic_title:string}
	 */
	protected function load_topic($topic_id)
	{
		$topic = $this->topics->find($topic_id);

		if ($topic === null)
		{
			throw $this->not_available();
		}

		return $topic;
	}

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
	 * @param int $forum_id
	 * @return void
	 */
	protected function require_manage($forum_id)
	{
		if (!$this->access->can_manage($forum_id))
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
		// helper::message translates its first argument; an already-composed
		// string is returned unchanged by the language service, which is what we
		// want for a message built from safe, integer-derived links.
		return $this->helper->message($html);
	}

	/**
	 * @param int $topic_id
	 * @return string
	 */
	protected function return_link($topic_id)
	{
		return $this->language->lang(
			'DONATIONCAMPAIGNS_RETURN_TO_TOPIC',
			'<a href="' . $this->topic_url($topic_id) . '">',
			'</a>'
		);
	}

	/**
	 * A campaign action in the MODERATOR log, scoped to the forum and topic.
	 *
	 * forum_id and topic_id are keyed so phpBB files the entry against them; the
	 * title is the message argument, escaped because the log viewer renders raw.
	 *
	 * @param string $log_key
	 * @param int $forum_id
	 * @param int $topic_id
	 * @param string $title
	 * @return void
	 */
	protected function log_campaign($log_key, $forum_id, $topic_id, $title)
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
				$this->escape_for_message($title),
			)
		);
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
	 * @return void
	 */
	protected function load_language()
	{
		// common carries the shared errors and the frontend workflow strings;
		// info_acp_donationcampaigns carries the campaign form's field labels,
		// reused rather than duplicated.
		$this->language->add_lang(array('common', 'info_acp_donationcampaigns'), 'uflagmey/donationcampaigns');
	}

	/**
	 * The public URL of a topic. Built from the integer alone, so there is
	 * nothing here to redirect.
	 *
	 * @param int $topic_id
	 * @return string
	 */
	protected function topic_url($topic_id)
	{
		// Built from the board's web root, not relative to the current page. This
		// controller is served under app.php/donationcampaigns/..., where a bare
		// "viewtopic.php" would resolve against that path and 404;
		// get_web_root_path() returns the correct prefix back to the board root.
		return append_sid($this->path_helper->get_web_root_path() . 'viewtopic.' . $this->php_ext(), 't=' . (int) $topic_id);
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
