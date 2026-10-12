# FX Trading Today: WordPress theme + Core plugin

Production build of Prototype V3, packaged as two parts:

| Gói | Vai trò |
|---|---|
| `fx-trading-today` (theme) | Presentation only: `theme.json`, CSS, PHP templates, header/footer, patterns, block styles |
| `fxt-core` (plugin) | Data and logic: CPTs, taxonomies, meta, settings, 12 blocks, REST render, country picker, demo importer |

Data stays in the plugin, so content survives a theme change. The theme never reads meta keys directly; it calls the plugin's template API (`fxt_core_*`).

Requirements: WordPress 6.5+ (tested on 6.7), PHP 7.4+ (tested on 8.4). No build step, no page builder, no third-party plugins, no external requests (fonts are self-hosted).

---

## 1. Installation (5 steps)

1. **Plugins > Add New > Upload**: upload `fxt-core.zip` and activate it.
2. **Appearance > Themes > Add New > Upload**: upload `fx-trading-today.zip` and activate it.
3. **Appearance > Import Demo**: click **Import demo content**. To overwrite the current front page, menus and Reading settings, tick *Also replace the current front page…* first.
4. Open the site. The front page, Broker Reviews, Compare, Test Evidence, Methodology, and the author and review pages now match Prototype V3.
5. Edit content in Gutenberg (Pages, Posts, Broker Reviews) and edit configuration under **Settings > FX Trading Today**.

WP-CLI: `wp fxt demo import [--force]` and `wp fxt demo remove [--yes]`.

The importer:
- Imports **content + configuration + Gutenberg markup only**. All CSS stays in the theme.
- Is **idempotent**: every object carries the marker `_fxt_demo_key`. Running it again updates items instead of duplicating them.
- Can be undone with **Remove demo content**, which deletes only objects carrying the marker. Settings are kept.
- Never overwrites content the user created themselves. If permalinks are set to "Plain", it switches them to `/%postname%/`.
- Moves the default "Hello world!" post to the Trash only if nobody has edited it.

## 2. Where to edit what (single source for each kind of data)

| Item to change | Location | Automatically updates |
|---|---|---|
| Broker: name, logo (featured image), description (excerpt), status, **category scores**, last reviewed, min deposit, currencies, costs, trading conditions, payments, accounts, legal entities, **conditions by country**, affiliate URL + button label | Broker Reviews > *broker*: panels **below the editor** (sidebar: "Broker data > Edit broker data") | Homepage (country hero, cards, compare), directory, compare page, review, mega menu, evidence |
| Overall score | **Calculated** from category scores × weights; the "Overall score override" field is for exceptions only | Everywhere scores appear |
| Broker review copy (verdict, pros/cons, notes, method) | Gutenberg in the broker post | Review page |
| Display order when scores tie / "manual" order | The "Order" field (Page Attributes) of each broker | Lists and the hero block when set to manual order |
| **Countries** (all 250, A–Z) | Broker Reviews > Countries: rename, set "Research status". The "Add missing countries" button restores deleted countries | Country selector, availability, directory, evidence, mega menu |
| Regulators, Platforms, Account types | Broker Reviews > Regulators / Platforms / Account Types. Platforms have "Full name" (review tile) and "Show in compact lists" (card) | Comparison rows, review tiles, directory filters |
| **Score categories** (add / edit / delete / reorder, weight, description, evidence used) | **Settings > FX Trading Today > Score categories**. Weights must total 100; an invalid set is rejected | Homepage methodology, Methodology page, review score card, every broker score |
| Directory "Minimum deposit" filter amounts | Settings > FX Trading Today > General > Minimum deposit filter | Directory |
| **Broker Reviews filter text**: filter labels, placeholders, descriptions, order, show/hide, option names, panel title, sort labels, result heading, result note, empty-result text, clear buttons | **Settings > FX Trading Today > Broker Reviews filters**. Placeholders `{count}` and `{country}`; empty fields use the default wording | Broker Reviews page only. Broker data, filter keys and filtering logic never change with these labels |
| **Country context on reviews** (heading, description, "Change country" link, accepting / accepting without entity / not accepting / unknown messages) and the default **Trader verdict for countries not accepted** | **Settings > FX Trading Today > Review page**. Placeholders `{broker}`, `{country}`, `{entity}`, `{regulator}`, `{jurisdiction}` | Country context box and verdict on every review |
| Country-specific messages (all brokers in one country) | Broker Reviews > Countries > *country*: "Country context: …" (3 fields) | Overrides the Settings defaults for that country |
| Broker + country message and **Trader verdict for this country** | Broker Reviews > *broker* > Broker data: conditions by country > *country*: "Country context message", "Trader verdict for this country" | Highest priority for that broker and country |
| Sections hidden when residents are not accepted | Gutenberg: wrap them in the **Country eligibility section** block (demo: Regulation and client protection, Deposit and withdrawal) | Review body |
| **Data sources** of each fact (Tested / Verified at source / Broker-stated), reference link, check date | Broker Reviews > *broker* > **Broker data: sources**; per legal entity: "Source of entity and licence data"; per country: "Availability source" | Source labels on the review page, directory rows, comparison table; eligible countries of the directory filter |
| Test evidence (broker × country): period, account, summary, timeline, **each deposit/withdrawal test** (method, amount, date, time, fee, result, status, steps, broker/bank status, masked details, **screenshot**, **verified by/on**, note) | Broker Reviews > Test Evidence (sidebar: "Edit evidence data") | Evidence page, evidence viewer, index, review "Research evidence", homepage snapshot |
| Author: name, photo, role, short/long bio, expertise, figures, principles, disclosure, **profile links** | Users > *user* > section "FX Trading Today author profile" | Bylines, author boxes, author page, Query Loop bylines, structured data |
| Header menu, mega menu, footer columns | Appearance > Menus | Header, mobile drawer, footer |
| Header button, banner, risk warning, affiliate disclosure, key pages | Settings > FX Trading Today | Header, footer, links in blocks |
| Section copy (hero, how it works, independence…) | Gutenberg: Pages > *page* | — |

### Mega menu (built entirely from menu items)

The top-level item with the CSS class **`mega`** opens the mega menu. Each of its children is a column or card:

| Child item | Role | Fields used |
|---|---|---|
| Class `mega-auto-brokers` | Column of the highest-scoring brokers (automatic) | Navigation Label = heading; URL + **Description** = link below the list |
| Class `mega-auto-countries` | Column of researched countries (automatic) | Same as above; `%d` in the Description = number of countries |
| Class `mega-feature` | Featured card | Description = small label; Navigation Label = title; **Title Attribute** = call to action |
| Any other item | Link column | Navigation Label = heading; its child items = the links |

Turn on the **CSS Classes**, **Description** and **Title Attribute** fields in Appearance > Menus > Screen Options.

### Content inside the page header
On pages that use the theme's templates, consecutive blocks at the **top** of the content that carry the class `page-hero__extra` render inside the page header. Examples: the research-areas chips on the Broker Reviews page, and the principles on the Methodology page.

## 3. Blocks (category "FX Trading Today")

| Block | Purpose | Admin options (block sidebar) |
|---|---|---|
| Brokers in your country | Brokers available in the selected country | Number shown; order by score or manual |
| Broker cards | Broker review cards | **Choose brokers + reorder** (empty = automatic by score) |
| Broker comparison | Comparison widget | Layout, slots, **default brokers + order**, **rows/groups to show**, sync to URL |
| Broker data | One data section of a review | Section |
| Evidence data | One section of a test | Section |
| Evidence snapshot | Key results for one broker in one country (homepage audience card) | Broker, country |
| Score weights | Score categories (bars or table) | Display style |
| Broker directory | Search + filters + sort | — |
| Test evidence index | Published evidence + tests in progress | All countries (with country filter) or only the selected country; headings; show/hide in progress |
| Post byline | Author + role + date + read time (inside Query Loop) | Show role, text before the date, read-time format |

Every block is server-rendered; data is never copied into the post content.

## 3b. Version 1.2 rules

- **Result heading** of the Broker Reviews page is the only H2 there: "{n} forex brokers reviewed" (singular for one), counted from the same filtered list as the cards and refreshed with every filter change. "Filter brokers" and "Select your country" are plain text (same look, not headings).
- **Directory country filter** starts at "Select a country" (the visitor's saved country is not applied there and choosing one does not change it). It offers only countries with published research, A to Z: published test evidence with completed tests for a published broker, or a published broker's availability classified as Tested or Verified at source. The list has no counts or markers, so test-account countries cannot be told apart. Availability is disabled until a country is chosen.
- **"Deposit & withdrawal tests: n methods tested"** replaces "Countries researched N of M" on directory rows and broker cards. Methodology: a payment method counts once per broker when at least one deposit or withdrawal test row with status "Completed" uses it in a published evidence set; methods are matched by name (case and spacing ignored), so the same method used for deposit and withdrawal, or in several countries, counts once. Tests in progress, failed tests and broker-stated methods are not counted.
- **Source labels**: Tested (our documented test), Verified at source (checked against an official or primary source), Broker-stated (published by the broker, not checked). Unclassified facts show "Source not classified". A fact marked Tested without published completed tests for that broker (and country, where it applies) shows "Needs review" and the broker screen lists it in a warning. A Verified or Tested label with an http(s) reference links to it; the check date is in the tooltip and screen-reader text.
- **Score pending**: shown wherever a score is not complete yet (directory, cards, review, comparison, mega menu). No number and no "Research score" caption is shown with it.

## 3c. Version 1.3 rules

- **Country selector works the same on every page.** Choosing a country stores it (cookie) and refreshes every country-dependent region in place through REST: homepage blocks, comparison, broker reviews (badges, country context and the whole review body), evidence index. Nothing reloads. When a page comes from a full-page cache rendered for another country, the page corrects itself on load to the saved country, so the choice is kept while navigating.
- **Eligibility** per review = the broker's "Availability" for the visitor's country. Only "Not accepting residents" is restricted: content inside **Country eligibility section** blocks is hidden, the table of contents follows, and the **Trader verdict** block shows the country row's verdict or the default verdict from Settings. "Availability pending" (unknown/unverified) never hides anything.
- **Priority of texts**: broker + country row > country (Broker Reviews > Countries) > Settings > Review page > built-in default. Admin text is plain text; HTML is removed.
- **Trader verdict** block: the verdict written in the block is the default; a country row verdict replaces it for that country (keeping the block's quote style; the "Most relevant for" line is kept only for accepted countries).
- **Test Evidence pages** show "Last updated {date}" (post modification date) instead of Country and Test period; the evidence index shows "Last updated" too. Country, period and all relations stay in the record.
- **Affiliate button** shows only "Affiliate button label" (default "Visit {broker}"). The link keeps `rel="sponsored nofollow"`, and the affiliate disclosure stays in the footer.
- **Broker Reviews filter** uses its own parameter `?market=XX`, so it never changes or reads the site-wide `?country=`.

## 4. Country, cache, privacy

- The country is a **preference**: `?country=XX` > cookie `fxt_country` > the default setting. The site **never reads location**.
- Changing country on the home page and the compare page refreshes the affected regions through REST (`/wp-json/fxt/v1/render`) without a page reload. Review, evidence and directory pages reload.
- **Full-page cache** (Varnish, WP Rocket, Cloudflare APO…): the cache must vary on the `fxt_country` cookie, or bypass cache when that cookie exists. Otherwise visitors see the cached country.
- Compare, directory and the country parameter all work **without JavaScript** (GET forms).
- Evidence data: the fields Trading account / Account holder / Bank / Reference are **masked automatically on save** (they keep only the last 4 digits and initials). The original value is never stored.

## 5. Developer notes

- Templates: `front-page.php`, `page.php`, `templates/page-wide.php` (tool pages), `templates/page-document.php` (TOC + byline), `single.php`, `single-fxt_broker.php`, `single-fxt_evidence.php`, `author.php`, `home.php`, `archive.php`, `search.php`, `404.php`.
- Override a plugin view from the theme: copy `fxt-core/views/<view>.php` to `fx-trading-today/fxt-core/<view>.php`.
- Data cache: transient `fxt_core_brokers_v1`, cleared automatically when a broker, term, meta or setting is saved.
- Security: every output is escaped; meta boxes, term fields and user fields use nonces + capability checks; settings use the Settings API; the REST endpoint is read-only and accepts only an allowlist of views and a same-site `base`.
- Structured data: BreadcrumbList, Review (`reviewRating` is emitted only when the Sample data label is off), ProfilePage/Person.

## 6. What was tested (WordPress 6.7, PHP 8.4, SQLite)

- `php -l` for every file; PHP debug log has no notices or warnings from the theme or plugin.
- All 23 demo posts and pages open in the **real Gutenberg editor** with every block valid (no "unexpected content").
- 47/47 Playwright checks pass: country picker (refreshes regions, cookie), compare (replace, remove, compare all, collapse, URL sync), directory (live search, filters, chip, country), TOC, evidence viewer (masking, Escape, focus return), mega menu, search button, mobile drawer, no horizontal scroll at 390px on 7 page types, and the no-JS paths.
- Remove demo content → import again: no duplicates, and no links pointing at deleted pages.
- Save round trip: meta box → front end → reload editor.
- Screenshots compared with Prototype V3 at 1440px and 390px.

## 6b. Acceptance checks (version 1.1)

22/22 automated checks through the admin screens pass: editing a broker updates the review, homepage and compare page; category scores recalculate the overall score; editing a weight updates the homepage, the Methodology page and the score card; an invalid total is rejected; adding test evidence updates the index, the country filter and the review, and sensitive values are masked on save; editing the author updates bylines and author boxes; editing the menu updates the header and mega menu; the country selector lists all 250 countries A–Z. Every demo page still opens valid in Gutenberg and all 47 interaction checks pass.

## 6c. Acceptance checks (version 1.2)

63/63 automated checks through the admin screens and the frontend pass (headings and outline, country filter default/eligibility/order/privacy/no-JS/REST, count and grammar, methods tested and its reaction to publishing or unpublishing evidence, source labels including "Needs review", label/heading/note/option edits, hidden filters, filter order, placeholder safety, tab saving, importer rerun). The 47 interaction journeys, the 22 version 1.1 checks and the block-validity check of all 23 demo items still pass; the PHP log stays empty. Computed styles of the former headings are identical to version 1.1.

Upgrading from 1.1: no re-import needed. Legal entities saved with the old "Verified on official register" checkbox read as "Verified at source" until saved again. Facts without a classification show "Source not classified" until an editor sets one. Filter text uses the defaults until the new settings tab is saved. The directory now uses its own country filter, so links such as "All brokers for Vietnam" only pre-select a country that qualifies.

## 6d. Acceptance checks (version 1.3)

53/53 checks through real admin screens and the browser pass: in-place country change on reviews (badges, context, sections, verdict, table of contents, author box kept), restore on accepted countries, unknown eligibility untouched, persistence across reviews / homepage / compare / evidence pages, `?country` in the URL, a page cached for another country corrected on load, directory `?market`, evidence Last updated, affiliate label, Review page settings, country and broker-country overrides with priority, section toggle in Gutenberg, block validity, importer rerun keeping admin text. The 63 version 1.2 checks, 47 interaction journeys, 22 version 1.1 checks and the block-validity check of all 23 demo items pass.

Upgrading from 1.2: no re-import needed for the selector, evidence and button changes. To hide sections on existing reviews, wrap them in a **Country eligibility section** block and put the verdict quote in a **Trader verdict** block (or re-import the demo for the demo reviews). Links to the directory with `?country=` should use `?market=`.

## 7. Known differences from the prototype

- The prototype pointed the "Visit broker (affiliate link)" button at its own page. The demo leaves `Affiliate URL` empty, so the button is hidden until a real link is entered.
- Reading time is estimated from word count. The prototype used fixed numbers.
- Overall scores are calculated from the weights. Pepperstone's demo category scores were adjusted slightly so the calculated score (4.4) and the order match V3.
- The audience card ("Vietnam, Exness") is now read from the real Test Evidence, so its row labels follow the data ("Local bank transfer withdrawal", "Customer support").
- "Latest research" uses the **Query Loop** block over Posts, so it lists articles. The prototype mixed in reviews and evidence pages, which have their own blocks.
