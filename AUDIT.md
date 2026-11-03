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
