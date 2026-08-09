=== ShareNivo - Fast Social Sharing ===
Contributors: ShareNivo
Tags: social share, share buttons, social media, gdpr, shortcode
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight social sharing buttons for WordPress with responsive layouts, privacy-first design, and flexible styling options.

== Description ==

ShareNivo adds clean, fast social sharing buttons to posts and pages. It uses locally loaded CSS and JavaScript, has no third-party tracking scripts, and keeps share-count requests disabled unless you explicitly enable them.

Features:
* Lightweight frontend CSS and JavaScript with no third-party tracking scripts.
* Privacy-friendly sharing links (no tracking cookies).
* Desktop and mobile display positions.
* Custom shape, size, spacing, colors, and multiple hover animations.
* Shortcode support: `[sharenivo_share]`.
* Action support: `do_action( 'sharenivo_display_buttons' )`.
* Filters for developers: `sharenivo_settings`, `sharenivo_networks`, `sharenivo_button_html`.
* Native Gutenberg block with live preview.
* Share count display with cached fetches.
* Extended network support: Threads, Bluesky, Telegram, Reddit.
* Auto-detects newly registered public custom post types.

Existing ShareNova settings, hooks, shortcodes, filters, and block content remain supported during the rebrand migration.

== External services ==

This plugin connects to external APIs to fetch public share counts for your content.

* **Reddit API:** When the Reddit network is enabled, the plugin connects to `https://www.reddit.com/api/info.json` to fetch the social share count. It sends the current page's URL. [Reddit Privacy Policy](https://www.reddit.com/policies/privacy-policy)
* **SharedCount API:** When enabled in the advanced settings, the plugin connects to `https://api.sharedcount.com/v1.0/` for multi-network share counts. It sends the current page URL and the API Key you provide. [SharedCount Privacy Policy](https://www.sharedcount.com/privacy)

== Installation ==

1. Upload the `sharenivo` folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen in WordPress.
3. Go to **Settings > Social Share** and configure options.

== Frequently Asked Questions ==

= Does this plugin load external scripts? =

No third-party scripts are loaded. Share links open the selected social network URL directly. If share counts are enabled, the documented count providers are contacted and responses are cached.

= How can I place buttons manually? =

Use `[sharenivo_share]` in post content or call `do_action( 'sharenivo_display_buttons' )` in templates.

= Can I customize styling? =

Yes. Use built-in style settings and optional custom CSS.

== Changelog ==

= 1.4.1 =
* Refined the ShareNivo admin dashboard with an original branded workspace layout, clearer hierarchy, responsive navigation, and focused controls.
* Fixed Plugin Check issues for line endings, current WordPress compatibility metadata, and discouraged translation loading.
* Kept the lowercase `sharenivo` text domain and all legacy ShareNova integrations intact for safe upgrades.

= 1.4.0 =
* Rebranded the plugin as ShareNivo with updated metadata, namespace, text domain, assets, and documentation.
* Added migration for existing ShareNova settings and preserved legacy hooks, shortcode, filters, and block rendering.
* Refined the admin dashboard with a local system font stack, safer AJAX notices, improved tab handling, and corrected network chip interactions.
* Removed the remote admin font request to keep the dashboard lightweight and privacy-friendly.

= 1.3.1 =
* Fixed fatal error on activation by correcting plugin constant names
* Fixed X (Twitter) and Bluesky SVG icon rendering issues
* Added Left, Center, and Right alignment options for inline positioned buttons
* Adjusted hook priorities so inline bottom buttons display correctly before related post widgets

= 1.3.0 =
* Completely redesigned admin settings dashboard with a modern UI.
* Premium dark-accent and glass card layout for improved user experience.
* Upgraded CSS and JavaScript framework for smooth interactions and animations.

= 1.2.0 =
* Replaced portrait rectangle with landscape rectangle button shape option.
* Added compact icon-only More button with automatic hide at 4 or fewer networks.
* Added setting to show/hide More button behavior when more than 4 networks are selected.
* Added admin network ordering with up/down controls.
* Added frontend toggle script for More networks panel.

= 1.1.0 =
* Added native Gutenberg block with live preview.
* Added share count display (total/per-network) with transient caching.
* Added Threads, Bluesky, Telegram, and Reddit share buttons.
* Added multiple hover animation styles.
* Added automatic detection of newly registered public custom post types.
* Added optional SharedCount API integration for broader count coverage.

= 1.0.1 =
* Added WordPress.org-ready readme and license packaging.
* Applied saved style settings (spacing, custom colors, animation toggle) on frontend.
* Added runtime support for documented action/filter APIs.
* Hardened settings sanitization and nonce handling.
* Improved i18n textdomain loading and mobile sticky CSS behavior.
* Removed full object cache flush on uninstall.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.4.1 =
Recommended update for the refreshed admin experience and WordPress Plugin Check compatibility fixes.

= 1.4.0 =
ShareNivo is a compatibility-preserving rebrand with a refined, lighter admin experience. Existing ShareNova settings and integrations are migrated automatically.

= 1.3.1 =
Important bug fixes resolving a fatal activation conflict and several styling/alignment issues.

= 1.3.0 =
Major admin dashboard redesign with a fully modern user experience. Update recommended for better configuration workflow.

= 1.2.0 =
Recommended update for landscape shape, compact More button controls, and network ordering.

= 1.1.0 =
Recommended update for new share/count/block features and improved CPT handling.
