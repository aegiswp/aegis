# Contributing Documentation

How to write and maintain Aegis documentation. Use this page when your change is docs-only or when a feature PR needs matching user/developer docs.

For general contribution workflow (branches, commits, PRs), see [[contributing]].

## Where documentation lives

Docs are kept **per product**. Each product has its own `docs/` folder next to its code and its own `aegis-docs.json` manifest:

| Product | Type | Local tree | Doc Set |
|---------|------|------------|---------|
| **Aegis theme** | theme | `wp-content/themes/aegis/docs/` | Aegis theme |
| **Aegis Dash** | plugin | `wp-content/plugins/aegis-dash/docs/` | Aegis Dash |
| **Aegis Pro** | add-on | `wp-content/plugins/aegis-pro/docs/` | Aegis Pro |
| **Aegis Ads** | add-on | `wp-content/plugins/aegis-ads/docs/` | Aegis Ads |

The `docs/` folders are **local only**. Each repository lists `/docs/` in `.gitignore`, so documentation is never pushed to GitHub or packed into a release zip.

The **Aegis Docs** plugin imports each folder into the site as one Doc Set. The Markdown files are the source; the site shows what the import last read:

```bash
wp aegis-docs import wp-content/themes/aegis/docs wp-content/plugins/aegis-dash/docs wp-content/plugins/aegis-pro/docs wp-content/plugins/aegis-ads/docs
```

Import related folders together so links between them resolve. Running the import again updates the existing docs, so edit the Markdown and re-import instead of editing imported pages.

### How products are organised

Every product is a Doc Set under a brand set (**Aegis**). A product is one of four types, and a product can name the set it builds on. The manifest's `set` block says so:

```json
"set": {
  "name": "Aegis Pro",
  "slug": "pro",
  "type": "add-on",
  "extends": "aegis-dash",
  "order": 2,
  "parent": { "name": "Aegis", "slug": "aegis" }
}
```

| Key | Meaning |
|-----|---------|
| `type` | `theme`, `child-theme`, `plugin` or `add-on`. Shown as a badge on the Doc Set header. |
| `extends` | Slug of the Doc Set this product builds on, such as a child theme's parent theme or an add-on's base plugin. The header links to it, so the child's docs only need to cover what differs. |
| `aliases` | Earlier slugs of a renamed product, so the import keeps its Doc Set and docs instead of creating a copy. |
| `order` | Position among the products in the brand. |

The manifest's `package` id ties each page to its source file. Never change it after the first import.

To document a new theme, child theme or plugin, create its `docs/` folder and manifest with its own slug and order, document only what belongs to that product, and link to the parent product's docs for the rest.

The theme and its siblings link to each other with normal relative paths (for example `../../../../plugins/aegis-dash/docs/blocks/custom-blocks.md`); the import turns them into links to the matching page.

Root files that are also documentation:

- `README.md`, `INSTRUCTIONS.md`, `DESIGN.md`, `BUILD.md`
- `CONTRIBUTING.md`
- WordPress.org `readme.txt` (theme / plugins)

Screenshots go in the folder's `screenshots/` directory and are linked like any image: `![What the screenshot shows](../screenshots/name.png)`. On import, each PNG, JPG, GIF, WebP, or AVIF file a doc links to is uploaded to the Media Library once and reused on later imports.

## Scope rules

- **Theme docs** cover templates, patterns, `theme.json` tokens, style variations, the framework's core block enhancements, and theme-owned features.
- **Aegis Dash docs** cover the Aegis blocks (Countdown, Slider, Toggle, Related Posts, Map, Modal), the admin dashboard, integrations, snippets and conditionals. Aegis Pro and Aegis Ads document their own features. Do not duplicate those pages under the theme tree.
- Each product documents only what it offers. If a feature moves to another product, move its page and fix the links.
- Cross-link instead of copying: use relative links to the sibling docs trees.
- Prefer design tokens over hardcoded hex in examples. When documenting palette values, match live `theme.json` (see [[color-palette]] and root `DESIGN.md`).
- Keep counts accurate (patterns, templates, palette size). Prefer [[introduction-inventory]] and on-disk trees over outdated README copy.

## Page anatomy

Each page is a Markdown file under `docs/`. Typical layout:

```text
docs/
├── _sidebar.md          # Nav (required for every new page)
├── _footer.md
├── home.md
├── getting-started/
├── using-the-theme/
├── design-system/
├── blocks/
├── features/
├── development/
├── reference/
└── troubleshooting/
```

### File naming

- Lowercase kebab-case: `quick-start-guide.md`, `css-variables-reference.md`.
- One topic per file. Prefer a short slug that matches how you will link it.

### Links

Docs use wiki-style links:

```markdown
[[color-palette]]
[[color-palette|Color Palette]]
```

The slug is the file name without `.md`. For links to another docs tree, use normal relative Markdown links:

```markdown
[Aegis Dash Patterns](../../../../plugins/aegis-dash/docs/features/plugin-patterns.md)
```

### Headings and tone

- Start with a single `#` title that matches the sidebar label.
- Use `##` / `###` for sections; keep one job per section.
- Write for the reader (site builder or developer); avoid internal chatty notes.
- Use tables for inventories and option matrices.
- Use fenced code blocks for CSS custom properties, block markup, and shell commands.

## Adding or updating a page

1. Create or edit the `.md` file in the correct folder under `docs/`.
2. Add the file to its section in `docs/aegis-docs.json`, and a sidebar entry in `docs/_sidebar.md`. Files missing from the manifest are imported into a "More" section.
3. If the page is a primary topic, add a one-line link under the matching section in `docs/home.md`.
4. From feature/code PRs, update any page that would otherwise be wrong (counts, hexes, APIs, file paths).
5. Re-import the folder to see the change on the site. Documentation stays local, so there is nothing to commit or push.

### Sidebar example

```markdown
## Development

- [[contributing|Contributing]]
- [[documentation|Contributing Documentation]]
```

### Checklist (docs PR)

- [ ] Page title and filename are clear and kebab-case
- [ ] `aegis-docs.json` and `_sidebar.md` updated (and `home.md` if it is a top-level topic)
- [ ] Internal links use `[[slug]]` / `[[slug|Label]]`
- [ ] No plugin/Pro content duplicated into theme docs (cross-link instead)
- [ ] Token / palette / pattern counts match `theme.json` and on-disk files
- [ ] Counts, links and screenshots checked; the folder re-imports without unresolved links

## Related root docs

| File | When to update |
|------|----------------|
| `DESIGN.md` | Design tokens change in `theme.json` |
| `readme.txt` | Public feature summary / pattern counts for WordPress.org |
| `README.md` | Contributor overview tables that mirror the design system |
| `INSTRUCTIONS.md` / `BUILD.md` | Local setup or build commands change |

## What not to do

- Do not put contributor docs only in chat or issues; land them in `docs/` or the root files above.
- Do not invent Secondary / Tertiary / Quaternary palette families — they were removed.
- Do not document Success / Info / Warning / Error as full 50–950 scales; each ships `100` / `500` / `600` only.
- Do not assume `docs/` is present in the theme zip or the repository: it is local only.

## Next steps

- [[contributing]] — Branches, commits, and PR process
- [[development-setup]] — Local environment
- [[file-structure]] — Repository layout
- [[introduction-inventory]] — Ground-truth inventory for counts and structure
