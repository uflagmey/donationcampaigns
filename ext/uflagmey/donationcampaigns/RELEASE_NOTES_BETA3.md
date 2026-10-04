# Donation Campaigns — Release Notes

## 1.0.0-beta3

**Status:** Third public beta, in preparation. It implements two more points of
the review feedback received on phpBB.com: a board-wide campaign list with an
ACP switch, and creating a campaign together with a new topic, like a poll.

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

## Fixed

- Topic titles containing `&`, `"` or `<` were shown escaped twice on the
  management page, the campaign form and the ACP campaign list — a topic called
  "Kosten & Miete" read "Kosten &amp; Miete". phpBB stores topic titles
  already escaped; the extension now prints them as phpBB does. A test now
  rejects escaping such a field again.
- A campaign with a target of zero (possible only by editing the database
  directly) was shown as "target reached". It no longer is.

---

## Upgrade from 1.0.0-beta2

1. Disable the extension (not "Delete data"), upload the new files over the
   old ones and check that all twelve folders arrived — see *Updating to a
   newer version* in the administrator guide. Then enable it (or run the
   migration runner as usual) and **purge the cache**.
2. The migration `m10_campaign_list` adds one setting,
   `donationcampaigns_list_enabled`, switched **off**. Nothing else changes in
   the database.
3. Switch the campaign list on in the ACP settings if you want it.

---

## For translators

New keys:

| File | Key |
|---|---|
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_TITLE` — page title, breadcrumb and quick-links entry ("Spendenkampagnen") |
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_EMPTY` |
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_CAMPAIGN`, `…_FORUM`, `…_PROGRESS` — column headings |
| `common.php` | `DONATIONCAMPAIGNS_PUBLIC_LIST_TOTAL` — plural array, "%d campaign" / "%d campaigns" |
| `info_acp_donationcampaigns.php` | `DONATIONCAMPAIGNS_SETTINGS_LIST_ENABLED`, `DONATIONCAMPAIGNS_SETTINGS_LIST_ENABLED_EXPLAIN` |
| `common.php` | `DONATIONCAMPAIGNS_POSTING_TAB` — the tab in the posting form ("Spendenkampagne") |
| `common.php` | `DONATIONCAMPAIGNS_POSTING_EXPLAIN`, `DONATIONCAMPAIGNS_POSTING_ATTACH` — panel text and checkbox |
| `common.php` | `DONATIONCAMPAIGNS_POSTING_TITLE_EXPLAIN`, `DONATIONCAMPAIGNS_POSTING_TITLE_PLACEHOLDER` — "leave empty to use the topic title" |
| `logs.php` | `LOG_DONATIONCAMPAIGNS_POSTING_CREATE_FAILED` — critical log entry, one `%s` (topic ID) |

The public list keys are deliberately `…_PUBLIC_LIST_…`: the ACP campaign list
already uses `DONATIONCAMPAIGNS_LIST_…`, and those keys are unchanged.
