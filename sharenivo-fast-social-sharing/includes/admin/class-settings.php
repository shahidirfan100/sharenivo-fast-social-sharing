<?php
/**
 * Settings storage, migration, validation, and admin screen.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ShareNivo settings manager.
 */
class Settings {

	/**
	 * Get the current defaults.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$networks = array( 'facebook', 'x', 'linkedin', 'whatsapp', 'pinterest', 'threads', 'bluesky', 'telegram', 'reddit', 'email', 'copy' );

		return array(
			'schema_version'   => SHARENIVO_SCHEMA_VERSION,
			'enabled'          => true,
			'networks'         => $networks,
			'network_order'    => $networks,
			'post_types'       => array( 'post', 'page' ),
			'show_on_homepage' => false,
			'locations'        => array(
				'floating' => array(
					'enabled'     => true,
					'side'        => 'left',
					'vertical'    => 50,
					'hide_mobile' => true,
				),
				'inline'   => array(
					'enabled'   => false,
					'position'  => 'bottom',
					'alignment' => 'left',
				),
				'sticky'   => array(
					'enabled'   => true,
					'width'     => 'compact',
					'alignment' => 'center',
				),
				'popup'    => array(
					'enabled'       => false,
					'title'         => 'Enjoyed this article?',
					'message'       => 'Share it with your network.',
					'trigger'       => 'scroll',
					'trigger_value' => 70,
					'frequency'     => 'session',
				),
				'flyin'    => array(
					'enabled'       => false,
					'side'          => 'right',
					'title'         => 'Worth sharing?',
					'message'       => 'Help others discover this page.',
					'trigger'       => 'bottom',
					'trigger_value' => 70,
					'frequency'     => 'session',
				),
				'media'    => array(
					'enabled'   => false,
					'min_width' => 300,
					'networks'  => array( 'pinterest', 'facebook', 'x', 'copy' ),
				),
			),
			'style'            => array(
				'shape'          => 'rounded',
				'size'           => 'medium',
				'color_scheme'   => 'network',
				'brand_bg'       => '#3a1f4f',
				'brand_hover'    => '#ff5a4f',
				'icon_color'     => '#ffffff',
				'show_labels'    => false,
				'gap'            => 8,
				'hover'          => 'lift',
				'entrance'       => 'fade',
				'more_button'    => true,
				'max_visible'    => 6,
			),
			'follow'           => array(
				'enabled'  => false,
				'heading'  => 'Follow us',
				'profiles' => array(),
			),
		);
	}

	/**
	 * Get settings, migrating legacy ShareNivo and ShareNova configurations.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$stored = get_option( 'sharenivo_settings', false );

		if ( false === $stored ) {
			$stored = get_option( 'sharenova_settings', false );
		}

		if ( ! is_array( $stored ) ) {
			$stored = self::get_defaults();
		}

		if ( empty( $stored['schema_version'] ) || 2 > absint( $stored['schema_version'] ) ) {
			$stored = self::migrate_legacy_settings( $stored );
			update_option( 'sharenivo_settings', $stored, false );
			update_option( 'sharenivo_schema_version', SHARENIVO_SCHEMA_VERSION, false );
		}

		// Normalize earlier 2.x settings to the current allowlisted schema.
		if ( SHARENIVO_SCHEMA_VERSION > absint( $stored['schema_version'] ?? 0 ) ) {
			$stored                   = self::sanitize_settings( self::normalize_settings( $stored ) );
			$stored['schema_version'] = SHARENIVO_SCHEMA_VERSION;
			update_option( 'sharenivo_settings', $stored, false );
			update_option( 'sharenivo_schema_version', SHARENIVO_SCHEMA_VERSION, false );
		}

		// Move the original 2.0 default pair to the refined plum/coral brand.
		if (
			isset( $stored['style']['brand_bg'], $stored['style']['brand_hover'] ) &&
			'#635bff' === strtolower( $stored['style']['brand_bg'] ) &&
			'#ff7a66' === strtolower( $stored['style']['brand_hover'] )
		) {
			$stored['style']['brand_bg']    = '#3a1f4f';
			$stored['style']['brand_hover'] = '#ff5a4f';
			update_option( 'sharenivo_settings', $stored, false );
		}

		$settings = self::normalize_settings( $stored );

		/**
		 * Filter normalized settings at runtime.
		 *
		 * @param array $settings ShareNivo settings.
		 */
		$settings = apply_filters( 'sharenivo_settings', $settings );
		$settings = apply_filters( 'sharenova_settings', $settings );

		return is_array( $settings ) ? self::sanitize_settings( self::normalize_settings( $settings ) ) : self::get_defaults();
	}

	/**
	 * Convert 1.x settings into the 2.0 schema.
	 *
	 * @param array $legacy Legacy settings.
	 * @return array
	 */
	private static function migrate_legacy_settings( $legacy ) {
		$settings = self::get_defaults();

		$settings['enabled']          = isset( $legacy['enabled'] ) ? (bool) $legacy['enabled'] : true;
		$settings['networks']         = isset( $legacy['networks'] ) ? Networks::sanitize_network_list( $legacy['networks'] ) : $settings['networks'];
		$settings['network_order']    = isset( $legacy['network_order'] ) ? self::sort_networks( $settings['networks'], $legacy['network_order'] ) : $settings['networks'];
		$settings['post_types']       = isset( $legacy['post_types'] ) && is_array( $legacy['post_types'] ) ? $legacy['post_types'] : $settings['post_types'];
		$settings['show_on_homepage'] = ! empty( $legacy['show_on_homepage'] );
		$position = isset( $legacy['position'] ) ? sanitize_key( $legacy['position'] ) : 'floating_left';
		if ( in_array( $position, array( 'inline_top', 'inline_bottom' ), true ) ) {
			$settings['locations']['floating']['enabled'] = false;
			$settings['locations']['inline']['enabled']   = true;
			$settings['locations']['inline']['position']  = 'inline_top' === $position ? 'top' : 'bottom';
		} else {
			$settings['locations']['floating']['side'] = 'floating_right' === $position ? 'right' : 'left';
		}

		$settings['locations']['sticky']['enabled'] = 'sticky_bottom' === ( $legacy['mobile_position'] ?? 'sticky_bottom' );
		$settings['style']['shape']                 = self::map_legacy_shape( $legacy['button_shape'] ?? 'circle' );
		$settings['style']['size']                  = $legacy['button_size'] ?? 'medium';
		$settings['style']['hover']                 = $legacy['hover_animation'] ?? 'lift';
		$settings['style']['gap']                   = absint( $legacy['button_gap'] ?? 8 );
		$settings['style']['more_button']           = ! isset( $legacy['show_more_network_button'] ) || ! empty( $legacy['show_more_network_button'] );

		if ( 'custom' === ( $legacy['color_scheme'] ?? 'brand' ) ) {
			$settings['style']['color_scheme'] = 'brand';
			$settings['style']['brand_bg']     = $legacy['custom_bg_color'] ?? '#3a1f4f';
			$settings['style']['brand_hover']  = $legacy['custom_hover_bg'] ?? '#ff5a4f';
			$settings['style']['icon_color']   = $legacy['custom_icon_color'] ?? '#ffffff';
		}

		$settings['schema_version'] = SHARENIVO_SCHEMA_VERSION;
		return self::sanitize_settings( $settings );
	}

	/**
	 * Map old button shapes.
	 *
	 * @param string $shape Legacy shape.
	 * @return string
	 */
	private static function map_legacy_shape( $shape ) {
		$shape = sanitize_key( $shape );
		if ( in_array( $shape, array( 'landscape', 'portrait', 'landscape_rectangle' ), true ) ) {
			return 'pill';
		}
		return in_array( $shape, array( 'circle', 'square', 'rounded' ), true ) ? $shape : 'rounded';
	}

	/**
	 * Merge nested defaults without discarding valid saved values.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	private static function normalize_settings( $settings ) {
		$defaults = self::get_defaults();
		$settings = is_array( $settings ) ? array_intersect_key( $settings, $defaults ) : array();
		$settings = wp_parse_args( $settings, $defaults );
		$settings['locations'] = wp_parse_args( is_array( $settings['locations'] ) ? $settings['locations'] : array(), $defaults['locations'] );
		foreach ( $defaults['locations'] as $location => $location_defaults ) {
			$settings['locations'][ $location ] = wp_parse_args( is_array( $settings['locations'][ $location ] ) ? $settings['locations'][ $location ] : array(), $location_defaults );
		}
		$settings['style']  = wp_parse_args( is_array( $settings['style'] ) ? $settings['style'] : array(), $defaults['style'] );
		$settings['follow'] = wp_parse_args( is_array( $settings['follow'] ) ? $settings['follow'] : array(), $defaults['follow'] );
		$settings['networks']      = Networks::sanitize_network_list( $settings['networks'] );
		$settings['network_order'] = self::sort_networks( $settings['networks'], $settings['network_order'] );
		return $settings;
	}

	/**
	 * Sort active networks by a submitted order.
	 *
	 * @param array        $networks Active networks.
	 * @param array|string $order    Preferred order.
	 * @return array
	 */
	public static function sort_networks( $networks, $order ) {
		$networks = Networks::sanitize_network_list( $networks );
		if ( is_string( $order ) ) {
			$order = explode( ',', $order );
		}
		$order = Networks::sanitize_network_list( is_array( $order ) ? $order : array() );

		$sorted = array();
		foreach ( $order as $network ) {
			if ( in_array( $network, $networks, true ) ) {
				$sorted[] = $network;
			}
		}
		foreach ( $networks as $network ) {
			if ( ! in_array( $network, $sorted, true ) ) {
				$sorted[] = $network;
			}
		}
		return $sorted;
	}

	/**
	 * Validate the complete settings tree.
	 *
	 * @param array $input Untrusted settings.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$defaults = self::get_defaults();
		$input    = is_array( $input ) ? $input : array();
		$locations = isset( $input['locations'] ) && is_array( $input['locations'] ) ? $input['locations'] : array();
		$style     = isset( $input['style'] ) && is_array( $input['style'] ) ? $input['style'] : array();
		$follow    = isset( $input['follow'] ) && is_array( $input['follow'] ) ? $input['follow'] : array();

		$networks = Networks::sanitize_network_list( $input['networks'] ?? array() );
		$order    = self::sort_networks( $networks, $input['network_order'] ?? array() );

		$public_post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $public_post_types['attachment'] );
		$post_types = array();
		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$post_types = array_values( array_intersect( array_values( $public_post_types ), array_map( 'sanitize_key', $input['post_types'] ) ) );
		}

		$trigger_options   = array( 'delay', 'scroll', 'bottom', 'inactivity', 'exit', 'comment', 'purchase' );
		$frequency_options = array( 'always', 'session', 'day', 'week' );
		$shape_options     = array( 'circle', 'rounded', 'square', 'pill' );
		$size_options      = array( 'small', 'medium', 'large' );
		$color_options     = array( 'network', 'brand', 'minimal' );
		$hover_options     = array( 'lift', 'grow', 'slide', 'glow', 'tilt', 'pulse', 'twist', 'none' );
		$entrance_options  = array( 'fade', 'slide', 'zoom', 'none' );

		$clean = array(
			'schema_version'   => SHARENIVO_SCHEMA_VERSION,
			'enabled'          => ! empty( $input['enabled'] ),
			'networks'         => $networks,
			'network_order'    => $order,
			'post_types'       => $post_types,
			'show_on_homepage' => ! empty( $input['show_on_homepage'] ),
			'locations'        => array(),
			'style'            => array(),
			'follow'           => array(),
		);

		$floating = isset( $locations['floating'] ) && is_array( $locations['floating'] ) ? $locations['floating'] : array();
		$clean['locations']['floating'] = array(
			'enabled'     => ! empty( $floating['enabled'] ),
			'side'        => self::allow_value( $floating['side'] ?? '', array( 'left', 'right' ), 'left' ),
			'vertical'    => max( 10, min( 90, absint( $floating['vertical'] ?? 50 ) ) ),
			'hide_mobile' => ! empty( $floating['hide_mobile'] ),
		);

		$inline = isset( $locations['inline'] ) && is_array( $locations['inline'] ) ? $locations['inline'] : array();
		$clean['locations']['inline'] = array(
			'enabled'   => ! empty( $inline['enabled'] ),
			'position'  => self::allow_value( $inline['position'] ?? '', array( 'top', 'bottom', 'both' ), 'bottom' ),
			'alignment' => self::allow_value( $inline['alignment'] ?? '', array( 'left', 'center', 'right' ), 'left' ),
		);

		$sticky = isset( $locations['sticky'] ) && is_array( $locations['sticky'] ) ? $locations['sticky'] : array();
		$clean['locations']['sticky'] = array(
			'enabled'   => ! empty( $sticky['enabled'] ),
			'width'     => self::allow_value( $sticky['width'] ?? '', array( 'compact', 'full' ), 'compact' ),
			'alignment' => self::allow_value( $sticky['alignment'] ?? '', array( 'left', 'center', 'right' ), 'center' ),
		);

		foreach ( array( 'popup', 'flyin' ) as $location ) {
			$value = isset( $locations[ $location ] ) && is_array( $locations[ $location ] ) ? $locations[ $location ] : array();
			$clean['locations'][ $location ] = array(
				'enabled'       => ! empty( $value['enabled'] ),
				'title'         => sanitize_text_field( $value['title'] ?? $defaults['locations'][ $location ]['title'] ),
				'message'       => sanitize_textarea_field( $value['message'] ?? $defaults['locations'][ $location ]['message'] ),
				'trigger'       => self::allow_value( $value['trigger'] ?? '', $trigger_options, $defaults['locations'][ $location ]['trigger'] ),
				'trigger_value' => max( 1, min( 600, absint( $value['trigger_value'] ?? $defaults['locations'][ $location ]['trigger_value'] ) ) ),
				'frequency'     => self::allow_value( $value['frequency'] ?? '', $frequency_options, 'session' ),
			);
			if ( 'flyin' === $location ) {
				$clean['locations'][ $location ]['side'] = self::allow_value( $value['side'] ?? '', array( 'left', 'right' ), 'right' );
			}
		}

		$media = isset( $locations['media'] ) && is_array( $locations['media'] ) ? $locations['media'] : array();
		$clean['locations']['media'] = array(
			'enabled'   => ! empty( $media['enabled'] ),
			'min_width' => max( 120, min( 1200, absint( $media['min_width'] ?? 300 ) ) ),
			'networks'  => Networks::sanitize_network_list( $media['networks'] ?? array() ),
		);

		$brand_bg    = sanitize_hex_color( $style['brand_bg'] ?? $defaults['style']['brand_bg'] );
		$brand_hover = sanitize_hex_color( $style['brand_hover'] ?? $defaults['style']['brand_hover'] );
		$icon_color  = sanitize_hex_color( $style['icon_color'] ?? $defaults['style']['icon_color'] );

		$clean['style'] = array(
			'shape'        => self::allow_value( $style['shape'] ?? '', $shape_options, 'rounded' ),
			'size'         => self::allow_value( $style['size'] ?? '', $size_options, 'medium' ),
			'color_scheme' => self::allow_value( $style['color_scheme'] ?? '', $color_options, 'network' ),
			'brand_bg'     => $brand_bg ? $brand_bg : $defaults['style']['brand_bg'],
			'brand_hover'  => $brand_hover ? $brand_hover : $defaults['style']['brand_hover'],
			'icon_color'   => $icon_color ? $icon_color : $defaults['style']['icon_color'],
			'show_labels'  => ! empty( $style['show_labels'] ),
			'gap'          => min( 30, absint( $style['gap'] ?? 8 ) ),
			'hover'        => self::allow_value( $style['hover'] ?? '', $hover_options, 'lift' ),
			'entrance'     => self::allow_value( $style['entrance'] ?? '', $entrance_options, 'fade' ),
			'more_button'  => ! empty( $style['more_button'] ),
			'max_visible'  => max( 3, min( 12, absint( $style['max_visible'] ?? 6 ) ) ),
		);

		$profiles = array();
		$allowed_profiles = Networks::get_follow_networks();
		if ( isset( $follow['profiles'] ) && is_array( $follow['profiles'] ) ) {
			foreach ( $follow['profiles'] as $network => $profile_url ) {
				$network = sanitize_key( $network );
				$url     = esc_url_raw( $profile_url );
				if ( isset( $allowed_profiles[ $network ] ) && $url ) {
					$profiles[ $network ] = $url;
				}
			}
		}
		$clean['follow'] = array(
			'enabled'  => ! empty( $follow['enabled'] ),
			'heading'  => sanitize_text_field( $follow['heading'] ?? $defaults['follow']['heading'] ),
			'profiles' => $profiles,
		);

		return $clean;
	}

	/**
	 * Validate an enumerated value.
	 *
	 * @param mixed  $value    Submitted value.
	 * @param array  $allowed  Allowed values.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private static function allow_value( $value, $allowed, $fallback ) {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Render the settings dashboard.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings        = self::get_settings();
		$share_networks  = Networks::get_share_networks();
		$follow_networks = Networks::get_follow_networks();
		$post_types      = get_post_types( array( 'public' => true ), 'objects' );
		unset( $post_types['attachment'] );
		?>
		<div class="wrap sharenivo-admin" data-sharenivo-version="<?php echo esc_attr( SHARENIVO_VERSION ); ?>">
			<?php if ( isset( $_GET['sharenivo-updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['sharenivo-updated'] ) ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status flag. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'ShareNivo settings saved.', 'sharenivo-fast-social-sharing' ); ?></p></div>
			<?php endif; ?>
			<header class="sharenivo-admin__header">
				<div class="sharenivo-brand">
					<span class="sharenivo-brand__mark" aria-hidden="true"><svg viewBox="0 0 48 48"><circle cx="13" cy="14" r="7"/><circle cx="35" cy="10" r="6"/><circle cx="34" cy="36" r="8"/><path d="m19 13 10-2M17 19l12 12"/></svg></span>
					<div><h1><?php esc_html_e( 'ShareNivo', 'sharenivo-fast-social-sharing' ); ?></h1><p><?php esc_html_e( 'Fast social reach. Zero tracking. Your design.', 'sharenivo-fast-social-sharing' ); ?></p></div>
				</div>
				<div class="sharenivo-admin__status"><span><?php esc_html_e( 'No external requests', 'sharenivo-fast-social-sharing' ); ?></span><strong>v<?php echo esc_html( SHARENIVO_VERSION ); ?></strong></div>
			</header>

			<div class="sharenivo-admin__shell">
				<nav class="sharenivo-tabs" aria-label="<?php esc_attr_e( 'ShareNivo settings', 'sharenivo-fast-social-sharing' ); ?>">
					<?php
					$tabs = array(
						'overview'  => __( 'Overview', 'sharenivo-fast-social-sharing' ),
						'networks'  => __( 'Networks', 'sharenivo-fast-social-sharing' ),
						'locations' => __( 'Locations', 'sharenivo-fast-social-sharing' ),
						'design'    => __( 'Design', 'sharenivo-fast-social-sharing' ),
						'follow'    => __( 'Follow', 'sharenivo-fast-social-sharing' ),
						'advanced'  => __( 'Advanced', 'sharenivo-fast-social-sharing' ),
					);
					foreach ( $tabs as $tab => $label ) :
						?>
						<button type="button" class="sharenivo-tabs__button<?php echo esc_attr( 'overview' === $tab ? ' is-active' : '' ); ?>" data-tab="<?php echo esc_attr( $tab ); ?>" aria-selected="<?php echo esc_attr( 'overview' === $tab ? 'true' : 'false' ); ?>"><?php echo esc_html( $label ); ?></button>
					<?php endforeach; ?>
				</nav>

				<form class="sharenivo-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'sharenivo_save_settings', 'sharenivo_settings_nonce' ); ?>
					<input type="hidden" name="action" value="sharenivo_save_settings_action">

					<section class="sharenivo-panel is-active" data-panel="overview">
						<div class="sharenivo-panel__heading"><div><span class="sharenivo-eyebrow"><?php esc_html_e( 'Control center', 'sharenivo-fast-social-sharing' ); ?></span><h2><?php esc_html_e( 'ShareNivo at a glance', 'sharenivo-fast-social-sharing' ); ?></h2></div></div>
						<div class="sharenivo-metrics">
							<div><strong><?php echo esc_html( count( $settings['networks'] ) ); ?></strong><span><?php esc_html_e( 'Active share options', 'sharenivo-fast-social-sharing' ); ?></span></div>
							<div><strong><?php echo esc_html( self::count_active_locations( $settings['locations'] ) ); ?></strong><span><?php esc_html_e( 'Enabled locations', 'sharenivo-fast-social-sharing' ); ?></span></div>
							<div><strong>0</strong><span><?php esc_html_e( 'Tracking requests', 'sharenivo-fast-social-sharing' ); ?></span></div>
						</div>
						<div class="sharenivo-card sharenivo-master-card">
							<div><h3><?php esc_html_e( 'Master switch', 'sharenivo-fast-social-sharing' ); ?></h3><p><?php esc_html_e( 'Pause all ShareNivo output without losing your configuration.', 'sharenivo-fast-social-sharing' ); ?></p></div>
							<?php self::render_toggle( 'sharenivo_settings[enabled]', $settings['enabled'], __( 'Enable ShareNivo', 'sharenivo-fast-social-sharing' ) ); ?>
						</div>
						<div class="sharenivo-feature-grid">
							<div class="sharenivo-card"><span class="sharenivo-card__icon">01</span><h3><?php esc_html_e( 'Multiple placements', 'sharenivo-fast-social-sharing' ); ?></h3><p><?php esc_html_e( 'Use inline, floating, mobile sticky, popup, fly-in, and media sharing together.', 'sharenivo-fast-social-sharing' ); ?></p></div>
							<div class="sharenivo-card"><span class="sharenivo-card__icon">02</span><h3><?php esc_html_e( 'Smart local triggers', 'sharenivo-fast-social-sharing' ); ?></h3><p><?php esc_html_e( 'Trigger prompts by time, scroll, reading completion, inactivity, comments, or purchases.', 'sharenivo-fast-social-sharing' ); ?></p></div>
							<div class="sharenivo-card"><span class="sharenivo-card__icon">03</span><h3><?php esc_html_e( 'Privacy by design', 'sharenivo-fast-social-sharing' ); ?></h3><p><?php esc_html_e( 'No API calls, tracking pixels, remote fonts, share counts, or analytics beacons.', 'sharenivo-fast-social-sharing' ); ?></p></div>
						</div>
					</section>

					<section class="sharenivo-panel" data-panel="networks" hidden>
						<div class="sharenivo-panel__heading"><div><span class="sharenivo-eyebrow"><?php esc_html_e( '2026-ready', 'sharenivo-fast-social-sharing' ); ?></span><h2><?php esc_html_e( 'Sharing networks', 'sharenivo-fast-social-sharing' ); ?></h2><p><?php esc_html_e( 'Only networks with current browser sharing flows are included.', 'sharenivo-fast-social-sharing' ); ?></p></div></div>
						<div class="sharenivo-network-grid">
							<?php foreach ( $share_networks as $network => $data ) : $checked = in_array( $network, $settings['networks'], true ); ?>
								<label class="sharenivo-network-card<?php echo esc_attr( $checked ? ' is-selected' : '' ); ?>" data-network="<?php echo esc_attr( $network ); ?>">
									<input class="sharenivo-network-checkbox" type="checkbox" name="sharenivo_settings[networks][]" value="<?php echo esc_attr( $network ); ?>" <?php checked( $checked ); ?>>
									<span class="sharenivo-network-card__icon" style="--network-color:<?php echo esc_attr( $data['color'] ); ?>"><?php echo wp_kses( Networks::get_icon( $network ), self::svg_allowed_html() ); ?></span>
									<span><?php echo esc_html( $data['label'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<div class="sharenivo-card"><h3><?php esc_html_e( 'Display order', 'sharenivo-fast-social-sharing' ); ?></h3><p><?php esc_html_e( 'Use the controls to set an accessible, deterministic order.', 'sharenivo-fast-social-sharing' ); ?></p><ol class="sharenivo-order-list" id="sharenivo-order-list">
							<?php foreach ( $settings['network_order'] as $network ) : if ( ! isset( $share_networks[ $network ] ) ) { continue; } ?>
								<li data-network="<?php echo esc_attr( $network ); ?>"><span><?php echo esc_html( $share_networks[ $network ]['label'] ); ?></span><span><button type="button" class="sharenivo-order-up" aria-label="<?php esc_attr_e( 'Move up', 'sharenivo-fast-social-sharing' ); ?>">↑</button><button type="button" class="sharenivo-order-down" aria-label="<?php esc_attr_e( 'Move down', 'sharenivo-fast-social-sharing' ); ?>">↓</button></span></li>
							<?php endforeach; ?>
						</ol><input type="hidden" id="sharenivo-network-order" name="sharenivo_settings[network_order]" value="<?php echo esc_attr( implode( ',', $settings['network_order'] ) ); ?>"></div>
					</section>

					<section class="sharenivo-panel" data-panel="locations" hidden>
						<div class="sharenivo-panel__heading"><div><span class="sharenivo-eyebrow"><?php esc_html_e( 'Placement engine', 'sharenivo-fast-social-sharing' ); ?></span><h2><?php esc_html_e( 'Choose where sharing appears', 'sharenivo-fast-social-sharing' ); ?></h2></div></div>
						<div class="sharenivo-location-grid">
							<?php self::render_location_card( 'floating', __( 'Floating rail', 'sharenivo-fast-social-sharing' ), __( 'Persistent desktop sharing on the left or right edge.', 'sharenivo-fast-social-sharing' ), $settings ); ?>
							<?php self::render_location_card( 'inline', __( 'Inline buttons', 'sharenivo-fast-social-sharing' ), __( 'Place buttons above, below, or around the post content.', 'sharenivo-fast-social-sharing' ), $settings ); ?>
							<?php self::render_location_card( 'sticky', __( 'Mobile sticky bar', 'sharenivo-fast-social-sharing' ), __( 'Thumb-friendly sharing at the bottom of small screens.', 'sharenivo-fast-social-sharing' ), $settings ); ?>
							<?php self::render_location_card( 'popup', __( 'Share popup', 'sharenivo-fast-social-sharing' ), __( 'Accessible modal prompt controlled by a local trigger.', 'sharenivo-fast-social-sharing' ), $settings ); ?>
							<?php self::render_location_card( 'flyin', __( 'Share fly-in', 'sharenivo-fast-social-sharing' ), __( 'A compact corner prompt for engaged readers.', 'sharenivo-fast-social-sharing' ), $settings ); ?>
							<?php self::render_location_card( 'media', __( 'Image sharing', 'sharenivo-fast-social-sharing' ), __( 'Show lightweight share controls over eligible post images.', 'sharenivo-fast-social-sharing' ), $settings ); ?>
						</div>
					</section>

					<section class="sharenivo-panel" data-panel="design" hidden>
						<div class="sharenivo-panel__heading"><div><span class="sharenivo-eyebrow"><?php esc_html_e( 'Original ShareNivo system', 'sharenivo-fast-social-sharing' ); ?></span><h2><?php esc_html_e( 'Design and motion', 'sharenivo-fast-social-sharing' ); ?></h2></div></div>
						<div class="sharenivo-design-layout"><div class="sharenivo-card sharenivo-fields">
							<?php self::render_select_field( __( 'Button shape', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[style][shape]', $settings['style']['shape'], array( 'circle' => __( 'Circle', 'sharenivo-fast-social-sharing' ), 'rounded' => __( 'Rounded', 'sharenivo-fast-social-sharing' ), 'square' => __( 'Square', 'sharenivo-fast-social-sharing' ), 'pill' => __( 'Pill', 'sharenivo-fast-social-sharing' ) ) ); ?>
							<?php self::render_select_field( __( 'Button size', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[style][size]', $settings['style']['size'], array( 'small' => __( 'Small', 'sharenivo-fast-social-sharing' ), 'medium' => __( 'Medium', 'sharenivo-fast-social-sharing' ), 'large' => __( 'Large', 'sharenivo-fast-social-sharing' ) ) ); ?>
							<?php self::render_select_field( __( 'Color system', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[style][color_scheme]', $settings['style']['color_scheme'], array( 'network' => __( 'Network colors', 'sharenivo-fast-social-sharing' ), 'brand' => __( 'ShareNivo brand', 'sharenivo-fast-social-sharing' ), 'minimal' => __( 'Minimal monochrome', 'sharenivo-fast-social-sharing' ) ) ); ?>
							<div class="sharenivo-field sharenivo-color-fields"><label><?php esc_html_e( 'Brand background', 'sharenivo-fast-social-sharing' ); ?><input class="sharenivo-color" type="text" name="sharenivo_settings[style][brand_bg]" value="<?php echo esc_attr( $settings['style']['brand_bg'] ); ?>"></label><label><?php esc_html_e( 'Brand hover', 'sharenivo-fast-social-sharing' ); ?><input class="sharenivo-color" type="text" name="sharenivo_settings[style][brand_hover]" value="<?php echo esc_attr( $settings['style']['brand_hover'] ); ?>"></label><label><?php esc_html_e( 'Icon color', 'sharenivo-fast-social-sharing' ); ?><input class="sharenivo-color" type="text" name="sharenivo_settings[style][icon_color]" value="<?php echo esc_attr( $settings['style']['icon_color'] ); ?>"></label></div>
							<?php self::render_select_field( __( 'Hover effect', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[style][hover]', $settings['style']['hover'], array( 'lift' => __( 'Lift', 'sharenivo-fast-social-sharing' ), 'grow' => __( 'Grow', 'sharenivo-fast-social-sharing' ), 'slide' => __( 'Icon slide', 'sharenivo-fast-social-sharing' ), 'glow' => __( 'Glow', 'sharenivo-fast-social-sharing' ), 'tilt' => __( 'Tilt', 'sharenivo-fast-social-sharing' ), 'pulse' => __( 'Pulse', 'sharenivo-fast-social-sharing' ), 'twist' => __( 'Icon twist', 'sharenivo-fast-social-sharing' ), 'none' => __( 'None', 'sharenivo-fast-social-sharing' ) ) ); ?>
							<?php self::render_select_field( __( 'Entrance effect', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[style][entrance]', $settings['style']['entrance'], array( 'fade' => __( 'Fade', 'sharenivo-fast-social-sharing' ), 'slide' => __( 'Slide', 'sharenivo-fast-social-sharing' ), 'zoom' => __( 'Zoom', 'sharenivo-fast-social-sharing' ), 'none' => __( 'None', 'sharenivo-fast-social-sharing' ) ) ); ?>
							<div class="sharenivo-field"><label><?php esc_html_e( 'Gap (px)', 'sharenivo-fast-social-sharing' ); ?><input type="number" min="0" max="30" name="sharenivo_settings[style][gap]" value="<?php echo esc_attr( $settings['style']['gap'] ); ?>"></label></div>
							<div class="sharenivo-field"><label><?php esc_html_e( 'Visible before More', 'sharenivo-fast-social-sharing' ); ?><input type="number" min="3" max="12" name="sharenivo_settings[style][max_visible]" value="<?php echo esc_attr( $settings['style']['max_visible'] ); ?>"></label></div>
							<?php self::render_toggle( 'sharenivo_settings[style][show_labels]', $settings['style']['show_labels'], __( 'Show network labels', 'sharenivo-fast-social-sharing' ) ); ?>
							<?php self::render_toggle( 'sharenivo_settings[style][more_button]', $settings['style']['more_button'], __( 'Use compact More menu', 'sharenivo-fast-social-sharing' ) ); ?>
						</div><div class="sharenivo-preview"><span><?php esc_html_e( 'Live preview', 'sharenivo-fast-social-sharing' ); ?></span><div class="sharenivo-preview__canvas"><div class="sharenivo-preview__buttons"><i class="is-facebook"><?php echo wp_kses( Networks::get_icon( 'facebook' ), self::svg_allowed_html() ); ?></i><i class="is-x"><?php echo wp_kses( Networks::get_icon( 'x' ), self::svg_allowed_html() ); ?></i><i class="is-linkedin"><?php echo wp_kses( Networks::get_icon( 'linkedin' ), self::svg_allowed_html() ); ?></i></div></div></div></div>
					</section>

					<section class="sharenivo-panel" data-panel="follow" hidden>
						<div class="sharenivo-panel__heading"><div><span class="sharenivo-eyebrow"><?php esc_html_e( 'Audience growth', 'sharenivo-fast-social-sharing' ); ?></span><h2><?php esc_html_e( 'Social follow profiles', 'sharenivo-fast-social-sharing' ); ?></h2><p><?php esc_html_e( 'Profile links are rendered locally through the Follow block, widget, shortcode, or action.', 'sharenivo-fast-social-sharing' ); ?></p></div></div>
						<div class="sharenivo-card sharenivo-fields">
							<?php self::render_toggle( 'sharenivo_settings[follow][enabled]', $settings['follow']['enabled'], __( 'Enable follow tools', 'sharenivo-fast-social-sharing' ) ); ?>
							<div class="sharenivo-field"><label><?php esc_html_e( 'Heading', 'sharenivo-fast-social-sharing' ); ?><input type="text" name="sharenivo_settings[follow][heading]" value="<?php echo esc_attr( $settings['follow']['heading'] ); ?>"></label></div>
							<div class="sharenivo-profile-grid">
							<?php foreach ( $follow_networks as $network => $data ) : ?>
								<label><span style="--network-color:<?php echo esc_attr( $data['color'] ); ?>"><?php echo wp_kses( Networks::get_icon( $network ), self::svg_allowed_html() ); ?></span><b><?php echo esc_html( $data['label'] ); ?></b><input type="url" name="sharenivo_settings[follow][profiles][<?php echo esc_attr( $network ); ?>]" value="<?php echo esc_attr( $settings['follow']['profiles'][ $network ] ?? '' ); ?>" placeholder="https://"></label>
							<?php endforeach; ?>
							</div>
							<p class="description"><?php esc_html_e( 'Use [sharenivo_follow] or the ShareNivo Follow widget. Empty profiles are never rendered.', 'sharenivo-fast-social-sharing' ); ?></p>
						</div>
					</section>

					<section class="sharenivo-panel" data-panel="advanced" hidden>
						<div class="sharenivo-panel__heading"><div><span class="sharenivo-eyebrow"><?php esc_html_e( 'Rules and portability', 'sharenivo-fast-social-sharing' ); ?></span><h2><?php esc_html_e( 'Advanced settings', 'sharenivo-fast-social-sharing' ); ?></h2></div></div>
						<div class="sharenivo-card sharenivo-fields">
							<h3><?php esc_html_e( 'Content types', 'sharenivo-fast-social-sharing' ); ?></h3><div class="sharenivo-checkbox-grid">
							<?php foreach ( $post_types as $post_type ) : ?><label><input type="checkbox" name="sharenivo_settings[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $settings['post_types'], true ) ); ?>> <?php echo esc_html( $post_type->labels->singular_name ); ?></label><?php endforeach; ?>
							</div>
							<?php self::render_toggle( 'sharenivo_settings[show_on_homepage]', $settings['show_on_homepage'], __( 'Allow automatic buttons on the front page', 'sharenivo-fast-social-sharing' ) ); ?>
							<div class="sharenivo-portability"><div><label><?php esc_html_e( 'Export configuration', 'sharenivo-fast-social-sharing' ); ?><textarea class="sharenivo-export" rows="8" readonly><?php echo esc_textarea( wp_json_encode( $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea></label><button type="button" class="button sharenivo-copy-export"><?php esc_html_e( 'Copy JSON', 'sharenivo-fast-social-sharing' ); ?></button></div><div><label><?php esc_html_e( 'Import configuration', 'sharenivo-fast-social-sharing' ); ?><textarea name="sharenivo_import_json" rows="8" placeholder="{ }"></textarea></label><p class="description"><?php esc_html_e( 'Paste a ShareNivo 2.x JSON export. Imported values are validated before saving.', 'sharenivo-fast-social-sharing' ); ?></p></div></div>
						</div>
					</section>

					<footer class="sharenivo-savebar"><span class="sharenivo-savebar__message" role="status" aria-live="polite"></span><button type="submit" class="button button-primary sharenivo-save-button"><?php esc_html_e( 'Save ShareNivo settings', 'sharenivo-fast-social-sharing' ); ?></button></footer>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX settings save handler.
	 */
	public static function ajax_save_settings() {
		check_ajax_referer( 'sharenivo_save_settings', 'sharenivo_settings_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to change these settings.', 'sharenivo-fast-social-sharing' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The full nested array is validated by sanitize_settings().
		$input = isset( $_POST['sharenivo_settings'] ) && is_array( $_POST['sharenivo_settings'] ) ? wp_unslash( $_POST['sharenivo_settings'] ) : array();
		$import_json = isset( $_POST['sharenivo_import_json'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sharenivo_import_json'] ) ) : '';

		if ( '' !== trim( $import_json ) ) {
			$imported = json_decode( $import_json, true );
			if ( ! is_array( $imported ) ) {
				wp_send_json_error( array( 'message' => __( 'The import JSON is invalid.', 'sharenivo-fast-social-sharing' ) ), 400 );
			}
			$input = $imported;
		}

		$settings = self::sanitize_settings( $input );
		update_option( 'sharenivo_settings', $settings, false );
		update_option( 'sharenivo_schema_version', SHARENIVO_SCHEMA_VERSION, false );

		wp_send_json_success(
			array(
				'message' => __( 'Settings saved.', 'sharenivo-fast-social-sharing' ),
				'export'  => wp_json_encode( $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
			)
		);
	}

	/**
	 * Non-JavaScript settings save fallback.
	 */
	public static function save_settings_redirect() {
		check_admin_referer( 'sharenivo_save_settings', 'sharenivo_settings_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'sharenivo-fast-social-sharing' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The full nested array is validated below.
		$input       = isset( $_POST['sharenivo_settings'] ) && is_array( $_POST['sharenivo_settings'] ) ? wp_unslash( $_POST['sharenivo_settings'] ) : array();
		$import_json = isset( $_POST['sharenivo_import_json'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sharenivo_import_json'] ) ) : '';
		if ( '' !== trim( $import_json ) ) {
			$imported = json_decode( $import_json, true );
			if ( ! is_array( $imported ) ) {
				wp_die( esc_html__( 'The import JSON is invalid.', 'sharenivo-fast-social-sharing' ) );
			}
			$input = $imported;
		}

		update_option( 'sharenivo_settings', self::sanitize_settings( $input ), false );
		update_option( 'sharenivo_schema_version', SHARENIVO_SCHEMA_VERSION, false );
		wp_safe_redirect( add_query_arg( 'sharenivo-updated', '1', admin_url( 'options-general.php?page=sharenivo' ) ) );
		exit;
	}

	/**
	 * Count enabled locations.
	 *
	 * @param array $locations Locations.
	 * @return int
	 */
	private static function count_active_locations( $locations ) {
		$count = 0;
		foreach ( $locations as $location ) {
			if ( ! empty( $location['enabled'] ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Render a toggle control.
	 *
	 * @param string $name    Field name.
	 * @param bool   $checked Checked state.
	 * @param string $label   Label.
	 */
	private static function render_toggle( $name, $checked, $label ) {
		?>
		<label class="sharenivo-toggle"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>><span aria-hidden="true"></span><b><?php echo esc_html( $label ); ?></b></label>
		<?php
	}

	/**
	 * Render a select field.
	 *
	 * @param string $label   Label.
	 * @param string $name    Field name.
	 * @param string $value   Current value.
	 * @param array  $options Options.
	 */
	private static function render_select_field( $label, $name, $value, $options ) {
		?>
		<div class="sharenivo-field"><label><?php echo esc_html( $label ); ?><select name="<?php echo esc_attr( $name ); ?>"><?php foreach ( $options as $option => $option_label ) : ?><option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( $option_label ); ?></option><?php endforeach; ?></select></label></div>
		<?php
	}

	/**
	 * Render one location configuration card.
	 *
	 * @param string $key      Location key.
	 * @param string $title    Card title.
	 * @param string $summary  Card summary.
	 * @param array  $settings Settings.
	 */
	private static function render_location_card( $key, $title, $summary, $settings ) {
		$value = $settings['locations'][ $key ];
		?>
		<article class="sharenivo-location-card<?php echo esc_attr( ! empty( $value['enabled'] ) ? ' is-enabled' : '' ); ?>" data-location-card="<?php echo esc_attr( $key ); ?>">
			<header><div><span class="sharenivo-location-card__number"><?php echo esc_html( strtoupper( substr( $key, 0, 2 ) ) ); ?></span><h3><?php echo esc_html( $title ); ?></h3></div><?php self::render_toggle( 'sharenivo_settings[locations][' . $key . '][enabled]', ! empty( $value['enabled'] ), __( 'Enabled', 'sharenivo-fast-social-sharing' ) ); ?></header>
			<p><?php echo esc_html( $summary ); ?></p><div class="sharenivo-location-card__settings">
			<?php if ( 'floating' === $key ) : ?>
				<?php self::render_select_field( __( 'Side', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][floating][side]', $value['side'], array( 'left' => __( 'Left', 'sharenivo-fast-social-sharing' ), 'right' => __( 'Right', 'sharenivo-fast-social-sharing' ) ) ); ?>
				<div class="sharenivo-field"><label><?php esc_html_e( 'Vertical position (%)', 'sharenivo-fast-social-sharing' ); ?><input type="number" min="10" max="90" name="sharenivo_settings[locations][floating][vertical]" value="<?php echo esc_attr( $value['vertical'] ); ?>"></label></div>
				<?php self::render_toggle( 'sharenivo_settings[locations][floating][hide_mobile]', $value['hide_mobile'], __( 'Hide rail on mobile', 'sharenivo-fast-social-sharing' ) ); ?>
			<?php elseif ( 'inline' === $key ) : ?>
				<?php self::render_select_field( __( 'Content position', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][inline][position]', $value['position'], array( 'top' => __( 'Above', 'sharenivo-fast-social-sharing' ), 'bottom' => __( 'Below', 'sharenivo-fast-social-sharing' ), 'both' => __( 'Above and below', 'sharenivo-fast-social-sharing' ) ) ); ?>
				<?php self::render_select_field( __( 'Alignment', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][inline][alignment]', $value['alignment'], array( 'left' => __( 'Left', 'sharenivo-fast-social-sharing' ), 'center' => __( 'Center', 'sharenivo-fast-social-sharing' ), 'right' => __( 'Right', 'sharenivo-fast-social-sharing' ) ) ); ?>
			<?php elseif ( 'sticky' === $key ) : ?>
				<?php self::render_select_field( __( 'Bar width', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][sticky][width]', $value['width'], array( 'compact' => __( 'Fit buttons', 'sharenivo-fast-social-sharing' ), 'full' => __( 'Full screen', 'sharenivo-fast-social-sharing' ) ) ); ?>
				<?php self::render_select_field( __( 'Bar alignment', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][sticky][alignment]', $value['alignment'], array( 'left' => __( 'Left', 'sharenivo-fast-social-sharing' ), 'center' => __( 'Center', 'sharenivo-fast-social-sharing' ), 'right' => __( 'Right', 'sharenivo-fast-social-sharing' ) ) ); ?>
				<p class="description"><?php esc_html_e( 'Alignment positions a fitted bar. Full-screen bars use equal responsive columns and wrap when needed instead of scrolling.', 'sharenivo-fast-social-sharing' ); ?></p>
			<?php elseif ( in_array( $key, array( 'popup', 'flyin' ), true ) ) : ?>
				<div class="sharenivo-field"><label><?php esc_html_e( 'Title', 'sharenivo-fast-social-sharing' ); ?><input type="text" name="sharenivo_settings[locations][<?php echo esc_attr( $key ); ?>][title]" value="<?php echo esc_attr( $value['title'] ); ?>"></label></div>
				<div class="sharenivo-field"><label><?php esc_html_e( 'Message', 'sharenivo-fast-social-sharing' ); ?><textarea rows="3" name="sharenivo_settings[locations][<?php echo esc_attr( $key ); ?>][message]"><?php echo esc_textarea( $value['message'] ); ?></textarea></label></div>
				<?php if ( 'flyin' === $key ) { self::render_select_field( __( 'Corner', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][flyin][side]', $value['side'], array( 'left' => __( 'Bottom left', 'sharenivo-fast-social-sharing' ), 'right' => __( 'Bottom right', 'sharenivo-fast-social-sharing' ) ) ); } ?>
				<?php self::render_select_field( __( 'Trigger', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][' . $key . '][trigger]', $value['trigger'], array( 'delay' => __( 'Time delay', 'sharenivo-fast-social-sharing' ), 'scroll' => __( 'Scroll percentage', 'sharenivo-fast-social-sharing' ), 'bottom' => __( 'Bottom of content', 'sharenivo-fast-social-sharing' ), 'inactivity' => __( 'Inactivity', 'sharenivo-fast-social-sharing' ), 'exit' => __( 'Desktop exit intent', 'sharenivo-fast-social-sharing' ), 'comment' => __( 'After commenting', 'sharenivo-fast-social-sharing' ), 'purchase' => __( 'After WooCommerce purchase', 'sharenivo-fast-social-sharing' ) ) ); ?>
				<div class="sharenivo-field"><label><?php esc_html_e( 'Trigger value', 'sharenivo-fast-social-sharing' ); ?><input type="number" min="1" max="600" name="sharenivo_settings[locations][<?php echo esc_attr( $key ); ?>][trigger_value]" value="<?php echo esc_attr( $value['trigger_value'] ); ?>"><small><?php esc_html_e( 'Seconds for delay/inactivity, percentage for scroll.', 'sharenivo-fast-social-sharing' ); ?></small></label></div>
				<?php self::render_select_field( __( 'Frequency', 'sharenivo-fast-social-sharing' ), 'sharenivo_settings[locations][' . $key . '][frequency]', $value['frequency'], array( 'always' => __( 'Every page view', 'sharenivo-fast-social-sharing' ), 'session' => __( 'Once per session', 'sharenivo-fast-social-sharing' ), 'day' => __( 'Once per day', 'sharenivo-fast-social-sharing' ), 'week' => __( 'Once per week', 'sharenivo-fast-social-sharing' ) ) ); ?>
			<?php elseif ( 'media' === $key ) : ?>
				<div class="sharenivo-field"><label><?php esc_html_e( 'Minimum image width (px)', 'sharenivo-fast-social-sharing' ); ?><input type="number" min="120" max="1200" name="sharenivo_settings[locations][media][min_width]" value="<?php echo esc_attr( $value['min_width'] ); ?>"></label></div>
				<div class="sharenivo-checkbox-grid"><?php foreach ( array( 'pinterest', 'facebook', 'x', 'copy' ) as $network ) : ?><label><input type="checkbox" name="sharenivo_settings[locations][media][networks][]" value="<?php echo esc_attr( $network ); ?>" <?php checked( in_array( $network, $value['networks'], true ) ); ?>> <?php echo esc_html( Networks::get_share_networks()[ $network ]['label'] ); ?></label><?php endforeach; ?></div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Uses the global network and design settings.', 'sharenivo-fast-social-sharing' ); ?></p>
			<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Allowed markup for local SVG icons in admin.
	 *
	 * @return array
	 */
	private static function svg_allowed_html() {
		return array(
			'svg'  => array( 'viewbox' => true, 'aria-hidden' => true, 'focusable' => true ),
			'path' => array( 'd' => true ),
			'circle' => array( 'cx' => true, 'cy' => true, 'r' => true ),
		);
	}
}
