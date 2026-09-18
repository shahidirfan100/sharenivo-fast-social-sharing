<?php
/**
 * Per-content sharing metadata.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes and retrieves ShareNivo's optional post-level share data.
 */
class Share_Meta {

	/**
	 * Post meta key.
	 *
	 * @var string
	 */
	const META_KEY = '_sharenivo_share_meta';

	/**
	 * Get sanitized metadata for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get( $post_id ) {
		$value = get_post_meta( absint( $post_id ), self::META_KEY, true );
		return self::sanitize( is_array( $value ) ? $value : array() );
	}

	/**
	 * Sanitize submitted metadata.
	 *
	 * @param mixed $value Submitted data.
	 * @return array
	 */
	public static function sanitize( $value ) {
		$value = is_array( $value ) ? $value : array();
		$clean = array(
			'title'                 => sanitize_text_field( $value['title'] ?? '' ),
			'description'           => sanitize_textarea_field( $value['description'] ?? '' ),
			'x_text'                => sanitize_text_field( $value['x_text'] ?? '' ),
			'pinterest_image'       => esc_url_raw( $value['pinterest_image'] ?? '' ),
			'pinterest_description' => sanitize_textarea_field( $value['pinterest_description'] ?? '' ),
		);

		return array_filter(
			$clean,
			static function ( $item ) {
				return '' !== $item;
			}
		);
	}

	/**
	 * Check whether metadata contains an actual override.
	 *
	 * @param array $value Metadata.
	 * @return bool
	 */
	public static function has_values( $value ) {
		return ! empty( self::sanitize( $value ) );
	}
}
