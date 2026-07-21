# Administrator guide

How to run a donation campaign with this extension, and what to be careful
about. See the [README](../README.md) for what the extension is, and
[PRIVACY.md](PRIVACY.md) for what publishing a donor's name means.

---

## Before you start

This extension is a **ledger of payments you have already received**. It does
not take money, does not talk to PayPal or a bank, and cannot tell whether a
payment arrived. You check your account, and you record what you find.

That means one habit matters more than any setting here:

> **Record a donation only after the money has actually arrived and you have
> verified it yourself.**

Everything the public sees — the total, the progress bar, the donor list — is
built from those records. If you enter a promise, the board will show it as
money received.

---

## 1. Install and enable

Copy the package to `ext/uflagmey/donationcampaigns/`, then
**ACP → Customise → Extensions → Donation Campaigns → Enable**.

Enabling creates the tables, the settings, the permission and the ACP menu.
Disabling later hides everything but keeps your data.

## 2. Grant the permission

**ACP → Permissions → Global permissions → *(role or group)* → Permissions →
Miscellaneous → "Can manage donation campaigns"**

Full and Standard Administrator roles receive it automatically at installation.

This single permission (`a_donationcampaigns`) governs everything: viewing the
pages, creating campaigns, recording donations, deleting them. There is no
read-only variant, so anyone you grant it to can also delete records.

## 3. Configure the currency

**ACP → Extensions → Donation campaigns → Settings**

- **Currency code** — three letters, e.g. `EUR`
- **Currency symbol** — shown next to every amount, e.g. `€`
- **Decimal places** — `2` for most currencies, `0` for yen, `3` for dinar
- **Donors listed** — how many names the public box shows before summarising

### ⚠️ Changing the decimal places later

This is the one setting that can silently misrepresent your figures.

Amounts are stored as whole numbers of the smallest unit — 250.00 € is stored
as `25000` cents. The decimal-places setting decides where the separator is
drawn when that number is displayed. **It converts nothing.**

| Stored | At 2 places | At 3 places |
|---|---|---|
| `25000` | 250.00 | 25.000 |
| `870` | 8.70 | 0.870 |

Change it on a board that already has donations and every historic amount is
re-read, not re-scaled. The extension will not let you do this by accident: if
any campaign or donation exists, it shows a warning and refuses the change until
you tick a confirmation box.

Set this correctly **before** recording your first donation. If you must change
it afterwards, expect to correct every stored amount by hand.

## 3a. ⚠️ Before you merge or split a topic

**Merging a topic into another, or splitting every post out of a topic, deletes
that topic — and with it its campaign and every donation record you have
entered.** phpBB removes the emptied topic automatically, and this extension
cleans up alongside it.

There is no warning and no undo, and a moderator can trigger it without holding
the donation-campaign permission. If a topic carries a campaign, move posts
*into* it rather than merging it away, or export the donation list first.

This is a known defect scheduled to be fixed before the final 1.0 release.

## 4. Create a campaign

Campaigns are created from the topic they belong to. There is no way to create
one from the ACP, and no field anywhere that asks for a topic number.

Open the topic, then:

**Topic tools (the wrench) → Donation campaign**

That opens the campaign form with the topic already fixed. The same menu entry
appears on every topic and always reads *Donation campaign*: if the topic has
no campaign you get a new one to fill in, and if it already has one — even a
disabled one — you get that campaign to edit. Which of the two you see is
decided when you click, not when the page was drawn, so it is never out of
date.

Only administrators holding **Can manage donation campaigns** *and* ACP access
see the entry at all. It is invisible to everyone else, including guests.

| Field | Notes |
|---|---|
| Title | The heading of the public box |
| Topic | Shown, not editable. A campaign belongs to its topic for life |
| Description | Optional. BBCode, smilies and links are permitted |
| Target amount | `250.00` or `250,00`. Must be above zero |
| Donation link | Optional. Must be a full `http://` or `https://` address |
| Link text | The words on the public button, for example *How to donate* or *Über PayPal spenden*. Required whenever a donation link is set |
| Enabled | Untick to hide the box while keeping the data |
| Show donor names | See [PRIVACY.md](PRIVACY.md) first |
| Show donation count | Shows how many donations, without naming anyone |

Save, and the page offers **Back to topic**, where the box now appears.

If someone else created or deleted a campaign for that topic while you had the
form open, nothing you typed is saved. The form comes back with the current
state and says what happened, so a colleague's campaign is never overwritten by
a form that was drawn before theirs existed.

### About the donation link

The link is rendered as a public button. Only `http://` and `https://` are
accepted — `javascript:`, `data:` and protocol-relative addresses are rejected,
and are also re-checked before rendering.

The extension does not visit the address or check that it works. Point it
somewhere you control and have tested.

The button's wording is yours to choose per campaign, so it can describe what
actually happens next — *Request bank details* when the link opens a page of
instructions, *Über PayPal spenden* when it opens a payment page. The button is
a link and nothing else: the extension embeds no provider form, loads no
provider script, shows no logo and accepts no HTML. Clicking it takes the
reader off the board, and you still confirm the money by hand and record it as
a donation afterwards.

A link with no text would be a button with nothing written on it, so the
extension refuses to save that combination rather than inventing a label.

### ⚠️ About publishing payment details

Nothing here stops you putting your bank details in the campaign description,
and people do. Understand what that means before you do it: the description is
shown on a public topic that guests and search engines can read, and account
identifiers published that way are routinely harvested and used in fraudulent
payment requests.

If your board needs to publish payment details, treat it as an operational and
privacy decision in its own right — not as a side effect of filling in a form.
A donation link to a page you control is usually the safer shape.

## 5. Record a payment

Only after it has arrived and you have checked.

**Campaigns → Donations → Add confirmed donation**

| Field | Notes |
|---|---|
| Amount received | The amount that actually arrived. `50.00` or `50,00` |
| Payment received on | The date the **money arrived**, not today's date |
| Donor | The name to show publicly. Leave empty for *Anonymous* |
| Show donor publicly | Untick to count the donation without naming the donor |

Saving recalculates the campaign total immediately.

## 6. Decide whether to name the donor

Three settings interact:

1. **Campaign → Show donor names** — the master switch for that campaign.
2. **Donation → Show donor publicly** — per donation.
3. **An empty donor name** — always displayed as *Anonymous*.

A donation with the public flag off still counts towards the total and the
donation count. Only the name is withheld.

**Ask the donor before publishing their name.** The extension cannot know
whether you did. See [PRIVACY.md](PRIVACY.md).

## 7. Edit or delete a confirmed entry

Editing recalculates the total. So does deleting — the confirmation names the
amount and donor so you can see what you are about to destroy.

Deleting a donation is permanent and there is no undo. A donation cannot be
moved to another campaign; delete it and record it under the right one.

## 8. Recalculate a total

**Campaigns → Recalculate total**

The displayed total is a cached copy of the sum of that campaign's donations. It
is recomputed on every add, edit and delete, so it should never drift. Use this
action if you have edited the database directly, restored a partial backup, or
simply want to confirm the figure — it recomputes from the donation rows and
tells you the value before and after.

It is safe to run at any time and never changes a donation.

## 9. Disable or archive a campaign

Untick **Enabled** and save. The box disappears from the topic; every donation
record is kept, and the campaign stays in the ACP where you can still edit it,
add donations to it, or re-enable it.

Use this when a campaign has finished but you want the records.

## 10. What happens when a topic or forum is deleted

This is the part worth knowing before someone tidies up the board.

| Action | Effect on the campaign |
|---|---|
| **Topic soft-deleted** (moderator's "delete") | **Nothing.** The topic is recoverable, so the campaign and its donations are kept |
| **Topic permanently deleted** | The campaign and **all its donations are permanently destroyed** |
| **Forum deleted** with its content | Every campaign in it, and all their donations, are destroyed |
| **Sub-forum deleted** | Its campaigns are destroyed; the parent's are not |
| **Topic pruned** (manual, moderator, or scheduled) | Same as permanent deletion |
| **All posts in a topic deleted** | phpBB removes the emptied topic, so the campaign goes too |
| **User deleted with their posts** | Their topics are removed, and campaigns on those topics go with them |
| **Topic moved** | Nothing. The campaign follows the topic |

Deletion is atomic: either the campaign and all its donations go, or nothing
does. No orphaned donation rows are left behind.

**There is no warning and no undo.** phpBB does not know it is about to destroy
a financial record. If a campaign's history matters, export it before deleting
its topic — and prefer *disabling* the campaign to deleting the topic.

Purging the extension destroys everything it stores, in every campaign.

---

## Troubleshooting

**The box does not appear on the topic.** Check the campaign is Enabled, that
the campaign is enabled, and purge the cache. The box renders on `viewtopic`; if
the topic itself is unreachable, so is the box.

**The total looks wrong.** Use **Recalculate total**. If it changes, something
wrote to the database outside the extension.

**Amounts are out by a factor of ten or a hundred.** The decimal-places setting
was changed after the data was recorded. See step 3.

**A donor's name appears when it should not.** Check both switches: the
campaign's *Show donor names* and that donation's *Show donor publicly*.

**Menu entries have vanished.** The extension is disabled. Data is intact.
