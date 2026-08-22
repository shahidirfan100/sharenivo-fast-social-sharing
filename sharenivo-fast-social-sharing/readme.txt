=== ShareNivo - Fast Social Sharing ===
Contributors: shahidirfan100
Tags: social share, share buttons, social media, privacy, lightweight
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, privacy-first social sharing and follow tools with flexible placements, smart local triggers, and zero automatic external requests.

== Description ==

ShareNivo adds polished social sharing without loading social SDKs, remote fonts, tracking pixels, analytics beacons, or share-count APIs. Its frontend uses one local stylesheet and one small local script, loaded only when ShareNivo can produce output.

= What makes ShareNivo different? =

ShareNivo combines six placements and behavior-based prompts in a local, privacy-first engine. Delay, reading progress, inactivity, comment return, exit intent, and WooCommerce confirmation triggers run in the browser without analytics collection or background calls to social networks. Its compact and full-width mobile bars preserve configured shapes and use equal responsive columns without forcing page-level horizontal scrolling.

= ShareNivo 2.1 features =

* Active browser-sharing options: Facebook, X, LinkedIn, WhatsApp, Pinterest, Threads, Bluesky, Telegram, Reddit, Email, and Copy Link.
* Floating desktop rail, inline buttons, mobile sticky bar, popup, fly-in, and image sharing.
* Local triggers for delay, scroll depth, reading completion, inactivity, desktop exit intent, comments, and WooCommerce order confirmation.
* Frequency controls stored in the visitor's browser with no server call.
* Follow profiles for active social and creator platforms.
* Original ShareNivo deep-plum and vivid-coral design system with network, brand, and minimal color modes.
* Shape, size, spacing, labels, button order, eight hover choices, and entrance controls.
* Accessible keyboard navigation, visible focus, dialog semantics, live copy feedback, and reduced-motion support.
* Per-post placement overrides.
* Share and Follow blocks, shortcodes, widgets, actions, and filters.
* Validated JSON import/export.
* Upgrade migration for existing ShareNivo and ShareNova settings and integrations.

Share counts were removed in 2.0. They require external requests, add latency, and are increasingly unavailable or inconsistent across social platforms.

== External services ==

ShareNivo makes no automatic requests to external services and does not send analytics or visitor data. When a visitor deliberately clicks a share button, their browser opens that network's official sharing page and sends the current page URL and, where supported, its title or selected image. That interaction is governed by the selected network's terms and privacy policy.

Supported destinations and their policies:

* Facebook: [Terms](https://www.facebook.com/terms.php), [Privacy Policy](https://www.facebook.com/privacy/policy/)
* X: [Terms](https://x.com/en/tos), [Privacy Policy](https://x.com/en/privacy)
* LinkedIn: [User Agreement](https://www.linkedin.com/legal/user-agreement), [Privacy Policy](https://www.linkedin.com/legal/privacy-policy)
* WhatsApp: [Terms](https://www.whatsapp.com/legal/terms-of-service), [Privacy Policy](https://www.whatsapp.com/legal/privacy-policy)
* Pinterest: [Terms](https://policy.pinterest.com/terms-of-service), [Privacy Policy](https://policy.pinterest.com/privacy-policy)
* Threads: [Terms](https://help.instagram.com/769983657850450), [Supplemental Privacy Policy](https://help.instagram.com/515230437301944)
* Bluesky: [Terms](https://bsky.social/about/support/tos), [Privacy Policy](https://bsky.social/about/support/privacy-policy)
* Telegram: [Terms](https://telegram.org/tos), [Privacy Policy](https://telegram.org/privacy)
* Reddit: [User Agreement](https://redditinc.com/policies/user-agreement), [Privacy Policy](https://redditinc.com/policies/privacy-policy)

Email uses the visitor's configured mail application. Copy Link stays in the browser.

== Installation ==

1. Upload the `sharenivo-fast-social-sharing` folder to `/wp-content/plugins/`, or install the ZIP from Plugins > Add New > Upload Plugin.
2. Activate ShareNivo from the Plugins screen.
3. Open Settings > ShareNivo.
4. Select networks, placements, and design options, then save.

== Screenshots ==

1. Overview dashboard with active networks, enabled placements, tracking-request status, and the master switch.
2. Current sharing-network selector and deterministic display-order controls.
3. Placement controls for floating, inline, mobile sticky, popup, fly-in, and image sharing.
4. Button design, color, motion, labels, spacing, and live-preview controls.
5. Responsive inline and full-width mobile sticky buttons with equal columns and no horizontal scrolling.

== Frequently Asked Questions ==

= Does ShareNivo make frontend API requests? =

No. ShareNivo does not fetch counts, load social SDKs, download fonts, or send analytics. A network is contacted only after a visitor clicks its share link.

= Why are share counts gone? =

Most networks no longer provide reliable public counters. Fetching counts adds latency, caching work, failure modes, and privacy disclosures. ShareNivo prioritizes speed and predictable output.

= How can I place sharing manually? =

Use `[sharenivo_share]`, add the ShareNivo Share Buttons block, or call `do_action( 'sharenivo_display_buttons' )` in a theme template.

= How can I display follow links? =

Configure profiles on the Follow tab, then use `[sharenivo_follow]`, the ShareNivo Follow Links block, the ShareNivo Follow widget, or `do_action( 'sharenivo_display_follow' )`.

= Does it support existing ShareNova content? =

Yes. Legacy settings, the `[sharenova_share]` shortcode, the `sharenova_display_buttons` action, compatibility filters, and the original block name continue to work.

= Does ShareNivo respect accessibility preferences? =

Yes. Controls have accessible names and focus states, popup focus is contained, Escape closes transient interfaces, status messages use live regions, and motion is minimized when the operating system requests reduced motion.

== Changelog ==

= 2.1.1 =
* Removed the custom CSS editor, storage path, configuration import key, and frontend output to follow WordPress.org directory requirements.
* Added a settings-schema migration that retains supported 2.x options while removing unsupported top-level values.
* Verified the contributor metadata and confirmed that no obsolete share-count service references remain.
* Clarified ShareNivo's local trigger engine and responsive mobile-bar focus.

= 2.1.0 =
* Added Glow, Tilt, Pulse, and Icon twist hover effects alongside Lift, Grow, Icon slide, and None.
* Added live dashboard previews for every hover choice.
* Kept all hover effects CSS-only with no extra requests, libraries, or frontend JavaScript.
* Preserved reduced-motion support for animated effects.

= 2.0.0 =
* Rebuilt the frontend as a zero-request, privacy-first placement engine.
* Removed share counts, count caches, API keys, count endpoints, and all related admin controls.
* Added floating, inline, mobile sticky, popup, fly-in, and image-sharing locations.
* Added delay, scroll, bottom-of-content, inactivity, exit-intent, comment, and purchase triggers using local browser logic.
* Added session, daily, and weekly prompt frequency controls without tracking requests.
* Added Share and Follow blocks, follow shortcode, follow widget, and follow action.
* Added per-post location overrides and validated settings portability.
* Added Threads, Bluesky, and current network-browser share flows while removing obsolete integrations.
* Introduced the original ShareNivo plum/coral dashboard and button design system.
* Preserved selected button shapes and labels across desktop, tablet, and mobile layouts.
* Standardized labeled button dimensions so short and long network names render at equal sizes.
* Added compact/full-width mobile sticky layouts with left, center, and right alignment controls.
* Made full-width sticky buttons use responsive equal columns without horizontal scrolling or oversized empty gaps.
* Refined the dashboard and default brand treatment with a prominent deep-plum and vivid-coral palette.
* Added conditional assets, keyboard-safe dialogs, live copy feedback, and reduced-motion support.
* Preserved existing ShareNivo and ShareNova settings and developer integrations.

= 1.4.1 =
* Refined the ShareNivo admin dashboard and WordPress.org metadata.
* Preserved ShareNova upgrade compatibility.

== Upgrade Notice ==

= 2.1.1 =
Compliance update that removes arbitrary CSS input while preserving supported ShareNivo settings.

= 2.1.0 =
Adds four lightweight hover effects and matching live previews without increasing frontend requests.

= 2.0.0 =
Major privacy and performance release. Share-count requests and related settings are removed; existing placement, network, styling, shortcode, action, filter, and block integrations are migrated where possible.
