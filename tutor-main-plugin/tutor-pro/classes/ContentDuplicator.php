<?php
/**
 * Handle Content Duplicate
 *
 * @author themeum
 * @link https://themeum.com
 * @package TutorPro
 * @since 3.0.0
 */

namespace TUTOR_PRO;

use Tutor\Helpers\HttpHelper;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;
use Tutor\Models\QuizModel;
use Tutor\Traits\JsonResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContentDuplicator
 *
 * @since 3.0.0
 */
class ContentDuplicator {
	use JsonResponse;

	/**
	 * Question table name.
	 *
	 * @var string
	 */
	private $question_table;

	/**
	 * Answer table name.
	 *
	 * @var string
	 */
	private $answer_table;

	/**
	 * Register hooks
	 *
	 * @since 3.0.0
	 *
	 * @param boolean $register_hooks register hooks.
	 *
	 * @return void
	 */
	public function __construct( $register_hooks = true ) {
		global $wpdb;

		$this->question_table = $wpdb->prefix . 'tutor_quiz_questions';
		$this->answer_table   = $wpdb->prefix . 'tutor_quiz_question_answers';

		if ( ! $register_hooks ) {
			return;
		}

		add_action( 'wp_ajax_tutor_duplicate_content', array( $this, 'ajax_duplicate_content' ) );
	}

	/**
	 * Duplicate course content.
	 *
	 * @return void
	 */
	public function ajax_duplicate_content() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->json_response( tutor_utils()->error_message( 'nonce' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$course_id    = Input::post( 'course_id', 0, Input::TYPE_INT );
		$content_type = Input::post( 'content_type' );
		$content_id   = Input::post( 'content_id', 0, Input::TYPE_INT );

		$new_id        = 0;
		$content_types = array( 'lesson', 'assignment', 'question', 'answer', 'quiz', 'topic' );

		if ( ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
			$this->json_response( tutor_utils()->error_message(), null, HttpHelper::STATUS_FORBIDDEN );
		}

		if ( ! in_array( $content_type, $content_types, true ) ) {
			$this->json_response( __( 'Invalid content type', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		if ( 'lesson' === $content_type ) {
			$new_id = $this->duplicate_lesson( $content_id );
		}

		if ( 'assignment' === $content_type ) {
			$new_id = $this->duplicate_assignment( $content_id );
		}

		if ( 'question' === $content_type ) {
			$question = QuizModel::get_question( $content_id );
			if ( $question ) {
				$new_id = $this->duplicate_quiz_question( $content_id, $question->quiz_id );
			}
		}

		if ( 'answer' === $content_type ) {
			$new_id = $this->duplicate_question_answer( $content_id );
		}

		if ( 'quiz' === $content_type ) {
			$post = get_post( $content_id );
			if ( tutor()->quiz_post_type === $post->post_type ) {
				$new_id = $this->duplicate_quiz( $content_id, $post->post_parent );
			}
		}

		if ( 'topic' === $content_type ) {
			$new_id = $this->duplicate_topic( $content_id );
		}

		if ( $new_id ) {
			$this->json_response( __( 'Duplicated successfully', 'tutor-pro' ), $new_id );
		} else {
			$this->json_response( __( 'Duplication failed', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}
	}


	/**
	 * Duplicate post meta.
	 *
	 * @param int $post_id post id.
	 * @param int $new_post_id new post id.
	 *
	 * @return void
	 */
	public function duplicate_post_meta( $post_id, $new_post_id ) {
		$post_meta = get_post_meta( $post_id );

		foreach ( $post_meta as $key => $values ) {
			foreach ( $values as $value ) {
				add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
			}
		}
	}

	/**
	 * Duplicate post with meta data
	 *
	 * @since 3.0.0
	 *
	 * @param int     $post_id post id.
	 * @param boolean $with_meta with post meta or not.
	 * @param bool    $add_copy_suffix add copy suffix or not.
	 * @param array   $overrides an assoc array to override existing data during duplicate.
	 *                like: [ 'post_parent' => 123, 'post_author' => 1 ].
	 *
	 * @return false|int false on failure, post id on success.
	 */
	private function duplicate_post( $post_id, $with_meta = true, $add_copy_suffix = true, $overrides = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$new_data = $post->to_array();
		unset( $new_data['ID'] );
		$new_data['post_title'] = $post->post_title . ( $add_copy_suffix ? ' ' . __( '(copy)', 'tutor-pro' ) : '' );

		foreach ( $overrides as $key => $value ) {
			$new_data[ $key ] = $value;
		}

		$new_post_id = wp_insert_post( $new_data );

		if ( $with_meta ) {
			$this->duplicate_post_meta( $post_id, $new_post_id );
		}

		return $new_post_id;
	}


	/**
	 * Duplicate a lesson.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id id.
	 *
	 * @return false|int false on failure or lesson id if successful.
	 */
	public function duplicate_lesson( $id ) {
		$post = get_post( $id );
		if ( tutor()->lesson_post_type !== $post->post_type ) {
			return false;
		}

		return $this->duplicate_post( $id );
	}

	/**
	 * Duplicate a assignment.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id id.
	 *
	 * @return false|int false on failure or lesson id if successful.
	 */
	public function duplicate_assignment( $id ) {
		$post = get_post( $id );
		if ( tutor()->assignment_post_type !== $post->post_type ) {
			return false;
		}

		return $this->duplicate_post( $id );
	}

	/**
	 * Duplicate a question answer.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $answer_id answer id.
	 * @param bool $copy_suffix add title copy suffix or not.
	 *
	 * @return false|int false on failure or answer id if successful.
	 */
	public function duplicate_question_answer( $answer_id, $copy_suffix = true ) {
		global $wpdb;

		$ans_row = QueryHelper::get_row( $this->answer_table, array( 'answer_id' => $answer_id ), 'answer_id' );
		if ( ! $ans_row ) {
			return false;
		}

		$answer_data                 = (array) $ans_row;
		$answer_data['answer_title'] = $ans_row->answer_title . ( $copy_suffix ? ' (copy)' : '' );
		$answer_data['is_correct']   = 0;
		unset( $answer_data['answer_id'] );

		$wpdb->insert( $this->answer_table, $answer_data );
		$new_answer_id = $wpdb->insert_id;

		return $new_answer_id;
	}

	/**
	 * Duplicate quiz question.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $question_id question id.
	 * @param int  $quiz_id assign to quiz id.
	 * @param bool $copy_suffix title copy suffix.
	 * @param bool $db_transaction use db transaction or not.
	 *
	 * @return false|int false on failure or quiz id if successful.
	 */
	public function duplicate_quiz_question( $question_id, $quiz_id, $copy_suffix = true, $db_transaction = true ) {
		global $wpdb;

		try {
			if ( $db_transaction ) {
				$wpdb->query( 'START TRANSACTION' );
			}

			$old_question = QuizModel::get_question( $question_id );
			$old_answers  = QuizModel::get_question_answers( $old_question->question_id, $old_question->question_type );

			$new_question_data                   = (array) $old_question;
			$new_question_data['quiz_id']        = $quiz_id;
			$new_question_data['question_title'] = $old_question->question_title . ( $copy_suffix ? ' (copy)' : '' );
			unset( $new_question_data['question_id'] );

			$wpdb->insert( $this->question_table, $new_question_data );
			$new_question_id = $wpdb->insert_id;

			foreach ( $old_answers as $ans_row ) {
				$answer_data = (array) $ans_row;

				if ( isset( $answer_data['image_url'] ) ) {
					unset( $answer_data['image_url'] );
				}

				$answer_data['belongs_question_id'] = $new_question_id;
				unset( $answer_data['answer_id'] );

				$wpdb->insert( $this->answer_table, $answer_data );
			}

			if ( $db_transaction ) {
				$wpdb->query( 'COMMIT' );
			}
		} catch ( \Throwable $th ) {
			if ( $db_transaction ) {
				$wpdb->query( 'ROLLBACK' );
			}

			$new_question_id = false;
		}

		return $new_question_id;
	}

	/**
	 * Duplicate quiz.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $id quiz id.
	 * @param int  $topic_id topic id.
	 * @param bool $add_copy_suffix add suffix or not.
	 * @param bool $db_transaction use db transaction or not.
	 *
	 * @return false|int false on failure or quiz id if successful.
	 */
	public function duplicate_quiz( $id, $topic_id, $add_copy_suffix = true, $db_transaction = true ) {
		$post = get_post( $id );
		if ( tutor()->quiz_post_type !== $post->post_type ) {
			return false;
		}

		global $wpdb;

		try {
			if ( $db_transaction ) {
				$wpdb->query( 'START TRANSACTION' );
			}

			$new_quiz_id = $this->duplicate_post( $id, true, $add_copy_suffix, array( 'post_parent' => $topic_id ) );
			$questions   = QueryHelper::get_all( $this->question_table, array( 'quiz_id' => $id ), 'question_id' );
			foreach ( $questions as $question_row ) {
				$this->duplicate_quiz_question( $question_row->question_id, $new_quiz_id, false, false );
			}

			if ( $db_transaction ) {
				$wpdb->query( 'COMMIT' );
			}

			return $new_quiz_id;
		} catch ( \Throwable $th ) {
			if ( $db_transaction ) {
				$wpdb->query( 'ROLLBACK' );
			}

			return false;
		}

	}

	/**
	 * Duplicate a topic and it's content like lesson, assignment, quiz etc.
	 *
	 * @since 3.0.0
	 *
	 * @param int $topic_id topic id.
	 *
	 * @return false|int false on failure or topic id if successful.
	 */
	public function duplicate_topic( $topic_id ) {
		$post = get_post( $topic_id );
		if ( tutor()->topics_post_type !== $post->post_type ) {
			return false;
		}

		$contents = tutor_utils()->get_course_contents_by_topic( $topic_id, -1 );

		global $wpdb;

		try {
			$wpdb->query( 'START TRANSACTION' );

			$new_topic_id = $this->duplicate_post( $topic_id );

			foreach ( $contents->posts as $content ) {
				// Lesson assignment duplicate.
				if ( in_array(
					$content->post_type,
					array( tutor()->lesson_post_type, tutor()->assignment_post_type ),
					true
				) ) {
					$this->duplicate_post( $content->ID, true, false, array( 'post_parent' => $new_topic_id ) );
				}

				// Quiz duplicate.
				if ( tutor()->quiz_post_type === $content->post_type ) {
					$this->duplicate_quiz( $content->ID, $new_topic_id, false, false );
				}
			}

			$wpdb->query( 'COMMIT' );
			return $new_topic_id;
		} catch ( \Throwable $th ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

	}
}
