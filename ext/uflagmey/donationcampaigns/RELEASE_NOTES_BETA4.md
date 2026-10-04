# Donation Campaigns — Release Notes

## 1.0.0-beta4

**Status:** Fourth public beta, a technical one. The templates are now written
in native Twig, a few leftovers of older phpBB coding patterns are gone, and one
defect from beta3 is fixed. Nothing changes for visitors, managers or
administrators: the pages look and behave exactly as in beta3.

Requires phpBB **3.3.16 or later** (below 4.0). prosilver only. PHP **8.2 or newer**.

---

## Changed: templates in native Twig

All fifteen templates use Twig syntax — `{{ VAR|e }}`, `{% if %}`,
`{% for %}`, `{{ lang('KEY') }}` — instead of phpBB's legacy syntax (`{VAR}`,
`<!-- IF -->`, `<!-- BEGIN -->`, `{L_KEY}`). The occasion was a note from the
Extension Check team of phpBB.de; phpBB's documentation ("Tutorial: Template
syntax") says the legacy syntax will be deprecated and recommends Twig, and the
official Skeleton Extension writes Twig only.

phpBB still understands the legacy syntax, so this changes spelling, not
behaviour. Each step was checked against the previous version: the compiled
templates and 46 captured page states of a test board are identical. A test
keeps the legacy syntax out. See ADR-020 in
[DEVELOPERS.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).

Also changed, none of it visible:

- Developer notes in the templates are Twig comments now, so they no longer
  appear in the page source.
- Input tags end without a space before `>`.
- The frontend controllers receive phpBB's PHP file extension from the service
  container instead of reading a global variable.
- The stylesheet uses the standard property name `overflow-wrap` instead of
  the older `word-wrap`.

---

## Fixed

- **Since beta3, the post text appeared a second time in the page source of
  the posting form.** When a manager started a new topic and the form was shown
  again — after a preview or with an error message — the text of the post was
  repeated inside an HTML comment in the page source. It was not visible on the
  page and could not be used to inject anything (the text is escaped), but it
  did not belong there. The cause was a developer comment that named phpBB's
  post-text variable, which phpBB's template engine filled in even inside a
  comment. Developer notes are no longer HTML comments, and a test renders the
  form with a marker text and fails if it appears.

---

## Upgrade from 1.0.0-beta3

1. Disable the extension (not "Delete data"), upload the new files over the
   old ones and check that all twelve folders arrived — see *Updating to a
   newer version* in the administrator guide. Then enable it again.
2. **Purge the cache** (ACP → General → Purge the cache). Boards that do not
   recompile changed templates automatically keep serving the old ones until
   then.
3. There is **no migration** in this release: no database change, no new
   setting, no permission change.

Updating straight from an earlier beta: read the beta3 and beta2 notes first
([RELEASE_NOTES_BETA3.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/RELEASE_NOTES_BETA3.md),
[RELEASE_NOTES_BETA2.md](https://github.com/uflagmey/donationcampaigns/blob/main/ext/uflagmey/donationcampaigns/RELEASE_NOTES_BETA2.md)).

---

## For translators

No new, changed or removed language keys. The language files are unchanged
since beta3; existing translations work as they are.
