<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\unit;

/**
 * The rules that hold across the whole package, checked against the source.
 *
 * Every one of these has a behavioural test somewhere too. These exist because
 * a behavioural test only covers the paths someone thought to write; a rule
 * like "no SQL outside repositories" has to hold in code that does not exist
 * yet, and the cheapest way to keep it true is to fail the build when it stops
 * being true.
 */
class architecture_test extends \phpbb_test_case
{
	/**
	 * Template variables that carry a value phpBB core stored ALREADY escaped.
	 *
	 * Core reads a topic subject and a forum name through request->variable(),
	 * which runs htmlspecialchars() on the way in, so topic_title and
	 * forum_name sit in the database as "Kosten &amp; Miete". Core's own
	 * templates print them without |e. Adding |e escapes them a second time
	 * and the page shows "Kosten &amp; Miete" (found in beta3, F1).
	 *
	 * Everything the extension stores itself is stored raw and still needs |e.
	 * Matched as a suffix, so block variables (row.TOPIC_TITLE) are covered.
	 */
	const CORE_ESCAPED_FIELDS = array('TOPIC_TITLE', 'FORUM_NAME');

	/**
	 * Templates still written in phpBB's legacy syntax, relative to the
	 * package. 1.0.0-beta4 converts them to native Twig.
	 *
	 * A ratchet: a template on this list must still contain legacy syntax, so
	 * a converted file cannot stay listed; a template off the list must not
	 * contain any. Each conversion removes its files; when the list is empty
	 * it is deleted with the check that reads it.
	 */
	const LEGACY_SYNTAX_PENDING = array(
		'adm/style/acp_donationcampaigns_campaigns.html',
		'adm/style/acp_donationcampaigns_donations.html',
		'adm/style/acp_donationcampaigns_settings.html',
	);

	/**
	 * SHA-256 of the license text both license files must carry.
	 *
	 * It is the license.txt of the official phpBB Skeleton Extension 1.2.3,
	 * downloaded from phpbb.com (customise/db/official_tool/ext_skeleton) on
	 * 2026-10-04; byte-identical to the GitHub tag 1.2.3 of
	 * phpbb-extensions/phpbb-ext-skeleton and to EPV's reference
	 * src/Resources/gpl-2.0.txt, so EPV's similarity check reports 100 %.
	 * Copied byte for byte, never retyped.
	 */
	const SKELETON_LICENSE_SHA256 = 'd8c320ffc0030d1b096ae4732b50d2b811cf95e9a9b7377c1127b2563e0a0388';

	/**
	 * The one character list a whitespace trim may use, as written in the
	 * source: PHP 8.6's default for trim(), ltrim() and rtrim().
	 *
	 * PHP 8.6 added the form feed to the default list, so a call without a
	 * second argument trims differently on 8.2-8.5 than on 8.6 (EC
	 * PHPCompatibility X, NewTrimCharactersDefault.NotSet). Naming the list
	 * makes every supported version trim like 8.6.
	 */
	const TRIM_CHARACTERS = '" \f\n\r\t\v\0"';

	/**
	 * Whitespace trims that deliberately use another character list.
	 *
	 * 'path/relative/to/the/package.php:line' => 'reason'. Empty on purpose:
	 * add an entry only with a written reason.
	 */
	const TRIM_CHARACTERS_ALLOWLIST = array();

	/** @var string */
	protected $package;

	public function setUp(): void
	{
		parent::setUp();

		$this->package = dirname(dirname(__DIR__));
	}

	/**
	 * Production PHP files, excluding tests.
	 *
	 * @return array
	 */
	public function production_files()
	{
		$package = dirname(dirname(__DIR__));
		$files = array();

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($package));

		foreach ($iterator as $file)
		{
			$path = $file->getPathname();

			if (substr($path, -4) !== '.php' || strpos($path, '/tests/') !== false)
			{
				continue;
			}

			$files[str_replace($package . '/', '', $path)] = array($path);
		}

		ksort($files);

		return $files;
	}

	/**
	 * @param string $path
	 * @return string Source with comments and docblocks removed
	 */
	protected function code_of($path)
	{
		$source = file_get_contents($path);

		$code = '';

		foreach (token_get_all($source) as $token)
		{
			if (is_array($token) && in_array($token[0], array(T_COMMENT, T_DOC_COMMENT), true))
			{
				continue;
			}

			$code .= is_array($token) ? $token[1] : $token;
		}

		return $code;
	}

	/**
	 * SQL belongs to repositories. A service or a module that grew a query
	 * would bypass the transaction boundaries and the ordering guarantees that
	 * everything else depends on.
	 *
	 * @dataProvider production_files
	 */
	public function test_only_repositories_and_migrations_contain_sql($path)
	{
		$relative = str_replace($this->package . '/', '', $path);

		if (strpos($relative, 'repository/') === 0 || strpos($relative, 'migrations/') === 0)
		{
			$this->assertTrue(true, 'Repositories and migrations own the SQL');

			return;
		}

		$code = $this->code_of($path);

		foreach (array('sql_query', 'sql_build_array', 'sql_in_set', 'SELECT ', 'INSERT INTO', 'DELETE FROM', 'UPDATE ') as $fragment)
		{
			$this->assertStringNotContainsString($fragment, $code, "{$relative} contains SQL: {$fragment}");
		}
	}

	/**
	 * Money is integer minor units everywhere. A float in this path loses a
	 * cent on an ordinary value: (int) ('8.70' * 100) is 869.
	 *
	 * @dataProvider production_files
	 */
	public function test_no_floating_point_money_arithmetic($path)
	{
		$relative = str_replace($this->package . '/', '', $path);
		$code = $this->code_of($path);

		foreach (array('(float)', '(double)', 'floatval', 'round(', 'number_format') as $fragment)
		{
			$this->assertStringNotContainsString($fragment, $code, "{$relative} uses floating-point arithmetic: {$fragment}");
		}
	}

	/**
	 * collected_amount is derived from SUM(). Only the repository setter may
	 * write it, and only donation_service may call that setter.
	 *
	 * @dataProvider production_files
	 */
	public function test_only_the_donation_service_writes_the_campaign_total($path)
	{
		$relative = str_replace($this->package . '/', '', $path);

		$allowed = array(
			'service/donation_service.php',
			'repository/campaign_repository.php',
		);

		if (in_array($relative, $allowed, true))
		{
			$this->assertTrue(true);

			return;
		}

		$this->assertStringNotContainsString(
			'set_collected_amount',
			$this->code_of($path),
			"{$relative} writes the campaign total directly"
		);
	}

	/**
	 * The total is never adjusted by arithmetic — it is always recomputed.
	 * See ADR-003.
	 *
	 * @dataProvider production_files
	 */
	public function test_the_total_is_never_delta_adjusted($path)
	{
		$relative = str_replace($this->package . '/', '', $path);
		$code = $this->code_of($path);

		foreach (array('collected_amount +', 'collected_amount -', "collected_amount'] +", "collected_amount'] -") as $fragment)
		{
			$this->assertStringNotContainsString($fragment, $code, "{$relative} adjusts the total by delta");
		}
	}

	/**
	 * Escaping lives in the templates now, so PHP must not do it too — a value
	 * escaped in both places renders as visible entities. The only permitted
	 * PHP-side escaper is utf8_htmlspecialchars(), and only for the two sinks
	 * that have no template boundary: confirm_box() messages and admin-log
	 * parameters.
	 *
	 * @dataProvider production_files
	 */
	public function test_no_production_file_calls_htmlspecialchars_directly($path)
	{
		$relative = str_replace($this->package . '/', '', $path);
		$code = $this->code_of($path);

		// utf8_htmlspecialchars() contains the substring, so strip it first.
		$allowed = array('utf8_htmlspecialchars');

		// htmlspecialchars_decode() is the opposite operation, and exactly one
		// file needs it: the posting listener turns core's escaped subject
		// back into raw text before it becomes a campaign title (ADR-019).
		if ($relative === 'event/posting_listener.php')
		{
			$allowed[] = 'htmlspecialchars_decode';
		}

		$without_wrapper = str_replace($allowed, '', $code);

		$this->assertStringNotContainsString(
			'htmlspecialchars',
			$without_wrapper,
			"{$relative} calls htmlspecialchars() directly; use |e in the template, or utf8_htmlspecialchars() for a sink"
		);
	}

	/**
	 * A frontend template may only use language keys that exist on the
	 * frontend: the extension's own files, or phpBB's frontend common.php.
	 * phpBB's EDIT lives in acp/common.php, so {L_EDIT} rendered the raw key
	 * "EDIT" in the donation list on a German board — found live in beta2.
	 */
	public function test_frontend_templates_use_only_frontend_language_keys()
	{
		global $phpbb_root_path;

		$lang = array();
		include $phpbb_root_path . 'language/en/common.php';
		include $this->package . '/language/en/common.php';
		include $this->package . '/language/en/info_acp_donationcampaigns.php';

		$files = glob($this->package . '/styles/prosilver/template/*.html');
		$files = array_merge($files, glob($this->package . '/styles/prosilver/template/event/*.html'));
		$this->assertNotEmpty($files);

		foreach ($files as $file)
		{
			// {L_KEY} in legacy markup, lang('KEY') in Twig partials.
			preg_match_all('/\{L_([A-Z0-9_]+)\}|lang\(\'([A-Z0-9_]+)\'\)/', file_get_contents($file), $matches);

			foreach (array_unique(array_filter(array_merge($matches[1], $matches[2]))) as $key)
			{
				$this->assertArrayHasKey(
					$key,
					$lang,
					basename($file) . " uses L_{$key}, which is not defined for the frontend"
				);
			}
		}
	}

	/**
	 * Every DISPLAYED amount goes through format_money(), which owns the
	 * symbol, its side and its separator (ADR-017). A bare format() call on a
	 * display path is how the management pages ended up without a symbol.
	 * format_for_input() and parse() remain for form fields.
	 *
	 * @dataProvider production_files
	 */
	public function test_displayed_amounts_always_carry_the_symbol($path)
	{
		$relative = str_replace($this->package . '/', '', $path);

		$this->assertDoesNotMatchRegularExpression(
			'/formatter->format\(/',
			file_get_contents($path),
			"{$relative} formats an amount for display without its currency symbol; use format_money()"
		);
	}

	/**
	 * The beta1 moderator permissions were replaced by forum permissions
	 * (ADR-016). Only the two migrations that create and retire them may still
	 * name them; anywhere else a leftover name would be a check that can never
	 * pass, or documentation that sends an administrator to the wrong tab.
	 *
	 * @dataProvider production_files
	 */
	public function test_the_retired_moderator_permissions_are_named_only_by_their_migrations($path)
	{
		$relative = str_replace($this->package . '/', '', $path);

		if (in_array($relative, array('migrations/v10x/m7_manage_permissions.php', 'migrations/v10x/m8_forum_permissions.php'), true))
		{
			$this->addToAssertionCount(1);
			return;
		}

		$this->assertStringNotContainsString(
			'm_donationcampaigns',
			file_get_contents($path),
			"{$relative} still names a retired m_donationcampaigns_* permission"
		);
	}

	/**
	 * Every administrator-controlled scalar in an ACP template carries |e.
	 */
	public function test_every_acp_template_escapes_its_administrator_controlled_values()
	{
		$must_escape = array(
			'DONATIONCAMPAIGNS_CURRENCY_CODE', 'DONATIONCAMPAIGNS_CURRENCY_SYMBOL',
			'DONATIONCAMPAIGNS_CAMPAIGN_TITLE', 'DONATIONCAMPAIGNS_TARGET_AMOUNT',
			'DONATIONCAMPAIGNS_COLLECTED_AMOUNT', 'DONATIONCAMPAIGNS_EXTERNAL_URL',
			'DONATIONCAMPAIGNS_DONATION_AMOUNT',
			'DONATIONCAMPAIGNS_DONOR_NAME', 'DONATIONCAMPAIGNS_DONATION_TIME',
			// donationcampaigns_row.TOPIC_TITLE is deliberately absent: it is a
			// core-escaped field, see CORE_ESCAPED_FIELDS.
			'donationcampaigns_row.TITLE',
			'donationcampaigns_donation.DONOR_NAME', 'donationcampaigns_donation.AMOUNT',
			'donationcampaigns_error.MESSAGE',
		);

		$checked = 0;

		foreach (glob($this->package . '/adm/style/*.html') as $file)
		{
			foreach ($this->outputs($file) as $output)
			{
				if (in_array($output['var'], $must_escape, true))
				{
					$checked++;
					$this->assertContains('e', $output['filters'], basename($file) . " renders {$output['var']} without |e");
				}
			}
		}

		// A rule that finds nothing to check proves nothing.
		$this->assertGreaterThan(0, $checked);
	}

	/**
	 * Every value a template prints: {{ var|filter… }} in the form phpBB's
	 * lexer hands to Twig, so legacy {VAR|e} and native {{ VAR|e }} read alike.
	 *
	 * @param string $file
	 * @return array[] each array('var' => string, 'filters' => string[])
	 */
	protected function outputs($file)
	{
		$lexed = \uflagmey\donationcampaigns\tests\template_renderer::lexed(file_get_contents($file));
		preg_match_all('/\{\{-?\s*([A-Za-z_][A-Za-z0-9_.]*)\s*((?:\|\s*[a-z_]+(?:\([^)]*\))?\s*)*)-?\}\}/', $lexed, $matches, PREG_SET_ORDER);

		$outputs = array();
		foreach ($matches as $match)
		{
			preg_match_all('/\|\s*([a-z_]+)/', $match[2], $filters);
			$outputs[] = array('var' => $match[1], 'filters' => $filters[1]);
		}

		return $outputs;
	}

	/**
	 * The description is the ONE administrator-controlled value that must NOT
	 * carry |e, and only inside the textarea.
	 *
	 * generate_text_for_edit() hands back text that is already HTML-escaped
	 * once, meant to be emitted raw so the browser decodes exactly that layer.
	 * Escaping it again put a second layer in the markup that the browser only
	 * half removed, so each edit/save cycle stored one more &amp; than the
	 * last. Core renders {MESSAGE} the same way in posting_editor.html.
	 *
	 * It is safe for the same reason it is safe in core: entities are intact,
	 * so markup arrives as literal text and cannot close the textarea.
	 */
	public function test_the_description_textarea_does_not_double_escape()
	{
		// The campaign form moved to the frontend in the RC2 cutover, and its
		// fields into a shared include in beta3 (frontend form + posting
		// panel); the textarea contract is unchanged.
		$fields = $this->package . '/styles/prosilver/template/donationcampaigns_campaign_fields.html';

		$desc = array_filter($this->outputs($fields), function ($output) {
			return $output['var'] === 'DONATIONCAMPAIGNS_DESC';
		});
		$this->assertCount(1, $desc, 'The description is printed exactly once, in the textarea');
		$this->assertSame(array(), reset($desc)['filters'], 'The description textarea escapes text that generate_text_for_edit() already escaped');

		// Rendered: the one escaped layer reaches the textarea unchanged.
		$html = \uflagmey\donationcampaigns\tests\template_renderer::render(
			file_get_contents($this->package . '/styles/prosilver/template/donationcampaigns_campaign_form.html'),
			array('DONATIONCAMPAIGNS_DESC' => '&lt;b&gt; &amp;amp;')
		);
		$this->assertStringContainsString('>&lt;b&gt; &amp;amp;</textarea>', $html);

		// No ACP template renders the description at all now.
		foreach (glob($this->package . '/adm/style/*.html') as $file)
		{
			foreach ($this->outputs($file) as $output)
			{
				$this->assertNotSame('DONATIONCAMPAIGNS_DESC', $output['var'], basename($file));
			}
		}
	}

	/**
	 * The other half of the escaping contract: a core-escaped field must NOT
	 * carry |e, in any shipped template, frontend or ACP.
	 */
	public function test_core_escaped_fields_are_not_escaped_again()
	{
		$files = array_merge(
			glob($this->package . '/adm/style/*.html'),
			glob($this->package . '/styles/prosilver/template/*.html'),
			glob($this->package . '/styles/prosilver/template/event/*.html')
		);
		$this->assertNotEmpty($files);

		$printed = array();

		foreach ($files as $file)
		{
			foreach ($this->outputs($file) as $output)
			{
				foreach (self::CORE_ESCAPED_FIELDS as $field)
				{
					if (substr($output['var'], -strlen($field)) === $field)
					{
						$printed[] = $output['var'];
						$this->assertNotContains('e', $output['filters'], basename($file) . " escapes {$output['var']}, which core already stored escaped");
					}
				}
			}
		}

		// Finding none would mean the rule no longer looks at anything.
		$this->assertNotEmpty($printed);
	}

	public function test_no_acp_template_marks_a_value_safe()
	{
		foreach (glob($this->package . '/adm/style/*.html') as $file)
		{
			$contents = file_get_contents($file);

			$this->assertStringNotContainsString('|raw', $contents, basename($file) . ' marks a value safe');
			$this->assertStringNotContainsString('autoescape', $contents);
		}
	}

	/**
	 * Twig autoescaping is off board-wide. Re-enabling it globally would
	 * double-escape every core template, and marking values safe would defeat
	 * the contract entirely.
	 *
	 * @dataProvider production_files
	 */
	public function test_template_escaping_is_never_bypassed($path)
	{
		$relative = str_replace($this->package . '/', '', $path);
		$code = $this->code_of($path);

		foreach (array('autoescape', 'raw|', '|raw', 'setEscaper', 'Markup(') as $fragment)
		{
			$this->assertStringNotContainsString($fragment, $code, "{$relative} tampers with escaping: {$fragment}");
		}
	}

	/**
	 * Plain request text is read raw and escaped at output. variable() escapes
	 * on the way IN, which would store &amp; for an administrator who typed &.
	 * The one legitimate use is the campaign description, which then goes
	 * through phpBB's BBCode storage encoder.
	 */
	public function test_only_the_description_is_read_through_the_escaping_request_method()
	{
		$code = $this->code_of($this->package . '/acp/main_module.php');

		preg_match_all('/\$request->variable\(\s*\'([a-z_]+)\'/', $code, $matches);

		$expected = array(
			// Read as an integer or a flag, where escaping is irrelevant.
			// 't' is the topic context; it is additionally shape-checked with
			// is_scalar() before casting, because an array reaching (int)
			// becomes 1 rather than 0.
			'action', 'campaign_id', 'donation_id', 'topic_id', 'start', 't',
			// The state the form was drawn for. Compared as an integer and
			// never written, so escaping is irrelevant here too.
			'expected_campaign_id',
			'campaign_enabled', 'show_donor_names', 'show_donation_count', 'donation_public',
			// The only free text, and only because the BBCode encoder follows.
			'campaign_desc',
		);

		foreach ($matches[1] as $name)
		{
			$this->assertContains($name, $expected, "{$name} is read with variable(), which escapes it on input");
		}
	}

	/**
	 * The same rule for the shared campaign form reader (beta3): free text
	 * raw, only the description and the flags through variable().
	 */
	public function test_the_campaign_form_reads_free_text_raw()
	{
		$code = $this->code_of($this->package . '/service/campaign_form.php');

		preg_match_all('/->variable\(\$prefix \. \'([a-z_]+)\'/', $code, $matches);

		$this->assertNotEmpty($matches[1]);

		foreach ($matches[1] as $name)
		{
			$this->assertContains($name, array('campaign_desc', 'show_donor_names', 'show_donation_count', 'show_donation_date'), "{$name} is read with variable(), which escapes it on input");
		}
	}

	/**
	 * No production file may reach into the database handle except to open a
	 * transaction. The services take $db for exactly that.
	 *
	 * @dataProvider production_files
	 */
	public function test_the_database_handle_is_used_only_for_transactions($path)
	{
		$relative = str_replace($this->package . '/', '', $path);

		if (strpos($relative, 'repository/') === 0 || strpos($relative, 'migrations/') === 0)
		{
			$this->assertTrue(true);

			return;
		}

		$code = $this->code_of($path);

		preg_match_all('/\$this->db->([a-z_]+)\(/', $code, $matches);

		$used = array_unique($matches[1]);

		$this->assertSame(
			array(),
			array_diff($used, array('sql_transaction')),
			"{$relative} uses the database handle for something other than a transaction"
		);
	}

	/**
	 * Every physical database identifier stays within Oracle's 30-byte limit.
	 * phpBB validates column and index names but NOT table names, so an
	 * over-long table fails as a raw driver error during installation.
	 */
	public function test_every_database_identifier_fits_oracle()
	{
		$migration = file_get_contents($this->package . '/migrations/v10x/m1_initial_schema.php');

		preg_match_all("/'(ufdc_[a-z_]+)'|'(dc_[a-z_]+)'/", $migration, $matches);

		$identifiers = array_filter(array_merge($matches[1], $matches[2]));

		$this->assertNotEmpty($identifiers);

		foreach ($identifiers as $identifier)
		{
			// Table names carry the board prefix; index names carry the table.
			$physical = 'phpbb_' . $identifier;

			$this->assertLessThanOrEqual(
				30,
				strlen($physical),
				"{$physical} exceeds Oracle's 30-byte identifier limit"
			);
		}
	}

	/**
	 * A donor row carries a computed display name and amount — plus, when the
	 * campaign opted in, a formatted date — and nothing else.
	 * The name and amount are worked out ABOVE the assignment, so no raw storage
	 * column reaches the template: a private donor's stored name cannot leak,
	 * and no identifier or bbcode metadata rides along. The listener is the only
	 * thing that writes to the public template.
	 */
	public function test_the_public_listener_exposes_only_computed_donor_fields()
	{
		$code = $this->code_of($this->package . '/event/viewtopic_listener.php');

		// The donor row is built here, from the array literal to the block
		// assignment; its keys are NAME, AMOUNT and the optional DATE, assigned
		// from values computed above, never from a raw row field.
		preg_match('/\$row = array\(.*?assign_block_vars\(\s*\'donationcampaigns_donor\',\s*\$row\);/s', $code, $match);

		$this->assertNotEmpty($match, 'The donor block assignment could not be found');

		foreach (array('donor_name', 'donation_amount', 'donation_public', 'donation_id', 'campaign_id', 'bbcode') as $forbidden)
		{
			$this->assertStringNotContainsString($forbidden, $match[0], "A donor row exposes {$forbidden}");
		}
	}

	/**
	 * ADR-018: the board list controller is the third controller precisely
	 * because it is NOT a write path. That is enforced here, not promised:
	 * no write method, no form key, no POST handling.
	 */
	public function test_the_list_controller_writes_nothing()
	{
		$code = $this->code_of($this->package . '/controller/list_controller.php');

		foreach (array(
			'create_campaign', 'update_campaign', 'delete_campaign', 'purge_for', 'insert(', 'update(',
			'add_donation', 'update_donation', 'delete_donation', 'recalculate',
			'check_form_key', 'add_form_key', 'is_set_post', 'confirm_box', '->log',
		) as $fragment)
		{
			$this->assertStringNotContainsString($fragment, $code, "The read-only list controller contains {$fragment}");
		}
	}

	/**
	 * Every shipped template, relative to the package.
	 *
	 * @return string[]
	 */
	protected function shipped_templates()
	{
		$templates = array();

		foreach (array('adm/style', 'styles') as $root)
		{
			$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->package . '/' . $root, \FilesystemIterator::SKIP_DOTS));

			foreach ($files as $file)
			{
				if (substr($file->getFilename(), -5) === '.html')
				{
					$templates[] = substr($file->getPathname(), strlen($this->package) + 1);
				}
			}
		}

		sort($templates);

		return $templates;
	}

	/**
	 * Every piece of phpBB legacy template syntax in a source: the comment
	 * tags phpBB's lexer turns into Twig tags, and anything its variable
	 * patterns rewrite (phpbb/template/twig/lexer.php:111, :118, :122, :126,
	 * :324). Searched everywhere, comments included: the lexer rewrites a
	 * {WORD} inside a comment too, which is how {MESSAGE} in an HTML comment
	 * printed the post text (beta4 F1).
	 *
	 * @param string $source
	 * @return string[]
	 */
	protected function legacy_syntax($source)
	{
		$patterns = array(
			'/<!--\s*(?:IF|ELSE ?IF|ELSE|ENDIF|BEGIN|BEGINELSE|END|INCLUDE|INCLUDEJS|INCLUDECSS|INCLUDEPHP|DEFINE|UNDEFINE|ENDDEFINE|EVENT|PHP|ENDPHP)\b.*?-->/s',
			'/\{[a-zA-Z0-9_.]+(?:\|[^}]+?)?\}/',
			'/\{\$[a-zA-Z0-9_.]+\}/',
		);

		$found = array();

		foreach ($patterns as $pattern)
		{
			preg_match_all($pattern, $source, $matches);
			$found = array_merge($found, $matches[0]);
		}

		return $found;
	}

	/**
	 * Native Twig only, as the phpBB Skeleton Extension writes templates and
	 * as the Extension Check team asked for beta4.
	 */
	public function test_templates_use_native_twig_only()
	{
		$templates = $this->shipped_templates();
		$this->assertNotEmpty($templates);

		// Collected, so one run names every offending template.
		$problems = array();

		foreach ($templates as $template)
		{
			$found = $this->legacy_syntax(file_get_contents($this->package . '/' . $template));
			$pending = in_array($template, self::LEGACY_SYNTAX_PENDING, true);

			if ($pending && !$found)
			{
				$problems[] = "{$template} is native Twig now: remove it from LEGACY_SYNTAX_PENDING";
			}
			else if (!$pending && $found)
			{
				$problems[] = "{$template} uses phpBB's legacy template syntax (" . count($found) . 'x), e.g. ' . implode(' ', array_slice(array_unique($found), 0, 3));
			}
		}

		$this->assertSame(array(), $problems);
	}

	/**
	 * No HTML comment reaches a visitor: developer notes are Twig comments,
	 * which render as nothing. Second stage of the guard. A template still on
	 * LEGACY_SYNTAX_PENDING may keep phpBB's legacy tags, which only look like
	 * comments; nothing else may start with "<!--". With the list gone, no
	 * "<!--" at all.
	 */
	public function test_templates_carry_no_html_comments()
	{
		$legacy_tag = '/^<!--\s*(?:IF|ELSE ?IF|ELSE|ENDIF|BEGIN|BEGINELSE|END|INCLUDE|INCLUDEJS|INCLUDECSS|INCLUDEPHP|DEFINE|UNDEFINE|ENDDEFINE|EVENT|PHP|ENDPHP)\b/';
		$problems = array();

		foreach ($this->shipped_templates() as $template)
		{
			$pending = in_array($template, self::LEGACY_SYNTAX_PENDING, true);
			preg_match_all('/<!--.*?(?:-->|\z)/s', file_get_contents($this->package . '/' . $template), $comments);

			foreach ($comments[0] as $comment)
			{
				if ($pending && preg_match($legacy_tag, $comment))
				{
					continue;
				}

				$problems[] = $template . ': ' . substr(preg_replace('/\s+/', ' ', $comment), 0, 60);
			}
		}

		$this->assertSame(array(), $problems);
	}

	public function test_the_pending_list_names_only_shipped_templates()
	{
		$this->assertSame(array(), array_diff(self::LEGACY_SYNTAX_PENDING, $this->shipped_templates()));
	}

	/**
	 * @return array
	 */
	public function legacy_syntax_samples()
	{
		return array(
			'IF'					=> array('<!-- IF S_FLAG -->'),
			'IF on a block'			=> array('<!-- IF .block -->'),
			'ELSE'					=> array('<!-- ELSE -->'),
			'ELSEIF'				=> array('<!-- ELSEIF S_FLAG -->'),
			'ENDIF'					=> array('<!-- ENDIF -->'),
			'BEGIN'					=> array('<!-- BEGIN block -->'),
			'BEGINELSE'				=> array('<!-- BEGINELSE -->'),
			'END'					=> array('<!-- END block -->'),
			'INCLUDE'				=> array('<!-- INCLUDE overall_header.html -->'),
			'INCLUDEJS'				=> array('<!-- INCLUDEJS script.js -->'),
			'INCLUDECSS'			=> array('<!-- INCLUDECSS @vendor_package/style.css -->'),
			'DEFINE'				=> array('<!-- DEFINE $X = 1 -->'),
			'EVENT'					=> array('<!-- EVENT some_event -->'),
			'language variable'		=> array('{L_KEY}'),
			'JS language variable'	=> array('{LA_KEY}'),
			'variable'				=> array('{VAR}'),
			'filtered variable'		=> array('{VAR|e}'),
			'block variable'		=> array('{block.VAR}'),
			'defined variable'		=> array('{$VAR}'),
			'in an HTML comment'	=> array('<!-- as core does with {MESSAGE} -->'),
			'in a Twig comment'		=> array('{# as core does with {MESSAGE} #}'),
			'unspaced Twig print'	=> array('{{VAR}}'),
		);
	}

	/**
	 * The guard recognises every legacy construct the extension could use.
	 *
	 * @dataProvider legacy_syntax_samples
	 */
	public function test_the_legacy_syntax_guard_finds($source)
	{
		$this->assertNotEmpty($this->legacy_syntax($source));
	}

	/**
	 * ...and leaves native Twig, plain HTML comments and Twig hash literals
	 * alone, so it does not simply reject everything.
	 */
	public function test_the_legacy_syntax_guard_accepts_native_twig()
	{
		$twig = "{% if S_FLAG %}{{ VAR }}{{ VAR|e }}{% else %}{{ lang('KEY') }}{{ lang('KEY')|e('js') }}{% endif %}\n"
			. "{% for row in loops.block %}{{ row.VAR }}{% else %}-{% endfor %}\n"
			. "{% include 'overall_header.html' %}{% include '@vendor_package/x.html' with {'prefix': '', 'step': row.STEP} only %}\n"
			. "{% INCLUDECSS '@vendor_package/style.css' %}{% INCLUDEJS 'script.js' %}\n"
			. "{# a Twig comment #}<!-- an HTML comment -->";

		$this->assertSame(array(), $this->legacy_syntax($twig));
	}

	/**
	 * The list is an overview; donors stay in the topic (C5).
	 */
	public function test_the_list_template_names_no_donor()
	{
		$template = file_get_contents($this->package . '/styles/prosilver/template/donationcampaigns_list.html');

		// ADR-013: no inline CSS. prosilver hides .responsive-show with an
		// inline style; the list does it with its own class instead.
		$this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $template);
		$this->assertStringContainsString('class="responsive-show donationcampaigns-list-forum"', $template);
		$this->assertMatchesRegularExpression('/\.donationcampaigns-list-forum\s*\{\s*display:\s*none;/', file_get_contents($this->package . '/styles/prosilver/theme/donationcampaigns.css'));

		// Template variables only — prose in a comment may say "donor". Every
		// name in a print or a tag, as phpBB's lexer hands the file to Twig:
		// the include's arguments count as much as a printed value.
		$lexed = \uflagmey\donationcampaigns\tests\template_renderer::lexed($template);
		$lexed = preg_replace("/'[^']*'/", '', $lexed);
		preg_match_all('/\{[{%].*?[}%]\}/s', $lexed, $tags);
		preg_match_all('/[A-Za-z_][A-Za-z0-9_.]*/', implode(' ', $tags[0]), $matches);
		$this->assertNotEmpty($matches[0]);

		foreach (array_unique($matches[0]) as $var)
		{
			foreach (array('DONOR', 'DESC', '_URL') as $forbidden)
			{
				$this->assertStringNotContainsStringIgnoringCase($forbidden, $var, "The list template renders {$var}");
			}
		}
	}

	/**
	 * Every service and listener is registered, or it silently does nothing on
	 * a real board while every unit test passes.
	 */
	public function test_every_listener_is_registered_in_the_container()
	{
		$services = file_get_contents($this->package . '/config/services.yml');

		foreach (glob($this->package . '/event/*.php') as $listener)
		{
			$class = basename($listener, '.php');

			$this->assertStringContainsString(
				'event\\' . $class,
				$services,
				"Listener {$class} is not registered and will never fire"
			);
		}
	}

	public function test_every_service_class_is_registered_in_the_container()
	{
		$services = file_get_contents($this->package . '/config/services.yml');

		foreach (glob($this->package . '/service/*.php') as $service)
		{
			$class = basename($service, '.php');

			$this->assertStringContainsString(
				'service\\' . $class,
				$services,
				"Service {$class} is not registered"
			);
		}
	}

	public function test_every_repository_class_is_registered_in_the_container()
	{
		$services = file_get_contents($this->package . '/config/services.yml');

		foreach (glob($this->package . '/repository/*.php') as $repository)
		{
			$class = basename($repository, '.php');

			$this->assertStringContainsString(
				'repository\\' . $class,
				$services,
				"Repository {$class} is not registered"
			);
		}
	}

	/**
	 * Every trim(), ltrim() and rtrim() names its characters. A whitespace
	 * trim uses exactly TRIM_CHARACTERS; a list without any whitespace
	 * character (e.g. ltrim($digits, '0')) is not a whitespace trim and may
	 * differ. Anything the source cannot show (a variable, a constant) fails
	 * unless it is on the allowlist.
	 *
	 * @dataProvider production_files
	 */
	public function test_every_trim_names_its_characters($path)
	{
		$relative = str_replace($this->package . '/', '', $path);
		$tokens = token_get_all(file_get_contents($path));
		$violations = array();

		foreach ($tokens as $i => $token)
		{
			if (!is_array($token) || !in_array($token[0], array(T_STRING, T_NAME_FULLY_QUALIFIED), true)
				|| !in_array(strtolower(ltrim($token[1], '\\')), array('trim', 'ltrim', 'rtrim'), true))
			{
				continue;
			}

			$previous = $this->neighbour($tokens, $i, -1);
			$next = $this->neighbour($tokens, $i, 1);

			// A method or function of that name, or a declaration, is not the PHP function.
			if ($next === null || $tokens[$next] !== '('
				|| ($previous !== null && is_array($tokens[$previous]) && in_array($tokens[$previous][0], array(T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION), true)))
			{
				continue;
			}

			$where = $relative . ':' . $token[2];
			$characters = $this->second_argument($tokens, $next);

			if (isset(self::TRIM_CHARACTERS_ALLOWLIST[$where]))
			{
				continue;
			}

			if ($characters === '')
			{
				$violations[] = "{$where} {$token[1]}() has no character list";
			}
			else if ($characters !== self::TRIM_CHARACTERS && !$this->is_literal_without_whitespace($characters))
			{
				$violations[] = "{$where} {$token[1]}() trims {$characters}, expected " . self::TRIM_CHARACTERS;
			}
		}

		$this->assertSame(array(), $violations, implode("\n", $violations));
	}

	/**
	 * @param array $tokens
	 * @param int   $i
	 * @param int   $step -1 or 1
	 * @return int|null Index of the nearest token that is not whitespace or a comment
	 */
	protected function neighbour(array $tokens, $i, $step)
	{
		for ($j = $i + $step; isset($tokens[$j]); $j += $step)
		{
			if (!is_array($tokens[$j]) || !in_array($tokens[$j][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true))
			{
				return $j;
			}
		}

		return null;
	}

	/**
	 * @param array $tokens
	 * @param int   $open Index of the opening parenthesis of the call
	 * @return string Source of the second argument without surrounding whitespace, '' if there is none
	 */
	protected function second_argument(array $tokens, $open)
	{
		$depth = 0;
		$argument = 0;
		$source = '';

		for ($j = $open; isset($tokens[$j]); $j++)
		{
			$text = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];

			if (in_array($text, array('(', '[', '{'), true) || (is_array($tokens[$j]) && in_array($tokens[$j][0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true)))
			{
				$depth++;
			}
			else if (in_array($text, array(')', ']', '}'), true))
			{
				$depth--;

				if ($depth === 0)
				{
					break;
				}
			}
			else if ($text === ',' && $depth === 1)
			{
				$argument++;
				continue;
			}

			if ($argument === 1 && $depth >= 1)
			{
				$source .= $text;
			}
		}

		return trim($source, " \f\n\r\t\v\0");
	}

	/**
	 * @param string $source
	 * @return bool True for a plain string literal that contains no whitespace character
	 */
	protected function is_literal_without_whitespace($source)
	{
		if (!preg_match('/^\'([^\'\\\\]*)\'$/', $source, $match) && !preg_match('/^"([^"\\\\$]*)"$/', $source, $match))
		{
			return false;
		}

		return strpbrk($match[1], " \f\n\r\t\v\0") === false;
	}

	/**
	 * Shipped markup uses HTML5 syntax: no self-closing "/>" and no quoted
	 * boolean attribute such as checked="checked". The phpBB Extension Check
	 * (XHTMLcheck) warns on both in html, php and js files.
	 *
	 * @dataProvider markup_files
	 */
	public function test_shipped_markup_uses_html5_syntax($path)
	{
		$relative = str_replace($this->package . '/', '', $path);
		$violations = array();

		foreach (file($path) as $number => $line)
		{
			// Any "/>" counts: a template condition inside a tag
			// (<input <!-- IF X -->checked<!-- ENDIF --> />) defeats a tag pattern.
			if (strpos($line, '/>') !== false)
			{
				$violations[] = $relative . ':' . ($number + 1) . ' self-closing tag';
			}

			if (preg_match('#(?<![\w$>-])(checked|selected|disabled|readonly|multiple|required)\s*=\s*["\']#i', $line))
			{
				$violations[] = $relative . ':' . ($number + 1) . ' quoted boolean attribute';
			}
		}

		$this->assertSame(array(), $violations, implode("\n", $violations));
	}

	/**
	 * Shipped html, php and js files, excluding tests.
	 *
	 * @return array
	 */
	public function markup_files()
	{
		$package = dirname(dirname(__DIR__));
		$files = array();

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($package));

		foreach ($iterator as $file)
		{
			$path = $file->getPathname();

			if (!in_array(pathinfo($path, PATHINFO_EXTENSION), array('html', 'php', 'js'), true) || strpos($path, '/tests/') !== false)
			{
				continue;
			}

			$files[str_replace($package . '/', '', $path)] = array($path);
		}

		ksort($files);

		return $files;
	}

	/**
	 * The shipped license.txt and the repository LICENSE are the phpBB
	 * skeleton's license text, unchanged — the text the Extension Check
	 * compares against.
	 */
	public function test_the_license_is_the_phpbb_skeleton_text()
	{
		$shipped = $this->package . '/license.txt';
		// The repository root holds LICENSE; the package is ext/uflagmey/donationcampaigns.
		$repository = dirname(dirname(dirname($this->package))) . '/LICENSE';

		$this->assertFileExists($shipped);
		$this->assertFileExists($repository);
		$this->assertFileEquals($shipped, $repository, 'license.txt and LICENSE differ');
		$this->assertSame(self::SKELETON_LICENSE_SHA256, hash_file('sha256', $shipped), 'license.txt is not the skeleton text');
	}

	/**
	 * The package ships no development infrastructure.
	 */
	public function test_the_package_contains_no_local_environment_files()
	{
		foreach (array('docker-compose.yml', 'Dockerfile', 'install-config.yml', '.env') as $name)
		{
			$this->assertFileDoesNotExist($this->package . '/' . $name);
		}
	}
}
