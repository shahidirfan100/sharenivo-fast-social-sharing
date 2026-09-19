<?php
/**
 * Frontend markup renderer.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders all ShareNivo share and follow interfaces.
 */
class Renderer {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Instance counter for unique accessible controls.
	 *
	 * @var int
	 */
	private static $instance = 0;

	/**
	 * Constructor.
	 *
	 * @param array $settings Normalized settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Build share context.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_context( $post_id = 0 ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			$post_id = absint( get_queried_object_id() );
		}

		$url   = $post_id ? get_permalink( $post_id ) : home_url( '/' );
		$title = $post_id ? get_the_title( $post_id ) : wp_get_document_title();
		$meta  = $post_id ? Share_Meta::get( $post_id ) : array();
		$description = $post_id ? wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 40, '…' ) : '';
		$description = $meta['description'] ?? $description;
		$description = $description ? $description : wp_strip_all_tags( get_bloginfo( 'description' ) );

		if ( ! $url ) {
			return array();
		}

		return array(
			'post_id'               => $post_id,
			'url'                   => esc_url_raw( $url ),
			'title'                 => wp_strip_all_tags( $meta['title'] ?? ( $title ? $title : get_bloginfo( 'name' ) ) ),
			'description'           => wp_strip_all_tags( $description ),
			'x_text'                => wp_strip_all_tags( $meta['x_text'] ?? ( $meta['title'] ?? ( $title ? $title : get_bloginfo( 'name' ) ) ) ),
			'pinterest_image'       => esc_url_raw( $meta['pinterest_image'] ?? '' ),
			'pinterest_description' => wp_strip_all_tags( $meta['pinterest_description'] ?? $description ),
			'featured_image'        => $post_id ? esc_url_raw( get_the_post_thumbnail_url( $post_id, 'full' ) ?: '' ) : '',
		);
	}

	/**
	 * Render a share location.
	 *
	 * @param string $location Location name.
	 * @param int    $post_id  Post ID.
	 * @param array  $networks Optional selected network keys.
	 * @return string
	 */
	public function render_share( $location, $post_id = 0, $networks = array() ) {
		$context = $this->get_context( $post_id );
		if ( empty( $context ) || empty( $this->settings['networks'] ) ) {
			return '';
		}

		$location = sanitize_key( $location );
		$networks = ! empty( $networks ) ? Networks::sanitize_network_list( $networks ) : $this->settings['network_order'];
		$buttons  = $this->render_button_group( $context, $networks, $location );
		if ( '' === $buttons ) {
			return '';
		}

		if ( 'popup' === $location ) {
			return $this->render_popup( $buttons );
		}

		if ( 'flyin' === $location ) {
			return $this->render_flyin( $buttons );
		}

		return $buttons;
	}

	/**
	 * Render an accessible click-to-share quote.
	 *
	 * @param array $attributes Quote attributes.
	 * @param int   $post_id    Post ID.
	 * @return string
	 */
	public function render_quote( $attributes = array(), $post_id = 0 ) {
		$context = $this->get_context( $post_id );
		$text    = isset( $attributes['text'] ) ? sanitize_textarea_field( $attributes['text'] ) : '';
		$style   = isset( $attributes['style'] ) ? sanitize_key( $attributes['style'] ) : 'card';
		$style   = in_array( $style, array( 'card', 'minimal', 'accent', 'bordered' ), true ) ? $style : 'card';
		if ( empty( $context ) || '' === trim( $text ) ) {
			return '';
		}

		$url = Networks::get_share_url( 'x', $context['url'], $context['title'], '', $text );
		return sprintf(
			'<figure class="sharenivo-quote sharenivo-quote--%1$s"><blockquote><p>%2$s</p></blockquote><figcaption><a href="%3$s" target="_blank" rel="noopener noreferrer" aria-label="%4$s"><span class="sharenivo-quote__icon" aria-hidden="true">%5$s</span><span>%6$s</span></a></figcaption></figure>',
			esc_attr( $style ),
			esc_html( $text ),
			esc_url( $url ),
			esc_attr__( 'Share this quote on X', 'sharenivo-fast-social-sharing' ),
			wp_kses( Networks::get_icon( 'x' ), self::svg_allowed_html() ),
			esc_html__( 'Share this quote', 'sharenivo-fast-social-sharing' )
		);
	}

	/**
	 * Render a group of share buttons.
	 *
	 * @param array  $context  Share context.
	 * @param array  $networks Networks.
	 * @param string $location Location.
	 * @param string $media    Optional media URL.
	 * @return string
	 */
	private function render_button_group( $context, $networks, $location, $media = '' ) {
		$registry = Networks::get_share_networks();
		$networks = Networks::sanitize_network_list( $networks, $registry );
		if ( empty( $networks ) ) {
			return '';
		}

		++self::$instance;
		$instance_id = 'sharenivo-more-' . self::$instance;
		$style       = $this->get_location_style( $location );
		$max_visible = ! empty( $style['more_button'] ) ? absint( $style['max_visible'] ) : count( $networks );
		if ( 'floating' === $location && ! empty( $this->settings['locations']['floating']['max_buttons'] ) ) {
			$networks = array_slice( $networks, 0, absint( $this->settings['locations']['floating']['max_buttons'] ) );
		}
		$max_visible = min( $max_visible, count( $networks ) );
		$primary     = array_slice( $networks, 0, $max_visible );
		$secondary   = array_slice( $networks, $max_visible );
		$classes     = array(
			'sharenivo-share',
			'sharenivo-share--' . sanitize_html_class( $location ),
			'sharenivo-shape--' . sanitize_html_class( $style['shape'] ),
			'sharenivo-size--' . sanitize_html_class( $style['size'] ),
			'sharenivo-color--' . sanitize_html_class( $style['color_scheme'] ),
			'sharenivo-hover--' . sanitize_html_class( $style['hover'] ),
			'sharenivo-hover-colors--' . sanitize_html_class( $style['hover_colors'] ?? 'shared' ),
			'sharenivo-entrance--' . sanitize_html_class( $style['entrance'] ),
		);

		if ( ! empty( $style['show_labels'] ) ) {
			$classes[] = 'sharenivo-share--labels';
		}
		if ( 'inline' === $location ) {
			$classes[] = 'sharenivo-align--' . sanitize_html_class( $this->settings['locations']['inline']['alignment'] );
		}
		if ( 'sticky' === $location ) {
			$classes[] = 'sharenivo-sticky--' . sanitize_html_class( $this->settings['locations']['sticky']['width'] );
			$classes[] = 'sharenivo-sticky-align--' . sanitize_html_class( $this->settings['locations']['sticky']['alignment'] );
		}
		if ( 'floating' === $location ) {
			$classes[] = 'sharenivo-side--' . sanitize_html_class( $this->settings['locations']['floating']['side'] );
			$mobile_position = $this->settings['locations']['floating']['mobile_position'] ?? 'off';
			if ( in_array( $mobile_position, array( 'top', 'bottom' ), true ) ) {
				$classes[] = 'sharenivo-mobile--' . sanitize_html_class( $mobile_position );
			} elseif ( ! empty( $this->settings['locations']['floating']['hide_mobile'] ) ) {
				$classes[] = 'sharenivo-hide-mobile';
			}
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-sharenivo-location="<?php echo esc_attr( $location ); ?>">
			<div class="sharenivo-share__primary">
				<?php foreach ( $primary as $network ) : ?>
					<?php echo $this->render_button( $network, $registry[ $network ], $context, $media ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated from escaped local values. ?>
				<?php endforeach; ?>
				<?php if ( ! empty( $secondary ) ) : ?>
					<button type="button" class="sharenivo-button sharenivo-button--more" aria-expanded="false" aria-controls="<?php echo esc_attr( $instance_id ); ?>">
						<span class="sharenivo-button__icon"><?php echo wp_kses( Networks::get_icon( 'more' ), self::svg_allowed_html() ); ?></span>
						<span class="sharenivo-button__label"><?php esc_html_e( 'More', 'sharenivo-fast-social-sharing' ); ?></span>
					</button>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $secondary ) ) : ?>
				<div class="sharenivo-share__more" id="<?php echo esc_attr( $instance_id ); ?>" hidden>
					<?php foreach ( $secondary as $network ) : ?>
						<?php echo $this->render_button( $network, $registry[ $network ], $context, $media ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<span class="sharenivo-copy-status" role="status" aria-live="polite"></span>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render one network button.
	 *
	 * @param string $network Network key.
	 * @param array  $data    Network data.
	 * @param array  $context Share context.
	 * @param string $media   Optional media URL.
	 * @return string
	 */
	private function render_button( $network, $data, $context, $media = '' ) {
		$media      = $media ? $media : ( 'pinterest' === $network ? ( $context['pinterest_image'] ?? '' ) : '' );
		$url        = Networks::get_share_url( $network, $context['url'], $context['title'], $media, $context['description'] ?? '', $context['pinterest_description'] ?? '', $context['x_text'] ?? '' );
		$label      = $data['label'];
		$class_name = 'sharenivo-button sharenivo-button--' . sanitize_html_class( $network );
		$icon       = '<span class="sharenivo-button__icon">' . wp_kses( Networks::get_icon( $network ), self::svg_allowed_html() ) . '</span>';
		$text       = '<span class="sharenivo-button__label">' . esc_html( $label ) . '</span>';
		$aria       = sprintf(
			/* translators: %s: social network name. */
			__( 'Share on %s', 'sharenivo-fast-social-sharing' ),
			$label
		);

		if ( 'copy' === $network ) {
			$html = sprintf(
				'<button type="button" class="%1$s" data-sharenivo-copy="%2$s" aria-label="%3$s">%4$s%5$s</button>',
				esc_attr( $class_name ),
				esc_url( $context['url'] ),
				esc_attr__( 'Copy page link', 'sharenivo-fast-social-sharing' ),
				$icon,
				$text
			);
		} elseif ( 'email' === $network ) {
			$html = sprintf(
				'<a class="%1$s" href="%2$s" aria-label="%3$s" data-sharenivo-network="%4$s">%5$s%6$s</a>',
				esc_attr( $class_name ),
				esc_url( $url ),
				esc_attr( $aria ),
				esc_attr( $network ),
				$icon,
				$text
			);
		} else {
			$html = sprintf(
				'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s" data-sharenivo-network="%4$s">%5$s%6$s</a>',
				esc_attr( $class_name ),
				esc_url( $url ),
				esc_attr( $aria ),
				esc_attr( $network ),
				$icon,
				$text
			);
		}

		/**
		 * Filter one complete share button.
		 *
		 * @param string $html     Escaped button HTML.
		 * @param string $network Network key.
		 * @param string $url     Share URL.
		 * @param array  $settings Normalized settings.
		 */
		$html = apply_filters( 'sharenivo_button_html', $html, $network, $url, $this->settings );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Retained for backward compatibility with existing ShareNova integrations.
		return apply_filters( 'sharenova_button_html', $html, $network, $url, $this->settings );
	}

	/**
	 * Render popup shell.
	 *
	 * @param string $buttons Buttons markup.
	 * @return string
	 */
	private function render_popup( $buttons ) {
		$config = $this->settings['locations']['popup'];
		ob_start();
		?>
		<div class="sharenivo-prompt sharenivo-prompt--popup" data-sharenivo-prompt="popup" data-trigger="<?php echo esc_attr( $config['trigger'] ); ?>" data-trigger-value="<?php echo esc_attr( $config['trigger_value'] ); ?>" data-frequency="<?php echo esc_attr( $config['frequency'] ); ?>" hidden>
			<div class="sharenivo-prompt__backdrop" data-sharenivo-close></div>
			<div class="sharenivo-prompt__dialog" role="dialog" aria-modal="true" aria-labelledby="sharenivo-popup-title" tabindex="-1">
				<button type="button" class="sharenivo-prompt__close" data-sharenivo-close aria-label="<?php esc_attr_e( 'Close sharing dialog', 'sharenivo-fast-social-sharing' ); ?>"><?php echo wp_kses( Networks::get_icon( 'close' ), self::svg_allowed_html() ); ?></button>
				<span class="sharenivo-prompt__accent" aria-hidden="true"></span>
				<h2 id="sharenivo-popup-title"><?php echo esc_html( $config['title'] ); ?></h2>
				<p><?php echo esc_html( $config['message'] ); ?></p>
				<?php echo $buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render fly-in shell.
	 *
	 * @param string $buttons Buttons markup.
	 * @return string
	 */
	private function render_flyin( $buttons ) {
		$config = $this->settings['locations']['flyin'];
		ob_start();
		?>
		<aside class="sharenivo-prompt sharenivo-prompt--flyin sharenivo-prompt--<?php echo esc_attr( $config['side'] ); ?>" data-sharenivo-prompt="flyin" data-trigger="<?php echo esc_attr( $config['trigger'] ); ?>" data-trigger-value="<?php echo esc_attr( $config['trigger_value'] ); ?>" data-frequency="<?php echo esc_attr( $config['frequency'] ); ?>" aria-labelledby="sharenivo-flyin-title" hidden>
			<button type="button" class="sharenivo-prompt__close" data-sharenivo-close aria-label="<?php esc_attr_e( 'Close sharing prompt', 'sharenivo-fast-social-sharing' ); ?>"><?php echo wp_kses( Networks::get_icon( 'close' ), self::svg_allowed_html() ); ?></button>
			<span class="sharenivo-prompt__accent" aria-hidden="true"></span>
			<h2 id="sharenivo-flyin-title"><?php echo esc_html( $config['title'] ); ?></h2>
			<p><?php echo esc_html( $config['message'] ); ?></p>
			<?php echo $buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</aside>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the media overlay template.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function render_media_template( $post_id = 0 ) {
		$config  = $this->settings['locations']['media'];
		$context = $this->get_context( $post_id );
		if ( empty( $context ) || empty( $config['networks'] ) ) {
			return '';
		}

		$buttons = $this->render_button_group( $context, $config['networks'], 'media' );
		$meta    = $context['post_id'] ? Share_Meta::get( $context['post_id'] ) : array();
		$attrs   = sprintf(
			'data-position="%1$s" data-featured-image="%2$s" data-post-image="%3$s" data-post-description="%4$s" data-share-title="%5$s" data-share-description="%6$s"',
			esc_attr( $config['position'] ?? 'top-right' ),
			esc_attr( $context['featured_image'] ?? '' ),
			esc_attr( $meta['pinterest_image'] ?? '' ),
			esc_attr( $meta['pinterest_description'] ?? '' ),
			esc_attr( $context['title'] ),
			esc_attr( $context['description'] ?? '' )
		);
		return '<template id="sharenivo-media-template" ' . $attrs . '><div class="sharenivo-media-tools sharenivo-media-tools--' . esc_attr( $config['position'] ?? 'top-right' ) . '">' . $buttons . '</div></template>';
	}

	/**
	 * Render follow profiles.
	 *
	 * @return string
	 */
	public function render_follow() {
		$config   = $this->settings['follow'];
		$registry = Networks::get_follow_networks();
		if ( empty( $config['enabled'] ) || empty( $config['profiles'] ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="sharenivo-follow">
			<?php if ( $config['heading'] ) : ?><h2 class="sharenivo-follow__heading"><?php echo esc_html( $config['heading'] ); ?></h2><?php endif; ?>
			<div class="sharenivo-follow__links">
				<?php
				foreach ( $config['profiles'] as $network => $profile_url ) :
					if ( ! isset( $registry[ $network ] ) ) {
						continue;
					}
					$follow_aria = sprintf(
						/* translators: %s: social network name. */
						__( 'Follow on %s', 'sharenivo-fast-social-sharing' ),
						$registry[ $network ]['label']
					);
					?>
					<a class="sharenivo-follow__link sharenivo-button--<?php echo esc_attr( $network ); ?>" style="--sn-network:<?php echo esc_attr( $registry[ $network ]['color'] ); ?>" href="<?php echo esc_url( $profile_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $follow_aria ); ?>"><span><?php echo wp_kses( Networks::get_icon( $network ), self::svg_allowed_html() ); ?></span><b><?php echo esc_html( $registry[ $network ]['label'] ); ?></b></a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate sanitized runtime CSS variables.
	 *
	 * @return string
	 */
	public function get_dynamic_css() {
		$style       = $this->settings['style'];
		$brand_bg    = sanitize_hex_color( $style['brand_bg'] ?? '' );
		$brand_hover = sanitize_hex_color( $style['brand_hover'] ?? '' );
		$icon_color  = sanitize_hex_color( $style['icon_color'] ?? '' );
		$gap         = min( 30, absint( $style['gap'] ?? 8 ) );
		$vertical    = max( 10, min( 90, absint( $this->settings['locations']['floating']['vertical'] ?? 50 ) ) );
		return sprintf(
			':root{--sn-brand:%1$s;--sn-brand-hover:%2$s;--sn-icon:%3$s;--sn-gap:%4$dpx;--sn-floating-y:%5$d%%;}',
			$brand_bg ? $brand_bg : '#3a1f4f',
			$brand_hover ? $brand_hover : '#ff5a4f',
			$icon_color ? $icon_color : '#ffffff',
			$gap,
			$vertical
		);
	}

	/**
	 * Resolve the global or floating-specific design settings.
	 *
	 * @param string $location Location key.
	 * @return array
	 */
	private function get_location_style( $location ) {
		$style = $this->settings['style'];
		if ( 'floating' === $location && empty( $this->settings['locations']['floating']['style']['inherit'] ) ) {
			$floating = $this->settings['locations']['floating']['style'];
			$style    = array_merge(
				$style,
				array(
					'shape'        => $floating['shape'],
					'size'         => $floating['size'],
					'color_scheme' => $floating['color_scheme'],
					'hover'        => $floating['hover'],
					'hover_colors' => $floating['hover_colors'],
				)
			);
		}
		return $style;
	}

	/**
	 * Allowed inline SVG markup.
	 *
	 * @return array
	 */
	private static function svg_allowed_html() {
		return array(
			'svg'  => array( 'viewbox' => true, 'aria-hidden' => true, 'focusable' => true ),
			'path' => array( 'd' => true ),
		);
	}
}
