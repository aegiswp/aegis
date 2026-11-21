# Updating

This page explains how to update the Aegis theme to the latest version safely and efficiently.

## Before Updating

Before performing any update, take the following precautions:

1. **Back up your site** — Create a full backup of your database and files.
2. **Check the changelog** — Review the release notes shown with the update on **Dashboard → Updates**, or the theme's `CHANGELOG.md`, for breaking changes.
3. **Test in staging** — If possible, test the update on a staging environment first.
4. **Check plugin compatibility** — Ensure your Aegis plugins (Aegis Dash, Aegis Pro) are compatible with the new theme version.

## Block Migration Notes

### Aegis blocks move to Aegis Dash

Slider, Slide, Toggle, Toggle Content, Countdown and Related Posts are provided by **Aegis Dash 1.1.0 or newer**. Block names, markup and CSS classes are unchanged, so saved content keeps working.

1. Install and activate Aegis Dash 1.1.0 or newer (the theme's notice on **Appearance → Themes** installs it).
2. The first time it loads, Aegis Dash looks at your content and turns on the settings of every one of these blocks that a post, template or template part already uses. Blocks nothing uses stay off until you turn them on at **Aegis → Blocks**.
3. Extras marked **Pro** stay off until Aegis Pro is active, then you can turn them on.
4. Aegis theme 1.1.0 still contains its own copy of each block and registers it only while Aegis Dash is not providing it. A later theme release removes those copies, so do not wait.

### Aegis (1.0.x) becomes Aegis Dash

The plugin that shipped as **Aegis** (folder `aegis`) is now **Aegis Dash** (folder `aegis-dash`). Aegis Dash cannot arrive as an update to the old plugin, because the folder name changed.

1. Install Aegis Dash from the theme's notice or upload `aegis-dash.zip`.
2. Activate it. It deactivates the old Aegis plugin for you and keeps every setting.
3. Delete the old Aegis plugin. While Aegis Dash is active, deleting it does not remove your Aegis Dash settings.


### `aegis/video` → `core/video`

The **`aegis/video`** custom block was removed. Existing content still using that block should be migrated to **`core/video`** before or after updating.

Review affected pages in the Site Editor after replacing them.

### Related Posts

The **`core/query` Related Posts** block variation was removed. Replace those blocks with the **`aegis/related-posts`** block from Aegis Dash, or use **`aegisProRelatedPosts`** on Query Loop with [Aegis Pro (coming soon)](../../../../plugins/aegis-pro/docs/features/query-loop-pro.md) for advanced related-post queries.

Without Aegis Dash, the theme's own copy of the block and its `blog-related-posts-*` patterns are registered. With Aegis Dash active, the plugin provides the block and its patterns (`aegis/related-posts-*`). There is no separate Related Posts switch: the block is registered once at least one setting is on at **Aegis → Blocks → Related Posts**. See [Related Posts Block](../../../../plugins/aegis-dash/docs/blocks/related-posts.md).

### Image Icon → `core/icon`

The **Image Icon** variation (`core/image` with `is-style-icon`) was removed. Insert the WordPress **Icon** block instead. IDs look like `core/home` or `social/facebook`.

Migrate leftover Image icons and legacy `aegis/{set}/{name}` Icon IDs:

```bash
wp aegis migrate-icons
# WordPress Studio:
studio wp aegis migrate-icons
# Preview only:
studio wp aegis migrate-icons --dry-run
```

The command updates all public post types and `widget_block` widgets. Review headers and patterns in the Site Editor afterward. See [SVG Icons](../features/svg-icons.md).

## Update Methods

### Method 1: Automatic Updates From GitHub (Recommended)

Since 1.0.1, Aegis updates itself from GitHub through the normal WordPress update screens. No plugin or account is needed.

![Dashboard → Updates listing an available Aegis theme update with the installed and new version numbers and the Update Themes button.](../screenshots/theme-update.png)

*Aegis updates appear in Dashboard → Updates like any other theme.*

- `style.css` declares `Update URI: https://github.com/aegiswp/aegis`, so WordPress does not look for Aegis on WordPress.org.
- The theme's updater (`src/Admin/GitHubUpdater.php`) reads the latest published release of `aegiswp/aegis`. Drafts and pre-releases are skipped.
- If the release notes contain a line such as `Requires Aegis Dash: 1.1.0`, the release is held back until Aegis Dash is installed, active and at least that version. An admin notice on the Themes, Updates and Plugins screens explains the wait.
- When that release is newer than the installed version, WordPress shows the usual **Update now** link under **Appearance → Themes** and in **Dashboard → Updates**. The release notes open from the update link.
- The update installs the release asset `aegis.zip`, which contains a single `aegis/` folder, so the theme folder keeps its name.
- The auto-update toggle on the theme details screen works as for any other theme.
- Release data is cached for 6 hours (30 minutes after an error) and cleared after any update.

To update:

1. Go to **Dashboard → Updates** (or **Appearance → Themes**).
2. Click **Update now** for Aegis.

Developers can point the updater at a fork or mirror with the `aegis_theme_update_repository` filter. See [Hooks and Filters](../reference/hooks-and-filters.md#theme-filters).

```php
add_filter( 'aegis_theme_update_repository', fn() => 'my-org/aegis-fork' );
```

The repository must publish releases with an `aegis.zip` asset.

### Method 2: Manual Update via Upload

1. Download `aegis.zip` from the latest Aegis release.
2. Navigate to **Appearance → Themes**.
3. Click **Add New Theme** and then **Upload Theme**.
4. Select the downloaded `.zip` file.
5. Click **Install Now**.
6. When prompted, choose **Replace current with uploaded** to overwrite the existing installation.
7. Click **Activate** if the theme was deactivated during the process.

### Method 3: Manual Update via FTP

1. Download and extract `aegis.zip` from the latest release.
2. Connect to your server via FTP.
3. Navigate to `wp-content/themes/`.
4. Rename the existing `aegis` folder to `aegis-backup` (as a safety measure).
5. Upload the new `aegis` folder.
6. Verify the site works correctly.
7. Delete the `aegis-backup` folder once confirmed.

### Method 4: Using Git (Development)

If you installed Aegis via Git:

```bash
cd wp-content/themes/aegis
git fetch origin
git checkout main
git pull origin main
composer install --no-dev
npm install
npm run build
```

### Method 5: Using Composer

If you manage Aegis through Composer:

```bash
composer update aegiswp/aegis
```

## Updating Aegis plugins

When updating the theme, you should also check for updates to Aegis plugins:

| Plugin | Update Location |
|--------|----------------|
| Aegis Dash (free) | **Plugins → Installed Plugins**. Install it from the theme's admin notice, which downloads the latest Aegis Dash release |
| Aegis Pro | Coming soon |

Always update the theme and plugins together to ensure compatibility.

## Version Compatibility

Aegis follows semantic versioning (SemVer):

| Version Change | Meaning | Action Required |
|----------------|---------|-----------------|
| Patch (1.0.x) | Bug fixes, no breaking changes | Safe to update immediately |
| Minor (1.x.0) | New features, backwards compatible | Review new features, safe to update |
| Major (x.0.0) | Breaking changes possible | Read migration guide before updating |

## What Gets Preserved During Updates

The following customizations are preserved when you update:

- **Global Styles** — Colors, typography, and layout changes made in the Site Editor.
- **Template modifications** — Custom templates created in the Site Editor.
- **User-created patterns** — Patterns you have created yourself.
- **Database content** — Posts, pages, and media are never affected.
- **Plugin settings** — Aegis Dash configurations remain intact.

## What May Be Overwritten

The following are overwritten during an update:

- **Theme files** — All PHP, JSON, and asset files in the theme directory.
- **Default templates** — Original templates are replaced with updated versions.
- **Default patterns** — Bundled patterns are replaced with updated versions.

> [!NOTE]
> If you have modified theme files directly (not recommended), those changes will be lost during an update. Always use child themes or the Site Editor for customizations.

## Troubleshooting Updates

### Update Failed

If an update fails:

1. Check file permissions on the `wp-content/themes/` directory.
2. Ensure sufficient disk space is available.
3. Check the PHP error log for specific error messages.
4. Try the manual upload or FTP method as a fallback.
5. If **Update now** never appears, the site may not reach `api.github.com`. Check outgoing HTTP requests in **Tools → Site Health**.

### Site Broken After Update

If your site does not work correctly after updating:

1. Clear all caches (browser, plugin, server).
2. Deactivate and reactivate the theme.
3. Check **Tools → Site Health** for any reported issues.
4. If the issue persists, restore your backup and report the issue through [Getting Support](../troubleshooting/support.md).

### Rolling Back

To revert to a previous version:

1. Download the `aegis.zip` of the previous release.
2. Follow the manual update process (Method 2 or Method 3) using the older version file.

## Staying Informed

To be notified of new releases:

- New versions appear in **Dashboard → Updates** and on **Appearance → Themes**. Enable auto-updates for Aegis to install them automatically.
- Release news is published on the [Aegis documentation site](https://www.atmostfear-entertainment.com/docs/aegis/theme/aegis-theme/).

## Next Steps

- [[requirements]] — Verify your environment meets the latest requirements.
- [[common-issues]] — Solutions to common problems after updating.
