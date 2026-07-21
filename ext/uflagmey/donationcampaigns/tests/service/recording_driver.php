<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

/**
 * A real sqlite3 driver that also records what it was asked to do.
 *
 * Transaction boundaries are part of campaign_service's contract, so they are
 * asserted rather than described in a comment. A mock would not do: the
 * statements must genuinely execute, because the ordering guarantee this class
 * is used to verify (donations deleted before campaigns) is only meaningful
 * against a database that actually applies them.
 */
class recording_driver extends \phpbb\db\driver\sqlite3
{
	/** @var string[] Transaction verbs in the order they were issued */
	public $transaction_log = array();

	/** @var string[] Every statement issued, in order */
	public $queries = array();

	public function __construct()
	{
		parent::__construct();

		// phpBB's driver base class derives sql_layer by slicing its own class
		// name against the literal prefix 'phpbb\db\driver\'. A subclass in any
		// other namespace therefore ends up with a garbage layer name, and
		// db\tools\tools fails on an undefined type-map key. Restore it.
		$this->sql_layer = 'sqlite3';
	}

	public function sql_transaction($status = 'begin')
	{
		$this->transaction_log[] = $status;

		return parent::sql_transaction($status);
	}

	public function sql_query($query = '', $cache_ttl = 0)
	{
		$this->queries[] = $query;

		return parent::sql_query($query, $cache_ttl);
	}

	/**
	 * Reset the log, so a test can ignore whatever its fixture setup did.
	 */
	public function forget()
	{
		$this->transaction_log = array();
		$this->queries = array();
	}

	/**
	 * Index of the first statement matching a pattern, or null.
	 *
	 * @param string $pattern
	 * @return int|null
	 */
	public function first_query_matching($pattern)
	{
		foreach ($this->queries as $index => $query)
		{
			if (preg_match($pattern, $query))
			{
				return $index;
			}
		}

		return null;
	}
}
