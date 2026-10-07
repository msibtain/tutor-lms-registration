<?php
/**
 * Enrollment list managements for the backend admin page
 *
 * @package Enrollment List.
 */

namespace TUTOR_ENROLLMENTS;

defined( 'ABSPATH' ) || exit;

use TUTOR\Backend_Page_Trait;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;
use Tutor\Models\EnrollmentModel;
use TUTOR\User;


/**
 * Enrollment list managements for the backend admin page
 *
 * @since 2.0.0
 *
 * @package Enrollment List.
 */
class Enrollments_List {

	/**
	 * Trait for utilities
	 *
	 * @var $page_title
	 */

	use Backend_Page_Trait;

	/**
	 * Bulk Action
	 *
	 * @var $bulk_action
	 */
	public $bulk_action = true;

	/**
	 * Handle dependencies
	 *
	 * @since 4.0.0 param $register_hooks added.
	 *
	 * @param bool $register_hooks register hooks.
	 *
	 * @return void
	 */
	public function __construct( $register_hooks = true ) {
		if ( ! $register_hooks ) {
			return;
		}

		/**
		 * Handle bulk action
		 *
		 * @since v2.0.0
		 */
		add_action( 'wp_ajax_tutor_enrollment_bulk_action', array( $this, 'enrollment_bulk_action' ) );
	}

	/**
	 * Page title fallback
	 *
	 * @since 3.5.0
	 *
	 * @param string $name Property name.
	 *
	 * @return string
	 */
	public function __get( $name ) {
		if ( 'page_title' === $name ) {
			return esc_html__( 'Enrollment', 'tutor-pro' );
		}
	}

	/**
	 * Get enrollment exclude courses query for excluding course enrollments
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_enrollment_exclude_courses_query() {

		$exclude_bundle_course_ids = QueryHelper::get_joined_data(
			'posts as p',
			array(
				array(
					'type'  => 'LEFT',
					'table' => 'postmeta as pm',
					'on'    => 'p.ID = pm.post_id',
				),
			),
			array( 'ID' ),
			array(
				'p.post_type' => 'tutor_enrolled',
				'pm.meta_key' => '_tutor_bundle_id',
			),
			array(),
			'',
			0,
			0,
			'DESC',
			'ARRAY_A',
		);

		$enrollment_ids           = isset( $exclude_bundle_course_ids['results'] ) ? array_column( $exclude_bundle_course_ids['results'], 'ID' ) : array();
		$enrollment_ids           = apply_filters( 'tutor_exclude_course_enrollments', $enrollment_ids );
		$enrollment_ids_clause    = QueryHelper::prepare_in_clause( $enrollment_ids );
		$exclude_course_ids_query = $enrollment_ids_clause ? "AND ( enrol.ID NOT IN ({$enrollment_ids_clause}) )" : '';

		return $exclude_course_ids_query;
	}


	/**
	 * Get total Enrolments
	 *
	 * @since 1.4.0
	 *
	 * @param string $status status.
	 * @param string $search_term search term.
	 * @param string $course_id course id.
	 * @param string $date date.
	 *
	 * @return int
	 */
	public function get_total_enrolments( $status, $search_term = '', $course_id = '', $date = '' ) {
		global $wpdb;
		$status      = sanitize_text_field( $status );
		$course_id   = sanitize_text_field( $course_id );
		$date        = sanitize_text_field( $date );
		$search_term = sanitize_text_field( $search_term );

		$search_term_raw = $search_term;
		$search_term     = '%' . $wpdb->esc_like( $search_term ) . '%';

		// Add course id in where clause.
		$course_query = '';
		if ( $course_id > 0 ) {
			$course_query = $wpdb->prepare( 'AND course.ID = %d', $course_id );
		}

		// Add date in where clause.
		$date_query = '';
		if ( '' !== $date ) {
			$date_query = "AND DATE(enrol.post_date) = CAST('$date' AS DATE) ";
		}

		// Add status in where clause.
		if ( 'approved' === $status ) {
			$status = 'completed';
		} elseif ( 'cancelled' === $status ) {
			$status = array( 'cancel', 'canceled', 'cancelled' );
		} elseif ( 'all' === $status ) {
			$status = '';
		}

		$status_query = '';
		if ( is_array( $status ) && count( $status ) ) {
			$in_clause    = QueryHelper::prepare_in_clause( $status );
			$status_query = "AND enrol.post_status IN ({$in_clause})";
		} elseif ( ! empty( $status ) ) {
			$status_query = "AND enrol.post_status = '$status' ";
		}

		$post_types = array( tutor()->course_post_type );
		if ( tutor_utils()->is_addon_enabled( 'course-bundle' ) ) {
			$post_types[] = tutor()->bundle_post_type;
		}
		$post_type_query = QueryHelper::prepare_in_clause( $post_types );

		//phpcs:disable -- variables are properly escaped.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(enrol.ID)
			FROM 	{$wpdb->posts} enrol
					INNER JOIN {$wpdb->posts} course
							ON enrol.post_parent = course.ID
							AND course.post_type IN ({$post_type_query})
					INNER JOIN {$wpdb->users} student
							ON enrol.post_author = student.ID
			WHERE 	enrol.post_type = %s
					{$status_query}
					{$course_query}
					{$date_query}
					AND ( enrol.ID LIKE %s OR student.display_name LIKE %s OR student.user_email = %s OR course.post_title LIKE %s )
					{$this->get_enrollment_exclude_courses_query()}
			",
				'tutor_enrolled',
				$search_term,
				$search_term,
				$search_term_raw,
				$search_term
			)
		);
		//phpcs:enable

		return (int) $count;
	}

	/**
	 * Get enrollments
	 *
	 * @since 1.0.0
	 *
	 * @param string $status status.
	 * @param int    $start start.
	 * @param int    $limit limit.
	 * @param string $search_term search term.
	 * @param int    $course_id course id.
	 * @param string $date date.
	 * @param string $order order.
	 *
	 * @return array
	 */
	public function get_enrolments( $status, $start = 0, $limit = 10, $search_term = '', $course_id = 0, $date = '', $order = 'DESC' ) {
		global $wpdb;
		$status      = sanitize_text_field( $status );
		$course_id   = sanitize_text_field( $course_id );
		$date        = sanitize_text_field( $date );
		$search_term = sanitize_text_field( $search_term );

		$search_term_raw = $search_term;
		$search_term     = '%' . $wpdb->esc_like( $search_term ) . '%';

		// add course id in where clause.
		$course_query = '';
		if ( $course_id > 0 ) {
			$course_query = $wpdb->prepare( 'AND course.ID = %d', $course_id );
		}

		// add date in where clause.
		$date_query = '';
		if ( '' !== $date ) {
			$date_query = "AND DATE(enrol.post_date) = CAST('$date' AS DATE) ";
		}

		// add status in where clause.
		if ( 'approved' === $status ) {
			$status = 'completed';
		} elseif ( 'cancelled' === $status ) {
			$status = array( 'cancel', 'canceled', 'cancelled' );
		} elseif ( 'all' === $status ) {
			$status = '';
		}

		$status_query = '';
		if ( is_array( $status ) && count( $status ) ) {
			$in_clause    = QueryHelper::prepare_in_clause( $status );
			$status_query = "AND enrol.post_status IN ({$in_clause})";
		} elseif ( ! empty( $status ) ) {
			$status_query = "AND enrol.post_status = '$status' ";
		}

		$post_types = array( tutor()->course_post_type );

		if ( tutor_utils()->is_addon_enabled( 'course-bundle' ) ) {
			$post_types[] = tutor()->bundle_post_type;
		}

		$post_type_query = QueryHelper::prepare_in_clause( $post_types );

		//phpcs:disable -- variables are properly escaped.
		$enrolments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT enrol.ID AS enrol_id,
					enrol.post_author AS student_id,
					enrol.post_date AS enrol_date,
					enrol.post_title AS enrol_title,
					enrol.post_status AS status,
					enrol.post_parent AS course_id,
					course.post_title AS course_title,
					course.guid,
					student.user_nicename,
					student.user_email,
					student.display_name
			FROM 	{$wpdb->posts} enrol
					INNER JOIN {$wpdb->posts} course
							ON enrol.post_parent = course.ID
						   AND course.post_type IN ({$post_type_query})
					INNER JOIN {$wpdb->users} student
							ON enrol.post_author = student.ID
			WHERE 	enrol.post_type = %s
					{$status_query}
					{$course_query}
					{$date_query}
					AND ( enrol.ID LIKE %s OR student.display_name LIKE %s OR student.user_email = %s OR course.post_title LIKE %s )
					{$this->get_enrollment_exclude_courses_query()}
			ORDER BY enrol_id {$order}
			LIMIT 	%d, %d;
			",
				'tutor_enrolled',
				$search_term,
				$search_term,
				$search_term_raw,
				$search_term,
				$start,
				$limit
			)
		);
		//phpcs:enable

		return $enrolments;
	}

	/**
	 * Available tabs that will visible on the right side of page navbar
	 *
	 * @param string $course_id selected course id | optional.
	 * @param string $date selected date | optional.
	 * @param string $search search by user name or email | optional.
	 * @return array
	 * @since v2.0.0
	 */
	public function tabs_key_value( $course_id, $date, $search ): array {
		$url       = get_pagenum_link();
		$url       = apply_filters( 'tutor_data_tab_base_url', get_pagenum_link() );
		$all       = self::get_enrolled_number( '', $course_id, $date, $search );
		$approved  = self::get_enrolled_number( 'completed', $course_id, $date, $search );
		$pending   = self::get_enrolled_number( 'pending', $course_id, $date, $search );
		$cancelled = self::get_enrolled_number( 'cancelled', $course_id, $date, $search );
		$tabs      = array(
			array(
				'key'   => 'all',
				'title' => __( 'All', 'tutor-pro' ),
				'value' => $all,
				'url'   => $url . '&data=all',
			),
			array(
				'key'   => 'approved',
				'title' => __( 'Approved', 'tutor-pro' ),
				'value' => $approved,
				'url'   => $url . '&data=approved',
			),
			array(
				'key'   => 'pending',
				'title' => __( 'Pending', 'tutor-pro' ),
				'value' => $pending,
				'url'   => $url . '&data=pending',
			),
			array(
				'key'   => 'cancelled',
				'title' => __( 'Cancelled', 'tutor-pro' ),
				'value' => $cancelled,
				'url'   => $url . '&data=cancelled',
			),
		);
		return $tabs;
	}

	/**
	 * Prepare bulk actions that will show on dropdown options
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function prpare_bulk_actions(): array {
		$actions = array(
			$this->bulk_action_default(),
			array(
				'value'  => 'complete',
				'option' => __( 'Approve', 'tutor-pro' ),
			),
			$this->bulk_action_cancel(),
		);
		return $actions;
	}

	/**
	 * Count enrolled number by status & filters
	 * Count all enrollment | approved | cancelled
	 *
	 * @since 2.0.0
	 *
	 * @param string $status | required.
	 * @param string $course_id selected course id | optional.
	 * @param string $date selected date | optional.
	 * @param string $search_term search by user name or email | optional.
	 *
	 * @return int
	 */
	protected static function get_enrolled_number( string $status, $course_id = '', $date = '', $search_term = '' ): int {
		global $wpdb;
		$status      = sanitize_text_field( $status );
		$course_id   = sanitize_text_field( $course_id );
		$date        = sanitize_text_field( $date );
		$search_term = sanitize_text_field( $search_term );

		$search_term = '%' . $wpdb->esc_like( $search_term ) . '%';

		// add course id in where clause.
		$course_query = '';
		if ( $course_id > 0 ) {
			$course_query = $wpdb->prepare( 'AND course.ID = %d', $course_id );
		}

		// add date in where clause.
		$date_query = '';
		if ( '' !== $date ) {
			$date_query = "AND DATE(enrol.post_date) = CAST('$date' AS DATE) ";
		}

		// Add status in where clause.
		if ( 'cancelled' === $status ) {
			$status = array( 'cancel', 'canceled', 'cancelled' );
		}

		$status_query = '';
		if ( is_array( $status ) && count( $status ) ) {
			$in_clause    = QueryHelper::prepare_in_clause( $status );
			$status_query = "AND enrol.post_status IN ({$in_clause})";
		} elseif ( ! empty( $status ) ) {
			$status_query = "AND enrol.post_status = '$status' ";
		}

		$post_types = array( tutor()->course_post_type );
		if ( tutor_utils()->is_addon_enabled( 'course-bundle' ) ) {
			$post_types[] = tutor()->bundle_post_type;
		}

		$post_type_query = QueryHelper::prepare_in_clause( $post_types );

		//phpcs:disable -- variables are sanitized
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
					FROM 	{$wpdb->posts} enrol
							INNER JOIN {$wpdb->posts} course
									ON enrol.post_parent = course.ID
									AND course.post_type IN ({$post_type_query})
							INNER JOIN {$wpdb->users} student
									ON enrol.post_author = student.ID
					WHERE 	enrol.post_type = %s
							{$status_query}
							{$date_query}
							{$course_query}
							AND ( enrol.ID LIKE %s OR student.display_name LIKE %s OR student.user_email LIKE %s OR course.post_title LIKE %s )
					",
				'tutor_enrolled',
				$search_term,
				$search_term,
				$search_term,
				$search_term
			)
		);
		//phpcs:enable

		return $count ? $count : 0;
	}

	/**
	 * Handle bulk action for enrolment list
	 *
	 * @since 2.0.0
	 *
	 * @return void JSON response.
	 */
	public function enrollment_bulk_action() {
		tutor_utils()->checking_nonce();

		$status_list = array( 'complete', 'cancel', 'delete' );
		$status      = Input::post( 'bulk-action', '' );

		if ( ! User::is_admin() || ! in_array( $status, $status_list, true ) ) {
			wp_send_json_error();
		}

		$bulk_ids = Input::post( 'bulk-ids', '' );
		$bulk_ids = explode( ',', $bulk_ids );
		$bulk_ids = array_filter(
			$bulk_ids,
			function ( $id ) {
				return is_numeric( $id );
			}
		);

		if ( 'delete' === $status ) {
			self::delete_cancelled_enrollment( $bulk_ids );
		} else {
			EnrollmentModel::update_enrollments( $status, $bulk_ids );
		}

		wp_send_json_success();
	}

	/**
	 * Delete only cancelled enrollment
	 *
	 * @since 2.2.4
	 *
	 * @param array $bulk_ids id list.
	 *
	 * @return void
	 */
	public static function delete_cancelled_enrollment( $bulk_ids ) {
		if ( ! User::is_admin() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized action', 'tutor-pro' ) ) );
		}

		$page_url = admin_url( 'admin.php?page=enrollments' );
		do_action( 'tutor_before_bulk_enrollment_delete', $bulk_ids, $page_url );

		// Delete course progress for selected ids.
		foreach ( $bulk_ids as $id ) {
			$course_id  = get_post_field( 'post_parent', $id );
			$student_id = get_post_field( 'post_author', $id );

			if ( $course_id && $student_id ) {
				tutor_utils()->delete_course_progress( $course_id, $student_id );
			}
			do_action( 'tutor_delete_course_enrollments', $id, $course_id, $student_id );
		}

		$ids_str       = QueryHelper::prepare_in_clause( $bulk_ids );
		$cancel_status = QueryHelper::prepare_in_clause( array( 'cancel', 'canceled', 'cancelled' ) );

		// Now delete selected cancelled enrollments.
		global $wpdb;
		//phpcs:disable -- ids_str is sanitized
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->posts}
				WHERE ID IN ($ids_str)
				AND post_type = %s
				AND post_status IN ($cancel_status)
			",
				'tutor_enrolled',
			)
		);
		//phpcs:enable

		tutor_utils()->redirect_to( $page_url, __( 'Enrollment successfully deleted', 'tutor-pro' ) );
		exit;
	}

	/**
	 * Execute bulk action for enrollment list ex: complete | cancel
	 *
	 * @since 2.0.0
	 *
	 * @param string $status hold status for updating.
	 * @param array  $enrollment_ids ids that need to update.
	 *
	 * @return bool
	 */
	public static function update_enrollments( string $status, array $enrollment_ids ): bool {
		global $wpdb;
		$enrollment_ids_in = QueryHelper::prepare_in_clause( $enrollment_ids );
		$status            = 'complete' === $status ? 'completed' : $status;

		//phpcs:disable -- $enrollment_ids_in is sanitized
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts}
				SET post_status = %s
				WHERE ID IN ($enrollment_ids_in)
			",
				$status
			)
		);
		//phpcs:enable

		// Run action hook.
		foreach ( $enrollment_ids as $id ) {
			do_action( 'tutor_enrollment/after/' . $status, $id );
		}

		return true;
	}
}
