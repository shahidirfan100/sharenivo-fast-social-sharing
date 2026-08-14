<?php
/**
 * Social network registry.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the supported 2026 network registry and share URL builders.
 *
 * The registry intentionally contains no share-count endpoints, SDKs, API
 * clients, tracking pixels, or remote assets.
 */
class Networks {

	/**
	 * Get networks with supported browser sharing flows.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function get_share_networks() {
		$networks = array(
			'facebook'  => array( 'label' => __( 'Facebook', 'sharenivo-fast-social-sharing' ), 'color' => '#1877f2' ),
			'x'         => array( 'label' => __( 'X', 'sharenivo-fast-social-sharing' ), 'color' => '#111111' ),
			'linkedin'  => array( 'label' => __( 'LinkedIn', 'sharenivo-fast-social-sharing' ), 'color' => '#0a66c2' ),
			'whatsapp'  => array( 'label' => __( 'WhatsApp', 'sharenivo-fast-social-sharing' ), 'color' => '#25d366' ),
			'pinterest' => array( 'label' => __( 'Pinterest', 'sharenivo-fast-social-sharing' ), 'color' => '#e60023' ),
			'threads'   => array( 'label' => __( 'Threads', 'sharenivo-fast-social-sharing' ), 'color' => '#111111' ),
			'bluesky'   => array( 'label' => __( 'Bluesky', 'sharenivo-fast-social-sharing' ), 'color' => '#1185fe' ),
			'telegram'  => array( 'label' => __( 'Telegram', 'sharenivo-fast-social-sharing' ), 'color' => '#229ed9' ),
			'reddit'    => array( 'label' => __( 'Reddit', 'sharenivo-fast-social-sharing' ), 'color' => '#ff4500' ),
			'email'     => array( 'label' => __( 'Email', 'sharenivo-fast-social-sharing' ), 'color' => '#64748b' ),
			'copy'      => array( 'label' => __( 'Copy link', 'sharenivo-fast-social-sharing' ), 'color' => '#3a1f4f' ),
		);

		/**
		 * Filter the supported network registry.
		 *
		 * This compatibility filter can remove networks or adjust their local
		 * labels/colors. Share URL builders remain intentionally first-party.
		 *
		 * @param array $networks Network registry.
		 */
		$filtered = apply_filters( 'sharenivo_networks', $networks );
		$filtered = apply_filters( 'sharenova_networks', $filtered );
		if ( ! is_array( $filtered ) ) {
			return $networks;
		}

		$clean = array();
		foreach ( $networks as $key => $defaults ) {
			if ( ! isset( $filtered[ $key ] ) || ! is_array( $filtered[ $key ] ) ) {
				continue;
			}
			$label = isset( $filtered[ $key ]['label'] ) ? sanitize_text_field( $filtered[ $key ]['label'] ) : $defaults['label'];
			$color = isset( $filtered[ $key ]['color'] ) ? sanitize_hex_color( $filtered[ $key ]['color'] ) : $defaults['color'];
			$clean[ $key ] = array(
				'label' => $label ? $label : $defaults['label'],
				'color' => $color ? $color : $defaults['color'],
			);
		}
		return $clean;
	}

	/**
	 * Get supported follow-profile networks.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function get_follow_networks() {
		$networks = self::get_share_networks();
		unset( $networks['email'], $networks['copy'], $networks['whatsapp'] );

		$networks['instagram'] = array( 'label' => __( 'Instagram', 'sharenivo-fast-social-sharing' ), 'color' => '#e1306c' );
		$networks['youtube']   = array( 'label' => __( 'YouTube', 'sharenivo-fast-social-sharing' ), 'color' => '#ff0000' );
		$networks['tiktok']    = array( 'label' => __( 'TikTok', 'sharenivo-fast-social-sharing' ), 'color' => '#111111' );
		$networks['github']    = array( 'label' => __( 'GitHub', 'sharenivo-fast-social-sharing' ), 'color' => '#24292f' );

		return $networks;
	}

	/**
	 * Return a filtered, ordered list of valid network keys.
	 *
	 * @param array $networks Submitted network keys.
	 * @param array $allowed  Allowed registry.
	 * @return array
	 */
	public static function sanitize_network_list( $networks, $allowed = array() ) {
		if ( empty( $allowed ) ) {
			$allowed = self::get_share_networks();
		}

		if ( ! is_array( $networks ) ) {
			return array();
		}

		$clean = array();
		foreach ( $networks as $network ) {
			$network = sanitize_key( $network );
			if ( isset( $allowed[ $network ] ) && ! in_array( $network, $clean, true ) ) {
				$clean[] = $network;
			}
		}

		return $clean;
	}

	/**
	 * Build a network share URL.
	 *
	 * @param string $network Network key.
	 * @param string $url     Canonical page URL.
	 * @param string $title   Page title.
	 * @param string $media   Optional media URL for Pinterest.
	 * @return string
	 */
	public static function get_share_url( $network, $url, $title, $media = '' ) {
		$url_encoded   = rawurlencode( esc_url_raw( $url ) );
		$title_encoded = rawurlencode( wp_strip_all_tags( $title ) );
		$text_encoded  = rawurlencode( trim( wp_strip_all_tags( $title ) . ' ' . esc_url_raw( $url ) ) );
		$media_encoded = rawurlencode( esc_url_raw( $media ) );

		switch ( $network ) {
			case 'facebook':
				return 'https://www.facebook.com/sharer/sharer.php?u=' . $url_encoded;
			case 'x':
				return 'https://twitter.com/intent/tweet?url=' . $url_encoded . '&text=' . $title_encoded;
			case 'linkedin':
				return 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url_encoded;
			case 'whatsapp':
				return 'https://wa.me/?text=' . $text_encoded;
			case 'pinterest':
				$url = 'https://www.pinterest.com/pin/create/button/?url=' . $url_encoded . '&description=' . $title_encoded;
				return $media_encoded ? $url . '&media=' . $media_encoded : $url;
			case 'threads':
				return 'https://www.threads.net/intent/post?text=' . $text_encoded;
			case 'bluesky':
				return 'https://bsky.app/intent/compose?text=' . $text_encoded;
			case 'telegram':
				return 'https://t.me/share/url?url=' . $url_encoded . '&text=' . $title_encoded;
			case 'reddit':
				return 'https://www.reddit.com/submit?url=' . $url_encoded . '&title=' . $title_encoded;
			case 'email':
				return 'mailto:?subject=' . $title_encoded . '&body=' . $url_encoded;
			case 'copy':
				return esc_url_raw( $url );
		}

		return '';
	}

	/**
	 * Get a network icon.
	 *
	 * Icons are original inline SVG paths shipped locally with the plugin.
	 *
	 * @param string $network Network key.
	 * @return string
	 */
	public static function get_icon( $network ) {
		$icons = array(
			'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13.7 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.3-1.5 1.6-1.5H17V4.6c-.8-.1-1.7-.2-2.5-.2-2.5 0-4.2 1.5-4.2 4.3v2.2H7.5V14h2.8v8h3.4Z"/></svg>',
			'x'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18.7 3h3.1l-6.9 7.9L23 21h-6.4l-5-6.5L5.9 21H2.8l7.3-8.4L2.3 3h6.6l4.5 5.9L18.7 3Zm-1.1 16h1.7L8 4.9H6.2L17.6 19Z"/></svg>',
			'linkedin'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.5 8.2H3V21h3.5V8.2ZM4.8 3A2.1 2.1 0 1 0 4.8 7.2 2.1 2.1 0 0 0 4.8 3ZM21 13.7c0-3.8-2-5.7-4.8-5.7-2.2 0-3.2 1.2-3.8 2.1v-1.9H9V21h3.5v-6.3c0-1.7.3-3.3 2.4-3.3 2 0 2.1 1.9 2.1 3.4V21H21v-7.3Z"/></svg>',
			'whatsapp'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.5 3.5A11.8 11.8 0 0 0 1.9 17.7L.3 23.5l6-1.6A11.7 11.7 0 0 0 12 23.4h.1A11.7 11.7 0 0 0 20.5 3.5Zm-8.4 17.9h-.1a9.7 9.7 0 0 1-5-1.4l-.4-.2-3.5.9.9-3.4-.2-.4A9.8 9.8 0 1 1 12 21.4Zm5.4-7.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2l-.9 1.1c-.2.2-.3.2-.6.1-1.7-.9-2.8-1.5-3.9-3.4-.3-.5.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.3c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.4-1.2 1.2-1.2 2.9s1.3 3.4 1.4 3.6c.2.2 2.5 3.8 6 5.3 2.2.9 3.1 1 4.2.8.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.3Z"/></svg>',
			'pinterest' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 0 0-3.6 19.3c-.1-.8-.1-2 .1-2.9l1.2-5.1s-.3-.7-.3-1.7c0-1.6.9-2.8 2.1-2.8 1 0 1.5.8 1.5 1.7 0 1-.6 2.5-1 3.8-.3 1.2.6 2.1 1.7 2.1 2.1 0 3.7-2.2 3.7-5.3 0-2.8-2-4.8-4.9-4.8-3.3 0-5.3 2.5-5.3 5.1 0 1 .4 2.1.9 2.7.1.1.1.2.1.3l-.3 1.3c-.1.2-.2.3-.4.2-1.5-.7-2.4-2.8-2.4-4.5 0-3.7 2.7-7.1 7.7-7.1 4.1 0 7.2 2.9 7.2 6.8 0 4-2.5 7.3-6.1 7.3-1.2 0-2.3-.6-2.7-1.3l-.7 2.8c-.3 1-.9 2.2-1.4 3A10 10 0 1 0 12 2Z"/></svg>',
			'threads'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16.2 10.6c-.1-2.6-1.6-4.2-4.3-4.3-2.2-.1-4 .8-4.9 2.5l1.7.9c.5-1 1.6-1.5 3.1-1.5 1.6.1 2.3.7 2.5 2.1-3.6.6-5.4 2.1-5.2 4.4.1 1.9 1.7 3.1 3.8 3.1 2.3 0 3.8-1.2 4.3-3.4.8.4 1.2 1 1.2 1.8 0 2.2-2.2 3.9-5.1 3.9H10c-3.9 0-7-3.1-7-7s3.1-7 7-7h4c3.9 0 7 3.1 7 7h2c0-5-4-9-9-9h-4c-5 0-9 4-9 9s4 9 9 9h3.3c4 0 7.1-2.5 7.1-5.9 0-2.8-1.8-4.6-4.2-4.6Zm-3.3 5.3c-1 0-1.8-.5-1.8-1.3-.1-1.1 1-1.9 3.3-2.3 0 2.5-.5 3.6-1.5 3.6Z"/></svg>',
			'bluesky'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 10.8c-1.1-2.1-5.7-6.3-8-7-3.5-1.1-2.2 3.2-1.6 4.4.9 1.9 3.1 3.7 4.6 4.4-2.1 0-4.5-.1-6.3-.5-3.6-.7-1.9 2.4-.4 3.5C4 18.5 9.1 19.3 12 21.1c2.9-1.8 8-2.6 11.7-5.5 1.5-1.1 3.1-4.2-.5-3.5-1.7.4-4.1.5-6.2.5 1.5-.7 3.7-2.5 4.6-4.4.6-1.2 1.9-5.5-1.6-4.4-2.3.7-6.9 4.9-8 7Z"/></svg>',
			'telegram'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21.7 3.4 18.5 20c-.2 1.2-.9 1.5-1.9.9l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.4-5 9.1-8.2c.4-.4-.1-.6-.6-.2L6 13.8l-4.8-1.5c-1-.3-1.1-1 .2-1.5L20.2 3.5c.9-.3 1.7.2 1.5-.1Z"/></svg>',
			'reddit'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.9 11.2c.1-.3.1-.6.1-.9a2.3 2.3 0 0 0-4.1-1.4c-1.3-.8-2.9-1.3-4.6-1.4l1-3 2.6.6a1.7 1.7 0 1 0 .3-1.2l-3.2-.8c-.3-.1-.6.1-.7.4l-1.3 4c-1.7.1-3.3.6-4.6 1.4a2.3 2.3 0 0 0-4 1.5c0 .3 0 .6.1.8-.3.5-.5 1.1-.5 1.8 0 3.1 4.5 5.7 10 5.7s10-2.6 10-5.7c0-.7-.4-1.3-1.1-1.8ZM7.3 12a1.4 1.4 0 1 1 0 2.8 1.4 1.4 0 0 1 0-2.8Zm7.8 4.4c-.8.8-2 1.2-3.1 1.2-1.2 0-2.3-.4-3.2-1.2a.6.6 0 0 1 .8-.9c.7.6 1.5.9 2.4.9.8 0 1.7-.3 2.3-.9a.6.6 0 0 1 .8.9Zm1.6-1.6a1.4 1.4 0 1 1 0-2.8 1.4 1.4 0 0 1 0 2.8Z"/></svg>',
			'email'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 5H4a2 2 0 0 0-2 2v10c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Zm0 4-8 5-8-5V7l8 5 8-5v2Z"/></svg>',
			'copy'      => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 7V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3Zm2 0h5a2 2 0 0 1 2 2v5h2V5h-9v2Zm5 2H5v10h10V9Z"/></svg>',
			'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm11.5 1.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/></svg>',
			'youtube'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M23 7.3a3 3 0 0 0-2.1-2.1C19 4.7 12 4.7 12 4.7s-7 0-8.9.5A3 3 0 0 0 1 7.3 31 31 0 0 0 .5 12a31 31 0 0 0 .5 4.7 3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.7 31 31 0 0 0-.5-4.7ZM9.7 15.5v-7l6.1 3.5-6.1 3.5Z"/></svg>',
			'tiktok'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16.7 3c.2 1.7 1.2 3.2 2.8 4.1.8.4 1.6.7 2.5.7v3.5a9.4 9.4 0 0 1-5.3-1.7v6.6a6.8 6.8 0 1 1-5.9-6.7v3.6a3.3 3.3 0 1 0 2.4 3.1V3h3.5Z"/></svg>',
			'github'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-1.9c-2.9.6-3.5-1.2-3.5-1.2-.5-1.2-1.2-1.5-1.2-1.5-1-.7.1-.7.1-.7 1.1.1 1.7 1.1 1.7 1.1 1 1.7 2.6 1.2 3.2.9.1-.7.4-1.2.7-1.5-2.3-.3-4.7-1.2-4.7-5a3.9 3.9 0 0 1 1-2.7 3.6 3.6 0 0 1 .1-2.7s.8-.3 2.8 1a9.5 9.5 0 0 1 5 0c2-1.3 2.8-1 2.8-1a3.6 3.6 0 0 1 .1 2.7 3.9 3.9 0 0 1 1 2.7c0 3.9-2.4 4.7-4.7 5 .4.3.7 1 .7 2V21c0 .3.2.6.7.5A10 10 0 0 0 12 2Z"/></svg>',
			'more'      => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>',
			'close'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12l5.6 5.6-1.4 1.4-5.6-5.6L6.4 19 5 17.6l5.6-5.6L5 6.4 6.4 5Z"/></svg>',
		);

		return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
	}
}
