# Donation Campaigns — Release Notes

## 1.0.0-rc1

**Status:** Release Candidate. Feature-complete for the 1.0 line. Not a final
release — see *Known limitations* and the release-blocking item below before
deploying to a production board.

Requires phpBB **3.3.16 or later** (below 4.0). prosilver only. PHP 7.2–8.x.

---

## What the extension does

An administrator attaches a fundraising campaign to a topic. The topic then
shows a box above its first post with a title, an optional description, a target
amount, the amount collected, a progress bar, and — optionally — the donation
count and the names of donors who agreed to be listed.

The extension **records confirmed donations; it does not process payments.** The
public total is the sum of donation records an administrator has entered by hand
after money actually arrived. An optional button links to wherever a board
chooses to take payment (PayPal, a bank-details page, another topic); the
extension integrates with no provider.

---

## What was completed

- Public campaign box on `viewtopic`, rendered through a verified core event,
  with an accessible progress bar that needs no JavaScript.
- ACP management: campaign list, per-campaign donation records, board-wide
  currency and display settings.
- **Campaign creation from the topic it belongs to** (RC1's headline change).
  A neutral *Donation campaign* entry in the topic tools menu opens the ACP
  form with the topic already fixed; the ACP resolves at request time whether
  to create or edit. There is no context-free ACP creation path and no raw
  Topic ID field anywhere in the normal UI. See ADR-014.
- Money handled entirely as integer minor units; a localized currency formatter
  parses and displays amounts in the reader's language, accepting both `.` and
  `,` as the decimal separator and rejecting grouped input.
- German language pack, at parity with English (enforced by a test).
- Cascade cleanup when a topic or forum is deleted, so a campaign never outlives
  the topic it points at.
- ACL-gated writes, CSRF on every form, server-side validation, output escaped
  at the template boundary, and a scheme allowlist on the external link.

---

## Major architectural decisions

Recorded in full in `docs/DEVELOPERS.md`; summarized here.

| ADR | Decision |
|---|---|
| 001 | Target phpBB 3.3.x only |
| 002 | Money as integer minor units; one board-wide currency |
| 003 | The collected total is denormalised but always recalculated from `SUM()`, never delta-adjusted |
| 004 | Donors are free text, not references to forum users |
| 005 | Two services and two repositories; business logic in services, persistence in repositories |
| 006 | Public box renders at the `viewtopic_body_poll_before` template event |
| 007 | Two deletion listeners feed one unified cleanup operation |
| 008 | A single `a_donationcampaigns` permission |
| 009 | The concurrent-write race is accepted and mitigated operationally (unique index is authoritative) |
| 010 | No JavaScript is required for any essential function |
| 011 | Policy and coding-standard violations fail the commit that introduces them |
| 012 | Every shared-namespace identifier carries the package machine name |
| 013 | Version 1.0 officially supports prosilver only |
| 014 | Campaigns are created only from their topic; the ACP resolves state at request time and never trusts an action verb from the URL |

The architecture is **frozen** for the 1.0 line: `Repository → Service →
Listener / ACP Module`. The extension never modifies phpBB core and adds no
public write route.

---

## Known limitations

These are deliberate boundaries of the 1.0 scope, not defects.

- **prosilver only.** A style that does not provide the
  `viewtopic_topic_tools_after` template event offers no way to create a
  campaign, because that is now the only creation entry point (ADR-013 / 014).
- **No payment processing** and no provider integration, by design. The button
  links out; nothing is charged, and no transaction data is stored.
- **A campaign cannot be moved to another topic** once created.
- **One campaign per topic**, enforced by a unique index.
- **Donation dates are date-only**, stored as UTC midnight; the board timezone
  is not applied to them.
- **Campaigns can attach to a soft-deleted or unapproved topic.** The box simply
  does not render until the topic is visible. Left unchanged for 1.0 (a
  deliberate RC decision).

---

## Known issue — RELEASE-BLOCKING before 1.0 final

**Merging topics, or splitting every post out of a topic, deletes that topic —
and with it its campaign and every confirmed donation record.**

phpBB empties the source topic during a merge or full split, then removes it via
`delete_topics()` (`functions_admin.php:2058`), which fires
`core.delete_topics_before_query` — the event this extension's cascade listens
to. The cleanup then removes the campaign and its donation rows.

Three properties make this serious: the deletion is **silent**, it is
**irreversible**, and it can be triggered by a **moderator who does not hold
`a_donationcampaigns`** and receives no indication anything was lost. These are
records of money actually received.

The cascade itself is correct in isolation — an orphaned campaign pointing at a
dead topic would be worse. The fix belongs at the level of distinguishing a
topic being *destroyed* from a topic being *emptied into another*, and must be
designed rather than patched. It must **not** be addressed by restoring a
context-free creation form so the campaign can be retyped.

**A dedicated design/fix task is required before 1.0.0 final.** Documented for
administrators in `docs/ADMIN_GUIDE.md` and for developers, with the verified
call chain, in `docs/DEVELOPERS.md`.

---

## Intentionally deferred to 1.1

Out of scope for the frozen 1.0 line; recorded so they are not mistaken for
oversights.

- **Merge/split data-loss fix** (the release-blocking item above) — must land
  before *final* 1.0.0, and is the one deferred item that gates the release.
- Support for styles other than prosilver.
- Any payment-provider integration.
- Moving a campaign between topics.
- Per-forum or multi-currency support.
- A visibility check that would refuse campaigns on hidden topics.

New functionality that changes product scope is deferred to 1.1 by policy for
the duration of the RC phase.

---

## Test status

- **1360 automated tests, 6822 assertions — all passing** (PHPUnit 9.6).
- **PHPCS** clean across the whole package (phpBB coding standard).
- **`php -l`** clean on every production file.
- Coverage spans unit (currency, language parity, architecture rules),
  service and repository layers against a real SQLite database, migrations,
  ACP module behaviour, the topic-context resolution and its race guard, and
  the public listener.
- Architecture rules are themselves tested: no SQL outside repositories, no
  floating-point money, no PHP-side double-escaping, the total written only by
  the donation service.

---

## Live verification status

Verified on the local Docker phpBB 3.3.17 board (the project's official
integration environment), across administrator, permission-limited
administrator, and guest roles:

- Topic-tools link appears for authorised admins in both action bars, with no
  duplicate DOM id; absent for guests and for an admin lacking either required
  permission.
- Create-from-topic, edit-from-topic, and the neutral label resolving correctly
  on topics with no campaign, an enabled campaign, and a disabled campaign.
- Expired admin session round-trips back to the correct form.
- URL tampering (`t=999999`, `t=-1`, `t=abc`, and a crafted
  `action=delete&campaign_id=…`) refused or ignored; nothing destroyed.
- Comma-decimal amount parsed without floating point.
- ACP list has no creation action; empty state explains where campaigns come
  from.
- RC1 polish confirmed visually: currency shown beside the amount field, campaign
  title rendered as entered (no forced uppercase), description at post-text size.
- No PHP, Twig, or JavaScript errors; no broken layout; functions with
  JavaScript disabled.

One item carries a caveat: the figures-line font size has a correct, tested CSS
rule but could not be given a trustworthy live pixel measurement; the visible
result is correct in the verification screenshots.

---

## Upgrade / migration

No database migration is introduced by the RC1 changes. Existing campaigns
continue to work and resolve to the edit form with no data step.
