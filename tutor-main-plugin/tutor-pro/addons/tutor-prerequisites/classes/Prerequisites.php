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

use stdClass;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use TUTOR\Tutor_Base;
use TUTOR\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Prerequisites
 *
 * @since 2.0.0
 */
class Prerequisites extends Tutor_Base {

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

		add_action( 'tutor/course/single/content/before/all', array( $this, 'prereq_redirect' ), 101, 2 );
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );
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
		$prerequisites_course_ids = Input::post( '_tutor_course_prerequisites_ids', array(), Input::TYPE_ARRAY );
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
				update_post_meta( $post_ID, '_tutor_course_prerequisites_ids', $prerequisites_course_ids );
			} else {
				delete_post_meta( $post_ID, '_tutor_course_prerequisites_ids' );
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
		$post_id            = get_the_ID();
		$show_prerequisites = apply_filters( 'tutor_pro_show_prerequisites_courses', true, $post_id );
		if ( $show_prerequisites ) {
			$course_prerequisites_ids = maybe_unserialize( get_post_meta( $post_id, '_tutor_course_prerequisites_ids', true ) );
			if ( is_array( $course_prerequisites_ids ) && count( $course_prerequisites_ids ) ) {
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
	public function prereq_redirect( $course_id ) {
		$redirect = apply_filters( 'tutor_pro_prerequisites_redirect', true, $course_id );

		if ( $redirect ) {
			$required_complete       = false;
			$saved_prerequisites_ids = maybe_unserialize( get_post_meta( $course_id, '_tutor_course_prerequisites_ids', true ) );

			if ( is_array( $saved_prerequisites_ids ) && count( $saved_prerequisites_ids ) ) {
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
		$course_prerequisites_ids = maybe_unserialize( get_post_meta( $course_id, '_tutor_course_prerequisites_ids', true ) );
		if ( is_array( $course_prerequisites_ids ) && count( $course_prerequisites_ids ) ) {
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
}
