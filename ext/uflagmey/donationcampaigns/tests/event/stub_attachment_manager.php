<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\event;

/**
 * Stands in for phpBB's attachment.manager during forum deletion.
 *
 * Core resolves that service and asks it to remove the forum's attachments.
 * Building the real one would pull in the filesystem, upload and resync
 * services for a step this extension has no part in; the deletion path is
 * still core's own from end to end.
 */
class stub_attachment_manager
{
	/** @var array Every delete call, for inspection */
	public $calls = array();

	/**
	 * @param string $mode
	 * @param array|int $ids
	 * @param bool $resync
	 * @return int
	 */
	public function delete($mode, $ids, $resync = true)
	{
		$this->calls[] = array($mode, $ids);

		return 0;
	}
}
