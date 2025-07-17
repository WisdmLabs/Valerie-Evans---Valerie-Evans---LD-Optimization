<?php
/**
 * Position Manager for LearnDash Certificate Builder.
 *
 * @file
 * @brief Manages element positioning on certificate templates.
 * @details This file contains the PositionManager class which handles the
 * positioning and layout of elements on certificate templates, including
 * text, images, and dynamic content like course lists.
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */

namespace LearnDash_Certificate_Builder\Position;

/**
 * Position management handler for certificate elements.
 *
 * @brief Class for managing element positions on certificates.
 * @details Handles positioning and layout management for certificate elements:
 *          - Storing element coordinates
 *          - Converting between units (px to mm)
 *          - Managing element dimensions
 *          - Calculating layout positions
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */
class PositionManager {
	/**
	 * WordPress option name for storing element coordinates.
	 *
	 * @brief Option key for coordinates storage.
	 * @var string
	 * @access private
	 * @since 1.0.0
	 */
	private $coordinates_option = 'lcb_element_coordinates';

	/**
	 * Stores element coordinates for a certificate background.
	 *
	 * @brief Saves element coordinates for a specific background.
	 * @details Validates and stores the coordinates for certificate elements
	 * associated with a specific background image.
	 *
	 * @param int   $background_id The background image ID.
	 * @param array $coordinates Array of element coordinates.
	 * @return bool Whether the save was successful.
	 * @access public
	 * @since 1.0.0
	 */
	public function save_coordinates( $background_id, $coordinates ) {
		if ( ! $this->validate_coordinates( $coordinates ) ) {
			return false;
		}

		$all_coordinates                   = get_option( $this->coordinates_option, array() );
		$all_coordinates[ $background_id ] = $coordinates;

		return update_option( $this->coordinates_option, $all_coordinates );
	}

	/**
	 * Retrieves element coordinates for a certificate background.
	 *
	 * @brief Gets element coordinates for a specific background.
	 * @details Retrieves stored coordinates for certificate elements. Returns
	 * default coordinates if none are found for the specified background.
	 *
	 * @param int $background_id The background image ID.
	 * @return array Array of element coordinates.
	 * @access public
	 * @since 1.0.0
	 */
	public function get_coordinates( $background_id ) {
		$all_coordinates = get_option( $this->coordinates_option, array() );
		return isset( $all_coordinates[ $background_id ] )
			? $all_coordinates[ $background_id ]
			: $this->get_default_coordinates();
	}


	/**
	 * Provides default element coordinates.
	 *
	 * @brief Gets default coordinates for certificate elements.
	 * @details Returns predefined coordinates for essential certificate elements:
	 * - User name placement
	 * - Course list position
	 * - Signature location
	 * - Certification numbers
	 * - Total hours
	 *
	 * @return array Default coordinates.
	 * @access public
	 * @since 1.0.0
	 */
	public function get_default_coordinates() {
		return array(
			'user_name'   => array(
				'x' => 300,
				'y' => 200,
			),
			'bacb_number' => array(
				'x' => 300,
				'y' => 300,
			),
			'qaba_number' => array(
				'x' => 300,
				'y' => 350,
			),
			'ibao_number' => array(
				'x' => 300,
				'y' => 400,
			),
			'total_hours' => array(
				'x' => 300,
				'y' => 450,
			),
			'course_list' => array(
				'x' => 300,
				'y' => 600,
			),
			'signature'   => array(
				'x' => 500,
				'y' => 800,
			),
		);
	}

	/**
	 * Validates coordinate array structure.
	 *
	 * @brief Validates the structure of coordinates array.
	 * @details Ensures coordinates array contains all required elements and
	 * valid numeric values for x and y coordinates.
	 *
	 * @param array $coordinates Array of coordinates to validate.
	 * @return bool Whether the coordinates are valid.
	 * @access private
	 * @since 1.0.0
	 */
	private function validate_coordinates( $coordinates ) {
		$required_elements = array(
			'user_name',
			'bacb_number',
			'qaba_number',
			'ibao_number',
			'total_hours',
			'course_list',
			'signature',
		);
		$is_valid          = true;

		// Check if all required elements exist and have valid coordinates.
		foreach ( $required_elements as $element ) {
			$element_valid = isset( $coordinates[ $element ] ) &&
				isset( $coordinates[ $element ]['x'] ) &&
				isset( $coordinates[ $element ]['y'] ) &&
				is_numeric( $coordinates[ $element ]['x'] ) &&
				is_numeric( $coordinates[ $element ]['y'] );

			if ( ! $element_valid ) {
				$is_valid = false;
				break;
			}
		}

		return $is_valid;
	}
}
