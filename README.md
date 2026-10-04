# FX Trading Today: WordPress theme + Core plugin

Production build of Prototype V3, packaged as two parts:

| Gói | Vai trò |
|---|---|
| `fx-trading-today` (theme) | Presentation only: `theme.json`, CSS, PHP templates, header/footer, patterns, block styles |
| `fxt-core` (plugin) | Data and logic: CPTs, taxonomies, meta, settings, 8 dynamic blocks, REST render, country picker, demo importer |

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

## 2. Where to edit what

| Item to change | Location |
|---|---|
| Body copy for each section (home page, methodology…) | Gutenberg: Pages > *the page* (all core blocks) |
| Reuse a section | Block inserter > Patterns > "FX Trading Today: home / sections" |
| Broker review copy (verdict, pros/cons, notes) | Broker Reviews > *broker*: blocks in the editor |
| Structured broker data (score, costs, entities, by-country conditions…) | Broker Reviews > *broker*: the **Broker data** panels below the editor |
| Regulators, Platforms, Account types | Broker Reviews > Regulators / Platforms / Account Types (sidebar checkboxes) |
| Countries (ISO code, currency, research status, order) | Broker Reviews > Markets |
| Test evidence (summary, timeline, deposits, withdrawals, viewer records) | Broker Reviews > Test Evidence |
| Author (role, short bio, expertise, figures, principles…) | Users > *user* > section "FX Trading Today author profile" |
| Prototype banner, Sample data label, default country, header button, risk warning, affiliate disclosure, score weights, key pages | **Settings > FX Trading Today** |
| Header menu, mega menu, 5 footer columns | Appearance > Menus (locations Primary, Footer column 1–5) |
| Logo, site name | Appearance > Customize > Site Identity |
| Colours, fonts, sizes | `theme.json` (single source; CSS only aliases the variables) |

### Mega menu
In the **Primary** menu, the top-level item with the CSS class `mega-top-brokers` opens the mega menu:
- Column 1: brokers with the highest scores (automatic).
- Column 2: the item's child items. The column heading is the parent item's *Description*.
- Column 3: countries (automatic, from Markets).
- Featured card: a child item with the class `mega-feature`.

Enable the CSS class field in Appearance > Menus > Screen Options.

### Content inside the page header
On pages that use the theme's templates, consecutive blocks at the **top** of the content that carry the class `page-hero__extra` render inside the page header. Examples: the research-areas chips on the Broker Reviews page, and the principles on the Methodology page.

## 3. Blocks (category "FX Trading Today")

| Block | Purpose |
|---|---|
| Brokers in your country | Hero list that changes with the selected country |
| Broker cards | Cards for the highest-scoring brokers |
| Broker comparison | Comparison widget (preview / full, 2–4 slots, sync to URL) |
| Broker data | One data section of a review (quick facts, regulation, costs, platforms, payments, accounts, evidence, final score) |
| Evidence data | Summary / timeline / deposits / withdrawals of a test |
| Score weights | Weights from Settings (list or table) |
| Broker directory | Search + filters + sort |
| Test evidence index | List of published evidence pages |

Every block is server-rendered (`block.json` + `render.php`) and previewed live in the editor. Data is never written into the post content, so changing a number in the meta box updates every page that shows it.

Block style variations (Block sidebar > Styles): Lead, Eyebrow, Small note, Sample data tag, Strong link, Link card (paragraph); Check / Lock / Pros / Cons / Researcher notes / Chips (list); Data table; Card, Callout (group); Verdict (quote); Pros and cons (columns); Secondary, Light (button); Search field.

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

## 7. Known differences from the prototype

- The prototype pointed the "Visit broker (affiliate link)" button at its own page. The demo leaves `Affiliate URL` empty, so the button is hidden until a real link is entered.
- Reading time is estimated from word count. The prototype used fixed numbers.
- "Latest research" uses the **Query Loop** block over Posts, so it lists articles. The prototype mixed in reviews and evidence pages, which have their own blocks.
