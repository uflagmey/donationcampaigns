<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace uflagmey\donationcampaigns\service;

use uflagmey\donationcampaigns\repository\campaign_repository;
use uflagmey\donationcampaigns\repository\donation_repository;
use uflagmey\donationcampaigns\repository\topic_repository;
use uflagmey\donationcampaigns\exception\donationcampaigns_exception;

/**
 * Campaign business rules and cleanup coordination.
 *
 * This class owns validation, the writable-field policy, transaction
 * boundaries and the order in which cleanup happens. It owns NO SQL — every
 * persistence operation goes through a repository. The database handle is
 * injected solely to open transactions.
 *
 * TRANSACTION BOUNDARIES, per public method:
 *
 *   get_campaign()           none — single read
 *   get_campaign_for_topic() none — single read, runs on every topic view
 *   get_public_donor_list()  none — single read
 *   validate()               none — reads only
 *   create_campaign()        none — one INSERT, atomic by itself
 *   update_campaign()        none — one UPDATE, atomic by itself
 *   delete_campaign()        OWN — two statements across two tables
 *   purge_for_topics()       OWN — resolve, delete donations, delete campaigns
 *   purge_for_forum()        delegates to purge_for_topics(); opens none itself
 *
 * A transaction is opened only where several dependent writes must succeed or
 * fail together. Wrapping a single statement would add a round trip and buy
 * nothing.
 */
class campaign_service
{
	/**
	 * Fields an administrator may set.
	 *
	 * collected_amount is deliberately absent. It is derived from the donation
	 * rows, and a field that cannot be submitted cannot be tampered with. See
	 * specification section 9.2.
	 */
	const WRITABLE_FIELDS = array(
		'topic_id',
		'campaign_title',
		// campaign_desc is writable, but its BBCode metadata is NOT: uid,
		// bitfield and options are produced by the encoder from the text
		// itself. A caller supplying them would be describing markup the text
		// does not contain, which is how a payload slips past the display
		// path. Absent for the same reason as collected_amount.
		'campaign_desc',
		'target_amount',
		'campaign_enabled',
		'show_donor_names',
		'show_donation_count',
		'external_url',
		'external_link_text',
	);

	/**
	 * Ceiling imposed by the UINT storage column, shared with the money path.
	 */
	const MAX_TARGET_AMOUNT = currency_formatter::MAX_MINOR_UNITS;

	/**
	 * Width of the campaign_title column, in characters.
	 */
	const MAX_TITLE_LENGTH = 255;

	/**
	 * The button label's limit, matching the column m6 creates.
	 *
	 * Deliberately shorter than the 255 used elsewhere in the table: this is a
	 * button, and a label past this length does not render sensibly at any
	 * width. A test asserts the two numbers stay equal.
	 */
	const MAX_LINK_TEXT_LENGTH = 100;

	/**
	 * The URL's limit, matching the column m1 creates.
	 *
	 * Checked here so the database is never what decides: a longer value is
	 * silently truncated by a non-strict engine, producing a broken link
	 * nobody notices, and throws a raw driver error on a strict one.
	 */
	const MAX_URL_LENGTH = 255;

	/**
	 * The only URL schemes an external donation link may use.
	 *
	 * An allowlist rather than a blocklist: a blocklist has to anticipate every
	 * dangerous scheme, and javascript:, data: and vbscript: are only the ones
	 * we happen to know about.
	 */
	const ALLOWED_URL_SCHEMES = array('http', 'https');

	/** @var \phpbb\db\driver\driver_interface Injected ONLY to open transactions */
	protected $db;

	/** @var campaign_repository */
	protected $campaigns;

	/** @var donation_repository */
	protected $donations;

	/** @var topic_repository */
	protected $topics;

	/** @var description_formatter */
	protected $descriptions;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		campaign_repository $campaigns,
		donation_repository $donations,
		topic_repository $topics,
		description_formatter $descriptions
	)
	{
		$this->db = $db;
		$this->campaigns = $campaigns;
		$this->donations = $donations;
		$this->topics = $topics;
		$this->descriptions = $descriptions;
	}

	// ------------------------------------------------------------------ reads

	/**
	 * A campaign for administrative use, enabled or not.
	 *
	 * The ACP must be able to see and edit a disabled campaign; otherwise
	 * disabling one would make it uneditable and re-enabling impossible.
	 *
	 * TRANSACTION BOUNDARY: none.
	 *
	 * @param int $campaign_id
	 * @return array|null
	 */
	public function get_campaign($campaign_id)
	{
		return $this->campaigns->find_by_id($campaign_id);
	}

	/**
	 * The campaign a topic shows publicly.
	 *
	 * Disabled campaigns are invisible on the front end even though the row
	 * still exists — that is the whole point of the flag. The repository
	 * deliberately does not apply this rule, because the ACP needs the rows it
	 * hides.
	 *
	 * TRANSACTION BOUNDARY: none. Runs on every topic view and must stay cheap.
	 *
	 * @param int $topic_id
	 * @return array|null Null when there is no campaign, or it is disabled
	 */
	public function get_campaign_for_topic($topic_id)
	{
		$campaign = $this->campaigns->find_by_topic_id($topic_id);

		if ($campaign === null || !$campaign['campaign_enabled'])
		{
			return null;
		}

		return $campaign;
	}

	/**
	 * The donations a campaign may list by name, most recent first.
	 *
	 * TRANSACTION BOUNDARY: none.
	 *
	 * @param int $campaign_id
	 * @param int $limit
	 * @return array
	 */
	public function get_public_donor_list($campaign_id, $limit)
	{
		return $this->donations->find_public_by_campaign($campaign_id, $limit);
	}

	/**
	 * How many donations a campaign has, public or not.
	 *
	 * The public flag governs whether a donor is named, never whether the
	 * donation happened, so a non-public donation is included here.
	 *
	 * TRANSACTION BOUNDARY: none.
	 *
	 * @param int $campaign_id
	 * @return int
	 */
	public function count_donations($campaign_id)
	{
		return $this->donations->count_by_campaign($campaign_id);
	}

	/**
	 * How many of a campaign's donations may be listed by name.
	 *
	 * Used only to say how many donors a limited list left out.
	 *
	 * TRANSACTION BOUNDARY: none.
	 *
	 * @param int $campaign_id
	 * @return int
	 */
	public function count_public_donations($campaign_id)
	{
		return $this->donations->count_public_by_campaign($campaign_id);
	}

	/**
	 * A page of campaigns for the ACP list, each with its topic's title.
	 *
	 * Composed from two repositories here rather than by a join, because the
	 * campaigns table and the topics table have different owners: one is ours,
	 * the other is core's. The titles are fetched in ONE query for the page.
	 *
	 * Disabled campaigns are included — the ACP is where they are re-enabled.
	 * A campaign whose topic has vanished is still listed, with an empty
	 * title, so it remains visible and therefore deletable.
	 *
	 * TRANSACTION BOUNDARY: none.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function list_campaigns($limit, $offset = 0)
	{
		$campaigns = $this->campaigns->find_all($limit, $offset);

		if (empty($campaigns))
		{
			return array();
		}

		$titles = $this->topics->find_titles_by_ids(array_column($campaigns, 'topic_id'));

		foreach ($campaigns as $index => $campaign)
		{
			$topic_id = $campaign['topic_id'];

			$campaigns[$index]['topic_title'] = isset($titles[$topic_id]) ? $titles[$topic_id] : '';
		}

		return $campaigns;
	}

	/**
	 * How many campaigns exist, for pagination.
	 *
	 * TRANSACTION BOUNDARY: none.
	 *
	 * @return int
	 */
	public function count_campaigns()
	{
		return $this->campaigns->count_all();
	}

	// ------------------------------------------------------------- validation

	/**
	 * Check an input array against every campaign rule.
	 *
	 * Returns ALL failures rather than stopping at the first, so the ACP can
	 * show an administrator everything that is wrong in one pass instead of
	 * one error per submission.
	 *
	 * Errors are language keys, never display strings, so the caller renders
	 * them in the administrator's own language.
	 *
	 * TRANSACTION BOUNDARY: none. Reads only.
	 *
	 * Create and edit differ only in which campaign may legitimately already
	 * occupy the target topic, and $campaign_id expresses that completely: 0
	 * means "no campaign yet", so any existing one is a conflict.
	 *
	 * @param array $input
	 * @param int $campaign_id The campaign being edited; 0 when creating
	 * @return string[] Language keys; empty array when the input is valid
	 */
	public function validate(array $input, $campaign_id = 0)
	{
		$errors = array();

		$title = isset($input['campaign_title']) ? trim((string) $input['campaign_title']) : '';

		if ($title === '')
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED';
		}
		else if (utf8_strlen($title) > self::MAX_TITLE_LENGTH)
		{
			// Characters, not bytes: the column is 255 characters wide, and a
			// multibyte title that fits must not be rejected.
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG';
		}

		$target = isset($input['target_amount']) ? (int) $input['target_amount'] : 0;

		if ($target <= 0)
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE';
		}
		else if ($target > self::MAX_TARGET_AMOUNT)
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE';
		}

		$topic_id = isset($input['topic_id']) ? (int) $input['topic_id'] : 0;

		if ($topic_id <= 0)
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED';
		}
		else
		{
			if (!$this->topics->topic_exists($topic_id))
			{
				$errors[] = 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND';
			}

			// One campaign per topic. The unique index enforces this in the
			// database; this check exists so the administrator sees a sentence
			// rather than a raw constraint violation. Comparing ids means a
			// campaign is never reported as a duplicate of itself.
			$existing = $this->campaigns->find_by_topic_id($topic_id);

			if ($existing !== null && $existing['campaign_id'] !== (int) $campaign_id)
			{
				$errors[] = 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN';
			}
		}

		$url = isset($input['external_url']) ? trim((string) $input['external_url']) : '';

		if ($url !== '' && !$this->is_safe_url($url))
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_URL_INVALID';
		}

		// Characters, not bytes, as everywhere else in this file.
		if (utf8_strlen($url) > self::MAX_URL_LENGTH)
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG';
		}

		$link_text = isset($input['external_link_text']) ? trim((string) $input['external_link_text']) : '';

		// The label belongs to the button, and the button only exists when
		// there is somewhere to send people. With no URL nothing is rendered,
		// so an empty label is simply unused; with a URL an empty label would
		// render a button with nothing written on it. Refused here rather than
		// substituted at render time, which would quietly accept a submission
		// the administrator did not mean to make.
		if ($url !== '' && $link_text === '')
		{
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED';
		}

		if (utf8_strlen($link_text) > self::MAX_LINK_TEXT_LENGTH)
		{
			// Characters, not bytes: "Über PayPal spenden" is 19, not 20.
			$errors[] = 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG';
		}

		return $errors;
	}

	/**
	 * Scheme allowlist for the optional external donation link.
	 *
	 * Rejects javascript:, data:, vbscript: and anything else outside the
	 * allowlist, case-insensitively and tolerant of surrounding whitespace.
	 * Protocol-relative URLs are rejected outright: they carry no scheme of
	 * their own, inherit the page's, and so slip past a naive scheme check.
	 *
	 * @param string $url
	 * @return bool
	 */
	protected function is_safe_url($url)
	{
		$url = trim($url);

		if (strpos($url, '//') === 0)
		{
			return false;
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);

		if ($scheme === false || $scheme === null)
		{
			return false;
		}

		return in_array(strtolower($scheme), self::ALLOWED_URL_SCHEMES, true);
	}

	// ----------------------------------------------------------------- writes

	/**
	 * Create a campaign.
	 *
	 * Validates before writing. The ACP is expected to call validate() first so
	 * it can display every error at once, but this method does not rely on that
	 * — a caller that forgets gets an exception rather than an invalid row, and
	 * the rules stay enforceable in exactly one place.
	 *
	 * TRANSACTION BOUNDARY: none. One INSERT, atomic by itself.
	 *
	 * @param array $input
	 * @return int New campaign id
	 * @throws donationcampaigns_exception When the input is invalid
	 */
	public function create_campaign(array $input)
	{
		$this->assert_valid($input, 0);

		$now = time();

		$data = $this->encode_description($this->filter_writable($input));
		$data['collected_amount'] = 0;
		$data['campaign_created'] = $now;
		$data['campaign_updated'] = $now;

		try
		{
			return $this->campaigns->insert($data);
		}
		catch (\Exception $e)
		{
			// validate() already checked that the topic was free, but two
			// concurrent requests can both pass that check. The UNIQUE index
			// on topic_id is the authoritative protection and it rejects the
			// loser. Report that as the duplicate it is, rather than as a raw
			// driver error — but only if the topic really is taken now, so a
			// connection failure is not disguised as one.
			if ($this->campaigns->exists_for_topic($data['topic_id']))
			{
				throw new donationcampaigns_exception('DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN');
			}

			throw $e;
		}
	}

	/**
	 * Update a campaign.
	 *
	 * TRANSACTION BOUNDARY: none. One UPDATE, atomic by itself.
	 *
	 * @param int $campaign_id
	 * @param array $input
	 * @return void
	 * @throws donationcampaigns_exception When the input is invalid
	 */
	public function update_campaign($campaign_id, array $input)
	{
		$campaign_id = (int) $campaign_id;

		$this->assert_valid($input, $campaign_id);

		$data = $this->encode_description($this->filter_writable($input));
		$data['campaign_updated'] = time();

		$this->campaigns->update($campaign_id, $data);
	}

	/**
	 * Delete one campaign and everything that depends on it.
	 *
	 * TRANSACTION BOUNDARY: OWN. Two statements across two tables; a partial
	 * apply would leave donations pointing at a campaign that no longer exists.
	 *
	 * @param int $campaign_id
	 * @return void
	 */
	public function delete_campaign($campaign_id)
	{
		$campaign_id = (int) $campaign_id;

		$this->db->sql_transaction('begin');

		try
		{
			// Donations FIRST. See purge_for_topics() for why the order is a
			// hard constraint rather than a preference.
			$this->donations->delete_by_campaign_ids(array($campaign_id));
			$this->campaigns->delete_by_ids(array($campaign_id));

			$this->db->sql_transaction('commit');
		}
		catch (\Exception $e)
		{
			$this->db->sql_transaction('rollback');

			throw $e;
		}
	}

	// ---------------------------------------------------------------- cleanup

	/**
	 * Cascade cleanup for deleted topics. Used by BOTH deletion listeners, so
	 * the ordering below is written once and cannot drift between them.
	 *
	 * The ordering is a hard constraint, not a preference: donation rows key on
	 * campaign_id, so once the campaign rows are gone their ids are
	 * unresolvable and the donations are orphaned permanently — invisible in
	 * the ACP, invisible on the front end, and growing forever. See
	 * specification section 7.3.4.
	 *
	 * TRANSACTION BOUNDARY: OWN. Nests correctly inside the transaction core
	 * has already opened around its own deletion, so cleanup is atomic with it.
	 *
	 * @param int[] $topic_ids
	 * @return int Number of campaigns removed
	 */
	public function purge_for_topics(array $topic_ids)
	{
		if (empty($topic_ids))
		{
			// No transaction at all on the empty path. Topic deletion is
			// common and most topics have no campaign; an empty begin/commit
			// on every one of them is pure overhead.
			return 0;
		}

		$campaign_ids = $this->campaigns->find_campaign_ids_for_topics($topic_ids);

		if (empty($campaign_ids))
		{
			return 0;
		}

		$this->db->sql_transaction('begin');

		try
		{
			$this->donations->delete_by_campaign_ids($campaign_ids);
			$this->campaigns->delete_by_ids($campaign_ids);

			$this->db->sql_transaction('commit');
		}
		catch (\Exception $e)
		{
			$this->db->sql_transaction('rollback');

			// Rethrown rather than swallowed: a listener that saw a silent
			// success would let phpBB finish deleting the topic, leaving our
			// rows behind with nothing pointing at them.
			throw $e;
		}

		return count($campaign_ids);
	}

	/**
	 * Cascade cleanup for a deleted forum.
	 *
	 * Resolves the forum's real topic list and delegates, so both deletion
	 * paths share ONE cleanup implementation and the forum listener holds no
	 * knowledge of how cleanup works or in what order it must happen.
	 *
	 * The topic list is queried rather than taken from the core event's
	 * topic_ids payload, because that payload is built from a join against the
	 * attachments table and therefore lists only topics WITH attachments. See
	 * specification section 7.3.6.
	 *
	 * TRANSACTION BOUNDARY: none of its own; owned by purge_for_topics().
	 *
	 * @param int $forum_id
	 * @return int Number of campaigns removed
	 */
	public function purge_for_forum($forum_id)
	{
		return $this->purge_for_topics($this->topics->find_topic_ids_by_forum((int) $forum_id));
	}

	// ---------------------------------------------------------------- helpers

	/**
	 * The description as an administrator typed it, for the edit form.
	 *
	 * The textarea must show source, not storage: a campaign edited twice
	 * would otherwise accumulate escaping on every pass.
	 *
	 * TRANSACTION BOUNDARY: none.
	 *
	 * @param array $campaign A hydrated campaign row
	 * @return string
	 */
	public function decode_description(array $campaign)
	{
		return $this->descriptions->for_edit(
			$campaign['campaign_desc'],
			$campaign['desc_bbcode_uid'],
			$campaign['desc_bbcode_options']
		);
	}

	/**
	 * Replace the writable description with its stored encoding.
	 *
	 * Runs on EVERY write, so there is no path by which a raw description
	 * reaches the column. See specification issue D.
	 *
	 * @param array $data Already reduced to writable fields
	 * @return array
	 */
	protected function encode_description(array $data)
	{
		$encoded = $this->descriptions->for_storage(isset($data['campaign_desc']) ? $data['campaign_desc'] : '');

		$data['campaign_desc'] = $encoded['text'];
		$data['desc_bbcode_uid'] = $encoded['uid'];
		$data['desc_bbcode_bitfield'] = $encoded['bitfield'];
		$data['desc_bbcode_options'] = $encoded['flags'];

		return $data;
	}

	/**
	 * @param array $input
	 * @param int $campaign_id
	 * @return void
	 * @throws donationcampaigns_exception Carrying the first failure as its
	 *         language key, and every failure in its parameters
	 */
	protected function assert_valid(array $input, $campaign_id)
	{
		$errors = $this->validate($input, $campaign_id);

		if (!empty($errors))
		{
			throw new donationcampaigns_exception($errors[0], $errors);
		}
	}

	/**
	 * Reduce arbitrary input to the fields an administrator may set, and
	 * normalise the free-text ones.
	 *
	 * collected_amount is not merely ignored here — it is absent from
	 * WRITABLE_FIELDS, so it cannot reach the repository even if a future
	 * caller passes it.
	 *
	 * @param array $input
	 * @return array
	 */
	protected function filter_writable(array $input)
	{
		$data = array_intersect_key($input, array_flip(self::WRITABLE_FIELDS));

		// Store what was validated: validation trims before checking, so
		// writing the untrimmed value would persist something the rules never
		// actually saw.
		foreach (array('campaign_title', 'external_url', 'external_link_text') as $field)
		{
			if (isset($data[$field]))
			{
				$data[$field] = trim((string) $data[$field]);
			}
		}

		return $data;
	}
}
