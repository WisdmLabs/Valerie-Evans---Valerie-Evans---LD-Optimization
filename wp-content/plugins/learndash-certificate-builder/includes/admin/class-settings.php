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
		add_action( 'wp_ajax_lcb_save_coordinates', array( $this, 'save_coordinates' ) );
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
					$sanitized[ absint( $background_id ) ][ sanitize_text_field( $element ) ] = array(
						'x' => absint( $pos['x'] ),
						'y' => absint( $pos['y'] ),
					);
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
				'user_name'       => array(
					'x' => 100,
					'y' => 100,
				),
				'completion_date' => array(
					'x' => 100,
					'y' => 200,
				),
				'course_list'     => array(
					'x' => 100,
					'y' => 300,
				),
				'signature'       => array(
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

	/**
	 * AJAX handler for saving element coordinates.
	 */
	public function save_coordinates() {
		// Verify nonce.
		check_ajax_referer( 'lcb_admin_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Get and validate the background ID.
		$background_id = isset( $_POST['background_id'] ) ? absint( wp_unslash( $_POST['background_id'] ) ) : 0;
		if ( ! $background_id ) {
			wp_send_json_error( 'Missing background ID.' );
		}

		// Get and validate the coordinates.
		$coordinates_json = isset( $_POST['coordinates'] ) ? sanitize_text_field( wp_unslash( $_POST['coordinates'] ) ) : '';
		if ( empty( $coordinates_json ) ) {
			wp_send_json_error( 'No coordinates data received.' );
		}

		// Decode JSON data.
		$raw_coordinates = json_decode( $coordinates_json, true );
		if ( ! is_array( $raw_coordinates ) ) {
			wp_send_json_error( 'Invalid coordinates data format.' );
		}

		// Sanitize coordinates.
		$sanitized_coordinates = array();
		foreach ( $raw_coordinates as $element => $pos ) {
			if ( isset( $pos['x'], $pos['y'] ) ) {
				$sanitized_coordinates[ sanitize_text_field( $element ) ] = array(
					'x' => absint( $pos['x'] ),
					'y' => absint( $pos['y'] ),
				);
			}
		}

		// Get current coordinates.
		$saved_coordinates = get_option( 'lcb_element_coordinates', array() );
		if ( ! is_array( $saved_coordinates ) ) {
			$saved_coordinates = array();
		}

		// Update coordinates for this background.
		$saved_coordinates[ $background_id ] = $sanitized_coordinates;

		// Save updated coordinates.
		if ( update_option( 'lcb_element_coordinates', $saved_coordinates ) ) {
			wp_send_json_success( 'Coordinates saved successfully.' );
		} else {
			wp_send_json_error( 'Failed to save coordinates.' );
		}
	}
}
