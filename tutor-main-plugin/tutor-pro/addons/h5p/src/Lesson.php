<?php
/**
 * Handle Tutor H5P Lesson logic
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

use Tutor\Cache\TutorCache;
use TUTOR\Input;
use TUTOR\Tutor_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Tutor H5P Lesson class
 */
class Lesson extends Tutor_Base {

	const TUTOR_H5P_LESSON_STATEMENT_LIST  = 'tutor-h5p-lesson-statements';
	const TUTOR_H5P_LESSON_STATEMENT_COUNT = 'tutor-h5p-lesson-statement-count';
	/**
	 * Tutor H5P Lesson class constructor
	 */
	public function __construct() {

		/**
		 * Hooks for handling tutor H5P lesson
		*/
		add_action( 'wp_ajax_tutor_h5p_list_lesson_contents', array( $this, 'obtain_lesson_content_list' ) );
		add_action( 'wp_ajax_save_h5p_lesson_xAPI_statement', array( $this, 'save_h5p_lesson_xAPI_statement' ) );
		add_filter( 'tutor_validate_lesson_complete', array( $this, 'lesson_completion_restriction' ), 10, 3 );
		add_action( 'wp_ajax_set_h5p_lesson_finished', array( $this, 'set_h5p_lesson_finished' ) );
	}

	/**
	 * Set meta value when all H5P contents are complete
	 *
	 * @since 3.0.0
	 *
	 * @return JSON
	 */
	public function set_h5p_lesson_finished() {

		tutor_utils()->checking_nonce();

		$user_id   = get_current_user_id();
		$lesson_id = Input::post( 'lesson_id', 0, INPUT::TYPE_INT );

		update_user_meta( $user_id, '_tutor_h5p_lesson_completed_' . $lesson_id, true );

		return wp_send_json_success();
	}

	/**
	 * Prevent h5p lesson completion until all h5p lesson are complete
	 *
	 * @since 3.0.0
	 *
	 * @param bool $is_complete check if the lesson is complete.
	 * @param int  $user_id the user id.
	 * @param int  $lesson_id the lesson id.
	 * @return bool
	 */
	public function lesson_completion_restriction( $is_complete, $user_id, $lesson_id ) {
		$lesson     = get_post( $lesson_id );
		$content    = $lesson->post_content;
		$shortcodes = Utils::get_h5p_shortcodes( $content );

		if ( ! tutor_utils()->get_option( 'disable_complete_lesson_button' ) ) {
			return $is_complete;
		}

		if ( count( $shortcodes ) ) {
			if ( ! get_user_meta( $user_id, '_tutor_h5p_lesson_completed_' . $lesson_id, true ) ) {
				$is_complete = false;
			}
		}

		return $is_complete;
	}

	/**
	 * Provide H5P content list for lesson.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function obtain_lesson_content_list() {

		tutor_utils()->checking_nonce();

		$search_filter = Input::post( 'search_filter', null, INPUT::TYPE_STRING );
		$h5p_contents  = Utils::get_h5p_contents( null, $search_filter );

		$filtered_h5p_contents = array();
		$filtered_contents     = array( 'Personality Quiz' );

		if ( tutor_utils()->get_option( 'disable_complete_lesson_button' ) ) {
			array_push( $filtered_contents, 'Interactive Book' );
		}

		// The following content type support are not available for lesson for now.
		foreach ( $h5p_contents as $content ) {
			if ( ! in_array( $content->content_type, $filtered_contents, true ) ) {
				array_push( $filtered_h5p_contents, $content );
			}
		}

		wp_send_json_success( array( 'output' => $filtered_h5p_contents ) );
	}

	/**
	 * Save tutor H5P lesson xAPI statement on database
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public function save_h5p_lesson_xAPI_statement() {
		global $wpdb;
		tutor_utils()->checking_nonce();

		$topic_id   = Input::post( 'topic_id', 0, INPUT::TYPE_INT );
		$lesson_id  = Input::post( 'lesson_id', 0, INPUT::TYPE_INT );
		$content_id = Input::post( 'content_id', 0, INPUT::TYPE_INT );
		$course_id  = Input::post( 'course_id', 0, INPUT::TYPE_INT );
		$instructor = tutor_utils()->get_instructors_by_course( $course_id );
		$user_id    = get_current_user_id();
		$statement  = Input::post( 'statement' );

		if ( 0 === $topic_id && 0 === $lesson_id && 0 === $course_id && 0 === $content_id ) {
			wp_send_json_error();
		}

		$lesson_xapi_statement = array(
			'content_id'    => $content_id,
			'user_id'       => $user_id,
			'created_at'    => current_time( 'mysql', true ),
			'instructor_id' => isset( $instructor[0] ) ? $instructor[0]->ID : 0,
		);

		$statement = json_decode( $statement );

		// Check if statement has verb.
		if ( isset( $statement->verb ) ) {
			$verb = $statement->verb;

			if ( isset( $verb->id ) ) {
				$verb_id                          = $verb->id;
				$lesson_xapi_statement['verb_id'] = $verb_id;
			}

			// The verb display text is placed on the display property.
			if ( isset( $verb->display ) ) {

				$display                       = $verb->display;
				$lesson_xapi_statement['verb'] = Utils::get_xpi_locale_property( $display );

			}
		}

		// Check if statement has activity.
		if ( isset( $statement->object ) ) {

			$object = $statement->object;

			// Activity definition property contains the activity details.
			if ( isset( $object->definition ) ) {
				$definition = $object->definition;

				if ( isset( $definition->name ) ) {
					$lesson_xapi_statement['activity_name'] = Utils::get_xpi_locale_property( $definition->name );
				} elseif ( isset( $definition->description ) ) {
						$lesson_xapi_statement['activity_name'] = Utils::get_xpi_locale_property( $definition->description );
				} elseif ( isset( $statement->context ) ) {
					$context                                = $statement->context;
					$contextActivities                      = isset( $context->contextActivities ) ? $context->contextActivities : null;
					$category                               = isset( $contextActivities->category ) ? $contextActivities->category : null;
					$library_id                             = is_array( $category ) && count( $category ) ? $category[0]->id : null;
					$library_name                           = isset( $library_id ) ? str_replace( 'http://h5p.org/libraries/', '', $library_id ) : null;
					$lesson_xapi_statement['activity_name'] = isset( $library_name ) ? $library_name : '';
				}
				$lesson_xapi_statement['activity_description'] = isset( $definition->description ) ? Utils::get_xpi_locale_property( $definition->description ) : '';

				if ( is_array( $definition->correctResponsesPattern ) && count( $definition->correctResponsesPattern ) ) {
					$lesson_xapi_statement['activity_correct_response_pattern'] = maybe_serialize( $definition->correctResponsesPattern );
				}

				// Choices are the available choices provided on the H5P content.
				$choices = $definition->choices ?? $definition->source;
				if ( is_array( $choices ) && count( $choices ) ) {
					$lesson_xapi_statement['activity_choices'] = maybe_serialize( $choices );
				}

				// Target are the possible answer for the H5P content.
				$target = $definition->target;
				if ( is_array( $target ) && count( $target ) ) {
					$lesson_xapi_statement['activity_target'] = maybe_serialize( $target );
				}

				$lesson_xapi_statement['activity_interaction_type'] = isset( $definition->interactionType ) ? $definition->interactionType : '';
			}
		}

		// Check if result is provided with the statement.
		if ( isset( $statement->result ) ) {
			$result = $statement->result;
			if ( isset( $result->score ) ) {
				$score                                        = $result->score;
				$lesson_xapi_statement['result_max_score']    = $score->max;
				$lesson_xapi_statement['result_min_score']    = $score->min;
				$lesson_xapi_statement['result_raw_score']    = $score->raw;
				$lesson_xapi_statement['result_scaled_score'] = $score->scaled;
			}

			$lesson_xapi_statement['result_completion'] = $result->completion;
			$lesson_xapi_statement['result_success']    = $result->success;
			$lesson_xapi_statement['result_response']   = $result->response;
			$lesson_xapi_statement['result_duration']   = $result->duration;
		}

		Utils::save_tutor_h5p_statement( $lesson_xapi_statement );

		$lesson_xapi_statement['course_id'] = $course_id;
		$lesson_xapi_statement['lesson_id'] = $lesson_id;
		$lesson_xapi_statement['topic_id']  = $topic_id;

		$is_inserted = $wpdb->insert( "{$wpdb->prefix}tutor_h5p_lesson_statement", $lesson_xapi_statement );

		if ( $is_inserted ) {
			wp_send_json_success();
		}
	}

	/**
	 * Delete H5P lesson statements by course id or lesson id.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id the course id.
	 * @param int $lesson_id the lesson id.
	 * @return void
	 */
	public static function delete_h5p_lesson_statements_by_id( $course_id, $lesson_id ) {
		global $wpdb;

		$course_id = (int) filter_var( $course_id, FILTER_SANITIZE_NUMBER_INT );
		$lesson_id = (int) filter_var( $lesson_id, FILTER_SANITIZE_NUMBER_INT );

		$where_clause = '';

		if ( 0 !== $course_id ) {
			$where_clause = "WHERE course_id IN ({$course_id})";
		}
		if ( 0 !== $lesson_id ) {
			$where_clause = "WHERE lesson_id IN ({$lesson_id})";
		}

		$delete_statements = $wpdb->query(
			"DELETE FROM {$wpdb->prefix}tutor_h5p_lesson_statement {$where_clause}"
		);
	}


	/**
	 * Count all the H5P lesson statements.
	 *
	 * @since 3.0.0
	 *
	 * @param string $search search filter to query with.
	 * @param string $date date filter to query with.
	 *
	 * @return int
	 */
	public static function count_h5p_lesson_statements( $search = '', $date = '', $filter = '' ) {
		global $wpdb;

		$search_query = sanitize_text_field( $search );
		$date_query   = sanitize_text_field( $date );
		$filter_query = sanitize_text_field( $filter );

		if ( '' !== $search_query ) {
			$search_query = '%' . $wpdb->esc_like( $search_query ) . '%';
			$search       = $wpdb->prepare( ' AND (verb LIKE %s OR activity_name LIKE %s OR user_id LIKE %s)', $search_query, $search_query, $search_query );
		}

		if ( '' !== $date ) {
			$date_query = tutor_get_formated_date( 'Y-m-d', $date );
			$date       = $wpdb->prepare( ' AND DATE(created_at) = %s', $date_query );
		}

		if ( '' !== $filter_query ) {
			$filter = $wpdb->prepare( ' AND course_id = %d', $filter_query );
		}

		$statement_count = TutorCache::get( self::TUTOR_H5P_LESSON_STATEMENT_COUNT );

		if ( false === $statement_count ) {
			TutorCache::set(
				self::TUTOR_H5P_LESSON_STATEMENT_COUNT,
				// phpcs:disable
				$statement_count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(statement_id) FROM {$wpdb->prefix}tutor_h5p_lesson_statement
						WHERE 1=1 AND instructor_id = %d {$filter} {$search} {$date}",
						get_current_user_id()
					)
				)
				// phpcs:enable
			);
		}

		return (int) $statement_count;
	}


	/**
	 * Get all the tutor H5P lesson statements.
	 *
	 * @since 3.0.0
	 *
	 * @param string $limit the row limit.
	 * @param string $offset the row offset.
	 * @param string $order the sorting order.
	 * @param string $search the search value.
	 * @param string $date the date value to search for.
	 * @return array
	 */
	public static function get_h5p_lesson_statements( $limit = '', $offset = '', $order = 'DESC', $search = '', $date = '', $filter = '' ) {
		global $wpdb;

		$limit_query  = sanitize_text_field( $limit );
		$offset_query = sanitize_text_field( $offset );
		$order_query  = sanitize_sql_orderby( $order );
		$search_query = sanitize_text_field( $search );
		$date_query   = sanitize_text_field( $date );
		$filter_query = sanitize_text_field( $filter );

		if ( '' !== $limit_query ) {
			$limit = ' LIMIT %d';
		}

		if ( '' !== $offset_query ) {
			$offset = ' OFFSET %d';
		}

		if ( $order_query ) {
			$order = " ORDER BY created_at {$order_query}";
		}

		if ( '' !== $search_query ) {
			$search_query = '%' . $wpdb->esc_like( $search_query ) . '%';
			$search       = $wpdb->prepare( ' AND (verb LIKE %s OR activity_name LIKE %s OR user_id LIKE %s)', $search_query, $search_query, $search_query );
		}

		if ( '' !== $date ) {
			$date_query = tutor_get_formated_date( 'Y-m-d', $date );
			$date       = $wpdb->prepare( ' AND DATE(created_at) = %s', $date_query );
		}

		if ( '' !== $filter_query ) {
			$filter = $wpdb->prepare( ' AND course_id = %d', $filter_query );
		}

		$lesson_statements = TutorCache::get( self::TUTOR_H5P_LESSON_STATEMENT_LIST );

		if ( false === $lesson_statements ) {
			TutorCache::set(
				self::TUTOR_H5P_LESSON_STATEMENT_LIST,
				// phpcs:disable
				$lesson_statements = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}tutor_h5p_lesson_statement
            			WHERE 1=1 AND instructor_id = %d {$filter} {$search} {$date} {$order}
            			{$limit}
           				{$offset}",
						get_current_user_id(),
						$limit_query,
						$offset_query,
					)
				)
				// phpcs:enable
			);
		}

		return $lesson_statements;
	}
}
