<?php
/**
 * Prerequisites logic handler.
 *
 * @package TutorPro\TutorPrerequisites
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

namespace TUTOR_PREREQUISITES;

defined( 'ABSPATH' ) || exit;

use stdClass;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use TUTOR\Tutor_Base;
use WP_Error;

/**
 * Class Prerequisites
 *
 * @since 2.0.0
 */
class Prerequisites extends Tutor_Base {

	/**
	 * Course prerequisite meta key
	 *
	 * @since 3.7.1
	 */
	const META_KEY = '_tutor_course_prerequisites_ids';

	/**
	 * Register hooks
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'save_post_' . $this->course_post_type, array( $this, 'save_course_meta' ) );
		add_action( 'tutor_course/single/tab/info/before', array( $this, 'prerequisites_courses_lists' ) );

		add_action( 'tutor/course/single/content/before/all', array( $this, 'prerequisite_redirect' ), 101, 2 );
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );
		add_filter( 'tutor_user_can_complete_course', array( $this, 'can_user_complete_course' ), 10, 3 );
	}

	/**
	 * Save prerequisites course meta.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_ID post id.
	 *
	 * @return void
	 */
	public function save_course_meta( $post_ID ) {
		$prerequisites_main_edit  = Input::post( '_tutor_prerequisites_main_edit' );
		$prerequisites_course_ids = Input::post( self::META_KEY, array(), Input::TYPE_ARRAY );
		$prerequisites_course_ids = is_array( $prerequisites_course_ids ) ? $prerequisites_course_ids : array();

		// Filter non numeric.
		$prerequisites_course_ids = array_filter(
			$prerequisites_course_ids,
			function ( $id ) {
				return $id && is_numeric( $id );
			}
		);

		if ( $prerequisites_main_edit ) {
			if ( is_array( $prerequisites_course_ids ) && count( $prerequisites_course_ids ) ) {
				update_post_meta( $post_ID, self::META_KEY, $prerequisites_course_ids );
			} else {
				delete_post_meta( $post_ID, self::META_KEY );
			}
		}
	}

	/**
	 * Pre-requisites course lists
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function prerequisites_courses_lists() {
		$post_id = get_the_ID();

		$course_prerequisites_ids = self::get_prerequisite_course_ids( $post_id );
		if ( count( $course_prerequisites_ids ) ) {
			$show_prerequisites = apply_filters( 'tutor_pro_show_prerequisites_courses', true, $post_id, $course_prerequisites_ids );
			if ( $show_prerequisites ) {
				include dirname( __DIR__ ) . '/views/course-prerequisites.php';
			}
		}
	}

	/**
	 * Pre-requisites redirect
	 *
	 * @since 2.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function prerequisite_redirect( $course_id ) {
		$required_complete       = false;
		$saved_prerequisites_ids = self::get_prerequisite_course_ids( $course_id );

		if ( count( $saved_prerequisites_ids ) ) {
			$redirect = apply_filters( 'tutor_pro_prerequisites_redirect', true, $course_id, $saved_prerequisites_ids );
			if ( ! $redirect ) {
				return;
			}

			foreach ( $saved_prerequisites_ids as $prerequisite_course_id ) {
				if ( ! tutor_utils()->is_completed_course( $prerequisite_course_id ) ) {
					$required_complete = true;
				}
			}

			$user_id = get_current_user_id();
			if ( tutor_utils()->has_user_course_content_access( $user_id, $course_id ) ) {
				$required_complete = false;
			}
		}

		if ( $required_complete ) {
			$link = get_permalink( $course_id ) . '#tutor_prereq';
			wp_safe_redirect( $link );
			exit;
		}
	}

	/**
	 * Extend course details response
	 *
	 * @since 3.0.0
	 *
	 * @param array $data course data.
	 *
	 * @return array
	 */
	public function extend_course_details_response( $data ) {
		$course_id                = $data['ID'];
		$course_prerequisites_ids = self::get_prerequisite_course_ids( $course_id );
		if ( count( $course_prerequisites_ids ) ) {
			$courses = get_posts(
				array(
					'post_type'      => tutor()->course_post_type,
					'post__in'       => $course_prerequisites_ids,
					'posts_per_page' => -1,
				)
			);

			$items = array();
			foreach ( $courses as $course ) {
				$tmp                 = new stdClass();
				$tmp->id             = $course->ID;
				$tmp->post_title     = $course->post_title;
				$tmp->featured_image = get_the_post_thumbnail_url( $course->ID );

				if ( ! $tmp->featured_image ) {
					$tmp->featured_image = CourseModel::get_course_preview_image_placeholder();
				}

				$items[] = $tmp;
			}

			$data['course_prerequisites'] = $items;
		}

		return $data;
	}

	/**
	 * Check whether user can complete course or not
	 *
	 * @since 3.7.1
	 *
	 * @param bool    $can Default value of the filter hook.
	 * @param integer $user_id User id.
	 * @param integer $course_id Course id.
	 *
	 * @return mixed
	 */
	public function can_user_complete_course( $can, int $user_id, int $course_id ) {
		$course_prerequisites_ids = self::get_prerequisite_course_ids( $course_id );
		foreach ( $course_prerequisites_ids as $course_id ) {
			$is_completed = tutor_utils()->is_completed_course( $course_id, $user_id );
			if ( ! $is_completed ) {
				$can = new WP_Error( 400, __( 'You need to complete the prerequisites courses first.', 'tutor-pro' ) );
			}
		}

		return $can;
	}

	/**
	 * Get prerequisite course ids of a course
	 *
	 * @since 3.7.1
	 *
	 * @param integer $course_id Course id.
	 *
	 * @return array array of prerequisite course ids
	 */
	public static function get_prerequisite_course_ids( int $course_id ): array {
		$ids = maybe_unserialize( get_post_meta( $course_id, self::META_KEY, true ) );

		return tutor_utils()->count( $ids ) ? $ids : array();
	}
}
