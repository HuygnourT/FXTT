# FX Trading Today: WordPress Theme Architecture & Implementation Plan

Tài liệu này là bước "audit + kiến trúc" trước khi code (mục 10 của brief).
Prototype V3 (`fx-trading-today/`) là **source of truth về UI/UX**.
Mọi quyết định đánh dấu ⚑ cần bạn xác nhận trước khi implementation.

---

## 1. Audit Prototype V3

### 1.1 Trang hiện có

| Prototype | Mục đích | Dữ liệu |
|---|---|---|
| `index.html` | Homepage | Hỗn hợp: editorial (hero copy, how it works, who it's for, independence, final CTA) + dynamic (hero theo quốc gia, broker cards, compare preview, latest research) |
| `broker-reviews.html` | Danh sách review, search, 7 filter, sort | Dynamic hoàn toàn |
| `review.html?broker=` | Review 1 broker, 11 section, theo quốc gia | Structured data + editorial (verdict, pros/cons, final verdict) |
| `compare.html` | So sánh 8 nhóm tiêu chí | Dynamic hoàn toàn |
| `methodology.html` | Bài editorial dài | Editorial (đã là HTML tĩnh) |
| `evidence.html?broker=&country=` | Bằng chứng test | Structured data + ghi chú editorial |
| `author.html?author=` | Hồ sơ tác giả | User profile + danh sách bài |

### 1.2 Reusable UI components (đã có CSS trong prototype)

**Chrome:** prototype banner, header + mega menu, mobile drawer, country chip + country picker dialog, breadcrumb, footer + risk warning.
**Atoms:** button (primary/secondary/light/sm), tag-sample, chip, badge (status/availability/test), score, yes/no mark, unverified mark, avatar, monogram.
**Molecules:** broker card, review row, hero-broker row, fact grid, data table, platform tile, entity chain, callout, evidence link, link card, author line, author box, summary card, timeline (vertical/horizontal), meter, weight stack, TOC.
**Organisms:** country hero module, compare widget, filters panel, score card, evidence viewer dialog, process flow, trust bar, steps, audience bento, money flow, final CTA.

### 1.3 Thực thể dữ liệu (từ `assets/js/data/*`)

- **Broker:** score + 6 điểm thành phần, status, founded, min deposit, regulators, platforms, account types (bảng), costs, trading conditions, currencies, payments, **dữ liệu theo từng quốc gia** (availability, entity, local payments, test status), verdict, pros, cons, final verdict, author.
- **Market (quốc gia):** ISO code, tên, currency, research status.
- **Test evidence:** broker × country, period, account, status, summary, timeline, deposits, withdrawals, record chi tiết đã che thông tin nhạy cảm, research notes.
- **Author:** name, role, bio, location, since, languages, markets, expertise, stats, principles, disclosure.
- **Global:** brand, banner, risk warning, affiliate disclosure, score weights, default country, header CTA.

---

## 2. WordPress Content Model

### 2.1 Tổng quan

| Dữ liệu | WordPress | Lý do |
|---|---|---|
| Broker review | **CPT `fxt_broker`** | Entity riêng, archive riêng, nhiều structured fields |
| Test evidence | **CPT `fxt_evidence`** | Một post = một broker × một quốc gia, có URL riêng |
| Guides, analysis, news | **Posts** + Categories | Editorial chuẩn (Country guide, Regulation guide, Trading cost analysis, Broker comparison) |
| Home, Methodology, Compare, About | **Pages** | Gutenberg content + patterns + dynamic blocks |
| Quốc gia | **Taxonomy `fxt_market`** (+ term meta) | Một nguồn dữ liệu cho broker, evidence, filter, picker |
| Regulators (group) | **Taxonomy `fxt_regulator`** | Filter + hiển thị chip |
| Platforms | **Taxonomy `fxt_platform`** | Filter + compare rows |
| Account categories | **Taxonomy `fxt_account_type`** (Standard/Raw/Pro/Cent) | Filter; bảng chi tiết nằm trong meta |
| Tác giả | **WordPress Users** + user meta | Dùng `post_author` + `author.php` có sẵn của WP |
| Thiết lập chung | **Site Identity** (tên, logo) + **trang Theme Settings** (1 option) | Single source of truth |
| Menu | **Navigation Menus** (`primary`, `footer-1..5`) | Sửa trong Appearance > Menus |

### 2.2 `fxt_broker`

URL: `/broker-reviews/` (archive), `/broker-reviews/{slug}/` (single). Supports: title, editor, excerpt, thumbnail (logo), author, revisions, custom-fields.

| Field | Nơi lưu | Ghi chú |
|---|---|---|
| Tên broker | `post_title` | |
| Logo | Featured image | Fallback: monogram từ meta `fxt_monogram` |
| Short verdict | `post_excerpt` | Dùng ở cards, hero, list |
| Review body (verdict, analysis, pros/cons, final verdict) | `post_content` (Gutenberg) | Editorial, sửa bằng core blocks + patterns |
| Score, 6 điểm thành phần | `fxt_score` (number, nullable), `fxt_score_breakdown` (object) | Rỗng = "Pending" |
| Review status | `fxt_status` (`published` / `updating` / `pending`) | |
| Founded, min deposit, max leverage, execution, order types | meta scalar | |
| Costs | `fxt_costs` (object: spread, commission, swap, other_fees) | |
| Currencies | `fxt_currencies` (array) | |
| Payment rails | `fxt_payments` (object: bank, cards, ewallets, crypto + processing times) | |
| Account rows | `fxt_accounts` (array of rows) | Bảng "Account types" |
| Legal entities | `fxt_entities` (array: key, name, regulator, licence, jurisdiction, protection, **verified**) | `verified=false` hiển thị "unverified" |
| Dữ liệu theo quốc gia | `fxt_markets` (object keyed by ISO: availability, entity_key, local_payments, tests{deposit, withdrawal, platform, support, last_tested}) | Trung tâm của nguyên tắc "country first" |
| Affiliate link | `fxt_affiliate_url`, `fxt_affiliate_label` | Luôn render `rel="sponsored nofollow"` + nhãn |
| Regulators / platforms / account types | Taxonomies | |

Mọi meta đăng ký bằng `register_post_meta()` với `show_in_rest`, JSON schema, `sanitize_callback`, `auth_callback`. Dữ liệu dạng object/array lưu một meta key duy nhất (không tạo hàng chục key rời).

### 2.3 `fxt_evidence`

URL: `/test-evidence/{broker}-{country}/`. Content (Gutenberg) = research notes ("What happened during our test?").
Meta: `fxt_broker_id` (relation), term `fxt_market`, `fxt_period` {from, to}, `fxt_account_label`, `fxt_status`, `fxt_summary[]`, `fxt_timeline[]`, `fxt_deposits[]`, `fxt_withdrawals[]`, `fxt_records{}` (steps, broker/bank status, masked fields, note).

**Bảo vệ dữ liệu nhạy cảm:** `sanitize_callback` tự che account number, tên, bank account, transaction reference (chỉ giữ 4 ký tự cuối) **khi lưu**. Dữ liệu chưa che không bao giờ vào database.

### 2.4 Tác giả (Users)

Mặc định Timo, Founder & Lead Broker Reviewer. User meta: `fxt_role_title`, `description` (bio core), `fxt_short_bio`, `fxt_location`, `fxt_since`, `fxt_languages`, `fxt_expertise`, `fxt_stats`, `fxt_principles`, `fxt_avatar_id` (ảnh local, fallback initials, không phụ thuộc Gravatar). Sửa trong Users > Profile. Byline và author box lấy từ `post_author`, nên mọi bài mới tự có tác giả.

### 2.5 Theme Settings (một option `fxt_settings`, Settings API, nonce + sanitize)

Prototype banner (bật/tắt + text) · **hiện/ẩn nhãn Sample data** (tắt khi có dữ liệu thật) · default country · header CTA (label + URL) · risk warning · affiliate disclosure · footer tagline · social links · score weights (6 hạng mục, tổng phải = 100%).
Brand name và logo dùng **Site Identity** có sẵn (`blogname`, `custom_logo`): không lưu lặp.

---

## 3. Gutenberg Strategy

### 3.1 Core Blocks (đa số nội dung)

Heading, Paragraph, List, Table, Quote, Image, Buttons, Columns, Group, Cover, Details (FAQ), Separator, Query Loop (Latest research), Navigation (không dùng: menu render bằng PHP để giữ mega menu).

### 3.2 Block Styles (`register_block_style`, chỉ CSS, không JS)

| Block | Style | Tương ứng prototype |
|---|---|---|
| Paragraph | `lead`, `eyebrow` | `.page-hero__lead`, `.u-label` |
| List | `check`, `pros`, `cons`, `notes` | `.check-list`, `.pc-list--pro/con`, `.notes-list` |
| Table | `data` | `.data-table` trong `.table-scroll` |
| Group | `card`, `callout`, `surface-band`, `dark-band` | `.card`, `.callout`, `.section--surface`, `.audience-card--feature` |
| Button | `secondary`, `light` | `.btn--secondary`, `.btn--light` |
| Quote | `verdict` | `.verdict-block` |

### 3.3 Block Patterns (`/patterns/*.php`, tự đăng ký, chỉ dùng core blocks)

Hero intro · Trust bar · How it works (4 steps) · Who it's for (bento) · Editorial independence (money flow) · Final CTA · Pros & Cons · Trader verdict · Final verdict · Methodology CTA · Process flow · Verification chain · Testing grid · Country example table · FAQ · Affiliate disclosure · Callout.
Category pattern: "FX Trading Today".

### 3.4 Custom blocks: chỉ cho phần dữ liệu động mà core blocks không biểu diễn được

Tất cả là **dynamic blocks** (`block.json` + `render.php`, render phía server, editor preview bằng `ServerSideRender`). Viết bằng JS thuần, **không cần build step** (không webpack/node khi cài theme).

| Block | Dùng ở | Lý do không dùng core |
|---|---|---|
| `fxt/country-brokers` | Hero homepage | Danh sách theo quốc gia + country picker |
| `fxt/broker-cards` | Homepage, page bất kỳ | Card từ meta, sắp theo score |
| `fxt/broker-compare` | Homepage (preview), Compare page (full) | Widget tương tác |
| `fxt/broker-data` | Bên trong review | 1 block, thuộc tính `section`: quick-facts, regulation, costs, platforms, payments, accounts, evidence, score |
| `fxt/evidence-data` | Bên trong evidence | `section`: summary, timeline, deposits, withdrawals |
| `fxt/score-weights` | Homepage, Methodology | Đọc trọng số từ Theme Settings (single source) |

**Điểm quan trọng:** trang review là Gutenberg content xen kẽ core blocks (editorial) và `fxt/broker-data` (dữ liệu). CPT `fxt_broker` có **block template** mặc định đúng thứ tự 11 section của prototype, nên broker mới tạo ra đã có bố cục chuẩn, và editor vẫn có thể đổi thứ tự hoặc thêm đoạn văn ở giữa. ⚑

---

## 4. Templates (classic PHP theme + `theme.json`, kiểu hybrid) ⚑

| Prototype | Template |
|---|---|
| index.html | `front-page.php` (render page content của Home) |
| broker-reviews.html | `archive-fxt_broker.php`: filter bằng GET params, chạy **server-side** (hoạt động không cần JS, SEO được); JS chỉ cập nhật tức thì |
| review.html | `single-fxt_broker.php`: hero + score card từ meta, rồi `the_content()` |
| compare.html | Page dùng block `fxt/broker-compare` (không cần template riêng) |
| methodology.html | `page.php` + TOC tự sinh từ heading của content |
| evidence.html | `single-fxt_evidence.php` + `archive-fxt_evidence.php` |
| author.html | `author.php` |
| (mới) | `single.php`, `archive.php`, `search.php`, `404.php`, `index.php` |

Chọn hybrid (PHP templates) thay vì Block Theme / Site Editor vì: phần lớn bố cục phụ thuộc dữ liệu động (country, compare, filters), mega menu và country picker cần markup chính xác, và hiệu năng/kiểm soát HTML tốt hơn. Phần nội dung vẫn 100% Gutenberg.

Country context: JS giữ nguyên cơ chế của prototype (simulated, mặc định từ setting, `?country=XX`), nhưng **server render sẵn quốc gia mặc định** để nội dung có trong HTML ban đầu (SEO).

---

## 5. CSS & Design System Mapping

| Prototype | Theme |
|---|---|
| `tokens.css` | `theme.json` → palette, fontFamilies, fontSizes (fluid), spacingSizes, layout (`contentSize` 760px, `wideSize` 1200px). Tên biến cũ giữ làm alias: `--color-accent: var(--wp--preset--color--accent)`, nên CSS component tái sử dụng gần như nguyên vẹn |
| `base.css` | `assets/css/base.css` |
| `components.css` | `assets/css/components.css` |
| `layout.css` | `assets/css/layout.css` (header, footer, drawer, dialog) |
| `sections.css` | `assets/css/patterns.css` (style cho patterns/homepage) |
| `pages.css` | `assets/css/templates/{broker,archive,evidence,author}.css`: chỉ load ở template tương ứng |
| (mới) | `assets/css/blocks.css`: map core block markup (`.wp-block-table`, `.wp-block-list`...) và block styles sang design system |
| Google Fonts CDN | **Self-host** woff2 (Newsreader, IBM Plex Sans/Mono) khai báo `fontFace` trong `theme.json`: không gọi CDN, editor và frontend dùng chung font |

Editor: `add_editor_style()` + `theme.json`, nên nội dung trong Gutenberg gần giống frontend.
Load có điều kiện: CSS/JS của block chỉ enqueue khi block có trên trang (`block.json` `style`/`viewScript`), template CSS theo `is_singular()`/`is_post_type_archive()`. Script `defer` (WP 6.3+ strategy).

---

## 6. JavaScript

Port từ prototype, bỏ phần dữ liệu giả lập (dữ liệu giờ đến từ PHP):

| Prototype | Theme |
|---|---|
| `core/cp.js` (templating, country state) | `assets/js/core.js` |
| `core/layout.js` (menu, drawer, picker) | `assets/js/navigation.js` + `country-picker.js` (header/footer render bằng PHP) |
| `components/compare.js` | `blocks/broker-compare/view.js` |
| `pages/reviews.js` | `assets/js/broker-filters.js` (progressive enhancement trên form GET) |
| `components/toc.js` | `assets/js/toc.js` |
| evidence viewer | `blocks/evidence-data/view.js` (native `<dialog>`) |

Dữ liệu cho compare/hero: PHP in một JSON gọn (`wp_add_inline_script`) **chỉ trên trang có block**, cache bằng transient, xóa cache khi lưu broker. Không cần REST round-trip.

---

## 7. Cấu trúc thư mục

```
fxt-theme/                      (theme)
├── style.css                   header theme
├── theme.json
├── functions.php               chỉ bootstrap, require inc/*
├── screenshot.png
├── inc/
│   ├── setup.php               supports, menus, image sizes, editor style
│   ├── enqueue.php             CSS/JS có điều kiện
│   ├── template-tags.php       fxt_byline(), fxt_breadcrumb(), fxt_badge()...
│   ├── nav-walker.php          mega menu từ menu cấp 2
│   ├── block-styles.php
│   ├── patterns.php            pattern category
│   └── seo.php                 JSON-LD (Organization, Review, ProfilePage)
├── template-parts/             header, footer, banner, country-dialog, broker-card, review-row, author-box...
├── patterns/                   *.php (core blocks only)
├── blocks/                     6 dynamic blocks (block.json, render.php, editor.js, view.js)
├── assets/{css,js,fonts,img}
├── languages/fxt.pot
└── *.php                       front-page, single-fxt_broker, archive-fxt_broker, single-fxt_evidence, author, page, single, archive, search, 404, index

fxt-core/                       (companion plugin, xem ⚑ mục 9)
├── fxt-core.php
├── includes/
│   ├── class-post-types.php    fxt_broker, fxt_evidence
│   ├── class-taxonomies.php    fxt_market, fxt_regulator, fxt_platform, fxt_account_type
│   ├── class-meta.php          register_post_meta + schema + sanitize (gồm masking)
│   ├── class-meta-boxes.php    UI nhập liệu (repeatable rows)
│   ├── class-user-meta.php
│   ├── class-settings.php      Theme Settings page
│   ├── class-repository.php    truy vấn + cache (single source cho template & block)
│   └── class-demo-importer.php
├── demo/                       JSON + Gutenberg markup + placeholder images
└── cli/                        `wp fxt demo import|reset`
```

---

## 8. Demo Data Import

**Vị trí:** Appearance > Import Demo Data (và WP-CLI `wp fxt demo import`).
**Bảo mật:** `manage_options` + nonce + chạy từng bước có báo tiến độ.

**Nguồn dữ liệu:** `demo/*.json` (structured) + `demo/content/*.html` (Gutenberg markup hợp lệ, mở được bằng editor), ảnh placeholder trong `demo/media/`.

**Thứ tự import:**
1. Markets (8 quốc gia + term meta), regulators, platforms, account types, categories
2. User Timo + user meta (gán bài cho Timo)
3. Media (logo/placeholder → Media Library)
4. 6 broker reviews (meta + taxonomies + Gutenberg content theo block template)
5. Test evidence (Exness × Vietnam đầy đủ)
6. Posts (5 bài Latest research)
7. Pages: Home, Methodology, Compare Brokers, About (+ Reading settings: static front page)
8. Menus: primary (+ mega menu), footer 1 đến 5, gán vị trí
9. Theme Settings (banner, disclosure, risk warning, weights, default country)

**Idempotent:** mỗi item có meta `_fxt_demo_key`; chạy lại thì cập nhật, không nhân bản. Có nút **Remove demo content** (chỉ xóa item có cờ demo).
**Không import CSS:** importer chỉ tạo content + configuration. Xóa demo thì design system vẫn nguyên.
**Sau import:** website hiển thị như Prototype V3, mọi bài mở được bằng Gutenberg.

---

## 9. Quyết định cần xác nhận ⚑

1. **Theme + companion plugin (khuyến nghị)** hay chỉ một theme. Chuẩn WordPress: CPT/dữ liệu thuộc "plugin territory"; đổi theme không mất dữ liệu broker. Theme sẽ hiện thông báo cài plugin nếu thiếu.
2. **Custom fields UI:** native meta boxes viết tay (khuyến nghị, không phụ thuộc plugin, có repeatable rows) hay **ACF Pro** (UI đẹp hơn, nhưng là plugin trả phí).
3. **Review page:** data sections là block di chuyển được trong Gutenberg (khuyến nghị) hay bố cục cố định trong template.
4. Ngôn ngữ: theme translation-ready (text domain `fxt`), demo content tiếng Anh như prototype.

---

## 10. Implementation Plan

| Phase | Nội dung | Kết quả kiểm tra |
|---|---|---|
| **P1 Foundation** | Scaffold theme, `theme.json`, port CSS, self-host fonts, header/mega menu/drawer/footer từ Menus, Theme Settings, templates cơ bản | Theme activate được, trang trống đúng design system |
| **P2 Content model** | CPTs, taxonomies, meta + schema + sanitize/masking, meta boxes, user meta, repository + cache | Nhập/lưu broker trong Admin, REST trả meta đúng |
| **P3 Templates** | single broker, archive broker + filters server-side, evidence, author, front page, page + TOC, single/archive/search/404 | Render đúng với dữ liệu nhập tay |
| **P4 Blocks & patterns** | 6 dynamic blocks, 17 patterns, block styles, editor styles, block template cho `fxt_broker` | Editor hiển thị gần frontend |
| **P5 Interactivity** | Country state + picker, compare, filters, TOC, evidence viewer | Các hành trình như mục 10 của brief v2 |
| **P6 Demo importer** | JSON + Gutenberg markup cho toàn bộ nội dung V3, importer idempotent, remove, WP-CLI | Install → Activate → Import → giống V3 |
| **P7 QA** | `php -l`, kiểm tra escaping/sanitization/nonce, chạy WordPress thật (WordPress Playground) → import → chụp màn hình so với V3 ở 1440/390, kiểm tra hành trình, a11y, overflow | Báo cáo kiểm thử + README hướng dẫn admin |

**Giao nộp:** `fxt-theme.zip` + `fxt-core.zip` (hoặc 1 zip nếu chọn phương án chỉ theme) + README hướng dẫn cài đặt và quản trị.

**Giới hạn đã biết:** môi trường hiện tại chặn wordpress.org, nên mình sẽ kiểm thử bằng WordPress Playground (npm). Nếu Playground không tải được WordPress core, mình sẽ kiểm thử PHP độc lập và ghi rõ phần chưa chạy trên WordPress thật.
