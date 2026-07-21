<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\service\description_formatter;

/**
 * Stands in for phpBB's BBCode pipeline.
 *
 * The real one resolves text_formatter.parser from the container, which needs
 * a booted board; it is exercised on the Docker test board instead. What these
 * tests need to prove is the SERVICE's policy — that encoding always happens,
 * that its output is what reaches storage, and that a caller cannot supply the
 * metadata itself — and that policy is visible through any formatter.
 */
class fake_description_formatter extends description_formatter
{
	/** @var array Every for_storage call */
	public $storage_calls = array();

	public function for_storage($text, $allow_bbcode = true, $allow_urls = true, $allow_smilies = true)
	{
		$this->storage_calls[] = func_get_args();

		if ($text === '')
		{
			return array('text' => '', 'uid' => '', 'bitfield' => '', 'flags' => 7);
		}

		// Mimics the real contract, measured against phpBB 3.3.17 rather than
		// assumed. generate_text_for_storage() ABSORBS exactly one layer of
		// HTML entity encoding -- the layer request->variable() applied -- so
		// "A & B" and "A &amp; B" store identically. Metadata is produced
		// here, never supplied by the caller.
		return array(
			'text'		=> html_entity_decode($text, ENT_QUOTES, 'UTF-8'),
			'uid'		=> 'uid1234',
			'bitfield'	=> 'QQ==',
			'flags'		=> 7,
		);
	}

	/**
	 * generate_text_for_edit() returns text HTML-escaped exactly ONCE, to be
	 * emitted raw into a textarea so the browser decodes that one layer.
	 *
	 * The previous double did the opposite -- it decoded -- and that is why a
	 * large suite never noticed the textarea escaping an already-escaped
	 * value. A double that cannot express the bug cannot catch it.
	 */
	public function for_edit($text, $uid, $flags)
	{
		return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
	}
}
