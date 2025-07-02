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
	private const COORDINATES_OPTION = 'lcb_element_coordinates';

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

		$all_coordinates                   = get_option( self::COORDINATES_OPTION, array() );
		$all_coordinates[ $background_id ] = $coordinates;

		return update_option( self::COORDINATES_OPTION, $all_coordinates );
	}

	/**
	 * Get coordinates for a specific background
	 *
	 * @param int $background_id The background image ID.
	 * @return array Array of element coordinates.
	 */
	public function get_coordinates( $background_id ) {
		$all_coordinates = get_option( self::COORDINATES_OPTION, array() );
		return isset( $all_coordinates[ $background_id ] )
			? $all_coordinates[ $background_id ]
			: $this->get_default_coordinates();
	}

	/**
	 * Delete coordinates for a specific background
	 *
	 * @param int $background_id The background image ID.
	 * @return bool Whether the deletion was successful.
	 */
	public function delete_coordinates( $background_id ) {
		$all_coordinates = get_option( self::COORDINATES_OPTION, array() );

		if ( isset( $all_coordinates[ $background_id ] ) ) {
			unset( $all_coordinates[ $background_id ] );
			return update_option( self::COORDINATES_OPTION, $all_coordinates );
		}

		return true;
	}

	/**
	 * Get default coordinates for elements
	 *
	 * @return array Default coordinates.
	 */
	public function get_default_coordinates() {
		return array(
			'user_name'       => array(
				'x' => 300,
				'y' => 200,
			),
			'completion_date' => array(
				'x' => 300,
				'y' => 400,
			),
			'course_list'     => array(
				'x' => 300,
				'y' => 600,
			),
			'signature'       => array(
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
		$required_elements = array( 'user_name', 'completion_date', 'course_list', 'signature' );

		// Check if all required elements exist
		foreach ( $required_elements as $element ) {
			if ( ! isset( $coordinates[ $element ] ) ) {
				return false;
			}

			// Check if x and y coordinates exist for each element
			if ( ! isset( $coordinates[ $element ]['x'] ) || ! isset( $coordinates[ $element ]['y'] ) ) {
				return false;
			}

			// Validate coordinate values
			if ( ! is_numeric( $coordinates[ $element ]['x'] ) || ! is_numeric( $coordinates[ $element ]['y'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Update a single element's coordinates
	 *
	 * @param int    $background_id The background image ID.
	 * @param string $element_type The type of element to update.
	 * @param array  $coordinates Array containing x and y coordinates.
	 * @return bool Whether the update was successful.
	 */
	public function update_element_coordinates( $background_id, $element_type, $coordinates ) {
		if ( ! isset( $coordinates['x'] ) || ! isset( $coordinates['y'] ) ) {
			return false;
		}

		$all_coordinates                  = $this->get_coordinates( $background_id );
		$all_coordinates[ $element_type ] = array(
			'x' => (int) $coordinates['x'],
			'y' => (int) $coordinates['y'],
		);

		return $this->save_coordinates( $background_id, $all_coordinates );
	}

	/**
	 * Get coordinates for a specific element
	 *
	 * @param int    $background_id The background image ID.
	 * @param string $element_type The type of element.
	 * @return array|false Coordinates array or false if not found.
	 */
	public function get_element_coordinates( $background_id, $element_type ) {
		$coordinates = $this->get_coordinates( $background_id );
		return isset( $coordinates[ $element_type ] ) ? $coordinates[ $element_type ] : false;
	}
}
