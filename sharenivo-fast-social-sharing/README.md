# ShareNivo 2.2.0

ShareNivo is a privacy-first WordPress social sharing and follow plugin. It provides flexible placements through original ShareNivo code and design, without social SDKs, analytics beacons, remote assets, or share-count requests.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer

## Frontend design

ShareNivo registers one local CSS file and one dependency-free local JavaScript file. They are enqueued only when automatic output, a ShareNivo block/shortcode, or configured follow profiles can appear.

The default palette is:

- Deep plum: `#3a1f4f`
- Vivid coral: `#ff5a4f`
- White icon: `#ffffff`

Network, ShareNivo brand, and minimal monochrome color systems are available. Motion respects `prefers-reduced-motion`.

## Supported sharing options

Facebook, X, LinkedIn, WhatsApp, Pinterest, Threads, Bluesky, Telegram, Reddit, Email, and Copy Link.

ShareNivo builds browser sharing links locally. It does not fetch share counts or connect to network APIs in the background.

## Placements and triggers

Automatic placements include:

- Floating desktop rail
- Inline buttons above, below, or around content
- Mobile sticky bar with compact/full-width and left/center/right layout controls
- Accessible popup
- Corner fly-in
- Eligible-image share controls

The floating rail can use its own shape, color, hover treatment, button limit, and mobile edge position. Image controls support minimum width and height, four local overlay positions, opt-out classes such as no-pin, and selectable Pinterest image/description sources.

Popup and fly-in triggers include delay, scroll percentage, bottom of content, inactivity, desktop exit intent, comment return, and WooCommerce order confirmation. Session/day/week frequency state is stored in the browser only.

## Manual integrations

The share shortcode also accepts an explicit post_id and comma-separated networks, for example: sharenivo_share post_id="123" networks="facebook,x,pinterest". The Click-to-Share Quote block and sharenivo_quote shortcode provide four original local quote styles.

Shortcodes:

```text
[sharenivo_share]
[sharenivo_follow]
```

Theme actions:

```php
do_action( 'sharenivo_display_buttons', get_the_ID(), 'inline' );
do_action( 'sharenivo_display_follow' );
```

Blocks:

- ShareNivo Share Buttons
- ShareNivo Follow Links
- ShareNivo Click-to-Share Quote

Widget:

- ShareNivo Follow

## Developer filters

Runtime settings:

```php
add_filter( 'sharenivo_settings', function ( $settings ) {
	$settings['style']['show_labels'] = true;
	return $settings;
} );
```

Remove a built-in network or adjust its label/color:

```php
add_filter( 'sharenivo_networks', function ( $networks ) {
	unset( $networks['reddit'] );
	return $networks;
} );
```

Filter final button markup:

```php
add_filter( 'sharenivo_button_html', function ( $html, $network, $share_url, $settings ) {
	return $html;
}, 10, 4 );
```

Only built-in network keys are accepted. This keeps URL generation, sanitization, and the zero-SDK performance promise deterministic.

## Upgrade compatibility

Version 2.0 migrates 1.x options into a nested, allowlisted settings schema while preserving supported ShareNivo and ShareNova integrations. Version 2.2 adds new fields through the same allowlisted schema without changing existing network keys or saved options.

## Compatibility

The 2.2.0 release is tested up to WordPress 7.1 and requires WordPress 6.0 or newer and PHP 7.4 or newer. ShareNivo uses WordPress core APIs for settings, blocks, widgets, shortcodes, metadata, and asset loading. It makes no automatic external HTTP requests.

## Privacy and external services

ShareNivo does not collect analytics, send visitor data, load social SDKs, fetch share counts, or download remote assets. External social networks are contacted only when a visitor deliberately clicks a sharing or follow link. The destination network's terms and privacy policy then apply.
