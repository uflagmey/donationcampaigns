<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * Stands in for phpBB's pagination service.
 *
 * The real one renders into a template block and needs a full page context.
 * What matters at this layer is that the module hands it the right totals, so
 * the call is recorded rather than performed.
 */
class stub_pagination
{
	/** @var array Every generate_template_pagination call */
	public $calls = array();

	public function generate_template_pagination($base_url, $block_var_name, $start_name, $num_items, $per_page, $start = 1)
	{
		$this->calls[] = compact('base_url', 'block_var_name', 'start_name', 'num_items', 'per_page', 'start');
	}

	public function on_page($num_items, $per_page, $start)
	{
		return 1;
	}
}
