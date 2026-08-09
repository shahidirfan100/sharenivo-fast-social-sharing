# ShareNivo

Current version: **1.4.1**

A lightweight, performant WordPress plugin for adding beautiful social sharing buttons to your website. GDPR compliant with responsive design.

## Features

- ✅ **Lightweight & Fast** - Minimal footprint, no external dependencies
- ✅ **Privacy First** - GDPR compliant, no tracking or cookies
- ✅ **Responsive Design** - Floating sidebar on desktop, sticky bottom bar on mobile
- ✅ **10 Social Networks** - Facebook, X, LinkedIn, WhatsApp, Pinterest, Threads, Bluesky, Telegram, Reddit, Email
- ✅ **Customizable** - Multiple button shapes, sizes, colors, and positions
- ✅ **Animation Modes** - Lift, Pulse, Spin, Bounce, and Flip hover styles
- ✅ **Share Counts** - Display total shares or per-network counts with caching
- ✅ **Gutenberg Block** - Native block with live preview in the editor
- ✅ **CPT Auto Detection** - Automatically includes newly registered public post types
- ✅ **Smart More Button** - Show first 4 buttons and reveal extra networks on demand
- ✅ **Easy to Use** - Simple settings page with tabbed interface
- ✅ **Developer Friendly** - Clean code, hooks, filters, and shortcode support
- ✅ **Translation Ready** - Full i18n support

## Installation

### From WordPress Admin

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

### Manual Installation

1. Upload the `sharenivo` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Social Share to configure

## Configuration

### General Settings

- **Enable Plugin** - Turn the plugin on/off globally
- **Social Networks** - Select which networks to display

### Display Settings

- **Post Types** - Choose which post types show share buttons (Posts, Pages, Custom Post Types)
- **Desktop Position** - Floating Left, Floating Right, Inline Top, or Inline Bottom
- **Mobile Position** - Sticky Bottom Bar, Inline, or Hidden

### Style Settings

- **Button Shape** - Circle, Square, Rounded Rectangle, or Landscape Rectangle
- **Button Size** - Small (40px), Medium (50px), or Large (60px)
- **Color Scheme** - Brand Colors (official network colors) or Custom Colors
- **Custom Colors** - Set your own background, icon, and hover colors
- **Spacing** - Adjust gap between buttons and margins
- **Animations** - Enable/disable hover effects
- **Hover Animation Type** - Lift, Pulse, Spin, Bounce, Flip

### Advanced Settings

- **Custom CSS** - Add your own CSS to override default styles
- **Share Count Display** - Show total or per-network counts
- **Cache TTL** - Control count cache duration
- **SharedCount API Key** - Optional API key for broader count coverage
- **Shortcode** - Use `[sharenivo_share]` to manually place buttons anywhere

## Usage

### Automatic Display

Once configured, share buttons will automatically appear on the selected post types in the chosen position.

### Manual Placement with Shortcode

Add the shortcode anywhere in your content:

```
[sharenivo_share]
```

### Programmatic Display

Use the action hook in your theme:

```php
do_action( 'sharenivo_display_buttons' );
```

### Gutenberg Block

Use the `Social Share Buttons` block in the block editor for live-preview rendering.

## Customization

### Filters

**Modify available networks:**
```php
add_filter( 'sharenivo_networks', function( $networks ) {
    // Add or remove networks
    return $networks;
});
```

**Customize button HTML:**
```php
add_filter( 'sharenivo_button_html', function( $html, $network, $share_url, $settings ) {
    // Modify button HTML
    return $html;
}, 10, 4);
```

**Modify settings:**
```php
add_filter( 'sharenivo_settings', function( $settings ) {
    // Change default settings
    return $settings;
});
```

### Upgrade compatibility

Sites upgrading from ShareNova keep their saved settings automatically. The legacy `[sharenova_share]` shortcode, `sharenova_display_buttons` action, `sharenova_*` filters, and `wssp/share-buttons` block remain available while new integrations should use the ShareNivo names above.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile Safari (iOS)
- Mobile Chrome (Android)

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher (PHP 8.0+ recommended)

## Performance

- **Page Load Impact:** < 100ms
- **CSS File Size:** ~5KB
- **JS File Size:** ~2KB
- **No External Assets:** All plugin CSS/JS assets loaded locally
- **SVG Icons:** Crisp at any size, minimal file size

## Privacy & GDPR

This plugin is GDPR compliant by design:

- ✅ No cookies set
- ✅ No user tracking
- ✅ No external tracking services
- ✅ No data collection
- ✅ Share links open directly to social networks

## Frequently Asked Questions

### How do I change the button position?

Go to Settings → Social Share → Display tab and select your preferred position for desktop and mobile.

### Can I customize the button colors?

Yes! Go to Settings → Social Share → Style tab, select "Custom Colors" and use the color pickers.

### How do I add buttons to custom post types?

Go to Settings → Social Share → Display tab and check the custom post types you want to include.

### Can I use the plugin on WooCommerce products?

Yes! Just enable the "Products" post type in the Display settings.

### How do I hide buttons on specific pages?

Currently, you can control by post type. For more granular control, use the shortcode method and disable automatic display.

### Does this plugin slow down my website?

No. The frontend assets are loaded only when sharing is enabled. Optional share counts use cached requests to the providers documented in `readme.txt`.

## Changelog

### 1.4.1 (2026-08-09)

- Refreshed the admin dashboard with ShareNivo’s original midnight, indigo, and coral workspace design.
- Fixed Plugin Check compatibility issues for line endings, current WordPress metadata, and discouraged translation loading.
- Preserved all legacy ShareNova settings, hooks, shortcodes, filters, and block content during the update.

### 1.4.0 (2026-08-09)
- Rebranded the plugin as ShareNivo with a new plugin slug, namespace, text domain, and Gutenberg block name.
- Migrated existing ShareNova settings and retained legacy hooks, shortcode, filters, and block rendering for safe upgrades.
- Refined the admin dashboard with a local system font stack, safer notices, better tab handling, and corrected network chip toggles.
- Removed the remote admin font request to keep the dashboard lightweight and privacy-friendly.
- Updated all plugin metadata, documentation, and asset references for the new brand.

### 1.3.1 (2026-02-20)
- Fixed fatal error on activation by correcting plugin constant names
- Fixed X (Twitter) and Bluesky SVG icon rendering issues
- Added Left, Center, and Right alignment options for inline positioned buttons
- Adjusted hook priorities so inline bottom buttons display correctly before related post widgets

### 1.3.0 (2026-02-20)
- Completely redesigned admin settings dashboard with a modern UI
- Premium dark-accent and glass card layout for improved user experience
- Upgraded CSS and JavaScript framework for smooth interactions and animations

### 1.2.0 (2026-02-12)
- Replaced portrait rectangle with landscape rectangle button shape
- Added compact icon-only More button with auto-hide at 4 or fewer networks
- Added setting to show/hide More button behavior when more than 4 networks are selected
- Added admin network order control with up/down buttons
- Added frontend toggle script for More panel

### 1.1.0 (2026-02-12)
- Added Threads, Bluesky, Telegram, and Reddit networks
- Added share count display (total/per-network) with transient caching
- Added native Gutenberg block with live preview
- Added multiple hover animation types
- Added auto-detect support for newly registered public custom post types
- Added optional SharedCount API integration

### 1.0.1 (2026-02-12)
- Added WordPress.org-ready `readme.txt` and license file
- Implemented frontend usage of spacing, color, and animation settings
- Added `sharenivo_display_buttons` action and documented filters in runtime
- Improved settings sanitization with strict whitelisting and `wp_unslash()`
- Fixed textdomain loading path and mobile sticky CSS overlay issue
- Removed full cache flush on uninstall

### 1.0.0 (2026-02-12)
- Initial release
- 6 social networks supported
- Responsive design (desktop floating, mobile sticky)
- Customizable button styles
- Settings page with tabs
- Shortcode support
- Translation ready

## Support

For support, feature requests, or bug reports, please visit:
- WordPress.org support forums

## Credits

Developed by ShareNivo Team

## License

GPL-2.0-or-later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
