<?php
/**
 * Handle report related task.
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.8
 */

namespace TUTOR_REPORT;

use TUTOR\Input;
use TUTOR\Tutor_Base;
use TUTOR\Students_List;
use TUTOR_REPORT\Analytics;
use TUTOR\Backend_Page_Trait;
use Tutor\Models\EnrollmentModel;
use TutorPro\Subscription\Controllers\ReportController;

/**
 * Class Report
 *
 * @since 1.0.0
 */
class Report extends Tutor_Base {

	/**
	 * Backend_Page_Trait for inherit common methods
	 * ex: Bulk actions
	 */
	use Backend_Page_Trait;

	/**
	 * Register hooks and dependencies.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_filter( 'tutor_admin_menu', array( $this, 'register_menu' ) );

		/**
		 * Ajax Actions
		 */
		add_action( 'wp_ajax_treport_quiz_atttempt_delete', array( $this, 'treport_quiz_atttempt_delete' ) );
		add_action( 'wp_ajax_tutor_report_get_student_enrollments', array( $this, 'total_enrolled_students' ) );
		add_action( 'wp_ajax_tutor_admin_student_list_bulk_action', array( $this, 'bulk_action' ) );
	}

	/**
	 * Register scripts.
	 *
	 * @param string $page page.
	 *
	 * @return void
	 */
	public function admin_scripts( $page ) {
		if ( 'tutor_report' === Input::get( 'page' ) ) {

			wp_enqueue_script( 'tutor-pro-chart-js', tutor_pro()->url . 'assets/lib/Chart.bundle.min.js', array(), TUTOR_REPORT()->version, true );
			wp_enqueue_script( 'tutor-report', TUTOR_REPORT()->url . 'assets/js/report.js', array( 'tutor-admin', 'tutor-pro-chart-js' ), TUTOR_REPORT()->version, true );

			wp_enqueue_style(
				'tutor-pro-report',
				TUTOR_REPORT()->url . 'assets/css/report.css',
				'',
				TUTOR_PRO_VERSION
			);

			$sub_page   = Input::get( 'sub_page', 'overview' );
			$chart_data = $this->get_chart_data( $sub_page );

			wp_add_inline_script(
				'tutor-report',
				'const _tutor_analytics = ' . wp_json_encode( $chart_data ),
				'before'
			);

		}
	}

	/**
	 * Add sub-menu.
	 *
	 * @since 3.8.0
	 *
	 * @param array $menu menu.
	 *
	 * @return array
	 */
	public function register_menu( $menu ) {
		$menu['group_two']['reports'] = array(
			'parent_slug' => 'tutor',
			'page_title'  => __( 'Reports', 'tutor-pro' ),
			'menu_title'  => __( 'Reports', 'tutor-pro' ),
			'capability'  => 'manage_tutor',
			'menu_slug'   => 'tutor_report',
			'callback'    => array( $this, 'tutor_report' ),
		);

		return $menu;
	}

	/**
	 * Render report page
	 *
	 * @since 1.0.0
	 */
	public function tutor_report() {
		include TUTOR_REPORT()->path . 'views/pages/report.php';
	}

	/**
	 * Handle ajax request for delete quiz attempt
	 *
	 * @since 1.0.0
	 */
	public function treport_quiz_atttempt_delete() {
		tutor_utils()->checking_nonce();

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html( tutor_utils()->error_message() ) );
		}
		global $wpdb;

		$attempt_id = Input::post( 'attempt_id', 0 );

		$wpdb->delete( $wpdb->comments, array( 'comment_ID' => $attempt_id ) );
		$wpdb->delete( $wpdb->commentmeta, array( 'comment_id' => $attempt_id ) );

		wp_send_json_success();
	}

	/**
	 * Get total enrollment graph for backend
	 *
	 * @since 2.0.0
	 */
	public static function total_enrolled_students() {
		$period     = Input::post( 'period', '' );
		$start_date = Input::post( 'start_date', '' );
		$end_date   = Input::post( 'end_date', '' );

		if ( '' !== $start_date ) {
			$start_date = tutor_get_formated_date( 'Y-m-d', $start_date );
		}

		if ( '' !== $end_date ) {
			$end_date = tutor_get_formated_date( 'Y-m-d', $end_date );
		}

		$enrollments = Analytics::get_total_students_by_user( 0, $period, $start_date, $end_date );
		wp_send_json( $enrollments );
		exit;
	}

	/**
	 * Get chart data for admin overview page
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public static function chart_dependent_data(): array {

		$time_period = Input::get( 'period', 'last30days' );
		$start_date  = Input::get( 'start_date', '' );
		$end_date    = Input::get( 'end_date', '' );

		if ( '' !== $start_date ) {
			$start_date = tutor_get_formated_date( 'Y-m-d', $start_date );
		}

		if ( '' !== $end_date ) {
			$end_date = tutor_get_formated_date( 'Y-m-d', $end_date );
		}

		$current_page = Input::get( 'page', '' );
		$sub_page     = Input::get( 'sub_page', 'overview' );

		/**
		 * If the sub_page is overview or it is course details page (admin side)
		 * then get graph data
		 *
		 * @since 2.0.0
		 */
		$course_id = Input::get( 'course_id', 0, Input::TYPE_INT );
		if ( 'overview' === $sub_page || ( 'courses' === $sub_page && '' != $course_id ) ) {
			$overview_graph = array(
				array(
					'id'    => 'ta_total_earnings',
					'label' => __( 'Earning', 'tutor-pro' ),
					'data'  => Analytics::get_earnings_by_user( 0, $time_period, $start_date, $end_date, $course_id )['earnings'],
				),
				array(
					'id'    => 'ta_total_course_enrolled',
					'label' => __( 'Enrolled', 'tutor-pro' ),
					'data'  => Analytics::get_total_students_by_user( 0, $time_period, $start_date, $end_date, $course_id )['enrollments'],
				),
				array(
					'id'    => 'ta_total_refund',
					'label' => __( 'Refund', 'tutor-pro' ),
					'data'  => Analytics::get_refunds_by_user( 0, $time_period, $start_date, $end_date, $course_id )['refunds'],
				),
				array(
					'id'    => 'ta_total_discount',
					'label' => __( 'Discount', 'tutor-pro' ),
					'data'  => Analytics::get_discounts_by_user( 0, $time_period, $start_date, $end_date, $course_id )['discounts'],
				),
			);
			return $overview_graph;
		}
		return array();
	}

	/**
	 * Get sales list
	 *
	 * @since 2.0.0
	 *
	 * @param int    $offset to set offset in query | optional.
	 * @param int    $limit to get limited item | optional.
	 * @param string $course_id to sort item course wise | optional.
	 * @param string $date to sort item date wise | YYYY-MM-DD | optional.
	 * @param string $order to sort item as course title | optional.
	 * @param string $search to sort item as course title | optional.
	 *
	 * @return array
	 */
	public static function sales_list( int $offset = 0, int $limit = 10, $course_id = '', $date = '', $order = '', $search = '' ): array {
		global $wpdb;

		$offset    = sanitize_text_field( $offset );
		$limit     = sanitize_text_field( $limit );
		$course_id = sanitize_text_field( $course_id );
		$date      = sanitize_text_field( $date );
		$order     = sanitize_sql_orderby( $order );
		$search    = sanitize_text_field( $search );

		$search_term = '%' . $wpdb->esc_like( $search ) . '%';

		// Add course id in where clause.
		$course_query = '';
		if ( '' !== $course_id ) {
			$course_query = "AND course.ID = $course_id";
		}

		// Add date in where clause.
		$date_query = '';
		if ( '' !== $date ) {
			$date_query = "AND DATE(post.post_date) = CAST('$date' AS DATE) ";
		}

		// Order query.
		$order_query = '';
		if ( '' !== $order ) {
			$order_query = "ORDER BY post.post_modified {$order}";
		} else {
			$order_query = 'ORDER BY post.post_modified DESC';
		}

		//phpcs:disable
		$sales = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post.ID, post.post_parent, post.post_author, post.post_status, post.post_date, meta.meta_value as order_id 
				FROM {$wpdb->posts} AS post
				INNER JOIN {$wpdb->posts} AS course 
					ON course.ID = post.post_parent
				JOIN {$wpdb->postmeta} meta 
					ON post.ID = meta.post_id
				WHERE meta.meta_key = %s 
					AND post.post_type = %s
					{$course_query}
					{$date_query}
					AND (course.post_title LIKE %s )
				{$order_query}
				LIMIT %d, %d
			",
				EnrollmentModel::ENROLLMENT_ORDER_ID_META,
				'tutor_enrolled',
				$search_term,
				$offset,
				$limit
			)
		);
		//phpcs:enable

		return array(
			'list'  => $sales,
			'total' => self::count_total_sales( $course_id, $date, $search ),
		);
	}

	/**
	 * Count total sales item
	 *
	 * @since 2.0.0
	 *
	 * @param string $course_id to sort item course wise | optional.
	 * @param string $date to sort item date wise | YYYY-MM-DD | optional.
	 * @param string $search to sort item as course title | optional.
	 *
	 * @return int
	 */
	public static function count_total_sales( $course_id = '', $date = '', $search = '' ): int {
		global $wpdb;
		$course_id   = sanitize_text_field( $course_id );
		$date        = sanitize_text_field( $date );
		$search      = sanitize_text_field( $search );
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';

		$course_query = '';
		if ( '' !== $course_id ) {
			$course_query = "AND course.ID = $course_id";
		}
		// Add date in where clause.
		$date_query = '';
		if ( '' !== $date ) {
			$date_query = "AND DATE(post.post_date) = CAST('$date' AS DATE) ";
		}

		//phpcs:disable
		$total_items = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$wpdb->posts} AS post
				INNER JOIN {$wpdb->posts} AS course 
					ON course.ID = post.post_parent
				JOIN {$wpdb->postmeta} meta 
					ON post.ID = meta.post_id
				WHERE meta.meta_key = %s 
					AND post.post_type = %s
					{$course_query}
					{$date_query}
					AND (course.post_title LIKE %s )
			",
				EnrollmentModel::ENROLLMENT_ORDER_ID_META,
				'tutor_enrolled',
				$search_term
			)
		);
		//phpcs:enable

		return $total_items ? $total_items : 0;
	}

	/**
	 * Get available bulk actions for student list
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function student_list_bulk_actions() {
		$actions = array(
			$this->bulk_action_default(),
			$this->bulk_action_delete(),
		);
		return apply_filters( 'tutor_admin_student_list_bulk_action', $actions );
	}

	/**
	 * Handle bulk action
	 *
	 * @since 2.0.0
	 *
	 * @return json response
	 */
	public function bulk_action() {
		tutor_utils()->checking_nonce();
		$bulk_action = Input::post( 'bulk-action', '' );
		$bulk_ids    = Input::post( 'bulk-ids', '' );

		if ( 'delete' === $bulk_action ) {
			return Students_List::delete_students( $bulk_ids );
		}
		exit;
	}

	/**
	 * Retrieves chart data based on the specified subpage.
	 *
	 * @since 3.6.0
	 *
	 * @param string $sub_page The subpage name.
	 *
	 * @return array The filtered chart data based on the subpage.
	 */
	private function get_chart_data( $sub_page ) {

		switch ( $sub_page ) {
			case 'subscriptions':
				return apply_filters( 'tutor_report_graph_data', ( new ReportController() )->chart_dependent_data() );

			case 'courses':
			case 'overview':
				return apply_filters( 'tutor_report_graph_data', self::chart_dependent_data() );

			default:
				return array();
		}
	}
}
