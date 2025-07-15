<?php
/**
 * Data Retriever for LearnDash Certificate Builder
 *
 * @file
 * @brief Handles data retrieval from LearnDash for certificate generation.
 * @details This file contains the DataRetriever class which is responsible for
 * fetching course completion data, user information, and other necessary data
 * from LearnDash for certificate generation.
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */

namespace LearnDash_Certificate_Builder\Data;

/**
 * Data retrieval handler for LearnDash integration.
 *
 * @brief Class for retrieving course and user data from LearnDash.
 * @details Handles all data retrieval operations from LearnDash including:
 *          - Getting completed courses for a user
 *          - Retrieving user display names
 *          - Fetching course completion dates
 *          - Checking course completion status
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */
class DataRetriever {
	/**
	 * Retrieve completed courses for a user.
	 *
	 * @brief Get completed courses for a user.
	 * @details Fetches all courses that the user has completed, including
	 * course title and completion date. Only returns courses where the user
	 * has met all completion requirements.
	 *
	 * @param int $user_id User ID to check for completed courses.
	 * @return array Array of completed courses with details:
	 *               - id: Course ID
	 *               - title: Course title
	 *               - completion_date: Formatted completion date
	 * @access public
	 * @since 1.0.0
	 */
	public function get_completed_courses( $user_id ) {
		// Validate user ID.
		$user_id = absint( $user_id );
		if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
			return array();
		}

		$courses      = array();
		$user_courses = learndash_get_user_courses_from_meta( $user_id );

		if ( ! is_array( $user_courses ) ) {
			return array();
		}

		foreach ( $user_courses as $course_id ) {
			$course_id = absint( $course_id );
			if ( ! $course_id || ! get_post( $course_id ) ) {
				continue;
			}

			if ( learndash_course_completed( $user_id, $course_id ) ) {
				$courses[] = array(
					'id'              => $course_id,
					'title'           => get_the_title( $course_id ),
					'completion_date' => $this->get_course_completion_date( $user_id, $course_id ),
				);
			}
		}

		return $courses;
	}

	/**
	 * Get the display name for a user.
	 *
	 * @brief Retrieve user's display name.
	 * @details Gets the display name for the specified user from WordPress.
	 * Returns empty string if user not found.
	 *
	 * @param int $user_id User ID to get display name for.
	 * @return string User's display name or empty string if not found.
	 * @access public
	 * @since 1.0.0
	 */
	public function get_user_display_name( $user_id ) {
		// Validate user ID.
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return '';
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
			return '';
		}

		return $user->display_name;
	}

	/**
	 * Get the completion date for a course.
	 *
	 * @brief Get course completion date.
	 * @details Retrieves and formats the date when the user completed the specified course.
	 * Uses WordPress date format settings for consistent display.
	 *
	 * @param int $user_id User ID to check completion for.
	 * @param int $course_id Course ID to get completion date for.
	 * @return string Formatted completion date or empty string if not completed.
	 * @access public
	 * @since 1.0.0
	 */
	public function get_course_completion_date( $user_id, $course_id ) {
		$completion_date = '';

		// Validate input parameters.
		$is_valid  = true;
		$user_id   = absint( $user_id );
		$course_id = absint( $course_id );

		if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
			$is_valid = false;
		}

		if ( $is_valid && ( ! $course_id || ! get_post( $course_id ) || get_post_type( $course_id ) !== 'sfwd-courses' ) ) {
			$is_valid = false;
		}

		if ( $is_valid ) {
			$completion_timestamp = get_user_meta( $user_id, 'course_completed_' . $course_id, true );
			if ( $completion_timestamp && is_numeric( $completion_timestamp ) ) {
				$completion_date = date_i18n( get_option( 'date_format' ), $completion_timestamp );
			}
		}

		return $completion_date;
	}

	/**
	 * Check if user has completed any courses.
	 *
	 * @brief Check for completed courses.
	 * @details Determines if the user has completed at least one course.
	 * Useful for quick validation before certificate generation.
	 *
	 * @param int $user_id User ID to check for completed courses.
	 * @return bool True if user has completed any courses, false otherwise.
	 * @access public
	 * @since 1.0.0
	 */
	public function has_completed_courses( $user_id ) {
		$completed_courses = $this->get_completed_courses( $user_id );
		return ! empty( $completed_courses );
	}

	/**
	 * Get total number of completed courses.
	 *
	 * @brief Count completed courses.
	 * @details Returns the total number of courses the user has completed.
	 * Useful for statistics and validation.
	 *
	 * @param int $user_id User ID to count completed courses for.
	 * @return int Number of completed courses.
	 * @access public
	 * @since 1.0.0
	 */
	public function get_completed_courses_count( $user_id ) {
		return count( $this->get_completed_courses( $user_id ) );
	}

	/**
	 * Check if user has completed a specific course.
	 *
	 * @brief Check specific course completion.
	 * @details Verifies if the user has completed all requirements for the specified course.
	 * Uses LearnDash's completion tracking system.
	 *
	 * @param int $user_id The user ID to check.
	 * @param int $course_id The course ID to check completion for.
	 * @return bool Whether the user has completed the course.
	 * @access public
	 * @since 1.0.0
	 */
	public function has_completed_course( $user_id, $course_id ) {
		// Validate user ID.
		$user_id = absint( $user_id );
		if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
			return false;
		}

		// Validate course ID.
		$course_id = absint( $course_id );
		if ( ! $course_id || ! get_post( $course_id ) || get_post_type( $course_id ) !== 'sfwd-courses' ) {
			return false;
		}

		return learndash_course_completed( $user_id, $course_id );
	}
}
