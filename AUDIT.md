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
