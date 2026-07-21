<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\migration;

use uflagmey\donationcampaigns\migrations\v10x\m2_initial_config;

/**
 * Exercises the configuration migration through phpBB's real migration tool
 * rather than asserting against the array returned by update_data().
 *
 * An array assertion would confirm that the migration intends to create a key.
 * Running the tool confirms that it does, that it does so exactly once, and
 * that a second run leaves an administrator's edited value alone.
 */
class m2_initial_config_test extends \phpbb_test_case
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\migration\tool\config */
	protected $tool;

	/**
	 * The four extension-owned keys and their approved defaults.
	 */
	public function expected_config()
	{
		return array(
			// key, default value
			array('donationcampaigns_currency_code', 'EUR'),
			array('donationcampaigns_currency_symbol', '€'),
			array('donationcampaigns_currency_exponent', 2),
			array('donationcampaigns_donor_list_limit', 25),
		);
	}

	public function setUp(): void
	{
		parent::setUp();

		$this->config = new \phpbb\config\config(array());
		$this->tool = new \phpbb\db\migration\tool\config($this->config);
	}

	protected function create_migration()
	{
		return new m2_initial_config(
			$this->config,
			$this->getMockBuilder('\phpbb\db\driver\driver_interface')->getMock(),
			$this->getMockBuilder('\phpbb\db\tools\tools')->disableOriginalConstructor()->getMock(),
			'',
			'php',
			'phpbb_'
		);
	}

	/**
	 * Run the migration's update_data() steps through the real config tool.
	 */
	protected function apply_migration()
	{
		foreach ($this->create_migration()->update_data() as $step)
		{
			list($call, $arguments) = $step;
			$this->assertStringStartsWith(
				'config.',
				$call,
				"Migration step '{$call}' does not use the config tool. Configuration must "
				. 'be added through migration tools only, never by writing to the config table.'
			);

			$method = substr($call, strlen('config.'));
			call_user_func_array(array($this->tool, $method), $arguments);
		}
	}

	protected function revert_migration()
	{
		foreach ($this->create_migration()->revert_data() as $step)
		{
			list($call, $arguments) = $step;
			$method = substr($call, strlen('config.'));
			call_user_func_array(array($this->tool, $method), $arguments);
		}
	}

	public function test_migration_depends_on_the_schema_migration()
	{
		$this->assertSame(
			array('\uflagmey\donationcampaigns\migrations\v10x\m1_initial_schema'),
			m2_initial_config::depends_on()
		);
	}

	/**
	 * @dataProvider expected_config
	 */
	public function test_config_key_is_absent_before_installation($key, $default)
	{
		$this->assertFalse(
			isset($this->config[$key]),
			"Config key {$key} exists before the migration has run"
		);
	}

	/**
	 * @dataProvider expected_config
	 */
	public function test_config_key_is_created_with_its_documented_default($key, $default)
	{
		$this->apply_migration();

		$this->assertTrue(isset($this->config[$key]), "Config key {$key} was not created");
		$this->assertSame(
			(string) $default,
			(string) $this->config[$key],
			"Config key {$key} does not carry its documented default"
		);
	}

	public function test_exactly_four_extension_config_keys_are_created()
	{
		$this->apply_migration();

		$owned = $this->extension_config_keys();

		$this->assertCount(
			4,
			$owned,
			'Expected exactly four extension-owned config keys, found: ' . implode(', ', $owned)
		);
	}

	/**
	 * There must be no extension version config key.
	 *
	 * phpBB reads an extension's version from composer.json metadata
	 * ($meta['version'], $meta['extra']['version-check'] in acp_extensions),
	 * and migration state comes exclusively from the migrations table. A
	 * config copy would be a second version source with no reader, kept in
	 * sync by hand until it drifted.
	 */
	public function test_no_duplicate_version_config_key_is_created()
	{
		$this->apply_migration();

		$this->assertFalse(
			isset($this->config['donationcampaigns_version']),
			'A duplicate extension version config key was created. phpBB reads the '
			. 'version from composer.json; a config copy has no consumer and can drift.'
		);
	}

	/**
	 * Each key must be created exactly once. A duplicated step would be
	 * invisible in the config array but would show up as a repeated call.
	 */
	public function test_no_config_key_is_added_twice_by_the_migration()
	{
		$keys = array();

		foreach ($this->create_migration()->update_data() as $step)
		{
			list($call, $arguments) = $step;

			if ($call === 'config.add')
			{
				$keys[] = $arguments[0];
			}
		}

		$this->assertSame(
			array_unique($keys),
			$keys,
			'The migration adds the same config key more than once'
		);
		$this->assertCount(4, $keys);
	}

	/**
	 * Re-running must not reset a value an administrator has changed.
	 *
	 * phpBB does not revert migrations on disable, so a re-enable does not
	 * re-run update_data() — but the migration must be safe even if it does,
	 * because a repaired or re-applied installation would.
	 */
	public function test_reapplying_does_not_overwrite_administrator_edits()
	{
		$this->apply_migration();

		// An administrator changes the board currency.
		$this->config->set('donationcampaigns_currency_code', 'GBP');
		$this->config->set('donationcampaigns_currency_symbol', '£');
		$this->config->set('donationcampaigns_donor_list_limit', 50);

		$this->apply_migration();

		$this->assertSame('GBP', $this->config['donationcampaigns_currency_code']);
		$this->assertSame('£', $this->config['donationcampaigns_currency_symbol']);
		$this->assertSame('50', (string) $this->config['donationcampaigns_donor_list_limit']);
	}

	public function test_reapplying_does_not_duplicate_keys()
	{
		$this->apply_migration();
		$this->apply_migration();

		$this->assertCount(4, $this->extension_config_keys());
	}

	/**
	 * Reverting must remove the extension's keys and nothing else.
	 */
	public function test_revert_removes_only_extension_owned_keys()
	{
		// A key belonging to the board, which must survive.
		$this->config->set('board_contact', 'admin@example.org');
		$this->config->set('donation_unrelated_other_extension', 'keep me');

		$this->apply_migration();
		$this->assertCount(4, $this->extension_config_keys());

		$this->revert_migration();

		$this->assertCount(
			0,
			$this->extension_config_keys(),
			'Revert left extension-owned config keys behind'
		);
		$this->assertSame('admin@example.org', $this->config['board_contact']);
		$this->assertSame(
			'keep me',
			$this->config['donation_unrelated_other_extension'],
			'Revert removed a config key the extension does not own'
		);
	}

	public function test_effectively_installed_is_false_before_installation()
	{
		$this->assertFalse($this->create_migration()->effectively_installed());
	}

	public function test_effectively_installed_is_true_after_installation()
	{
		$this->apply_migration();

		$this->assertTrue($this->create_migration()->effectively_installed());
	}

	/**
	 * The exponent is a count of decimal digits and must be stored as an
	 * integer, not as a formatted or floating-point value.
	 */
	public function test_currency_exponent_is_an_integer_value()
	{
		$this->apply_migration();

		$stored = $this->config['donationcampaigns_currency_exponent'];

		$this->assertSame(
			(string) (int) $stored,
			(string) $stored,
			'The currency exponent is not stored as a plain integer'
		);
		$this->assertSame(2, (int) $stored);
	}

	/**
	 * ISO 4217 codes are three uppercase letters. Storage only — validation of
	 * administrator input belongs to the ACP settings form in a later task.
	 */
	public function test_currency_code_default_is_iso_style_uppercase()
	{
		$this->apply_migration();

		$code = (string) $this->config['donationcampaigns_currency_code'];

		$this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $code);
	}

	/**
	 * The documented safe range for later ACP validation is 1..500. The default
	 * must sit inside it.
	 */
	public function test_donor_list_limit_default_is_within_the_documented_safe_range()
	{
		$this->apply_migration();

		$limit = (int) $this->config['donationcampaigns_donor_list_limit'];

		$this->assertGreaterThanOrEqual(1, $limit);
		$this->assertLessThanOrEqual(500, $limit);
	}

	/**
	 * This migration establishes storage defaults only. Validation, range
	 * enforcement and administrator-facing warnings belong to the ACP task.
	 */
	public function test_migration_performs_no_operations_beyond_config()
	{
		foreach ($this->create_migration()->update_data() as $step)
		{
			$this->assertStringStartsWith('config.', $step[0]);
		}

		$migration = $this->create_migration();

		$this->assertSame(
			array(),
			$migration->update_schema(),
			'The config migration must not alter the schema'
		);
	}

	/**
	 * @return string[]
	 */
	protected function extension_config_keys()
	{
		$owned = array();

		foreach ($this->config as $key => $value)
		{
			if (strpos($key, 'donationcampaigns_') === 0)
			{
				$owned[] = $key;
			}
		}

		return $owned;
	}
}
