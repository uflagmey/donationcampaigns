# Donation Campaigns — Release Notes

## 1.0.0-beta1

**Status:** First public beta of the new, frontend-based management
architecture — campaign and donation management happen on the topic, under
forum-scoped moderator permissions, rather than in the ACP. See *Known
limitations* and the release-blocking item below before deploying to a
production board.

Requires phpBB **3.3.16 or later** (below 4.0). prosilver only. PHP 7.2–8.x.

---

## What the extension does

An administrator, or an authorised forum moderator, attaches a fundraising
campaign to a topic. The topic then shows a box above its first post with a
title, an optional description, a target amount, the amount collected, a
progress bar, and — optionally — the donation count and the names of donors who
agreed to be listed.

The extension **records confirmed donations; it does not process payments.** The
public total is the sum of donation records entered by hand after money actually
arrived. An optional button links to wherever a board chooses to take payment
(PayPal, a bank-details page, another topic); the extension integrates with no
provider.

---

## Headline change — management moved from the ACP to the topic

Campaigns *and* donations are now managed from the topic, not the ACP. The
topic-tools **Donation campaign** entry opens a management landing
(`/app.php/donationcampaigns/topic/{topic_id}`) that resolves state on arrival:
the create form when there is no campaign yet, otherwise a campaign summary plus
only the actions the viewer is authorised for. The service layer and the
`Repository → Service` core are unchanged; the ACP module's write paths were
replaced by thin frontend controllers. See ADR-015 in [DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).

### Three permissions

Grouped under a dedicated **Donation Campaigns** permission category:

- **`a_donationcampaigns`** — administrator, global. Full ACP access, the
  admin-only maintenance, and an override for every frontend action on every
  forum. Granted to the admin roles on install.
- **`m_donationcampaigns_manage`** — moderator, forum-scoped. Manage the
  campaign *shell* on topics in that forum: create, edit, enable/disable, delete
  an *empty* campaign. Does not grant donation management.
- **`m_donationcampaigns_donations`** — moderator, forum-scoped. Manage the
  donation *ledger*: add, edit, delete confirmed donations.

The two `m_` permissions are independent; `a_donationcampaigns` overrides both.

> ⚠️ **`m_donationcampaigns_donations` exposes personal data** — donor names,
> private donor identities and confirmed amounts. Grant it only to people
> trusted with that information.

> ⚠️ **Granting either `m_` permission makes the grantee a forum moderator.**
> phpBB treats anyone holding an `m_` permission on a forum as a moderator of
> it, so the grantee appears in that forum's Moderator list and gains moderator
> standing there. This is inherent to phpBB and is a real (if limited)
> promotion, not a hidden grant.

### Deletion policy — split by surface

- **From the topic**, only an *empty* campaign can be deleted; a non-empty one
  is refused (disable it, or ask an administrator).
- **From the ACP**, an administrator *may* hard-delete a *non-empty* campaign,
  cascading its donations, behind a confirmation that names the campaign and its
  donation count.

Donations are deleted individually from the topic behind a confirmation naming
the amount and donor. All deletion is permanent; totals are always recomputed
from the donation rows.

### Audit by surface

Frontend actions (campaign create/edit/enable/disable/delete; donation
add/edit/delete) are written to the **moderator log**, scoped to the forum and
topic, and read in the MCP. ACP actions (hard-delete, recalculate, settings) are
written to the **administrator log**. The log an entry lands in tells you where
the action was performed.

### The ACP is now read-only oversight plus admin maintenance

ACP → Donation campaigns keeps Settings, a read-only Campaigns list and a
read-only Donations list. Its former create/edit forms are gone; the list rows
link out to the frontend routes on the topic. It retains admin-only maintenance:
**Recalculate total** and campaign hard-delete, both requiring
`a_donationcampaigns`.

---

## Localisation

The extension ships **English and German** language packs (`language/en` and
`language/de`), kept at parity by a test.

---

## Major architectural decisions

Recorded in full in [DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md); summarized here.

| ADR | Decision |
|---|---|
| 001 | Target phpBB 3.3.x only |
| 002 | Money as integer minor units; one board-wide currency |
| 003 | The collected total is denormalised but always recalculated from `SUM()`, never delta-adjusted |
| 004 | Donors are free text, not references to forum users |
| 005 | Two services and two repositories; business logic in services, persistence in repositories |
| 006 | Public box renders at the `viewtopic_body_poll_before` template event |
| 007 | Two deletion listeners feed one unified cleanup operation |
| 008 | A single `a_donationcampaigns` permission (superseded by ADR-015) |
| 009 | The concurrent-write race is accepted and mitigated operationally (unique index is authoritative) |
| 010 | No JavaScript is required for any essential function |
| 011 | Policy and coding-standard violations fail the commit that introduces them |
| 012 | Every shared-namespace identifier carries the package machine name |
| 013 | Version 1.0 officially supports prosilver only |
| 014 | Campaigns are created only from their topic; the ACP resolves state at request time (superseded by ADR-015) |
| 015 | Frontend-primary management with forum-scoped moderator permissions; ACP becomes read-only oversight plus admin maintenance |

The layering (`Repository → Service → Controller / Listener / ACP Module`) is
unchanged. The extension never modifies phpBB core and adds no public write
route.

---

## Known limitations

These are deliberate boundaries of the scope, not defects.

- **prosilver only.** A style that does not provide the
  `viewtopic_topic_tools_after` template event offers no way to manage a
  campaign, because that is the only management entry point (ADR-013 / 015).
- **No payment processing** and no provider integration, by design. The button
  links out; nothing is charged, and no transaction data is stored.
- **No public donation form.** Every entry is made from the topic by an
  administrator or an authorised forum moderator.
- **A campaign cannot be moved to another topic** once created.
- **One campaign per topic**, enforced by a unique index.
- **Donation dates are date-only**, stored as UTC midnight; the board timezone
  is not applied to them.
- **No bulk deletion of donations.**
- **Campaigns can attach to a soft-deleted or unapproved topic.** The box simply
  does not render until the topic is visible.

---

## Known issue — RELEASE-BLOCKING before 1.0 final

**Merging topics, or splitting every post out of a topic, deletes that topic —
and with it its campaign and every confirmed donation record.**

phpBB empties the source topic during a merge or full split, then removes it via
`delete_topics()` (`functions_admin.php:2058`), which fires
`core.delete_topics_before_query` — the event this extension's cascade listens
to. The cleanup then removes the campaign and its donation rows.

The deletion is **silent** and **irreversible**, and it can be triggered by a
moderator who does not hold any donation permission. These are records of money
actually received.

The cascade itself is correct in isolation — an orphaned campaign pointing at a
dead topic would be worse. The fix belongs at the level of distinguishing a
topic being *destroyed* from a topic being *emptied into another*, and must be
designed rather than patched. Documented for administrators in
`docs/ADMIN_GUIDE.md` and for developers, with the verified call chain, in
[DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).

---

## Upgrade / migration

A new migration adds the two forum-scoped moderator permissions,
`m_donationcampaigns_manage` and `m_donationcampaigns_donations`. **Neither is
granted to anyone on install.** Existing `a_donationcampaigns` access is
unchanged, so upgrading administrators keep working exactly as before, and **no
moderator gains any access until a board owner explicitly grants the opt-in
permissions** on the forums they should manage.

Existing campaigns and donations continue to work with no data step.

---

## Why beta

The management architecture is new — a frontend surface, new controllers, and a
new permission model — so this release is a beta, to gather real-world
verification of that surface before a final 1.0. The reasoning is recorded in
full as ADR-015 in [DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).
