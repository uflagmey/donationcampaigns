<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\acp;

/**
 * Captures admin log entries instead of writing them.
 *
 * A settings change is an administrative action on a shared board, so it has
 * to be auditable. Asserting the log entry is written keeps that from being
 * dropped silently.
 */
class recording_log implements \phpbb\log\log_interface
{
	/** @var string[] Operation keys, in order */
	public $operations = array();

	/** @var array Full entries */
	public $entries = array();

	public function add($mode, $user_id, $log_ip, $log_operation, $log_time = false, $additional_data = array())
	{
		$this->operations[] = $log_operation;
		$this->entries[] = func_get_args();

		return array();
	}

	public function is_enabled($type = '')
	{
		return true;
	}

	public function disable($type = '')
	{
	}

	public function enable($type = '')
	{
	}

	public function delete($mode, $conditions = array())
	{
	}

	public function get_logs($mode, $count_logs = true, $limit = 0, $offset = 0, $forum_id = 0, $topic_id = 0, $user_id = 0, $log_time = 0, $sort_by = 'l.log_time DESC', $keywords = '')
	{
		return array();
	}

	public function get_log_count()
	{
		return 0;
	}

	public function get_valid_offset()
	{
		return 0;
	}
}
