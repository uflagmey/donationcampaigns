<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\controller;

/**
 * Core's pagination with only the template output recorded.
 *
 * validate_start() is core's own, unchanged — the clamping of an
 * out-of-range ?start= is exactly what the list relies on, so it must not be
 * a stand-in. Only generate_template_pagination(), which needs a full page
 * context, is replaced by a recorder.
 */
class recording_pagination extends \phpbb\pagination
{
	/** @var array Every generate_template_pagination call */
	public $calls = array();

	public function __construct()
	{
	}

	public function generate_template_pagination($base_url, $block_var_name, $start_name, $num_items, $per_page, $start = 1, $reverse_count = false, $ignore_on_page = false)
	{
		$this->calls[] = compact('base_url', 'block_var_name', 'start_name', 'num_items', 'per_page', 'start');
	}
}
