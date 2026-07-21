<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

/**
 * The campaign description's text pipeline.
 *
 * A thin seam over phpBB's three content functions, which are globals that
 * resolve text_formatter.parser from the container and therefore need a booted
 * board. Wrapping them here lets campaign_service own the POLICY — that a
 * description is always encoded before storage, and that its BBCode metadata
 * is produced here rather than accepted from a request — while the encoding
 * itself stays phpBB's, not a reimplementation of it.
 *
 * THE CONTRACT, in full (specification issue D):
 *
 *   INPUT    $request->variable(..., true) escapes the text, then for_storage()
 *            parses BBCode and produces text + uid + bitfield + flags.
 *   STORAGE  All four values are stored together. They are meaningless apart:
 *            a uid describing markup the text does not contain is how a
 *            crafted payload gets past the display path.
 *   DISPLAY  for_display() renders the four back. It does NOT sanitise — it
 *            relies on the text having been escaped on the way in. Nothing may
 *            escape it a second time, or an administrator's formatting appears
 *            as visible tags.
 *   EDIT     for_edit() decodes the stored text back to what was typed, so the
 *            textarea shows source rather than storage.
 *
 * This is the opposite contract to the plain scalar fields (campaign_title,
 * donor_name, external_url), which are stored raw and escaped at every output
 * point. The difference is deliberate: those fields carry no markup and have
 * no storage-side pipeline to escape them.
 */
class description_formatter
{
	/**
	 * Encode text for storage.
	 *
	 * @param string $text Text as typed, already escaped by the request layer
	 * @param bool $allow_bbcode
	 * @param bool $allow_urls
	 * @param bool $allow_smilies
	 * @return array text, uid, bitfield, flags
	 */
	public function for_storage($text, $allow_bbcode = true, $allow_urls = true, $allow_smilies = true)
	{
		$text = (string) $text;

		if ($text === '')
		{
			// An empty description carries no markup, so it carries no
			// metadata either. Storing a uid for empty text would describe
			// formatting that is not there.
			return array('text' => '', 'uid' => '', 'bitfield' => '', 'flags' => OPTION_FLAG_BBCODE | OPTION_FLAG_SMILIES | OPTION_FLAG_LINKS);
		}

		$uid = '';
		$bitfield = '';
		$flags = 0;

		generate_text_for_storage($text, $uid, $bitfield, $flags, $allow_bbcode, $allow_urls, $allow_smilies);

		return array(
			'text'		=> $text,
			'uid'		=> $uid,
			'bitfield'	=> $bitfield,
			'flags'		=> (int) $flags,
		);
	}

	/**
	 * Decode stored text back into the source an administrator typed.
	 *
	 * @param string $text
	 * @param string $uid
	 * @param int $flags
	 * @return string
	 */
	public function for_edit($text, $uid, $flags)
	{
		if ((string) $text === '')
		{
			return '';
		}

		$decoded = generate_text_for_edit((string) $text, (string) $uid, (int) $flags);

		return isset($decoded['text']) ? $decoded['text'] : '';
	}

	/**
	 * Render stored text for output.
	 *
	 * @param string $text
	 * @param string $uid
	 * @param string $bitfield
	 * @param int $flags
	 * @return string
	 */
	public function for_display($text, $uid, $bitfield, $flags)
	{
		if ((string) $text === '')
		{
			return '';
		}

		return generate_text_for_display((string) $text, (string) $uid, (string) $bitfield, (int) $flags);
	}
}
