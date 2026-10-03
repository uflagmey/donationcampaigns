# Donation Campaigns — Release Notes

## 1.0.0-beta2 (in development)

**Status:** Work in progress. This file grows with each change that lands for
beta2; `composer.json` still reads `1.0.0-beta1` until the release.

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

## Upgrade from 1.0.0-beta1

1. Replace the extension files and run the update (disable/enable, or the
   migration runner as usual).
2. The migration `m8_forum_permissions`:
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

Further beta2 changes will be listed here as they land.
