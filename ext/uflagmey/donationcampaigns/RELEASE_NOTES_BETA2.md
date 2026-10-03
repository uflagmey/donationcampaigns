# Donation Campaigns — Release Notes

## 1.0.0-beta2

**Status:** Second public beta. It implements the review feedback received on
phpBB.com for beta1: permissions for any group, a manage button in the campaign
box, donation dates and the currency symbol position.

Requires phpBB **3.3.16 or later** (below 4.0). prosilver only. PHP **8.2 or newer**.

---

## Breaking change — forum permissions instead of moderator permissions

Thanks to review feedback from the phpBB.com community, the two forum-scoped
permissions are now ordinary **forum permissions** that can be granted to any
group, moderators or not:

| beta1 (removed) | beta2 (new) | Meaning |
|---|---|---|
| `m_donationcampaigns_manage` | `f_donationcampaigns_manage` | Manage the campaign shell: create, edit, enable/disable, delete an empty campaign |
| `m_donationcampaigns_donations` | `f_donationcampaigns_donations` | Manage confirmed donations: add, edit, delete. Exposes donor names and amounts |

**Why.** In phpBB every `m_` grant also makes the holder a moderator of the
forum: listed as "Moderator", with access to the Moderator Control Panel. Forum
permissions grant only themselves. They sit on the **Forum permissions** tab,
category **Donation Campaigns**, next to phpBB's own "Can post polls". See
ADR-016 in [DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).

**Read access is now required.** Managing a campaign — also through the
administrator override `a_donationcampaigns` — requires *Can read forum* in the
topic's forum.

`a_donationcampaigns` is unchanged: global, granted to the Full and Standard
Administrator roles, ACP access plus an override for every frontend action.

---

## New: display options

- **Manage button in the campaign box.** Whoever may manage the campaign or its
  donations in that forum sees a **Manage** button in the box header. It opens
  the same management page as the topic-tools entry.
- **Donation date in the donor list.** New per-campaign option *Show donation
  date*: "Anna — 50,00 € (03.10.2026)". Ticked for new campaigns, **off for
  existing ones**, so updating changes no public page. See the privacy note in
  the administrator guide.
- **Currency symbol before or after the amount**, plus an option for the space
  between them (*ACP → Extensions → Donation campaigns → Settings*). Defaults
  keep the beta1 layout.

## Fixed

- The currency symbol was shown only in the topic box. The management page,
  the donation list, the ACP lists, confirmation dialogs and log entries now
  show it too, in the configured position.
- The ACP donation list showed the donation date with a time of day and in the
  viewer's time zone; a viewer west of UTC saw the previous day. Dates are now
  shown as calendar days, the same for everyone. The management page's
  donation list gains a date column.

---

## Upgrade from 1.0.0-beta1

1. Replace the extension files and run the update (disable/enable, or the
   migration runner as usual).
2. The migration `m9_display_options` adds the two currency settings (beta1
   layout) and the per-campaign date option (off). The migration
   `m8_forum_permissions`:
   - adds the two forum permissions, **granted to nobody**;
   - removes the two moderator permissions **and their grants** — they are not
     carried over;
   - removes the moderator standing the old grants created (the hidden
     "any moderator permission" flag), so former holders lose MCP access and no
     longer appear as moderators — unless they hold other moderator permissions
     in that forum, which are left untouched;
   - rebuilds the forum moderator list.
3. **Re-assign the new permissions**: ACP → Permissions → Group's (or User's)
   forum permissions → group and forum(s) → Advanced permissions → Donation
   Campaigns.
4. A custom permission role you created only for the old permissions remains,
   but empty. Delete it if you no longer need it.

---

## For translators

Language keys changed in `language/*/permissions_donationcampaigns.php`
(meaning and wording unchanged, only the key names):

| Removed | Added |
|---|---|
| `ACL_M_DONATIONCAMPAIGNS_MANAGE` | `ACL_F_DONATIONCAMPAIGNS_MANAGE` |
| `ACL_M_DONATIONCAMPAIGNS_DONATIONS` | `ACL_F_DONATIONCAMPAIGNS_DONATIONS` |

New keys:

| File | Key |
|---|---|
| `common.php` | `DONATIONCAMPAIGNS_DATE_FORMAT` — a PHP `date()` format for donation dates, date only (de `d.m.Y`, en `j M Y`) |
| `common.php` | `DONATIONCAMPAIGNS_MANAGE_BUTTON` |
| `info_acp_donationcampaigns.php` | `DONATIONCAMPAIGNS_SETTINGS_SYMBOL_BEFORE`, `…_SYMBOL_BEFORE_EXPLAIN`, `DONATIONCAMPAIGNS_SETTINGS_SYMBOL_SPACE`, `…_SYMBOL_SPACE_EXPLAIN` |
| `info_acp_donationcampaigns.php` | `DONATIONCAMPAIGNS_FORM_SHOW_DATE`, `DONATIONCAMPAIGNS_FORM_SHOW_DATE_EXPLAIN` |
