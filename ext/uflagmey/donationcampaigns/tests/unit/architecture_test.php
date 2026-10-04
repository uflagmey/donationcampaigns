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
		$without_wrapper = str_replace('utf8_htmlspecialchars', '', $code);

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

		foreach (glob($this->package . '/adm/style/*.html') as $file)
		{
			$contents = file_get_contents($file);

			foreach ($must_escape as $var)
			{
				$this->assertStringNotContainsString(
					'{' . $var . '}',
					$contents,
					basename($file) . " renders {$var} without |e"
				);
			}
		}
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
		// The campaign form moved to the frontend in the RC2 cutover; the
		// textarea contract is unchanged.
		$form = file_get_contents($this->package . '/styles/prosilver/template/donationcampaigns_campaign_form.html');

		$this->assertStringContainsString(
			'{DONATIONCAMPAIGNS_DESC}</textarea>',
			$form,
			'The description textarea escapes text that generate_text_for_edit() already escaped'
		);
		$this->assertStringNotContainsString('{DONATIONCAMPAIGNS_DESC|e}', $form);

		// No ACP template renders the description at all now.
		foreach (glob($this->package . '/adm/style/*.html') as $file)
		{
			$this->assertStringNotContainsString('{DONATIONCAMPAIGNS_DESC}', file_get_contents($file), basename($file));
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

		foreach ($files as $file)
		{
			preg_match_all('/\{([A-Za-z0-9_.]+)\|e\}/', file_get_contents($file), $matches);

			foreach ($matches[1] as $var)
			{
				foreach (self::CORE_ESCAPED_FIELDS as $field)
				{
					$this->assertStringEndsNotWith(
						$field,
						$var,
						basename($file) . " escapes {$var}, which core already stored escaped"
					);
				}
			}
		}
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

		// Template variables only — prose in a comment may say "donor".
		preg_match_all('/\{([A-Za-z0-9_.|]+)\}/', $template, $matches);
		$this->assertNotEmpty($matches[1]);

		foreach ($matches[1] as $var)
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
