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

		if ( ! $url ) {
			return array();
		}

		return array(
			'post_id' => $post_id,
			'url'     => esc_url_raw( $url ),
			'title'   => wp_strip_all_tags( $title ? $title : get_bloginfo( 'name' ) ),
		);
	}

	/**
	 * Render a share location.
	 *
	 * @param string $location Location name.
	 * @param int    $post_id  Post ID.
	 * @return string
	 */
	public function render_share( $location, $post_id = 0 ) {
		$context = $this->get_context( $post_id );
		if ( empty( $context ) || empty( $this->settings['networks'] ) ) {
			return '';
		}

		$location = sanitize_key( $location );
		$buttons  = $this->render_button_group( $context, $this->settings['network_order'], $location );
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
		$style       = $this->settings['style'];
		$max_visible = ! empty( $style['more_button'] ) ? absint( $style['max_visible'] ) : count( $networks );
		$primary     = array_slice( $networks, 0, $max_visible );
		$secondary   = array_slice( $networks, $max_visible );
		$classes     = array(
			'sharenivo-share',
			'sharenivo-share--' . sanitize_html_class( $location ),
			'sharenivo-shape--' . sanitize_html_class( $style['shape'] ),
			'sharenivo-size--' . sanitize_html_class( $style['size'] ),
			'sharenivo-color--' . sanitize_html_class( $style['color_scheme'] ),
			'sharenivo-hover--' . sanitize_html_class( $style['hover'] ),
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
			if ( ! empty( $this->settings['locations']['floating']['hide_mobile'] ) ) {
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
		$url        = Networks::get_share_url( $network, $context['url'], $context['title'], $media );
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
		return '<template id="sharenivo-media-template"><div class="sharenivo-media-tools">' . $buttons . '</div></template>';
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
		$style    = $this->settings['style'];
		$vertical = absint( $this->settings['locations']['floating']['vertical'] );
		return sprintf(
			':root{--sn-brand:%1$s;--sn-brand-hover:%2$s;--sn-icon:%3$s;--sn-gap:%4$dpx;--sn-floating-y:%5$d%%;}',
			$style['brand_bg'],
			$style['brand_hover'],
			$style['icon_color'],
			absint( $style['gap'] ),
			$vertical
		);
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
