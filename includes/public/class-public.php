<?php
/**
 * Public-facing functionality.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PublicDisplay class.
 */
class PublicDisplay {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Whether automatic inline buttons were already injected.
	 *
	 * @var bool
	 */
	private $inline_injected = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = Settings::get_settings();

		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'display_buttons' ) );
		add_filter( 'the_content', array( $this, 'inject_inline_buttons' ), 15 );
		add_action( 'sharenivo_display_buttons', array( $this, 'display_buttons_manual' ) );
		// Legacy hook and shortcode aliases keep existing theme integrations working.
		add_action( 'sharenova_display_buttons', array( $this, 'display_buttons_manual' ) );
		add_shortcode( 'sharenivo_share', array( $this, 'shortcode_handler' ) );
		add_shortcode( 'sharenova_share', array( $this, 'shortcode_handler' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Get cache-busting asset version.
	 *
	 * @param string $relative_path Relative path from plugin root.
	 * @return string
	 */
	private function get_asset_version( $relative_path ) {
		$path = SHARENIVO_PLUGIN_DIR . ltrim( $relative_path, '/\\' );
		if ( file_exists( $path ) ) {
			return (string) filemtime( $path );
		}

		return SHARENIVO_VERSION;
	}

	/**
	 * Get allowed HTML tags including SVG for wp_kses().
	 *
	 * @return array
	 */
	private function get_allowed_html() {
		$svg_args = array(
			'svg'      => array( 'class' => true, 'aria-hidden' => true, 'aria-labelledby' => true, 'role' => true, 'xmlns' => true, 'width' => true, 'height' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
			'g'        => array( 'fill' => true ),
			'title'    => array( 'title' => true ),
			'path'     => array( 'd' => true, 'fill' => true ),
			'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ),
			'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'fill' => true, 'rx' => true, 'ry' => true ),
			'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ),
			'polyline' => array( 'points' => true ),
		);
		return array_merge( wp_kses_allowed_html( 'post' ), $svg_args );
	}

	/**
	 * Register Gutenberg block.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'sharenivo-block-editor',
			SHARENIVO_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			$this->get_asset_version( 'assets/js/block.js' ),
			true
		);

		wp_register_style(
			'sharenivo-block-public-css',
			SHARENIVO_PLUGIN_URL . 'assets/css/public.css',
			array( 'wp-edit-blocks' ),
			$this->get_asset_version( 'assets/css/public.css' )
		);

		wp_register_style(
			'sharenivo-block-editor-css',
			SHARENIVO_PLUGIN_URL . 'assets/css/block-editor.css',
			array( 'wp-edit-blocks', 'sharenivo-block-public-css' ),
			$this->get_asset_version( 'assets/css/block-editor.css' )
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sharenivo-block-editor', 'sharenivo-fast-social-sharing', SHARENIVO_PLUGIN_DIR . 'languages' );
		}

		$block_args = array(
				'api_version'     => 2,
				'editor_script'   => 'sharenivo-block-editor',
				'editor_style'    => 'sharenivo-block-editor-css',
				'render_callback' => array( $this, 'render_block' ),
		);

		register_block_type( 'sharenivo/share-buttons', $block_args );
		// Existing posts may still contain the original block name.
		register_block_type( 'wssp/share-buttons', $block_args );
	}

	/**
	 * Render callback for Gutenberg block.
	 *
	 * @param array      $attributes Block attributes.
	 * @param string     $content    Block content.
	 * @param \WP_Block  $block      Parsed block.
	 * @return string
	 */
	public function render_block( $attributes = array(), $content = '', $block = null ) {
		if ( empty( $this->settings['enabled'] ) ) {
			return '';
		}

		$post_id = 0;
		if ( is_object( $block ) && ! empty( $block->context['postId'] ) ) {
			$post_id = absint( $block->context['postId'] );
		}

		return $this->render_buttons( $post_id, true );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( empty( $this->settings['enabled'] ) ) {
			return;
		}

		wp_enqueue_style(
			'sharenivo-public-css',
			SHARENIVO_PLUGIN_URL . 'assets/css/public.css',
			array(),
			$this->get_asset_version( 'assets/css/public.css' )
		);

		wp_enqueue_script(
			'sharenivo-public-js',
			SHARENIVO_PLUGIN_URL . 'assets/js/public.js',
			array(),
			$this->get_asset_version( 'assets/js/public.js' ),
			true
		);

		$dynamic_styles = $this->get_dynamic_styles();
		if ( ! empty( $dynamic_styles ) ) {
			wp_add_inline_style( 'sharenivo-public-css', $dynamic_styles );
		}

		// Add inline custom CSS if provided.
		if ( ! empty( $this->settings['custom_css'] ) ) {
			wp_add_inline_style( 'sharenivo-public-css', $this->settings['custom_css'] );
		}
	}

	/**
	 * Add plugin body classes.
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public function add_body_class( $classes ) {
		if ( ! empty( $this->settings['enabled'] ) && empty( $this->settings['enable_animations'] ) ) {
			$classes[] = 'sharenivo-no-animations';
		}

		return $classes;
	}

	/**
	 * Check if buttons can be rendered on current request.
	 *
	 * @return bool
	 */
	private function can_render_buttons() {
		if ( empty( $this->settings['enabled'] ) ) {
			return false;
		}

		if ( ! is_singular() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get enabled post types with optional auto-detect support.
	 *
	 * @return array
	 */
	private function get_enabled_post_types() {
		$post_types = array();
		if ( ! empty( $this->settings['post_types'] ) && is_array( $this->settings['post_types'] ) ) {
			$post_types = array_map( 'sanitize_key', $this->settings['post_types'] );
		}

		if ( ! empty( $this->settings['auto_detect_post_types'] ) ) {
			$public_types = get_post_types( array( 'public' => true ), 'objects' );
			foreach ( $public_types as $post_type ) {
				if ( ! empty( $post_type->_builtin ) || 'attachment' === $post_type->name ) {
					continue;
				}
				$post_types[] = $post_type->name;
			}
		}

		$post_types = array_values( array_unique( array_filter( $post_types ) ) );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		return $post_types;
	}

	/**
	 * Check if current post already has the share block.
	 *
	 * @return bool
	 */
	private function has_share_block() {
		if ( ! function_exists( 'has_block' ) ) {
			return false;
		}

		$post = get_post();
		if ( ! ( $post instanceof \WP_Post ) ) {
			return false;
		}

		return has_block( 'sharenivo/share-buttons', $post->post_content ) || has_block( 'wssp/share-buttons', $post->post_content );
	}

	/**
	 * Check if automatic buttons should be displayed on current page.
	 *
	 * @return bool
	 */
	private function should_display_buttons() {
		if ( ! $this->can_render_buttons() ) {
			return false;
		}

		if ( is_front_page() ) {
			if ( empty( $this->settings['show_on_homepage'] ) ) {
				return false;
			}
		} else {
			$post_type = get_post_type();
			if ( ! in_array( $post_type, $this->get_enabled_post_types(), true ) ) {
				return false;
			}
		}

		// Avoid duplicate output when the block is already present in content.
		if ( $this->has_share_block() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get sanitized desktop display position.
	 *
	 * @return string
	 */
	private function get_display_position() {
		$position = sanitize_key( $this->settings['position'] ?? 'floating_left' );
		$allowed_positions = array( 'floating_left', 'floating_right', 'inline_top', 'inline_bottom' );

		if ( ! in_array( $position, $allowed_positions, true ) ) {
			return 'floating_left';
		}

		return $position;
	}

	/**
	 * Display share buttons.
	 */
	public function display_buttons() {
		if ( ! $this->should_display_buttons() ) {
			return;
		}

		// Inline positions are rendered via the_content filter.
		if ( in_array( $this->get_display_position(), array( 'inline_top', 'inline_bottom' ), true ) ) {
			return;
		}

		// Output is built from sanitized settings and escaped URLs/attributes.
		echo wp_kses( $this->render_buttons(), $this->get_allowed_html() );
	}

	/**
	 * Inject inline buttons into post content when selected.
	 *
	 * @param string $content Content HTML.
	 * @return string
	 */
	public function inject_inline_buttons( $content ) {
		if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		if ( $this->inline_injected || ! $this->should_display_buttons() ) {
			return $content;
		}

		$position = $this->get_display_position();
		if ( ! in_array( $position, array( 'inline_top', 'inline_bottom' ), true ) ) {
			return $content;
		}

		$this->inline_injected = true;
		$buttons_html = $this->render_buttons();

		if ( 'inline_top' === $position ) {
			return $buttons_html . $content;
		}

		return $content . $buttons_html;
	}

	/**
	 * Display buttons for manual hook usage.
	 */
	public function display_buttons_manual() {
		if ( ! $this->can_render_buttons() ) {
			return;
		}

		echo wp_kses( $this->render_buttons(), $this->get_allowed_html() );
	}

	/**
	 * Build dynamic styles from plugin settings.
	 *
	 * @return string
	 */
	private function get_dynamic_styles() {
		$button_gap = absint( $this->settings['button_gap'] ?? 10 );
		$margin_top = absint( $this->settings['margin_top'] ?? 20 );
		$margin_bottom = absint( $this->settings['margin_bottom'] ?? 20 );
		$alignment_setting = sanitize_key( $this->settings['inline_alignment'] ?? 'left' );
		
		$justify = 'flex-start';
		if ( 'center' === $alignment_setting ) {
			$justify = 'center';
		} elseif ( 'right' === $alignment_setting ) {
			$justify = 'flex-end';
		}

		$styles = sprintf(
			'.sharenivo-share-buttons{--sharenivo-gap:%1$dpx;--sharenivo-margin-top:%2$dpx;--sharenivo-margin-bottom:%3$dpx;}' .
			'.sharenivo-share-buttons.sharenivo-floating-left,.sharenivo-share-buttons.sharenivo-floating-right,.sharenivo-share-buttons.sharenivo-inline-top,.sharenivo-share-buttons.sharenivo-inline-bottom,.sharenivo-share-buttons.sharenivo-mobile-sticky-bottom,.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-inline-top,.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-inline-bottom,.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-floating-left,.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-floating-right{gap:var(--sharenivo-gap);}' .
			'.sharenivo-share-buttons.sharenivo-inline-top,.sharenivo-share-buttons.sharenivo-inline-bottom{justify-content:%4$s;}' .
			'@media (min-width:768px){.sharenivo-share-buttons.sharenivo-inline-top,.sharenivo-share-buttons.sharenivo-inline-bottom{margin-top:var(--sharenivo-margin-top);margin-bottom:var(--sharenivo-margin-bottom);}}' .
			'@media (max-width:767px){.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-inline-top,.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-inline-bottom,.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-floating-left,.sharenivo-share-buttons.sharenivo-mobile-inline.sharenivo-floating-right{margin-top:var(--sharenivo-margin-top);margin-bottom:var(--sharenivo-margin-bottom);justify-content:%4$s;}}',
			$button_gap,
			$margin_top,
			$margin_bottom,
			$justify
		);

		if ( 'custom' === $this->settings['color_scheme'] ) {
			$bg_color = sanitize_hex_color( $this->settings['custom_bg_color'] ?? '' );
			$icon_color = sanitize_hex_color( $this->settings['custom_icon_color'] ?? '' );
			$hover_color = sanitize_hex_color( $this->settings['custom_hover_bg'] ?? '' );

			$bg_color = ! empty( $bg_color ) ? $bg_color : '#000000';
			$icon_color = ! empty( $icon_color ) ? $icon_color : '#ffffff';
			$hover_color = ! empty( $hover_color ) ? $hover_color : '#434343';

			$styles .= sprintf(
				'.sharenivo-share-buttons .sharenivo-button{background:%1$s !important;}' .
				'.sharenivo-share-buttons .sharenivo-button svg{fill:%2$s !important;}' .
				'body:not(.sharenivo-no-animations) .sharenivo-share-buttons .sharenivo-button:hover{background:%3$s !important;}',
				$bg_color,
				$icon_color,
				$hover_color
			);
		}

		return $styles;
	}

	/**
	 * Get sharing context data.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_share_context( $post_id = 0 ) {
		$post_id = absint( $post_id );
		if ( ! $post_id && is_singular() ) {
			$post_id = absint( get_queried_object_id() );
		}

		if ( $post_id ) {
			$post_url = get_permalink( $post_id );
			$post_title = get_the_title( $post_id );
		} else {
			global $wp;
			$post_url = home_url( add_query_arg( array(), $wp->request ) );
			$post_title = wp_title( '', false );
			if ( empty( $post_title ) ) {
				$post_title = get_bloginfo( 'name' );
			}
		}

		if ( empty( $post_url ) ) {
			return array();
		}

		if ( '' === trim( (string) $post_title ) ) {
			$post_title = get_bloginfo( 'name' );
		}

		return array(
			'post_id' => $post_id,
			'url'     => $post_url,
			'title'   => $post_title,
		);
	}

	/**
	 * Get ordered active networks and ensure they exist in share URL map.
	 *
	 * @param array $share_urls Share URL map.
	 * @return array
	 */
	private function get_ordered_networks( $share_urls ) {
		$networks = apply_filters( 'sharenivo_networks', $this->settings['networks'] );
		$networks = apply_filters( 'sharenova_networks', $networks );
		if ( ! is_array( $networks ) ) {
			$networks = $this->settings['networks'];
		}

		$order = $this->settings['network_order'] ?? $this->settings['networks'];
		$networks = Settings::sort_networks_by_order( $networks, $order );

		return array_values(
			array_filter(
				$networks,
				static function ( $network ) use ( $share_urls ) {
					return isset( $share_urls[ $network ] );
				}
			)
		);
	}

	/**
	 * Render share buttons HTML.
	 *
	 * @param int  $post_id  Post ID.
	 * @param bool $is_block Whether rendering inside block context.
	 * @return string
	 */
	private function render_buttons( $post_id = 0, $is_block = false ) {
		$context = $this->get_share_context( $post_id );
		if ( empty( $context ) ) {
			return '';
		}

		$share_urls = $this->get_share_urls( $context['url'], $context['title'] );
		$networks = $this->get_ordered_networks( $share_urls );

		if ( empty( $networks ) ) {
			return '';
		}

		$network_labels = Settings::get_allowed_networks();
		$position_class = $is_block ? 'sharenivo-inline-top' : 'sharenivo-' . str_replace( '_', '-', $this->settings['position'] );
		$shape_value = sanitize_key( $this->settings['button_shape'] ?? 'circle' );
		if ( in_array( $shape_value, array( 'portrait', 'landscape_rectangle' ), true ) ) {
			$shape_value = 'landscape';
		}
		$shape_class    = 'sharenivo-shape-' . $shape_value;
		$size_class     = 'sharenivo-size-' . $this->settings['button_size'];
		$mobile_class   = $is_block ? 'sharenivo-mobile-inline' : 'sharenivo-mobile-' . str_replace( '_', '-', $this->settings['mobile_position'] );
		$hover_animation = sanitize_key( $this->settings['hover_animation'] ?? 'lift' );
		if ( empty( $hover_animation ) ) {
			$hover_animation = 'lift';
		}
		$animation_class = 'sharenivo-anim-' . $hover_animation;

		$show_share_counts = ! empty( $this->settings['show_share_counts'] );
		$share_count_mode = ( 'network' === ( $this->settings['share_count_mode'] ?? '' ) ) ? 'network' : 'total';
		$share_counts = array();

		if ( $show_share_counts ) {
			$share_counts = $this->get_share_counts( $context['url'], $networks );
		}

		$enable_more_button = ! empty( $this->settings['show_more_network_button'] );
		$use_more_button = $enable_more_button && count( $networks ) > 4;
		$primary_networks = $use_more_button ? array_slice( $networks, 0, 4 ) : $networks;
		$secondary_networks = $use_more_button ? array_slice( $networks, 4 ) : array();
		$has_more_networks = ! empty( $secondary_networks );

		ob_start();
		?>
		<?php if ( $show_share_counts && 'total' === $share_count_mode ) : ?>
			<div class="sharenivo-total-shares">
				<span class="sharenivo-total-shares-label"><?php esc_html_e( 'Total Shares', 'sharenivo-fast-social-sharing' ); ?>:</span>
				<span class="sharenivo-total-shares-value"><?php echo esc_html( $this->format_count( absint( $share_counts['total'] ?? 0 ) ) ); ?></span>
			</div>
		<?php endif; ?>
		<div class="sharenivo-share-buttons <?php echo esc_attr( $position_class . ' ' . $mobile_class . ' ' . $animation_class ); ?>">
			<?php foreach ( $primary_networks as $network ) : ?>
				<?php
				echo wp_kses(
					$this->get_network_button_html( $network, $shape_class, $size_class, $share_urls, $network_labels, $show_share_counts, $share_count_mode, $share_counts ),
					$this->get_allowed_html()
				);
				?>
			<?php endforeach; ?>

			<?php if ( $has_more_networks ) : ?>
				<div class="sharenivo-more-wrap">
					<button type="button" class="sharenivo-button sharenivo-more-toggle <?php echo esc_attr( $shape_class . ' ' . $size_class ); ?>" aria-expanded="false" aria-label="<?php esc_attr_e( 'Show more networks', 'sharenivo-fast-social-sharing' ); ?>">
						<span class="sharenivo-more-text"><?php esc_html_e( 'More', 'sharenivo-fast-social-sharing' ); ?></span>
						<?php
						echo wp_kses( $this->get_network_icon( 'more' ), $this->get_allowed_html() );
						?>
					</button>
					<div class="sharenivo-more-networks" hidden>
						<?php foreach ( $secondary_networks as $network ) : ?>
							<?php
							echo wp_kses(
								$this->get_network_button_html( $network, $shape_class, $size_class, $share_urls, $network_labels, $show_share_counts, $share_count_mode, $share_counts ),
								$this->get_allowed_html()
							);
							?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build a single network button HTML.
	 *
	 * @param string $network          Network slug.
	 * @param string $shape_class      Shape class.
	 * @param string $size_class       Size class.
	 * @param array  $share_urls       Share URLs.
	 * @param array  $network_labels   Network labels.
	 * @param bool   $show_share_counts Whether share counts are enabled.
	 * @param string $share_count_mode Share count mode.
	 * @param array  $share_counts     Share count data.
	 * @return string
	 */
	private function get_network_button_html( $network, $shape_class, $size_class, $share_urls, $network_labels, $show_share_counts, $share_count_mode, $share_counts ) {
		$network_label = isset( $network_labels[ $network ] ) ? $network_labels[ $network ] : ucfirst( sanitize_text_field( $network ) );
		$count_html = '';

		if ( $show_share_counts && 'network' === $share_count_mode ) {
			$network_count = absint( $share_counts[ $network ] ?? 0 );
			if ( $network_count > 0 ) {
				$count_html = '<span class="sharenivo-network-count" aria-hidden="true">' . esc_html( $this->format_count( $network_count ) ) . '</span>';
			}
		}

		$button_html = sprintf(
			'<a class="sharenivo-button sharenivo-%1$s %2$s" href="%3$s" target="_blank" rel="noopener noreferrer" aria-label="%4$s">%5$s%6$s</a>',
			esc_attr( $network ),
			esc_attr( $shape_class . ' ' . $size_class ),
			esc_url( $share_urls[ $network ] ),
			/* translators: %s: network name */
			esc_attr( sprintf( __( 'Share on %s', 'sharenivo-fast-social-sharing' ), $network_label ) ),
			$this->get_network_icon( $network ),
			$count_html
		);

		/**
		 * Filter individual network button HTML.
		 *
		 * @param string $button_html Generated button markup.
		 * @param string $network     Network slug.
		 * @param string $share_url   Share URL.
		 * @param array  $settings    Plugin settings.
		 */
		$button_html = apply_filters( 'sharenivo_button_html', $button_html, $network, $share_urls[ $network ], $this->settings );
		return apply_filters( 'sharenova_button_html', $button_html, $network, $share_urls[ $network ], $this->settings );
	}

	/**
	 * Fetch share counts for the given URL.
	 *
	 * @param string $url      URL.
	 * @param array  $networks Network list.
	 * @return array
	 */
	private function get_share_counts( $url, $networks ) {
		$cache_key = 'sharenivo_share_counts_' . md5( $url );
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['total'] ) ) {
			return $cached;
		}

		$counts = array_fill_keys( $networks, 0 );
		$counts = $this->fetch_sharedcount_counts( $url, $counts, $networks );

		if ( in_array( 'reddit', $networks, true ) && empty( $counts['reddit'] ) ) {
			$counts['reddit'] = $this->fetch_reddit_count( $url );
		}

		/**
		 * Filter computed share counts before caching.
		 *
		 * @param array  $counts   Per-network counts.
		 * @param string $url      Shared URL.
		 * @param array  $networks Network list.
		 * @param array  $settings Plugin settings.
		 */
		$filtered_counts = apply_filters( 'sharenivo_share_counts', $counts, $url, $networks, $this->settings );
		$filtered_counts = apply_filters( 'sharenova_share_counts', $filtered_counts, $url, $networks, $this->settings );
		if ( is_array( $filtered_counts ) ) {
			$counts = $filtered_counts;
		}

		$total = 0;
		foreach ( $networks as $network ) {
			$counts[ $network ] = absint( $counts[ $network ] ?? 0 );
			$total += $counts[ $network ];
		}
		$counts['total'] = $total;

		$cache_ttl = max( 5, absint( $this->settings['share_count_cache_ttl'] ?? 60 ) ) * MINUTE_IN_SECONDS;
		set_transient( $cache_key, $counts, $cache_ttl );

		return $counts;
	}

	/**
	 * Fetch counts from SharedCount API (optional).
	 *
	 * @param string $url      URL.
	 * @param array  $counts   Existing counts.
	 * @param array  $networks Networks.
	 * @return array
	 */
	private function fetch_sharedcount_counts( $url, $counts, $networks ) {
		$api_key = trim( (string) ( $this->settings['sharedcount_api_key'] ?? '' ) );
		if ( '' === $api_key ) {
			return $counts;
		}

		$endpoint = add_query_arg(
			array(
				'url'    => $url,
				'apikey' => $api_key,
			),
			'https://api.sharedcount.com/v1.0/'
		);

		$response = wp_remote_get( $endpoint, $this->get_remote_request_args() );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return $counts;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return $counts;
		}

		if ( in_array( 'facebook', $networks, true ) && isset( $data['Facebook'] ) && is_array( $data['Facebook'] ) ) {
			$counts['facebook'] = absint( $data['Facebook']['total_count'] ?? $data['Facebook']['Total_count'] ?? 0 );
		}

		if ( in_array( 'pinterest', $networks, true ) ) {
			$counts['pinterest'] = absint( $data['Pinterest'] ?? 0 );
		}

		if ( in_array( 'reddit', $networks, true ) ) {
			$counts['reddit'] = absint( $data['Reddit'] ?? $counts['reddit'] ?? 0 );
		}

		return $counts;
	}

	/**
	 * Fetch count from Reddit endpoint.
	 *
	 * @param string $url URL.
	 * @return int
	 */
	private function fetch_reddit_count( $url ) {
		$endpoint = add_query_arg(
			array(
				'url' => $url,
			),
			'https://www.reddit.com/api/info.json'
		);

		$response = wp_remote_get( $endpoint, $this->get_remote_request_args() );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return 0;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return 0;
		}

		$dist = absint( $data['data']['dist'] ?? 0 );
		if ( $dist > 0 ) {
			return $dist;
		}

		if ( isset( $data['data']['children'] ) && is_array( $data['data']['children'] ) ) {
			return absint( count( $data['data']['children'] ) );
		}

		return 0;
	}

	/**
	 * Build common args for remote requests.
	 *
	 * @return array
	 */
	private function get_remote_request_args() {
		return array(
			'timeout'     => 5,
			'redirection' => 2,
			'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
		);
	}

	/**
	 * Format share counts for display.
	 *
	 * @param int $count Raw count.
	 * @return string
	 */
	private function format_count( $count ) {
		$count = absint( $count );

		if ( $count >= 1000000 ) {
			return round( $count / 1000000, 1 ) . 'M';
		}

		if ( $count >= 1000 ) {
			return round( $count / 1000, 1 ) . 'K';
		}

		return (string) $count;
	}

	/**
	 * Get share URLs for all networks.
	 *
	 * @param string $url   Post URL.
	 * @param string $title Post title.
	 * @return array Share URLs.
	 */
	private function get_share_urls( $url, $title ) {
		$url_encoded = rawurlencode( $url );
		$title_encoded = rawurlencode( $title );

		return array(
			'facebook'  => "https://www.facebook.com/sharer/sharer.php?u={$url_encoded}",
			'x'         => "https://x.com/intent/tweet?url={$url_encoded}&text={$title_encoded}",
			'linkedin'  => "https://www.linkedin.com/sharing/share-offsite/?url={$url_encoded}",
			'whatsapp'  => "https://wa.me/?text={$title_encoded}%20{$url_encoded}",
			'pinterest' => "https://pinterest.com/pin/create/button/?url={$url_encoded}&description={$title_encoded}",
			'threads'   => "https://www.threads.net/intent/post?text={$title_encoded}%20{$url_encoded}",
			'bluesky'   => "https://bsky.app/intent/compose?text={$title_encoded}%20{$url_encoded}",
			'telegram'  => "https://t.me/share/url?url={$url_encoded}&text={$title_encoded}",
			'reddit'    => "https://www.reddit.com/submit?url={$url_encoded}&title={$title_encoded}",
			'email'     => "mailto:?subject={$title_encoded}&body={$url_encoded}",
		);
	}

	/**
	 * Get SVG icon for network.
	 *
	 * @param string $network Network name.
	 * @return string SVG icon HTML.
	 */
	private function get_network_icon( $network ) {
		$icons = array(
			'facebook'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M22.675 0H1.325C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82v-9.294H9.692v-3.622h3.127V8.413c0-3.1 1.894-4.785 4.659-4.785 1.325 0 2.464.099 2.794.143v3.24h-1.918c-1.504 0-1.794.714-1.794 1.763v2.31h3.587l-.467 3.622h-3.12V24h6.116C23.407 24 24 23.407 24 22.675V1.325C24 .593 23.407 0 22.675 0z"/></svg>',
			'x'         => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
			'linkedin'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M22.23 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.46C23.21 24 24 23.23 24 22.27V1.73C24 .77 23.21 0 22.23 0zM7.12 20.45H3.56V9.02h3.56v11.43zM5.34 7.65c-1.14 0-2.06-.92-2.06-2.06 0-1.14.92-2.06 2.06-2.06s2.06.92 2.06 2.06c0 1.14-.92 2.06-2.06 2.06zM20.45 20.45h-3.56v-5.84c0-1.39-.03-3.18-1.94-3.18-1.94 0-2.24 1.51-2.24 3.07v5.95H9.02V9.02h3.42v1.56h.05c.48-.91 1.66-1.87 3.42-1.87 3.65 0 4.33 2.4 4.33 5.52v6.22z"/></svg>',
			'whatsapp'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.372 0 0 5.372 0 12c0 2.162.572 4.19 1.572 5.95L0 24l6.184-1.603A11.978 11.978 0 0012 24c6.628 0 12-5.372 12-12S18.628 0 12 0zm5.255 17.345c-.21.59-1.229 1.14-2.118 1.286-.563.09-1.26.156-3.646-.772-3.08-1.282-5.05-4.434-5.202-4.644-.15-.21-1.238-1.65-1.238-3.146 0-1.496.784-2.232 1.06-2.54.278-.31.604-.388.805-.388h.577c.188 0 .434-.05.679.51.246.558.84 1.938.915 2.075.073.137.122.298.024.476-.098.18-.147.298-.294.465-.15.164-.309.365-.44.493-.15.143-.31.297-.136.576.175.278.78 1.292 1.674 2.09 1.15 1.03 2.118 1.346 2.42 1.496.302.15.478.125.654-.075.175-.2.753-.878.953-1.178.2-.3.4-.25.677-.15.278.1 1.763.832 2.065.982.302.15.503.225.578.35.075.125.075.725-.135 1.315z"/></svg>',
			'pinterest' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/></svg>',
			'threads'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M16.3 10.6c-.2 0-.4 0-.6.1-.4-2.4-1.9-3.7-4.5-3.8-2.2-.1-4 .9-4.8 2.7-.2.5 0 1 .5 1.2.5.2 1 0 1.2-.5.5-1.2 1.6-1.9 3.1-1.8 1.8.1 2.7.9 3 2.6-1.3.4-2.5 1-3.3 1.8-1 .9-1.4 2-1.2 3.1.3 1.6 1.8 2.7 3.7 2.7 2.5 0 4.1-1.3 4.5-3.7.1-.5.1-1 .1-1.5.8.2 1.2.6 1.2 1.4 0 1.8-1.8 3.2-4.1 3.2H10c-3.7 0-6.8-2.9-6.8-6.5S6.3 5.1 10 5.1h4c3.7 0 6.8 2.9 6.8 6.5 0 .5.4.9.9.9s.9-.4.9-.9C22.6 6.8 18.7 3 14 3h-4C5.3 3 1.4 6.8 1.4 11.5S5.3 20 10 20h5.3c3.3 0 5.9-2.2 5.9-5 0-2.8-2.1-4.4-4.9-4.4zm-2.9 6.3c-.9 0-1.6-.5-1.7-1.2-.1-.5.1-1 .7-1.5.5-.5 1.4-.9 2.4-1.3 0 .3 0 .6-.1.9-.2 1.4-.9 3.1-2.9 3.1z"/></svg>',
			'bluesky'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 10.8c-1.087-2.114-5.713-6.347-7.96-7.036-3.49-1.069-2.209 3.249-1.642 4.414.928 1.906 3.09 3.655 4.636 4.38-2.155-.008-4.551-.115-6.27-.474-3.566-.745-1.928 2.378-.458 3.518C4 18.455 9.07 19.333 12 21.062c2.93-1.73 8.001-2.607 11.693-5.46.126-.098.243-.186.353-.263 1.155-1.229 3.1-4.263-.458-3.518-1.718.36-4.114.466-6.27.474 1.547-.725 3.708-2.474 4.636-4.38.567-1.165 1.848-5.483-1.642-4.414-2.247.689-6.873 4.922-7.96 7.036z"/></svg>',
			'telegram'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.888-.662 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
			'reddit'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12c0-1.66-1.34-3-3-3-.8 0-1.52.31-2.05.82-1.99-1.36-4.67-2.24-7.65-2.32l1.29-4.08 3.51.82a2 2 0 101.94-2.24 2 2 0 00-1.86 1.27l-3.96-.93a.75.75 0 00-.9.5L9.82 7.4c-2.93.14-5.56 1.02-7.52 2.33A2.99 2.99 0 000 12c0 1.3.84 2.4 2 2.82 0 .12-.01.24-.01.36 0 3.67 4.48 6.65 10.01 6.65s10-2.98 10-6.65c0-.12 0-.24-.01-.36A3 3 0 0024 12zM7.5 13.5A1.5 1.5 0 119 12a1.5 1.5 0 01-1.5 1.5zm8.73 3.7a5.9 5.9 0 01-4.23 1.3 5.86 5.86 0 01-4.22-1.3.75.75 0 011.04-1.08c.64.62 1.71.95 3.18.95 1.47 0 2.55-.33 3.18-.95a.75.75 0 011.05 1.08zM16.5 13.5A1.5 1.5 0 1118 12a1.5 1.5 0 01-1.5 1.5z"/></svg>',
			'more'      => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4a1 1 0 011 1v6h6a1 1 0 110 2h-6v6a1 1 0 11-2 0v-6H5a1 1 0 010-2h6V5a1 1 0 011-1z"/><path d="M7 20a1 1 0 010-2h10a1 1 0 110 2z"/></svg>',
			'email'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
		);

		return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_handler( $atts ) {
		if ( empty( $this->settings['enabled'] ) ) {
			return '';
		}

		return $this->render_buttons();
	}
}
