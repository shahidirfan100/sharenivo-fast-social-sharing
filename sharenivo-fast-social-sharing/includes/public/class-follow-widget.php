<?php
/**
 * ShareNivo follow widget.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the profiles configured on the ShareNivo settings screen.
 */
class FollowWidget extends \WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'sharenivo_follow',
			__( 'ShareNivo Follow', 'sharenivo-fast-social-sharing' ),
			array(
				'classname'                   => 'widget_sharenivo_follow',
				'description'                 => __( 'Displays the social profiles configured in ShareNivo.', 'sharenivo-fast-social-sharing' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Render widget output.
	 *
	 * @param array $args     Theme widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		unset( $instance );
		$markup = ( new Renderer( Settings::get_settings() ) )->render_follow();
		if ( '' === $markup ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registered theme wrapper.
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all values.
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registered theme wrapper.
	}

	/**
	 * Render the widget admin note.
	 *
	 * @param array $instance Widget instance.
	 */
	public function form( $instance ) {
		unset( $instance );
		?>
		<p><?php esc_html_e( 'Profile links and the heading are managed under Settings → ShareNivo → Follow.', 'sharenivo-fast-social-sharing' ); ?></p>
		<?php
	}

	/**
	 * Widget contains no instance-specific settings.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Previous values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		unset( $new_instance, $old_instance );
		return array();
	}
}
