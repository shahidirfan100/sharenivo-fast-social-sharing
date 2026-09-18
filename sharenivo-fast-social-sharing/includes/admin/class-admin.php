<?php
/**
 * Admin functionality.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the settings screen and per-content controls.
 */
class Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_settings' ) );
		add_action( 'wp_ajax_sharenivo_save_settings_action', array( '\\ShareNivo\\Settings', 'ajax_save_settings' ) );
		add_action( 'wp_ajax_sharenova_save_settings_action', array( '\\ShareNivo\\Settings', 'ajax_save_settings' ) );
		add_action( 'admin_post_sharenivo_save_settings_action', array( '\\ShareNivo\\Settings', 'save_settings_redirect' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SHARENIVO_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Register the settings page.
	 */
	public function add_menu() {
		add_options_page(
			__( 'ShareNivo Settings', 'sharenivo-fast-social-sharing' ),
			__( 'ShareNivo', 'sharenivo-fast-social-sharing' ),
			'manage_options',
			'sharenivo',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		( new Settings() )->render_page();
	}

	/**
	 * Enqueue assets only where ShareNivo needs them.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$is_settings = 'settings_page_sharenivo' === $hook;
		$is_editor   = in_array( $hook, array( 'post.php', 'post-new.php' ), true );

		if ( ! $is_settings && ! $is_editor ) {
			return;
		}

		wp_enqueue_style(
			'sharenivo-admin',
			SHARENIVO_PLUGIN_URL . 'assets/css/admin.css',
			$is_settings ? array( 'wp-color-picker' ) : array(),
			$this->asset_version( 'assets/css/admin.css' )
		);

		if ( ! $is_settings ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'sharenivo-admin',
			SHARENIVO_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			$this->asset_version( 'assets/js/admin.js' ),
			true
		);
		wp_localize_script(
			'sharenivo-admin',
			'sharenivoAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'saving'  => __( 'Saving…', 'sharenivo-fast-social-sharing' ),
				'saved'   => __( 'Settings saved.', 'sharenivo-fast-social-sharing' ),
				'error'   => __( 'Could not save. Please try again.', 'sharenivo-fast-social-sharing' ),
				'copied'  => __( 'Copied.', 'sharenivo-fast-social-sharing' ),
			)
		);
	}

	/**
	 * Add the content-level override box to configured public post types.
	 */
	public function add_meta_boxes() {
		$settings   = Settings::get_settings();
		$post_types = ! empty( $settings['post_types'] ) ? $settings['post_types'] : array( 'post', 'page' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'sharenivo-content-settings',
				__( 'ShareNivo', 'sharenivo-fast-social-sharing' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render content-level placement controls.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		$value     = get_post_meta( $post->ID, '_sharenivo_override', true );
		$value     = is_array( $value ) ? $value : array();
		$mode      = isset( $value['mode'] ) ? sanitize_key( $value['mode'] ) : 'inherit';
		$locations = isset( $value['locations'] ) && is_array( $value['locations'] ) ? $value['locations'] : array();
		$share_meta = Share_Meta::get( $post->ID );

		wp_nonce_field( 'sharenivo_save_post_settings', 'sharenivo_post_nonce' );
		?>
		<div class="sharenivo-metabox">
			<p><label><strong><?php esc_html_e( 'Display rule', 'sharenivo-fast-social-sharing' ); ?></strong>
				<select name="sharenivo_override[mode]">
					<option value="inherit" <?php selected( $mode, 'inherit' ); ?>><?php esc_html_e( 'Use global settings', 'sharenivo-fast-social-sharing' ); ?></option>
					<option value="disabled" <?php selected( $mode, 'disabled' ); ?>><?php esc_html_e( 'Disable on this content', 'sharenivo-fast-social-sharing' ); ?></option>
					<option value="custom" <?php selected( $mode, 'custom' ); ?>><?php esc_html_e( 'Use selected locations', 'sharenivo-fast-social-sharing' ); ?></option>
				</select>
			</label></p>
			<fieldset><legend class="screen-reader-text"><?php esc_html_e( 'Custom ShareNivo locations', 'sharenivo-fast-social-sharing' ); ?></legend>
			<?php
			$labels = array(
				'floating' => __( 'Floating rail', 'sharenivo-fast-social-sharing' ),
				'inline'   => __( 'Inline buttons', 'sharenivo-fast-social-sharing' ),
				'sticky'   => __( 'Mobile sticky bar', 'sharenivo-fast-social-sharing' ),
				'popup'    => __( 'Popup', 'sharenivo-fast-social-sharing' ),
				'flyin'    => __( 'Fly-in', 'sharenivo-fast-social-sharing' ),
				'media'    => __( 'Image sharing', 'sharenivo-fast-social-sharing' ),
			);
			foreach ( $labels as $key => $label ) :
				?>
				<label class="sharenivo-metabox__check"><input type="checkbox" name="sharenivo_override[locations][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $locations, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
			</fieldset>
			<p class="description"><?php esc_html_e( 'Custom mode overrides which globally configured placements appear here.', 'sharenivo-fast-social-sharing' ); ?></p>
			<hr>
			<h4><?php esc_html_e( 'Custom sharing data', 'sharenivo-fast-social-sharing' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Blank fields use the normal post title, excerpt, and image. These values stay in WordPress and are only placed into the selected share link.', 'sharenivo-fast-social-sharing' ); ?></p>
			<p><label><?php esc_html_e( 'Share title', 'sharenivo-fast-social-sharing' ); ?><input type="text" class="widefat" name="sharenivo_share_meta[title]" value="<?php echo esc_attr( $share_meta['title'] ?? '' ); ?>"></label></p>
			<p><label><?php esc_html_e( 'Share description', 'sharenivo-fast-social-sharing' ); ?><textarea class="widefat" rows="3" name="sharenivo_share_meta[description]"><?php echo esc_textarea( $share_meta['description'] ?? '' ); ?></textarea></label></p>
			<p><label><?php esc_html_e( 'Custom X text', 'sharenivo-fast-social-sharing' ); ?><input type="text" class="widefat" name="sharenivo_share_meta[x_text]" value="<?php echo esc_attr( $share_meta['x_text'] ?? '' ); ?>"></label></p>
			<p><label><?php esc_html_e( 'Pinterest image URL', 'sharenivo-fast-social-sharing' ); ?><input type="url" class="widefat" name="sharenivo_share_meta[pinterest_image]" value="<?php echo esc_attr( $share_meta['pinterest_image'] ?? '' ); ?>" placeholder="https://"></label></p>
			<p><label><?php esc_html_e( 'Pinterest description', 'sharenivo-fast-social-sharing' ); ?><textarea class="widefat" rows="2" name="sharenivo_share_meta[pinterest_description]"><?php echo esc_textarea( $share_meta['pinterest_description'] ?? '' ); ?></textarea></label></p>
		</div>
		<?php
	}

	/**
	 * Save content-level overrides.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_post_settings( $post_id ) {
		if ( ! isset( $_POST['sharenivo_post_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sharenivo_post_nonce'] ) ), 'sharenivo_save_post_settings' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$mode = isset( $_POST['sharenivo_override']['mode'] ) ? sanitize_key( wp_unslash( $_POST['sharenivo_override']['mode'] ) ) : 'inherit';
		$mode      = in_array( $mode, array( 'inherit', 'disabled', 'custom' ), true ) ? $mode : 'inherit';
		$allowed   = array( 'floating', 'inline', 'sticky', 'popup', 'flyin', 'media' );
		$locations = isset( $_POST['sharenivo_override']['locations'] ) && is_array( $_POST['sharenivo_override']['locations'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['sharenivo_override']['locations'] ) ) : array();
		$locations = array_values( array_intersect( $allowed, $locations ) );
		$share_meta_input = isset( $_POST['sharenivo_share_meta'] ) && is_array( $_POST['sharenivo_share_meta'] ) ? wp_unslash( $_POST['sharenivo_share_meta'] ) : array();
		$share_meta       = Share_Meta::sanitize( $share_meta_input );
		if ( Share_Meta::has_values( $share_meta ) ) {
			update_post_meta( $post_id, Share_Meta::META_KEY, $share_meta );
		} else {
			delete_post_meta( $post_id, Share_Meta::META_KEY );
		}

		if ( 'inherit' === $mode ) {
			delete_post_meta( $post_id, '_sharenivo_override' );
			return;
		}

		update_post_meta(
			$post_id,
			'_sharenivo_override',
			array(
				'mode'      => $mode,
				'locations' => $locations,
			)
		);
	}

	/**
	 * Add a settings link to the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'options-general.php?page=sharenivo' ) ) . '">' . esc_html__( 'Settings', 'sharenivo-fast-social-sharing' ) . '</a>'
		);
		return $links;
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
