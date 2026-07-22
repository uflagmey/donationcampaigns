<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * The ACP settings template, checked as a file.
 *
 * A missing form token, an unlabelled field or a language key with no string
 * all fail silently at runtime, so they are asserted here.
 */
class settings_template_test extends \phpbb_test_case
{
	/** @var string */
	protected $package;

	/** @var string */
	protected $file;

	public function setUp(): void
	{
		parent::setUp();

		$this->package = dirname(dirname(__DIR__));
		$this->file = $this->package . '/adm/style/acp_donationcampaigns_settings.html';
	}

	/**
	 * @return string
	 */
	protected function template()
	{
		return file_get_contents($this->file);
	}

	public function test_the_template_exists_where_phpbb_looks_for_it()
	{
		$this->assertFileExists($this->file);
	}

	/**
	 * The placeholder shipped in task 6 was explicitly temporary. Leaving it
	 * behind would mean a dead file referencing a language key that no longer
	 * needs to exist.
	 */
	public function test_the_task_6_placeholder_is_gone()
	{
		$this->assertFileDoesNotExist($this->package . '/adm/style/acp_donationcampaigns_placeholder.html');
	}

	/**
	 * Without S_FORM_TOKEN the form posts no token, check_form_key() always
	 * fails, and the page becomes impossible to submit.
	 */
	public function test_the_form_carries_a_csrf_token()
	{
		$this->assertStringContainsString('{S_FORM_TOKEN}', $this->template());
	}

	public function test_the_form_posts_to_the_module_action()
	{
		$template = $this->template();

		$this->assertStringContainsString('method="post"', $template);
		$this->assertStringContainsString('action="{U_ACTION}"', $template);
	}

	public function test_there_is_no_inline_css()
	{
		$this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $this->template());
	}

	public function test_there_is_no_javascript()
	{
		$template = $this->template();

		$this->assertStringNotContainsStringIgnoringCase('<script', $template);
		$this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $template);
		$this->assertStringNotContainsStringIgnoringCase('javascript:', $template);
	}

	/**
	 * Every input is reachable and announced. An unlabelled field is unusable
	 * with a screen reader and merely unclear with one.
	 */
	public function test_every_input_has_a_label()
	{
		$template = $this->template();

		preg_match_all('/<input[^>]*\sid="([^"]+)"/', $template, $inputs);

		$skip = array('submit', 'reset');

		foreach ($inputs[1] as $id)
		{
			if (in_array($id, $skip, true))
			{
				continue;
			}

			$this->assertStringContainsString('for="' . $id . '"', $template, "Input {$id} has no label");
		}
	}

	public function test_the_bounds_are_declared_on_the_numeric_inputs()
	{
		$template = $this->template();

		$this->assertStringContainsString('min="0" max="4"', $template, 'The exponent bounds are not on the input');
		$this->assertStringContainsString('min="1" max="500"', $template, 'The donor limit bounds are not on the input');
	}

	/**
	 * Every visible string comes from a language file. A hard-coded sentence
	 * cannot be translated and will not be, because nobody will find it.
	 */
	public function test_every_language_key_used_has_an_english_string()
	{
		preg_match_all('/\{L_([A-Z0-9_]+)\}/', $this->template(), $matches);

		$this->assertNotEmpty($matches[1]);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		// Supplied by phpBB itself.
		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'WARNING', 'BACK', 'ACP_NO_ITEMS');

		foreach (array_unique($matches[1]) as $key)
		{
			if (in_array($key, $core_keys, true))
			{
				continue;
			}

			$this->assertArrayHasKey($key, $lang, "No English string for L_{$key}");
		}
	}

	/**
	 * The banner is a one-line prompt; it says WHY it appeared and no more.
	 * The detail an administrator must actually understand before confirming
	 * lives on the confirmation checkbox, which is the point of no return.
	 */
	public function test_the_banner_says_why_it_appeared()
	{
		$lang = array();
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$banner = $lang['DONATIONCAMPAIGNS_SETTINGS_EXPONENT_WARNING'];

		$this->assertStringContainsString('displayed', $banner);
		$this->assertLessThan(140, strlen($banner), 'The banner has grown back into an essay');
	}

	public function test_the_confirmation_explains_what_actually_happens()
	{
		$lang = array();
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$explain = $lang['DONATIONCAMPAIGNS_SETTINGS_EXPONENT_CONFIRM_EXPLAIN'];

		// The three things to understand before ticking it.
		$this->assertStringContainsString('not converted', $explain);
		$this->assertStringContainsString('1000', $explain, 'The worked example was lost in the move');
		$this->assertStringContainsString('10.00', $explain);
		$this->assertStringContainsString('1.000', $explain);
	}

	public function test_the_warning_is_shown_only_when_the_board_has_amounts()
	{
		// A board with nothing recorded has nothing to be warned about, so
		// neither the banner nor its script is on the page at all.
		$this->assertStringContainsString('S_DONATIONCAMPAIGNS_HAS_AMOUNTS', $this->template());
		$this->assertSame(
			2,
			substr_count($this->template(), 'S_DONATIONCAMPAIGNS_HAS_AMOUNTS'),
			'The banner and its script should share one condition'
		);
	}

	public function test_the_confirmation_control_is_offered_only_when_needed()
	{
		$this->assertStringContainsString('<!-- IF S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT -->', $this->template());
	}

	public function test_the_template_is_balanced()
	{
		$template = $this->template();

		$this->assertSame(
			substr_count($template, '<!-- IF '),
			substr_count($template, '<!-- ENDIF -->'),
			'Unbalanced IF/ENDIF'
		);
		$this->assertSame(
			substr_count($template, '<!-- BEGIN '),
			substr_count($template, '<!-- END '),
			'Unbalanced BEGIN/END'
		);
	}

	// ------------------------------------------------- the campaign list

	/**
	 * @return string
	 */
	protected function list_template()
	{
		return file_get_contents($this->package . '/adm/style/acp_donationcampaigns_campaigns.html');
	}

	/**
	 * No inline CSS, no inline JavaScript — with ONE documented exception.
	 *
	 * phpBB's ACP has no class for the "back" link. All 21 core templates that
	 * carry one write the float inline:
	 *
	 *     <a href="{U_BACK}" style="float: {S_CONTENT_FLOW_END};">
	 *
	 * S_CONTENT_FLOW_END is what makes it right-to-left aware, so the rule
	 * cannot be met by hard-coding "right" either. Matching core exactly is
	 * the point of using the pattern at all, so this one idiom is permitted
	 * and every other inline style still fails.
	 *
	 * @param string $template
	 * @return void
	 */
	protected function assert_no_inline_css_or_javascript($template)
	{
		$without_core_back_link = str_replace(
			'style="float: {S_CONTENT_FLOW_END};"',
			'',
			$template
		);

		$this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $without_core_back_link, 'Inline CSS beyond the core back-link idiom');
		$this->assertStringNotContainsStringIgnoringCase('<script', $template);
		$this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $template);
		$this->assertStringNotContainsStringIgnoringCase('javascript:', $template);
	}

	/**
	 * A column shows a STATE; a checkbox carries an INSTRUCTION. Reusing one
	 * string for both made the Visibility column read "Show donor publicly"
	 * as though every row were telling the administrator to do something.
	 */
	public function test_the_visibility_column_shows_a_state_not_an_instruction()
	{
		$list = $this->donation_template('donations');
		$form = $this->donation_template('donation_form');

		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_VISIBILITY_PUBLIC}', $list);
		$this->assertStringNotContainsString('{L_DONATIONCAMPAIGNS_SHOW_DONOR_PUBLICLY}', $list);

		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_SHOW_DONOR_PUBLICLY}', $form);
		$this->assertStringNotContainsString('{L_DONATIONCAMPAIGNS_VISIBILITY_PUBLIC}', $form);

		$lang = array();
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$this->assertSame('Public', $lang['DONATIONCAMPAIGNS_VISIBILITY_PUBLIC']);
		$this->assertSame('Show donor publicly', $lang['DONATIONCAMPAIGNS_SHOW_DONOR_PUBLICLY']);
	}

	/**
	 * The inline-style exception is deliberately one string wide.
	 *
	 * phpBB ships no class for the ACP back link, so all 21 core templates
	 * carrying one write the float inline, and S_CONTENT_FLOW_END is what
	 * keeps it right-to-left aware. That single idiom is permitted; this test
	 * exists so the exception cannot quietly grow into "inline styles are
	 * fine now" without a failing build.
	 *
	 * @dataProvider rejected_inline_styles
	 */
	public function test_the_inline_style_exception_covers_nothing_else($markup)
	{
		$failed = false;

		try
		{
			$this->assert_no_inline_css_or_javascript($markup);
		}
		catch (\PHPUnit\Framework\AssertionFailedError $e)
		{
			$failed = true;
		}

		$this->assertTrue($failed, "The guard accepted markup it should reject: {$markup}");
	}

	public function rejected_inline_styles()
	{
		return array(
			'hard-coded float'	=> array('<a href="#" style="float: right;">Back</a>'),
			'other property'	=> array('<div style="margin-top: 10px;">x</div>'),
			'width'				=> array('<span style="width: 50%"></span>'),
			'inline script'		=> array('<script>alert(1)</script>'),
			'event handler'		=> array('<a href="#" onclick="go()">x</a>'),
			'javascript url'	=> array('<a href="javascript:go()">x</a>'),
		);
	}

	/**
	 * ...and the one permitted idiom still passes, so the guard is not simply
	 * rejecting everything.
	 */
	public function test_the_core_back_link_idiom_is_permitted()
	{
		$this->assert_no_inline_css_or_javascript(
			'<a href="{U_BACK}" style="float: {S_CONTENT_FLOW_END};">&laquo; {L_BACK}</a>'
		);
	}

	// --------------------------------------- the decimal-places warning

	/**
	 * @return string
	 */
	protected function settings_script()
	{
		return file_get_contents($this->package . '/adm/style/donationcampaigns_settings.js');
	}

	public function test_the_warning_is_hidden_until_a_currency_field_is_touched()
	{
		$template = $this->template();

		// Unconditionally hidden: the banner only ever appears through the
		// script. During confirmation it is not rendered at all.
		$this->assertStringContainsString('role="alert" hidden>', $template);
		$this->assertStringContainsString(
			'<!-- IF S_DONATIONCAMPAIGNS_HAS_AMOUNTS and not S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT -->',
			$template,
			'The banner would render alongside the server-side validation warning'
		);
	}

	/**
	 * The attribute, not a class. A class hides it visually while leaving it in
	 * the accessibility tree, so a screen reader would announce a warning that
	 * is not on the page.
	 */
	public function test_the_warning_is_hidden_from_assistive_technology_too()
	{
		$template = $this->template();

		$this->assertStringContainsString('id="donationcampaigns_exponent_warning"', $template);

		// The bare HTML attribute, which removes the element from the
		// accessibility tree as well as from the page.
		$this->assertStringContainsString(' hidden>', $template);
		$this->assertStringContainsString('class="errorbox"', $template, 'Visibility is being faked with a class');
	}

	public function test_the_settings_page_loads_its_script_only_when_it_is_needed()
	{
		$template = $this->template();

		$this->assertStringContainsString('<!-- INCLUDEJS donationcampaigns_settings.js -->', $template);

		// No recorded amounts, no warning, no reason to load anything.
		$position = strpos($template, 'INCLUDEJS');
		$guard = strrpos(substr($template, 0, $position), 'S_DONATIONCAMPAIGNS_HAS_AMOUNTS');

		$this->assertNotFalse($guard, 'The script is loaded unconditionally');
	}

	/**
	 * THE DRIFT GUARD.
	 *
	 * The script finds its fields by id. Rename one in the template and the
	 * warning silently stops appearing, with nothing failing anywhere. This
	 * asserts every id the script watches is an id the template renders.
	 */
	public function test_the_script_watches_fields_the_template_actually_renders()
	{
		preg_match_all("/'(donationcampaigns_[a-z_]+)'/", $this->settings_script(), $watched);

		$this->assertNotEmpty($watched[1], 'The script watches nothing');

		$template = $this->template();

		foreach (array_unique($watched[1]) as $id)
		{
			if ($id === 'donationcampaigns_exponent_warning')
			{
				continue;
			}

			$this->assertStringContainsString(
				'id="' . $id . '"',
				$template,
				"The script watches #{$id}, which the template does not render"
			);
		}
	}

	/**
	 * And the converse: the three fields the warning is ABOUT are the three it
	 * watches. Adding a currency setting without wiring it up would leave the
	 * warning silent for a field it applies to.
	 */
	public function test_the_script_watches_every_currency_field()
	{
		$script = $this->settings_script();

		foreach (array('code', 'symbol', 'exponent') as $field)
		{
			$this->assertStringContainsString(
				"donationcampaigns_currency_{$field}",
				$script,
				"The warning does not react to the currency {$field} field"
			);
		}

		// ...and not to anything unrelated.
		$this->assertStringNotContainsString('donor_list_limit', $script, 'An unrelated setting reveals the warning');
	}

	public function test_the_script_uses_no_library_and_no_network()
	{
		$script = $this->settings_script();

		$this->assertStringNotContainsString('jQuery', $script);
		$this->assertDoesNotMatchRegularExpression('/(^|[^a-zA-Z_$])\$\(/', $script, 'The script uses jQuery');
		$this->assertStringNotContainsString('XMLHttpRequest', $script);
		$this->assertStringNotContainsString('fetch(', $script);
		$this->assertStringNotContainsString('//cdn', $script);
		$this->assertStringContainsString("'use strict';", $script);
	}

	/**
	 * The rule the warning describes is enforced server-side. If the script
	 * were the only thing standing between an administrator and a silent
	 * reinterpretation of every stored amount, this feature would be unsafe
	 * with scripting disabled.
	 */
	public function test_the_confirmation_step_does_not_depend_on_the_script()
	{
		$module = file_get_contents($this->package . '/acp/main_module.php');

		$this->assertStringContainsString('donationcampaigns_confirm_exponent', $module);
		$this->assertStringNotContainsString('.js', $module, 'The module references a script');
	}

	/**
	 * EXACTLY ONE WARNING, EVER.
	 *
	 * Both boxes used to open with the same sentence, side by side, on the
	 * confirmation page. They are now mutually exclusive by construction: the
	 * banner is not rendered when the server is asking for confirmation, and
	 * the validation box only exists when the server has something to say.
	 */
	public function test_the_banner_and_the_validation_warning_cannot_appear_together()
	{
		$template = $this->template();

		$banner = strpos($template, 'id="donationcampaigns_exponent_warning"');
		$guard = strrpos(substr($template, 0, $banner), '<!-- IF ');

		$this->assertSame(
			'<!-- IF S_DONATIONCAMPAIGNS_HAS_AMOUNTS and not S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT -->',
			trim(substr($template, $guard, strpos($template, '-->', $guard) + 3 - $guard)),
			'The banner is not excluded from the confirmation step'
		);
	}

	/**
	 * phpBB puts warnings before the content they concern, not buried inside
	 * the fieldset being edited.
	 */
	public function test_the_banner_sits_above_the_currency_fieldset()
	{
		$template = $this->template();

		$banner = strpos($template, 'id="donationcampaigns_exponent_warning"');
		$form = strpos($template, '<form ');
		$currency = strpos($template, '{L_DONATIONCAMPAIGNS_SETTINGS_CURRENCY}');

		$this->assertLessThan($form, $banner, 'The banner is inside the form');
		$this->assertLessThan($currency, $banner, 'The banner is below the Currency fieldset');
	}

	/**
	 * The checkbox belongs to the decimal-places setting, so it stays with it.
	 */
	public function test_the_confirmation_checkbox_stays_in_the_currency_fieldset()
	{
		$template = $this->template();

		$exponent = strpos($template, 'id="donationcampaigns_currency_exponent"');
		$checkbox = strpos($template, 'id="donationcampaigns_confirm_exponent"');
		$display = strpos($template, '{L_DONATIONCAMPAIGNS_SETTINGS_DISPLAY}');

		$this->assertGreaterThan($exponent, $checkbox, 'The checkbox is above the setting it confirms');
		$this->assertLessThan($display, $checkbox, 'The checkbox drifted out of the Currency fieldset');
	}

	public function test_the_campaign_list_template_exists()
	{
		$this->assertFileExists($this->package . '/adm/style/acp_donationcampaigns_campaigns.html');
	}

	public function test_the_campaign_list_has_no_inline_css_or_javascript()
	{
		$template = $this->list_template();

		$this->assert_no_inline_css_or_javascript($template);
	}

	/**
	 * The columns an administrator reads are the titles, not the ids.
	 */
	public function test_the_campaign_list_labels_rows_by_title()
	{
		$template = $this->list_template();

		$this->assertStringContainsString('{donationcampaigns_row.TITLE|e}', $template);
		$this->assertStringContainsString('{donationcampaigns_row.TOPIC_TITLE|e}', $template);
		$this->assertStringNotContainsString('{donationcampaigns_row.CAMPAIGN_ID}', $template, 'A raw id is being shown as a label');
	}

	public function test_the_campaign_list_offers_every_action()
	{
		$template = $this->list_template();

		foreach (array('U_EDIT', 'U_DELETE', 'U_RECALCULATE') as $action)
		{
			$this->assertStringContainsString('{donationcampaigns_row.' . $action . '}', $template);
		}

		// No create action: campaigns are created from their topic.
		$this->assertStringNotContainsString('{U_DONATIONCAMPAIGNS_ADD}', $template);
	}

	public function test_the_campaign_list_includes_pagination()
	{
		$this->assertStringContainsString('pagination.html', $this->list_template());
	}

	public function test_the_campaign_list_handles_being_empty()
	{
		$t = $this->list_template();

		// Core keeps the empty state INSIDE the table, as a BEGINELSE row.
		// A green successbox outside it reads as "operation succeeded".
		$this->assertStringContainsString('<!-- BEGINELSE -->', $t);
		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_LIST_EMPTY_EXPLAIN}', $t);
		$this->assertStringNotContainsString('successbox', $t);
	}

	/**
	 * The list is a summary. A description or a donor name here would be both
	 * a privacy leak and an escaping contract this task has not defined.
	 */
	public function test_the_campaign_list_shows_no_description_or_donor()
	{
		$template = $this->list_template();

		$this->assertStringNotContainsStringIgnoringCase('DESC', $template);
		$this->assertStringNotContainsStringIgnoringCase('DONOR', $template);
	}

	public function test_every_campaign_list_language_key_has_an_english_string()
	{
		preg_match_all('/\{L_([A-Z0-9_]+)\}/', $this->list_template(), $matches);

		$this->assertNotEmpty($matches[1]);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'WARNING', 'EDIT', 'DELETE', 'BACK', 'ACP_NO_ITEMS');

		foreach (array_unique($matches[1]) as $key)
		{
			if (in_array($key, $core_keys, true))
			{
				continue;
			}

			$this->assertArrayHasKey($key, $lang, "No English string for L_{$key}");
		}
	}

	public function test_the_campaign_list_template_is_balanced()
	{
		$template = $this->list_template();

		$this->assertSame(
			substr_count($template, '<!-- IF '),
			substr_count($template, '<!-- ENDIF -->')
		);
		$this->assertSame(
			substr_count($template, '<!-- BEGIN '),
			substr_count($template, '<!-- END ')
		);
	}

	// ------------------------------------------------- the campaign form

	/**
	 * @return string
	 */
	protected function form_template()
	{
		return file_get_contents($this->package . '/styles/prosilver/template/donationcampaigns_campaign_form.html');
	}

	public function test_the_campaign_form_template_exists()
	{
		// The campaign form moved to the topic frontend in the RC2 cutover.
		$this->assertFileExists($this->package . '/styles/prosilver/template/donationcampaigns_campaign_form.html');
	}

	public function test_the_campaign_form_has_no_inline_css_or_javascript()
	{
		$t = $this->form_template();

		$this->assert_no_inline_css_or_javascript($t);
	}

	public function test_the_campaign_form_carries_a_csrf_token()
	{
		$this->assertStringContainsString('{S_FORM_TOKEN}', $this->form_template());
	}

	public function test_the_campaign_form_offers_every_field()
	{
		$t = $this->form_template();

		// No campaign_enabled: enable/disable are separate actions on the
		// management landing, not a checkbox on this form.
		foreach (array('campaign_title', 'campaign_desc', 'target_amount', 'external_url', 'show_donor_names', 'show_donation_count') as $field)
		{
			$this->assertStringContainsString('name="' . $field . '"', $t, "Field {$field} is missing");
		}

		$this->assertStringNotContainsString('name="campaign_enabled"', $t, 'The enabled checkbox must not be on the edit form');

		// The topic is NOT among them. It is shown as a linked title and can
		// never be retyped, so there is no input to find.
		$this->assertStringNotContainsString('name="topic_id"', $t);
		$this->assertStringContainsString('{DONATIONCAMPAIGNS_TOPIC_TITLE|e}', $t);
	}

	/**
	 * The collected total is derived. Rendering it as an input would invite
	 * exactly the tampering the service is built to prevent.
	 */
	/**
	 * The collected total is derived. The frontend edit form does not show it at
	 * all (the landing does), so it certainly is never an input here.
	 */
	public function test_the_collected_total_is_never_an_input()
	{
		$t = $this->form_template();

		$this->assertStringNotContainsString('name="collected_amount"', $t);
		$this->assertDoesNotMatchRegularExpression('/<input[^>]*collected/i', $t);
	}

	public function test_no_bbcode_metadata_is_ever_an_input()
	{
		$t = $this->form_template();

		foreach (array('desc_bbcode_uid', 'desc_bbcode_bitfield', 'desc_bbcode_options') as $field)
		{
			$this->assertStringNotContainsString($field, $t, "{$field} must never be submittable");
		}
	}

	/**
	 * The target accepts "10,50", so it cannot be type="number" — a numeric
	 * input silently discards a comma decimal in most browsers.
	 */
	public function test_the_target_is_a_text_field_not_a_number_field()
	{
		$this->assertMatchesRegularExpression(
			'/<input[^>]*id="target_amount"[^>]*type="text"/',
			$this->form_template()
		);
	}

	public function test_every_campaign_form_input_has_a_label()
	{
		$t = $this->form_template();

		preg_match_all('/<input[^>]*\sid="([^"]+)"/', $t, $inputs);
		preg_match_all('/<textarea[^>]*\sid="([^"]+)"/', $t, $areas);

		foreach (array_merge($inputs[1], $areas[1]) as $id)
		{
			if (in_array($id, array('submit', 'reset'), true))
			{
				continue;
			}

			$this->assertStringContainsString('for="' . $id . '"', $t, "Input {$id} has no label");
		}
	}

	/**
	 * Publishing a donor's name is a privacy decision, so the consequence is
	 * stated next to the control that causes it, not buried in a manual.
	 */
	public function test_the_donor_privacy_warning_is_next_to_its_checkbox()
	{
		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_DONOR_PRIVACY_WARNING}', $this->form_template());
	}

	public function test_every_campaign_form_language_key_has_an_english_string()
	{
		preg_match_all('/\{L_([A-Z0-9_]+)\}/', $this->form_template(), $matches);

		$this->assertNotEmpty($matches[1]);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'WARNING', 'EDIT', 'DELETE', 'BACK', 'ACP_NO_ITEMS');

		foreach (array_unique($matches[1]) as $key)
		{
			if (in_array($key, $core_keys, true))
			{
				continue;
			}

			$this->assertArrayHasKey($key, $lang, "No English string for L_{$key}");
		}
	}

	public function test_the_campaign_form_template_is_balanced()
	{
		$t = $this->form_template();

		$this->assertSame(substr_count($t, '<!-- IF '), substr_count($t, '<!-- ENDIF -->'));
		$this->assertSame(substr_count($t, '<!-- BEGIN '), substr_count($t, '<!-- END '));
	}

	// ---------------------------------------------------- the donation pages

	/**
	 * @param string $name
	 * @return string
	 */
	protected function donation_template_path($name)
	{
		// The list is an ACP oversight page; the form moved to the frontend with
		// the donation ledger. Both are checked here so neither drifts.
		$map = array(
			'donations'		=> 'adm/style/acp_donationcampaigns_donations.html',
			'donation_form'	=> 'styles/prosilver/template/donationcampaigns_donation_form.html',
		);

		return $this->package . '/' . $map[$name];
	}

	protected function donation_template($name)
	{
		return file_get_contents($this->donation_template_path($name));
	}

	public function donation_template_data()
	{
		return array('list' => array('donations'), 'form' => array('donation_form'));
	}

	/**
	 * @dataProvider donation_template_data
	 */
	public function test_the_donation_template_exists($name)
	{
		$this->assertFileExists($this->donation_template_path($name));
	}

	/**
	 * @dataProvider donation_template_data
	 */
	public function test_the_donation_template_has_no_inline_css_or_javascript($name)
	{
		$t = $this->donation_template($name);

		$this->assert_no_inline_css_or_javascript($t);
	}

	/**
	 * @dataProvider donation_template_data
	 */
	public function test_every_donation_language_key_has_an_english_string($name)
	{
		preg_match_all('/\{L_([A-Z0-9_]+)\}/', $this->donation_template($name), $matches);

		$this->assertNotEmpty($matches[1]);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'WARNING', 'EDIT', 'DELETE', 'BACK', 'ACP_NO_ITEMS');

		foreach (array_unique($matches[1]) as $key)
		{
			if (in_array($key, $core_keys, true))
			{
				continue;
			}

			$this->assertArrayHasKey($key, $lang, "No English string for L_{$key}");
		}
	}

	/**
	 * @dataProvider donation_template_data
	 */
	public function test_the_donation_template_is_balanced($name)
	{
		$t = $this->donation_template($name);

		$this->assertSame(substr_count($t, '<!-- IF '), substr_count($t, '<!-- ENDIF -->'));
		$this->assertSame(substr_count($t, '<!-- BEGIN '), substr_count($t, '<!-- END '));
	}

	public function test_the_donation_form_carries_a_csrf_token()
	{
		$this->assertStringContainsString('{S_FORM_TOKEN}', $this->donation_template('donation_form'));
	}

	public function test_the_donation_form_offers_every_field()
	{
		$t = $this->donation_template('donation_form');

		foreach (array('donation_amount', 'donor_name', 'donation_time', 'donation_public') as $field)
		{
			$this->assertStringContainsString('name="' . $field . '"', $t, "Field {$field} is missing");
		}
	}

	/**
	 * The campaign a donation belongs to is fixed by the page it was opened
	 * from. Offering it as an input would let a submission move money between
	 * campaigns and corrupt two totals at once.
	 */
	public function test_the_donation_form_never_offers_a_campaign_or_total_input()
	{
		$t = $this->donation_template('donation_form');

		$this->assertStringNotContainsString('name="campaign_id"', $t);
		$this->assertStringNotContainsString('name="collected_amount"', $t);
	}

	/**
	 * The amount accepts "8,70", so it cannot be type="number".
	 */
	public function test_the_donation_amount_is_a_text_field()
	{
		$this->assertMatchesRegularExpression(
			'/<input[^>]*id="donation_amount"[^>]*type="text"/',
			$this->donation_template('donation_form')
		);
	}

	public function test_every_donation_form_input_has_a_label()
	{
		$t = $this->donation_template('donation_form');

		preg_match_all('/<input[^>]*\sid="([^"]+)"/', $t, $inputs);

		foreach ($inputs[1] as $id)
		{
			if (in_array($id, array('submit', 'reset'), true))
			{
				continue;
			}

			$this->assertStringContainsString('for="' . $id . '"', $t, "Input {$id} has no label");
		}
	}

	/**
	 * Publishing a donor's name needs their consent, so the reminder sits
	 * beside the control that publishes it.
	 */
	public function test_the_consent_reminder_is_beside_the_visibility_control()
	{
		$this->assertStringContainsString('{L_DONATIONCAMPAIGNS_PUBLIC_EXPLAIN}', $this->donation_template('donation_form'));
	}

	/**
	 * The wording has to make the model unambiguous: these are receipts, not
	 * pledges, and this extension never touches a payment provider.
	 */
	public function test_the_wording_states_that_entries_are_confirmed_receipts()
	{
		$lang = array();
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$this->assertStringContainsString('public total', $lang['DONATIONCAMPAIGNS_DONATIONS_OVERSIGHT_EXPLAIN']);
		$this->assertStringContainsString('been received', $lang['DONATIONCAMPAIGNS_DONATION_FORM_EXPLAIN']);
		$this->assertStringContainsString('does not process payments', $lang['DONATIONCAMPAIGNS_DONATION_FORM_EXPLAIN']);
		$this->assertStringContainsString('Anonymous', $lang['DONATIONCAMPAIGNS_PUBLIC_EXPLAIN']);
		$this->assertStringContainsString('consent', $lang['DONATIONCAMPAIGNS_PUBLIC_EXPLAIN']);
	}

	public function test_the_donation_list_includes_pagination_and_an_empty_state()
	{
		$t = $this->donation_template('donations');

		$this->assertStringContainsString('pagination.html', $t);
		$this->assertStringContainsString('<!-- BEGINELSE -->', $t);
		$this->assertStringContainsString('{L_ACP_NO_ITEMS}', $t);
		$this->assertStringNotContainsString('successbox', $t);
	}

	public function test_the_donation_list_labels_rows_by_donor_not_id()
	{
		$t = $this->donation_template('donations');

		$this->assertStringContainsString('{donationcampaigns_donation.DONOR_NAME|e}', $t);
		$this->assertStringNotContainsString('{donationcampaigns_donation.DONATION_ID}', $t);
	}
}
