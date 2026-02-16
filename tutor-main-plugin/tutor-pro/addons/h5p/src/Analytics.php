<?php
/**
 * Handle H5P Analytics logic
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

use TUTOR\Input;
use TUTOR\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor H5P analytics class
 */
class Analytics {

	/**
	 * Tutor H5P analytics class constructor
	 */
	public function __construct() {

		add_action( 'wp_ajax_view_activities_statements_modal', array( $this, 'view_activities_statements_modal' ) );
		add_action( 'wp_ajax_view_verb_statements_modal', array( $this, 'view_verb_statements_modal' ) );
		add_action( 'wp_ajax_view_learners_statements_modal', array( $this, 'view_learners_statements_modal' ) );
		add_action( 'wp_ajax_view_last_ten_statements_modal', array( $this, 'view_last_ten_statements_modal' ) );

		/**
		 * Show result of H5P lesson or quiz interaction
		 */
		add_action( 'wp_ajax_view_h5p_statement_result', array( $this, 'view_h5p_statement_result' ) );
	}


	/**
	 * Provide activity statements modal view for a verb or learner.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public function view_activities_statements_modal() {

		tutor_utils()->checking_nonce();

		$verb    = Input::post( 'verb' );
		$user_id = Input::post( 'user_id', 0, INPUT::TYPE_INT );

		$search = $verb ?? $user_id;

		$all_activity_statements = self::get_h5p_total_statement_count( 'activity_name', '', '', 'DESC', $search );

		ob_start();
		include_once Utils::addon_config()->path . 'views/analytics/modals/activities-modal.php';
		$output = ob_get_clean();

		wp_send_json_success( array( 'output' => $output ) );
	}

	/**
	 * Provide verb statements modal view for a verb or learner.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public function view_verb_statements_modal() {

		tutor_utils()->checking_nonce();

		$activity_name = Input::post( 'activity_name' );
		$user_id       = Input::post( 'user_id', 0, INPUT::TYPE_INT );

		$search = $activity_name ?? $user_id;

		$all_verb_statements = self::get_h5p_total_statement_count( 'verb', '', '', 'DESC', $search );

		ob_start();
		include_once Utils::addon_config()->path . 'views/analytics/modals/verbs-modal.php';
		$output = ob_get_clean();

		wp_send_json_success( array( 'output' => $output ) );
	}

	/**
	 * Provide learner statements modal view for a verb or learner.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public function view_learners_statements_modal() {

		tutor_utils()->checking_nonce();

		$activity_name = Input::post( 'activity_name' );
		$verb          = Input::post( 'verb' );
		$search        = $activity_name ?? $verb;

		$all_learners_statements = self::get_h5p_total_statement_count( 'user_id', '', '', 'DESC', $search );

		ob_start();
		include_once Utils::addon_config()->path . 'views/analytics/modals/learners-modal.php';
		$output = ob_get_clean();

		wp_send_json_success( array( 'output' => $output ) );
	}


	/**
	 * Provide modal view to show last ten statements.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public function view_last_ten_statements_modal() {

		tutor_utils()->checking_nonce();

		$activity_name = Input::post( 'activity_name' );
		$verb          = Input::post( 'verb' );
		$user_id       = Input::post( 'user_id', 0, INPUT::TYPE_INT );
		$search        = $activity_name ?? $verb ?? $user_id;

		$last_ten_statements = Utils::get_last_ten_statements( $search );

		ob_start();
		include_once Utils::addon_config()->path . 'views/analytics/modals/last-statements-modal.php';
		$output = ob_get_clean();

		wp_send_json_success( array( 'output' => $output ) );
	}

	/**
	 * Show tutor H5P result for quiz and lesson statement on modal.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function view_h5p_statement_result() {
		global $wpdb;

		tutor_utils()->checking_nonce();

		if ( ! User::has_any_role( array( User::INSTRUCTOR, User::ADMIN ) ) ) {
			wp_send_json_error( array( 'message' => tutor_utils()->error_message() ) );
		}

		$statement_id = Input::post( 'statement_id', 0, INPUT::TYPE_INT );
		$content_id   = Input::post( 'content_id', 0, INPUT::TYPE_INT );
		$is_lesson    = Input::post( 'is_lesson' );
		$db_name      = isset( $is_lesson ) ? 'tutor_h5p_lesson_statement' : 'tutor_h5p_quiz_statement';

		$where_clause = '';

		if ( 0 !== $statement_id ) {
			$where_clause = $wpdb->prepare( 'AND statement_id = %d', $statement_id );
		}
        //phpcs:disable
		$h5p_quiz_result_statements = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}{$db_name}
            WHERE 1=1 {$where_clause}"
		);
        //phpcs:enable

		ob_start();
		include_once Utils::addon_config()->path . 'views/modals/h5p-quiz-result-modal.php';
		$output = ob_get_clean();

		wp_send_json_success( array( 'output' => $output ) );
	}

	/**
	 * Provide the H5P lesson and quiz statements and statement count for analytics
	 *
	 * @since 3.0.0
	 *
	 * @param string $period_query the period query.
	 * @param string $start_date the start date.
	 * @param string $end_date the end date.
	 * @return array
	 */
	public static function get_h5p_statements_count( $period_query = '', $start_date = '', $end_date = '' ) {
		global $wpdb;

		$group_query      = ' GROUP BY saved_date';
		$total_statements = 0;
		$all_statements   = array();

		if ( '' !== $period_query ) {
			switch ( $period_query ) {
				case 'monthly':
					$period_query = ' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())';
					break;
				case 'today':
					$period_query = ' AND DATE(created_at) = CURDATE()';
					break;
				case 'yearly':
					$period_query = ' AND YEAR(created_at) = YEAR(CURDATE())';
					break;
				case 'last30days':
					$period_query = ' AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE() ';
					break;
				case 'last90days':
					$period_query = ' AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY) AND CURDATE() ';
					break;
				case 'last365days':
					$period_query = ' AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 365 DAY) AND CURDATE() ';
					break;
				default:
					break;
			}
		}

		if ( '' !== $start_date && '' !== $end_date ) {
			$period_query = " AND DATE(created_at) BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) ";
		}

		// phpcs:disable
		$output = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COUNT(statement_id) as total,
            	DATE(created_at) as saved_date
            	FROM {$wpdb->prefix}tutor_h5p_statement
				WHERE 1=1 AND instructor_id = %d
            	{$period_query}
            	{$group_query}",
				get_current_user_id()
			)
		);
		// phpcs:enable

		$statements = array(
			'statements'       => $output,
			'statements_count' => isset( $output[0] ) ? $output[0]->total : 0,
		);

		return $statements;
	}

	/**
	 * Get total H5P statements count
	 *
	 * @since 3.0.0
	 *
	 * @return string|int|null
	 */
	public static function get_all_statements_count() {
		global $wpdb;

		//phpcs:disable
		$output = $wpdb->get_var(
			$wpdb->prepare( 
				"SELECT COUNT(statement_id) as total
				FROM {$wpdb->prefix}tutor_h5p_statement
				WHERE 1=1 AND instructor_id = %d",
				get_current_user_id()
			)
		);
		//phpcs:enable

		return $output;
	}

	/**
	 * Get total H5P statements count in a month
	 *
	 * @since 3.0.0
	 *
	 * @return string|int|null
	 */
	public static function get_all_monthly_statements_count() {

		global $wpdb;

		//phpcs:disable
		$output = $wpdb->get_var(
			$wpdb->prepare( 
				"SELECT COUNT(statement_id) as total
				FROM {$wpdb->prefix}tutor_h5p_statement
				WHERE 1=1 AND instructor_id = %d
				AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())",
				get_current_user_id()
			)
		);
		//phpcs:enable

		return $output;
	}

	/**
	 * Get total statements for verb, activities and learners.
	 *
	 * @since 3.0.0
	 *
	 * @param string $group_by group by verb, activity or learner.
	 * @param string $limit limit the rows returned.
	 * @param string $offset offset the rows.
	 * @param string $order sorting order.
	 * @param string $search the search value.
	 * @param string $date the date value to search for.
	 * @return array
	 */
	public static function get_h5p_total_statement_count( $group_by = '', $limit = '', $offset = '', $order = 'DESC', $search = '', $date = '' ) {
		global $wpdb;

		$group_by_column = sanitize_text_field( $group_by );
		$limit_query     = sanitize_text_field( $limit );
		$offset_query    = sanitize_text_field( $offset );
		$order_query     = sanitize_sql_orderby( $order );
		$search_query    = sanitize_text_field( $search );
		$date            = sanitize_text_field( $date );

		if ( '' !== $group_by ) {
			$group_by = " GROUP BY {$group_by_column}";
		}

		if ( '' !== $limit_query ) {
			$limit = "LIMIT {$limit_query}";
		}

		if ( '' !== $offset_query ) {
			$offset = "OFFSET {$offset_query}";
		}

		if ( '' !== $order_query ) {
			$order = "ORDER BY statement_count {$order_query}";
		}

		if ( '' !== $search ) {
			$search = $wpdb->prepare( ' AND (verb = %s OR activity_name = %s OR user_id = %s)', $search_query, $search_query, $search_query );
		}

		if ( '' !== $date ) {
			$date_query = tutor_get_formated_date( 'Y-m-d', $date );
			$date       = " AND DATE(created_at) = '$date_query'";
		}

		$statements = $wpdb->get_results(
			// phpcs:disable
			$wpdb->prepare(
				"SELECT {$group_by_column}, verb_id, COUNT($group_by_column) AS 'statement_count'
            	FROM {$wpdb->prefix}tutor_h5p_statement
            	WHERE 1=1 AND instructor_id = %d {$search} {$date}
            	{$group_by}
				{$order}
				{$limit}
				{$offset}",
				get_current_user_id()
			)
			// phpcs:enable
		);

		return $statements;
	}
}
