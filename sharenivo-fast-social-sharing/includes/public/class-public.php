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
 * Coordinates conditional assets and frontend placement output.
 */
class PublicDisplay {

	/**
	 * Normalized settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Shared markup renderer.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize' ), 1 );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_footer_locations' ) );
		add_action( 'wp_head', array( $this, 'render_social_meta' ), 20 );
		add_filter( 'the_content', array( $this, 'inject_inline_buttons' ), 15 );

		add_shortcode( 'sharenivo_share', array( $this, 'share_shortcode' ) );
		add_shortcode( 'sharenivo_follow', array( $this, 'follow_shortcode' ) );
		add_shortcode( 'sharenova_share', array( $this, 'share_shortcode' ) );
		add_shortcode( 'sharenivo_quote', array( $this, 'quote_shortcode' ) );
		add_shortcode( 'sharenivo_click_to_share', array( $this, 'quote_shortcode' ) );
		add_action( 'sharenivo_display_buttons', array( $this, 'render_manual_share' ) );
		add_action( 'sharenivo_display_follow', array( $this, 'render_manual_follow' ) );
		add_action( 'sharenova_display_buttons', array( $this, 'render_manual_share' ) );
	}

	/**
	 * Load normalized settings after WordPress is ready for translations.
	 */
	public function initialize() {
		$this->settings = Settings::get_settings();
		$this->renderer = new Renderer( $this->settings );
	}

	/**
	 * Register dynamic share and follow blocks.
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'sharenivo-block-editor',
			SHARENIVO_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
			$this->asset_version( 'assets/js/block.js' ),
			true
		);
		wp_register_style(
			'sharenivo-public',
			SHARENIVO_PLUGIN_URL . 'assets/css/public.css',
			array(),
			$this->asset_version( 'assets/css/public.css' )
		);
		wp_register_style(
			'sharenivo-block-editor',
			SHARENIVO_PLUGIN_URL . 'assets/css/block-editor.css',
			array( 'wp-edit-blocks', 'sharenivo-public' ),
			$this->asset_version( 'assets/css/block-editor.css' )
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sharenivo-block-editor', 'sharenivo-fast-social-sharing', SHARENIVO_PLUGIN_DIR . 'languages' );
		}

		$args = array(
			'api_version'     => 2,
			'editor_script'   => 'sharenivo-block-editor',
			'editor_style'    => 'sharenivo-block-editor',
			'style'           => 'sharenivo-public',
			'uses_context'    => array( 'postId' ),
			'render_callback' => array( $this, 'render_block' ),
		);
		register_block_type( 'sharenivo/share-buttons', $args );
		register_block_type( 'sharenivo/follow-links', $args );
		register_block_type( 'wssp/share-buttons', $args );
		$quote_args               = $args;
		$quote_args['attributes'] = array(
			'text'  => array(
				'type'    => 'string',
				'default' => '',
			),
			'style' => array(
				'type'    => 'string',
				'default' => 'card',
			),
		);
		register_block_type( 'sharenivo/share-quote', $quote_args );
	}

	/**
	 * Render a server-side block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Saved block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public function render_block( $attributes = array(), $content = '', $block = null ) {
		unset( $content );
		if ( empty( $this->settings['enabled'] ) ) {
			return '';
		}

		$block_name = is_object( $block ) ? $block->name : '';
		if ( 'sharenivo/follow-links' === $block_name ) {
			return $this->renderer->render_follow();
		}

		$post_id = is_object( $block ) && ! empty( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
		if ( 'sharenivo/share-quote' === $block_name ) {
			return $this->renderer->render_quote( is_array( $attributes ) ? $attributes : array(), $post_id );
		}
		return $this->renderer->render_share( 'inline', $post_id );
	}

	/**
	 * Register the classic widget.
	 */
	public function register_widget() {
		register_widget( '\\ShareNivo\\FollowWidget' );
	}

	/**
	 * Load the small frontend bundle only when it can produce output.
	 */
	public function enqueue_assets() {
		if ( ! $this->request_needs_assets() ) {
			return;
		}

		wp_enqueue_style(
			'sharenivo-public',
			SHARENIVO_PLUGIN_URL . 'assets/css/public.css',
			array(),
			$this->asset_version( 'assets/css/public.css' )
		);
		wp_enqueue_script(
			'sharenivo-public',
			SHARENIVO_PLUGIN_URL . 'assets/js/public.js',
			array(),
			$this->asset_version( 'assets/js/public.js' ),
			true
		);

		wp_add_inline_style( 'sharenivo-public', $this->renderer->get_dynamic_css() );

		wp_localize_script(
			'sharenivo-public',
			'sharenivoPublic',
			array(
				'copySuccess' => __( 'Link copied.', 'sharenivo-fast-social-sharing' ),
				'copyError'   => __( 'Copy failed. Please copy the address from your browser.', 'sharenivo-fast-social-sharing' ),
				'media'       => array(
					'enabled'            => $this->automatic_location_enabled( 'media' ),
					'minWidth'          => absint( $this->settings['locations']['media']['min_width'] ),
					'minHeight'         => absint( $this->settings['locations']['media']['min_height'] ),
					'imageSource'       => $this->settings['locations']['media']['image_source'],
					'descriptionSource' => $this->settings['locations']['media']['description_source'],
				),
			)
		);
	}

	/**
	 * Inject automatic inline buttons into the main singular content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function inject_inline_buttons( $content ) {
		if ( ! $this->automatic_location_enabled( 'inline' ) || ! is_main_query() || ! in_the_loop() || is_feed() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( function_exists( 'has_block' ) && ( has_block( 'sharenivo/share-buttons', get_post_field( 'post_content', $post_id ) ) || has_block( 'wssp/share-buttons', get_post_field( 'post_content', $post_id ) ) ) ) {
			return $content;
		}

		$buttons  = $this->renderer->render_share( 'inline', $post_id );
		$position = $this->settings['locations']['inline']['position'];
		if ( 'top' === $position ) {
			return $buttons . $content;
		}
		if ( 'both' === $position ) {
			return $buttons . $content . $this->renderer->render_share( 'inline', $post_id );
		}
		return $content . $buttons;
	}

	/**
	 * Render non-inline automatic placements in the footer.
	 */
	public function render_footer_locations() {
		if ( ! $this->automatic_output_allowed() ) {
			return;
		}

		$post_id = absint( get_queried_object_id() );
		foreach ( array( 'floating', 'sticky', 'popup', 'flyin' ) as $location ) {
			if ( $this->automatic_location_enabled( $location ) ) {
				echo $this->renderer->render_share( $location, $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all values.
			}
		}

		if ( $this->automatic_location_enabled( 'media' ) ) {
			echo $this->renderer->render_media_template( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all values.
		}
	}

	/**
	 * Share shortcode callback.
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string
	 */
	public function share_shortcode( $attributes = array() ) {
		$attributes = shortcode_atts( array( 'location' => 'inline', 'post_id' => 0, 'networks' => '' ), $attributes, 'sharenivo_share' );
		$location   = sanitize_key( $attributes['location'] );
		$location   = in_array( $location, array( 'inline', 'floating', 'sticky' ), true ) ? $location : 'inline';
		$post_id    = absint( $attributes['post_id'] ) ?: get_the_ID();
		$networks   = '' !== trim( (string) $attributes['networks'] ) ? preg_split( '/[\s,]+/', sanitize_text_field( $attributes['networks'] ) ) : array();
		return ! empty( $this->settings['enabled'] ) ? $this->renderer->render_share( $location, $post_id, $networks ) : '';
	}

	/**
	 * Click-to-share quote shortcode callback.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $content    Enclosed quote text.
	 * @return string
	 */
	public function quote_shortcode( $attributes = array(), $content = '' ) {
		$attributes = shortcode_atts( array( 'text' => '', 'style' => 'card', 'post_id' => 0 ), $attributes, 'sharenivo_quote' );
		$text       = trim( $attributes['text'] ? $attributes['text'] : $content );
		$post_id    = absint( $attributes['post_id'] ) ?: get_the_ID();
		return ! empty( $this->settings['enabled'] ) ? $this->renderer->render_quote( array( 'text' => $text, 'style' => $attributes['style'] ), $post_id ) : '';
	}

	/**
	 * Follow shortcode callback.
	 *
	 * @return string
	 */
	public function follow_shortcode() {
		return ! empty( $this->settings['enabled'] ) ? $this->renderer->render_follow() : '';
	}

	/**
	 * Output share buttons for theme integrations.
	 *
	 * @param int    $post_id  Optional post ID.
	 * @param string $location Optional display context.
	 */
	public function render_manual_share( $post_id = 0, $location = 'inline' ) {
		if ( empty( $this->settings['enabled'] ) ) {
			return;
		}
		$location = in_array( $location, array( 'inline', 'floating', 'sticky' ), true ) ? $location : 'inline';
		echo $this->renderer->render_share( $location, absint( $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all values.
	}

	/**
	 * Output follow links for theme integrations.
	 */
	public function render_manual_follow() {
		if ( ! empty( $this->settings['enabled'] ) ) {
			echo $this->renderer->render_follow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all values.
		}
	}

	/**
	 * Determine whether the current page qualifies for automatic output.
	 *
	 * @return bool
	 */
	private function automatic_output_allowed() {
		if ( empty( $this->settings['enabled'] ) || ( ! is_singular() && ! is_front_page() ) ) {
			return false;
		}
		if ( is_front_page() && empty( $this->settings['show_on_homepage'] ) ) {
			return false;
		}

		$post_id = absint( get_queried_object_id() );
		if ( $post_id ) {
			$override = get_post_meta( $post_id, '_sharenivo_override', true );
			if ( is_array( $override ) && 'disabled' === ( $override['mode'] ?? '' ) ) {
				return false;
			}
		}

		if ( ! is_front_page() && ! in_array( get_post_type( $post_id ), $this->settings['post_types'], true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check a location against global and per-post rules.
	 *
	 * @param string $location Location key.
	 * @return bool
	 */
	private function automatic_location_enabled( $location ) {
		if ( ! $this->automatic_output_allowed() || ! isset( $this->settings['locations'][ $location ] ) ) {
			return false;
		}

		$post_id  = absint( get_queried_object_id() );
		$override = $post_id ? get_post_meta( $post_id, '_sharenivo_override', true ) : array();
		if ( is_array( $override ) && 'custom' === ( $override['mode'] ?? '' ) ) {
			$locations = isset( $override['locations'] ) && is_array( $override['locations'] ) ? $override['locations'] : array();
			return in_array( $location, $locations, true );
		}
		return ! empty( $this->settings['locations'][ $location ]['enabled'] );
	}

	/**
	 * Determine whether any automatic or manual interface needs frontend assets.
	 *
	 * @return bool
	 */
	private function request_needs_assets() {
		if ( empty( $this->settings['enabled'] ) ) {
			return false;
		}
		if ( $this->automatic_output_allowed() ) {
			return true;
		}
		if ( ! empty( $this->settings['follow']['enabled'] ) && ! empty( $this->settings['follow']['profiles'] ) ) {
			return true;
		}

		$post = get_post();
		if ( ! ( $post instanceof \WP_Post ) ) {
			return false;
		}
		$content = $post->post_content;
		return has_shortcode( $content, 'sharenivo_share' ) || has_shortcode( $content, 'sharenivo_follow' ) || has_shortcode( $content, 'sharenova_share' ) || has_shortcode( $content, 'sharenivo_quote' ) || has_shortcode( $content, 'sharenivo_click_to_share' ) || ( function_exists( 'has_block' ) && ( has_block( 'sharenivo/share-buttons', $content ) || has_block( 'sharenivo/follow-links', $content ) || has_block( 'wssp/share-buttons', $content ) || has_block( 'sharenivo/share-quote', $content ) ) );
	}

	/**
	 * Render optional social preview metadata without duplicating SEO plugins.
	 */
	public function render_social_meta() {
		if ( empty( $this->settings['social_meta']['enabled'] ) || is_admin() || is_feed() || ! is_singular() || $this->has_social_meta_provider() ) {
			return;
		}

		$context = $this->renderer->get_context( get_queried_object_id() );
		if ( empty( $context ) ) {
			return;
		}
		if ( ! apply_filters( 'sharenivo_should_output_social_meta', true, $context['post_id'], $context ) ) {
			return;
		}

		$tags = array(
			'og:title'       => $context['title'],
			'og:description' => $context['description'],
			'og:url'         => $context['url'],
			'og:type'        => 'article',
			'twitter:title'  => $context['title'],
			'twitter:description' => $context['description'],
			'twitter:card'   => $context['featured_image'] ? 'summary_large_image' : 'summary',
		);
		if ( $context['featured_image'] ) {
			$tags['og:image']      = $context['featured_image'];
			$tags['twitter:image'] = $context['featured_image'];
		}

		foreach ( $tags as $name => $content ) {
			$attribute = 0 === strpos( $name, 'og:' ) ? 'property' : 'name';
			printf( '<meta %1$s="%2$s" content="%3$s" />' . "\n", esc_attr( $attribute ), esc_attr( $name ), esc_attr( $content ) );
		}
	}

	/**
	 * Detect common SEO metadata providers before emitting optional tags.
	 *
	 * @return bool
	 */
	private function has_social_meta_provider() {
		foreach ( array( 'WPSEO_VERSION', 'AIOSEO_VERSION', 'RANK_MATH_VERSION', 'SEOPRESS_VERSION', 'THE_SEO_FRAMEWORK_VERSION', 'SLIM_SEO_VERSION' ) as $constant ) {
			if ( defined( $constant ) ) {
				return true;
			}
		}
		foreach ( array( 'WPSEO_Options', 'AIOSEO\\Plugin\\Common\\Main', 'RankMath\\Helper', 'The_SEO_Framework\\Load' ) as $class ) {
			if ( class_exists( $class, false ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get a cache-busting local asset version.
	 *
	 * @param string $relative_path Relative asset path.
	 * @return string
	 */
	private function asset_version( $relative_path ) {
		$path = SHARENIVO_PLUGIN_DIR . ltrim( $relative_path, '/\\' );
		return file_exists( $path ) ? (string) filemtime( $path ) : SHARENIVO_VERSION;
	}
}
