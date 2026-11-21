# Custom Blocks

The Aegis blocks are provided by the **Aegis Dash** plugin. This page explains where they live now; each block is documented in the Aegis Dash set.

## Where the blocks live

Since Aegis Dash 1.1.0, all Aegis blocks come from Aegis Dash:

| Block | Namespace | Documentation |
|-------|-----------|---------------|
| Countdown | `aegis/countdown` | [Countdown](../../../../plugins/aegis-dash/docs/blocks/countdown.md) |
| Slider and Slide | `aegis/slider`, `aegis/slide` | [Slider and Slide](../../../../plugins/aegis-dash/docs/blocks/slider.md) |
| Toggle and Toggle Content | `aegis/toggle`, `aegis/toggle-content` | [Toggle](../../../../plugins/aegis-dash/docs/blocks/toggle.md) |
| Related Posts | `aegis/related-posts` | [Related Posts](../../../../plugins/aegis-dash/docs/blocks/related-posts.md) |
| Map | `aegis/map` | [Map](../../../../plugins/aegis-dash/docs/blocks/map.md) |
| Modal | `aegis/modal` | [Modals](../../../../plugins/aegis-dash/docs/blocks/modals.md) |

The first four blocks used to be theme blocks. Their names, markup and CSS classes did not change, so saved content keeps working.

## What the theme still does

- **Fallback copy.** Aegis theme 1.1.0 still contains the Countdown, Slider, Slide, Toggle, Toggle Content and Related Posts blocks. It registers one only when Aegis Dash is not already providing it. A later theme release removes these copies, so install Aegis Dash now.
- **Install prompt.** On **Appearance → Themes** the theme offers to install and activate Aegis Dash. See [[installation]].
- **Update hold.** A theme release that depends on a newer Aegis Dash is not offered until Aegis Dash is installed, active and new enough. See [[updating]].
- **Styling.** Front-end styling for the blocks comes from the plugin; the theme provides the colour, spacing and typography tokens they use.

## Block feature settings

Turn block features on or off at **Aegis → Blocks** (Aegis Dash). Settings start off on a fresh install. See [Custom Blocks in Aegis Dash](../../../../plugins/aegis-dash/docs/blocks/custom-blocks.md) and [Block variations](../../../../plugins/aegis-dash/docs/blocks/block-variations.md). Aegis Pro adds more extras, such as tabs and Compare; see [Pro blocks](../../../../plugins/aegis-pro/docs/features/pro-blocks.md).

## Video

Aegis does not register `aegis/video`. Video uses WordPress **`core/video`**: the theme framework only adds responsive-embed support, and the Aegis Video add-on provides the player. See [[core-video]].

## Next Steps

- [[enhanced-core-blocks]] — Framework core block enhancements
- [[block-variations]] — Framework block variations
- [[block-patterns]] — Pre-built layouts using these blocks
