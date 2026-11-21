# Core Video Block

Aegis does **not** register a custom `aegis/video` block. Video uses WordPress **`core/video`**, enhanced by the theme framework, the Aegis Video add-on, Aegis Dash, and Aegis Pro.

## Layer responsibilities

| Layer | Enhancement |
|-------|-------------|
| **Theme framework** | Styling, MediaElement skins (`video.css`) |
| **Aegis Video add-on** | The custom player, player styles and facades, the video type picker in the editor (YouTube / Vimeo / Audio / Bunny), and the schema markup extra |
| **Aegis Dash** | Lists the Video feature toggles at **Aegis → Blocks → Video** and hosts the BunnyCDN card at **Aegis → Connectors** |
| **Aegis Pro** | BunnyCDN, chapters, engagement, marketing, analytics, advanced player extras, privacy, multi-audio, SEO sitemaps — see [Video Stack](../../../../plugins/aegis-pro/docs/features/video-stack.md) |

## Custom player

The custom player, theater mode, keyboard shortcuts and player skins are provided by the free **Aegis Video** add-on, not the theme. Without the add-on, the Video block uses the standard WordPress player, styled by the theme.

## Usage

1. Insert the standard **Video** block (`core/video`) from the block inserter.
2. Upload or embed a video as with any block theme.
3. Configure Pro features in the block sidebar when Pro is active and the matching extra is on.
4. Toggle player extras at **Aegis → Blocks → Video**. BunnyCDN Stream credentials and extras are at **Aegis → Connectors → BunnyCDN**.

## Migrating legacy `aegis/video` content

Older sites may still contain **`aegis/video`** blocks. Replace them with **`core/video`**.

The editor also registers a deprecated `aegis/video` stub so unsaved legacy blocks can convert. See [Updating](../getting-started/updating.md#aegisvideo--corevideo) and [Pro Known Issues](../../../../plugins/aegis-pro/docs/troubleshooting/known-issues.md#legacy-aegisvideo-block).

## Next Steps

- [[enhanced-core-blocks]] — Framework media block styling
- [Aegis Dash Block Variations](../../../../plugins/aegis-dash/docs/blocks/block-variations.md) — Video toggles
- [Pro Video Stack](../../../../plugins/aegis-pro/docs/features/video-stack.md)
