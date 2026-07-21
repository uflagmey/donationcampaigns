<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

/**
 * The shipped prosilver assets, checked as files.
 *
 * ADR-013 fixes where they live and what they may contain, and the template's
 * filename is the entire mechanism by which phpBB decides where the block
 * renders — a typo produces silence, not an error. Both are asserted here
 * rather than left to a manual browser check.
 */
class prosilver_assets_test extends \phpbb_test_case
{
	/** @var string */
	protected $package;

	/** @var string */
	protected $template_file;

	/** @var string */
	protected $css_file;

	public function setUp(): void
	{
		parent::setUp();

		$this->package = dirname(dirname(__DIR__));
		$this->template_file = $this->package . '/styles/prosilver/template/event/viewtopic_body_poll_before.html';
		$this->css_file = $this->package . '/styles/prosilver/theme/donationcampaigns.css';
	}

	/**
	 * @return string
	 */
	protected function template()
	{
		return file_get_contents($this->template_file);
	}

	/**
	 * @return string
	 */
	protected function css()
	{
		return file_get_contents($this->css_file);
	}

	// ------------------------------------------------------------- locations

	/**
	 * ADR-013: templates under styles/prosilver/template, stylesheets under
	 * styles/prosilver/theme. phpBB resolves both by convention, so a file in
	 * the wrong directory is simply never loaded.
	 */
	public function test_the_template_is_in_the_prosilver_event_directory()
	{
		$this->assertFileExists($this->template_file);
	}

	public function test_the_stylesheet_is_in_the_prosilver_theme_directory()
	{
		$this->assertFileExists($this->css_file);
	}

	public function test_no_assets_are_shipped_for_any_other_style()
	{
		$styles = glob($this->package . '/styles/*', GLOB_ONLYDIR);

		$this->assertSame(
			array($this->package . '/styles/prosilver'),
			$styles,
			'Version 1.0 supports prosilver only — see ADR-013'
		);
	}

	// ---------------------------------------------------------- event naming

	/**
	 * The template's filename must match a real event in core's
	 * viewtopic_body.html, and that event must sit OUTSIDE the S_HAS_POLL
	 * conditional — otherwise the box would only appear on topics with a poll,
	 * which is exactly the bug the counter-intuitive event name invites.
	 *
	 * Verified against the phpBB source tree the tests run against.
	 */
	public function test_the_event_exists_in_core_and_sits_outside_the_poll_condition()
	{
		global $phpbb_root_path;

		$core_template = $phpbb_root_path . 'styles/prosilver/template/viewtopic_body.html';

		if (!file_exists($core_template))
		{
			$this->markTestSkipped('phpBB source tree not available');
		}

		$lines = file($core_template);

		$event_line = null;
		$poll_line = null;
		$postrow_line = null;

		foreach ($lines as $number => $line)
		{
			if ($event_line === null && strpos($line, '<!-- EVENT viewtopic_body_poll_before -->') !== false)
			{
				$event_line = $number;
			}

			if ($poll_line === null && strpos($line, 'IF S_HAS_POLL') !== false)
			{
				$poll_line = $number;
			}

			if ($postrow_line === null && strpos($line, 'BEGIN postrow') !== false)
			{
				$postrow_line = $number;
			}
		}

		$this->assertNotNull($event_line, 'The event no longer exists in core');
		$this->assertNotNull($poll_line);
		$this->assertNotNull($postrow_line);

		$this->assertLessThan($poll_line, $event_line, 'The event moved inside the poll conditional');
		$this->assertLessThan($postrow_line, $event_line, 'The event moved below the post loop');
	}

	/**
	 * The stylesheet only reaches a page if something asks for it. phpBB's
	 * mechanism is INCLUDECSS from a header event, using the @vendor_package
	 * namespace — a plain <link> to a guessed path breaks under a different
	 * board URL or asset version.
	 */
	public function test_the_stylesheet_is_included_by_the_phpbb_mechanism()
	{
		$header = $this->package . '/styles/prosilver/template/event/overall_header_head_append.html';

		$this->assertFileExists($header);

		$contents = file_get_contents($header);

		$this->assertStringContainsString(
			'INCLUDECSS @uflagmey_donationcampaigns/donationcampaigns.css',
			$contents
		);
		$this->assertStringNotContainsString('<link', $contents, 'Use INCLUDECSS, not a hand-built link tag');
	}

	// ------------------------------------------------------ ADR-013 contents

	public function test_the_template_contains_no_inline_style()
	{
		$this->assertDoesNotMatchRegularExpression(
			'/\sstyle\s*=/i',
			$this->template(),
			'Inline CSS is forbidden by ADR-013'
		);
	}

	public function test_the_template_contains_no_javascript()
	{
		$template = $this->template();

		$this->assertStringNotContainsStringIgnoringCase('<script', $template);
		$this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $template, 'An inline event handler is JavaScript');
		$this->assertStringNotContainsStringIgnoringCase('javascript:', $template);
	}

	/**
	 * Nothing in the box may depend on scripting: no element is hidden by
	 * default awaiting a script to reveal it, and the progress width comes
	 * from a stylesheet class rather than a script.
	 */
	public function test_the_box_needs_no_javascript_to_be_usable()
	{
		$this->assertStringNotContainsString('hidden', $this->template());
		$this->assertDoesNotMatchRegularExpression('/display\s*:\s*none/i', $this->template());
	}

	public function test_every_custom_class_carries_the_package_prefix()
	{
		preg_match_all('/class="([^"]+)"/', $this->template(), $matches);

		// Classes phpBB itself defines, reused for meaning rather than looks.
		$phpbb_classes = array('panel', 'inner', 'button');

		foreach ($matches[1] as $attribute)
		{
			foreach (preg_split('/\s+/', trim($attribute)) as $class)
			{
				if ($class === '' || in_array($class, $phpbb_classes, true))
				{
					continue;
				}

				$this->assertStringStartsWith(
					'donationcampaigns-',
					$class,
					"CSS class {$class} is not namespaced"
				);
			}
		}
	}

	// --------------------------------------------------------- typography

	/**
	 * REGRESSION. prosilver's h3 rule carries text-transform: uppercase
	 * (common.css:44), so a campaign titled "Betrieb des Forums" rendered as
	 * "BETRIEB DES FORUMS". A title is the administrator's own words and must
	 * reach the reader as written.
	 */
	public function test_the_campaign_title_is_not_forced_to_uppercase()
	{
		$this->assertMatchesRegularExpression(
			'/\.donationcampaigns-title\s*\{[^}]*text-transform:\s*none/s',
			$this->css(),
			'The title inherits prosilver uppercase h3 styling'
		);
	}

	public function test_no_rule_applies_a_text_transform()
	{
		// Comments stripped first: this file explains which core rule it is
		// overriding, and naming that rule is not applying it.
		$declarations = preg_replace('#/\*.*?\*/#s', '', $this->css());

		preg_match_all('/text-transform:\s*([a-z-]+)/', $declarations, $m);

		foreach ($m[1] as $value)
		{
			$this->assertSame('none', $value, 'A text-transform other than none is applied');
		}
	}

	/**
	 * The box sits inside a .panel, which inherits prosilver's 10px body size
	 * while post text resolves to 13px. Left alone the campaign reads as a
	 * footnote next to the post it belongs to. These assert the intent, in the
	 * em units the stylesheet uses.
	 *
	 * @dataProvider typography_expectations
	 */
	public function test_the_box_sets_a_readable_size($selector, $min_em)
	{
		$css = $this->css();

		$this->assertSame(1, preg_match('/\.' . preg_quote($selector, '/') . '\s*\{([^}]*)\}/s', $css, $block),
			"{$selector} has no rule block");
		$this->assertSame(1, preg_match('/font-size:\s*([0-9.]+)em/', $block[1], $size),
			"{$selector} sets no font-size");
		$this->assertGreaterThanOrEqual($min_em, (float) $size[1],
			"{$selector} is smaller than the surrounding topic text");
	}

	public function typography_expectations()
	{
		return array(
			// Clearly prominent, but still below the post subject (1.7em).
			'the title'			=> array('donationcampaigns-title', 1.4),
			// Post text resolves to 1.3em against the same 10px base.
			'the description'	=> array('donationcampaigns-desc', 1.3),
			'the figures'		=> array('donationcampaigns-figures', 1.3),
		);
	}

	/**
	 * Subordinate to the post it accompanies: the campaign heading must not
	 * out-shout the post subject, which resolves to 1.7em.
	 */
	public function test_the_title_stays_below_the_post_subject()
	{
		preg_match('/\.donationcampaigns-title\s*\{([^}]*)\}/s', $this->css(), $block);
		preg_match('/font-size:\s*([0-9.]+)em/', $block[1], $size);

		$this->assertLessThan(1.7, (float) $size[1], 'The campaign title competes with the post subject');
	}

	public function test_the_whole_block_is_guarded_by_the_show_flag()
	{
		$template = trim($this->template());

		$this->assertStringStartsWith('<!-- IF S_DONATIONCAMPAIGNS_SHOW -->', $template);
		$this->assertStringEndsWith('<!-- ENDIF -->', $template);
	}

	// ------------------------------------------------------- accessibility

	public function test_the_progress_indicator_is_semantic()
	{
		$template = $this->template();

		$this->assertStringContainsString('role="progressbar"', $template);
		// ARIA requires valuenow within valuemin/valuemax, so it carries the
		// clamped figure. The real percentage -- which may exceed 100 -- is
		// announced through aria-valuetext, which takes precedence for
		// assistive technology, so nobody hears a different number from the
		// one on screen.
		$this->assertStringContainsString('aria-valuenow="{DONATIONCAMPAIGNS_PERCENT}"', $template);
		$this->assertStringContainsString('aria-valuetext="{DONATIONCAMPAIGNS_PERCENT_RAW}%"', $template);
		$this->assertStringContainsString('{DONATIONCAMPAIGNS_PERCENT_RAW}%', $template);
		$this->assertStringContainsString('aria-valuemin="0"', $template);
		$this->assertStringContainsString('aria-valuemax="100"', $template);
	}

	/**
	 * The bar is decorative; the figures beside it are the actual information.
	 * A screen reader, and a reader who cannot distinguish the bar's colour,
	 * must get the same facts from text.
	 */
	public function test_progress_has_a_text_equivalent()
	{
		$template = $this->template();

		// The money values carry |e; the percentage is an integer.
		$this->assertStringContainsString('{DONATIONCAMPAIGNS_COLLECTED|e}', $template);
		$this->assertStringContainsString('{DONATIONCAMPAIGNS_TARGET|e}', $template);
		$this->assertStringContainsString('{DONATIONCAMPAIGNS_PERCENT_RAW}', $template);
	}

	public function test_the_target_reached_state_is_not_signalled_by_colour_alone()
	{
		$this->assertStringContainsString(
			'{L_DONATIONCAMPAIGNS_TARGET_REACHED}',
			$this->template(),
			'Reaching the target must be stated in words, not only shown in colour'
		);
	}

	public function test_the_box_has_a_heading()
	{
		$this->assertMatchesRegularExpression('/<h[23][^>]*>/', $this->template());
	}

	public function test_the_donation_link_has_meaningful_text()
	{
		$template = $this->template();

		// The label is the campaign's own configured text, escaped once, not a
		// fixed string. A board links to bank details as readily as to PayPal.
		$this->assertStringContainsString('{DONATIONCAMPAIGNS_LINK_TEXT|e}', $template);
		$this->assertStringNotContainsString('DONATE_LINK', $template, 'A fixed Donate label is still hard-coded');
		$this->assertDoesNotMatchRegularExpression('/>\s*(here|click here|link)\s*</i', $template);
	}

	/**
	 * An external link opened from a forum page must not hand the target
	 * window a reference back, and must not pass the board's link equity to an
	 * address an administrator typed in.
	 */
	public function test_the_external_link_is_safely_attributed()
	{
		$this->assertStringContainsString('rel="noopener noreferrer nofollow"', $this->template());
	}

	// ---------------------------------------------------------- stylesheet

	/**
	 * The bar width is expressed as a class per five-percent step, because
	 * ADR-013 forbids the inline style the obvious implementation would use.
	 * Every step the listener can emit needs a rule, or the bar silently has
	 * no width.
	 */
	public function test_the_stylesheet_defines_a_rule_for_every_progress_step()
	{
		$css = $this->css();

		for ($step = 0; $step <= 100; $step += 5)
		{
			$this->assertStringContainsString(
				".donationcampaigns-bar--{$step}",
				$css,
				"No stylesheet rule for progress step {$step}"
			);
		}
	}

	public function test_the_stylesheet_uses_no_fixed_widths()
	{
		$this->assertDoesNotMatchRegularExpression(
			'/width\s*:\s*\d+px/i',
			$this->css(),
			'A fixed pixel width breaks on narrow screens — see ADR-013'
		);
	}

	public function test_the_stylesheet_uses_no_absolute_positioning()
	{
		$this->assertDoesNotMatchRegularExpression('/position\s*:\s*absolute/i', $this->css());
	}

	public function test_every_stylesheet_selector_is_namespaced()
	{
		preg_match_all('/^\s*(\.[a-z0-9_-]+)/mi', $this->css(), $matches);

		$this->assertNotEmpty($matches[1]);

		foreach ($matches[1] as $selector)
		{
			$this->assertStringStartsWith('.donationcampaigns-', trim($selector));
		}
	}

	public function test_the_stylesheet_is_balanced()
	{
		$css = $this->css();

		$this->assertSame(
			substr_count($css, '{'),
			substr_count($css, '}'),
			'Unbalanced braces in the stylesheet'
		);
	}

	// ------------------------------------------------- the escaping contract

	/**
	 * Escaping lives in the template now, so the template is where it has to
	 * be enforced. Every administrator-controlled scalar carries |e.
	 */
	public function test_every_administrator_controlled_value_carries_the_escape_filter()
	{
		$template = $this->template();

		foreach (array('DONATIONCAMPAIGNS_CAMPAIGN_TITLE', 'DONATIONCAMPAIGNS_COLLECTED', 'DONATIONCAMPAIGNS_TARGET', 'DONATIONCAMPAIGNS_URL') as $var)
		{
			$this->assertStringContainsString('{' . $var . '|e}', $template, "{$var} is rendered without |e");
			$this->assertStringNotContainsString('{' . $var . '}', $template, "{$var} also appears unescaped");
		}

		$this->assertStringContainsString('{donationcampaigns_donor.NAME|e}', $template);
		$this->assertStringNotContainsString('{donationcampaigns_donor.NAME}', $template);
	}

	/**
	 * The description is the one value that must NOT be escaped: it has been
	 * through phpBB's text-formatting path, and escaping it again would show
	 * an administrator's formatting as visible tags.
	 */
	public function test_the_description_is_rendered_without_the_escape_filter()
	{
		$template = $this->template();

		$this->assertStringContainsString('{DONATIONCAMPAIGNS_DESC}', $template);
		$this->assertStringNotContainsString('{DONATIONCAMPAIGNS_DESC|e}', $template);
	}

	/**
	 * Nothing is ever marked safe, and autoescaping is never touched.
	 */
	public function test_no_template_bypasses_escaping()
	{
		foreach (glob($this->package . '/styles/prosilver/template/event/*.html') as $file)
		{
			$contents = file_get_contents($file);

			$this->assertStringNotContainsString('|raw', $contents, basename($file) . ' marks a value safe');
			$this->assertStringNotContainsString('autoescape', $contents);
		}
	}
}
