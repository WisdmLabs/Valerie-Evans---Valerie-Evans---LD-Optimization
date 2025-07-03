<?php
/**
 * Admin settings class for LearnDash Certificate Builder
 *
 * @package LearnDash_Certificate_Builder
 */

namespace LearnDash_Certificate_Builder\Admin;

/**
 * Handles the admin settings page for certificate builder.
 */
class Settings {
	/**
	 * Initialize the admin settings.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'lcb_settings',
			'lcb_background_image',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			)
		);

		register_setting(
			'lcb_settings',
			'lcb_signature_image',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			)
		);

		register_setting(
			'lcb_settings',
			'lcb_element_coordinates',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_coordinates' ),
			)
		);
	}

	/**
	 * Sanitize coordinates data.
	 *
	 * @param string $input The coordinates JSON string.
	 * @return array The sanitized coordinates array.
	 */
	public function sanitize_coordinates( $input ) {
		// If input is already an array, return it.
		if ( is_array( $input ) ) {
			return $input;
		}

		// Try to decode JSON input.
		$coordinates = json_decode( $input, true );
		if ( ! is_array( $coordinates ) ) {
			return array();
		}

		// Sanitize each coordinate set.
		$sanitized = array();
		foreach ( $coordinates as $background_id => $elements ) {
			if ( ! is_array( $elements ) ) {
				continue;
			}

			$sanitized[ absint( $background_id ) ] = array();
			foreach ( $elements as $element => $pos ) {
				if ( isset( $pos['x'], $pos['y'] ) ) {
					// Store base coordinates.
					$sanitized[ absint( $background_id ) ][ sanitize_text_field( $element ) ] = array(
						'x' => absint( $pos['x'] ),
						'y' => absint( $pos['y'] ),
					);

					// Process font settings for username and course list elements.
					if ( in_array( $element, array( 'user_name', 'course_list' ), true ) ) {
						// Add font size if set.
						if ( isset( $pos['font_size'] ) ) {
							$sanitized[ absint( $background_id ) ][ $element ]['font_size'] = absint( $pos['font_size'] );
						}
						// Add font family if set.
						if ( isset( $pos['font_family'] ) ) {
							$sanitized[ absint( $background_id ) ][ $element ]['font_family'] = sanitize_text_field( $pos['font_family'] );
						}
						// Add text transform if set.
						if ( isset( $pos['text_transform'] ) ) {
							$sanitized[ absint( $background_id ) ][ $element ]['text_transform'] = sanitize_text_field( $pos['text_transform'] );
						}
					}
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Render the settings page content.
	 */
	public function render_settings_page() {
		// Get current settings.
		$background_id = get_option( 'lcb_background_image' );
		$signature_id  = get_option( 'lcb_signature_image' );
		$coordinates   = get_option( 'lcb_element_coordinates', array() );

		// Ensure coordinates is an array.
		if ( ! is_array( $coordinates ) ) {
			$coordinates = array();
		}

		// Set default coordinates if not set.
		if ( empty( $coordinates ) || ! isset( $coordinates['default'] ) ) {
			$coordinates['default'] = array(
				'user_name'   => array(
					'x' => 100,
					'y' => 100,
				),
				'course_list' => array(
					'x' => 100,
					'y' => 300,
				),
				'signature'   => array(
					'x' => 100,
					'y' => 400,
				),
			);
		}

		// Get coordinates for current background or use defaults.
		$current_coordinates = array();
		if ( $background_id && isset( $coordinates[ $background_id ] ) && is_array( $coordinates[ $background_id ] ) ) {
			$current_coordinates = $coordinates[ $background_id ];
		} elseif ( isset( $coordinates['default'] ) && is_array( $coordinates['default'] ) ) {
			$current_coordinates = $coordinates['default'];
		}

		// Include the admin template.
		include LCB_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}

	
}
