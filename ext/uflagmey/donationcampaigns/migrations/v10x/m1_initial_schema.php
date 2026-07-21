<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\migrations\v10x;

/**
 * Creates the campaign and donation tables.
 *
 * Physical identifiers use the abbreviated stem "ufdc" (uflagmey donation
 * campaigns) rather than the extension's full machine name. Oracle before
 * 12.2 caps identifiers at 30 bytes and phpBB 3.3 supports Oracle; the full
 * name would have produced a 33-byte table name. phpBB validates column and
 * index names against that limit but performs no check on table names, so the
 * failure would have surfaced as a raw database error during installation.
 * See ADR-012 and specification section 4.6.8.
 */
class m1_initial_schema extends \phpbb\db\migration\migration
{
	/**
	 * Makes a re-run a no-op rather than a duplicate-table error, so that a
	 * partially applied installation can be repaired by re-running migrations.
	 */
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'ufdc_campaigns');
	}

	public static function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'ufdc_campaigns'	=> array(
					'COLUMNS'	=> array(
						'campaign_id'			=> array('UINT', null, 'auto_increment'),
						// Named topic_id to match the core column it references.
						'topic_id'				=> array('UINT', 0),
						'campaign_title'		=> array('VCHAR:255', ''),
						'campaign_desc'			=> array('TEXT_UNI', ''),
						// phpBB's standard text-storage triplet. Types match the
						// equivalent core columns (posts.bbcode_uid,
						// posts.bbcode_bitfield, posts.bbcode_options).
						'desc_bbcode_uid'		=> array('VCHAR:8', ''),
						'desc_bbcode_bitfield'	=> array('VCHAR:255', ''),
						'desc_bbcode_options'	=> array('UINT:11', 7),
						// Money: integer minor units. Never float.
						'target_amount'			=> array('UINT', 0),
						// Derived from the donation rows by SUM(). Never input.
						'collected_amount'		=> array('UINT', 0),
						'campaign_enabled'		=> array('BOOL', 1),
						'show_donor_names'		=> array('BOOL', 1),
						'show_donation_count'	=> array('BOOL', 1),
						'external_url'			=> array('VCHAR:255', ''),
						'campaign_created'		=> array('TIMESTAMP', 0),
						'campaign_updated'		=> array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'campaign_id',
					'KEYS'			=> array(
						// One campaign per topic, enforced by the database.
						// An application-level check loses to concurrent
						// requests; a unique index does not.
						'dc_topic_id'	=> array('UNIQUE', array('topic_id')),
					),
				),
				$this->table_prefix . 'ufdc_donations'	=> array(
					'COLUMNS'	=> array(
						'donation_id'		=> array('UINT', null, 'auto_increment'),
						'campaign_id'		=> array('UINT', 0),
						// Money: integer minor units. Never float.
						'donation_amount'	=> array('UINT', 0),
						// Free text. Empty renders as the localised "Anonymous".
						'donor_name'		=> array('VCHAR:255', ''),
						'donation_time'		=> array('TIMESTAMP', 0),
						// Controls name visibility only. A non-public donation
						// still counts toward the campaign total.
						'donation_public'	=> array('BOOL', 1),
						'donation_created'	=> array('TIMESTAMP', 0),
						'donation_updated'	=> array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'donation_id',
					'KEYS'			=> array(
						// Composite: the leading column serves the recalculation
						// SUM and the donation count; both columns together serve
						// the ordered donor list without a filesort.
						'dc_campaign_time'	=> array('INDEX', array('campaign_id', 'donation_time')),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . 'ufdc_campaigns',
				$this->table_prefix . 'ufdc_donations',
			),
		);
	}
}
