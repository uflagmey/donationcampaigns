<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

use uflagmey\donationcampaigns\event\posting_listener;
use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\service\access;
use uflagmey\donationcampaigns\service\campaign_form;
use uflagmey\donationcampaigns\service\campaign_service;
use uflagmey\donationcampaigns\service\currency_formatter;
use uflagmey\donationcampaigns\tests\acp\recording_log;
use uflagmey\donationcampaigns\tests\controller\recording_helper;
use uflagmey\donationcampaigns\tests\repository\campaign_list_test_case;
use uflagmey\donationcampaigns\tests\service\fake_description_formatter;
use uflagmey\donationcampaigns\tests\unit\forum_scoped_auth;

/**
 * Creating a campaign from the posting form (ADR-019, Block D).
 *
 * Fixture: campaign_list_test_case. Forum A (2) is where the actor may manage
 * campaigns; forum B (3) is not. Topic 70 stands for the topic core has just
 * created when the create step runs.
 */
class posting_listener_test extends campaign_list_test_case
{
	const NEW_TOPIC = 70;

	/** @var recording_template */
	protected $template;

	/** @var recording_log */
	protected $log;

	/** @var recording_helper */
	protected $helper;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var array */
	protected $grants;

	/** @var campaign_service */
	protected $campaign_service;

	public function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;
		require_once $phpbb_root_path . 'includes/functions.php';
		require_once $phpbb_root_path . '../tests/mock/request.php';

		$loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$loader->set_extension_manager(new \phpbb_mock_extension_manager($phpbb_root_path, array(
			'uflagmey/donationcampaigns' => array(
				'ext_name' => 'uflagmey/donationcampaigns', 'ext_active' => true,
				'ext_path' => 'ext/uflagmey/donationcampaigns/',
			),
		)));
		$this->language = new \phpbb\language\language($loader);

		$this->config = new \phpbb\config\config(array(
			'donationcampaigns_currency_code'		=> 'EUR',
			'donationcampaigns_currency_symbol'		=> '€',
			'donationcampaigns_currency_exponent'	=> 2,
		));

		$this->campaign_service = new campaign_service(
			$this->db,
			new campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			new donation_repository($this->db, 'phpbb_ufdc_donations'),
			new topic_repository($this->db, 'phpbb_topics'),
			new fake_description_formatter()
		);

		// The actor manages campaigns in forum A and reads A and B.
		$this->grants = array('f_read' => array(self::FORUM_A, self::FORUM_B), 'f_donationcampaigns_manage' => array(self::FORUM_A));
	}

	/**
	 * @param array $post The posted form fields
	 * @return posting_listener
	 */
	protected function listener(array $post = array())
	{
		$this->template = new recording_template();
		$this->log = new recording_log();
		$this->helper = new recording_helper();

		$auth = new forum_scoped_auth($this->grants);
		$request = new \phpbb_mock_request(array(), $post);
		$formatter = new currency_formatter($this->language, $this->config);

		$user = new \phpbb_mock_user();
		$user->data = array('user_id' => 61);
		$user->ip = '127.0.0.1';

		return new posting_listener(
			new access($auth),
			$auth,
			$this->campaign_service,
			new campaign_form($request, $formatter, $this->config, $this->language),
			$this->config,
			$this->template,
			$this->language,
			$request,
			$this->log,
			$user,
			$this->helper
		);
	}

	/**
	 * The posted panel, with every field prefixed as the posting form sends it.
	 *
	 * @param array $fields Unprefixed overrides
	 * @param bool $attach The checkbox
	 * @return array
	 */
	protected function panel(array $fields = array(), $attach = true)
	{
		$fields += array(
			'campaign_title'		=> 'Server fund',
			'campaign_desc'			=> 'Help us',
			'target_amount'			=> '250.00',
			'external_url'			=> '',
			'external_link_text'	=> 'How to donate',
			'show_donor_names'		=> '1',
			'show_donation_count'	=> '1',
			'show_donation_date'	=> '1',
		);

		$post = array('donationcampaigns_panel' => '1');
		foreach ($fields as $key => $value)
		{
			$post['donationcampaigns_' . $key] = $value;
		}

		if ($attach)
		{
			$post['donationcampaigns_attach'] = '1';
		}

		return $post;
	}

	protected function template_vars_event($mode = 'post', $forum_id = self::FORUM_A)
	{
		return new \phpbb\event\data(array('mode' => $mode, 'forum_id' => $forum_id, 'post_data' => array(), 'error' => array()));
	}

	// ======================================================== display (Task 5)

	public function test_it_subscribes_to_the_posting_events()
	{
		$this->assertSame(array(
			'core.posting_modify_template_vars'		=> 'assign_panel',
			'core.posting_modify_submission_errors'	=> 'validate_panel',
			'core.posting_modify_submit_post_after'	=> 'create_campaign',
		), posting_listener::getSubscribedEvents());
	}

	/**
	 * @return array
	 */
	public function other_modes()
	{
		return array(array('reply'), array('quote'), array('edit'), array('delete'), array('bump'), array('smilies'), array(''));
	}

	/**
	 * D1: new topics only. Editing stays with the management landing.
	 *
	 * @dataProvider other_modes
	 */
	public function test_no_panel_outside_a_new_topic($mode)
	{
		$this->listener()->assign_panel($this->template_vars_event($mode));

		$this->assertSame(array(), $this->template->vars);
	}

	public function test_no_panel_without_the_permission_in_this_forum()
	{
		$this->listener()->assign_panel($this->template_vars_event('post', self::FORUM_B));

		$this->assertSame(array(), $this->template->vars);
	}

	public function test_no_panel_for_a_plain_member()
	{
		$this->grants = array('f_read' => true);

		$this->listener()->assign_panel($this->template_vars_event());

		$this->assertSame(array(), $this->template->vars);
	}

	public function test_the_admin_override_shows_the_panel()
	{
		$this->grants = array('f_read' => true, 'a_donationcampaigns' => true);

		$this->listener()->assign_panel($this->template_vars_event('post', self::FORUM_B));

		$this->assertTrue($this->template->vars['S_DONATIONCAMPAIGNS_POSTING']);
	}

	public function test_first_display_shows_the_new_campaign_defaults_unticked()
	{
		$this->listener()->assign_panel($this->template_vars_event());
		$vars = $this->template->vars;

		$this->assertTrue($vars['S_DONATIONCAMPAIGNS_POSTING']);
		$this->assertFalse($vars['S_DONATIONCAMPAIGNS_ATTACH']);
		$this->assertSame('', $vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertSame('', $vars['DONATIONCAMPAIGNS_TARGET_AMOUNT']);
		$this->assertSame('How to donate', $vars['DONATIONCAMPAIGNS_LINK_TEXT']);
		$this->assertTrue($vars['S_DONATIONCAMPAIGNS_SHOW_DONORS']);
		$this->assertTrue($vars['S_DONATIONCAMPAIGNS_SHOW_COUNT']);
		$this->assertTrue($vars['S_DONATIONCAMPAIGNS_SHOW_DATE']);
		$this->assertTrue($vars['S_DONATIONCAMPAIGNS_TITLE_FROM_SUBJECT']);
		$this->assertSame('€', $vars['DONATIONCAMPAIGNS_CURRENCY_SYMBOL']);
		// No topic exists yet, so the shared fields show no topic row.
		$this->assertArrayNotHasKey('DONATIONCAMPAIGNS_TOPIC_TITLE', $vars);
	}

	/**
	 * Preview, an error or an attachment refresh redisplays the form: what
	 * was entered comes back, including the checkbox and unticked flags.
	 */
	public function test_a_redisplay_keeps_what_was_entered()
	{
		$post = $this->panel(array('campaign_title' => 'Kosten & "Miete" <2026>', 'target_amount' => '12,5', 'show_donation_count' => ''));
		unset($post['donationcampaigns_show_donation_count']);

		$this->listener($post)->assign_panel($this->template_vars_event());
		$vars = $this->template->vars;

		$this->assertTrue($vars['S_DONATIONCAMPAIGNS_ATTACH']);
		// Raw here; the template escapes it once.
		$this->assertSame('Kosten & "Miete" <2026>', $vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
		$this->assertSame('12,5', $vars['DONATIONCAMPAIGNS_TARGET_AMOUNT']);
		$this->assertFalse($vars['S_DONATIONCAMPAIGNS_SHOW_COUNT']);
		$this->assertTrue($vars['S_DONATIONCAMPAIGNS_SHOW_DONORS']);
	}

	public function test_an_unticked_redisplay_keeps_the_values_but_not_the_tick()
	{
		$this->listener($this->panel(array('campaign_title' => 'Kept'), false))->assign_panel($this->template_vars_event());

		$this->assertFalse($this->template->vars['S_DONATIONCAMPAIGNS_ATTACH']);
		$this->assertSame('Kept', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	/**
	 * WD1: no prefill. An empty title stays empty on redisplay even when a
	 * subject exists — the subject is only used at submit.
	 */
	public function test_an_empty_title_is_not_prefilled_from_the_subject()
	{
		$event = $this->template_vars_event();
		$event['post_data'] = array('post_subject' => 'A subject');

		$this->listener($this->panel(array('campaign_title' => '')))->assign_panel($event);

		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	/**
	 * Fields posted WITHOUT the prefix belong to someone else (WD4).
	 */
	public function test_unprefixed_fields_are_ignored()
	{
		$this->listener(array('donationcampaigns_panel' => '1', 'campaign_title' => 'Not ours'))->assign_panel($this->template_vars_event());

		$this->assertSame('', $this->template->vars['DONATIONCAMPAIGNS_CAMPAIGN_TITLE']);
	}

	public function test_the_rendered_panel_escapes_the_entered_title_once()
	{
		$this->listener($this->panel(array('campaign_title' => 'Kosten & "Miete" <2026>')))->assign_panel($this->template_vars_event());

		$html = \uflagmey\donationcampaigns\tests\template_renderer::render($this->panel_template(), $this->template->vars);

		$this->assertStringContainsString('value="Kosten &amp; &quot;Miete&quot; &lt;2026&gt;"', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html);
	}

	public function test_every_panel_field_carries_the_prefix()
	{
		// The panel renders only on a page the listener enabled it for.
		$html = \uflagmey\donationcampaigns\tests\template_renderer::render($this->panel_template(), array('S_DONATIONCAMPAIGNS_POSTING' => true));

		preg_match_all('/name="([a-z_]+)"/', $html, $names);

		$this->assertNotEmpty($names[1]);
		foreach ($names[1] as $name)
		{
			$this->assertStringStartsWith('donationcampaigns_', $name);
		}
		$this->assertContains('donationcampaigns_attach', $names[1]);
		$this->assertContains('donationcampaigns_panel', $names[1]);
		$this->assertContains('donationcampaigns_target_amount', $names[1]);
	}

	// ===================================================== validation (Task 6)

	/**
	 * core.posting_modify_submission_errors as posting.php dispatches it.
	 */
	protected function submission_event($mode = 'post', $forum_id = self::FORUM_A, $submit = true, $subject = 'A new topic', array $error = array())
	{
		return new \phpbb\event\data(array(
			'post_data'	=> array('post_subject' => utf8_htmlspecialchars($subject)),
			'poll'		=> array(),
			'mode'		=> $mode,
			'post_id'	=> 0,
			'topic_id'	=> 0,
			'forum_id'	=> $forum_id,
			'submit'	=> $submit,
			'error'		=> $error,
		));
	}

	protected function validate(array $post, \phpbb\event\data $event = null)
	{
		$event = $event ?: $this->submission_event();
		$this->listener($post)->validate_panel($event);

		return $event['error'];
	}

	public function test_unticked_nothing_is_validated_whatever_the_fields_hold()
	{
		$this->assertSame(array(), $this->validate($this->panel(array('target_amount' => 'garbage', 'external_url' => 'javascript:x'), false)));
	}

	public function test_valid_fields_add_no_error()
	{
		$this->assertSame(array(), $this->validate($this->panel()));
	}

	/**
	 * Errors go into core's error array as SENTENCES (posting.php:1408), after
	 * whatever core or another extension already put there.
	 */
	public function test_an_invalid_target_blocks_the_post_with_a_translated_message()
	{
		$event = $this->submission_event('post', self::FORUM_A, true, 'A new topic', array('Core error first'));

		$errors = $this->validate($this->panel(array('target_amount' => '0')), $event);

		$this->assertSame('Core error first', $errors[0]);
		$this->assertCount(2, $errors);
		$this->assertStringNotContainsString('DONATIONCAMPAIGNS_', $errors[1]);
		$this->assertSame($this->language->lang('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE'), $errors[1]);
	}

	public function test_a_grouped_amount_reports_the_formatter_error_first()
	{
		$errors = $this->validate($this->panel(array('target_amount' => '1,000.00', 'external_url' => 'javascript:x')));

		$this->assertNotSame($this->language->lang('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE'), $errors[0]);
		$this->assertNotContains($this->language->lang('DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE'), $errors);
		$this->assertContains($this->language->lang('DONATIONCAMPAIGNS_ERROR_URL_INVALID'), $errors);
	}

	/**
	 * A preview validates like a poll does: the errors suppress the preview.
	 */
	public function test_a_preview_validates()
	{
		$post = $this->panel(array('target_amount' => '0')) + array('preview' => 'Preview');

		$this->assertNotEmpty($this->validate($post, $this->submission_event('post', self::FORUM_A, false)));
	}

	/**
	 * An attachment refresh or a draft action is neither submit nor preview.
	 */
	public function test_a_refresh_does_not_validate()
	{
		$post = $this->panel(array('target_amount' => '0')) + array('add_file' => 'Add the file');

		$this->assertSame(array(), $this->validate($post, $this->submission_event('post', self::FORUM_A, false)));
	}

	/**
	 * WD1: an empty campaign title becomes the topic title, so it is not an
	 * error while the topic has a subject.
	 */
	public function test_an_empty_title_is_fine_when_the_topic_has_a_subject()
	{
		$this->assertSame(array(), $this->validate($this->panel(array('campaign_title' => '   '))));
	}

	public function test_an_empty_title_without_a_subject_is_reported()
	{
		$errors = $this->validate($this->panel(array('campaign_title' => '')), $this->submission_event('post', self::FORUM_A, true, ''));

		$this->assertContains($this->language->lang('DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED'), $errors);
	}

	public function test_a_title_from_a_long_subject_is_checked_like_a_typed_one()
	{
		$this->assertSame(array(), $this->validate($this->panel(array('campaign_title' => '')), $this->submission_event('post', self::FORUM_A, true, 'Kosten & "Miete" <2026>')));
	}

	public function test_injected_fields_without_the_permission_are_ignored()
	{
		$this->assertSame(array(), $this->validate($this->panel(array('target_amount' => '0')), $this->submission_event('post', self::FORUM_B)));
	}

	/**
	 * Fields injected into a reply, a quote or an edit are never validated.
	 *
	 * @dataProvider other_modes
	 */
	public function test_injected_fields_outside_a_new_topic_are_ignored($mode)
	{
		$this->assertSame(array(), $this->validate($this->panel(array('target_amount' => '0')), $this->submission_event($mode)));
	}

	// ======================================================= creation (Task 7)

	/**
	 * core.posting_modify_submit_post_after as posting.php:1603 dispatches it
	 * for a NEW topic: submit_post() has filled data['topic_id'], while the
	 * event's own topic_id is still 0.
	 */
	protected function created_event($mode = 'post', $forum_id = self::FORUM_A, $subject = 'A new topic', array $data = array())
	{
		return new \phpbb\event\data(array(
			'post_data'			=> array('post_subject' => utf8_htmlspecialchars($subject)),
			'poll'				=> array(),
			'data'				=> $data + array('topic_id' => self::NEW_TOPIC, 'forum_id' => $forum_id, 'post_id' => 700),
			'mode'				=> $mode,
			'post_id'			=> 0,
			'topic_id'			=> 0,
			'forum_id'			=> $forum_id,
			'post_author_name'	=> '',
			'update_message'	=> true,
			'update_subject'	=> true,
			'redirect_url'		=> './viewtopic.php?t=' . self::NEW_TOPIC,
		));
	}

	protected function create(array $post, \phpbb\event\data $event = null)
	{
		$this->insert_topic(self::NEW_TOPIC, $event ? (int) $event['forum_id'] : self::FORUM_A);

		$event = $event ?: $this->created_event();
		$this->listener($post)->create_campaign($event);

		return $event;
	}

	protected function stored()
	{
		return $this->campaign_service->get_campaign_by_topic(self::NEW_TOPIC);
	}

	public function test_the_campaign_is_created_for_the_new_topic()
	{
		$this->grants['f_noapprove'] = array(self::FORUM_A);

		$event = $this->create($this->panel(array('external_url' => 'https://example.org/donate', 'show_donation_date' => '')));
		unset($event);
		$campaign = $this->stored();

		$this->assertNotNull($campaign, 'No campaign for data[topic_id]');
		$this->assertSame('Server fund', $campaign['campaign_title']);
		$this->assertSame(25000, $campaign['target_amount']);
		$this->assertSame(0, $campaign['collected_amount']);
		$this->assertTrue($campaign['campaign_enabled']);
		$this->assertTrue($campaign['show_donor_names']);
		$this->assertTrue($campaign['show_donation_count']);
		$this->assertSame('https://example.org/donate', $campaign['external_url']);
		$this->assertSame('How to donate', $campaign['external_link_text']);
	}

	public function test_an_unticked_flag_is_stored_off()
	{
		$post = $this->panel();
		unset($post['donationcampaigns_show_donation_date']);

		$this->create($post);

		$this->assertFalse($this->stored()['show_donation_date']);
	}

	/**
	 * D8: the same moderator-log entry as a frontend create, with the forum
	 * and the new topic, and the title escaped for the log viewer.
	 */
	public function test_creation_is_logged_like_a_frontend_create()
	{
		$this->create($this->panel(array('campaign_title' => 'A & B')));

		$this->assertSame(array('LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED'), $this->log->operations);
		list($mode, $user_id, , , , $data) = $this->log->entries[0];
		$this->assertSame('mod', $mode);
		$this->assertSame(61, $user_id);
		$this->assertSame(self::FORUM_A, $data['forum_id']);
		$this->assertSame(self::NEW_TOPIC, $data['topic_id']);
		$this->assertSame('A &amp; B', $data[0]);
	}

	/**
	 * WD1 + the review's point 1: an empty title takes the subject, decoded
	 * from core's escaped form, so the stored title is raw and the box
	 * escapes it exactly once.
	 */
	public function test_an_empty_title_takes_the_decoded_subject()
	{
		$subject = 'Kosten & "Miete" <2026> \'x\'';

		$this->create($this->panel(array('campaign_title' => '')), $this->created_event('post', self::FORUM_A, $subject));
		$title = $this->stored()['campaign_title'];

		$this->assertSame($subject, $title);
		$this->assertStringNotContainsString('&amp;', $title);
		$this->assertStringNotContainsString('&quot;', $title);

		$box = \uflagmey\donationcampaigns\tests\template_renderer::render(
			file_get_contents(dirname(dirname(__DIR__)) . '/styles/prosilver/template/event/viewtopic_body_poll_before.html'),
			// The box renders only for a topic the listener found a campaign for.
			array('S_DONATIONCAMPAIGNS_SHOW' => true, 'DONATIONCAMPAIGNS_CAMPAIGN_TITLE' => $title)
		);
		$this->assertStringContainsString('Kosten &amp; &quot;Miete&quot; &lt;2026&gt; &#039;x&#039;', $box);
		$this->assertStringNotContainsString('&amp;amp;', $box);
	}

	public function test_a_typed_title_wins_over_the_subject()
	{
		$this->create($this->panel(array('campaign_title' => 'Typed')), $this->created_event('post', self::FORUM_A, 'Subject'));

		$this->assertSame('Typed', $this->stored()['campaign_title']);
	}

	public function test_unticked_no_campaign_is_created()
	{
		$this->create($this->panel(array(), false));

		$this->assertNull($this->stored());
		$this->assertSame(array(), $this->log->operations);
	}

	public function test_injected_fields_without_the_permission_create_nothing()
	{
		$this->create($this->panel(), $this->created_event('post', self::FORUM_B));

		$this->assertNull($this->stored());
	}

	/**
	 * Fields injected into a reply, a quote or an edit create nothing.
	 *
	 * @dataProvider other_modes
	 */
	public function test_injected_fields_outside_a_new_topic_create_nothing($mode)
	{
		$this->create($this->panel(), $this->created_event($mode));

		$this->assertNull($this->stored());
		$this->assertSame(array(), $this->log->operations);
	}

	/**
	 * D7 / Q3: a topic that waits for approval is still created by
	 * submit_post(), so the campaign is created too; it becomes visible with
	 * the topic.
	 */
	public function test_a_topic_waiting_for_approval_gets_its_campaign()
	{
		$event = $this->create($this->panel(), $this->created_event('post', self::FORUM_A, 'Queued', array('force_approved_state' => false)));

		$this->assertNotNull($this->stored());
		$this->assertSame('./viewtopic.php?t=' . self::NEW_TOPIC, $event['redirect_url']);
	}

	/**
	 * Q4: core's posting lock stops a resubmitted form before submit_post(),
	 * so this event cannot run twice for one form. Should it ever run twice
	 * for one topic, the campaign exists: nothing more happens, no error.
	 */
	public function test_a_second_run_for_the_same_topic_creates_nothing_more()
	{
		$event = $this->create($this->panel());
		$this->listener($this->panel())->create_campaign($this->created_event());

		$this->assertSame(1, $this->campaign_service->count_campaigns() - 8);
		$this->assertSame(array(), $this->log->operations, 'The second run logged something');
		$this->assertSame('./viewtopic.php?t=' . self::NEW_TOPIC, $event['redirect_url']);
	}

	/**
	 * WD2: the post is saved, the insert fails. No exception escapes, a
	 * critical log entry records it, and an approved topic sends the poster
	 * to the management landing, which offers the create form.
	 */
	public function test_a_failed_insert_on_an_approved_topic_logs_and_redirects_to_the_landing()
	{
		$this->grants['f_noapprove'] = array(self::FORUM_A);
		$this->use_failing_insert();

		$event = $this->create($this->panel());

		$this->assertNull($this->stored());
		$this->assertSame(array('LOG_DONATIONCAMPAIGNS_POSTING_CREATE_FAILED'), $this->log->operations);
		$this->assertSame('critical', $this->log->entries[0][0]);
		$this->assertSame('uflagmey_donationcampaigns_manage?topic_id=' . self::NEW_TOPIC, $event['redirect_url']);
	}

	/**
	 * WD2: when the topic waits for approval, core shows its own fixed
	 * message after this event; only the log entry is written.
	 */
	public function test_a_failed_insert_on_a_queued_topic_only_logs()
	{
		$this->use_failing_insert();

		$event = $this->create($this->panel(), $this->created_event('post', self::FORUM_A, 'Queued'));

		$this->assertSame(array('LOG_DONATIONCAMPAIGNS_POSTING_CREATE_FAILED'), $this->log->operations);
		$this->assertSame('./viewtopic.php?t=' . self::NEW_TOPIC, $event['redirect_url']);
	}

	protected function use_failing_insert()
	{
		$this->campaign_service = new campaign_service(
			$this->db,
			new \uflagmey\donationcampaigns\tests\service\failing_insert_campaign_repository($this->db, 'phpbb_ufdc_campaigns'),
			new donation_repository($this->db, 'phpbb_ufdc_donations'),
			new topic_repository($this->db, 'phpbb_topics'),
			new fake_description_formatter()
		);
	}

	// ---------------------------------------------------------------- markup

	protected function panel_template()
	{
		return file_get_contents(dirname(dirname(__DIR__)) . '/styles/prosilver/template/event/posting_layout_include_panel_body.html');
	}

	protected function tab_template()
	{
		return file_get_contents(dirname(dirname(__DIR__)) . '/styles/prosilver/template/event/posting_editor_add_panel_tab.html');
	}

	/**
	 * The tab must look exactly like core's poll tab, or forum_fn.js does
	 * not switch it (posting_editor.html:130-131).
	 */
	public function test_the_tab_follows_the_poll_tab_markup()
	{
		// Only on a page the listener enabled the panel for.
		$this->assertSame('', trim(\uflagmey\donationcampaigns\tests\template_renderer::render($this->tab_template(), array())));

		$this->assertSame(
			'<li id="donationcampaigns-panel-tab" class="tab"><a href="#tabs" data-subpanel="donationcampaigns-panel" role="tab" aria-controls="donationcampaigns-panel">DONATIONCAMPAIGNS_POSTING_TAB</a></li>',
			trim(\uflagmey\donationcampaigns\tests\template_renderer::render($this->tab_template(), array('S_DONATIONCAMPAIGNS_POSTING' => true)))
		);
	}

	/**
	 * The panel mirrors posting_poll_body.html:1-2, and nothing hides it, so
	 * without JavaScript it is simply shown below the others.
	 */
	public function test_the_panel_follows_the_poll_panel_markup_and_needs_no_javascript()
	{
		$panel = $this->panel_template();

		// Only on a page the listener enabled it for, and then the whole panel.
		$this->assertSame('', trim(\uflagmey\donationcampaigns\tests\template_renderer::render($panel, array())));

		$html = \uflagmey\donationcampaigns\tests\template_renderer::render($panel, array('S_DONATIONCAMPAIGNS_POSTING' => true));
		$this->assertStringContainsString('<div class="panel bg3" id="donationcampaigns-panel">', $html);
		$this->assertStringContainsString('<div class="inner">', $html);
		$this->assertStringContainsString('name="donationcampaigns_target_amount"', $html, 'The shared fields are not in the panel');
		$this->assertStringContainsString("{% include '@uflagmey_donationcampaigns/donationcampaigns_campaign_fields.html' with {'prefix': 'donationcampaigns_'} %}", $panel);

		$this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $panel, 'ADR-013: no inline CSS, and nothing may hide the panel');
		$this->assertStringNotContainsStringIgnoringCase('<script', $panel);
		$this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $panel);
		// Our own error box would duplicate core's: errors go to core's.
		$this->assertStringNotContainsString('errorbox', $panel);
	}

	/**
	 * REGRESSION (beta4 F1, a defect since beta3). phpBB's lexer rewrites a
	 * template variable in braces even inside an HTML comment. A developer
	 * comment in the shared campaign fields named core's MESSAGE variable in
	 * braces, so every preview of the posting form printed the post text a
	 * second time into the page source, inside that comment.
	 */
	public function test_the_panel_never_prints_the_post_text()
	{
		$html = \uflagmey\donationcampaigns\tests\template_renderer::render($this->panel_template(), array(
			'S_DONATIONCAMPAIGNS_POSTING'	=> true,
			'MESSAGE'						=> 'SECRET-F1',
		));

		$this->assertStringContainsString('id="donationcampaigns-panel"', $html);
		$this->assertStringNotContainsString('SECRET-F1', $html);
	}
}
