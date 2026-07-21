<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\tests\service;

use uflagmey\donationcampaigns\repository\donation_repository;

/**
 * A donation repository whose writes fail.
 *
 * Proves that when the donation row itself cannot be persisted, nothing else
 * the transaction attempted survives either.
 */
class failing_donation_repository extends donation_repository
{
	public function insert(array $data)
	{
		throw new \RuntimeException('donation persistence failed');
	}

	public function update($donation_id, array $data)
	{
		throw new \RuntimeException('donation persistence failed');
	}

	public function delete_by_ids(array $donation_ids)
	{
		throw new \RuntimeException('donation persistence failed');
	}
}
