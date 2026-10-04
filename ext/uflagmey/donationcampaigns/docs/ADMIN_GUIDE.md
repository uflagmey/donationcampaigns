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

Enabling creates the tables, the settings, the three permissions and their
permission category, and the ACP menu. Disabling later hides everything but
keeps your data.

**Updating to a newer version:**

1. **Disable** the extension. Do *not* click "Delete data" — that removes every
   campaign and donation.
2. Upload the new files **over** the existing ones. Overwriting is safe; there
   is no need to delete the old folder first, and not deleting it means an
   interrupted upload leaves a working version behind.
3. **Check the upload is complete.** The extension folder must contain twelve
   folders: `acp`, `adm`, `config`, `controller`, `docs`, `event`, `exception`,
   `language`, `migrations`, `repository`, `service`, `styles`. FTP clients can
   skip folders without an obvious error.
4. **Enable** the extension — this runs any new database migrations — and
   purge the cache.

## 2. Grant the permissions

The extension ships three permissions, grouped under a dedicated **Donation
Campaigns** category in the permissions UI.

**`a_donationcampaigns`** — administrator, granted globally. Full and Standard
Administrator roles receive it automatically at installation. It governs the ACP
(settings, the read-only oversight lists, and the admin-only maintenance:
recalculate a total, hard-delete a non-empty campaign) and, as an override,
every frontend campaign and donation action on every forum the administrator
can read. An administrator can do everything from the topic as well as from the
ACP.

The other two are **forum permissions, forum-scoped** — you grant them to any
user or group, moderators or not, on the specific forums whose campaigns they
should manage. They sit on the **Forum permissions** tab, next to phpBB's own
"Can post polls":

**ACP → Permissions → Group's forum permissions** (or User's forum
permissions) → choose the group and the forum(s) → **Advanced permissions** →
category **Donation Campaigns**.

- **`f_donationcampaigns_manage`** lets the holder manage the campaign *shell* on
  topics in that forum: create, edit, enable/disable, and delete an *empty*
  campaign. It does **not** grant donation management.
- **`f_donationcampaigns_donations`** lets the holder manage the donation
  *ledger* on topics in that forum: add, edit and delete confirmed donations.

The two forum permissions are **independent**: holding one does not grant the
other. `a_donationcampaigns` overrides both. Neither is granted on installation,
and neither is part of any default role: assign them explicitly, or add them to
a forum role of your own.

**Read access is required.** Whoever manages a campaign — including an
administrator through the override — must be able to read the forum. Without
*Can read forum* there, the management entry is not shown and every management
page answers "not found".

Granting these permissions gives the holder nothing else: no access to the
Moderator Control Panel, no other moderator ability, and no listing as a
moderator of the forum.

> ⚠️ **`f_donationcampaigns_donations` exposes personal data.** It reveals donor
> names, private donor identities and confirmed amounts. Grant it only to people
> you trust with that information.

> **Upgrading from 1.0.0-beta1.** beta1 used two *moderator* permissions
> (`m_donationcampaigns_manage`, `m_donationcampaigns_donations`). The update
> removes them together with their grants and with the moderator standing they
> gave, and adds the two forum permissions above, granted to nobody. Re-assign
> them after updating. A custom permission role you built only for the old
> permissions is left in place but empty; delete it if you no longer need it.

## 3. Configure the currency

**ACP → Extensions → Donation campaigns → Settings**

- **Currency code** — three letters, e.g. `EUR`
- **Currency symbol** — shown next to every amount, e.g. `€`
- **Symbol before the amount** — *No* (default) gives `10,00 €`, *Yes* gives
  `€ 10,00`. It applies everywhere an amount appears: the topic box, the
  management pages, the ACP lists, confirmation dialogs and log entries.
- **Space between symbol and amount** — *Yes* (default) keeps them apart;
  *No* gives `$10.00`. The space is non-breaking, so an amount never wraps
  between number and symbol.
- **Decimal places** — `2` for most currencies, `0` for yen, `3` for dinar
- **Donors listed** — how many names the public box shows before summarising
- **Show the campaign list page** — see step 9b

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

The entry shows to anyone who can manage the campaign shell **or** the donations
in that forum — administrators (via the `a_donationcampaigns` override) and
users or groups holding either `f_donationcampaigns_manage` or
`f_donationcampaigns_donations` for that forum, provided they can read it. It
is invisible to everyone else, including guests.

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
| Show donation date | Lists the day each donation arrived next to name and amount. Only with *Show donor names*. Ticked for new campaigns; campaigns that existed before 1.0.0-beta2 keep it off until you tick it |

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

Recording happens **from the topic**, not the ACP. Open the topic, then **Topic
tools → Donation campaign** to reach the management landing, and click **Add
confirmed donation**. The donation ledger is shown only to an administrator or a
holder of `f_donationcampaigns_donations` for that forum; a manage-only
holder does not see it.

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

**Dates and anonymity.** With *Show donation date* ticked, an anonymous
donation is listed as "Anonymous — 50,00 € (3 Oct 2026)". In a small
community, amount and day together can be enough to guess who it was. If that
matters for a campaign, leave the date off.

**Ask the donor before publishing their name.** The extension cannot know
whether you did. See [PRIVACY.md](PRIVACY.md).

## 7. Edit or delete a confirmed entry

Donations are edited and deleted **from the topic**, from the same management
landing, by an administrator or a holder of `f_donationcampaigns_donations` for
that forum. Editing recalculates the total. So does deleting — the confirmation
names the amount and donor so you can see what you are about to destroy.

Deleting a donation is permanent and there is no undo. A donation cannot be
moved to another campaign; delete it and record it under the right one.

**Deleting a campaign — the policy differs by where you do it:**

- **From the topic**, only an *empty* campaign (no confirmed donations) can be
  deleted, by an administrator or a holder of `f_donationcampaigns_manage`. A
  non-empty campaign is refused there — disable it instead, or ask an
  administrator to delete it.
- **From the ACP**, an administrator (`a_donationcampaigns`) *may* hard-delete a
  *non-empty* campaign, cascading all of its donations. This is the deliberate
  difference: the ACP is the admin's tool for that case, and the confirmation
  names the campaign and its donation count.

Either way, deletion is permanent with no undo, and the total is always
recomputed from the donation rows.

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
record is kept, and the campaign is still there on the topic, where you can edit
it, add donations to it, or re-enable it from the management landing. The ACP
lists it read-only.

Use this when a campaign has finished but you want the records.

## 9b. The board-wide campaign list

**ACP → Extensions → Donation campaigns → Settings → Show the campaign list
page.** Off by default; an update never switches it on.

When it is on, the page `app.php/donationcampaigns` lists the campaigns, newest
first, 25 per page, and the quick-links menu gains a **Donation campaigns**
entry. Each row shows the campaign title (linked to its topic), the forum, the
progress bar, collected / target / percent and — if the campaign shows it — the
number of donations. **It never shows donor names**; those stay in the topic.

A visitor sees exactly the campaigns whose box they could see in the topic:

- the campaign is enabled;
- the topic exists and has not been moved away (a moved shadow is skipped);
- the visitor may read the forum;
- the topic is approved and not soft-deleted — moderators who may approve in
  that forum also see unapproved and soft-deleted topics, as in the forum;
- the forum has no password, or the visitor has already entered it in this
  session.

So guests see the campaigns in forums guests may read, and a campaign in a
staff-only forum stays invisible to everyone else. Campaigns that have reached
their target are listed like the others, with a full bar.

The quick-links entry is shown to everyone while the page is on, even to a
visitor for whom the list is empty. prosilver hides the whole quick-links menu
when quick links and search are both unavailable to a visitor; the page itself
still works at its address.

## 9a. Where actions are logged

"Who did what, and where" is read from the log the entry lands in, which depends
on the surface the action was performed on.

- **Actions taken from the topic** — creating, editing, enabling/disabling or
  deleting a campaign, and adding, editing or deleting a donation — are recorded
  in the **moderator log**, scoped to that forum and topic — also when the
  person acting is not a moderator. Read it in the ACP (**Maintenance →
  Moderator log**) or, as a moderator of the forum, in the MCP (**Moderator
  Control Panel → Forum logs**).
- **Actions taken in the ACP** — hard-deleting a campaign, recalculating a
  total, and changing settings — are recorded in the **administrator log**
  (**ACP → Maintenance → Logs**).

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

**The box does not appear on the topic.** Check the campaign is enabled and
purge the cache. The box renders on `viewtopic`; if the topic itself is
unreachable, so is the box. If the box, the *Manage* button **and** the
topic-tools entry are all missing while the ACP pages work, the `styles` folder
did not reach the server — see *Updating to a newer version* in step 1.

**The total looks wrong.** Use **Recalculate total**. If it changes, something
wrote to the database outside the extension.

**Amounts are out by a factor of ten or a hundred.** The decimal-places setting
was changed after the data was recorded. See step 3.

**A donor's name appears when it should not.** Check both switches: the
campaign's *Show donor names* and that donation's *Show donor publicly*.

**The campaign list says the page could not be found.** The list is switched off (the
default) — see step 9b. After updating the files, purge the cache before
testing.

**A campaign is missing from the campaign list.** Check the five conditions in
step 9b; the most common causes are a disabled campaign, a forum the visitor
may not read, and a password-protected forum not yet entered.

**Menu entries have vanished.** The extension is disabled. Data is intact.
