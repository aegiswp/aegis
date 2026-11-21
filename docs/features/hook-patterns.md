# Hook Patterns (Theme Injection Hooks)

The Aegis **framework** fires injection hooks at template part and post content boundaries. These hooks allow snippets, custom code, and (with Pro) hook patterns to inject content without editing template files.

## Framework-Fired Hooks

These hooks are fired automatically by `vendor/aegis/framework` CoreBlocks:

### Template Part Hooks

For each template part slug (e.g. `header`, `footer`), the framework wraps output with:

| Hook | Fires |
|------|-------|
| `aegis_before_{slug}` | Before the template part renders |
| `aegis_after_{slug}` | After the template part renders |

Examples: `aegis_before_header`, `aegis_after_footer`.

### Content Hooks

| Hook | Fires |
|------|-------|
| `aegis_before_content` | Before the main post content block output |
| `aegis_after_content` | After the main post content block output |

## Integration-Bridged Hooks

When the **Aegis Dash** integration is enabled, it bridges extra hooks from third-party plugins. They appear in the plugin's injection catalog, but the theme alone does not fire them.

| Plugin | Before hook | After hook | Fires on |
|--------|-------------|------------|----------|
| Fluent Forms | `aegis_before_fluentform` | `aegis_after_fluentform` | The form output |
| Gravity Forms | `aegis_before_gform` | `aegis_after_gform` | `gform_get_form_filter` |
| Ninja Forms | `aegis_before_nf_form` | `aegis_after_nf_form` | `ninja_forms_before_form_display` and `ninja_forms_after_form_display` |
| LearnDash course | `aegis_before_learndash_course` | `aegis_after_learndash_course` | A course page |
| LearnDash lesson | `aegis_before_learndash_lesson` | `aegis_after_learndash_lesson` | A lesson page |
| LearnDash topic | `aegis_before_learndash_topic` | `aegis_after_learndash_topic` | A topic page |
| LearnDash quiz | `aegis_before_learndash_quiz` | `aegis_after_learndash_quiz` | A quiz page |
| bbPress forums | `aegis_before_bbpress_forum` | `aegis_after_bbpress_forum` | The forums loop |
| bbPress topic | `aegis_before_bbpress_topic` | `aegis_after_bbpress_topic` | A single topic |
| Co-Authors Plus | `aegis_before_post_author` | `aegis_after_post_author` | `core/post-author` and `co-authors/block` |

Other bridged locations cover WooCommerce checkout and cart, EDD, and the map block.

### Hooks the Theme Fires Itself

The two LearnDash Focus Mode hooks are different: the theme framework fires them (`Integrations/LearnDash.php`), with or without the plugin.

| Hook | Argument |
|------|----------|
| `aegis_learndash_focus_header` | `int $course_id` |
| `aegis_learndash_focus_footer` | `int $course_id` |


