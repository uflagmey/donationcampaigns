<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\exception;

/**
 * Domain exception carrying a language key rather than a display string, so
 * that callers can render it in the user's own language.
 */
class donationcampaigns_exception extends \RuntimeException
{
	/** @var string */
	protected $language_key;

	/** @var array */
	protected $parameters;

	/**
	 * @param string $language_key
	 * @param array  $parameters
	 */
	public function __construct($language_key, array $parameters = array())
	{
		$this->language_key = $language_key;
		$this->parameters = $parameters;

		parent::__construct($language_key);
	}

	/**
	 * @return string
	 */
	public function get_language_key()
	{
		return $this->language_key;
	}

	/**
	 * @return array
	 */
	public function get_parameters()
	{
		return $this->parameters;
	}
}
