<?php
/**
 * Handle H5P Quiz logic
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

use Tutor\Cache\TutorCache;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;
use TUTOR\Tutor_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor H5P Quiz class
 */
class Quiz extends Tutor_Base {

	const TUTOR_H5P_QUIZ_STATEMENT_LIST  = 'tutor-h5p-quiz-statements';
	const TUTOR_H5P_QUIZ_STATEMENT_COUNT = 'tutor-h5p-quiz-statement-count';


	/**
	 * Constructor for Tutor H5P Quiz class
	 */
	public function __construct() {

		/**
		 * Hooks for handling tutor H5P quiz
		 */
		add_action( 'wp_ajax_tutor_h5p_list_quiz_contents', array( $this, 'obtain_quiz_content_list' ) );
		add_action( 'wp_ajax_save_h5p_question_xAPI_statement', array( $this, 'save_h5p_question_xAPI_statement' ) );
		add_action( 'wp_ajax_check_h5p_question_answered', array( $this, 'check_h5p_question_answered' ) );
		add_action( 'tutor_quiz/attempt_deleted', array( $this, 'delete_h5p_quiz_result_by_attempt_id' ), 10, 1 );

		/**
		 * Delete all quiz statements after quiz is deleted
		 */
		add_action( 'tutor_delete_quiz_after', array( $this, 'delete_h5p_quiz_info_all' ) );

		add_action( 'tutor_deleted_quiz_question_ids', array( $this, 'delete_h5p_question_info_all' ) );

		add_action( 'wp_ajax_view_h5p_quiz_result', array( $this, 'view_h5p_quiz_result' ) );

		add_filter( 'tutor_filter_attempt_answer_column', array( $this, 'filter_columns' ), 10, 2 );
	}

	/**
	 * Filter out table columns in tutor quiz attempt answers for h5p question.
	 *
	 * @since 3.0.0
	 *
	 * @param array $columns the columns to filter.
	 * @param array $answers the tutor quiz question answers array.
	 * @return array
	 */
	public function filter_columns( $columns, $answers ) {
		$is_h5p = false;

		// Check if it is a h5p quiz containing h5p questions.
		if ( is_array( $answers ) && count( $answers ) ) {
			foreach ( $answers as $answer ) {
				if ( isset( $answer->question_type ) && 'h5p' === $answer->question_type ) {
					$is_h5p = true;
				} else {
					$is_h5p = false;
				}
			}
		}

		if ( $is_h5p ) {
			$columns = array_filter(
				$columns,
				function ( $column ) {
					return ! in_array( $column, array( 'Given Answer', 'Correct Answer' ), true );
				}
			);
		}

		return $columns;
	}


	/**
	 * Count all the H5P quiz statements.
	 *
	 * @since 3.0.0
	 *
	 * @param string $search search filter to query with.
	 * @param string $date date filter to query with.
	 * @param string $filter the filter string to filter out content.
	 *
	 * @return int
	 */
	public static function count_h5p_quiz_statements( $search = '', $date = '', $filter = '' ) {
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

		$statement_count = TutorCache::get( self::TUTOR_H5P_QUIZ_STATEMENT_COUNT );

		if ( false === $statement_count ) {
			TutorCache::set(
				self::TUTOR_H5P_QUIZ_STATEMENT_COUNT,
				// phpcs:disable
				$statement_count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(statement_id) FROM {$wpdb->prefix}tutor_h5p_quiz_statement
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
	 * Get all the tutor H5P quiz statements.
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
	public static function get_h5p_quiz_statements( $limit = '', $offset = '', $order = 'DESC', $search = '', $date = '', $filter = '' ) {
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

		$quiz_statement = TutorCache::get( self::TUTOR_H5P_QUIZ_STATEMENT_LIST );

		if ( false === $quiz_statement ) {
			TutorCache::set(
				self::TUTOR_H5P_QUIZ_STATEMENT_LIST,
				// phpcs:disable
				$quiz_statement = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}tutor_h5p_quiz_statement
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

		return $quiz_statement;
	}


	/**
	 * Provide the H5P content list for creating tutor H5P quiz.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public function obtain_quiz_content_list() {
		tutor_utils()->checking_nonce();

		$search_filter = Input::post( 'search_filter', null, INPUT::TYPE_STRING );
		$h5p_contents  = Utils::get_h5p_contents( null, $search_filter );

		$filtered_h5p_contents = array();
		$filtered_contents     = array( 'Game Map', 'Question Set', 'Interactive Book', 'Interactive Video', 'Course Presentation', 'Personality Quiz' );

		// The following content type support are not available for quiz for now.
		foreach ( $h5p_contents as $content ) {
			if ( ! in_array( $content->content_type, $filtered_contents, true ) ) {
				array_push( $filtered_h5p_contents, $content );
			}
		}

		wp_send_json_success( array( 'output' => $filtered_h5p_contents ) );
	}

	/**
	 * Save tutor H5P quiz statement result property.
	 *
	 * @since 3.0.0
	 *
	 * @param object $result_statement the result property of statement.
	 * @param int    $quiz_id the quiz id.
	 * @param int    $attempt_id the attempt id.
	 * @param int    $question_id the question id.
	 * @param int    $content_id the content id.
	 * @return string|int
	 */
	private function save_h5p_quiz_statement_results( $result_statement, $quiz_id, $attempt_id, $question_id, $content_id ) {
		global $wpdb;

		$quiz_result = array(
			'quiz_id'     => $quiz_id,
			'question_id' => $question_id,
			'content_id'  => $content_id,
			'user_id'     => get_current_user_id(),
			'attempt_id'  => $attempt_id,
		);

		$content_result = Utils::addon_config()->h5p_admin_plugin->get_results( $content_id, get_current_user_id(), 0, 10, 1, 0 )[0];

		if ( isset( $result_statement->score ) ) {
			$score                       = $result_statement->score;
			$quiz_result['max_score']    = $score->max;
			$quiz_result['min_score']    = $score->min;
			$quiz_result['raw_score']    = $score->raw;
			$quiz_result['scaled_score'] = $score->scaled;
		}

		$quiz_result['completion'] = $result_statement->completion;
		$quiz_result['success']    = $result_statement->success;
		$quiz_result['response']   = $result_statement->response;
		$quiz_result['opened']     = $content_result->opened;
		$quiz_result['finished']   = $content_result->finished;

		$duration                = abs( $content_result->finished - $content_result->opened );
		$quiz_result['duration'] = (int) $duration;

		$result_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT result_id FROM {$wpdb->prefix}tutor_h5p_quiz_result 
				WHERE 1=1 AND (quiz_id = %d AND question_id = %d AND content_id = %d 
				AND user_id = %d AND attempt_id = %s)",
				$quiz_id,
				$question_id,
				$content_id,
				get_current_user_id(),
				$attempt_id
			)
		);

		if ( isset( $result_id ) && (int) $result_id > 0 ) {
			return 0;
		}

		$wpdb->insert( "{$wpdb->prefix}tutor_h5p_quiz_result", $quiz_result );
		return $wpdb->insert_id;
	}

	/**
	 * Save tutor H5P quiz statement on database.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function save_h5p_question_xAPI_statement() {
		global $wpdb;

		tutor_utils()->checking_nonce();

		$user_id             = get_current_user_id();
		$quiz_id             = Input::post( 'quiz_id', 0, INPUT::TYPE_INT );
		$course_id           = tutor_utils()->get_course_id_by_subcontent( $quiz_id );
		$topic_id            = wp_get_post_parent_id( $quiz_id );
		$question_id         = Input::post( 'question_id', 0, INPUT::TYPE_INT );
		$content_id          = Input::post( 'content_id', 0, INPUT::TYPE_INT );
		$statement           = Input::post( 'statement' );
		$attempt_id          = Input::post( 'attempt_id' );
		$course_id           = tutor_utils()->get_course_id_by_subcontent( $quiz_id );
		$instructor          = tutor_utils()->get_instructors_by_course( $course_id );
		$quiz_xapi_statement = array(
			'content_id'    => $content_id,
			'user_id'       => $user_id,
			'created_at'    => current_time( 'mysql', true ),
			'instructor_id' => isset( $instructor[0] ) ? $instructor[0]->ID : 0,
		);
		$quiz_result_id      = 0;
		$statement           = json_decode( $statement );

		// Check if statement has verb.
		if ( isset( $statement->verb ) ) {
			$verb = $statement->verb;

			if ( isset( $verb->id ) ) {
				$verb_id                        = $verb->id;
				$quiz_xapi_statement['verb_id'] = $verb_id;
			}

			// xAPI verb display text is placed in display property.
			if ( isset( $verb->display ) ) {

				$display                     = $verb->display;
				$quiz_xapi_statement['verb'] = Utils::get_xpi_locale_property( $display );

			}
		}

		// Check if the statement has any activity.
		if ( isset( $statement->object ) ) {

			$object = $statement->object;

			// xAPI activity definition property consist the activity details.
			if ( isset( $object->definition ) ) {
				$definition = $object->definition;

				if ( isset( $definition->name ) ) {
					$quiz_xapi_statement['activity_name'] = Utils::get_xpi_locale_property( $definition->name );
				} elseif ( isset( $definition->description ) ) {
					$quiz_xapi_statement['activity_name'] = Utils::get_xpi_locale_property( $definition->description );
				} elseif ( isset( $statement->context ) ) {
					$context                              = $statement->context;
					$contextActivities                    = isset( $context->contextActivities ) ? $context->contextActivities : null;
					$category                             = isset( $contextActivities->category ) ? $contextActivities->category : null;
					$library_id                           = is_array( $category ) && count( $category ) ? $category[0]->id : null;
					$library_name                         = isset( $library_id ) ? str_replace( 'http://h5p.org/libraries/', '', $library_id ) : null;
					$quiz_xapi_statement['activity_name'] = isset( $library_name ) ? $library_name : '';
				}

				$quiz_xapi_statement['activity_description'] = isset( $definition->description ) ? Utils::get_xpi_locale_property( $definition->description ) : '';

				if ( is_array( $definition->correctResponsesPattern ) && count( $definition->correctResponsesPattern ) ) {
					$quiz_xapi_statement['activity_correct_response_pattern'] = maybe_serialize( $definition->correctResponsesPattern );
				}

				// Activity choices are the possible option to select on H5P content.
				$choices = $definition->choices ?? $definition->source;
				if ( is_array( $choices ) && count( $choices ) ) {
					$quiz_xapi_statement['activity_choices'] = maybe_serialize( $choices );
				}

				// Targets of an activity are the possible answers to choose for the H5P content.
				$target = $definition->target;
				if ( is_array( $target ) && count( $target ) ) {
					$quiz_xapi_statement['activity_target'] = maybe_serialize( $target );
				}

				$quiz_xapi_statement['activity_interaction_type'] = isset( $definition->interactionType ) ? $definition->interactionType : '';
			}
		}

		// Check if the xAPI statement has result.
		if ( isset( $statement->result ) ) {
			$result         = $statement->result;
			$quiz_result_id = $this->save_h5p_quiz_statement_results( $result, $quiz_id, $attempt_id, $question_id, $content_id );
			if ( isset( $result->score ) ) {
				$score                                      = $result->score;
				$quiz_xapi_statement['result_max_score']    = $score->max;
				$quiz_xapi_statement['result_min_score']    = $score->min;
				$quiz_xapi_statement['result_raw_score']    = $score->raw;
				$quiz_xapi_statement['result_scaled_score'] = $score->scaled;
			}

			$quiz_xapi_statement['result_completion'] = $result->completion;
			$quiz_xapi_statement['result_success']    = $result->success;
			$quiz_xapi_statement['result_response']   = $result->response;
			$quiz_xapi_statement['result_duration']   = $result->duration;
		}

		Utils::save_tutor_h5p_statement( $quiz_xapi_statement );

		$quiz_xapi_statement['quiz_id']     = $quiz_id;
		$quiz_xapi_statement['question_id'] = $question_id;
		$quiz_xapi_statement['course_id']   = $course_id;
		$quiz_xapi_statement['topic_id']    = $topic_id;

		if ( $quiz_result_id ) {
			$quiz_xapi_statement['quiz_result_id'] = $quiz_result_id;
		}

		$is_inserted = $wpdb->insert( "{$wpdb->prefix}tutor_h5p_quiz_statement", $quiz_xapi_statement );

		if ( $is_inserted ) {
			wp_send_json_success();
		}
	}

	/**
	 * Check if all the H5P question for tutor H5P quiz has been answered.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function check_h5p_question_answered() {

		tutor_utils()->checking_nonce();

		$question_ids = Input::post( 'question_ids' );
		$question_ids = json_decode( $question_ids, true );
		$quiz_id      = Input::post( 'quiz_id', 0, INPUT::TYPE_INT );
		$attempt_id   = Input::post( 'attempt_id', 0, INPUT::TYPE_INT );
		$user_id      = get_current_user_id();

		$required_answers = array();

		if ( is_array( $question_ids ) && count( $question_ids ) ) {
			foreach ( $question_ids as $id_data ) {

				$question_id = (int) $id_data['question_id'];
				$content_id  = (int) $id_data['content_id'];

				if ( 0 !== $question_id && 0 !== $content_id ) {
					$has_result = Utils::get_h5p_quiz_result( $question_id, $user_id, $attempt_id, $quiz_id, $content_id );
					if ( is_array( $has_result ) && ! count( $has_result ) ) {
						array_push(
							$required_answers,
							array(
								'question_id' => $question_id,
								'content_id'  => $content_id,
							)
						);
					}
				}
			}
		}

		wp_send_json_success( array( 'required_answers' => json_encode( $required_answers ) ) );
	}

	/**
	 * Remove H5P quiz results after quiz attempt is deleted.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attempt_ids the list of attempt ids.
	 * @return void
	 */
	public function delete_h5p_quiz_result_by_attempt_id( $attempt_ids ) {
		global $wpdb;
		if ( isset( $attempt_ids ) ) {
			//phpcs:disable
			$wpdb->query( "DELETE FROM {$wpdb->prefix}tutor_h5p_quiz_result WHERE attempt_id IN($attempt_ids)" );
			//phpcs:enable
		}
	}

	/**
	 * Delete all quiz statements for a given quiz id.
	 *
	 * @since 3.0.0
	 *
	 * @param int $quiz_id the quiz id.
	 * @return void
	 */
	public function delete_h5p_quiz_info_all( $quiz_id ) {
		Utils::delete_h5p_quiz_statements_by_id( $quiz_id );
	}

	/**
	 * Delete all question statements for a given list of question ids.
	 *
	 * @since 3.0.0
	 *
	 * @param array $question_ids the list of question ids.
	 * @return void
	 */
	public function delete_h5p_question_info_all( $question_ids ) {
		Utils::delete_h5p_quiz_statements_by_id( 0, $question_ids );
	}

	/**
	 * Show result of tutor H5P quiz statements in modal
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function view_h5p_quiz_result() {
		global $wpdb;

		tutor_utils()->checking_nonce();

		$quiz_id          = Input::post( 'quiz_id', 0, INPUT::TYPE_INT );
		$user_id          = Input::post( 'user_id', 0, INPUT::TYPE_INT );
		$question_id      = Input::post( 'question_id', 0, INPUT::TYPE_INT );
		$content_id       = Input::post( 'content_id', 0, INPUT::TYPE_INT );
		$attempt_id       = Input::post( 'attempt_id', 0, INPUT::TYPE_INT );
		$content          = Utils::addon_config()->h5p_plugin->get_content( $content_id );
		$content_settings = Utils::addon_config()->h5p_plugin->get_content_settings( $content );
		$main_content     = isset( json_decode( $content_settings['jsonContent'] )->content ) ? json_decode( $content_settings['jsonContent'] )->content : null;
		$essay_keywords   = isset( json_decode( $content_settings['jsonContent'] )->keywords ) ? json_decode( $content_settings['jsonContent'] )->keywords : null;

		$results = Utils::get_h5p_quiz_results( $question_id, $user_id, $attempt_id, $quiz_id, $content_id );

		$result_ids    = array();
		$in_clause_ids = '';

		if ( is_array( $results ) && count( $results ) ) {
			foreach ( $results as $result ) {
				array_push( $result_ids, $result->result_id );
			}
		}

		$in_clause = QueryHelper::prepare_in_clause( $result_ids );

		$h5p_quiz_result_statements = $wpdb->get_results(
			//phpcs:disable
			"SELECT * FROM {$wpdb->prefix}tutor_h5p_quiz_statement WHERE quiz_result_id IN ({$in_clause})"
			//phpcs:enable
		);

		if ( is_array( $h5p_quiz_result_statements ) && count( $h5p_quiz_result_statements ) > 1 ) {
			usort(
				$h5p_quiz_result_statements,
				function ( $result_1, $result_2 ) {
					return $result_2->result_max_score - $result_1->result_max_score;
				}
			);
		}

		ob_start();
		include_once Utils::addon_config()->path . 'views/modals/h5p-quiz-result-modal.php';
		$output = ob_get_clean();

		wp_send_json_success( array( 'output' => $output ) );
	}
}
