# Privacy and data handling

What this extension stores, what it never stores, and what publishing a donor's
name actually does.

This document describes **technical behaviour**. It is not legal advice and
makes no claim of compliance with GDPR or any other regime. Whether your use of
this extension is lawful is a question for you and, if the answer matters, your
own advisers.

See also: [administrator guide](ADMIN_GUIDE.md) · [README](../README.md)

---

## What is stored

Two tables, and nothing else.

### Campaigns — `phpbb_ufdc_campaigns`

| Column | What it holds |
|---|---|
| `campaign_id` | Internal identifier |
| `topic_id` | The phpBB topic the campaign is attached to |
| `campaign_title` | Title, as the administrator typed it |
| `campaign_desc` | Description, plus three columns of phpBB BBCode metadata |
| `target_amount` | Target, as a whole number of the smallest currency unit |
| `collected_amount` | Cached sum of the campaign's donations |
| `campaign_enabled` | Whether the public box is shown |
| `show_donor_names` | Whether donor names may be listed |
| `show_donation_count` | Whether the number of donations is shown |
| `external_url` | Optional donation link |
| `campaign_created`, `campaign_updated` | Timestamps |

### Donations — `phpbb_ufdc_donations`

| Column | What it holds |
|---|---|
| `donation_id` | Internal identifier |
| `campaign_id` | The campaign it belongs to |
| `donation_amount` | Amount, as a whole number of the smallest currency unit |
| `donor_name` | **A free-text display name, typed by the administrator** |
| `donation_time` | The date the payment was received |
| `donation_public` | Whether the name may be shown |
| `donation_created`, `donation_updated` | Timestamps |

Four board settings are also stored: currency code, symbol, decimal places, and
how many donors the public box lists.

## What is not stored

None of the following exists anywhere in this extension — not in a column, not
in a log, not in a cache:

- Bank account numbers, IBANs, BICs, sort codes
- Payment credentials of any kind
- PayPal, Stripe or any other transaction or reference identifier
- Payment-provider callbacks or webhook payloads
- Card numbers or any card data
- Proof-of-payment files, screenshots or attachments
- Donor email addresses, postal addresses or telephone numbers
- Donor IP addresses
- Any link between a donation and a phpBB user account
- Private internal notes — there is no notes field

A donation record is an **amount, a date, and a name someone typed**. That is
the whole of it.

The extension makes **no outbound network requests**. It never contacts a
payment provider, an analytics service, or any third party. Nothing is sent
anywhere.

⚠️ These guarantees describe the extension's own fields. An administrator can
type anything into the campaign description or a donor name, including data that
does not belong on a public page. The extension cannot prevent that — see below.

## The donor name is the sensitive part

Everything else here is bookkeeping. The donor name is the one field that can
identify a living person, and the extension makes publishing it a single
checkbox. That deserves stating plainly:

> **The extension can publish a real person's name on a page that guests can
> read and search engines can index. It cannot know whether that person agreed.
> You hold that responsibility, not the software.**

The name is free text: it can be a first name, an initial, a pseudonym, a
company, or a full legal name. What appears is exactly what was typed.

### What "public" means

A campaign box appears on a normal topic. If guests can read that forum, then
the donor list is visible to anyone on the internet and can be crawled, cached
and archived by search engines. Removing a name later does not remove it from
caches and archives that already hold it.

### The three controls

1. **Leave the donor name empty.** The donation is recorded and counted, and
   the box shows *Anonymous*. Nothing identifying is stored at all.
2. **Untick "Show donor publicly"** on the donation. The name is stored but
   never rendered publicly. The donation still counts towards the total and the
   donation count.
3. **Untick "Show donor names"** on the campaign. No names are listed for that
   campaign, whatever the individual donations say.

A private donation is not hidden from the *figures* — its amount is included in
the total and in the count. Only the name is withheld. If a donor must not be
counted at all, do not record the donation.

### Before you publish a name

Ask the donor. The safe default is to record donations anonymously and add a
name only when someone has actually asked to be named.

## Deletion and erasure

| Request | What to do |
|---|---|
| "Remove my name, keep the donation" | Edit the donation, clear the donor name. It becomes *Anonymous*; the total is unchanged |
| "Do not show my name publicly" | Edit the donation, untick *Show donor publicly*. The name is retained but never rendered |
| "Delete my donation entirely" | Delete the donation. The campaign total is recalculated immediately |

Clearing the name is usually what people want: the financial record stays
accurate and nothing identifying remains.

Deletion is immediate and permanent. There is no archive, no soft-delete and no
undo for donation records.

### Cascade behaviour

Campaign and donation data is destroyed automatically when the topic it is
attached to disappears:

| Event | Effect |
|---|---|
| Topic **soft-deleted** | Data kept — the topic is recoverable |
| Topic **permanently deleted**, or pruned | Campaign and all its donations destroyed |
| Forum deleted with its content | Every campaign in it destroyed, with all donations |
| Sub-forum deleted | That sub-forum's campaigns destroyed |
| All posts in a topic deleted | phpBB removes the topic, so the campaign goes too |
| User deleted **with their posts** | Their topics go, and campaigns on those topics with them |

Deletion is atomic — donations are removed before their campaign, so no
donation row is ever left pointing at a campaign that no longer exists.

Note that deleting a phpBB **user** does not by itself remove donations naming
that person: the donor name is free text with no link to an account. Handle
those separately.

### Uninstalling

- **Disable** — data is kept and nothing is shown. Reversible.
- **Purge** — every campaign, every donation, the settings and the permission
  are destroyed. Not reversible.

Back up before purging.

## Logging

Recording, editing and deleting a campaign or donation writes an entry to
phpBB's **admin log**, which administrators can read. Those entries include the
campaign title, or the donation amount and donor name.

That means a donor name can survive in the admin log after the donation itself
is deleted. If an erasure request has to reach the log too, clear it through
**ACP → Maintenance → Admin log**; the extension does not manage that log's
retention.

No logging happens on the public side. Viewing a topic with a campaign records
nothing.
