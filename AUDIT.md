# Audit: hard-coded / dynamic / partial (before refactor, v1.0)

Legend: **D** = dynamic and editable in Admin · **P** = partially dynamic (data is dynamic, but some business values are fixed in code, or the editing place is hard to find) · **H** = hard-coded in PHP/JS.

Text labels in the interface (microcopy such as "Change country" or "Read review") are not business data. They stay translatable strings (`__()`), the WordPress standard, and are not exposed as fields.

## 1. Header

| Item | Status | Current state |
|---|---|---|
| Main menu items and URLs | D | Menu location "Primary" |
| CTA "Find a Broker" | D | Settings > FX Trading Today |
| Prototype banner | D | Settings |
| Mega menu: column headings "Highest research scores", "By account type", "By country" | **H** | Fixed strings in `inc/navigation.php` (only "By account type" could be overridden through Description) |
| Mega menu: "All broker reviews", "All N markets", "Brokers for X" | **H** | Fixed strings |
| Mega menu: feature card (label, "Read the methodology") | P | Reads Description/Title attribute from the menu item, which is hard to find |
| Mega menu: country column | P | Takes the first 4 countries, not chosen by an admin |

## 2. Countries

| Item | Status | Current state |
|---|---|---|
| Country list | P | `fxt_market` taxonomy with only 8 demo countries; no full list |
| Sort order | P | By `fxt_order`, not A–Z |
| Reuse | D | One source (`Repository::markets()`) for the selector, broker availability, evidence and the directory |
| "3 of 8" / "Available in 5 of 8 markets" | **H** (logic) | Denominator = all countries; becomes "3 of 249" once the full list is added |
| Broker meta box "Conditions by country" | P | Shows every country; with 249 countries it becomes unusable |

## 3. Homepage

| Section | Status | Current state |
|---|---|---|
| Hero, trust bar, how it works, audience, independence, final CTA | D | Core blocks, editable in Gutenberg |
| Brokers in your country | P | Data comes from Broker; the admin cannot choose the order or which brokers appear |
| Broker cards (Reviews) | **P** | Data comes from Broker, but **brokers are picked automatically by score**; no selection or ordering |
| Compare preview | **P** | Brokers entered as slugs in a text field (hard to use); fixed row set |
| Score weights (homepage + methodology) | **H/P** | 6 categories + descriptions + "evidence used" **hard-coded** in `Schema::categories()` and `views/blocks/score-weights.php`; only the % values are editable |
| Audience card "Vietnam, Exness" | **P** | Duplicates evidence data as plain text (violates single source of truth) |
| Latest research | D | Query Loop; the byline **lacks the author role** compared with V3 |

## 4. Broker Review

| Item | Status | Current state |
|---|---|---|
| CPT, add/edit/delete/publish | D | `fxt_broker` |
| Name, logo, description, score, breakdown, entities, costs, payments, accounts, by-country conditions, affiliate URL, last reviewed | D | Meta boxes **below the editor** (easy to miss: the likely reason the data looks "fixed") |
| Pros/Cons, verdict, review content | D | Gutenberg |
| Platform tiles (MT4, MT5, Web, Mobile, TradingView) | **H** | Fixed list in `views/broker/platforms.php`; adding a platform term does not show up |
| Compare: Platforms/Accounts rows | **H** | Fixed slugs `mt4…`, `standard/raw/pro/cent` |
| Overall score | P | Entered by hand; does not follow the weights (breaks single source) |
| CTA label | **H** | "Visit X (affiliate link)" fixed |
| Score categories in score card | **H** | From `Schema::categories()` |
| Directory: "Minimum deposit" filter ($50/$100/$200) | **H** | Fixed in `Blocks::directory_fields()` |

## 5. Methodology

| Item | Status |
|---|---|
| Page content (process, chain, tests, tables) | D (Gutenberg) |
| Weights table + bar | **H/P** (as in 3) |

## 6–7. Test Evidence

| Item | Status | Current state |
|---|---|---|
| Index page (title, intro) | D | Page + Gutenberg |
| Published list | D | Query of the `fxt_evidence` CPT |
| Index headings "Published evidence", "Testing in progress" | **H** | Fixed strings |
| Index by country | **H/missing** | No country filter; not tied to the selected country |
| Each test (deposit/withdrawal) | P | Split across **2 repeaters** (table row + viewer record) linked by Test ID: duplicate data |
| Screenshot / verification | **H** | Viewer always shows a placeholder; no image field, no "verified by" field |
| Status per test | missing | Only "Result" |

## 8. Author

| Item | Status | Current state |
|---|---|---|
| Author = WP User + meta (role, bio, avatar, expertise, figures, principles, disclosure) | D | Users > Profile |
| Assign author to Review/Evidence/Page | D | Author field in the editor |
| Byline in Query Loop (home, latest research) | **P** | Core block shows only the name, **missing the role** that V3 shows |
| Social links | **missing** | No field |
| Author archive | P | Lists only `post`; reviews/evidence appear only in the custom section; risk of 404 for authors who only write reviews |

## Change architecture (refactor, not rewrite)

1. **Country** stays the `fxt_market` taxonomy (one entity), but seeded with the **full ISO 3166-1 list** (249 countries, full names, sorted A–Z). Term meta `fxt_status` marks the countries under research. The selector shows every country A–Z. Denominators and the mega menu use "researched countries". Broker meta box: shows only countries that have data, plus an "Add country" button.
2. **Score categories** move into Settings as a repeater (key, label, weight, description, evidence used, order = row order). Validation: total = 100 (otherwise the save is refused and the previous configuration is kept). Broker breakdown fields are generated from the categories. **Overall score is calculated** from the breakdown × weights (a manual override field remains). Homepage, Methodology, Review score card, Compare and Directory read the same source.
3. **Platforms/Account types** render from taxonomy terms (term description = display name). Add the `fxt_cta_label` meta and a "Broker data" editor panel that jumps straight to the data.
4. **Homepage blocks**: Broker cards and Compare get a **Broker picker** (choose + reorder); Compare lets you choose which row groups to show. Brokers in your country: sort by score or by manual order. New `fxt/evidence-snapshot` block replaces the hard-coded audience card.
5. **Mega menu fully menu-driven**: columns = level-2 items (title = heading), links = level-3 items; auto columns via the `mega-auto-brokers` / `mega-auto-countries` classes; feature card = the `mega-feature` item, with label and CTA taken from the item's fields.
6. **Evidence**: each test = **one** repeater row (deposit/withdrawal) holding every field: method, amount, currency, date, time, fee, result, status, steps, masked details, note, **screenshot**, **verified by/date**. The Evidence Index block gains scope (all / selected country), editable headings, and an A–Z country filter.
7. **Author**: new `fxt/post-byline` block (name + role + date + read time) for the Query Loop; `fxt_social` meta (also emitted as `sameAs` in schema); author archive includes reviews/evidence/methodology.
8. **Importer** updated for all of the above; still idempotent.

## Status after refactor (v1.1)

Every **H/P** item above has moved to **D**: the data comes from one source and is editable in Admin (Broker Reviews, Countries, Platforms, Test Evidence, Users, Menus, Settings, block sidebar). What remains in PHP is only interface microcopy (button labels, empty-state messages), which stays translatable via `__()`.

Upgrading from v1.0: no re-import needed. Old weights become score categories, the full country list is seeded on the first visit to Admin, old `fxt_records` data is still read, and the `mega-top-brokers` menu class still works.

# Audit for version 1.2 (feedback: semantics, country filter, provenance, filter text)

| Item | Before (1.1) | After (1.2) |
|---|---|---|
| "Select your country" (country dialog) | `<h2 id="fxt-country-title">` | `<span class="country-dialog__title" id="fxt-country-title">`; dialog still named by `aria-labelledby`; computed style identical |
| "Filter brokers" | `<h2>` | `<span class="reviews-filters__title">`; computed style identical |
| Result heading | `<h2 aria-live>` "6 brokers for Vietnam" from the visitor's country | Only H2 of the results: "{n} forex brokers reviewed", same filtered list as the cards, singular form, updated by JS from the server render |
| Directory country filter | All 250 countries; defaults to the saved/visitor country; choosing one stored the site-wide cookie and reloaded | Only countries with published research (see README 3b), A to Z, default "Select a country", no counts/markers, filter only (no cookie, no reload); availability disabled until chosen; invalid or ineligible values ignored server-side (page, no-JS form, REST) |
| "Countries researched N of M" (directory rows, broker cards) | Market test statuses vs researched countries | "Deposit & withdrawal tests: n methods tested" from published evidence (`Repository::test_stats()`) |
| Source of facts | One checkbox per legal entity ("verified"); fixed "unverified" tags | `fxt_sources` meta (13 broker facts), `source` per legal entity and per country row; labels Tested / Verified at source / Broker-stated / Source not classified / Needs review |
| Pending score | "Pending" + "Research score" printed together ("PendingResearch score") | `fxt_core_score_is_pending()` + "Score pending" everywhere; caption hidden while pending |
| Directory text | `__()` strings in PHP | Settings > FX Trading Today > Broker Reviews filters (filters repeater with stable keys, option names, page text with `{count}`/`{country}`) |
| Settings page | One long form | Tabs; saving one tab keeps the other tab's values |

Data model changes (all additive): post meta `fxt_sources`; legal entity fields `source`, `source_ref`, `checked` (replaces `verified`); country row field `source`; settings `directory_filters`, `directory_options`, `directory_ui`. Broker cache key `fxt_core_brokers_v2`.

Not changed on purpose (outside this brief): the global country dialog still lists all countries with their research status; the review page quick fact "Available in N of M researched countries" and the optional comparison row "Countries researched" still exist; the author field "Markets tested in person" is public when filled in.

# Audit for version 1.3 (country selector, eligibility, evidence, CTA, country context)

| Item | Before (1.2) | After (1.3) |
|---|---|---|
| Country change on reviews, evidence, directory | Full page reload (`fxtCore.reload`); with a full-page cache the reload returned the cached country, so only the homepage (REST regions) appeared to work | No reload anywhere: regions refresh through REST (`review-badges`, `review-context`, `review-body` added); on load a page rendered for another country re-syncs to the saved one |
| Directory filter parameter | `?country=` (shared with the site preference, changed the header country) | `?market=` |
| Not accepting residents | Only a message; all sections shown | `fxt/eligibility-section` hides wrapped sections; `fxt/trader-verdict` shows country/default verdict; TOC rebuilt |
| Country context texts | `__()` strings in the view | Settings > Review page, per-country term fields, per broker-country row fields (priority in that order, reversed) |
| Evidence header | Country, Test period, "Published" = period end | "Last updated" = post modified date; country crumb removed; index rows show Last updated |
| Affiliate button | Label + "(affiliate link)" | Label only (rel="sponsored nofollow" kept, footer disclosure kept) |

Data model (additive): market row fields `context`, `verdict`; country term meta `fxt_ctx_accepting`, `fxt_ctx_restricted`, `fxt_ctx_unknown`; setting `country_context`; blocks `fxt/eligibility-section`, `fxt/trader-verdict`.

Kept on purpose: evidence titles/excerpts written by editors may still name the country, and the "Other test countries" chips on evidence pages still list the broker's other test countries.

# Audit for version 1.4 (Accepting residents / Tested residents)

| Item | Before (1.3) | After (1.4) |
|---|---|---|
| Availability per country | Select in each "Conditions by country" row (only countries added as rows; others "pending") | `fxt_accepting.countries` checklist of all countries; unticked = Not accepting (once configured) |
| Availability source | Per country row | One source for the list (`fxt_accepting.source/ref/checked`), shown in Quick Facts |
| Country rows | Mixed availability + test data | "Tested residents": test data, entity, payments, texts only |
| Quick Facts entity | "Legal entity for VN", first entity as fallback for any country | "Legal entity for Vietnam", only for Tested residents (`Repository::entity()` returns empty elsewhere) |
| Quick Facts count | "Available in N of M researched countries" | "Available in N countries" = ticked countries |
| Directory eligible countries | Rows with availability source tested/verified | Ticked countries when the list source is tested/verified, plus published evidence |
| Fact grid | Grey gap when a fact is hidden | Lines drawn by items; short rows end on the card colour |

Migration: read-time from v1.3 rows (`availability = yes`, shared `source`) until the broker is saved; nothing is deleted. Broker cache key `fxt_core_brokers_v3`.

# Audit for version 1.4.1 (content under the author line, source labels)

| Item | Before (1.4) | After (1.4.1) |
|---|---|---|
| Text under the author line | Not available | Site default in Settings > General (`byline_note`: text + reviews/evidence/posts/pages), per page `fxt_byline_note_mode` ('' default / custom / hide) + `fxt_byline_note`; rendered by `fxt_core_byline_note()` from `fxt_tt_the_byline()` |
| Labels in editorial text | Only on broker data fields | Rich text format `fxt/source-label` (`<a class="fxt-source-label" data-kind href target rel>`) + block `fxt/source-label`; four kinds from `Schema::label_kinds()` |
| Label output | n/a | `the_content` filter `fxt_core_label_markup()` (priority 20): site classes `source-tag source-tag--{kind}`, `<span>` when there is no link, `rel="noopener"` with a new tab |
| HTML in short texts | Plain text only | Field type `html`: `Fields::clean_inline()` removes script/style blocks with their content, then `wp_kses()` with `Fields::inline_html()` |
| Posts/pages without headings | Content squeezed into the 14rem TOC column | Full-width content when the TOC is hidden |

Nothing is migrated: existing content is unchanged, and the demo importer only adds the default note when the site has none saved.

# Audit for version 1.4.2 (broker name, tables, score card, entities, account fields, cost calculator)

| Item | Before (1.4.1) | After (1.4.2) |
|---|---|---|
| Broker name | `name` = post title (an SEO title leaked into buttons, tables, H1 "X review") | `fxt_name` field; empty: `Repository::broker_name()` strips " review…" / ": …" from the title. `title` kept separately for the H1 |
| Affiliate button | `cta_label` or "Visit {post title}" | `fxt_core_cta_label()`: label with `{broker}` -> broker name, or "Visit {broker name}" |
| Source label in data tables | Fixed line above Account types / Payments / Platforms | Block attributes `sourcePosition` (above, below, rows, after) + `sourceAfter`; `fxt_core_source_row()`; per-account `source` |
| Core Table block | Inline labels only | `render_block_core/table` filter: label-only row -> one full-width cell (`tr.fxt-source-row`) |
| Score card | Hero grid used an auto column: long words widened it past the viewport (card clipped on phones); value line-height 1 | `minmax(0, 1fr)` columns, wrapping title and buttons, value line-height 1.15 / clamp size, text value variant |
| Regulatory chain | Shown for every country, with "TBD" / "Research pending" placeholders | `Repository::entity_is_verified()`: tested resident + named entity + verified (or evidenced tested) source; otherwise not rendered |
| Entity map | Serves column (ISO codes) | Licence number column; `Repository::is_placeholder()` -> "Not available" |
| Account types | Fixed repeater (6 keys) in "accounts and legal entities" box | `Accounts`: `fxt_account_fields` (id, label, type, instrument, unit), `fxt_accounts` rows (value keys by id, `<id>__pips/__model/__amount`, `source`, `lot_units`, `_v`), `fxt_account_display` (account_label, columns [id, show]); own boxes + admin.js |
| Trading cost calculator | Not available | Block `fxt/cost-calculator`, `Costs` (volumes, instruments, options in Settings > Cost calculator) |

Migration: read-time. Missing `fxt_account_fields` = built-in set (same ids as the old keys); rows without `_v` get calculator numbers parsed from text; nothing is rewritten until the broker is saved. Broker cache key `fxt_core_brokers_v4`.

