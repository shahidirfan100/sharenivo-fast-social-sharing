<?php
/**
 * Settings page functionality.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Settings {

	/**
	 * Get default plugin settings.
	 *
	 * @return array
	 */
	private static function get_default_settings() {
		$default_networks = array( 'facebook', 'x', 'linkedin', 'whatsapp', 'pinterest', 'threads', 'bluesky', 'telegram', 'reddit', 'email' );

		return array(
			'enabled'            => true,
			'networks'           => $default_networks,
			'network_order'      => $default_networks,
			'post_types'         => array( 'post', 'page' ),
			'show_on_homepage'   => false,
			'auto_detect_post_types' => true,
			'position'           => 'floating_left',
			'mobile_position'    => 'sticky_bottom',
			'button_shape'       => 'circle',
			'button_size'        => 'medium',
			'show_more_network_button' => true,
			'color_scheme'       => 'brand',
			'hover_animation'    => 'lift',
			'custom_bg_color'    => '#000000',
			'custom_icon_color'  => '#ffffff',
			'custom_hover_bg'    => '#434343',
			'button_gap'         => 10,
			'margin_top'         => 20,
			'margin_bottom'      => 20,
			'enable_animations'  => true,
			'show_share_counts'  => false,
			'share_count_mode'   => 'total',
			'share_count_cache_ttl' => 60,
			'sharedcount_api_key' => '',
			'custom_css'         => '',
			'inline_alignment'   => 'left',
		);
	}

	/**
	 * Get list of allowed social networks.
	 *
	 * @return array
	 */
	public static function get_allowed_networks() {
		return array(
			'facebook'  => __( 'Facebook', 'sharenivo-fast-social-sharing' ),
			'x'         => __( 'X (Twitter)', 'sharenivo-fast-social-sharing' ),
			'linkedin'  => __( 'LinkedIn', 'sharenivo-fast-social-sharing' ),
			'whatsapp'  => __( 'WhatsApp', 'sharenivo-fast-social-sharing' ),
			'pinterest' => __( 'Pinterest', 'sharenivo-fast-social-sharing' ),
			'threads'   => __( 'Threads', 'sharenivo-fast-social-sharing' ),
			'bluesky'   => __( 'Bluesky', 'sharenivo-fast-social-sharing' ),
			'telegram'  => __( 'Telegram', 'sharenivo-fast-social-sharing' ),
			'reddit'    => __( 'Reddit', 'sharenivo-fast-social-sharing' ),
			'email'     => __( 'Email', 'sharenivo-fast-social-sharing' ),
		);
	}

	/**
	 * Sort networks by preferred order and append missing items.
	 *
	 * @param array $networks Active networks.
	 * @param array $order    Preferred order.
	 * @return array
	 */
	public static function sort_networks_by_order( $networks, $order ) {
		$networks = is_array( $networks ) ? array_values( array_unique( array_map( 'sanitize_key', $networks ) ) ) : array();
		$order = is_array( $order ) ? array_values( array_unique( array_map( 'sanitize_key', $order ) ) ) : array();

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
	 * Get plugin settings.
	 *
	 * @return array Settings array.
	 */
	public static function get_settings() {
		$defaults = self::get_default_settings();
		$settings = get_option( 'sharenivo_settings', false );

		// Migrate the old option once, without deleting it. This keeps upgrades
		// safe for existing installations and third-party integrations.
		if ( false === $settings ) {
			$settings = get_option( 'sharenova_settings', $defaults );
			if ( is_array( $settings ) && false === get_option( 'sharenivo_settings', false ) ) {
				add_option( 'sharenivo_settings', $settings, '', 'no' );
			}
		}

		$settings = wp_parse_args( $settings, $defaults );
		if ( isset( $settings['button_shape'] ) && in_array( $settings['button_shape'], array( 'portrait', 'landscape_rectangle' ), true ) ) {
			$settings['button_shape'] = 'landscape';
		}
		$settings['networks'] = self::sort_networks_by_order( $settings['networks'], $settings['network_order'] );
		$settings['network_order'] = $settings['networks'];

		/**
		 * Filter plugin settings before use.
		 *
		 * @param array $settings Current settings.
		 */
		$filtered_settings = apply_filters( 'sharenivo_settings', $settings );
		$filtered_settings = apply_filters( 'sharenova_settings', $filtered_settings );
		if ( ! is_array( $filtered_settings ) ) {
			return $settings;
		}

		$filtered_settings = wp_parse_args( $filtered_settings, $defaults );
		$filtered_settings['networks'] = self::sort_networks_by_order( $filtered_settings['networks'], $filtered_settings['network_order'] );
		$filtered_settings['network_order'] = $filtered_settings['networks'];

		return $filtered_settings;
	}

	/**
	 * Render settings page.
	 */
	public function render_page() {
		// Handle form submission.
		if ( isset( $_POST['sharenivo_save_settings'] ) ) {
			if ( ! isset( $_POST['sharenivo_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sharenivo_settings_nonce'] ) ), 'sharenivo_save_settings' ) ) {
				wp_die( esc_html__( 'Security check failed', 'sharenivo-fast-social-sharing' ) );
			}
			$this->save_settings();
		}

		$active_tab = isset( $_POST['sharenivo_active_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['sharenivo_active_tab'] ) ) : '#general';

		$settings = self::get_settings();
		$ordered_networks = self::sort_networks_by_order( $settings['networks'], $settings['network_order'] );
		?>
		<div class="wrap sharenivo-settings-wrap">
			<div class="sharenivo-page-header">
				<div class="sharenivo-header-info">
					<h1><?php echo esc_html__( 'ShareNivo', 'sharenivo-fast-social-sharing' ); ?></h1>
					<p class="sharenivo-header-subtitle"><?php esc_html_e( 'Fast, privacy-first sharing with a focused setup experience.', 'sharenivo-fast-social-sharing' ); ?></p>
				</div>
				<div class="sharenivo-header-badge"><?php echo esc_html__( 'Version', 'sharenivo-fast-social-sharing' ) . ' ' . esc_html( SHARENIVO_VERSION ); ?></div>
			</div>
			
			<div class="sharenivo-layout">
				<aside class="sharenivo-sidebar">
					<div class="sharenivo-sidebar-header"><?php esc_html_e( 'Settings', 'sharenivo-fast-social-sharing' ); ?></div>
					<nav class="sharenivo-tab-nav">
						<a href="#general" class="sharenivo-tab-link active">
							<svg class="sharenivo-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
							<?php esc_html_e( 'General', 'sharenivo-fast-social-sharing' ); ?>
						</a>
						<a href="#display" class="sharenivo-tab-link">
							<svg class="sharenivo-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
							<?php esc_html_e( 'Display', 'sharenivo-fast-social-sharing' ); ?>
						</a>
						<a href="#style" class="sharenivo-tab-link">
							<svg class="sharenivo-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
							<?php esc_html_e( 'Style', 'sharenivo-fast-social-sharing' ); ?>
						</a>
						<a href="#advanced" class="sharenivo-tab-link">
							<svg class="sharenivo-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
							<?php esc_html_e( 'Advanced', 'sharenivo-fast-social-sharing' ); ?>
						</a>
					</nav>
				</aside>

				<main class="sharenivo-main">
					<form method="post" action="" class="sharenivo-form-wrap">
						<?php wp_nonce_field( 'sharenivo_save_settings', 'sharenivo_settings_nonce' ); ?>
						<input type="hidden" name="sharenivo_save_settings" value="1" />
						<input type="hidden" name="sharenivo_active_tab" id="sharenivo_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

					<!-- General Tab -->
					<div id="general" class="sharenivo-tab-content active">
						<div class="sharenivo-panel-header">
							<div class="sharenivo-panel-icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
							</div>
							<div>
								<h3 class="sharenivo-panel-title"><?php esc_html_e( 'General Settings', 'sharenivo-fast-social-sharing' ); ?></h3>
								<p class="sharenivo-panel-desc"><?php esc_html_e( 'Core configuration and network selection.', 'sharenivo-fast-social-sharing' ); ?></p>
							</div>
						</div>
						
						<div class="sharenivo-panel-body">
							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Master Switch', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Enable or disable all sharing features.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<label class="sharenivo-toggle-wrap">
										<div class="sharenivo-toggle">
											<input type="checkbox" name="sharenivo_settings[enabled]" value="1" <?php checked( $settings['enabled'], true ); ?> />
											<div class="sharenivo-toggle-slider"></div>
										</div>
										<span class="sharenivo-toggle-label"><?php esc_html_e( 'Enable Plugin', 'sharenivo-fast-social-sharing' ); ?></span>
									</label>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Active Networks', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Select networks to display on your site.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<div class="sharenivo-network-chips">
										<?php
										$available_networks = self::get_allowed_networks();
										foreach ( $available_networks as $key => $label ) :
											$checked = in_array( $key, $settings['networks'], true );
											?>
											<label class="sharenivo-network-chip <?php echo esc_attr( $checked ? 'sharenivo-chip-active' : '' ); ?>">
												<span class="sharenivo-chip-dot"></span>
												<span class="sharenivo-chip-text"><?php echo esc_html( $label ); ?></span>
												<input type="checkbox" class="sharenivo-network-checkbox" data-network-label="<?php echo esc_attr( wp_strip_all_tags( $label ) ); ?>" name="sharenivo_settings[networks][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked, true ); ?> />
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Network Order', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Drag to arrange. First 4 show by default.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<ul id="sharenivo-network-order-list" class="sharenivo-network-order-list">
										<?php foreach ( $ordered_networks as $network ) : ?>
											<?php
											$network = sanitize_key( $network );
											if ( ! isset( $available_networks[ $network ] ) ) {
												continue;
											}
											?>
											<li data-network="<?php echo esc_attr( $network ); ?>">
												<span class="sharenivo-order-drag">&#9783;</span>
												<span class="sharenivo-order-label"><?php echo esc_html( $available_networks[ $network ] ); ?></span>
												<button type="button" class="sharenivo-order-btn sharenivo-order-up" aria-label="<?php esc_attr_e( 'Move up', 'sharenivo-fast-social-sharing' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg></button>
												<button type="button" class="sharenivo-order-btn sharenivo-order-down" aria-label="<?php esc_attr_e( 'Move down', 'sharenivo-fast-social-sharing' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></button>
											</li>
										<?php endforeach; ?>
									</ul>
									<input type="hidden" id="sharenivo-network-order" name="sharenivo_settings[network_order]" value="<?php echo esc_attr( implode( ',', $ordered_networks ) ); ?>" />
								</div>
							</div>

							<div class="sharenivo-field-row sharenivo-more-button-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Smart Expand', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Hide excess networks behind a menu.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<label class="sharenivo-toggle-wrap">
										<div class="sharenivo-toggle">
											<input type="checkbox" name="sharenivo_settings[show_more_network_button]" value="1" <?php checked( $settings['show_more_network_button'], true ); ?> />
											<div class="sharenivo-toggle-slider"></div>
										</div>
										<span class="sharenivo-toggle-label"><?php esc_html_e( 'Enable compact More button', 'sharenivo-fast-social-sharing' ); ?></span>
									</label>
									<p class="sharenivo-description"><?php esc_html_e( 'Only applies when 5 or more networks are selected. Otherwise, it hides automatically.', 'sharenivo-fast-social-sharing' ); ?></p>
								</div>
							</div>
						</div>
					</div>

					<!-- Display Tab -->
					<div id="display" class="sharenivo-tab-content">
						<div class="sharenivo-panel-header">
							<div class="sharenivo-panel-icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
							</div>
							<div>
								<h3 class="sharenivo-panel-title"><?php esc_html_e( 'Display Settings', 'sharenivo-fast-social-sharing' ); ?></h3>
								<p class="sharenivo-panel-desc"><?php esc_html_e( 'Control where and how the buttons appear.', 'sharenivo-fast-social-sharing' ); ?></p>
							</div>
						</div>
						
						<div class="sharenivo-panel-body">
							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Locations', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Select which pages show buttons.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<div class="sharenivo-network-chips">
										<label class="sharenivo-network-chip <?php echo esc_attr( ! empty( $settings['show_on_homepage'] ) ? 'sharenivo-chip-active' : '' ); ?>">
											<span class="sharenivo-chip-dot"></span>
											<span class="sharenivo-chip-text"><?php esc_html_e( 'Homepage (Front Page)', 'sharenivo-fast-social-sharing' ); ?></span>
											<input type="checkbox" name="sharenivo_settings[show_on_homepage]" value="1" <?php checked( ! empty( $settings['show_on_homepage'] ), true ); ?> />
										</label>

										<?php
										$post_types = get_post_types( array( 'public' => true ), 'objects' );
										foreach ( $post_types as $post_type ) :
											if ( 'attachment' === $post_type->name ) {
												continue;
											}
											$checked = in_array( $post_type->name, $settings['post_types'], true );
											?>
											<label class="sharenivo-network-chip <?php echo esc_attr( $checked ? 'sharenivo-chip-active' : '' ); ?>">
												<span class="sharenivo-chip-dot"></span>
												<span class="sharenivo-chip-text"><?php echo esc_html( $post_type->label ); ?></span>
												<input type="checkbox" name="sharenivo_settings[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( $checked, true ); ?> />
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Auto Detect', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Future post types support.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<label class="sharenivo-toggle-wrap">
										<div class="sharenivo-toggle">
											<input type="checkbox" name="sharenivo_settings[auto_detect_post_types]" value="1" <?php checked( $settings['auto_detect_post_types'], true ); ?> />
											<div class="sharenivo-toggle-slider"></div>
										</div>
										<span class="sharenivo-toggle-label"><?php esc_html_e( 'Include new Custom Post Types automatically', 'sharenivo-fast-social-sharing' ); ?></span>
									</label>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Desktop Position', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Where to show on large screens.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<select name="sharenivo_settings[position]" class="sharenivo-select">
										<option value="floating_left" <?php selected( $settings['position'], 'floating_left' ); ?>><?php esc_html_e( 'Floating Left (Sidebar)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="floating_right" <?php selected( $settings['position'], 'floating_right' ); ?>><?php esc_html_e( 'Floating Right (Sidebar)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="inline_top" <?php selected( $settings['position'], 'inline_top' ); ?>><?php esc_html_e( 'Inline Top (Above Content)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="inline_bottom" <?php selected( $settings['position'], 'inline_bottom' ); ?>><?php esc_html_e( 'Inline Bottom (Below Content)', 'sharenivo-fast-social-sharing' ); ?></option>
									</select>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Mobile Position', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Where to show on small screens.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<select name="sharenivo_settings[mobile_position]" class="sharenivo-select">
										<option value="sticky_bottom" <?php selected( $settings['mobile_position'], 'sticky_bottom' ); ?>><?php esc_html_e( 'Sticky Bottom Bar (Recommended)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="inline" <?php selected( $settings['mobile_position'], 'inline' ); ?>><?php esc_html_e( 'Inline (Matches Desktop Layout)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="hidden" <?php selected( $settings['mobile_position'], 'hidden' ); ?>><?php esc_html_e( 'Hidden on Mobile', 'sharenivo-fast-social-sharing' ); ?></option>
									</select>
								</div>
							</div>

							<div class="sharenivo-field-row sharenivo-inline-alignment-row" style="<?php echo esc_attr( in_array( $settings['position'], array( 'inline_top', 'inline_bottom' ), true ) ? '' : 'display:none;' ); ?>">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Inline Alignment', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Align buttons for top/bottom positions.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<select name="sharenivo_settings[inline_alignment]" class="sharenivo-select">
										<option value="left" <?php selected( $settings['inline_alignment'] ?? 'left', 'left' ); ?>><?php esc_html_e( 'Left', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="center" <?php selected( $settings['inline_alignment'] ?? 'left', 'center' ); ?>><?php esc_html_e( 'Center', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="right" <?php selected( $settings['inline_alignment'] ?? 'left', 'right' ); ?>><?php esc_html_e( 'Right', 'sharenivo-fast-social-sharing' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<!-- Style Tab -->
					<div id="style" class="sharenivo-tab-content">
						
						<div class="sharenivo-panel-header">
							<div class="sharenivo-panel-icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
							</div>
							<div>
								<h3 class="sharenivo-panel-title"><?php esc_html_e( 'Style Settings', 'sharenivo-fast-social-sharing' ); ?></h3>
								<p class="sharenivo-panel-desc"><?php esc_html_e( 'Customize shape, size, colors, and animations.', 'sharenivo-fast-social-sharing' ); ?></p>
							</div>
						</div>
						
						<div class="sharenivo-panel-body">
							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Button Shape', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<select name="sharenivo_settings[button_shape]" class="sharenivo-select">
										<option value="circle" <?php selected( $settings['button_shape'], 'circle' ); ?>><?php esc_html_e( 'Perfect Circle', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="rounded" <?php selected( $settings['button_shape'], 'rounded' ); ?>><?php esc_html_e( 'Rounded Corners', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="landscape" <?php selected( $settings['button_shape'], 'landscape' ); ?>><?php esc_html_e( 'Landscape Rectangle', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="square" <?php selected( $settings['button_shape'], 'square' ); ?>><?php esc_html_e( 'Sharp Square', 'sharenivo-fast-social-sharing' ); ?></option>
									</select>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Button Size', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<select name="sharenivo_settings[button_size]" class="sharenivo-select">
										<option value="small" <?php selected( $settings['button_size'], 'small' ); ?>><?php esc_html_e( 'Small', 'sharenivo-fast-social-sharing' ); ?> (40px)</option>
										<option value="medium" <?php selected( $settings['button_size'], 'medium' ); ?>><?php esc_html_e( 'Medium', 'sharenivo-fast-social-sharing' ); ?> (50px)</option>
										<option value="large" <?php selected( $settings['button_size'], 'large' ); ?>><?php esc_html_e( 'Large', 'sharenivo-fast-social-sharing' ); ?> (60px)</option>
									</select>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Color Scheme', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<select name="sharenivo_settings[color_scheme]" id="sharenivo-color-scheme" class="sharenivo-select">
										<option value="brand" <?php selected( $settings['color_scheme'], 'brand' ); ?>><?php esc_html_e( 'Official Brand Colors', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="custom" <?php selected( $settings['color_scheme'], 'custom' ); ?>><?php esc_html_e( 'Custom Solid Colors', 'sharenivo-fast-social-sharing' ); ?></option>
									</select>
								</div>
							</div>

							<div class="sharenivo-field-row sharenivo-custom-color-row" style="<?php echo esc_attr( ( 'custom' !== $settings['color_scheme'] ) ? 'display:none;' : '' ); ?>">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Custom Colors', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<div class="sharenivo-color-row">
										<input type="text" name="sharenivo_settings[custom_bg_color]" value="<?php echo esc_attr( $settings['custom_bg_color'] ); ?>" class="sharenivo-color-picker" data-default-color="#000000" />
										<span class="sharenivo-unit"><?php esc_html_e( 'Background', 'sharenivo-fast-social-sharing' ); ?></span>
									</div>
									<div class="sharenivo-color-row" style="margin-top: 10px;">
										<input type="text" name="sharenivo_settings[custom_icon_color]" value="<?php echo esc_attr( $settings['custom_icon_color'] ); ?>" class="sharenivo-color-picker" data-default-color="#ffffff" />
										<span class="sharenivo-unit"><?php esc_html_e( 'Icon', 'sharenivo-fast-social-sharing' ); ?></span>
									</div>
									<div class="sharenivo-color-row" style="margin-top: 10px;">
										<input type="text" name="sharenivo_settings[custom_hover_bg]" value="<?php echo esc_attr( $settings['custom_hover_bg'] ); ?>" class="sharenivo-color-picker" data-default-color="#434343" />
										<span class="sharenivo-unit"><?php esc_html_e( 'Hover', 'sharenivo-fast-social-sharing' ); ?></span>
									</div>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Spacing', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Gap between and margins around.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control" style="display: flex; gap: 15px; flex-wrap: wrap;">
									<div class="sharenivo-input-unit">
										<input type="number" name="sharenivo_settings[button_gap]" value="<?php echo esc_attr( $settings['button_gap'] ); ?>" min="0" max="50" class="sharenivo-input sharenivo-input-number" />
										<span class="sharenivo-unit">px Gap</span>
									</div>
									<div class="sharenivo-input-unit">
										<input type="number" name="sharenivo_settings[margin_top]" value="<?php echo esc_attr( $settings['margin_top'] ); ?>" min="0" max="100" class="sharenivo-input sharenivo-input-number" />
										<span class="sharenivo-unit">px Top</span>
									</div>
									<div class="sharenivo-input-unit">
										<input type="number" name="sharenivo_settings[margin_bottom]" value="<?php echo esc_attr( $settings['margin_bottom'] ); ?>" min="0" max="100" class="sharenivo-input sharenivo-input-number" />
										<span class="sharenivo-unit">px Bottom</span>
									</div>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Hover Animations', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Motion effects on interaction.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control" style="gap: 16px;">
									<label class="sharenivo-toggle-wrap">
										<div class="sharenivo-toggle">
											<input type="checkbox" name="sharenivo_settings[enable_animations]" value="1" <?php checked( $settings['enable_animations'], true ); ?> />
											<div class="sharenivo-toggle-slider"></div>
										</div>
										<span class="sharenivo-toggle-label"><?php esc_html_e( 'Enable CSS animations', 'sharenivo-fast-social-sharing' ); ?></span>
									</label>
									
									<select name="sharenivo_settings[hover_animation]" class="sharenivo-select">
										<option value="lift" <?php selected( $settings['hover_animation'], 'lift' ); ?>><?php esc_html_e( 'Lift (Move Up)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="pulse" <?php selected( $settings['hover_animation'], 'pulse' ); ?>><?php esc_html_e( 'Pulse (Grow)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="spin" <?php selected( $settings['hover_animation'], 'spin' ); ?>><?php esc_html_e( 'Spin (Rotate)', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="bounce" <?php selected( $settings['hover_animation'], 'bounce' ); ?>><?php esc_html_e( 'Bounce', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="flip" <?php selected( $settings['hover_animation'], 'flip' ); ?>><?php esc_html_e( 'Flip (3D)', 'sharenivo-fast-social-sharing' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<!-- Advanced Tab -->
					<div id="advanced" class="sharenivo-tab-content">
						<div class="sharenivo-panel-header">
							<div class="sharenivo-panel-icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
							</div>
							<div>
								<h3 class="sharenivo-panel-title"><?php esc_html_e( 'Advanced Settings', 'sharenivo-fast-social-sharing' ); ?></h3>
								<p class="sharenivo-panel-desc"><?php esc_html_e( 'Developer options and integrations.', 'sharenivo-fast-social-sharing' ); ?></p>
							</div>
						</div>
						
						<div class="sharenivo-panel-body">
							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Custom CSS', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Override default styles safely.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<textarea name="sharenivo_settings[custom_css]" rows="6" class="sharenivo-textarea" placeholder="/* Your CSS here */"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Share Counts', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Display number of shares.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<label class="sharenivo-toggle-wrap">
										<div class="sharenivo-toggle">
											<input type="checkbox" name="sharenivo_settings[show_share_counts]" value="1" <?php checked( $settings['show_share_counts'], true ); ?> />
											<div class="sharenivo-toggle-slider"></div>
										</div>
										<span class="sharenivo-toggle-label"><?php esc_html_e( 'Fetch and display counts', 'sharenivo-fast-social-sharing' ); ?></span>
									</label>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Count Settings', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control" style="display: flex; gap: 15px; flex-wrap: wrap;">
									<select name="sharenivo_settings[share_count_mode]" class="sharenivo-select" style="min-width: 150px;">
										<option value="total" <?php selected( $settings['share_count_mode'], 'total' ); ?>><?php esc_html_e( 'Total sum', 'sharenivo-fast-social-sharing' ); ?></option>
										<option value="network" <?php selected( $settings['share_count_mode'], 'network' ); ?>><?php esc_html_e( 'Per network', 'sharenivo-fast-social-sharing' ); ?></option>
									</select>

									<div class="sharenivo-input-unit">
										<input type="number" name="sharenivo_settings[share_count_cache_ttl]" value="<?php echo esc_attr( $settings['share_count_cache_ttl'] ); ?>" min="5" max="1440" class="sharenivo-input sharenivo-input-number" />
										<span class="sharenivo-unit">Min. Cache</span>
									</div>
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'SharedCount API', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Optional: For networks without public endpoints.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<input type="text" name="sharenivo_settings[sharedcount_api_key]" value="<?php echo esc_attr( $settings['sharedcount_api_key'] ); ?>" class="sharenivo-input" placeholder="API Key" />
								</div>
							</div>

							<div class="sharenivo-field-row">
								<div class="sharenivo-field-label">
									<span class="sharenivo-label-text"><?php esc_html_e( 'Shortcode', 'sharenivo-fast-social-sharing' ); ?></span>
									<span class="sharenivo-label-hint"><?php esc_html_e( 'Manual placement.', 'sharenivo-fast-social-sharing' ); ?></span>
								</div>
								<div class="sharenivo-field-control">
									<div class="sharenivo-shortcode-wrap">
										<code id="sharenivo-shortcode-text">[sharenivo_share]</code>
										<button type="button" class="sharenivo-btn sharenivo-btn-secondary sharenivo-copy-btn" data-clipboard-target="#sharenivo-shortcode-text">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
											<?php esc_html_e( 'Copy', 'sharenivo-fast-social-sharing' ); ?>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="sharenivo-form-footer">
						<button type="submit" name="sharenivo_save_settings" class="sharenivo-btn sharenivo-btn-primary sharenivo-save-btn">
							<svg class="sharenivo-btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
							<?php esc_html_e( 'Save Settings', 'sharenivo-fast-social-sharing' ); ?>
							<svg class="sharenivo-spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
						</button>
					</div>
				</form>
				</main>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings via AJAX.
	 */
	public static function ajax_save_settings() {
		$settings = new self();
		$settings->save_settings( true );
	}

	/**
	 * Save settings.
	 *
	 * @param bool $is_ajax Whether to return AJAX response.
	 */
	public function save_settings( $is_ajax = false ) {
		$nonce = isset( $_POST['sharenivo_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sharenivo_settings_nonce'] ) ) : '';

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'sharenivo_save_settings' ) ) {
			if ( $is_ajax ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Security check failed', 'sharenivo-fast-social-sharing' ) ) );
			}
			wp_die( esc_html__( 'Security check failed', 'sharenivo-fast-social-sharing' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			if ( $is_ajax ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to access this page.', 'sharenivo-fast-social-sharing' ) ) );
			}
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sharenivo-fast-social-sharing' ) );
		}

		$default_settings = self::get_default_settings();
		$allowed_networks = array_keys( self::get_allowed_networks() );
		$allowed_positions = array( 'floating_left', 'floating_right', 'inline_top', 'inline_bottom' );
		$allowed_mobile_positions = array( 'sticky_bottom', 'inline', 'hidden' );
		$allowed_shapes = array( 'circle', 'square', 'rounded', 'landscape', 'portrait', 'landscape_rectangle' );
		$allowed_sizes = array( 'small', 'medium', 'large' );
		$allowed_schemes = array( 'brand', 'custom' );
		$allowed_animations = array( 'lift', 'pulse', 'spin', 'bounce', 'flip' );
		$allowed_share_count_modes = array( 'total', 'network' );
		$allowed_alignments = array( 'left', 'center', 'right' );

		$settings = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( isset( $_POST['sharenivo_settings'] ) && is_array( $_POST['sharenivo_settings'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$settings = wp_unslash( $_POST['sharenivo_settings'] );
		}

		$selected_networks = array();
		if ( isset( $settings['networks'] ) && is_array( $settings['networks'] ) ) {
			$selected_networks = array_intersect(
				$allowed_networks,
				array_map( 'sanitize_text_field', $settings['networks'] )
			);
		} else if ( isset( $_POST['sharenivo_save_settings'] ) ) {
			// If form was submitted but no networks were selected, save an empty array rather than reverting to defaults.
			$selected_networks = array();
		} else {
			$selected_networks = $default_settings['networks'];
		}

		$submitted_network_order = array();
		if ( ! empty( $settings['network_order'] ) ) {
			if ( is_array( $settings['network_order'] ) ) {
				$submitted_network_order = array_map( 'sanitize_key', $settings['network_order'] );
			} else {
				$submitted_network_order = array_map(
					'sanitize_key',
					array_filter(
						array_map( 'trim', explode( ',', sanitize_text_field( $settings['network_order'] ) ) )
					)
				);
			}
		}
		$selected_networks = self::sort_networks_by_order( $selected_networks, $submitted_network_order );

		$selected_post_types = array();
		$public_post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $public_post_types['attachment'] );
		if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
			$selected_post_types = array_intersect(
				array_values( $public_post_types ),
				array_map( 'sanitize_text_field', $settings['post_types'] )
			);
		}
		if ( empty( $selected_post_types ) ) {
			$selected_post_types = $default_settings['post_types'];
		}

		$position = sanitize_text_field( $settings['position'] ?? $default_settings['position'] );
		if ( ! in_array( $position, $allowed_positions, true ) ) {
			$position = $default_settings['position'];
		}

		$mobile_position = sanitize_text_field( $settings['mobile_position'] ?? $default_settings['mobile_position'] );
		if ( ! in_array( $mobile_position, $allowed_mobile_positions, true ) ) {
			$mobile_position = $default_settings['mobile_position'];
		}

		$button_shape = sanitize_text_field( $settings['button_shape'] ?? $default_settings['button_shape'] );
		if ( in_array( $button_shape, array( 'portrait', 'landscape_rectangle' ), true ) ) {
			$button_shape = 'landscape';
		}
		if ( ! in_array( $button_shape, $allowed_shapes, true ) ) {
			$button_shape = $default_settings['button_shape'];
		}

		$button_size = sanitize_text_field( $settings['button_size'] ?? $default_settings['button_size'] );
		if ( ! in_array( $button_size, $allowed_sizes, true ) ) {
			$button_size = $default_settings['button_size'];
		}

		$color_scheme = sanitize_text_field( $settings['color_scheme'] ?? $default_settings['color_scheme'] );
		if ( ! in_array( $color_scheme, $allowed_schemes, true ) ) {
			$color_scheme = $default_settings['color_scheme'];
		}

		$hover_animation = sanitize_text_field( $settings['hover_animation'] ?? $default_settings['hover_animation'] );
		if ( ! in_array( $hover_animation, $allowed_animations, true ) ) {
			$hover_animation = $default_settings['hover_animation'];
		}

		$share_count_mode = sanitize_text_field( $settings['share_count_mode'] ?? $default_settings['share_count_mode'] );
		if ( ! in_array( $share_count_mode, $allowed_share_count_modes, true ) ) {
			$share_count_mode = $default_settings['share_count_mode'];
		}

		$inline_alignment = sanitize_text_field( $settings['inline_alignment'] ?? 'left' );
		if ( ! in_array( $inline_alignment, $allowed_alignments, true ) ) {
			$inline_alignment = 'left';
		}

		$custom_bg_color = sanitize_hex_color( $settings['custom_bg_color'] ?? $default_settings['custom_bg_color'] );
		if ( empty( $custom_bg_color ) ) {
			$custom_bg_color = $default_settings['custom_bg_color'];
		}

		$custom_icon_color = sanitize_hex_color( $settings['custom_icon_color'] ?? $default_settings['custom_icon_color'] );
		if ( empty( $custom_icon_color ) ) {
			$custom_icon_color = $default_settings['custom_icon_color'];
		}

		$custom_hover_bg = sanitize_hex_color( $settings['custom_hover_bg'] ?? $default_settings['custom_hover_bg'] );
		if ( empty( $custom_hover_bg ) ) {
			$custom_hover_bg = $default_settings['custom_hover_bg'];
		}

		$sanitized = array(
			'enabled'            => isset( $settings['enabled'] ) ? true : false,
			'networks'           => array_values( $selected_networks ),
			'network_order'      => array_values( $selected_networks ),
			'post_types'         => array_values( $selected_post_types ),
			'show_on_homepage'   => isset( $settings['show_on_homepage'] ) ? true : false,
			'auto_detect_post_types' => isset( $settings['auto_detect_post_types'] ) ? true : false,
			'position'           => $position,
			'mobile_position'    => $mobile_position,
			'button_shape'       => $button_shape,
			'button_size'        => $button_size,
			'show_more_network_button' => isset( $settings['show_more_network_button'] ) ? true : false,
			'color_scheme'       => $color_scheme,
			'hover_animation'    => $hover_animation,
			'custom_bg_color'    => $custom_bg_color,
			'custom_icon_color'  => $custom_icon_color,
			'custom_hover_bg'    => $custom_hover_bg,
			'button_gap'         => min( 50, absint( $settings['button_gap'] ?? $default_settings['button_gap'] ) ),
			'margin_top'         => min( 100, absint( $settings['margin_top'] ?? $default_settings['margin_top'] ) ),
			'margin_bottom'      => min( 100, absint( $settings['margin_bottom'] ?? $default_settings['margin_bottom'] ) ),
			'enable_animations'  => isset( $settings['enable_animations'] ) ? true : false,
			'show_share_counts'  => isset( $settings['show_share_counts'] ) ? true : false,
			'share_count_mode'   => $share_count_mode,
			'share_count_cache_ttl' => max( 5, min( 1440, absint( $settings['share_count_cache_ttl'] ?? $default_settings['share_count_cache_ttl'] ) ) ),
			'sharedcount_api_key' => sanitize_text_field( $settings['sharedcount_api_key'] ?? '' ),
			'custom_css'         => sanitize_textarea_field( $settings['custom_css'] ?? '' ),
			'inline_alignment'   => $inline_alignment,
		);

		update_option( 'sharenivo_settings', $sanitized );

		if ( $is_ajax ) {
			wp_send_json_success( array( 'message' => esc_html__( 'Settings saved successfully.', 'sharenivo-fast-social-sharing' ) ) );
		} else {
			// Show success message.
			add_settings_error(
				'sharenivo_messages',
				'sharenivo_message',
				__( 'Settings saved successfully.', 'sharenivo-fast-social-sharing' ),
				'updated'
			);

			settings_errors( 'sharenivo_messages' );
		}
	}
}
