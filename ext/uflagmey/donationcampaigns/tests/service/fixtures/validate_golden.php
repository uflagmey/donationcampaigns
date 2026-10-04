<?php
/**
 * Donation Campaigns extension for phpBB.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Recorded from campaign_service::validate() BEFORE the beta3 split
 * (recorded at d910c7c). Do not edit by hand; see campaign_validate_golden_test.
 */

return array (
  'title:ok target:ok topic:free url:none link:ok' =>
  array (
  ),
  'title:ok target:ok topic:free url:none link:empty' =>
  array (
  ),
  'title:ok target:ok topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:free url:ok link:ok' =>
  array (
  ),
  'title:ok target:ok topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:ok topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:ok topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:ok topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:ok topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:ok topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:ok topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:ok topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:ok topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:ok topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:ok topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:ok topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:ok topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:ok topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:ok topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:ok topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:ok topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:ok topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:ok topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:ok topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:ok topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:ok topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:ok topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:own url:none link:ok' =>
  array (
  ),
  'title:ok target:ok topic:own url:none link:empty' =>
  array (
  ),
  'title:ok target:ok topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:own url:ok link:ok' =>
  array (
  ),
  'title:ok target:ok topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:ok topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:ok topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:ok topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:ok topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:ok target:zero topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:ok target:zero topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:ok target:zero topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:zero topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:zero topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:zero topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:zero topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:zero topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:zero topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:zero topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:zero topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:zero topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:zero topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:zero topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:zero topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:zero topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:zero topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:zero topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:zero topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:zero topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:zero topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:zero topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:zero topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:zero topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:zero topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:ok target:zero topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:ok target:zero topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:ok target:zero topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:zero topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:zero topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:zero topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:zero topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:ok target:huge topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:ok target:huge topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:ok target:huge topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:huge topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:huge topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:huge topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:huge topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:ok target:huge topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:huge topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:huge topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:huge topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:huge topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:ok target:huge topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:huge topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:huge topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:huge topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:huge topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:huge topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:huge topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:huge topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:huge topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:huge topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:ok target:huge topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:huge topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:huge topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:ok target:huge topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:ok target:huge topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:ok target:huge topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:ok target:huge topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:ok target:huge topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:ok target:huge topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:ok target:huge topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
  ),
  'title:empty target:ok topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
  ),
  'title:empty target:ok topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
  ),
  'title:empty target:ok topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:ok topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:ok topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:ok topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:ok topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:ok topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:ok topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:ok topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:ok topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:ok topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:ok topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:ok topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:ok topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:ok topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:ok topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:ok topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:ok topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:ok topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:ok topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:ok topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:ok topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:ok topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:ok topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
  ),
  'title:empty target:ok topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
  ),
  'title:empty target:ok topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
  ),
  'title:empty target:ok topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:ok topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:ok topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:ok topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:ok topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:empty target:zero topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:empty target:zero topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:empty target:zero topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:zero topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:zero topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:zero topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:zero topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:zero topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:zero topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:zero topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:zero topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:zero topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:zero topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:zero topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:zero topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:zero topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:zero topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:zero topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:zero topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:zero topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:zero topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:zero topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:zero topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:zero topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:zero topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:empty target:zero topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:empty target:zero topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:empty target:zero topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:zero topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:zero topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:zero topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:zero topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:empty target:huge topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:empty target:huge topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:empty target:huge topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:huge topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:huge topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:huge topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:huge topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:empty target:huge topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:huge topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:huge topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:huge topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:huge topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:empty target:huge topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:huge topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:huge topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:huge topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:huge topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:huge topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:huge topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:huge topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:huge topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:huge topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:empty target:huge topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:huge topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:huge topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:empty target:huge topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:empty target:huge topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:empty target:huge topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:empty target:huge topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:empty target:huge topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:empty target:huge topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:empty target:huge topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_REQUIRED',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
  ),
  'title:long target:ok topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
  ),
  'title:long target:ok topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
  ),
  'title:long target:ok topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:ok topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:ok topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:ok topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:ok topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:ok topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:ok topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:ok topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:ok topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:ok topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:ok topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:ok topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:ok topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:ok topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:ok topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:ok topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:ok topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:ok topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:ok topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:ok topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:ok topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:ok topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:ok topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
  ),
  'title:long target:ok topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
  ),
  'title:long target:ok topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
  ),
  'title:long target:ok topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:ok topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:ok topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:ok topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:ok topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:long target:zero topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:long target:zero topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:long target:zero topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:zero topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:zero topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:zero topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:zero topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:zero topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:zero topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:zero topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:zero topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:zero topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:zero topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:zero topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:zero topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:zero topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:zero topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:zero topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:zero topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:zero topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:zero topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:zero topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:zero topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:zero topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:zero topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:long target:zero topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:long target:zero topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
  ),
  'title:long target:zero topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:zero topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:zero topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:zero topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:zero topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_TARGET_POSITIVE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:free url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:long target:huge topic:free url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:long target:huge topic:free url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:free url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:long target:huge topic:free url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:free url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:free url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:huge topic:free url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:free url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:free url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:huge topic:free url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:free url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:none url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:huge topic:none url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:huge topic:none url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:none url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
  ),
  'title:long target:huge topic:none url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:none url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:none url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:huge topic:none url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:none url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:none url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:huge topic:none url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:none url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_REQUIRED',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:missing url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:huge topic:missing url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:huge topic:missing url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:missing url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
  ),
  'title:long target:huge topic:missing url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:missing url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:missing url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:huge topic:missing url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:missing url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:missing url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:huge topic:missing url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:missing url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:shadow url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:huge topic:shadow url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:huge topic:shadow url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:shadow url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:huge topic:shadow url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:shadow url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:shadow url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:huge topic:shadow url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:shadow url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:shadow url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:huge topic:shadow url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:shadow url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_NOT_FOUND',
	3 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	4 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	5 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:taken url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:huge topic:taken url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:huge topic:taken url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:taken url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
  ),
  'title:long target:huge topic:taken url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:taken url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:taken url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:huge topic:taken url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:taken url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:taken url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:huge topic:taken url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:taken url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_TOPIC_HAS_CAMPAIGN',
	3 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	4 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:own url:none link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:long target:huge topic:own url:none link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:long target:huge topic:own url:none link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:own url:ok link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
  ),
  'title:long target:huge topic:own url:ok link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:own url:ok link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:own url:js link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
  ),
  'title:long target:huge topic:own url:js link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:own url:js link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_INVALID',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
  'title:long target:huge topic:own url:long link:ok' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
  ),
  'title:long target:huge topic:own url:long link:empty' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_REQUIRED',
  ),
  'title:long target:huge topic:own url:long link:long' =>
  array (
	0 => 'DONATIONCAMPAIGNS_ERROR_TITLE_TOO_LONG',
	1 => 'DONATIONCAMPAIGNS_ERROR_AMOUNT_TOO_LARGE',
	2 => 'DONATIONCAMPAIGNS_ERROR_URL_TOO_LONG',
	3 => 'DONATIONCAMPAIGNS_ERROR_LINK_TEXT_TOO_LONG',
  ),
);
