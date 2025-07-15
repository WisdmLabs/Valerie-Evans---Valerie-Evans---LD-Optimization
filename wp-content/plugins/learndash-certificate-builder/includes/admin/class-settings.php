<?php
/**
 * Admin Settings Handler for LearnDash Certificate Builder
 *
 * @file
 * @brief Manages plugin settings and admin interface.
 * @details This file contains the Settings class which handles all plugin
 * settings, including the admin interface, options management, and
 * certificate template configuration.
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */

namespace LearnDash_Certificate_Builder\Admin;

/**
 * Admin settings handler for certificate builder.
 *
 * @brief Class for managing plugin settings.
 * @details Handles all plugin settings functionality including:
 *          - Admin menu and pages
 *          - Settings fields and validation
 *          - Template configuration
 *          - Media management
 *          - Element coordinates
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */
class Settings {
	/**
	 * Initialize the admin settings.
	 *
	 * @brief Constructor - Set up settings.
	 * @details Initializes settings and adds necessary WordPress hooks
	 * for the admin interface.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );

	}

	/**
	 * Register plugin settings.
	 *
	 * @brief Register WordPress settings.
	 * @details Registers all plugin settings with WordPress settings API
	 * and defines their validation callbacks.
	 *
	 * @access public
	 * @since 1.0.0
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
	 * @brief Sanitize element coordinates.
	 * @details Validates and sanitizes the coordinates JSON data for certificate
	 * elements, including position and font settings.
	 *
	 * @param string $input The coordinates JSON string.
	 * @return array The sanitized coordinates array.
	 * @access public
	 * @since 1.0.0
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

					// Process font settings for username, course list and page number elements.
					if ( in_array( $element, array( 'user_name', 'course_list', 'page_number' ), true ) ) {
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
	 *
	 * @brief Display admin settings page.
	 * @details Outputs the HTML for the plugin's admin settings page,
	 * including forms and current settings. Handles coordinate management
	 * and default positions.
	 *
	 * @access public
	 * @since 1.0.0
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
				'page_number' => array(
					'x' => 100,
					'y' => 500,
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
