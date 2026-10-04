# Donation Campaigns — Release Notes

## 1.0.0-beta3

**Status:** Third public beta. It implements two more points of the review
feedback received on phpBB.com: a board-wide campaign list with an ACP switch,
and creating a campaign together with a new topic, like a poll. No breaking
change: an update from beta2 changes nothing visible until the new page is
switched on.

Requires phpBB **3.3.16 or later** (below 4.0). prosilver only. PHP **8.2 or newer**.

---

## New: the board-wide campaign list

A page at `app.php/donationcampaigns` lists the campaigns, newest first, 25 per
page: the campaign title (linked to its topic), the forum, the progress bar,
collected / target / percent and — where the campaign shows it — the number of
donations. **It never shows donor names**; those stay in the topic. While the
page is on, the quick-links menu has a **Donation campaigns** entry.

**Off by default.** Switch it on under ACP → Extensions → Donation campaigns →
Settings → *Show the campaign list page*. While it is off, the address answers
"page not found" and there is no quick-links entry, so an update changes no
public page.

**Each visitor sees exactly the campaigns whose box they could see in the
topic:** the campaign is enabled; the topic exists and has not been moved away;
the visitor may read the forum; the topic is approved and not soft-deleted
(moderators who may approve see those too, as in the forum); and the forum has
no password, or the visitor has already entered it in this session. See
ADR-018 in [DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).

---

## New: create a campaign together with a new topic

When a manager starts a **new topic**, the posting form has a **Donation
campaign** tab next to *Poll creation*. Ticking *Attach a donation campaign to
this topic* and filling in the usual fields creates the campaign together with
the topic. An empty title takes the topic title. Wrong entries stop the post
like a wrong poll does, and nothing typed is lost on preview or error.

- Only for new topics and only for users who may manage campaigns in that forum;
  replies and edits never show the tab. Changes go through *Topic tools* as before.
- A topic that needs approval keeps its campaign hidden until it is approved; a
  disapproved topic takes its campaign with it.
- Drafts do not keep the campaign fields.
- Works without JavaScript: the panel is then simply shown below the others.

See ADR-019 in [DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).

---

## Changed

- The templates, the language strings and two controller messages use HTML5
  syntax: `<br>` and `<input …>` instead of `<br />` and `<input … />`, and
  `checked` instead of `checked="checked"`. The pages look and behave the
  same; this follows the phpBB Extension Check (XHTMLcheck). A test keeps the
  old syntax out.

---

## Fixed

- Topic titles containing `&`, `"` or `<` were shown escaped twice on the
  management page, the campaign form and the ACP campaign list — a topic called
  "Kosten & Miete" read "Kosten &amp; Miete". phpBB stores topic titles
  already escaped; the extension now prints them as phpBB does. A test now
  rejects escaping such a field again.
- A campaign with a target of zero (possible only by editing the database
  directly) was shown as "target reached". It no longer is.
- Leading and trailing whitespace in titles, links, donor names, amounts and
  settings is now trimmed the same way on every supported PHP version. PHP 8.6
  also trims a form feed by default, earlier versions did not; the extension
  now names the characters itself, so PHP 8.2 to 8.5 behave like 8.6. A test
  keeps every trim call explicit.

---

## Upgrade from 1.0.0-beta2

1. Disable the extension (not "Delete data"), upload the new files over the
   old ones and check that all twelve folders arrived — see *Updating to a
   newer version* in the administrator guide. Then enable it (or run the
   migration runner as usual) and **purge the cache**.
2. The migration `m10_campaign_list` adds one setting,
   `donationcampaigns_list_enabled`, switched **off**. Nothing else changes in
   the database, and nothing changes for visitors: no new page, no new
   quick-links entry, until the setting is switched on.
3. The posting-form tab appears at once for users who may manage campaigns in a
   forum. It changes nothing unless its checkbox is ticked.
4. Switch the campaign list on in the ACP settings if you want it.

Updating straight from **1.0.0-beta1**: read the beta2 notes first — the beta1
moderator permissions were replaced and must be re-assigned
([RELEASE_NOTES_BETA2.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/RELEASE_NOTES_BETA2.md)).

---

## For translators

14 new keys, 13 keys changed in markup only, none removed.

**Changed markup:** every `<br />` in the strings is now `<br>` (HTML5).
Please do the same in your translation; the wording is unchanged.

| File | Keys |
|---|---|
| `common.php` | `DONATIONCAMPAIGNS_CAMPAIGN_SAVED_RETURN`, `DONATIONCAMPAIGNS_CAMPAIGN_DELETED_RETURN`, `DONATIONCAMPAIGNS_DONATION_SAVED_RETURN`, `DONATIONCAMPAIGNS_DONATION_DELETED_RETURN` |
| `logs.php` | `LOG_DONATIONCAMPAIGNS_CAMPAIGN_ADDED`, `…_CAMPAIGN_EDITED`, `…_CAMPAIGN_ENABLED`, `…_CAMPAIGN_DISABLED`, `…_CAMPAIGN_DELETED`, `…_TOTAL_RECALCULATED`, `…_DONATION_ADDED`, `…_DONATION_EDITED`, `…_DONATION_DELETED` |

**New keys:**

| File | Key | Note |
|---|---|---|
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_TITLE` | Page title, breadcrumb and quick-links entry ("Spendenkampagnen") |
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_EMPTY` | Shown when the visitor may see no campaign |
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_CAMPAIGN`, `DONATIONCAMPAIGNS_PUBLIC_LIST_FORUM`, `DONATIONCAMPAIGNS_PUBLIC_LIST_PROGRESS` | Column headings |
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_TOTAL` | Plural array: `1 => '%d campaign'`, `2 => '%d campaigns'` |
| `common.php` | `DONATIONCAMPAIGNS_POSTING_TAB` | The tab in the posting form ("Spendenkampagne") |
| `common.php` | `DONATIONCAMPAIGNS_POSTING_EXPLAIN` | Text at the top of the panel |
| `common.php` | `DONATIONCAMPAIGNS_POSTING_ATTACH` | The checkbox that switches the campaign on |
| `common.php` | `DONATIONCAMPAIGNS_POSTING_TITLE_EXPLAIN`, `DONATIONCAMPAIGNS_POSTING_TITLE_PLACEHOLDER` | "Leave empty to use the topic title" and the placeholder in the empty field |
| `info_acp_donationcampaigns.php` | `DONATIONCAMPAIGNS_SETTINGS_LIST_ENABLED`, `DONATIONCAMPAIGNS_SETTINGS_LIST_ENABLED_EXPLAIN` | The ACP switch |
| `logs.php` | `LOG_DONATIONCAMPAIGNS_POSTING_CREATE_FAILED` | Critical log entry; one `%s`, the topic ID |

The public list keys are deliberately `…_PUBLIC_LIST_…`: the ACP campaign list
already uses `DONATIONCAMPAIGNS_LIST_…`, and those keys are unchanged.
