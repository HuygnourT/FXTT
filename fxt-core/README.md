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
| **Broker name** (short brand name used on buttons, tables, headings; empty = post title without "review…") | Broker Reviews > *broker* > Broker data: research summary > **Broker name** | Affiliate and compare buttons, entity map, directory, cards, compare, menus. A custom post title becomes the H1 |
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
| Broker + country message and **Trader verdict for this country** | Broker Reviews > *broker* > Broker data: tested residents > *country*: "Country context message", "Trader verdict for this country" | Highest priority for that broker and country |
| Sections hidden when residents are not accepted | Gutenberg: wrap them in the **Country eligibility section** block (demo: Regulation and client protection, Deposit and withdrawal) | Review body |
| **Data sources** of each fact (Tested / Verified at source / Broker-stated), reference link, check date | Broker Reviews > *broker* > **Broker data: sources**; per legal entity: "Source of entity and licence data"; per country: "Availability source" | Source labels on the review page, directory rows, comparison table; eligible countries of the directory filter |
| Test evidence (broker × country): period, account, summary, timeline, **each deposit/withdrawal test** (method, amount, date, time, fee, result, status, steps, broker/bank status, masked details, **screenshot**, **verified by/on**, note); **spread, execution and customer support tests** (1.4.4: structured fields, other measurements, description, **several images**) | Broker Reviews > Test Evidence (sidebar: "Edit evidence data"); new box **Evidence: spread, execution and customer support tests** | Evidence page, evidence viewer, index, review "Research evidence", homepage snapshot |
| Author: name, photo, role, short/long bio, expertise, figures, principles, disclosure, **profile links** | Users > *user* > section "FX Trading Today author profile" | Bylines, author boxes, author page, Query Loop bylines, structured data |
| Header menu, mega menu, footer columns | Appearance > Menus | Header, mobile drawer, footer |
| Header button, banner, risk warning, affiliate disclosure, key pages | Settings > FX Trading Today | Header, footer, links in blocks |
| Section copy (hero, how it works, independence…) | Gutenberg: Pages > *page* | — |
| **Account types**: fields (add, remove, rename, reorder, type), values per account, row source label, **calculator values per 1.00 lot: spread cost and round-turn commission for EUR/USD and XAU/USD, Unit** | Broker Reviews > *broker* > **Broker data: account types** (box "Trading cost calculator: cost per 1.00 lot" in each account type) | Account types table, Quick Facts account list, Trading cost calculator |
| **Legal entity per country**: "Countries served (ISO codes) or key" of each legal entity, e.g. `VN,TH,ID` | Broker Reviews > *broker* > **Broker data: legal entities** | Quick Facts "Legal entity for {Country}", country message, client protection note |
| Which account fields the frontend table shows, their order, first column heading | **Broker data: account types display options** (box right below) | Account types table |
| Position of the source label in a data table (above, below, in each row, between rows after row N) | Gutenberg: select the **Broker data** block (Account types, Deposits and withdrawals, Platforms) > sidebar **Source label position** | That block |
| **Content under the author line** (default for the whole site) | **Settings > FX Trading Today > General > Content under the author line**: text (links, bold, italic and source labels allowed) + where it shows (broker reviews, test evidence, articles, pages) | Every page of the ticked types, directly below the author line at the top |
| Content under the author line for **one page** | Editor sidebar box **"Under the author line"**: Use the site default / Custom text for this page / Hide on this page | That page only |
| **Source labels inside the text** (Tested, Verified at source, Broker-stated, Not tested / Not verified) with an optional link | In any paragraph, list, heading or table cell: select text (or place the cursor), then the **Source label** button in the block toolbar (under the "More" arrow); or the **Source label** block for a "Source: [label] note" line | That page only |

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
| Broker data | One data section of a review (incl. **Score card**, 1.4.3) | Section; source label position for tables |
| Evidence data | One section of a test (1.4.4: + Spread tests, Execution tests, Customer support tests, each with its own heading, hidden when empty) | Section |
| Evidence snapshot | Key results for one broker in one country (homepage audience card) | Broker, country |
| Score weights | Score categories (bars or table) | Display style |
| Broker directory | Search + filters + sort | — |
| Test evidence index | Published evidence + tests in progress | All countries (with country filter) or only the selected country; headings; show/hide in progress |
| Post byline | Author + role + date + read time (inside Query Loop) | Show role, text before the date, read-time format |
| Trading cost calculator | LOT input (default 1.00, min 0.01, steps of 0.01) + spread cost, round-turn commission and total for EUR/USD and XAU/USD of every account type, recalculated as the visitor types | Title, broker (optional) |
| Source label | One line "Source: [label] note" | Label, label text, text before the label, note, link, open in a new tab |

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

## 3d. Version 1.4 rules: Accepting residents and Tested residents

Each broker has two independent country sections:

| Section | What it holds | Used for |
|---|---|---|
| **Broker data: accepting residents** | Checklist of all countries (search, "show selected only", select/clear shown, live count) + one **Availability source** (with reference and date) for the whole list | The only source of availability: hero badge, Quick Facts, country message, hidden sections and verdict, homepage "Brokers available in…", comparison, directory filter. Unticked countries = **Not accepting residents** (once at least one country is ticked; with none ticked, eligibility shows as pending everywhere) |
| **Broker data: tested residents** | Only countries researched in detail: Entity key, local payments, deposit/withdrawal/platform/support test status, last tested, optional country message and verdict | Country-specific legal entity (Quick Facts, regulation chain, comparison, country message), payments, test status, evidence. Adding a country here never makes it "accepting" |

Quick Facts: **"Legal entity for {country name}"** is shown only for Tested residents; **Country availability** shows the badge for the visitor's country, the Accepting residents source label and "Available in {n} countries" = number of ticked countries.

Upgrading from 1.3: brokers keep working without re-saving. Until a broker is saved, its accepted countries are read from the old rows marked "Available" (and their shared source). Saving the broker stores the checklist and removes the old availability fields from its country rows; rows that only held availability can then be removed from Tested residents.

## 3e. Version 1.4.1 rules: content under the author line, source labels

- **Content under the author line** sits directly below the author line at the top of reviews, evidence pages, articles and pages. The site default is set in Settings > General (with on/off per content type); each page can use the default, use its own text or hide it (sidebar box "Under the author line"). Allowed HTML: links, `<strong>`, `<em>`, `<br>`, `<code>` and source labels; scripts and other tags are removed on save and on output.
- **Source labels** are the same four labels used by the broker data: **Tested**, **Verified at source**, **Broker-stated**, **Not tested / Not verified**. Two ways to add them:
  - **Inline**: select words (they become the label text) or place the cursor (the label name, or your own text, is inserted), then the **Source label** toolbar button. Choose the label, an optional **link** and **Open in a new tab**. Clicking an existing label opens the same popover to change or remove it.
  - **Block** "Source label": a line "Source: [label] note", with its own link and new-tab option.
- A label **with a link** is a link to that URL (a small arrow marks it; new tab adds `rel="noopener"`); a label **without a link** is plain text. Labels use the site label styles; "Not tested / Not verified" has a dashed outline.
- In the author-line text the label can also be written by hand: `<span class="fxt-source-label" data-kind="tested">Tested</span>` (kinds: `tested`, `verified`, `stated`, `unverified`), or `<a class="fxt-source-label" data-kind="verified" href="…">Verified at source</a>` for a link.
- Article and page layout: when a page has no headings (no table of contents), the content now uses the full width instead of the narrow TOC column.

## 3f. Version 1.4.2 rules

- **Broker name** (new field) is used for the affiliate button ("Visit {name}", or the "Affiliate button label" with `{broker}` replaced), the compare button, the entity map heading and every list. When it is empty the post title is used without " review" and anything after ":" or " - ". When the post title differs from the broker name it is the H1; otherwise the H1 stays "{name} review". The affiliate URL and `rel="sponsored nofollow"` are unchanged.
- **Source labels in tables**
  - Broker data tables (Account types, Deposits and withdrawals, Platforms): block sidebar **Source label position** = above (default), below, in each row, or **between rows** after row N (a full-width row; 0 = before the first row). Trading costs keep one label per row (each fact has its own source).
  - Account rows can have their **own source** (Broker data: account types > a row > "Row source label…"); it is shown in that row whatever the position.
  - Core **Table** block: the Source label toolbar button works in any cell (inline). A row whose only content is a source label (other cells empty) becomes a full-width label row, so a label can sit between any two rows. Table styles are unchanged.
- **Score card**: no clipped text at any width (320 to 1440 px checked on all reviews). The hero no longer grows wider than the screen on phones (long broker names wrap), the score value has room for its glyphs, long labels wrap, and "Score pending" uses a smaller size.
- **Regulation and entities**: the regulatory chain (brand, legal entity, regulator, licence, jurisdiction) and the client protection note are shown only when the visitor's country is a Tested resident whose legal entity is named and **Verified at source** (or Tested with published evidence). Otherwise nothing is shown (no placeholders). Data and country links are unchanged.
- **Entity map**: columns Entity, Regulator, Jurisdiction, **Licence number** (from the legal entity). Empty or placeholder values (TBD, N/A, Research pending…) show "Not available". The heading uses the broker name.
- **Account types fields**
  - Each field has a **stable internal ID** (built-in: `min_deposit`, `spread`, `commission`, `execution`, `best_for`; new fields get an ID like `f_mva6el0am6w`). Renaming a label never changes values, display options or calculations.
  - Field types: Text, Number (with unit), **Spread (pips)** (pips for the calculator + optional display text, linked to one instrument), **Commission** (model: none / per lot per side / per lot round turn, amount in USD per lot, optional display text). Empty display text is generated from the numbers.
  - The editing order (box "account types") and the frontend order (box "display options") are separate.
  - Upgrading from 1.4.1: nothing to do. Brokers without field definitions use the built-in set with the same keys, so tables look the same; calculator numbers are read from the old texts ("From 1.0 pips", "$7 per lot round turn", "None") until the broker is saved. Texts that cannot be read ("Tiered", "Reduced", "TBD") stay N/A until numbers are entered.
- **Trading cost calculator**: replaced in 1.4.3 (see 3g). The 1.4.2 volume / instrument settings are no longer used.
- *(1.4.2 calculator, for reference)*
  - pip value per lot (USD) = units per lot x pip size x USD per 1 unit of the quote currency; spread cost = pips x pip value x lots; commission per side x 2 (round trip) or x 1 (opening), round turn x 1 (round trip) or x 0.5 (opening); total = spread + commission. The spread is paid once in both cases.
  - Spreads come from the account field of type Spread for the block's instrument; commission from the Commission field for that instrument, else the one for any instrument. Cent and other accounts can set "Units per lot for this account".
  - **N/A** whenever a needed value is missing: no spread field or pips for that instrument, commission model not set, non-USD quote without a USD rate. Nothing is guessed. Swaps, slippage, deposit fees and conversion are not included (stated under the table).
  - Values are calculated on every page view from the account data and settings, so the table always matches them.

## 3g. Version 1.4.3 rules

- **Score card in the Broker data block**: Broker data > Section **Score card** shows the same card as the review header anywhere in the review body. It uses the same data and calculation (category scores in Broker data: research summary x weights in Settings > Score categories), so both cards always match. Styling and responsive behaviour are the same (max width 32rem in the text column).
- **Cost per lot fields**: each account type (Broker data: account types) has **EUR/USD spread cost per 1.00 lot**, **XAU/USD spread cost per 1.00 lot** (numbers, decimals allowed, stored as numbers) and **Unit** (text, default `$`). Empty = N/A; **0 is a valid cost**.
- **Trading cost calculator** (block): one **LOT** input (default 1.00, minimum 0.01, steps of 0.01, positive only) and one row per account type of the broker, in the order of Broker data: account types.
  - **Total cost = cost per lot x LOT**, the same LOT for every account type, recalculated as the visitor types (no reload). Calculated in hundredths of a lot and rounded to cents (no float errors).
  - Invalid input (0, negative, more than two decimals) is marked and the last results stay; leaving the field rounds it to 0.01 steps (minimum 0.01).
  - The unit is shown before symbols (`$8.00`) and after letter codes (`8.00 USD`).
  - Without JavaScript the table shows the costs for 1.00 lot.
- **Upgrading from 1.4.2**: existing account data is unchanged. Rows saved before 1.4.3 are pre-filled once with the EUR/USD cost the 1.4.2 calculator showed for 1 lot round trip (spread pips x $10, or the account's own lot size, + commission); XAU/USD stays empty (N/A) because there was no gold data. Check the values in Broker data: account types and save. The Settings tab "Cost calculator" is gone (its saved values are left in the database, unused).

## 3h. Version 1.4.4 rules

- **Commission in the calculator**: each account type has, per instrument (EUR/USD, XAU/USD), a **spread cost per 1.00 lot** and a **commission per 1.00 lot, round turn** (opening + closing together; for a per-side commission enter twice the per-side amount), plus the Unit. Spread cost = spread cost per lot x LOT; commission = commission per lot x LOT; **total = spread cost + commission** (each rounded to cents first). 0 is a valid value; a missing value shows N/A, and a total needs both parts. Desktop: one row per account type, three columns per instrument (Spread, Commission round turn, Total). Phones: one card per account type with labelled values.
  - Upgrading from 1.4.3: the old "cost per lot" is kept as the spread cost. When it equals the value 1.4.3 pre-filled (spread + commission) it is split into its two parts; accounts without commission get commission 0; other commissions stay N/A until entered.
- **List styles** (List block > Styles): **Risk list** (warning icon per item) and **Numbered list** (styled numbers, nested lists continue as 2.1, 2.2). Both keep `ul` / `ol` / `li`, support nested lists and phones. Use an ordered list for numbered content.
- **Source labels in lists**: icon lists no longer make each item a flex container, so a label keeps its own size whatever the item length, the list length or nesting (check, lock, pros, cons, risk, numbered).
- **Legal entity mapping**: Tested residents are keyed by ISO code. A legal entity's "Countries served (ISO codes) or key" can list several codes, e.g. `VN,TH,ID` (trimmed, upper-cased, exact two-letter codes; "VNM" never matches "VN"). For a country in Tested residents the entity listing its code is used; otherwise the Tested residents row's "Entity key (optional)" link; brokers with a single entity and no ISO codes keep their pre-1.4.4 behaviour. Countries outside Tested residents never show legal entity details.
- **Regulation and entities** (hotfix after 1.4.4): the brand / legal entity / regulator / licence chain is removed. The section shows one table, **Entity map for {Broker name}**, with **every legal entity of the broker** (Entity, Regulator with its source label, Jurisdiction, Licence number), whatever the visitor's country. Placeholder licences show "Not available". The client protection note of the entity serving the visitor's country is added under the table only when that country is a Tested resident with verified entity data. As before, the whole section is hidden for countries whose residents the broker does not accept (Country eligibility section). Admin data is unchanged.
- **Test Evidence intro**: the paragraph under the title follows the hero width up to 64rem (it used the 44rem article measure and broke into a short second line on large screens); hero grid items can shrink, so long words or links wrap.
- **Images in Test Evidence**: the native media library is loaded explicitly on broker and evidence edit screens (the block editor does not always load it). "Choose image" uploads or picks an image and pre-selects the current one when replacing; "Remove" clears it. New gallery field for several images (add, remove), used by the new evidence categories. Existing screenshots are unchanged.
- **New evidence categories** (box "Evidence: spread, execution and customer support tests"; add, edit, remove rows):
  - Spread tests: date and time, instrument, account, session / conditions, lowest / average / highest spread, samples, status, other measurements, description, images.
  - Execution tests: date and time, instrument, order type, volume, conditions, execution time, slippage, status, other measurements, description, images.
  - Customer support tests: date and time, channel, language, question, first response, resolution time, result, agent name and ticket ID (masked on save), other measurements, description, images.
  - Shown by the Evidence data block (sections Spread tests / Execution tests / Customer support tests) with their own heading; a category without rows prints nothing. Deposit and withdrawal records are unchanged.
- Small fix: form fields (e.g. the comment box) no longer overflow on phones.

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

## 6e. Acceptance checks (version 1.4)

31/31 checks through the broker edit screen and the frontend: two separate boxes, full checklist with search / selected-only / select-clear / count, migrated ticks, accepted vs not accepting, availability source in Quick Facts, count of ticked countries only, entity named by country and shown only for tested residents, independence of the two sections in both directions, homepage / comparison / directory following the list, empty list = pending, importer restore. All earlier suites pass (v1.3 51/51, v1.2 63/63, journeys 47/47, v1.1 22/22) and every demo item is valid in Gutenberg.

## 6f. Acceptance checks (version 1.4.1)

20/20 checks: site default under the author line (reviews, evidence, articles when enabled), placement right after the author line, per-page custom / hide / back to default, links kept and scripts removed, inline label through the toolbar (four labels, selected text, cursor insert, link + new tab), frontend markup (link vs plain text), Source label block, blocks valid after reload. All earlier suites pass (v1.4 31/31, v1.3 51/51, v1.2 63/63, journeys 47/47, v1.1 22/22), every demo item is valid in Gutenberg and the PHP log is clean.

## 6g. Acceptance checks (version 1.4.2)

60/60 checks through the broker edit screen, Settings and the frontend: broker name on the buttons with a long SEO post title, `{broker}` placeholder, affiliate URL and rel kept; regulatory chain hidden for broker-stated data, shown for verified data in a tested country only; entity map licence numbers and neutral "Not available"; add / rename / reorder / remove account fields with stable IDs, display options (order, hidden column, heading), values kept; calculator figures checked by hand (1.0 pip x $10 = $10.00 per lot, $7 round turn, $3.50 per side x 2, opening only, cent lot size, gold contract size, USD/JPY with and without a rate, N/A for unknown data), volume presets edited and reordered; source label above / below / in each row / between rows, row source, core Table label row and inline label, toolbar button in table cells; score card and hero without overflow on 6 reviews x 5 widths; 1.4.1 data migration; importer restore. All earlier suites pass (1.4.1 20/20, 1.4 31/31 with the chain check updated to the 1.4.2 rule, 1.3 51/51, 1.2 63/63, journeys 47/47, 1.1 22/22), every demo item is valid in Gutenberg and the PHP log is clean.

## 6h. Acceptance checks (version 1.4.3)

46/46 checks: cost fields in every account type (numbers stored as numbers, 0 kept, empty = N/A, unit separate, other data preserved); LOT input position, default, min and step; brief example (Standard 8 / 24: 0.01 = $0.08 / $0.24, 0.10 = $0.80 / $2.40, 1.00 = $8.00 / $24.00, 10.00 = $80.00 / $240.00) plus 2.37, 1000 and 0.3 x 6.125; same LOT for all accounts; no page reload; invalid lots (0, -1, 0.005, 1.234) and normalisation; unit after letter codes; responsive at 360 px; still working after the review body is refreshed; new account type and edited costs appear without code changes; score card section with the same score as the header, updated by a category edit, responsive; 1.4.2 migration; demo import; version. All earlier suites pass (1.4.2 40/40 without its old calculator checks, 1.4.1 20/20, 1.4 31/31, 1.3 51/51, 1.2 63/63, journeys 47/47, 1.1 22/22), every demo item is valid in Gutenberg and the PHP log is clean.

## 6i. Acceptance checks (version 1.4.4)

60/60 checks (after the entity map hotfix): commission fields and storage (numbers, 0 kept, empty N/A); calculator headings, totals at 1.00 / 0.01 / 10 / 2.55 lots, N/A rules, unit on every value, no reload, invalid lot, phone cards; ISO entity keys (trim, case, exact match, KHM not KH, outside Tested residents, legacy link and single-entity brokers, normalisation); Entity map for {Broker name} with every entity and licence numbers, same for every country, broker-stated rows labelled, chain removed; Risk and Numbered list styles offered, semantic tags, icons and counters incl. nested, no bullets; source label size identical in tall, long and nested list items and at 360 px; evidence intro at 1440 / 1920 / 2560 / 360 px; media library loaded, Choose image, gallery add / remove, saved and shown, cleared; new categories shown, edited, hidden when empty, added back, masking; 1.4.3 migration split; demo import. Earlier suites pass with their 1.4.3 / 1.4.2 / 1.4 checks updated to the 1.4.4 rules where the brief changed them (calculator columns, entity map per country, ISO keys): 1.4.3 46/46, 1.4.2 40/40, 1.4.1 20/20, 1.4 31/31, 1.3 51/51, 1.2 63/63, journeys 47/47, 1.1 22/22. Every demo item is valid in Gutenberg and the PHP log is clean.

## 7. Known differences from the prototype

- The prototype pointed the "Visit broker (affiliate link)" button at its own page. The demo leaves `Affiliate URL` empty, so the button is hidden until a real link is entered.
- Reading time is estimated from word count. The prototype used fixed numbers.
- Overall scores are calculated from the weights. Pepperstone's demo category scores were adjusted slightly so the calculated score (4.4) and the order match V3.
- The audience card ("Vietnam, Exness") is now read from the real Test Evidence, so its row labels follow the data ("Local bank transfer withdrawal", "Customer support").
- "Latest research" uses the **Query Loop** block over Posts, so it lists articles. The prototype mixed in reviews and evidence pages, which have their own blocks.
