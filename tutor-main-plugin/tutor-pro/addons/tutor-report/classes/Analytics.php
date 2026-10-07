<?php
/**
 * Analytics for instructor
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.8
 */

namespace TUTOR_REPORT;

defined( 'ABSPATH' ) || exit;

use TUTOR_REPORT\ExportAnalytics;
use Tutor\Ecommerce\OrderController;
use Tutor\Helpers\QueryHelper;
use TUTOR\DashboardSectionManager;
use TUTOR\Icon;
use TUTOR\Input;
use TUTOR\Instructor;
use TUTOR\InstructorMetricsAdapter;
use Tutor\Components\StarRating;
use Tutor\Components\Table;
use Tutor\Models\OrderModel;
use TUTOR\User;
use DateTime;

/**
 * Class Analytics
 */
class Analytics extends ExportAnalytics {

	/**
	 * Transient key to store user earnings
	 *
	 * User id & other query param will be appended with the key
	 *
	 * @since 4.0.8
	 *
	 * @var string
	 */
	const DASHBOARD_USER_EARNINGS = 'tutor_dashboard_user_earnings_';

	/**
	 * Resolve dependencies
	 *
	 * @since 1.9.8
	 * @var mixed
	 */
	public $current_page;

	/**
	 * Register hooks
	 */
	public function __construct() {
		add_filter( 'tutor_dashboard/instructor_nav_items', array( $this, 'tutor_analytics_register_menu' ), 20 );
		add_filter( 'load_dashboard_template_part_from_other_location', array( $this, 'tutor_analytics_frontend' ) );
		add_filter( 'tutor_dashboard_sections', array( $this, 'register_report_dashboard_sections' ) );
		add_filter( 'tutor_dashboard_section_data', array( $this, 'get_report_dashboard_section_data' ), 10, 3 );

		/**
		 * Enqueue styles for analytics
		 *
		 * @since 1.9.8
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'tutor_analytics_scripts' ) );
		add_action( 'wp_ajax_view_progress', array( $this, 'view_progress' ) );
		add_action( 'wp_ajax_export_analytics', array( $this, 'export_analytics' ) );
	}

	/**
	 * Analytics frontend nav items
	 *
	 * @since 1.9.8
	 *
	 * @param mixed $nav_items nav items.
	 */
	public function tutor_analytics_register_menu( $nav_items ) {
		do_action( 'before_register_frontend_report_nav_item' );

		$nav_items['analytics'] = array(
			'title'       => __( 'Analytics', 'tutor-pro' ),
			'icon'        => Icon::ANALYTICS,
			'active_icon' => Icon::ANALYTICS_FILL,
			'auth_cap'    => tutor()->instructor_role,
		);
		return apply_filters( 'after_register_frontend_report_nav_item', $nav_items );
	}

	/**
	 * Load frontend report template
	 *
	 * @since 1.9.8
	 *
	 * @param string $template template.
	 */
	public function tutor_analytics_frontend( $template ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;
		if ( isset( $query_vars['tutor_dashboard_page'] ) && 'analytics' === $query_vars['tutor_dashboard_page'] ) {
			// Set current page.
			if ( isset( $query_vars['tutor_dashboard_sub_page'] ) ) {
				$this->current_page = $query_vars['tutor_dashboard_sub_page'];
			} else {
				$this->current_page = 'overview';
			}
			$new_template = TUTOR_REPORT()->path . 'templates/frontend_analytics.php';
			if ( file_exists( $new_template ) ) {
				return apply_filters( 'tutor_frontend_report_template', $new_template );
			}
		}
		return $template;
	}

	/**
	 * Analytics sub pages
	 *
	 * @return array
	 *
	 * @since 1.9.8
	 */
	public function sub_pages(): array {
		$sub_pages = array(
			'overview'   => array(
				'title' => __( 'Overview', 'tutor-pro' ),
				'url'   => esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics' ),
			),
			'courses'    => array(
				'title' => __( 'Courses', 'tutor-pro' ),
				'url'   => esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics/courses' ),
			),
			'earnings'   => array(
				'title' => __( 'Earnings', 'tutor-pro' ),
				'url'   => esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics/earnings' ),
			),
			'statements' => array(
				'title' => __( 'Statements', 'tutor-pro' ),
				'url'   => esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics/statements' ),
			),
			'students'   => array(
				'title' => __( 'Students', 'tutor-pro' ),
				'url'   => esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics/students' ),
			),
			'export'     => array(
				'title' => __( 'Export', 'tutor-pro' ),
				'url'   => esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics/export' ),
			),
		);
		return apply_filters( 'tutor_analytics_sub_pages', $sub_pages );
	}

	/**
	 * Load sub page
	 *
	 * @param string $page string.
	 *
	 * @since 1.9.8
	 */
	public function load_sub_page( string $page ) {
		$file = TUTOR_REPORT()->path . 'templates/' . $page . '.php';
		if ( file_exists( $file ) ) {
			ob_start();
			include_once $file;
			return apply_filters( 'tutor-analytics-sub-page', ob_get_clean() );
		} else {
			esc_html_e( 'Content Not Found!', 'tutor-pro' );
		}
	}

	/**
	 * Get earnings of current user
	 *
	 * @since 1.9.8
	 *
	 * @param string $period ( today | monthly| yearly ) required | string.
	 * @param int    $user_id user id.
	 *
	 * @return mixed
	 */
	public function get_total_earnings_by_period( string $period, int $user_id ) {
		global $wpdb;
		$period  = sanitize_text_field( $period );
		$user_id = sanitize_text_field( $user_id );

		$complete_status = tutor_utils()->get_earnings_completed_statuses();
		$complete_status = QueryHelper::prepare_in_clause( $complete_status );

		$period_filter = '';
		if ( 'today' === $period ) {
			$period_filter = 'AND DATE(created_at) = CURDATE()';
		}
		if ( 'monthly' === $period ) {
			$period_filter = 'AND MONTH(created_at) = MONTH(CURDATE()) ';
		}
		if ( 'yearly' === $period ) {
			$period_filter = 'AND YEAR(created_at) = YEAR(CURDATE()) ';
		}

		$earnings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT instructor_amount
				FROM {$wpdb->prefix}tutor_earnings
				WHERE user_id = %d
					AND order_status IN({$complete_status})
					{$period_filter}
				ORDER BY created_at ASC;
			",
				$user_id
			)
		);

		return $earnings;
	}

	/**
	 * Get total enrolled number from all courses
	 *
	 * this method depends on get_courses_by_period method to
	 *
	 * get all courses of a user
	 *
	 * @return int Total count enrolled users.
	 *
	 * @since 1.9.8
	 */
	public static function get_courses_with_search_by_user( int $user_id, string $search = '', $post_status = array( 'publish' ) ): int {
		global $wpdb;
		$user_id = sanitize_text_field( $user_id );
		$search  = sanitize_text_field( $search );

		$course_post_type = tutor()->course_post_type;
		$search_term      = '%' . $wpdb->esc_like( $search ) . '%';
		$post_status      = QueryHelper::prepare_in_clause( $post_status );

		$total_item = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count(ID)
				FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_status IN ({$post_status})
				AND post_author = %d
				AND (post_title LIKE %s)
			",
				$course_post_type,
				$user_id,
				$search_term
			)
		);

		return $total_item;
	}

	/**
	 * Get all courses of user, period wise ( today | monthly | yearly )
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int      $user_id     Instructor user ID.
	 * @param string   $order       Sort order. Accepts 'ASC' or 'DESC'. Default empty.
	 * @param string   $order_by    Column name to order by. Default empty.
	 * @param int      $offset      Number of records to skip (used for pagination).
	 * @param int      $limit       Maximum number of records to return.
	 * @param string   $search      Optional search term to filter courses by title.
	 * @param string[] $post_status List of allowed course post statuses. Defaults to ['publish'].
	 *
	 * @return array of courses
	 *
	 * @since 1.9.8
	 */
	public static function get_courses_with_total_enroll_earning( int $user_id, string $order = '', string $order_by = '', int $offset = 0, int $limit = 10, string $search = '', $post_status = array( 'publish' ) ) {
		global $wpdb;
		$post_type = tutor()->course_post_type;
		$user_id   = sanitize_text_field( $user_id );
		$order     = sanitize_text_field( $order );
		$order_by  = sanitize_text_field( $order_by );
		$offset    = sanitize_text_field( $offset );
		$limit     = sanitize_text_field( $limit );
		$search    = sanitize_text_field( $search );
		// if there is search then make offset 0 to get result.
		if ( '' !== $search ) {
			$offset = 0;
		}
		$search_term     = '%' . $wpdb->esc_like( $search ) . '%';
		$complete_status = tutor_utils()->get_earnings_completed_statuses();
		$complete_status = "'" . implode( "','", $complete_status ) . "'";
		$post_status     = QueryHelper::prepare_in_clause( $post_status );

		$courses = $wpdb->get_results(
			$wpdb->prepare(
				" SELECT post.ID , post.post_title, post.guid, count(enrollment.ID) AS learner
					FROM {$wpdb->posts} AS post
						LEFT JOIN {$wpdb->posts} AS enrollment ON enrollment.post_parent = post.ID
							AND enrollment.post_type = %s
							AND enrollment.post_status = %s
					WHERE post.post_type = %s
						AND post.post_author = %d
						AND post.post_status IN ({$post_status})
						AND ( post.post_title LIKE %s)
					GROUP BY post.ID
					ORDER BY {$order_by} {$order}
					LIMIT %d, %d
				",
				'tutor_enrolled',
				'completed',
				$post_type,
				$user_id,
				$search_term,
				$offset,
				$limit
			)
		);
		return $courses;
	}

	/**
	 * Get total earning from a course for admin
	 *
	 * @param int $course_id | required.
	 * @return mixed Total sales.
	 */
	public static function get_earnings_by_course( int $course_id ) {
		global $wpdb;

		$total_sales = 0;

		$product_id = get_post_meta( $course_id, '_tutor_course_product_id', true );
		if ( $product_id ) {
			$total_sales = $wpdb->get_var(
				"SELECT SUM( order_item_meta__line_total.meta_value) as order_item_amount
            FROM {$wpdb->posts} AS posts
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__line_total ON (order_items.order_item_id = order_item_meta__line_total.order_item_id)
                AND (order_item_meta__line_total.meta_key = '_line_total')
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id_array ON order_items.order_item_id = order_item_meta__product_id_array.order_item_id
            WHERE posts.post_type IN ( 'shop_order' )
            AND posts.post_status IN ( 'wc-completed' ) AND ( ( order_item_meta__product_id_array.meta_key IN ('_product_id','_variation_id')
            AND order_item_meta__product_id_array.meta_value IN ('{$product_id}') ) );"
			);
		}
		if ( function_exists( 'wc_price' ) ) {
			$total_sales = wc_price( $total_sales );
		}
		return $total_sales;
	}

	/**
	 * Get earning by user_id, optionally can set period ( today | monthly| yearly )
	 *
	 * Optionally can set start date & end date to get earnings from date range
	 *
	 * If period or date range not pass then it will return all time earnings
	 *
	 * Optionally can set course_id for getting specific course data
	 *
	 * @since 1.9.9
	 *
	 * @param int    $user_id    user id.
	 * @param string $period     sorting time period.
	 * @param string $start_date start date.
	 * @param string $end_date   end date.
	 * @param int    $course_id  course id.
	 *
	 * @return array
	 */
	public static function get_earnings_by_user( int $user_id, string $period = '', string $start_date = '', string $end_date = '', int $course_id = 0 ): array {
		global $wpdb;

		$user_id = (int) $user_id;

		if ( ! User::is_admin( $user_id ) && ! User::is_instructor( $user_id, true ) ) {
			return array();
		}

		$unique_query_token = md5( $user_id . $period . $start_date . $end_date . $course_id );
		$cache_key          = self::DASHBOARD_USER_EARNINGS . $unique_query_token;

		$cached_data = get_transient( $cache_key );
		if ( $cached_data && is_array( $cached_data ) ) {
			return $cached_data;
		}

		$period     = sanitize_text_field( $period );
		$start_date = sanitize_text_field( $start_date );
		$end_date   = sanitize_text_field( $end_date );

		$period_query = '';
		$group_query  = ' GROUP BY DATE(earnings.created_at) ';
		$course_query = '';
		$select_query = " DATE_FORMAT(earnings.created_at, '%b') AS label_name, DATE(earnings.created_at) AS date_format ";

		// set additional query for period or date range if condition not meet then get all time data.
		if ( ! empty( $period ) ) {
			$period_query = QueryHelper::get_period_clause( 'earnings.created_at', $period );
		}

		if ( in_array( $period, array( 'last90days', 'last365days', 'yearly' ), true ) ) {
			$group_query  = ' GROUP BY MONTH(earnings.created_at) ';
			$select_query = " DATE_FORMAT(earnings.created_at, '%b') AS label_name ";

		}

		if ( $course_id ) {
			$course_query = " AND course_id = $course_id ";
		}

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$period_query = $wpdb->prepare(
				" AND DATE(earnings.created_at) 
				BETWEEN CAST(%s AS DATE) AND CAST(%s AS DATE) ",
				$start_date,
				$end_date
			);

			$diff_days = ( new DateTime( $start_date ) )->diff( new DateTime( $end_date ) )->days;

			if ( $diff_days > 31 ) {
				$group_query  = ' GROUP BY MONTH(earnings.created_at) ';
				$select_query = " DATE_FORMAT(earnings.created_at, '%b') AS label_name ";

			}
		}

		if ( empty( $start_date ) && empty( $end_date ) && empty( $period ) ) {
			$select_query = " DATE_FORMAT(earnings.created_at, '%Y') AS label_name";
			$group_query  = ' GROUP BY YEAR(earnings.created_at) ';
		}

		/**
		 * Author query added to use same query for admin as well
		 * pass user_id value 0 to get all data for admin
		 *
		 * @since v2.0.0
		 */
		$author_query = '';
		if ( $user_id ) {
			$author_query = "AND earnings.user_id = {$user_id}";
		}
		// Get statuses.
		$complete_status = tutor_utils()->get_earnings_completed_statuses();
		$complete_status = "'" . implode( "','", $complete_status ) . "'";

		$is_backend_admin = User::is_admin() && is_admin() && ! wp_doing_ajax();
		$amount_type      = $is_backend_admin ? 'earnings.admin_amount' : 'earnings.instructor_amount';
		$amount_rate      = $is_backend_admin ? 'earnings.admin_rate' : 'earnings.instructor_rate';

		$amount_condition = "CASE
			WHEN orders.tax_type = 'inclusive' AND earnings.course_price_grand_total > 0
				THEN ( earnings.course_price_grand_total - orders.tax_amount ) * ( $amount_rate/100 )
			ELSE $amount_type
			END";

		$earnings = $wpdb->get_results(
			"SELECT  
				SUM($amount_condition) AS total,
				{$select_query}
			FROM {$wpdb->prefix}tutor_earnings as earnings 
			LEFT JOIN {$wpdb->prefix}tutor_orders as orders
				ON earnings.order_id = orders.id
			WHERE earnings.order_status IN({$complete_status})
				{$author_query}
				{$course_query}
				{$period_query}
				{$group_query}
			ORDER BY earnings.created_at ASC;"
		);

		$total_earnings = 0;
		foreach ( $earnings as $earning ) {
			$total_earnings += $earning->total;
		}

		$res = array(
			'earnings'       => $earnings,
			'total_earnings' => $total_earnings,
		);

		set_transient( $cache_key, $res, MINUTE_IN_SECONDS * 10 );

		return $res;
	}

	/**
	 * Get total enrollment / students by user_id, optionally can set period ( today | monthly| yearly )
	 *
	 * Optionally can set start date & end date to get enrollment list from date range
	 *
	 * If period or date range not pass then it will return all time enrollment list
	 *
	 * @param $user_id int | required
	 *
	 * @param $period string | optional
	 *
	 * @param $start_date string | optional | yy-mm-dd
	 *
	 * @param $end_date string | optional | yy-mm-dd
	 *
	 * @return array
	 *
	 * @since 1.9.9
	 */
	public static function get_total_students_by_user( int $user_id, string $period = '', $start_date = '', string $end_date = '', int $course_id = 0 ): array {
		global $wpdb;

		$course_post_type = tutor()->course_post_type;
		$user_id          = sanitize_text_field( $user_id );
		$period           = sanitize_text_field( $period );
		$start_date       = sanitize_text_field( $start_date );
		$end_date         = sanitize_text_field( $end_date );

		$period_query = '';
		$group_query  = ' GROUP BY DATE(enrollment.post_date) ';
		$select_query = " DATE_FORMAT(enrollment.post_date, '%%b') AS label_name, enrollment.post_date AS date_format ";

		// set additional query for period or date range.
		if ( ! empty( $period ) ) {
			$period_query = QueryHelper::get_period_clause( 'enrollment.post_date', $period );
		}
		// Period query.
		if ( in_array( $period, array( 'last90days', 'last365days', 'yearly' ), true ) ) {
			$group_query  = ' GROUP BY MONTH(enrollment.post_date) ';
			$select_query = " DATE_FORMAT(enrollment.post_date, '%%b') AS label_name ";
		}

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$period_query = " AND DATE(enrollment.post_date) BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) ";

			$diff_days = ( new DateTime( $start_date ) )->diff( new DateTime( $end_date ) )->days;

			if ( $diff_days > 31 ) {
				$group_query  = ' GROUP BY MONTH(enrollment.post_date) ';
				$select_query = " DATE_FORMAT(enrollment.post_date, '%%b') AS label_name ";
			}
		}

		if ( empty( $start_date ) && empty( $end_date ) && empty( $period ) ) {
			$select_query = " DATE_FORMAT(enrollment.post_date, '%%Y') AS label_name";
			$group_query  = ' GROUP BY YEAR(enrollment.post_date) ';
		}

		/**
		 * Author query added to use same query for admin as well
		 * pass user_id value 0 to get all data for admin
		 *
		 * @since v2.0.0
		 */
		$author_query = '';
		if ( $user_id ) {
			$author_query = "AND course.post_author = {$user_id}";
		}

		$course_query = '';
		if ( $course_id ) {
			$course_query = " AND course.ID = {$course_id} ";
		}

		$enrollments       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					COUNT(enrollment.ID) AS total, 
					{$select_query}
				FROM 
					{$wpdb->posts} enrollment
				INNER JOIN 
					{$wpdb->posts} course
				ON 
					enrollment.post_parent=course.ID
				WHERE 
					course.post_type = %s
					AND course.post_status = %s
					AND enrollment.post_type = %s
					AND enrollment.post_status = %s
					{$course_query}
					{$author_query}
					{$period_query}
					{$group_query}
				ORDER BY enrollment.post_date ASC;",
				$course_post_type,
				'publish',
				'tutor_enrolled',
				'completed'
			)
		);
		$total_enrollments = 0;
		foreach ( $enrollments as $enroll ) {
			$total_enrollments += $enroll->total;
		}
		return array(
			'total_enrollments' => $total_enrollments,
			'enrollments'       => $enrollments,
		);
	}

	/**
	 * Get total refunds by user_id (instructor), optionally can set period ( today | monthly| yearly )
	 *
	 * Optionally can set start date & end date to get enrollment list from date range
	 *
	 * If period or date range not pass then it will return all time enrollment list
	 *
	 * @since 1.9.9
	 *
	 * @since 3.0.0
	 * If monetization is tutor get data from OrderModel
	 *
	 * @since 4.0.0 Condition added for checking wooCommerce tables before fetching data.
	 *
	 * @param int    $user_id User id, if user not have admin access
	 * then only this user's refund amount will fetched.
	 * @param string $period Time period.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @param int    $course_id Course id.
	 *
	 * @return array
	 */
	public static function get_refunds_by_user( int $user_id, string $period = '', $start_date = '', string $end_date = '', int $course_id = 0 ): array {
		global $wpdb;

		if ( tutor_utils()->is_monetize_by_tutor() ) {
			return ( new OrderController( false ) )->get_refund_data( $user_id, $period, $start_date, $end_date, $course_id );
		}

		if ( ! QueryHelper::table_exists( 'wc_order_product_lookup' ) || ! QueryHelper::table_exists( 'wc_order_stats' ) ) {
			return array(
				'refunds'       => array(),
				'total_refunds' => 0,
			);
		}

		$course_post_type = tutor()->course_post_type;
		$user_id          = sanitize_text_field( $user_id );
		$period           = sanitize_text_field( $period );
		$start_date       = sanitize_text_field( $start_date );
		$end_date         = sanitize_text_field( $end_date );

		$course_query = '';
		$period_query = '';
		$group_query  = ' GROUP BY DATE(order_details.date_created) ';
		$select_query = " DATE_FORMAT( order_details.date_created, '%%b' ) AS label_name, DATE(order_details.date_created) AS date_format";
		// set additional query for period or date range.
		if ( '' !== $period ) {
			$period_query = QueryHelper::get_period_clause( 'order_details.date_created', $period );
		}

		// period query.
		if ( in_array( $period, array( 'last90days', 'last365days', 'yearly' ), true ) ) {
			$group_query  = ' GROUP BY MONTH(order_details.date_created) ';
			$select_query = " DATE_FORMAT(order_details.date_created, '%%b') AS label_name ";
		}

		if ( '' !== $start_date && '' !== $end_date ) {
			$period_query = " AND  DATE(order_details.date_created) BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) ";

			$diff_days = ( new DateTime( $start_date ) )->diff( new DateTime( $end_date ) )->days;

			if ( $diff_days > 31 ) {
				$group_query  = ' GROUP BY MONTH(order_details.date_created) ';
				$select_query = " DATE_FORMAT(order_details.date_created, '%%b') AS label_name ";

			}
		}

		if ( $course_id ) {
			$course_query = " AND post.ID = $course_id ";
		}

		if ( empty( $start_date ) && empty( $end_date ) && empty( $period ) ) {
			$select_query = " DATE_FORMAT(order_details.date_created, '%%Y') AS label_name";
			$group_query  = ' GROUP BY YEAR(order_details.date_created) ';
		}

		/**
		 * Author query added to use same query for admin as well
		 * pass user_id value 0 to get all data for admin
		 *
		 * @since v2.0.0
		 */
		$author_query = '';
		if ( $user_id ) {
			$author_query = "AND post.post_author = {$user_id}";
		}

		/**
		 * If admin side then deduct instructor commission
		 * If front side then deduct admin commission
		 *
		 * @since v2.0.9
		 */
		$commission = (int) tutor_utils()->get_option( User::is_admin() ? 'earning_instructor_commission' : 'earning_admin_commission' );

		$refunds       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					(SUM(order_details.total_sales) - SUM(order_details.total_sales) * $commission / 100 ) AS total,order_details.date_created AS date_format,
						{$select_query}
				FROM {$wpdb->posts} AS post
					INNER JOIN {$wpdb->postmeta} as mt1 ON mt1.post_id = post.ID
					INNER JOIN {$wpdb->prefix}wc_order_product_lookup AS w_order ON w_order.product_id = mt1.meta_value
					INNER JOIN {$wpdb->prefix}wc_order_stats AS order_details ON order_details.order_id = w_order.order_id
				WHERE mt1.meta_key = %s
					AND post.post_type = %s
					AND post.post_status = %s
					AND order_details.status = %s
					{$author_query}
					{$course_query}
					{$period_query}
					{$group_query}",
				'_tutor_course_product_id',
				$course_post_type,
				'publish',
				'wc-refunded'
			)
		);
		$total_refunds = 0;

		foreach ( $refunds as $refund ) {
			$total_refunds += $refund->total;
		}

		$response = array(
			'refunds'       => $refunds,
			'total_refunds' => $total_refunds,
		);

		return apply_filters( 'tutor_refund_data', $response, $user_id, $period, $start_date, $end_date, $course_id );
	}

	/**
	 * Get total discounts by user_id (instructor), optionally can set period ( today | monthly| yearly )
	 *
	 * Optionally can set start date & end date to get enrollment list from date range
	 *
	 * If period or date range not pass then it will return all time enrollment list
	 *
	 * @param $user_id int | required
	 *
	 * @param $period string | optional
	 *
	 * @param $start_date string | optional | yy-mm-dd
	 *
	 * @param $end_date string | optional | yy-mm-dd
	 *
	 * @return array
	 *
	 * @since 1.9.9
	 *
	 * @since 4.0.0 Condition added for checking wooCommerce tables before fetching data.
	 */
	public static function get_discounts_by_user( int $user_id, string $period = '', string $start_date = '', string $end_date = '', int $course_id = 0 ): array {
		global $wpdb;

		if ( tutor_utils()->is_monetize_by_tutor() ) {
			return ( new OrderController( false ) )->get_discount_data( $user_id, $period, $start_date, $end_date, $course_id );
		}

		if ( ! QueryHelper::table_exists( 'wc_order_product_lookup' ) || ! QueryHelper::table_exists( 'wc_order_stats' ) ) {
			return array(
				'discounts'       => array(),
				'total_discounts' => 0,
			);
		}

		$course_post_type = tutor()->course_post_type;
		$user_id          = sanitize_text_field( $user_id );
		$period           = sanitize_text_field( $period );
		$start_date       = sanitize_text_field( $start_date );
		$end_date         = sanitize_text_field( $end_date );

		$period_query = '';
		$group_query  = ' GROUP BY order_details.date_created ';
		$course_query = '';
		$select_query = " DATE_FORMAT( order_details.date_created, '%%b' ) AS label_name, DATE(order_details.date_created) AS date_format";
		// set additional query for period or date range.
		if ( '' !== $period ) {
			$period_query = QueryHelper::get_period_clause( 'order_details.date_created', $period );
		}

		// period query.
		if ( in_array( $period, array( 'last90days', 'last365days', 'yearly' ), true ) ) {
			$group_query  = ' GROUP BY MONTH(order_details.date_created) ';
			$select_query = " DATE_FORMAT( order_details.date_created, '%%b' ) AS label_name";
		}

		if ( $course_id ) {
			$course_query = " AND  post.ID = $course_id ";
		}

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$period_query = " AND DATE(order_details.date_created) BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) ";

			$diff_days = ( new DateTime( $start_date ) )->diff( new DateTime( $end_date ) )->days;

			if ( $diff_days > 31 ) {
				$group_query  = ' GROUP BY DATE(order_details.date_created) ';
				$select_query = " DATE_FORMAT(order_details.date_created, '%%b') AS label_name ";

			}
		}

		/**
		 * Author query added to use same query for admin as well
		 * pass user_id value 0 to get all data for admin
		 *
		 * @since v2.0.0
		 */
		$author_query = '';
		if ( $user_id ) {
			$author_query = "AND post.post_author = {$user_id}";
		}

		$discounts       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					SUM(w_order.coupon_amount) AS total, 
					{$select_query}
				FROM {$wpdb->posts} AS post
					INNER JOIN {$wpdb->postmeta} as mt1 ON mt1.post_id = post.ID
					INNER JOIN {$wpdb->prefix}wc_order_product_lookup AS w_order ON w_order.product_id = mt1.meta_value
					INNER JOIN {$wpdb->prefix}wc_order_stats AS order_details ON order_details.order_id = w_order.order_id
				WHERE mt1.meta_key = %s
					AND post.post_type = %s
					AND post.post_status = %s
					AND order_details.status = %s
					{$author_query}
					{$course_query}
					{$period_query}
					{$group_query}",
				'_tutor_course_product_id',
				$course_post_type,
				'publish',
				'wc-completed'
			)
		);
		$total_discounts = 0;

		foreach ( $discounts as $discount ) {
			$total_discounts += $discount->total;
		}

		return array(
			'discounts'       => $discounts,
			'total_discounts' => $total_discounts,
		);
	}

	/**
	 * Get total number of sales by user_id (instructor), optionally can set period ( today | monthly| yearly )
	 *
	 * Optionally can set start date & end date to get enrollment list from date range
	 *
	 * If period or date range not pass then it will return all time enrollment list
	 *
	 * @param $user_id int | required
	 *
	 * @param $period string | optional
	 *
	 * @param $start_date string | optional | yy-mm-dd
	 *
	 * @param $end_date string | optional | yy-mm-dd
	 *
	 * @return array
	 *
	 * @since 1.9.9
	 */
	public static function number_of_sales( int $user_id, string $period, string $start_date, string $end_date ): array {
		global $wpdb;

		$user_id    = sanitize_text_field( $user_id );
		$period     = sanitize_text_field( $period );
		$start_date = sanitize_text_field( $start_date );
		$end_date   = sanitize_text_field( $end_date );

		$period_query = '';
		$group_query  = ' GROUP BY DATE(created_at) ';
		$select_query = " DATE_FORMAT(created_at, '%%b') AS label_name, DATE(created_at) AS date_format ";
		$course_query = '';
		// set additional query for period or date range.
		if ( $period ) {
			$period_query = QueryHelper::get_period_clause( 'created_at', $period );
		}

		// period query.
		if ( in_array( $period, array( 'last90days', 'last365days', 'yearly' ), true ) ) {
			$group_query  = ' GROUP BY MONTH(created_at) ';
			$select_query = " DATE_FORMAT(created_at, '%%b') AS label_name ";
		}

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$period_query = " AND DATE(created_at) BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) ";

			$diff_days = ( new DateTime( $start_date ) )->diff( new DateTime( $end_date ) )->days;

			if ( $diff_days > 31 ) {
				$group_query  = ' GROUP BY MONTH(created_at) ';
				$select_query = " DATE_FORMAT(created_at, '%%b') AS label_name ";

			}
		}

		$complete_status = tutor_utils()->get_earnings_completed_statuses();
		$complete_status = QueryHelper::prepare_in_clause( $complete_status );

		$sales = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) AS total, 
					{$select_query}
            	FROM 
					{$wpdb->prefix}tutor_earnings
            	WHERE 
					user_id = %d
					AND order_status IN({$complete_status})
					{$period_query}
					{$group_query}
			",
				$user_id
			)
		);

		// Count total sales.
		$total_by_group = array_column( $sales, 'total' );
		$total_sale     = array_sum( $total_by_group );

		return array(
			'sales'       => $sales,
			'total_sales' => $total_sale,
		);
	}

	/**
	 * Get total number of sales by user_id (instructor), optionally can set period ( today | monthly| yearly )
	 *
	 * Optionally can set start date & end date to get enrollment list from date range
	 *
	 * If period or date range not pass then it will return all time enrollment list
	 *
	 * @param $user_id int | required
	 *
	 * @param $period string | optional
	 *
	 * @param $start_date string | optional | yy-mm-dd
	 *
	 * @param $end_date string | optional | yy-mm-dd
	 *
	 * @return array
	 *
	 * @since 1.9.9
	 */
	public static function commission_fees_by_user( int $user_id, string $period, string $start_date, string $end_date ): array {
		global $wpdb;

		$user_id    = sanitize_text_field( $user_id );
		$period     = sanitize_text_field( $period );
		$start_date = sanitize_text_field( $start_date );
		$end_date   = sanitize_text_field( $end_date );

		$period_query = '';
		$group_query  = ' GROUP BY DATE(created_at) ';
		$select_query = " DATE_FORMAT(created_at, '%%b') AS label_name, DATE(created_at) AS date_format ";

		// set additional query for period or date range.
		if ( $period ) {
			$period_query = QueryHelper::get_period_clause( 'created_at', $period );
		}

		// period query.
		if ( in_array( $period, array( 'last90days', 'last365days', 'yearly' ), true ) ) {
			$group_query  = ' GROUP BY MONTH(created_at) ';
			$select_query = " DATE_FORMAT(created_at, '%%b') AS label_name ";
		}

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$period_query = " AND DATE(created_at) BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) ";

			$diff_days = ( new DateTime( $start_date ) )->diff( new DateTime( $end_date ) )->days;

			if ( $diff_days > 31 ) {
				$group_query  = ' GROUP BY MONTH(created_at) ';
				$select_query = " DATE_FORMAT(created_at, '%%b') AS label_name ";

			}
		}

		$complete_status = tutor_utils()->get_earnings_completed_statuses();
		$complete_status = QueryHelper::prepare_in_clause( $complete_status );

		$commission_fees   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					SUM(admin_amount) AS total, 
					SUM(deduct_fees_amount) AS fees, 
					{$select_query}
            	FROM {$wpdb->prefix}tutor_earnings
            	WHERE 
					user_id = %d
					AND order_status IN({$complete_status})
					{$period_query}
					{$group_query}
			",
				$user_id
			)
		);
		$total_commissions = 0;
		$total_fees        = 0;
		$currency_symbol   = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
		foreach ( $commission_fees as $cf ) {
			$total_commissions += $cf->total;
			$total_fees        += $cf->fees;
		}
		$total = 0;
		if ( $total_commissions ) {
			$total = $total_commissions . '-' . $currency_symbol . $total_fees;
		}
		return array(
			'commission_fees' => $commission_fees,
			'total'           => $total,
		);
	}

	/**
	 * Get statements user_id (instructor)
	 *
	 * @since 1.9.9
	 * @since 4.0.0 Added $start_date, $end_date and $order parameters.
	 *
	 * @param int        $user_id     User ID.
	 * @param int        $offset      Number of records to skip (pagination offset).
	 * @param int        $limit       Maximum number of records to return.
	 * @param int|string $course_id   Optional course or bundle ID to filter statements.
	 * @param string     $start_date  Optional. Start date for filtering. Default empty.
	 * @param string     $end_date    Optional. End date for filtering. Default empty.
	 * @param string     $order       Sort direction. Accepts 'ASC' or 'DESC'.
	 *
	 * @return array
	 */
	public static function get_statements_by_user( int $user_id, int $offset, int $limit, $course_id = '', $start_date = '', $end_date = '', $order = '' ): array {
		global $wpdb;
		$course_post_type = tutor()->course_post_type;
		$bundle_post_type = tutor()->bundle_post_type;

		$user_id    = absint( $user_id );
		$limit      = absint( $limit );
		$offset     = absint( $offset );
		$course_id  = absint( $course_id );
		$start_date = sanitize_text_field( $start_date );
		$end_date   = sanitize_text_field( $end_date );
		$order      = QueryHelper::get_valid_sort_order( $order );

		$course_query = '';
		$order_clause = '';
		if ( ! empty( $course_id ) ) {
			$course_query = $wpdb->prepare( ' AND course.ID = %d ', $course_id );
		}

		if ( ! empty( $order ) ) {
			$order_clause = "ORDER BY statements.created_at {$order}";
		}

		$date_query = '';
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$where_condition = array(
				'statements.created_at' => array( 'BETWEEN', array( $start_date, $end_date ) ),
			);
			$date_query      = ' AND ' . QueryHelper::prepare_where_clause( $where_condition );
		}

		$post_type_in_clause = QueryHelper::prepare_in_clause( array( $course_post_type, $bundle_post_type ) );

		$res = array();

		if ( tutor_utils()->is_monetize_by_tutor() ) {
			$order_model = new OrderModel();
			$res         = $order_model->get_statements( $post_type_in_clause, $course_query, $date_query, $user_id, $offset, $limit, 'statements.created_at', $order );
		} elseif ( 'wc' === tutor_utils()->get_option( 'monetize_by' ) ) {

			//phpcs:disable
			$statements = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 
						statements.course_price_total AS order_total_price,
						null AS order_tax_amount,
						null AS order_tax_type,
						statements.*, 
						course.post_title AS course_title
					FROM {$wpdb->prefix}tutor_earnings AS statements
						INNER JOIN {$wpdb->posts} AS course ON course.ID = statements.course_id 
						AND course.post_type IN ({$post_type_in_clause})
					WHERE statements.user_id = %d
					{$course_query}
					{$date_query}
					{$order_clause}
					LIMIT %d, %d",
					$user_id,
					$offset,
					$limit
				)
			);
			//phpcs:enable

			$total_statements = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$wpdb->prefix}tutor_earnings AS statements
						INNER JOIN {$wpdb->posts} AS course ON course.ID = statements.course_id
						AND course.post_type IN ({$post_type_in_clause})
					WHERE statements.user_id = %d
					{$course_query}
					{$date_query}",
					$user_id
				)
			);

			$res = array(
				'statements'       => $statements,
				'total_statements' => $total_statements,
			);
		}

		return $res;
	}

	/**
	 * Get current user
	 *
	 * @return object
	 *
	 * @since 1.9.8
	 */
	public static function current_user() {
		$user = wp_get_current_user();
		return $user;
	}

	/**
	 * Enqueue styles
	 *
	 * @since 1.9.8
	 */
	public function tutor_analytics_scripts() {
		global $wp_query;
		$query_vars = $wp_query->query_vars;

		if ( 'analytics' === ( $query_vars['tutor_dashboard_page'] ?? '' ) ) {

			// export js.
			if ( 'export' === ( $query_vars['tutor_dashboard_sub_page'] ?? '' ) ) {
				wp_enqueue_script(
					'tutor-pro-jszip',
					tutor_pro()->url . 'assets/lib/jszip.min.js',
					array(),
					TUTOR_PRO_VERSION,
					true
				);
				wp_enqueue_script(
					'tutor-pro-export',
					TUTOR_REPORT()->url . 'assets/js/export.js',
					array( 'jquery', 'tutor-pro-jszip' ),
					TUTOR_PRO_VERSION,
					true
				);
			}

			// enqueue styles.
			wp_enqueue_style(
				'tutor-pro-analytics',
				TUTOR_REPORT()->url . 'assets/css/analytics.css',
				'',
				time()
			);
		}
	}

	/**
	 * Get dependent data to make chart
	 *
	 * It will return data as per query vars for Earnings | Enrollment list | Refunds | Discounts
	 *
	 * @return array
	 *
	 * @since 1.9.9
	 */
	protected static function chart_dependent_data() {
		global $wp_query;
		$query_vars = $wp_query->query_vars;
		$user_id    = self::current_user()->ID;
		$analytics  = array();

		if ( isset( $query_vars['tutor_dashboard_page'] ) && 'analytics' === $query_vars['tutor_dashboard_page'] ) {
			$time_period = Input::get( 'period', '' );
			$start_date  = Input::get( 'start_date', '' );
			$end_date    = Input::get( 'end_date', '' );
			if ( '' !== $start_date ) {
				$start_date = tutor_get_formated_date( 'Y-m-d', $start_date );
			}
			if ( '' !== $end_date ) {
				$end_date = tutor_get_formated_date( 'Y-m-d', $end_date );
			}

			if ( tutor_utils()->is_dashboard_page( 'overview' ) ) {
				$analytics = array(
					array(
						'id'    => 'ta_total_earnings',
						'label' => __( 'Earning', 'tutor-pro' ),
						'data'  => self::get_earnings_by_user( $user_id, $time_period, $start_date, $end_date )['earnings'],
					),
					array(
						'id'    => 'ta_total_course_enrolled',
						'label' => __( 'Enrolled', 'tutor-pro' ),
						'data'  => self::get_total_students_by_user( $user_id, $time_period, $start_date, $end_date )['enrollments'],
					),
					array(
						'id'    => 'ta_total_discount',
						'label' => __( 'Discount', 'tutor-pro' ),
						'data'  => self::get_discounts_by_user( $user_id, $time_period, $start_date, $end_date )['discounts'],
					),
					array(
						'id'    => 'ta_total_refund',
						'label' => __( 'Refund', 'tutor-pro' ),
						'data'  => self::get_refunds_by_user( $user_id, $time_period, $start_date, $end_date )['refunds'],
					),
				);
			}

			// Course details graph.
			if ( isset( $query_vars['tutor_dashboard_sub_page'] ) && 'course-details' === $query_vars['tutor_dashboard_sub_page'] ) {
				$course_id = Input::get( 'course_id', 0, Input::TYPE_INT );
				$analytics = array(
					array(
						'id'    => 'ta_total_earnings',
						'label' => __( 'Total Earning', 'tutor-pro' ),
						'data'  => self::get_earnings_by_user( $user_id, $time_period, $start_date, $end_date, $course_id )['earnings'],
					),
					array(
						'id'    => 'ta_total_discount',
						'label' => __( 'Discount', 'tutor-pro' ),
						'data'  => self::get_discounts_by_user( $user_id, $time_period, $start_date, $end_date, $course_id )['discounts'],
					),
					array(
						'id'    => 'ta_total_refund',
						'label' => __( 'Refund', 'tutor-pro' ),
						'data'  => self::get_refunds_by_user( $user_id, $time_period, $start_date, $end_date, $course_id )['refunds'],
					),
				);
			}

			// Earning graph.
			if ( isset( $query_vars['tutor_dashboard_sub_page'] ) && $query_vars['tutor_dashboard_sub_page'] === 'earnings' ) {
				$analytics = array(
					array(
						'id'    => 'ta_total_earnings',
						'label' => __( 'Total Earning', 'tutor-pro' ),
						'data'  => self::get_earnings_by_user( $user_id, $time_period, $start_date, $end_date )['earnings'],
					),
					array(
						'id'    => 'ta_total_course_enrolled',
						'label' => __( 'Number of Sales', 'tutor-pro' ),
						'data'  => self::number_of_sales( $user_id, $time_period, $start_date, $end_date )['sales'],
					),
					array(
						'id'     => 'ta_total_refund',
						'label'  => __( 'Commission', 'tutor-pro' ),
						'label2' => __( 'Fees', 'tutor-pro' ),
						'data'   => self::commission_fees_by_user( $user_id, $time_period, $start_date, $end_date )['commission_fees'],
					),
				);
			}

			return $analytics;
		}
		return $analytics;
	}

	public function view_progress() {
		tutor_utils()->checking_nonce();
		ob_start();
		include_once TUTOR_REPORT()->path . 'templates/course_progress.php';
		//phpcs:ignore --output esc in template file
		echo ob_get_clean();
		exit;
	}

	/**
	 * Get analytics data for export based on current user
	 *
	 * @since 1.9.10
	 *
	 * @return array
	 */
	public function analytics_data(): array {
		$arr = array(
			'students'  => $this->students_data(),
			'earnings'  => $this->earnings_data(),
			'discounts' => $this->discounts_data(),
			'refunds'   => $this->refunds_data(),
		);
		return $arr;
	}

	public function export_analytics() {
		tutor_utils()->checking_nonce();
		$arr = $this->analytics_data();
		wp_send_json_success( $arr );
	}

	/**
	 * Get last enrolled courses
	 *
	 * @param integer $limit
	 * @return mixed
	 * @since 2.0.2
	 */
	public static function get_last_enrolled_courses( int $limit = 5 ) {
		global $wpdb;

		$sql = "SELECT MAX(enrolled.post_date) as enrolled_time, enrolled.guid, enrolled.post_parent, course.ID, course.post_title
		FROM {$wpdb->posts} enrolled
		LEFT JOIN {$wpdb->posts} course ON enrolled.post_parent = course.ID
		WHERE enrolled.post_type = %s 
		AND enrolled.post_status = %s 
		AND course.post_type = %s
		GROUP BY enrolled.post_parent
		ORDER BY enrolled_time DESC LIMIT 0,%d ;";

		return $wpdb->get_results( $wpdb->prepare( $sql, 'tutor_enrolled', 'completed', tutor()->course_post_type, $limit ) );
	}

	/**
	 * Get reviews.
	 *
	 * @since 2.0.2
	 *
	 * @param integer $limit
	 *
	 * @return mixed
	 */
	public static function get_reviews( int $limit = 5 ) {
		global $wpdb;

		$sql = "select {$wpdb->comments}.comment_ID,
		{$wpdb->comments}.comment_post_ID,
		{$wpdb->comments}.comment_author,
		{$wpdb->comments}.comment_author_email,
		{$wpdb->comments}.comment_date,
		{$wpdb->comments}.comment_content,
		{$wpdb->comments}.user_id,
		{$wpdb->commentmeta}.meta_value as rating,
		{$wpdb->users}.display_name
		FROM {$wpdb->comments}
		INNER JOIN {$wpdb->commentmeta}
		ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id
		INNER  JOIN {$wpdb->users}
		ON {$wpdb->comments}.user_id = {$wpdb->users}.ID
		AND meta_key = %s 
		ORDER BY comment_ID DESC LIMIT 0,%d ;";

		return $wpdb->get_results( $wpdb->prepare( $sql, 'tutor_rating', $limit ) );
	}

	/**
	 * Get students
	 *
	 * @param integer $limit
	 * @return mixed
	 * @since 2.0.2
	 */
	public static function get_students( int $limit = 5 ) {
		global $wpdb;

		$sql = "SELECT SQL_CALC_FOUND_ROWS {$wpdb->users}.* ,
		{$wpdb->usermeta}.meta_value as registered_timestamp
		FROM {$wpdb->users}
		INNER JOIN {$wpdb->usermeta}
		ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id )
		WHERE 1 = %d
		AND ( {$wpdb->usermeta}.meta_key = %s )
		ORDER BY {$wpdb->usermeta}.meta_value DESC
		LIMIT 0,%d ";

		return $wpdb->get_results( $wpdb->prepare( $sql, 1, '_is_tutor_student', $limit ) );
	}

	/**
	 * Get teachers
	 *
	 * @param integer $limit
	 * @return mixed
	 * @since 2.0.2
	 */
	public static function get_teachers( int $limit = 5 ) {
		global $wpdb;

		$sql = "SELECT SQL_CALC_FOUND_ROWS {$wpdb->users}.* , meta_role.meta_value as registered_timestamp
		FROM {$wpdb->users}
		INNER JOIN {$wpdb->usermeta} meta_role ON ( {$wpdb->users}.ID = meta_role.user_id )
		INNER JOIN {$wpdb->usermeta} meta_status ON ( {$wpdb->users}.ID = meta_status.user_id )
		WHERE meta_role.meta_key = %s
			AND meta_status.meta_key = %s
			AND meta_status.meta_value = %s
		ORDER BY meta_role.meta_value DESC
		LIMIT 0,%d ";

		return $wpdb->get_results( $wpdb->prepare( $sql, '_is_tutor_instructor', '_tutor_instructor_status', 'approved', $limit ) );
	}

	/**
	 * Prepare earnings chart data with padded values.
	 *
	 * @since 4.0.0
	 *
	 * @param array $graph_data Raw earnings data containing `total` and `month_name`.
	 * @param bool  $price      Whether to prepare earnings data. If false, enrollment
	 *                          data is prepared instead.
	 *
	 * @return array {
	 *     @type int[]    $enrolled|$earnings Earnings/Enrolled values with leading and trailing padding.
	 *     @type string[] $labels   Month labels with leading and trailing padding.
	 * }
	 */
	public static function prepare_chart_data( array $graph_data, bool $price = true ) {

		$metric_key  = $price ? 'earnings' : 'enrolled';
		$metric_date = $price ? 'earning_date' : 'enrollment_date';

		$data = array(
			$metric_key  => array( 0 ),
			'labels'     => array( '' ),
			$metric_date => array( '' ),
			'currency'   => tutor_utils()->get_monetization_currency_config(),
		);

		foreach ( $graph_data as $item ) {
			$data[ $metric_key ][]  = (float) ( $item->total ?? 0 );
			$data['labels'][]       = $item->label_name ?? '';
			$data[ $metric_date ][] = ! empty( $item->date_format ) ? wp_date( 'M d', strtotime( $item->date_format ) ) : '';
		}

		$data[ $metric_key ][]  = 0;
		$data['labels'][]       = '';
		$data[ $metric_date ][] = '';

		return $data;
	}

	/**
	 * Register Report sections into DashboardSectionManager.
	 *
	 * @since 4.1.0
	 *
	 * @param array $sections Existing sections.
	 *
	 * @return array
	 */
	public function register_report_dashboard_sections( array $sections ): array {
		$sections['report_overview_cards']  = array(
			'title'          => __( 'Report Overview Cards', 'tutor-pro' ),
			'date_dependent' => false,
			'callback'       => array( $this, 'render_report_overview_cards_section' ),
		);
		$sections['report_graph']           = array(
			'title'          => __( 'Report Earnings Graph', 'tutor-pro' ),
			'date_dependent' => true,
			'callback'       => array( $this, 'render_report_graph_section' ),
		);
		$sections['report_popular_courses'] = array(
			'title'          => __( 'Report Popular Courses', 'tutor-pro' ),
			'date_dependent' => false,
			'callback'       => array( $this, 'render_report_popular_courses_section' ),
		);

		return $sections;
	}

	/**
	 * Retrieve raw domain data for Report dashboard sections.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed  $data       Default or incoming data payload.
	 * @param string $section_id Section identifier.
	 * @param array  $params     Query parameters.
	 *
	 * @return mixed
	 */
	public function get_report_dashboard_section_data( $data, string $section_id, array $params ) {
		switch ( $section_id ) {
			case 'report_overview_cards':
				return $this->get_report_overview_cards_data( $params );

			case 'report_graph':
				return $this->get_report_graph_data( $params );

			case 'report_popular_courses':
				return $this->get_report_popular_courses_data( $params );

			default:
				return $data;
		}
	}

	/**
	 * Get Report Overview Cards data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $params Query parameters.
	 *
	 * @return array
	 */
	public function get_report_overview_cards_data( array $params ): array {
		$user_id = $params['user_id'] ?? get_current_user_id();

		return array(
			array(
				'icon'      => Icon::COURSES,
				'title'     => esc_html__( 'Courses', 'tutor-pro' ),
				'sub_title' => esc_html__( 'Total Course', 'tutor-pro' ),
				'value'     => InstructorMetricsAdapter::get_total_courses( $user_id ),
			),
			array(
				'icon'      => Icon::PASSED,
				'title'     => esc_html__( 'Students', 'tutor-pro' ),
				'sub_title' => esc_html__( 'Total Student', 'tutor-pro' ),
				'value'     => InstructorMetricsAdapter::get_total_students( $user_id ),
			),
			array(
				'icon'      => Icon::STAR_LINE,
				'title'     => esc_html__( 'Reviews', 'tutor-pro' ),
				'sub_title' => esc_html__( 'Total Reviews', 'tutor-pro' ),
				'value'     => InstructorMetricsAdapter::get_instructor_ratings( $user_id )->rating_count ?? 0,
			),
		);
	}

	/**
	 * Render Report Overview Cards View.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data Overview cards data.
	 *
	 * @return string
	 */
	public function get_report_overview_cards_html( $data ): string {
		$card_template = TUTOR_REPORT()->path . 'templates/elements/box-card.php';
		ob_start();
		tutor_load_template_from_custom_path( $card_template, is_array( $data ) ? $data : array() );
		return ob_get_clean();
	}

	/**
	 * Render Report Overview Cards Section (Legacy / Direct).
	 *
	 * @since 4.1.0
	 *
	 * @param array $params Query parameters.
	 *
	 * @return array
	 */
	public function render_report_overview_cards_section( array $params ): array {
		$data = $this->get_report_overview_cards_data( $params );
		$html = $this->get_report_overview_cards_html( $data );

		return array(
			'html'     => $html,
			'has_data' => ! empty( $data ) && ! empty( $html ),
		);
	}

	/**
	 * Get Report Earnings Graph data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $params Query parameters.
	 *
	 * @return array
	 */
	public function get_report_graph_data( array $params ): array {
		$user_id     = isset( $params['user_id'] ) ? (int) $params['user_id'] : get_current_user_id();
		$start_date  = ! empty( $params['start_date'] ) ? sanitize_text_field( $params['start_date'] ) : '';
		$end_date    = ! empty( $params['end_date'] ) ? sanitize_text_field( $params['end_date'] ) : '';
		$time_period = ( ! empty( $start_date ) && ! empty( $end_date ) ) ? '' : sanitize_text_field( $params['period'] ?? Input::post( 'period', 'today' ) );

		$earnings    = self::get_earnings_by_user( $user_id, $time_period, $start_date, $end_date );
		$enrollments = self::get_total_students_by_user( $user_id, $time_period, $start_date, $end_date );
		$discounts   = self::get_discounts_by_user( $user_id, $time_period, $start_date, $end_date );
		$refunds     = self::get_refunds_by_user( $user_id, $time_period, $start_date, $end_date );

		return array(
			array(
				'tab_title'  => __( 'Total Earning', 'tutor-pro' ),
				'tab_value'  => empty( $earnings['total_earnings'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $earnings['total_earnings'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_earnings',
				'graph_data' => self::prepare_chart_data( $earnings['earnings'] ?? array() ),
			),
			array(
				'tab_title'  => __( 'Course Enrolled', 'tutor-pro' ),
				'tab_value'  => $enrollments['total_enrollments'] ?? 0,
				'data_attr'  => 'ta_total_course_enrolled',
				'graph_data' => self::prepare_chart_data( $enrollments['enrollments'] ?? array(), false ),
			),
			array(
				'tab_title'  => __( 'Total Refund', 'tutor-pro' ),
				'tab_value'  => empty( $refunds['total_refunds'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $refunds['total_refunds'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_refund',
				'graph_data' => self::prepare_chart_data( $refunds['refunds'] ?? array() ),
			),
			array(
				'tab_title'  => __( 'Total Discount', 'tutor-pro' ),
				'tab_value'  => empty( $discounts['total_discounts'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $discounts['total_discounts'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_discount',
				'graph_data' => self::prepare_chart_data( $discounts['discounts'] ?? array() ),
			),
		);
	}

	/**
	 * Render Report Earnings Graph View.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data Graph tabs data.
	 *
	 * @return string
	 */
	public function get_report_graph_html( $data ): string {
		$graph_template = TUTOR_REPORT()->path . 'templates/elements/graph.php';
		ob_start();
		tutor_load_template_from_custom_path( $graph_template, is_array( $data ) ? $data : array() );
		return ob_get_clean();
	}

	/**
	 * Render Report Earnings Graph Section (Legacy / Direct).
	 *
	 * @since 4.1.0
	 *
	 * @param array $params Query parameters.
	 *
	 * @return array
	 */
	public function render_report_graph_section( array $params ): array {
		$data = $this->get_report_graph_data( $params );
		$html = $this->get_report_graph_html( $data );

		return array(
			'html'     => $html,
			'has_data' => ! empty( $data ) && ! empty( $html ),
		);
	}

	/**
	 * Get Report Popular Courses data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $params Context parameters.
	 *
	 * @return array
	 */
	public function get_report_popular_courses_data( array $params ): array {
		$user_id = $params['user_id'] ?? get_current_user_id();
		$courses = tutor_utils()->most_popular_courses( 7, $user_id );
		return is_array( $courses ) ? $courses : array();
	}

	/**
	 * Render Report Popular Courses View.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data Popular courses.
	 *
	 * @return string
	 */
	public function get_report_popular_courses_html( $data ): string {
		$popular_courses = is_array( $data ) ? $data : array();
		if ( empty( $popular_courses ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="tutor-analytics-popular-courses tutor-mt-7">
			<div class="tutor-small tutor-mb-5">
				<?php esc_html_e( 'Most Popular Courses', 'tutor-pro' ); ?>
			</div>

			<?php
			$headings = array_map(
				fn( $content ) => array(
					'content' => $content,
					'class'   => 'tutor-surface-l1-hover',
				),
				array(
					esc_html__( 'Course Name', 'tutor-pro' ),
					esc_html__( 'Total Enrolled', 'tutor-pro' ),
					esc_html__( 'Rating', 'tutor-pro' ),
				)
			);

			$total_enrolled_label = sprintf(
				'<span class="tutor-analytics-popular-courses-mobile-label">%s</span>',
				esc_html__( 'Total Enrolled:', 'tutor-pro' )
			);
			$rating_label         = sprintf(
				'<span class="tutor-analytics-popular-courses-mobile-label">%s</span>',
				esc_html__( 'Rating:', 'tutor-pro' )
			);

			$contents = array();
			foreach ( $popular_courses as $course ) {
				$rating     = tutor_utils()->get_course_rating( $course->ID );
				$avg_rating = ! is_null( $rating ) ? $rating->rating_avg : 0;

				ob_start();
				StarRating::make()->rating( $avg_rating )->render();
				$rating_html = ob_get_clean();

				$course_summary = TUTOR_REPORT()->path . 'templates/elements/course-thumb-title.php';

				$contents[] = array(
					'columns' => array(
						array( 'content' => get_template_buffer( $course_summary, $course, false ) ),
						array( 'content' => $total_enrolled_label . esc_html( $course->total_enrolled ) ),
						array( 'content' => $rating_label . $rating_html ),
					),
				);
			}
			?>
			<div class="tutor-table-wrapper tutor-rounded-2xl tutor-border">
				<?php Table::make()->headings( $headings )->contents( $contents )->render(); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Report Popular Courses Section (Legacy / Direct).
	 *
	 * @since 4.1.0
	 *
	 * @param array $params Query parameters.
	 *
	 * @return array
	 */
	public function render_report_popular_courses_section( array $params ): array {
		$data = $this->get_report_popular_courses_data( $params );
		$html = $this->get_report_popular_courses_html( $data );

		return array(
			'html'     => $html,
			'has_data' => ! empty( $data ),
		);
	}
}
