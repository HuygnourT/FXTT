# Audit for version 1.4.7 (calculator footnote, Markdown import, templates)

Add this section to the end of the AUDIT.md delivered with 1.4.6.

| Item | Before (1.4.6) | After (1.4.7) |
|---|---|---|
| Calculator footnote | Fixed calculation note only | Meta `fxt_cost_footnote` (field type `richtext` with `basic`: p, br, a, strong, em; `Fields::clean_rich( $html, true )`), box `fxt-cost-footnote`; `fxt_core_cost_footnote()`; `.cost-calc__footnote` right after the calculator's `.table-scroll` |
| Markdown | None | `includes/class-markdown.php` (converter, no dependency: Markdown to a tree of core blocks) and `includes/class-markdown-import.php` (REST `POST fxt/v1/markdown` with per-post `edit_post` check, editor assets, template download through `admin-post.php?action=fxt_markdown_template` with nonce, page Broker Reviews > Markdown import) |
| Editor | None | `assets/js/markdown-import.js` (PluginDocumentSettingPanel + Modal: file or paste, Append / Replace, BlockPreview, confirmation), `assets/css/markdown-import.css`; blocks created with `wp.blocks.createBlock`, inserted with `insertBlocks` / `replaceBlocks` (undoable; saved with the post) |
| Templates | None | `templates/markdown/broker-review-template.md`, `templates/markdown/test-evidence-template.md` |
| Lists in the editor | Bullets and numbers only inside `.prose` (site) | Same rules for `.editor-styles-wrapper` (editor, block previews) |
| Textarea fields | Stored with the line endings the browser sent (CRLF) | Line endings normalised to LF |
| Admin init | `term_schemas()` built at plugin load (translations loaded too early) | Taxonomy names only at load |

No data migration. Imports change `post_content` only; the importer never writes post meta or post settings. Demo: calculator footnotes for Exness and IC Markets (sample data).

# Audit for version 1.4.8 (multiple source labels, Affiliate CTA block, instrument evidence dialog)

| Item | Before (1.4.7) | After (1.4.8) |
|---|---|---|
| Source label block | One label: `kind`, `text`, `url`, `newTab` + `prefix`, `note`, `visibility` | + `labels[]` (kind incl. `custom`, name, url, newTab, before, after, hidden); `fxt_core_source_block_labels()` reads old blocks as one label; `fxt_core_source_block_tag()` (http/https/mailto only, `rel="noopener noreferrer"` in a new tab); editor list with add / remove / reorder; `fxt_core_label_markup()` accepts the custom kind |
| Affiliate CTA | Hero button only | Block `fxt/affiliate-cta` (label, brokerId, style, justify, newTab), link from `fxt_affiliate_url` only, `rel="sponsored nofollow"`, nothing without a URL; Markdown `[[affiliate-cta]]` (Broker Reviews) |
| Spread evidence data | Images per session | Images per instrument (`sessions[].instruments[].images`); `sessions[].images` kept as unassigned (admin only, field option `hide_empty`, `assign`; "Assign to" in admin.js); 1.4.4 rows give their images to their instrument |
| Spread evidence view | Thumbnails under each session | Column "Evidence" after Instrument, `View evidence (n)` with the images as JSON (no image loaded with the page); dialog in `assets/js/evidence-gallery.js` (`fxt-core-evidence-gallery`), styles `.evidence-gallery*` in pages.css |
| Markdown import entry points | Sidebar panel | + list row action, admin page record picker (`?fxt-md-import=1` opens the dialog), header button (PluginSidebar, pinned once), ⋮ menu item |
| Demo | No images | Generated sample PNGs (GD) for spread instruments, marker `_fxt_demo_key = image:*`, removed with the demo; Exness sample Affiliate URL; Source label block with two labels; Affiliate CTA block in every review |

Migration: none needed at save time. Unknown relationships are never guessed: session images stay unassigned until an editor assigns them.
