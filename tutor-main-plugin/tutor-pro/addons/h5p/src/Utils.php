<?php
/**
 * Tutor H5P Addon Utils class
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

use Tutor\Helpers\QueryHelper;
use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utils class for Tutor H5P addon
 */
class Utils {

	/**
	 * Provide the list of user created H5P content from H5P plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param string $order_by sorting order.
	 * @param string $search_filter search for H5P content by title.
	 * @return array
	 */
	public static function get_h5p_contents( $order_by = null, $search_filter = null ) {
		global $wpdb;

		$order_field = null;
		$reverse     = null;
		$filter      = null;

		if ( isset( $order_by ) ) {
			$order_field = 'updated_at';
			$reverse     = 'ASC' === $order_by ? true : false;
		}

		if ( isset( $search_filter ) ) {

			$search_filter = '%' . $wpdb->esc_like( $search_filter ) . '%';

			$filter = array(
				array(
					'title',
					$search_filter,
					'LIKE',
				),
				array(
					'content_type',
					$search_filter,
					'LIKE',
				),
			);

		}

		if ( ! isset( $filter ) ) {
			$filter = array(
				array(
					'user_id',
					get_current_user_id(),
					'=',
				),
			);
		} else {
			array_push(
				$filter,
				array(
					'user_id',
					get_current_user_id(),
					'=',
				)
			);
		}

		$fields       = array( 'title', 'content_type', 'user_name', 'tags', 'updated_at', 'id', 'user_id' );
		$h5p_contents = new \H5PContentQuery( $fields, null, null, $order_field, $reverse, $filter );
		$results      = $h5p_contents->get_rows();

		return $results;
	}

	/**
	 * Get a single H5P content for a given content id.
	 *
	 * @since 3.0.0
	 *
	 * @param object $plugin_instance instance of h5p plugin.
	 * @param int    $content_id the content id to get the H5P content.
	 * @return string
	 */
	public static function get_h5p_content( $plugin_instance, $content_id ) {
		$content = $plugin_instance->get_content( $content_id );
		return $plugin_instance->add_assets( $content );
	}


	/**
	 * Delete quiz statement by quiz id or question id.
	 *
	 * @since 3.0.0
	 *
	 * @param integer $quiz_id the quiz id.
	 * @param array   $question_ids the array of question ids.
	 * @return void
	 */
	public static function delete_h5p_quiz_statements_by_id( $quiz_id = 0, $question_ids = null ) {
		global $wpdb;

		$quiz_id      = Input::sanitize( $quiz_id, 0, Input::TYPE_INT );
		$question_ids = is_array( $question_ids ) ? array_filter( $question_ids, 'is_numeric' ) : null;

		$where_clause = '';

		if ( 0 !== $quiz_id ) {
			$where_clause = " AND quiz_id IN ({$quiz_id})";
		}

		if ( is_array( $question_ids ) && count( $question_ids ) ) {
			$question_ids = QueryHelper::prepare_in_clause( $question_ids );
			$where_clause = " AND question_id IN ({$question_ids})";
		}

		$delete_statements = $wpdb->query(
			//phpcs:disable
			"DELETE FROM {$wpdb->prefix}tutor_h5p_quiz_statement WHERE 1=1 {$where_clause}"
			//phpcs:enable
		);

		$delete_results = $wpdb->query(
			//phpcs:disable
			"DELETE FROM {$wpdb->prefix}tutor_h5p_quiz_result WHERE 1=1 {$where_clause}"
			//phpcs:enable
		);
	}

	/**
	 * Provide the correct translated display text based on current locale.
	 *
	 * @since 3.0.0
	 *
	 * @param object $display_language the object with the display text.
	 * @return string
	 */
	public static function get_xpi_locale_property( $display_language ) {
		$default_locale = 'en-US';
		$current_locale = str_replace( '_', '-', get_locale() );

		if ( property_exists( $display_language, $current_locale ) ) {
			return $display_language->{ $current_locale };
		} else {
			return $display_language->{ $default_locale };
		}
	}


	/**
	 * Get single tutor H5P quiz result.
	 *
	 * @since 3.0.0
	 *
	 * @param int     $question_id the question id.
	 * @param int     $user_id the user id.
	 * @param integer $attempt_id the attempt id.
	 * @param integer $quiz_id the quiz id.
	 * @param integer $content_id the content id.
	 * @return array
	 */
	public static function get_h5p_quiz_result( $question_id, $user_id, $attempt_id = 0, $quiz_id = 0, $content_id = 0 ) {
		global $wpdb;

		$question_id = (int) $question_id;
		$user_id     = (int) $user_id;
		$content_id  = (int) $content_id;
		$attempt_id  = (int) $attempt_id;
		$quiz_id     = (int) $quiz_id;

		$where_clause = $wpdb->prepare( ' AND question_id = %d AND user_id = %d', $question_id, $user_id );

		if ( 0 !== $content_id ) {
			$where_clause .= $wpdb->prepare( ' AND content_id = %d', $content_id );
		}

		if ( 0 !== $attempt_id ) {
			$where_clause .= $wpdb->prepare( ' AND attempt_id = %d', $attempt_id );
		}

		if ( 0 !== $quiz_id ) {
			$where_clause .= $wpdb->prepare( ' AND quiz_id = %d', $quiz_id );
		}

		$quiz_results = $wpdb->get_results(
			// phpcs:disable
			"SELECT * FROM {$wpdb->prefix}tutor_h5p_quiz_result
			WHERE 1=1 {$where_clause}
            ORDER BY finished DESC LIMIT 1"
			// phpcs:enable
		);

		return $quiz_results;
	}

	/**
	 * Get quiz result list for tutor H5P quiz.
	 *
	 * @since 3.0.0
	 *
	 * @param integer $question_id the question id.
	 * @param integer $user_id the user id.
	 * @param integer $attempt_id the attempt id.
	 * @param integer $quiz_id the quiz id.
	 * @param integer $content_id the content id.
	 * @return mixed
	 */
	public static function get_h5p_quiz_results( $question_id = 0, $user_id = 0, $attempt_id = 0, $quiz_id = 0, $content_id = 0 ) {

		global $wpdb;

		$question_id = (int) $question_id;
		$user_id     = (int) $user_id;
		$content_id  = (int) $content_id;
		$attempt_id  = (int) $attempt_id;
		$quiz_id     = (int) $quiz_id;

		$where_clause = '';

		if ( 0 !== $content_id ) {
			$where_clause .= $wpdb->prepare( ' AND content_id = %d', $content_id );
		}

		if ( 0 !== $attempt_id ) {
			$where_clause .= $wpdb->prepare( ' AND attempt_id = %d', $attempt_id );
		}

		if ( 0 !== $quiz_id ) {
			$where_clause .= $wpdb->prepare( ' AND quiz_id = %d', $quiz_id );
		}

		if ( 0 !== $question_id ) {
			$where_clause .= $wpdb->prepare( ' AND question_id = %d', $question_id );
		}

		if ( 0 !== $user_id ) {
			$where_clause .= $wpdb->prepare( ' AND user_id = %d', $user_id );
		}

		$quiz_results = $wpdb->get_results(
			// phpcs:disable
			"SELECT * FROM {$wpdb->prefix}tutor_h5p_quiz_result
			 WHERE 1=1 {$where_clause}"
			// phpcs:enable
		);

		return $quiz_results;
	}

	/**
	 * Convert xAPI result duration from iso8601 to date string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $duration the iso8601 result duration.
	 * @return string
	 */
	public static function convert_iso8601_to_string( $duration ) {
		$hours   = null;
		$minutes = null;
		$seconds = null;

		if ( isset( $duration ) ) {
			preg_match( '/\\d{1,2}.\d{1,2}[H]/', $duration, $hours );
			preg_match( '/\d{1,2}.\d{1,2}[M]/', $duration, $minutes );
			preg_match( '/\d{1,2}.\d{1,2}[S]/', $duration, $seconds );
		}

		$duration = array(
			'hours'   => $hours && is_array( $hours ) ? $hours[0] : 0,
			'minutes' => $minutes && is_array( $minutes ) ? $minutes[0] : 0,
			'seconds' => $seconds && is_array( $seconds ) ? $seconds[0] : 0,
		);

		$hours   = substr( $duration['hours'], 0, -1 );
		$minutes = substr( $duration['minutes'], 0, -1 );
		$seconds = substr( $duration['seconds'], 0, -1 );

		$final_duration             = '';
		$hours ? $final_duration   .= $hours . ' hours ' : '';
		$minutes ? $final_duration .= $minutes . ' minutes ' : '';
		$seconds ? $final_duration .= $seconds . ' seconds' : '';

		return $final_duration;
	}

	/**
	 * Save H5P statements in database
	 *
	 * @since 3.0.0
	 *
	 * @param array $statement the statement data to insert.
	 *
	 * @return int|false
	 */
	public static function save_tutor_h5p_statement( $statement ) {

		global $wpdb;

		$is_inserted = $wpdb->insert( "{$wpdb->prefix}tutor_h5p_statement", $statement );

		return $is_inserted;
	}


	/**
	 * Get last ten H5P statements sorted by saved date.
	 *
	 * @since 3.0.0
	 *
	 * @param string $search the search query string.
	 *
	 * @return array
	 */
	public static function get_last_ten_statements( $search = '' ) {
		global $wpdb;

		// phpcs:disable
		$total_statements = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutor_h5p_statement
				WHERE 1=1 AND instructor_id = %d 
				AND (verb = %s OR activity_name = %s OR user_id = %s)
				ORDER BY created_at DESC LIMIT 10 ",
				get_current_user_id(),
				$search,
				$search,
				$search
			)
		);
		// phpcs:enable

		return $total_statements;
	}

	/**
	 * Obtain H5P content id from shortcode.
	 *
	 * @since 3.0.0
	 *
	 * @param string $short_code get content id from short code.
	 * @return int
	 */
	public static function get_h5p_content_id( $short_code ) {

		$content_id = array();
		preg_match( '/\d+/', explode( ' ', $short_code )[1], $content_id );

		return count( $content_id ) ? (int) $content_id[0] : 0;
	}

	/**
	 * Get all H5P shortcodes from lesson description
	 *
	 * @since 3.0.0
	 *
	 * @param string $input_string the lesson content string.
	 * @return array
	 */
	public static function get_h5p_shortcodes( $input_string ) {

		$matches     = array();
		$match_count = preg_match_all( '/\[h5p id="\d+"\]/', trim( $input_string ), $matches );
		$short_codes = array();

		if ( $match_count > 0 ) {
			$short_codes = $matches[0];
		}
		return $short_codes;
	}

	/**
	 * Provide chart data to show line chart.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public static function chart_data() {
		$analytics = array();
		if ( isset( $_GET['page'] ) && 'tutor_h5p' === $_GET['page'] ) {
			$time_period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : '';
			$start_date  = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
			$end_date    = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';
			if ( '' !== $start_date ) {
				$start_date = tutor_get_formated_date( 'Y-m-d', $start_date );
			}
			if ( '' !== $end_date ) {
				$end_date = tutor_get_formated_date( 'Y-m-d', $end_date );
			}

			$data = Analytics::get_h5p_statements_count( $time_period, $start_date, $end_date )['statements'];

			$analytics = array(
				array(
					'id'    => 'ta_total_statements',
					'label' => __( 'Total Statements', 'tutor-pro' ),
					'data'  => $data,
				),
			);
		}

		return $analytics;
	}

	/**
	 * Provide config for H5P addon
	 *
	 * @since 3.0.0
	 *
	 * @return object
	 */
	public static function addon_config() {
		$info = array(
			'path'             => plugin_dir_path( TUTOR_H5P_FILE ),
			'url'              => plugin_dir_url( TUTOR_H5P_FILE ),
			'basename'         => plugin_basename( TUTOR_H5P_FILE ),
			'version'          => TUTOR_H5P_VERSION,
			'nonce_action'     => 'tutor_nonce_action',
			'nonce'            => '_wpnonce',
			'h5p_plugin'       => class_exists( 'H5P_Plugin' ) ? \H5P_Plugin::get_instance() : null,
			'h5p_admin_plugin' => class_exists( 'H5P_Plugin_Admin') ? \H5P_Plugin_Admin::get_instance() : null,
		);

		return (object) $info;
	}
}
