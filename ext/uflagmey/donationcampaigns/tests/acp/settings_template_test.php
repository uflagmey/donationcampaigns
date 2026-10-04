<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

use uflagmey\donationcampaigns\tests\template_renderer;

/**
 * The ACP settings template, checked as a file and as rendered.
 *
 * A missing form token, an unlabelled field or a language key with no string
 * all fail silently at runtime, so they are asserted here. Where a rule is
 * about what a page shows -- a token inside the form, a banner only in one
 * state -- it is asserted on the page phpBB's engine renders, so it holds
 * whichever template syntax produced it.
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

	/**
	 * A shipped template rendered with phpBB's engine.
	 *
	 * @param string $path Relative to the package root
	 * @param array $vars
	 * @param array $blocks
	 * @return string
	 */
	protected function render($path, array $vars = array(), array $blocks = array())
	{
		return template_renderer::render(file_get_contents($this->package . '/' . $path), $vars, $blocks);
	}

	/**
	 * @param array $vars
	 * @return string
	 */
	protected function render_settings(array $vars = array())
	{
		return $this->render('adm/style/acp_donationcampaigns_settings.html', $vars);
	}

	/**
	 * A form token as phpBB renders S_FORM_TOKEN, recognisable in the output.
	 *
	 * @return array
	 */
	protected function form_token()
	{
		return array('S_FORM_TOKEN' => '<input type="hidden" name="form_token" value="TOKEN_MARKER">');
	}

	/**
	 * The one form of a rendered page.
	 *
	 * @param string $html
	 * @return string
	 */
	protected function form_of($html)
	{
		$this->assertSame(1, preg_match_all('#<form\b.*?</form>#s', $html, $forms), 'Not exactly one form rendered');

		return $forms[0][0];
	}

	/**
	 * The <dl> row of a rendered form that contains $needle.
	 *
	 * @param string $html
	 * @param string $needle
	 * @return string
	 */
	protected function row_with($html, $needle)
	{
		preg_match_all('#<dl>.*?</dl>#s', $html, $rows);

		foreach ($rows[0] as $row)
		{
			if (strpos($row, $needle) !== false)
			{
				return $row;
			}
		}

		$this->fail("No form row contains {$needle}");
	}

	/**
	 * The language keys a template uses, in either syntax: {L_KEY} or
	 * lang('KEY').
	 *
	 * @param string $template
	 * @return array
	 */
	protected function language_keys($template)
	{
		preg_match_all("/\\{L_([A-Z0-9_]+)\\}|lang\\('([A-Z0-9_]+)'\\)/", $template, $matches);

		return array_values(array_unique(array_filter(array_merge($matches[1], $matches[2]))));
	}

	/**
	 * Every opened block is closed, in either syntax, and phpBB's engine
	 * compiles the template.
	 *
	 * @param string $template
	 * @return void
	 */
	protected function assert_balanced($template)
	{
		$this->assertSame(
			substr_count($template, '<!-- IF ') + substr_count($template, '{% if '),
			substr_count($template, '<!-- ENDIF -->') + substr_count($template, '{% endif %}'),
			'Unbalanced IF/ENDIF'
		);
		$this->assertSame(
			substr_count($template, '<!-- BEGIN ') + substr_count($template, '{% for '),
			substr_count($template, '<!-- END ') + substr_count($template, '{% endfor %}'),
			'Unbalanced BEGIN/END'
		);
		$this->assertIsString(template_renderer::render($template, array()));
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
		$this->assertStringContainsString('value="TOKEN_MARKER"', $this->form_of($this->render_settings($this->form_token())));
	}

	public function test_the_form_posts_to_the_module_action()
	{
		$form = $this->form_of($this->render_settings(array('U_ACTION' => 'ACTION_URL')));

		$this->assertStringContainsString('method="post"', $form);
		$this->assertStringContainsString('action="ACTION_URL"', $form);
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
		$keys = $this->language_keys($this->template());

		$this->assertNotEmpty($keys);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		// Supplied by phpBB itself.
		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'WARNING', 'BACK', 'ACP_NO_ITEMS', 'YES', 'NO');

		foreach ($keys as $key)
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
		$this->assertStringNotContainsString('id="donationcampaigns_confirm_exponent"', $this->render_settings());
		$this->assertStringContainsString(
			'id="donationcampaigns_confirm_exponent"',
			$this->render_settings(array('S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT' => true))
		);
	}

	public function test_the_template_is_balanced()
	{
		$this->assert_balanced($this->template());
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
	 * (or, in native Twig, style="float: {{ S_CONTENT_FLOW_END }};").
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
		$without_core_back_link = preg_replace(
			'/style="float: (\\{S_CONTENT_FLOW_END\\}|\\{\\{ S_CONTENT_FLOW_END \\}\\});"/',
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
		$list = $this->language_keys($this->donation_template('donations'));
		$form = $this->language_keys($this->donation_template('donation_form'));

		$this->assertContains('DONATIONCAMPAIGNS_VISIBILITY_PUBLIC', $list);
		$this->assertNotContains('DONATIONCAMPAIGNS_SHOW_DONOR_PUBLICLY', $list);

		$this->assertContains('DONATIONCAMPAIGNS_SHOW_DONOR_PUBLICLY', $form);
		$this->assertNotContains('DONATIONCAMPAIGNS_VISIBILITY_PUBLIC', $form);

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
		$this->assert_no_inline_css_or_javascript(
			'<a href="{{ U_BACK }}" style="float: {{ S_CONTENT_FLOW_END }};">&laquo; {{ lang(\'BACK\') }}</a>'
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
		// Unconditionally hidden: the banner only ever appears through the
		// script. During confirmation it is not rendered at all.
		$this->assertStringContainsString(
			'role="alert" hidden>',
			$this->render_settings(array('S_DONATIONCAMPAIGNS_HAS_AMOUNTS' => true))
		);
		$this->assertStringNotContainsString(
			'id="donationcampaigns_exponent_warning"',
			$this->render_settings(array('S_DONATIONCAMPAIGNS_HAS_AMOUNTS' => true, 'S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT' => true)),
			'The banner would render alongside the server-side validation warning'
		);
		$this->assertStringNotContainsString(
			'id="donationcampaigns_exponent_warning"',
			$this->render_settings(),
			'A board without amounts is warned about nothing'
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
		// The tag, not the file name: a template comment mentions the name.
		$script = '#<script src="[^"]*/adm/style/donationcampaigns_settings\\.js\\?assets_version=\\d+"></script>#';

		$this->assertMatchesRegularExpression($script, $this->render_settings(array('S_DONATIONCAMPAIGNS_HAS_AMOUNTS' => true)));

		// No recorded amounts, no warning, no reason to load anything; and
		// during confirmation there is no banner for it to reveal.
		$this->assertDoesNotMatchRegularExpression($script, $this->render_settings(), 'The script is loaded unconditionally');
		$this->assertDoesNotMatchRegularExpression(
			$script,
			$this->render_settings(array('S_DONATIONCAMPAIGNS_HAS_AMOUNTS' => true, 'S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT' => true)),
			'The script is loaded for a banner that is not there'
		);
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
		// The confirmation step: amounts exist, the server refused the change
		// and says why.
		$html = template_renderer::render($this->template(), array(
			'S_DONATIONCAMPAIGNS_HAS_AMOUNTS'		=> true,
			'S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT'	=> true,
			'S_DONATIONCAMPAIGNS_ERROR'				=> true,
		), array('donationcampaigns_error' => array(array('MESSAGE' => 'Confirm the change'))));

		$this->assertSame(1, substr_count($html, 'class="errorbox"'), 'The banner is not excluded from the confirmation step');
		$this->assertStringNotContainsString('id="donationcampaigns_exponent_warning"', $html);
	}

	/**
	 * phpBB puts warnings before the content they concern, not buried inside
	 * the fieldset being edited.
	 */
	public function test_the_banner_sits_above_the_currency_fieldset()
	{
		$html = $this->render_settings(array('S_DONATIONCAMPAIGNS_HAS_AMOUNTS' => true));

		$banner = strpos($html, 'id="donationcampaigns_exponent_warning"');
		$form = strpos($html, '<form ');
		$currency = strpos($html, '<legend>DONATIONCAMPAIGNS_SETTINGS_CURRENCY</legend>');

		$this->assertNotFalse($banner);
		$this->assertNotFalse($currency);

		$this->assertLessThan($form, $banner, 'The banner is inside the form');
		$this->assertLessThan($currency, $banner, 'The banner is below the Currency fieldset');
	}

	/**
	 * The checkbox belongs to the decimal-places setting, so it stays with it.
	 */
	public function test_the_confirmation_checkbox_stays_in_the_currency_fieldset()
	{
		$html = $this->render_settings(array('S_DONATIONCAMPAIGNS_CONFIRM_EXPONENT' => true));

		$exponent = strpos($html, 'id="donationcampaigns_currency_exponent"');
		$checkbox = strpos($html, 'id="donationcampaigns_confirm_exponent"');
		$display = strpos($html, '<legend>DONATIONCAMPAIGNS_SETTINGS_DISPLAY</legend>');

		$this->assertNotFalse($checkbox);
		$this->assertNotFalse($display);

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
	/**
	 * The campaign list rendered with one row.
	 *
	 * @param array $row
	 * @param array $vars
	 * @return string
	 */
	protected function render_list(array $row, array $vars = array())
	{
		return $this->render('adm/style/acp_donationcampaigns_campaigns.html', $vars, array('donationcampaigns_row' => array($row)));
	}

	public function test_the_campaign_list_labels_rows_by_title()
	{
		$html = $this->render_list(array(
			'CAMPAIGN_ID'	=> 987654,
			'TITLE'			=> '<b>Roof</b>',
			// Core stores topic_title already escaped (F1).
			'TOPIC_TITLE'	=> 'Roof &amp; walls',
		));

		$this->assertStringContainsString('&lt;b&gt;Roof&lt;/b&gt;', $html);
		$this->assertStringNotContainsString('<b>Roof</b>', $html);
		$this->assertStringContainsString('Roof &amp; walls', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html);
		$this->assertStringNotContainsString('987654', $html, 'A raw id is being shown as a label');
	}

	public function test_the_campaign_list_offers_every_action()
	{
		$html = $this->render_list(
			array('U_EDIT' => 'URL_EDIT', 'U_DELETE' => 'URL_DELETE', 'U_RECALCULATE' => 'URL_RECALCULATE'),
			array('U_DONATIONCAMPAIGNS_ADD' => 'URL_ADD')
		);

		foreach (array('URL_EDIT', 'URL_DELETE', 'URL_RECALCULATE') as $action)
		{
			$this->assertStringContainsString('href="' . $action . '"', $html);
		}

		// No create action: campaigns are created from their topic.
		$this->assertStringNotContainsString('URL_ADD', $html);
	}

	public function test_the_campaign_list_includes_pagination()
	{
		$this->assertStringContainsString('pagination.html', $this->list_template());
	}

	public function test_the_campaign_list_handles_being_empty()
	{
		$html = $this->render('adm/style/acp_donationcampaigns_campaigns.html');

		// Core keeps the empty state INSIDE the table, as the loop's empty
		// row. A green successbox outside it reads as "operation succeeded".
		$this->assertMatchesRegularExpression('#<tbody>\\s*<tr class="row3">.*?<td colspan="6">DONATIONCAMPAIGNS_LIST_EMPTY_EXPLAIN</td>\\s*</tr>\\s*</tbody>#s', $html);
		$this->assertStringNotContainsString('successbox', $html);
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
		$keys = $this->language_keys($this->list_template());

		$this->assertNotEmpty($keys);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'WARNING', 'EDIT', 'DELETE', 'BACK', 'ACP_NO_ITEMS');

		foreach ($keys as $key)
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
		$this->assert_balanced($this->list_template());
	}

	// ------------------------------------------------- the campaign form

	/**
	 * @return string
	 */
	protected function form_template()
	{
		// The form's source: its fields live in a shared include since beta3,
		// pasted in here with the frontend's empty name prefix.
		return template_renderer::inline_partials(
			file_get_contents($this->package . '/styles/prosilver/template/donationcampaigns_campaign_form.html')
		);
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

	/**
	 * @param array $vars
	 * @return string
	 */
	protected function render_form(array $vars = array())
	{
		return $this->render('styles/prosilver/template/donationcampaigns_campaign_form.html', $vars);
	}

	public function test_the_campaign_form_carries_a_csrf_token()
	{
		$this->assertStringContainsString('value="TOKEN_MARKER"', $this->form_of($this->render_form($this->form_token())));
	}

	public function test_the_campaign_form_offers_every_field()
	{
		// Core stores topic_title already escaped (F1).
		$t = $this->render_form(array('DONATIONCAMPAIGNS_TOPIC_TITLE' => 'Roof &amp; walls'));

		// No campaign_enabled: enable/disable are separate actions on the
		// management landing, not a checkbox on this form.
		foreach (array('campaign_title', 'campaign_desc', 'target_amount', 'external_url', 'show_donor_names', 'show_donation_count') as $field)
		{
			$this->assertStringContainsString('name="' . $field . '"', $t, "Field {$field} is missing");
		}

		$this->assertStringNotContainsString('name="campaign_enabled"', $t, 'The enabled checkbox must not be on the edit form');

		// The topic is NOT among them. It is shown as a linked title and can
		// never be retyped, so there is no input to find. Shown without |e:
		// core stores topic_title already escaped (F1).
		$this->assertStringNotContainsString('name="topic_id"', $t);
		$this->assertStringContainsString('Roof &amp; walls', $t);
		$this->assertStringNotContainsString('&amp;amp;', $t);
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
		$this->assertStringContainsString(
			'DONATIONCAMPAIGNS_DONOR_PRIVACY_WARNING',
			$this->row_with($this->render_form(), 'name="show_donor_names"')
		);
	}

	public function test_every_campaign_form_language_key_has_an_english_string()
	{
		$keys = $this->language_keys($this->form_template());

		$this->assertNotEmpty($keys);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		// Frontend template: only keys phpBB defines for the FRONTEND (see
		// architecture_test::test_frontend_templates_use_only_frontend_language_keys).
		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'ERROR', 'DELETE', 'BACK');

		foreach ($keys as $key)
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
		$this->assert_balanced($this->form_template());
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
		$keys = $this->language_keys($this->donation_template($name));

		$this->assertNotEmpty($keys);

		$lang = array();
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		// Covers an ACP and a frontend template; frontend availability is checked
		// separately by the architecture test.
		$core_keys = array('COLON', 'SUBMIT', 'RESET', 'WARNING', 'ERROR', 'EDIT', 'DELETE', 'BACK', 'ACP_NO_ITEMS');

		foreach ($keys as $key)
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
		$this->assert_balanced($this->donation_template($name));
	}

	public function test_the_donation_form_carries_a_csrf_token()
	{
		$this->assertStringContainsString(
			'value="TOKEN_MARKER"',
			$this->form_of($this->render('styles/prosilver/template/donationcampaigns_donation_form.html', $this->form_token()))
		);
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
		$this->assertStringContainsString(
			'DONATIONCAMPAIGNS_PUBLIC_EXPLAIN',
			$this->row_with($this->render('styles/prosilver/template/donationcampaigns_donation_form.html'), 'name="donation_public"')
		);
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
		$this->assertStringContainsString('pagination.html', $this->donation_template('donations'));

		$html = $this->render('adm/style/acp_donationcampaigns_donations.html');

		// The empty state sits inside the table, as the loop's empty row.
		$this->assertMatchesRegularExpression('#<tbody>\\s*<tr class="row3">\\s*<td colspan="6">ACP_NO_ITEMS</td>\\s*</tr>\\s*</tbody>#s', $html);
		$this->assertStringNotContainsString('successbox', $html);
	}

	public function test_the_donation_list_labels_rows_by_donor_not_id()
	{
		$html = $this->render('adm/style/acp_donationcampaigns_donations.html', array(), array(
			'donationcampaigns_donation' => array(array('DONATION_ID' => 987654, 'DONOR_NAME' => '<b>Ann</b>')),
		));

		$this->assertStringContainsString('&lt;b&gt;Ann&lt;/b&gt;', $html);
		$this->assertStringNotContainsString('<b>Ann</b>', $html);
		$this->assertStringNotContainsString('987654', $html);
	}
}
