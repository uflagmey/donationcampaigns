# Donation Campaigns

A phpBB 3.3 extension that attaches a fundraising campaign to a topic and shows
its progress above the first post.

- [Administrator guide](docs/ADMIN_GUIDE.md) — the day-to-day workflow
- [Privacy and data handling](docs/PRIVACY.md) — what is stored, and what publishing a donor name means
- [Developer notes](docs/DEVELOPERS.md) — architecture, contracts, tests

---

## What it does

An administrator — or an authorised forum moderator — creates a campaign,
points it at an existing topic, and sets a target amount. The topic then shows
a box above its first post with the target, the amount collected, a progress
bar, and — optionally — the number of donations and the names of donors who
agreed to be named.

As money arrives, an administrator or an authorised forum moderator records
each payment from the topic. The public total is the sum of those records and
nothing else.

## What it does not do

Read this part before installing. It is the difference between what this
extension is and what people often assume a "donation extension" is.

- **It does not process payments.** No PayPal, no Stripe, no card handling, no
  webhooks, no callbacks. The extension never contacts a payment provider and
  makes no outbound requests of any kind.
- **It records confirmed receipts only.** A donation row means money that has
  *already arrived* and that an administrator has verified. It is bookkeeping,
  not a transaction.
- **No visitor or member can submit or confirm a donation.** There is no public
  form. Every entry is made from the topic by someone holding the appropriate
  permission — an administrator, or a forum moderator granted the forum-scoped
  donation permission for that forum.
- **It does not verify anything.** It cannot check a bank statement or a
  provider's records. Whether the money truly arrived is the administrator's
  judgement, and the extension records that judgement.
- **It stores no payment data** — no account numbers, no IBANs, no transaction
  identifiers, no card details, no proof-of-payment files. See
  [PRIVACY.md](docs/PRIVACY.md).
- **The action button is a plain external link.** Each campaign sets its own
  button text and destination — "How to donate", "Request bank details",
  "Über PayPal spenden". The destination may be a PayPal link, a page of bank
  instructions, another phpBB topic, a GoFundMe page or any other `https://`
  address. It is a link and nothing more: no provider script, no embedded
  form, no logo, no HTML. Whoever clicks it leaves the board, and the
  administrator still confirms the money by hand afterwards.

### Using PayPal, or anything else

There is nothing to configure and no integration to enable. Create a hosted
donation link in your own PayPal account, paste it into the campaign's
**Donation link**, and set **Link text** to whatever the button should say —
for example *Donate via PayPal*. The same field takes a GoFundMe or Betterplace
page, a bank-information page, an internal phpBB topic, or any other valid
`https://` address, and each campaign can point somewhere different.

The extension treats every one of these as an ordinary external link. It does
not know which provider a URL belongs to, stores no merchant identifier, and
performs no verification: whoever clicks the button leaves the board, and the
administrator records the money by hand afterwards. That is the whole design,
and it is intentional rather than unfinished — see
[DEVELOPERS.md](docs/DEVELOPERS.md).

## Requirements

| | |
|---|---|
| phpBB | **3.3.16 or later**, below 4.0. Enforced by `ext.php`; installing on an older 3.3.x fails cleanly |
| PHP | 7.2 – 8.x |
| Database | See below |
| Style | prosilver |
| Language | English and German |

### Verified versus designed-for

Stating this precisely matters more than making the table look full.

| | Actually executed | Designed for, not executed |
|---|---|---|
| **phpBB** | 3.3.17 | 3.3.16 (the supported floor, but never run) |
| **PHP** | 8.1 (board), 7.4/8.0/8.1 (tests) | 7.2, 7.3 — syntax-checked only; PHPUnit 9 needs ≥ 7.3 |
| **Database** | SQLite 3, MariaDB 10.11 | MySQL, PostgreSQL, MS SQL Server, Oracle |

The extension uses phpBB's DBAL throughout and writes no database-specific SQL,
so the untested engines are expected to work — but "expected" is not "verified",
and this table will not claim otherwise.

**prosilver is the only officially supported style** (ADR-013). Styles that
inherit from prosilver may work; that is neither tested nor supported.

## Installation

1. Copy the package to `ext/uflagmey/donationcampaigns/` in your board.
2. **ACP → Customise → Extensions → Donation Campaigns → Enable**.

Or from the command line:

```
php bin/phpbbcli.php extension:enable uflagmey/donationcampaigns
php bin/phpbbcli.php cache:purge
```

### Lifecycle

| Action | Effect |
|---|---|
| **Enable** | Creates two tables, four configuration settings, the three permissions and their permission category, and the ACP menu |
| **Disable** | Hides the campaign box and the ACP menu. **All data is kept**, and re-enabling restores everything |
| **Purge** | **Destroys all campaigns and donations**, and removes the tables, settings and permissions. This cannot be undone |
| **Upgrade** | Replace the files and run `php bin/phpbbcli.php db:migrate`. Migrations are idempotent and safe to re-run |

Take a database backup before purging. "Disable" is the reversible one; "purge"
is not.

## Permissions

Three permissions, grouped under a dedicated **Donation Campaigns** category in
the permissions UI:

- **`a_donationcampaigns`** — administrator, **global**. Full access: the ACP
  settings and read-only oversight lists, the admin-only maintenance
  (recalculate a total; hard-delete a non-empty campaign), and — as an override
  — every frontend campaign and donation action on every forum. Granted to
  `ROLE_ADMIN_FULL` and `ROLE_ADMIN_STANDARD` on installation, if those roles
  exist.
- **`m_donationcampaigns_manage`** — moderator, **forum-scoped**. Lets the
  holder manage the campaign *shell* on topics in that forum: create, edit,
  enable/disable, and delete an *empty* campaign. It does **not** grant donation
  management.
- **`m_donationcampaigns_donations`** — moderator, **forum-scoped**. Lets the
  holder manage the donation *ledger* on topics in that forum: add, edit and
  delete confirmed donations. ⚠️ This permission exposes donor names, private
  donor identities and confirmed amounts — grant it only to people you trust
  with that personal data.

The two `m_` permissions are **independent**: holding one does not grant the
other. `a_donationcampaigns` overrides both everywhere; it is not granted on
install to non-admins.

**Granting either `m_` permission makes the grantee a forum moderator.** phpBB
defines a forum moderator as anyone holding an `m_` permission on that forum, so
a user or group you grant a donation permission to appears in that forum's
**Moderator** list on topic and forum views and gains moderator standing there.
This is inherent to phpBB and is not a silent, invisible grant — treat it as
promoting the grantee to a limited moderator of that forum.

## Configuration

**ACP → Extensions → Donation campaigns → Settings**

| Setting | Default | Notes |
|---|---|---|
| Currency code | `EUR` | Three letters, e.g. `EUR`, `USD`, `GBP` |
| Currency symbol | `€` | Up to 10 characters |
| Decimal places | `2` | 0–4. `0` for yen, `3` for dinar |
| Donors listed | `25` | 1–500, before the box summarises the rest |

⚠️ **Changing "Decimal places" after donations exist changes how every stored
amount is read, and converts nothing.** An amount stored as `1000` shows as
`10.00` at two places and `1.000` at three. The extension refuses the change
unless you confirm it. See the [administrator guide](docs/ADMIN_GUIDE.md).

## Campaign workflow

Campaigns are managed **from the topic**, not the ACP.

1. Open the topic the campaign is for.
2. **Topic tools (the wrench) → Donation campaign**. This opens the management
   landing page for that topic; the topic is already fixed, with nothing to type
   and no ID to look up.
3. The landing resolves the current state: with no campaign yet (and permission
   to manage the shell) you get the create form; with a campaign already there
   you get its summary plus only the actions you are authorised for.
4. On the create/edit form, enter a title, an optional BBCode description, and
   the target; optionally add a donation link (`http://` or `https://` only);
   and choose whether to show donor names and the donation count.
5. Save, then **Back to topic** — the box is there.

Campaign actions (create, edit, enable/disable, delete an empty campaign)
require `a_donationcampaigns` or `m_donationcampaigns_manage` in that forum. The
ACP shows a read-only oversight list of campaigns and admin-only maintenance; it
cannot create or edit one.

One campaign per topic, enforced by a unique database index. A campaign cannot
be attached to the stub left behind by a moved topic — phpBB treats such a stub
as non-existent, so a campaign there would be unreachable.

## Donation workflow

1. **Wait for the money to actually arrive.**
2. On the topic, **Topic tools → Donation campaign** to open the management
   landing, then **Add confirmed donation**. The donation ledger — the button
   and the per-row Edit/Delete controls — is shown only to holders of
   `a_donationcampaigns` or `m_donationcampaigns_donations` in that forum; a
   manage-only holder does not see it.
3. Enter the amount received, the date it arrived, and the donor's display name.
4. Decide whether the donor may be named publicly — see
   [PRIVACY.md](docs/PRIVACY.md) before ticking that box.
5. Save. The campaign total is recalculated immediately from all its donations.

Amounts accept both `50.00` and `50,00`. Leave the name empty to record the
donation as *Anonymous*. A donation marked private still counts towards the
total and the count; only the name is withheld.

The total is always recomputed as the sum of the donation rows, never adjusted
by arithmetic, so a figure that has somehow drifted is corrected by the next
edit — or immediately, using **Recalculate total** in the ACP.

The ACP shows a read-only oversight list of donations; recording, editing and
deleting them happens on the topic.

## Known limitations

- No payment processing, and no provider-specific integration. Any provider
  can be linked to; none is integrated with. This is a design decision, not a
  gap — see [DEVELOPERS.md](docs/DEVELOPERS.md).
- No public donation form; every entry is made from the topic by an
  administrator or an authorised forum moderator.
- **Campaigns can only be managed from their topic.** A style that does not
  provide the `viewtopic_topic_tools_after` template event therefore offers no
  way to create one — see the prosilver note above.
- **A campaign cannot be moved to another topic** once created.
- Donation dates are date-only and stored as UTC midnight; the board's timezone
  is not applied to them.
- prosilver only.
- No bulk deletion of donations.
- The ACP campaign list and donation list paginate at 25 rows; there is no
  search.
- **Not yet reviewed in a browser across styles and screen sizes.**
- Databases other than SQLite and MariaDB have not been executed.
- The phpBB validation and development policies have not been reviewed, so no
  claim of compliance with them is made.

## Licence

GPL-2.0-only. See [license.txt](license.txt).

phpBB is a trademark of phpBB Limited. This extension is not affiliated with or
endorsed by phpBB Limited.
