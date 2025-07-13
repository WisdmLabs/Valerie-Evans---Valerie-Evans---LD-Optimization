<?php
/**
 * Position Manager class for LearnDash Certificate Builder
 *
 * @package LearnDash_Certificate_Builder
 */

namespace LearnDash_Certificate_Builder\Position;

/**
 * Class PositionManager
 * Handles storage and retrieval of element coordinates for certificate generation
 */
class PositionManager {
	/**
	 * Option name for storing coordinates
	 *
	 * @var string
	 */
	private $coordinates_option = 'lcb_element_coordinates';

	/**
	 * Save coordinates for a specific background
	 *
	 * @param int   $background_id The background image ID.
	 * @param array $coordinates Array of element coordinates.
	 * @return bool Whether the save was successful.
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
	 * Get coordinates for a specific background
	 *
	 * @param int $background_id The background image ID.
	 * @return array Array of element coordinates.
	 */
	public function get_coordinates( $background_id ) {
		$all_coordinates = get_option( $this->coordinates_option, array() );
		return isset( $all_coordinates[ $background_id ] )
			? $all_coordinates[ $background_id ]
			: $this->get_default_coordinates();
	}


	/**
	 * Get default coordinates for elements
	 *
	 * @return array Default coordinates.
	 */
	public function get_default_coordinates() {
		return array(
			'user_name'   => array(
				'x' => 300,
				'y' => 200,
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
	 * Validate coordinates array structure
	 *
	 * @param array $coordinates Array of coordinates to validate.
	 * @return bool Whether the coordinates are valid.
	 */
	private function validate_coordinates( $coordinates ) {
		$required_elements = array( 'user_name', 'course_list', 'signature' );

		// Check if all required elements exist.
		foreach ( $required_elements as $element ) {
			if ( ! isset( $coordinates[ $element ] ) ) {
				return false;
			}

			// Check if x and y coordinates exist for each element.
			if ( ! isset( $coordinates[ $element ]['x'] ) || ! isset( $coordinates[ $element ]['y'] ) ) {
				return false;
			}

			// Validate coordinate values.
			if ( ! is_numeric( $coordinates[ $element ]['x'] ) || ! is_numeric( $coordinates[ $element ]['y'] ) ) {
				return false;
			}
		}

		return true;
	}
}
