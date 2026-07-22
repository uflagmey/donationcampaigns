# Developer notes

Architecture, the contracts that hold it together, and how it is tested.

See also: [README](../README.md) · [administrator guide](ADMIN_GUIDE.md) ·
[privacy](PRIVACY.md)

---

## Package

| | |
|---|---|
| Composer name | `uflagmey/donationcampaigns` |
| Directory | `ext/uflagmey/donationcampaigns/` |
| Namespace | `uflagmey\donationcampaigns` |
| Licence | GPL-2.0-only |

Identifiers that enter a shared phpBB namespace carry the full
`donationcampaigns` prefix — config keys, the permission, service ids, language
keys, template variables, form keys, log keys, CSS classes.

**Physical database identifiers are the one exception**, using the abbreviated
stem `ufdc`: `phpbb_ufdc_campaigns`, `phpbb_ufdc_donations`. Oracle caps
identifiers at 30 bytes, phpBB 3.3 supports Oracle, and phpBB validates column
and index names but *not* table names — an over-long table name fails as a raw
driver error during installation. `phpbb_donationcampaigns_campaigns` is 33
bytes. A test asserts every physical identifier still fits.

## Layers

```
ACP module / event listeners     coordinate only — no SQL, no rules
        ↓
services                         business rules, transaction boundaries
        ↓
repositories                     persistence only — all SQL lives here
```

- **Repositories** hold every query. They cast values to their intended PHP
  types at the boundary, so no consumer has to remember the database returns
  strings. Not-found contract: single reads return `null`, list reads return an
  empty array, counts and sums return `int 0`.
- **Services** own validation, ordering and transactions. They take the database
  handle *only* to open transactions and issue no SQL of their own.
- **Listeners and the ACP module** read input, call a service, and render.

A test suite reads the source and fails the build if SQL appears outside a
repository, or if the database handle is used for anything but a transaction.

## Money

Every amount is an **integer number of the smallest currency unit**. 10.00 EUR
is `1000`. No float, no double, no `* 100`, no `/ 100`, anywhere.

Parsing and formatting are string operations in `currency_formatter`, because
the obvious implementation loses money on ordinary input:
`(int) ('8.70' * 100)` evaluates to `869`. The test table includes that case,
and a 2001-iteration round-trip proves parse/format is lossless.

An architectural test forbids `(float)`, `(double)`, `floatval`, `round()` and
`number_format` in production code.

## The campaign total

`campaigns.collected_amount` is a **cache**. The donation rows are the truth.

After every donation create, edit and delete, the total is recomputed as
`SUM(donation_amount)` and written inside the same transaction. It is never
adjusted by arithmetic — no `total + amount`, no `total - amount`, no
`total + (new - old)`.

Deltas compound: one missed rollback or one row changed out of band and the
figure is permanently wrong with nothing able to detect it. Recomputing is
self-healing — any single successful mutation repairs whatever drift preceded
it. Four tests seed a deliberately corrupted total and assert that a
subsequent add, edit, delete or explicit recalculation fixes it, and a
200-operation randomised sequence asserts, after every committed operation,
that every campaign's stored total equals a freshly computed `SUM()`.

`collected_amount` is absent from the services' writable-field lists, so it can
never arrive from request input.

## Transaction boundaries

| Method | Boundary |
|---|---|
| All reads, and validation | None |
| `campaign_service::create_campaign()` / `update_campaign()` | None — one statement |
| `campaign_service::delete_campaign()` | Own |
| `campaign_service::purge_for_topics()` | Own; none at all when nothing matches |
| `campaign_service::purge_for_forum()` | Delegates to `purge_for_topics()` |
| `donation_service::*` (add, edit, delete, recalculate) | Own, via one private helper |

phpBB's `sql_transaction('rollback')` is **not** nest-aware: it unwinds the
whole active transaction, not just the extension's level. That is deliberate
here — a cleanup failure inside core's deletion aborts core's deletion too,
rather than leaving orphans.

## Confirmed-donation semantics

A donation row is money **already received and verified by an administrator**.
The extension processes no payments, contacts no provider, makes no outbound
requests, and exposes no public entry point. Version 1.0 supports manually
confirmed donations only.

## The action button

`external_url` is a destination and `external_link_text` is the label on the
button pointing at it. Both are per campaign, and neither carries provider
semantics: there is no provider column, no enum, no transaction id, no
callback and no embedded markup. A PayPal link and a link to a forum topic
explaining bank transfers are the same thing to this code.

The label is plain text with **no BBCode pipeline** — unlike `campaign_desc`,
nothing parses it — stored raw and escaped once at output with `|e`. The
column is `VCHAR:100`, deliberately narrower than the 255 used elsewhere in
the table, because a button label past that length renders badly at any width;
`campaign_service::MAX_LINK_TEXT_LENGTH` matches it and a test asserts the two
stay equal. Length is counted in characters, not bytes.

A URL with an empty label is refused by `campaign_service` rather than
substituted at render time. Falling back silently would accept a submission
the administrator did not mean to make; with no URL the button is not rendered
at all, so an empty label is simply unused and permitted.

### Decision: no payment-provider integration

> **The extension intentionally treats payment destinations as ordinary
> external HTTPS links. No provider-specific integration exists in version
> 1.0.**

This was decided deliberately, not deferred for lack of time. A per-campaign
URL plus a per-campaign button label already expresses every destination a
board needs, and it does so without the extension knowing what any of them
are. PayPal works today: paste the hosted donation link from a PayPal account
into the campaign URL and set the label to say so.

A board-wide provider mode was designed and **rejected**. Two reasons decided
it. A global setting that supplied the destination would override a campaign
that had deliberately been pointed at a bank-information topic, which breaks
boards running more than one kind of destination at once. And generating a
provider URL from a merchant identifier means adopting that provider's link
format — the obvious PayPal one belongs to a deprecated integration, and the
current replacement requires provider-hosted setup and external JavaScript,
both outside what this extension will load.

**Deferred future work, explicitly not planned for 1.0:** if a provider ever
genuinely needs automation, phpBB's `service_collection` tagging is the
mechanism (core selects authentication providers that way in
`provider_collection::get_provider()`). Nothing in the current schema blocks
it, because campaigns store a destination and a label and never a provider.
Until such a provider exists, no registry, interface or abstraction should be
added — one implementation behind an interface is not extensibility, it is
indirection.

## Deletion cascades

Two listeners, because phpBB destroys topics two different ways.

| Event | Covers |
|---|---|
| `core.delete_topics_before_query` | Topic deletion, ACP/MCP/scheduled pruning, deleting the last post of a topic, deleting a user with their posts |
| `core.delete_forum_content_before_query` | Forum and sub-forum deletion |

`prune()`, `auto_prune()`, `delete_posts()` (when it empties a topic) and
`user_delete()` all funnel into `delete_topics()`, so one listener covers them
all. **Forum deletion is the exception** — it removes topics with a direct
`DELETE ... WHERE forum_id` and never calls `delete_topics()`. A test asserts
that asymmetry so it fails loudly if core ever changes.

⚠️ The forum event's `topic_ids` payload is **not** usable. Core builds it from
a join against the attachments table, so it lists only topics that *have* an
attachment, repeats a topic once per attachment, and omits ordinary topics
entirely. The service resolves the real topic list through `topic_repository`
instead. The test fixture reproduces that misleading payload deliberately, so
an implementation trusting it fails.

**Donations are always deleted before campaigns.** Donation rows key on
`campaign_id`; reverse the order and the ids become unresolvable and the rows
are orphaned permanently. Asserted on the statements actually issued, on both
SQLite and MariaDB.

There is deliberately **no denormalised `forum_id`** on campaigns — it would
make the cascade trivial and then have to be kept correct on every topic move,
forever.

### ⚠️ Known defect: merging or fully splitting a topic destroys its campaign

**Release-blocking; a fix is required before 1.0 final.** Recorded here rather
than left to be rediscovered.

Merging topics, or splitting *every* post out of one, empties the source topic.
`move_posts()` then calls `sync('topic', …)`, which collects the now-empty
topic into `$delete_topics` (`functions_admin.php:1967`) and calls
`delete_topics()` (`:2058`). That fires `core.delete_topics_before_query`
(`:833`) — the event this extension's cascade listens to — and the campaign and
**every confirmed donation record attached to it** are deleted.

Three things make this worse than it first sounds: the deletion is silent, it
is irreversible, and it can be triggered by a moderator who does not hold
`a_donationcampaigns` and gets no indication that anything was lost. These are
records of money actually received.

The cascade itself is not wrong — an orphaned campaign pointing at a dead topic
would be worse. The fix belongs at the level of *distinguishing* a topic being
destroyed from a topic being emptied into another one, and must be designed
rather than patched. It must **not** be addressed by reintroducing a
context-free creation form so the campaign can be retyped.

## Shadow topics

A campaign cannot attach to the stub left behind by a moved topic.
`viewtopic.php?t=<shadow>` answers HTTP 404 *"The requested topic does not
exist"* — identical to a topic that never existed — so a campaign there would
render nowhere. `topic_repository::topic_exists()` excludes
`topic_moved_id != 0`, and validation reports it exactly as a missing topic.

Cleanup deliberately still finds shadows, so a campaign attached to one before
this rule is still purged with its forum rather than becoming both unreachable
and undeletable.

## phpBB integration points

Verified against core source, not inferred from names.

| Point | Location | Note |
|---|---|---|
| `core.viewtopic_assign_template_vars_before` | `viewtopic.php:775` | `topic_id` in scope, after access checks |
| `viewtopic_body_poll_before` (template) | `viewtopic_body.html:77` | Despite the name, sits **outside** the `S_HAS_POLL` conditional that opens two lines later, so it renders on every topic |
| `core.delete_topics_before_query` | `functions_admin.php:833` | Inside core's transaction, opened at 817 |
| `core.delete_forum_content_before_query` | `acp_forums.php:2011` | Before core's `DELETE ... WHERE forum_id` at 2013 |
| `viewtopic_topic_tools_after` (template) | `viewtopic_topic_tools.html:46` | Last item in the wrench dropdown. That file is `INCLUDE`d **twice** (`viewtopic_body.html:44` and `:418`), so anything in it renders in both action bars — the entry therefore carries **no `id` attribute** |
| `S_DISPLAY_TOPIC_TOOLS` | `viewtopic_topic_tools.html:1` | Referenced by the wrapper condition and assigned **nowhere in core PHP**. It exists so an extension can force the dropdown open when no core tool would have |
| `core.permissions` | — | Declares the three permissions (`a_donationcampaigns` plus the two forum-scoped `m_donationcampaigns_*`) and their category to the ACP UI |
| `overall_header_head_append` (template) | — | `INCLUDECSS` for the stylesheet |

## The escaping contract

phpBB 3.3 runs Twig with **autoescape disabled**
(`phpbb/template/twig/environment.php:79`), so nothing is escaped for you.

**Domain scalars are stored raw** — campaign titles, topic titles, donor names,
external URLs, currency code and symbol. They are read with
`$request->raw_variable()`, never `variable()`, which would escape on input and
store `&amp;` for an administrator who typed `&`.

Escaping happens at output, in exactly two places:

1. **Templates.** Every administrator-controlled scalar carries Twig's `|e`.
   phpBB's lexer accepts a filter suffix on `{VAR}`, so `{VAR|e}` compiles to
   `{{ VAR|e }}` — the idiom core uses in `prosilver/attachment.html`. No
   `|raw`, ever, and global autoescape is never enabled.
2. **Sinks with no template.** `confirm_box()` renders `{MESSAGE_TEXT}` raw, and
   the ACP log viewer `sprintf`s log parameters into a language string and
   prints the result unescaped (`phpbb/log/log.php:665-710`, rendered by
   `{log.ACTION}`). Both are escaped immediately before the message is built,
   with phpBB's `utf8_htmlspecialchars()`. The escaped value is never written
   back to the database.

`U_*` URL variables are assigned already attribute-safe and must **not** carry
`|e`, or they would be escaped twice.

A test forbids any direct `htmlspecialchars()` call in production code and
requires `|e` on every administrator-controlled scalar in every template.

## The BBCode description

The campaign description is the one formatted field and takes the **opposite**
contract:

```
input    $request->variable(…, true)      escapes
         description_formatter::for_storage()   phpBB's generate_text_for_storage()
storage  text + uid + bitfield + flags    stored together; meaningless apart
display  for_storage's result → for_display()   rendered with NO |e
edit     for_edit()                       decoded back to source for the textarea
```

`generate_text_for_display()` does **not** sanitise — it censors and parses
BBCode and assumes the text was escaped on the way in. Escaping its output again
would render an administrator's formatting as visible tags.

Encoding lives in `campaign_service`, not the ACP module, so no caller can store
a raw description. The BBCode metadata columns are absent from the writable
fields: they are produced by the encoder, never accepted from a request, because
a uid describing markup the text does not contain is how a payload gets past the
display path.

`description_formatter` is a thin seam over phpBB's three content functions,
which resolve `text_formatter.parser` from the container and therefore need a
booted board. Unit tests substitute a fake and assert the service's *policy*;
the real pipeline is exercised on the Docker board.

phpBB 3.2+ stores BBCode as **s9e XML** (`<r>…<B><s>[b]</s>…</B></r>`) and
leaves `desc_bbcode_uid` and `desc_bbcode_bitfield` empty. That is correct, not
a bug; the columns are still required by the display and edit functions.

### ADR-014 — Campaigns are created only from their topic, and the ACP resolves state at request time

> **Superseded by ADR-015.** Management no longer lives in the ACP: campaign and
> donation actions moved to frontend controllers reached from the topic, under
> forum-scoped moderator permissions. ADR-014 is kept as the historical record
> of the topic-context creation decision, whose reasoning ADR-015 carries
> forward.

Creation used to mean reading a topic's numeric id out of its address and
typing it into the ACP. Nothing else in phpBB asks that, and the field it was
typed into was the one place a campaign could be pointed at the wrong topic.

The entry point is now a single neutral **Donation campaign** item in the
topic tools menu. It carries no verb, and the URL carries no action — only the
topic. The ACP resolves what to do when the request arrives:

    no campaign for this topic          -> create
    a campaign, enabled or disabled     -> edit that campaign

**Why neutral rather than "Add" / "Manage".** A topic page is rendered once and
may be clicked much later. Any verb decided at render time is a claim about the
past: a campaign can be created, deleted or disabled in between. Resolving on
arrival makes stale pages, disabled campaigns, concurrent creation and
hand-edited URLs one mechanism instead of four guards. It also keeps the
listener free of a campaign lookup, which matters because it runs on every
topic view on the board.

**Topic context suppresses the action parameter entirely.** With a topic in the
URL, `action` is not read at all, so `t=10&action=delete&campaign_id=1` cannot
be expressed rather than being caught.

**The expected-state guard.** Resolving on arrival alone would silently turn a
lost create race into an edit, overwriting the campaign that won with values
typed for one that did not exist. The form records which campaign it was drawn
for; on a mismatch nothing is written and the form is redrawn with an
explanation. The recorded id is untrusted but can only ever *downgrade* a write
to a re-render — the campaign written is always the resolved one.

**Two permissions.** `a_` is a real ACL option, not a prefix wildcard
(`schema_data.sql:411`), so an administrator holding `a_donationcampaigns`
without `a_` is refused by `adm/index.php` with a 403. Both are checked before
the link renders, and both checks are visibility only — `main_module::main()`
still enforces access itself.

**The module identifier is derived, never written.** phpBB maps a class name to
a URL token by replacing backslashes with dashes
(`functions_module.php:1141-1150`). Since this link is the only route to
creation, a stale literal would remove the feature rather than degrade it, and
would fail only for administrators, only at runtime. Two tests cover it.

**Links out of the ACP must carry `$phpbb_root_path`.** This code runs inside
`adm/`, where a bare `viewtopic.php` resolves to `/adm/viewtopic.php`. Every
unit test asserting "the URL contains `t=30`" passes either way; only following
the link on a running board shows it. All such links go through one helper and
a regression test asserts the root path is present.

**Consequence, accepted deliberately.** A style that does not provide
`viewtopic_topic_tools_after` offers no way to create a campaign at all. Under
ADR-013 that style is outside v1.0's supported presentation scope. A weaker ACP
form was explicitly rejected as a fallback, because it would have preserved the
raw topic-id workflow this decision exists to remove.

### ADR-015 — Frontend-primary management with forum-scoped moderator permissions

Campaign **and** donation management moved out of the ACP into frontend
controllers reached from the topic. The topic-tools **Donation campaign** entry
now opens a management landing (`/app.php/donationcampaigns/topic/{topic_id}`)
that resolves state on arrival — the create form when there is no campaign and
you may make one, otherwise a campaign summary plus only the actions you are
authorised for. This supersedes ADR-014.

**Why.** A public extension needs *forum moderators*, not only board
administrators, to run donations — the person who manages a fundraising topic is
usually its moderator, not someone with ACP access. The old model could not
express that: it had a single global `a_donationcampaigns` and did every write
in the ACP, so the only way to let someone manage donations was to make them an
administrator of the whole board. The ACP-only shape also did not feel like
native phpBB, where topic-level work happens on the topic.

**The three-permission model, forum-scoped.** One global admin permission plus
two forum-scoped moderator permissions:

- `a_donationcampaigns` — global admin, granted to the admin roles on install.
  Full ACP access and an override for every frontend action on every forum.
- `m_donationcampaigns_manage` — forum-scoped. The campaign shell: create, edit,
  enable/disable, delete an *empty* campaign.
- `m_donationcampaigns_donations` — forum-scoped. The donation ledger: add, edit
  and delete confirmed donations.

The two `m_` permissions are independent and neither is granted on install;
`a_donationcampaigns` overrides both. All three sit in a dedicated **Donation
Campaigns** permission category. Authorisation is checked per forum, resolved
from the campaign's topic.

**The layering did not change.** `Repository → Service → Controller` mirrors the
old `Repository → Service → ACP module`: the service layer was left untouched.
The frontend controllers are thin coordinators that read input, call the same
services and render, exactly as the ACP module did. No business rule moved into a
controller.

**Deletion policy is split deliberately.** From the topic only an *empty*
campaign may be deleted; a non-empty one is refused, to protect records of money
received from a forum moderator's cleanup. The ACP keeps an admin-only
hard-delete that cascades a *non-empty* campaign's donations — that is the
administrator's tool for the case the frontend refuses. Donations are deleted
individually behind a confirmation naming the amount and donor.

**Audit is by surface.** Frontend actions (campaign create/edit/enable/disable/
delete; donation add/edit/delete) are written to the **moderator log**, scoped
to the forum and topic, so they surface in the MCP alongside other moderator
activity. ACP actions (hard-delete, recalculate, settings) are written to the
**administrator log**. The log an entry lands in identifies where the action was
performed.

**Denial is a uniform 404.** An unauthorised or malformed request — a forum the
caller cannot manage, a missing topic or campaign, a mismatched id — answers
with the same "not found" response rather than distinguishing "forbidden" from
"does not exist", so the frontend never discloses whether a given campaign or
donation exists to someone not entitled to know.

**The ACP became read-only oversight plus admin maintenance.** It keeps
settings, a read-only campaign list and a read-only donation list, and the
admin-only maintenance (recalculate a total, hard-delete a non-empty campaign).
Its former create/edit forms are gone; the list rows link out to the frontend
routes on the topic.

**Consequence, accepted deliberately.** phpBB treats any holder of an `m_`
permission on a forum as a moderator of it, so granting either donation
permission lists the grantee among that forum's moderators and gives them
moderator standing there. This is inherent to phpBB's definition of a moderator
and is documented for board owners as a real (if limited) promotion, not a
hidden grant.

## Styles

prosilver only, for version 1.0 (ADR-013). Templates under
`styles/prosilver/template/`, stylesheet under `styles/prosilver/theme/`,
included with `INCLUDECSS` from a header template event.

No inline CSS, no inline JavaScript, and **nothing requires JavaScript** —
the progress bar's width is a CSS class per five-percent step rather than an
inline style. All custom classes carry the `donationcampaigns-` prefix. No fixed
widths, no absolute positioning.

## Tests

```
php vendor/bin/phpunit                       # everything
php vendor/bin/phpunit --testsuite unit      # pure logic + architectural guards
php vendor/bin/phpunit --testsuite migration # schema, config, permission, modules
php vendor/bin/phpunit --testsuite repository
php vendor/bin/phpunit --testsuite service   # rules, transactions, total integrity
php vendor/bin/phpunit --testsuite event     # listeners + shipped prosilver assets
php vendor/bin/phpunit --testsuite acp       # ACP modules + adm templates
php vendor/bin/phpcs --standard=phpcs.xml
find ext -name '*.php' -print0 | xargs -0 -n1 php -l
```

`phpbb_database_test_case` is unusable — it extends `PHPUnit\DbUnit\TestCase`,
and dbunit is abandoned and incompatible with PHPUnit 9. Database tests
therefore build their schema from phpBB's own baseline migration
(`\phpbb\db\migration\data\v30x\release_3_0_0`) and drive phpBB's real tools.
The 3.0.0 baseline predates the 3.1 visibility columns, so fixtures that drive
core's deletion paths add them explicitly.

Integration tests call **core's own functions** — `delete_topics()`,
`delete_posts()`, `prune()`, `auto_prune()`, `acp_forums::delete_forum()` —
through a real dispatcher with the listeners subscribed, rather than invoking
the listeners directly. The assumption worth testing is not that our code works
but that core reaches it.

### Database coverage

| Engine | Status |
|---|---|
| SQLite 3 | Every automated test |
| MariaDB 10.11 | Live integration: topic deletion, forum deletion with and without attachments, donations-first ordering observed in the query log, no orphans, migration idempotence — and the `mysqli` branch of forum deletion that SQLite never executes |
| MySQL, PostgreSQL, MS SQL, Oracle | **Not executed.** DBAL-only SQL, so expected to work; not verified |

## Local integration environment

A disposable Docker board (phpBB 3.3.17, PHP 8.2, MariaDB 10.11) is used to
verify UI work against a real installation, since automated tests cannot see a
template that fails to compile or a page that renders wrongly.

It lives outside the extension package and is not part of the repository. Its
setup notes live with it. **No credentials, hostnames or local paths belong in
this repository.**

## EPV and coding standards

```
docker compose exec web /opt/epv/vendor/bin/EPV.php run --dir=/var/www/html/phpBB/ext/
```

EPV must be pointed at the directory *containing* `vendor/package/`, not at the
extension root, or it reports a spurious packaging error. The current state is
**0 fatal, 0 errors, 0 warnings, 0 notices**.

Two of its rules shaped the code:

- **`htmlspecialchars()` is an Error** in EPV's eyes, whatever the context.
  Hence `|e` in templates and `utf8_htmlspecialchars()` at sinks.
- **Its SQL-injection check is a regex** requiring `' . $`, skipped when the line
  contains `sql_in_set`, `sql_escape` or several other DBAL helpers. An inline
  `(int)` cast breaks the pattern — which is why queries are written
  `= ' . (int) $id` rather than casting on an earlier line.

**EPV passing is not proof of submission-policy compliance.** It is a mechanical
scanner. The phpBB validation and development policies have not been reviewed
for this extension, and no claim about them is made.
