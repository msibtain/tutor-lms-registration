<?php
/**
 * Handle Multi Instructor Logic
 *
 * @package TutorPro/Addons
 * @subpackage MultiInstructor
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

namespace TUTOR_MT;

use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Helpers\QueryHelper;
use Tutor\Models\CourseModel;
use Tutor\Traits\JsonResponse;

/**
 * Handle multi instructors logics
 */
class MultiInstructors {
	use JsonResponse;

	/**
	 * Register Hooks
	 */
	public function __construct() {
		// Modal Perform.
		add_action( 'wp_ajax_tutor_add_instructors_to_course', array( $this, 'tutor_add_instructors_to_course' ) );
		add_action( 'wp_ajax_detach_instructor_from_course', array( $this, 'detach_instructor_from_course' ) );
		add_action( 'wp_ajax_tutor_course_instructor_search', array( $this, 'tutor_course_instructor_search' ) );

		/**
		 * Change the main instructor of the course
		 *
		 * @since v2.0.7
		 */
		$course_post_type = tutor()->course_post_type;
		add_action( "save_post_{$course_post_type}", array( $this, 'change_main_instructor' ) );

		add_action( "save_post_{$course_post_type}", array( $this, 'add_instructors_to_course' ) );
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );
	}

	/**
	 * Handle ajax request, search instructor
	 *
	 * @return void wp_json response
	 */
	public function tutor_course_instructor_search() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->json_response( tutor_utils()->error_message( 'nonce' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		global $wpdb;

		$course_id = Input::post( 'course_id', 0, Input::TYPE_INT );

		if ( ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
			$this->json_response( tutor_utils()->error_message(), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$args = array(
			'fields'     => array( 'ID', 'display_name', 'user_email', 'user_login' ),
			'meta_key'   => '_tutor_instructor_status',
			'meta_value' => 'approved',
		);

		if ( $course_id ) {
			$instructor_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM $wpdb->usermeta WHERE meta_key=%s AND meta_value=%s",
					'_tutor_instructor_course_id',
					$course_id
				)
			);

			if ( count( $instructor_ids ) ) {
				$args['exclude'] = $instructor_ids;
			}
		}

		$users = get_users( $args );

		foreach ( $users as $user ) {
			$user->avatar_url = get_avatar_url( $user->ID );
		}

		$this->json_response(
			__( 'Data fetched successfully!', 'tutor-pro' ),
			$users
		);
	}

	/**
	 * Get HTML output of instructor metabox
	 *
	 * @since 2.1.0
	 *
	 * @param integer $course_id course id.
	 *
	 * @return string HTML output.
	 */
	public function get_instructor_metabox_output( int $course_id ) {
		global $post;
		//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $course_id );

		ob_start();
		tutor_load_template_from_custom_path(
			dirname( __DIR__ ) . '/views/metabox/instructors-metabox.php',
			array(
				'post' => $post,
			),
			false
		);
		return ob_get_clean();
	}

	/**
	 * Handle ajax request for adding multi instructor to a course
	 *
	 * @return void   wp_json response
	 */
	public function tutor_add_instructors_to_course() {
		tutor_utils()->checking_nonce();

		$course_id      = Input::post( 'course_id', 0, Input::TYPE_INT );
		$instructor_ids = Input::post( 'tutor_instructor_ids', array(), Input::TYPE_ARRAY );

		if ( 0 === $course_id || ! is_array( $instructor_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Request', 'tutor-pro' ) ) );
		}

		if ( ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access Denied', 'tutor-pro' ) ) );
		}

		$instructor_ids = array_filter(
			$instructor_ids,
			function( $id ) {
				return is_numeric( $id );
			}
		);

		foreach ( $instructor_ids as $instructor_id ) {
			add_user_meta( $instructor_id, '_tutor_instructor_course_id', $course_id );
		}

		wp_send_json_success( array( 'output' => $this->get_instructor_metabox_output( $course_id ) ) );
	}

	/**
	 * On course save add instructor to course
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function add_instructors_to_course( int $course_id ) {
		$instructor_ids = Input::post( 'course_instructor_ids', array(), Input::TYPE_ARRAY );
		$instructor_ids = array_filter(
			$instructor_ids,
			function( $id ) {
				return is_numeric( $id );
			}
		);

		if ( 0 === count( $instructor_ids ) ) {
			return;
		}

		// remove all existing instructors from the course.
		global $wpdb;
		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key'   => '_tutor_instructor_course_id',
				'meta_value' => $course_id,
			)
		);

		foreach ( $instructor_ids as $instructor_id ) {
			add_user_meta( $instructor_id, '_tutor_instructor_course_id', $course_id );
		}
	}

	/**
	 * Extend course details response
	 *
	 * @since 3.0.0
	 *
	 * @param array $data response data.
	 *
	 * @return array
	 */
	public function extend_course_details_response( array $data ) {
		$course_id = $data['ID'];

		global $wpdb;

		$instructor_ids = CourseModel::get_course_instructor_ids( $course_id );

		$users = array();
		if ( count( $instructor_ids ) > 0 ) {
			$users = get_users(
				array(
					'include' => $instructor_ids,
					'fields'  => array( 'ID', 'display_name', 'user_email', 'user_login' ),
				)
			);

			foreach ( $users as $user ) {
				$user->avatar_url = get_avatar_url( $user->ID );
			}
		}

		$data['course_instructors'] = $users;

		return $data;
	}

	/**
	 * Remove instructor from a course
	 *
	 * @return void
	 */
	public function detach_instructor_from_course() {
		tutor_utils()->checking_nonce();

		global $wpdb;

		$instructor_id = Input::post( 'instructor_id', 0, Input::TYPE_INT );
		$course_id     = Input::post( 'course_id', 0, Input::TYPE_INT );

		if ( ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access Denied', 'tutor' ) ) );
		}

		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'user_id'    => $instructor_id,
				'meta_key'   => '_tutor_instructor_course_id',
				'meta_value' => $course_id,
			)
		);
		wp_send_json_success();
	}

	/**
	 * Change the main instructor of the course
	 *
	 * @since v2.0.7
	 *
	 * @param int $post_id  current post id (course).
	 *
	 * @return void
	 */
	public static function change_main_instructor( int $post_id ): void {
		global $wpdb;
		$author_id = Input::post( 'post_author_override', 0, Input::TYPE_INT );
		$table     = $wpdb->posts;

		if ( $author_id ) {
			if ( current_user_can( 'manage_options' ) ) {
				$where = array(
					'ID' => $post_id,
				);
				$data  = array(
					'post_author' => $author_id,
				);
				do_action( 'tutor_before_change_main_instructor', $post_id, $author_id );
				$update = QueryHelper::update( $table, $data, $where );
				/**
				 * Update course lesson author id so that
				 * new author can edit lesson from wp editor
				 *
				 * @since v2.1.0
				 */
				self::update_course_content_author( $post_id, $author_id );

				do_action( 'tutor_after_change_main_instructor', $post_id, $author_id, $update );
			}
		}
	}

	/**
	 * Update course content author id typically
	 * after change main instructor
	 *
	 * Note: For now only lesson author id is updating.
	 *
	 * @since v2.1.0
	 *
	 * @param int $course_id  course id.
	 * @param int $author_id  new author id.
	 *
	 * @return bool
	 */
	public static function update_course_content_author( int $course_id, int $author_id ): bool {
		global $wpdb;
		$response   = false;
		$lesson_ids = tutor_utils()->get_course_content_ids_by(
			tutor()->lesson_post_type,
			tutor()->course_post_type,
			$course_id
		);
		if ( is_array( $lesson_ids ) && count( $lesson_ids ) ) {
			$ids      = implode( ',', $lesson_ids );
			$response = QueryHelper::update_where_in(
				$wpdb->posts,
				array( 'post_author' => $author_id ),
				$ids
			);
		}
		return $response;
	}

}
