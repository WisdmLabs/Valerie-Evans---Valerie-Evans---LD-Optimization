<?php
/**
 * Data Retriever class for LearnDash Certificate Builder
 *
 * @package LearnDash_Certificate_Builder
 */

namespace LearnDash_Certificate_Builder\Data;

/**
 * Class DataRetriever
 * Handles fetching course completion and user data from LearnDash
 */
class DataRetriever {
	/**
	 * Get completed courses for a user
	 *
	 * @param int $user_id User ID.
	 * @return array Array of completed courses with details.
	 */
	public function get_completed_courses( $user_id ) {
		$courses      = array();
		$user_courses = learndash_user_get_enrolled_courses( $user_id );

		foreach ( $user_courses as $course_id ) {
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
	 * Get user display name
	 *
	 * @param int $user_id User ID.
	 * @return string User's display name.
	 */
	public function get_user_display_name( $user_id ) {
		$user = get_userdata( $user_id );
		return $user ? $user->display_name : '';
	}

	/**
	 * Get course completion date
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 * @return string Formatted completion date.
	 */
	public function get_course_completion_date( $user_id, $course_id ) {
		$completion_date = get_user_meta( $user_id, 'course_completed_' . $course_id, true );
		return $completion_date ? date_i18n( get_option( 'date_format' ), $completion_date ) : '';
	}

	/**
	 * Check if user has completed any courses
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user has completed courses.
	 */
	public function has_completed_courses( $user_id ) {
		$completed_courses = $this->get_completed_courses( $user_id );
		return ! empty( $completed_courses );
	}

	/**
	 * Get total number of completed courses
	 *
	 * @param int $user_id User ID.
	 * @return int Number of completed courses.
	 */
	public function get_completed_courses_count( $user_id ) {
		return count( $this->get_completed_courses( $user_id ) );
	}

	/**
	 * Check if user has completed a course
	 *
	 * @param int $user_id The user ID.
	 * @param int $course_id The course ID.
	 * @return bool Whether the user has completed the course.
	 */
	public function has_completed_course( $user_id, $course_id ) {
		return learndash_course_completed( $user_id, $course_id );
	}
}
