<?php
/**
 * Quiz class for PRO user.
 *
 * @package TutorPro\Quiz
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TUTOR_PRO;

use TUTOR\Input;
use TUTOR\Icon;
use Tutor\Helpers\QueryHelper;
use Tutor\Models\QuizModel;
use Tutor\Options_V2;
use Tutor\Traits\JsonResponse;
use Tutor\Components\SvgIcon;
use Tutor\Components\Badge;
use TutorPro\Models\QuizModel as ProQuizModel;

/**
 * Class Quiz
 *
 * @since 2.2.0
 */
class Quiz {
	use JsonResponse;

	/**
	 * Register hooks
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'tutor_quiz_question_data', array( $this, 'extend_question_data' ), 10, 2 );
		add_action( 'tutor_quiz_attempt_details_loop_after_row', array( $this, 'correct_answer_explanation_content' ), 10, 3 );
		add_action( 'tutor_quiz_attempt_details_mark_breakdown', array( $this, 'render_attempt_answer_mark_breakdown' ), 10, 3 );
		add_action( 'tutor_quiz_attempt_details_after_result', array( $this, 'display_answer_explanation_button' ), 10, 2 );
		add_action( 'tutor_quiz_question_desc_field', array( $this, 'add_wp_editor_to_quiz_question_desc' ) );
		add_action( 'tutor_quiz_question_desc_render', array( $this, 'render_question_description' ), 10, 2 );
		add_action( 'tutor_quiz_question_after_answers', array( $this, 'render_question_answer_explanation' ), 10, 3 );
		add_filter( 'tutor_filter_unsupported_quiz_question_types', array( $this, 'unsupported_quiz_question_types' ) );

		// Draw Image, Scale, and Pin Image question types (automatic grading) integration.
		add_action( 'tutor_require_question_answer_file', array( $this, 'render_draw_image_question' ), 10, 3 );
		add_action( 'tutor_require_question_answer_file', array( $this, 'render_pin_image_question' ), 10, 3 );
		add_action( 'tutor_quiz_attempt_details_after_question_template', array( $this, 'render_pro_attempt_details_inside_question_wrapper' ), 10, 3 );
		add_filter( 'tutor_filter_quiz_answer_data', array( $this, 'grade_draw_image_question' ), 10, 5 );
		add_action( 'tutor_require_question_answer_file', array( $this, 'render_scale_question' ), 10, 3 );
		add_action( 'tutor_require_question_answer_file', array( $this, 'render_coordinates_question' ), 10, 3 );
		add_action( 'tutor_require_question_answer_file', array( $this, 'render_puzzle_question' ), 10, 3 );
		add_filter( 'tutor_filter_quiz_answer_data', array( $this, 'grade_scale_question' ), 10, 5 );
		add_filter( 'tutor_filter_quiz_answer_data', array( $this, 'grade_pin_image_question' ), 10, 5 );
		add_filter( 'tutor_filter_quiz_answer_data', array( $this, 'grade_coordinates_question' ), 10, 5 );
		add_filter( 'tutor_filter_quiz_answer_data', array( $this, 'grade_puzzle_question' ), 10, 5 );

		add_action( 'tutor_before_course_builder_load', array( $this, 'enqueue_draw_image_question_script' ) );
		add_action( 'tutor_before_course_builder_load', array( $this, 'enqueue_pin_image_question_script' ) );
		add_action( 'tutor_enqueue_pin_image_question_script', array( $this, 'enqueue_pin_image_question_script' ) );
		add_action( 'tutor_enqueue_draw_image_question_script', array( $this, 'enqueue_draw_image_question_script' ) );

		// Allow the new learning-area frontend to enqueue the scale script.
		add_action( 'tutor_enqueue_scale_question_script', array( $this, 'enqueue_scale_question_script' ) );
		add_action( 'tutor_enqueue_coordinates_question_script', array( $this, 'enqueue_coordinates_question_script' ) );
		add_action( 'tutor_enqueue_puzzle_question_script', array( $this, 'enqueue_puzzle_question_script' ) );

		// Register custom question-type file handling filters (e.g. draw_image).
		add_filter( 'tutor_save_quiz_draw_image_mask', array( ProQuizModel::class, 'save_quiz_draw_image_mask' ), 10, 3 );
		add_filter( 'tutor_quiz/attempt_file_paths_for_deletion', array( ProQuizModel::class, 'add_draw_image_attempt_file_paths' ), 10, 2 );
		add_filter( 'tutor_quiz_quiz_file_paths_for_deletion', array( ProQuizModel::class, 'add_quiz_file_paths_for_deletion' ), 10, 2 );

		// Handle custom question-type answer processing and total marks adjustment.
		add_filter( 'tutor_quiz_process_custom_question_answer', array( $this, 'process_custom_question_answer' ), 10, 6 );
		add_filter( 'tutor_filter_quiz_total_marks', array( $this, 'adjust_custom_question_total_marks' ), 10, 6 );

		// Partial and negative scoring for multi-item auto-graded questions.
		add_filter( 'tutor_filter_quiz_answer_data', array( $this, 'grade_partial_negative_answer' ), 10, 5 );
		add_filter( 'tutor_filter_quiz_total_marks', array( $this, 'adjust_partial_negative_total_marks' ), 10, 3 );

		add_filter( 'tutor/options/attr', array( $this, 'add_grading_options' ), 20 );
		add_action( 'tutor_option_save_before', array( $this, 'validate_negative_mark_settings' ) );
		add_filter( 'tutor_quiz_default_settings', array( $this, 'add_quiz_grading_defaults' ) );
		add_filter( 'tutor_quiz_settings', array( $this, 'filter_quiz_settings' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_turnoff_confirmations' ) );
		add_filter( 'tutor_quiz_summary_parameters', array( $this, 'add_quiz_summary_parameters' ), 10, 2 );
		add_action( 'tutor_quiz/single/info', array( $this, 'render_marking_info' ), 10, 1 );
		add_filter( 'tutor_quiz_question_answers', array( $this, 'expand_stored_mask_values_to_urls' ), 10, 3 );

		// Pro answer status, badges, and breakdown hooks.
		add_filter( 'tutor_quiz_attempt_answer_status', array( $this, 'filter_attempt_answer_status' ), 10, 2 );
		add_filter( 'tutor_quiz_attempt_answer_badge', array( $this, 'filter_attempt_answer_badge' ), 10, 2 );
		add_filter( 'tutor_quiz_attempt_answer_counts', array( $this, 'filter_attempt_answer_counts' ), 10, 2 );
		add_filter( 'tutor_quiz_attempt_summary_statics', array( $this, 'filter_attempt_summary_partial_stat' ), 10, 3 );
		add_action( 'tutor_quiz_attempt_details_result_badge_after', array( $this, 'render_legacy_attempt_result_delta' ), 10, 3 );
		add_filter( 'tutor_quiz_review_answer_marks', array( $this, 'filter_quiz_review_answer_marks' ), 10, 5 );
		add_filter( 'tutor_course_builder_localized_data', array( $this, 'extend_course_builder_quiz_options' ) );
		add_filter( 'tutor_quiz_questions_sidebar_status_priority', array( $this, 'filter_questions_sidebar_status_priority' ) );

		// Handle custom question-type display in attempt details.
		add_action( 'tutor_quiz_render_given_answer_for_question_type', array( $this, 'render_custom_question_given_answer' ), 10, 1 );
		add_action( 'tutor_quiz_render_correct_answer_for_question_type', array( $this, 'render_custom_question_correct_answer' ), 10, 1 );

		// Ensure final attempt earned marks floor at zero when negative marking applies.
		add_action( 'tutor_quiz/attempt_ended', array( $this, 'clamp_attempt_earned_marks' ) );
	}

	/**
	 * Expand stored quiz mask values (basename / uploads-relative) to full URLs for REST/course builder.
	 *
	 * @since 4.0.0
	 *
	 * @param array       $answers        Answer rows.
	 * @param int         $question_id    Question id.
	 * @param string|null $question_type  Question type when known.
	 *
	 * @return array
	 */
	public function expand_stored_mask_values_to_urls( $answers, $question_id, $question_type ) {
		unset( $question_id );

		foreach ( $answers as $answer ) {
			$type = $question_type ? $question_type : ( isset( $answer->belongs_question_type ) ? $answer->belongs_question_type : '' );
			if ( ! in_array( $type, array( QuizModel::QUESTION_TYPE_DRAW_IMAGE, QuizModel::QUESTION_TYPE_PIN_IMAGE, QuizModel::QUESTION_TYPE_PUZZLE ), true ) ) {
				continue;
			}
			if ( empty( $answer->answer_two_gap_match ) ) {
				continue;
			}
			$expanded = QuizImageStorage::quiz_image_stored_value_to_url( $answer->answer_two_gap_match );
			if ( '' !== $expanded ) {
				$answer->answer_two_gap_match = $expanded;
			}
		}

		return $answers;
	}

	/**
	 * Add WP editor support to quiz question description.
	 *
	 * @since 2.2.3
	 *
	 * @param object $question_obj question object.
	 *
	 * @return void
	 */
	public function add_wp_editor_to_quiz_question_desc( $question_obj ) {
		wp_editor(
			$question_obj->question_description,
			'tutor_quiz_desc_text_editor',
			array( 'editor_height' => 150 )
		);
	}

	/**
	 * Render question description data.
	 *
	 * @since 2.2.3
	 *
	 * @param string      $description description with HTML markup.
	 *
	 * @param object|null $question question object.
	 *
	 * @return void
	 */
	public function render_question_description( $description, $question = null ) {
		add_filter( 'wp_kses_allowed_html', Input::class . '::allow_iframe', 10, 2 );
		echo do_shortcode( wp_kses_post( $description ) );
	}

	/**
	 * Add data to question during add/edit quiz question.
	 *
	 * @since 2.2.0
	 * @since 3.0.0 $input param added.
	 *
	 * @param array $data question data.
	 * @param array $input input data.
	 *
	 * @return array question data.
	 */
	public function extend_question_data( $data, $input ) {
		$data['answer_explanation'] = '';

		if ( isset( $input['answer_explanation'] ) ) {
			$explanation = Input::sanitize( wp_slash( $input['answer_explanation'] ), '', Input::TYPE_KSES_POST );
			if ( '<p><br></p>' === $explanation ) {
				$explanation = '';
			}

			$data['answer_explanation'] = $explanation;
		}

		/**
		 * WP editor support to Quiz question
		 *
		 * @since 2.2.3
		 */
		add_filter( 'wp_kses_allowed_html', Input::class . '::allow_iframe', 10, 2 );
		$data['question_description'] = Input::sanitize( wp_slash( $input['question_description'] ) ?? '', '', Input::TYPE_KSES_POST );
		remove_filter( 'wp_kses_allowed_html', Input::class . '::allow_iframe' );

		return $data;
	}

	/**
	 * Correct answer explanation content.
	 *
	 * @since 2.2.0
	 *
	 * @param object $answer answer object.
	 * @param string $answer_status answer status.
	 * @param array  $table_columns table columns.
	 *
	 * @return void
	 */
	public function correct_answer_explanation_content( $answer, $answer_status, $table_columns ) {
		$answer_explanation = isset( $answer->answer_explanation ) ? (string) $answer->answer_explanation : '';
		$trimmed_answer     = trim( wp_strip_all_tags( $answer_explanation ) );
		$is_image_answer    = preg_match( '/^(<p><img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*><\/p>)/i', $answer_explanation );

		if ( ( strlen( $trimmed_answer ) <= 0 && ! $is_image_answer ) || 'pending' === $answer_status ) {
			return;
		}

		$legacy_mode           = Options_V2::LEARNING_MODE_LEGACY === tutor_utils()->get_option( 'learning_mode' );
		$is_learning_area      = tutor_utils()->is_learning_area();
		$is_frontend_dashboard = tutor_utils()->is_dashboard_page();
		$use_modern_template   = $is_frontend_dashboard || ( $is_learning_area && ! $legacy_mode );
		if ( ! is_admin() && $use_modern_template ) {
			tutor_load_template(
				'shared.components.answer-explanation',
				array(
					'answer_explanation' => $answer_explanation,
					'question_id'        => (int) ( $answer->question_id ?? 0 ),
				),
				true
			);

			return;
		}
		?>
		<tr>
			<td colspan="<?php echo esc_attr( count( $table_columns ) ); ?>" class="column-empty-state data-td-content" id="tutor-question-<?php echo esc_attr( $answer->question_id ); ?>" style="display:none;">
				<div class="tutor-quiz-explanation-wrapper">
					<div class="tutor-d-flex tutor-gap-1 tutor-align-center tutor-mb-12">
						<svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M14.8113 10.9114C14.5746 12.8822 13.2838 13.6697 12.7263 14.2414C12.1679 14.8147 12.268 14.9289 12.3055 15.7697C12.3117 15.8738 12.2966 15.978 12.261 16.0759C12.2255 16.1739 12.1703 16.2636 12.0987 16.3394C12.0272 16.4152 11.941 16.4755 11.8452 16.5167C11.7495 16.5579 11.6463 16.5791 11.5421 16.5789H9.32712C9.22297 16.5787 9.11995 16.5573 9.02434 16.516C8.92874 16.4747 8.84255 16.4143 8.77104 16.3386C8.69954 16.2629 8.64421 16.1734 8.60843 16.0756C8.57265 15.9778 8.55718 15.8737 8.56295 15.7697C8.56295 14.9447 8.63962 14.7531 8.14295 14.2414C7.50962 13.6081 6.01962 12.7139 6.01962 10.2547C6.01581 9.64389 6.13859 9.03889 6.38021 8.47787C6.62183 7.91684 6.97705 7.41195 7.42349 6.99502C7.86992 6.57809 8.39789 6.25817 8.9741 6.05541C9.55032 5.85266 10.1623 5.77147 10.7714 5.81697C11.3806 5.86247 11.9737 6.03367 12.5134 6.31977C13.0531 6.60587 13.5277 7.00068 13.9072 7.4793C14.2868 7.95793 14.5631 8.50998 14.7187 9.10068C14.8742 9.69138 14.9058 10.3079 14.8113 10.9114Z" stroke="#4B505C" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M9.15104 16.5335V17.7935C9.15104 18.1377 9.34271 18.4202 9.57854 18.4202H11.2894C11.5269 18.4202 11.7177 18.1369 11.7177 17.7935V16.5335M9.78438 7.75021C9.29232 7.75043 8.8205 7.94605 8.47264 8.29406C8.12479 8.64208 7.92938 9.11399 7.92938 9.60604M16.5444 10.4235H18.0727M15.006 5.41354L16.0902 4.32938M15.8394 14.6702L16.9235 15.7535M10.4344 2.48438V3.72021M4.80104 4.32854L5.87771 5.41354M3.96771 15.7535L5.04437 14.6702M4.32438 10.4235H2.79688" stroke="#4B505C" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<div class="tutor-fw-medium"><?php esc_html_e( 'Answer Explanation', 'tutor-pro' ); ?></div>
					</div>
					<div class="tutor-overflow-hidden">							
						<?php echo wp_kses_post( wp_unslash( $answer_explanation ) ); ?>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render question attempt mark breakdown (student penalty notice or instructor breakdown table).
	 *
	 * @since 4.1.0
	 *
	 * @param object $answer               Question/answer object.
	 * @param string $answer_status        Evaluated answer status.
	 * @param bool   $is_instructor_review Whether current context is instructor review.
	 *
	 * @return void
	 */
	public function render_attempt_answer_mark_breakdown( $answer, $answer_status, $is_instructor_review = false ) {
		$is_instructor_review = ! empty( $is_instructor_review );

		$attempt_id   = (int) ( $answer->quiz_attempt_id ?? ( $answer->attempt_id ?? 0 ) );
		$attempt      = $attempt_id > 0 ? tutor_utils()->get_attempt( $attempt_id ) : null;
		$attempt_info = $attempt ? maybe_unserialize( $attempt->attempt_info ?? '' ) : array();

		if ( ! QuizGrader::has_partial_or_negative_marking( $attempt_info ) ) {
			return;
		}

		$is_evaluated_auto_status = in_array( $answer_status, array( 'correct', 'incorrect', 'partial', 'skipped' ), true );
		if ( ! $is_evaluated_auto_status ) {
			return;
		}

		if ( ! is_object( $answer ) || empty( $answer->question_type ) || in_array( $answer->question_type, QuizModel::get_manual_review_types(), true ) ) {
			return;
		}

		// In legacy admin table, mark breakdown is rendered directly inside the Result column.
		if ( is_admin() ) {
			return;
		}

		$minus_val = (float) ( $answer->minus_mark ?? 0 );

		$achieved_val = (float) ( $answer->achieved_mark ?? 0 );
		$earned_val   = round( $achieved_val + $minus_val, 2 );
		$qmark_val    = (float) ( $answer->question_mark ?? 0 );

		$format_num = static function ( float $num ): string {
			return ( floor( $num ) === $num ) ? (string) (int) $num : (string) round( $num, 2 );
		};

		$achieved_formatted = $format_num( $achieved_val );
		$minus_formatted    = $format_num( $minus_val );
		$earned_formatted   = $format_num( $earned_val );
		$total_formatted    = $format_num( $qmark_val );

		if ( ! $is_instructor_review ) {
			if ( $minus_val <= 0 ) {
				return;
			}
			?>
			<div class="tutor-quiz-student-penalty-notice">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: penalty points deducted */
						__( '-%s points', 'tutor-pro' ),
						$minus_formatted
					)
				);
				?>
			</div>
			<?php
			return;
		}

		if ( 'correct' === $answer_status ) {
			$score_label = __( 'Full Marks', 'tutor-pro' );
		} elseif ( 'skipped' === $answer_status ) {
			$score_label = __( 'Not answered', 'tutor-pro' );
		} else {
			$score_label = $earned_formatted;
		}
		$row_class = 'tutor-quiz-instructor-breakdown-row';
		if ( 'partial' === $answer_status ) {
			$row_class .= ' is-partial';
		}
		?>
		<div class="tutor-quiz-instructor-score-breakdown">
			<div class="<?php echo esc_attr( $row_class ); ?>">
				<span>
					<?php if ( $minus_val > 0 ) : ?>
						<span><?php echo esc_html( $earned_formatted ); ?></span>
						<span class="tutor-quiz-instructor-penalty-mark"><?php echo esc_html( ' - ' . $minus_formatted ); ?></span>
					<?php else : ?>
						<?php echo esc_html( $score_label ); ?>
					<?php endif; ?>
				</span>
				<span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: scored mark, 2: total question mark */
							__( 'Score: %1$s/%2$s', 'tutor-pro' ),
							$achieved_formatted,
							$total_formatted
						)
					);
					?>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Filter attempt answer status for partial marking in Pro.
	 *
	 * @since 4.1.0
	 *
	 * @param string      $status         Evaluated answer status.
	 * @param object|null $attempt_answer Attempt answer object.
	 *
	 * @return string
	 */
	public function filter_attempt_answer_status( $status, $attempt_answer ) {
		if ( ! is_object( $attempt_answer ) ) {
			return $status;
		}

		$question_type = (string) ( $attempt_answer->question_type ?? '' );
		if ( in_array( $question_type, QuizModel::get_manual_review_types(), true ) ) {
			return $status;
		}

		if ( ProQuizModel::ATTEMPT_ANSWER_PARTIAL === (int) ( $attempt_answer->is_correct ?? null ) ) {
			return 'partial';
		}

		return $status;
	}

	/**
	 * Filter attempt answer badge for partial marking in Pro.
	 *
	 * @since 4.1.0
	 *
	 * @param array       $badge          Badge metadata array.
	 * @param object|null $attempt_answer Attempt answer object.
	 *
	 * @return array
	 */
	public function filter_attempt_answer_badge( $badge, $attempt_answer ) {
		$counts        = $attempt_answer ? QuizGrader::get_attempt_answer_correct_counts( $attempt_answer ) : null;
		$partial_label = __( 'Partially Correct', 'tutor-pro' );

		if ( $counts ) {
			$correct       = (int) ( $counts['correct'] ?? 0 );
			$total         = (int) ( $counts['total'] ?? 0 );
			$incorrect     = (int) ( $counts['incorrect'] ?? 0 );

			if ( 0 < $incorrect && ( $correct === $total ) ) {
				$partial_label = __( 'Partially Correct', 'tutor-pro' );
			} else {
				$partial_label = sprintf(
					/* translators: 1: correct count, 2: total correct count. */
					__( '%1$d/%2$d correct', 'tutor-pro' ),
					$correct,
					$total
				);
			}
		}

		$badge['label_map']['partial']   = $partial_label;
		$badge['variant_map']['partial'] = Badge::SUCCESS;
		$badge['class_map']['partial']   = 'label-success';

		if ( 'partial' === ( $badge['status'] ?? '' ) ) {
			$badge['label']   = $partial_label;
			$badge['variant'] = Badge::SUCCESS;
			$badge['class']   = 'label-success';
		}

		return $badge;
	}

	/**
	 * Filter attempt answer counts to include partial status in Pro.
	 *
	 * @since 4.1.0
	 *
	 * @param array      $counts  Associative counts array.
	 * @param array|null $answers Answer objects.
	 *
	 * @return array
	 */
	public function filter_attempt_answer_counts( $counts, $answers ) {
		$counts['partial'] = 0;
		if ( is_array( $answers ) ) {
			foreach ( $answers as $answer ) {
				$status = QuizModel::get_attempt_answer_status( $answer );
				if ( 'partial' === $status ) {
					++$counts['partial'];
				}
			}
		}
		return $counts;
	}

	/**
	 * Add partial marking stat item to the attempt summary statics.
	 *
	 * @since 4.1.0
	 *
	 * @param array  $items        Ordered associative array of `key => item` pairs.
	 * @param object $attempt_data Attempt data object.
	 * @param array  $answers      Attempt answers list.
	 *
	 * @return array
	 */
	public function filter_attempt_summary_partial_stat( $items, $attempt_data, $answers ) {
		if ( ! is_object( $attempt_data ) ) {
			return $items;
		}

		$attempt_info = maybe_unserialize( $attempt_data->attempt_info ?? '' );
		$attempt_info = is_array( $attempt_info ) ? $attempt_info : array();
		if ( empty( $attempt_info['enable_partial_marking'] ) || '1' !== (string) $attempt_info['enable_partial_marking'] ) {
			return $items;
		}

		$answer_counts = QuizModel::get_attempt_answer_counts( $answers );
		$partial       = (int) ( $answer_counts['partial'] ?? 0 );

		$formatted_count = '<span class="tutor-font-semibold tutor-text-primary">' . $partial . '</span>';

		$items = array_merge(
			array(
				'partial' => array(
					'class' => 'partial',
					/* translators: %1$s: number of partially graded answers, wrapped in markup. */
					'label' => sprintf( __( '%1$s partial', 'tutor-pro' ), $formatted_count ),
					'count' => $partial,
				),
			),
			$items
		);

		return $items;
	}

	/**
	 * Render penalty delta in legacy attempt details result column.
	 *
	 * @since 4.1.0
	 *
	 * @param object $answer               Answer object.
	 * @param string $answer_status        Answer status.
	 * @param bool   $is_instructor_review Whether the reviewer is an instructor.
	 *
	 * @return void
	 */
	public function render_legacy_attempt_result_delta( $answer, $answer_status, $is_instructor_review = false ) {
		if ( ! is_object( $answer ) ) {
			return;
		}

		$minus_val = (float) ( $answer->minus_mark ?? 0 );
		if ( $minus_val <= 0 || ! in_array( $answer_status, array( 'partial', 'incorrect' ), true ) ) {
			return;
		}

		$achieved_val = (float) ( $answer->achieved_mark ?? 0 );
		$earned_val   = round( $achieved_val + $minus_val, 2 );
		$earned_str   = number_format( $earned_val, 2, '.', '' );
		$minus_str    = number_format( $minus_val, 2, '.', '' );
		?>
		<div class="tutor-quiz-result-delta">
			<?php if ( ! empty( $is_instructor_review ) && $earned_val > 0 ) : ?>
				<span class="is-earned">(+<?php echo esc_html( $earned_str ); ?>)</span>
			<?php endif; ?>
			<span class="is-penalty">-<?php echo esc_html( $minus_str ); ?></span>
		</div>
		<?php
	}

	/**
	 * Filter quiz review answer marks to apply negative marking on incorrect override.
	 *
	 * @since 4.1.0
	 *
	 * @param array       $marks          Marks array with achieved_mark and minus_mark.
	 * @param string      $mark_as        Target review status (correct or incorrect).
	 * @param object|null $attempt_answer Attempt answer object.
	 * @param object|null $question       Question object.
	 * @param object|null $attempt        Attempt object.
	 *
	 * @return array
	 */
	public function filter_quiz_review_answer_marks( array $marks, string $mark_as, $attempt_answer, $question, $attempt ): array {
		return QuizGrader::calculate_review_marks( $marks, $mark_as, $attempt_answer, $question, $attempt );
	}

	/**
	 * Register Pro quiz settings in course builder localized data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data Localized data array for course builder.
	 *
	 * @return array
	 */
	public function extend_course_builder_quiz_options( array $data ): array {
		$pro_options = Options_V2::get_only(
			array(
				'enable_quiz_partial_marking',
				'enable_quiz_negative_marking',
				'quiz_negative_mark_type',
				'quiz_negative_mark_value',
			)
		);

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = array_merge( $data['settings'], $pro_options );
		}

		return $data;
	}

	/**
	 * Add partial status to questions sidebar status priority.
	 *
	 * @since 4.1.0
	 *
	 * @param array $priority Status priority map.
	 *
	 * @return array
	 */
	public function filter_questions_sidebar_status_priority( array $priority ): array {
		$priority['partial'] = 2;
		asort( $priority );

		return $priority;
	}

	/**
	 * Display answer explanation button
	 *
	 * @since 3.8.0
	 *
	 * @param object $answer answer object.
	 * @param string $answer_status answer status.
	 *
	 * @return void
	 */
	public function display_answer_explanation_button( $answer, $answer_status ) {
		$trimmed_answer  = trim( wp_strip_all_tags( $answer->answer_explanation ) );
		$is_image_answer = preg_match( '/^(<p><img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*><\/p>)/i', $answer->answer_explanation );
		if ( ( strlen( $trimmed_answer ) > 0 || $is_image_answer ) && 'pending' !== $answer_status ) :
			?>
			<div class="tooltip-wrap">
				<span class="tooltip-txt <?php echo esc_attr( is_rtl() ? 'tooltip-right' : 'tooltip-left' ); ?>"><?php esc_html_e( 'Show Explanation', 'tutor-pro', ); ?></span>
				<button class="tutor-quiz-explanation-display-button" data-td-target="tutor-question-<?php echo esc_attr( $answer->question_id ); ?>">
					<svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M14.8113 10.4114C14.5746 12.3822 13.2838 13.1697 12.7263 13.7414C12.1679 14.3147 12.268 14.4289 12.3055 15.2697C12.3117 15.3738 12.2966 15.478 12.261 15.5759C12.2255 15.6739 12.1703 15.7636 12.0987 15.8394C12.0272 15.9152 11.941 15.9755 11.8452 16.0167C11.7495 16.0579 11.6463 16.0791 11.5421 16.0789H9.32712C9.22297 16.0787 9.11995 16.0573 9.02434 16.016C8.92874 15.9747 8.84255 15.9143 8.77104 15.8386C8.69954 15.7629 8.64421 15.6734 8.60843 15.5756C8.57265 15.4778 8.55718 15.3737 8.56295 15.2697C8.56295 14.4447 8.63962 14.2531 8.14295 13.7414C7.50962 13.1081 6.01962 12.2139 6.01962 9.75473C6.01581 9.14389 6.13859 8.53889 6.38021 7.97787C6.62183 7.41684 6.97705 6.91195 7.42349 6.49502C7.86992 6.07809 8.39789 5.75817 8.9741 5.55541C9.55032 5.35266 10.1623 5.27147 10.7714 5.31697C11.3806 5.36247 11.9737 5.53367 12.5134 5.81977C13.0531 6.10587 13.5277 6.50068 13.9072 6.9793C14.2868 7.45793 14.5631 8.00998 14.7187 8.60068C14.8742 9.19138 14.9058 9.8079 14.8113 10.4114Z" stroke="#0049F8" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M9.15104 16.0335V17.2935C9.15104 17.6377 9.34271 17.9202 9.57854 17.9202H11.2894C11.5269 17.9202 11.7177 17.6369 11.7177 17.2935V16.0335M9.78438 7.25021C9.29232 7.25043 8.8205 7.44605 8.47264 7.79406C8.12479 8.14208 7.92938 8.61399 7.92938 9.10604M16.5444 9.92354H18.0727M15.006 4.91354L16.0902 3.82938M15.8394 14.1702L16.9235 15.2535M10.4344 1.98438V3.22021M4.80104 3.82854L5.87771 4.91354M3.96771 15.2535L5.04437 14.1702M4.32438 9.92354H2.79688" stroke="#0049F8" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
			<?php
		endif;
	}

	/**
	 * Render question answer explanation.
	 *
	 * @since 3.8.0
	 * @since 4.0.0 Added legacy mode check.
	 *
	 * @param object $quiz quiz object.
	 * @param array  $quiz_settings quiz settings.
	 * @param object $question question object.
	 *
	 * @return void
	 */
	public function render_question_answer_explanation( $quiz, $quiz_settings, $question ) {
		$is_reveal_mode = '1' === (string) tutor_utils()->array_get( 'enable_answer_reveal', $quiz_settings );

		if ( ! $is_reveal_mode || ! in_array( $question->question_type, array( 'true_false', 'multiple_choice', 'single_choice' ), true ) || empty( $question->answer_explanation ) ) {
			return;
		}

		$legacy_mode = Options_V2::LEARNING_MODE_LEGACY === tutor_utils()->get_option( 'learning_mode' );

		if ( ! $legacy_mode ) {
			$answer_explanation = $question->answer_explanation ?? '';
			$question_id        = $question->question_id ?? 0;
			tutor_load_template(
				'learning-area.quiz.answer-explanation',
				array(
					'answer_explanation' => $answer_explanation,
					'question_id'        => $question_id,
				),
				true
			);

			return;
		}
		?>
			<div class="tutor-quiz-explanation-wrapper tutor-d-none">
				<div class="tutor-d-flex tutor-gap-1 tutor-align-center tutor-mb-12">
					<svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M14.8113 10.9114C14.5746 12.8822 13.2838 13.6697 12.7263 14.2414C12.1679 14.8147 12.268 14.9289 12.3055 15.7697C12.3117 15.8738 12.2966 15.978 12.261 16.0759C12.2255 16.1739 12.1703 16.2636 12.0987 16.3394C12.0272 16.4152 11.941 16.4755 11.8452 16.5167C11.7495 16.5579 11.6463 16.5791 11.5421 16.5789H9.32712C9.22297 16.5787 9.11995 16.5573 9.02434 16.516C8.92874 16.4747 8.84255 16.4143 8.77104 16.3386C8.69954 16.2629 8.64421 16.1734 8.60843 16.0756C8.57265 15.9778 8.55718 15.8737 8.56295 15.7697C8.56295 14.9447 8.63962 14.7531 8.14295 14.2414C7.50962 13.6081 6.01962 12.7139 6.01962 10.2547C6.01581 9.64389 6.13859 9.03889 6.38021 8.47787C6.62183 7.91684 6.97705 7.41195 7.42349 6.99502C7.86992 6.57809 8.39789 6.25817 8.9741 6.05541C9.55032 5.85266 10.1623 5.77147 10.7714 5.81697C11.3806 5.86247 11.9737 6.03367 12.5134 6.31977C13.0531 6.60587 13.5277 7.00068 13.9072 7.4793C14.2868 7.95793 14.5631 8.50998 14.7187 9.10068C14.8742 9.69138 14.9058 10.3079 14.8113 10.9114Z" stroke="#4B505C" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M9.15104 16.5335V17.7935C9.15104 18.1377 9.34271 18.4202 9.57854 18.4202H11.2894C11.5269 18.4202 11.7177 18.1369 11.7177 17.7935V16.5335M9.78438 7.75021C9.29232 7.75043 8.8205 7.94605 8.47264 8.29406C8.12479 8.64208 7.92938 9.11399 7.92938 9.60604M16.5444 10.4235H18.0727M15.006 5.41354L16.0902 4.32938M15.8394 14.6702L16.9235 15.7535M10.4344 2.48438V3.72021M4.80104 4.32854L5.87771 5.41354M3.96771 15.7535L5.04437 14.6702M4.32438 10.4235H2.79688" stroke="#4B505C" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<div class="tutor-fw-medium"><?php esc_html_e( 'Answer Explanation', 'tutor-pro' ); ?></div>
				</div>
				<div class="tutor-overflow-hidden">							
					<?php echo wp_kses_post( wp_unslash( $question->answer_explanation ) ); ?>
				</div>
			</div>
		<?php
	}

	/**
	 * Enqueue the draw-image question script.
	 *
	 * Script bundles the shared draw-on-image core (quiz-type/shared/draw-on-image.js).
	 * Script URL can be overridden via filter tutor_pro_draw_image_script_url.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_draw_image_question_script() {
		/**
		 * Filter the draw-image question script URL.
		 *
		 * @param string $script_url Default plugin asset URL.
		 */
		$script_url = apply_filters(
			'tutor_pro_draw_image_script_url',
			tutor_pro()->url . 'assets/js/draw-image-question.js'
		);

		wp_enqueue_script(
			'tutor-pro-draw-image-question',
			$script_url,
			array(),
			TUTOR_PRO_VERSION,
			true
		);
	}

	/**
	 * Include a template from this plugin. Used for add-on-only slugs (e.g. draw-image, pin-image) that core
	 * skips via `should_tutor_load_template` — calling `tutor_load_template()` for those would no-op for Pro too.
	 *
	 * @since 4.0.0
	 *
	 * @param string $dot_notation Same as tutor_load_template first parameter.
	 * @param array  $variables    Variables for the partial (extracted like core).
	 *
	 * @return void
	 */
	private function load_pro_quiz_template( $dot_notation, array $variables ) {
		$relative = str_replace( '.', DIRECTORY_SEPARATOR, $dot_notation );
		$file     = trailingslashit( tutor_pro()->path ) . 'templates/' . $relative . '.php';
		if ( ! file_exists( $file ) ) {
			return;
		}

		do_action( 'tutor_load_template_before', $dot_notation, $variables );
		extract( $variables ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- mirrors tutor_load_template().
		include $file;
		do_action( 'tutor_load_template_after', $dot_notation, $variables );
	}

	/**
	 * Render "Draw on Image" question type UI on the front-end quiz attempt page.
	 *
	 * @since 4.0.0
	 *
	 * @param string $question_type   Question type.
	 * @param object $is_started_quiz Quiz attempt object.
	 * @param object $question        Question object.
	 *
	 * @return void
	 */
	public function render_draw_image_question( $question_type, $is_started_quiz, $question ) {
		// Core passes the template slug (e.g. draw-image); DB uses draw_image.
		$normalized_type = is_string( $question_type ) ? str_replace( '-', '_', $question_type ) : '';
		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE !== $normalized_type ) {
			return;
		}

		if ( tutor_utils()->is_legacy_learning_mode() ) {
			return;
		}

		$question_id = (int) ( $question->question_id ?? 0 );
		if ( $question_id > 0 && ! empty( $GLOBALS['tutor_learning_area_draw_image_rendered'][ $question_id ] ) ) {
			return;
		}

		$this->enqueue_draw_image_question_script();

		$question_settings = maybe_unserialize( $question->question_settings );
		$answers           = QuizModel::get_answers_by_quiz_question( $question->question_id, false );
		$answer_required   = (bool) tutor_utils()->array_get( 'answer_required', $question_settings );

		$question_for_template = array_merge( (array) $question, array( 'question_answers' => $answers ) );

		$attempt_id = (int) ( is_object( $is_started_quiz ) ? ( $is_started_quiz->attempt_id ?? 0 ) : 0 );

		$this->load_pro_quiz_template(
			'learning-area.quiz.questions.draw-image',
			array(
				'question'                 => $question_for_template,
				'quiz_settings'            => array(),
				'answer_is_required'       => $answer_required,
				'required_message'         => __( 'The answer for this question is required', 'tutor-pro' ),
				'question_field_name_base' => sprintf( 'attempt[%d][quiz_question][%d]', $attempt_id, $question_id ),
			)
		);
	}

	/**
	 * Enqueue the pin-image question script for student quiz attempt.
	 *
	 * Script URL can be overridden via filter tutor_pro_pin_image_script_url.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_pin_image_question_script() {
		/**
		 * Filter the pin-image question script URL.
		 *
		 * @param string $script_url Default plugin asset URL.
		 */
		$script_url = apply_filters(
			'tutor_pro_pin_image_script_url',
			tutor_pro()->url . 'assets/js/pin-image-question.js'
		);

		wp_enqueue_script(
			'tutor-pro-pin-image-question',
			$script_url,
			array(),
			TUTOR_PRO_VERSION,
			true
		);
	}

	/**
	 * Render "Pin on Image" question type UI on the front-end quiz attempt page.
	 *
	 * @since 4.0.0
	 *
	 * @param string $question_type   Question type.
	 * @param object $is_started_quiz Quiz attempt object.
	 * @param object $question        Question object.
	 *
	 * @return void
	 */
	public function render_pin_image_question( $question_type, $is_started_quiz, $question ) {
		$question_type = str_replace( '-', '_', (string) $question_type );
		if ( QuizModel::QUESTION_TYPE_PIN_IMAGE !== $question_type ) {
			return;
		}

		// Learning area / legacy gate: never render the non-learning-area pin UI.
		if ( tutor_utils()->is_legacy_learning_mode() ) {
			return;
		}

		$question_id                      = (int) ( $question->question_id ?? 0 );
		$learning_area_pin_image_rendered = $GLOBALS['tutor_learning_area_pin_image_rendered'][ $question_id ] ?? false;
		if ( $learning_area_pin_image_rendered ) {
			return;
		}

		$this->enqueue_pin_image_question_script();

		$question_settings = maybe_unserialize( $question->question_settings );
		$answers           = QuizModel::get_answers_by_quiz_question( $question->question_id, false );
		$answer_required   = (bool) tutor_utils()->array_get( 'answer_required', $question_settings );

		$attempt_id = (int) ( is_object( $is_started_quiz ) ? ( $is_started_quiz->attempt_id ?? 0 ) : 0 );

		$question_for_template = array_merge(
			(array) $question,
			array(
				'question_answers' => $answers,
			)
		);

		$this->load_pro_quiz_template(
			'learning-area.quiz.questions.pin-image',
			array(
				'question'                 => $question_for_template,
				'quiz_settings'            => array(),
				'answer_is_required'       => $answer_required,
				'required_message'         => __( 'The answer for this question is required', 'tutor-pro' ),
				'question_field_name_base' => sprintf( 'attempt[%d][quiz_question][%d]', $attempt_id, $question_id ),
			)
		);
	}

	/**
	 * Enqueue the scale question script for student quiz attempt.
	 *
	 * Script URL can be overridden via filter tutor_pro_scale_script_url.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_scale_question_script() {
		/**
		 * Filter the scale question script URL.
		 *
		 * @param string $script_url Default plugin asset URL.
		 */
		$script_url = apply_filters(
			'tutor_pro_scale_script_url',
			tutor_pro()->url . 'assets/js/scale-question.js'
		);

		wp_enqueue_script(
			'tutor-pro-scale-question',
			$script_url,
			array(),
			TUTOR_PRO_VERSION,
			true
		);
	}

	/**
	 * Render "Scale" question type UI on the front-end quiz attempt page.
	 *
	 * @since 4.0.0
	 *
	 * @param string $question_type   Question type.
	 * @param object $is_started_quiz Quiz attempt object.
	 * @param object $question        Question object.
	 *
	 * @return void
	 */
	public function render_scale_question( $question_type, $is_started_quiz, $question ) {
		// If the new learning-area frontend has already rendered this question,
		// skip the legacy renderer to avoid duplicate UI.
		if ( isset( $GLOBALS['tutor_learning_area_scale_rendered'][ $question->question_id ] ) && $GLOBALS['tutor_learning_area_scale_rendered'][ $question->question_id ] ) {
			return;
		}

		if ( QuizModel::QUESTION_TYPE_SCALE !== $question_type ) {
			return;
		}

		// Learning area / legacy gate: never render the non-learning-area scale UI.
		if ( tutor_utils()->is_legacy_learning_mode() ) {
			return;
		}

		$question_id = (int) ( $question->question_id ?? 0 );
		if ( $question_id <= 0 ) {
			return;
		}

		$this->enqueue_scale_question_script();

		$question_settings = maybe_unserialize( $question->question_settings );
		$answers           = QuizModel::get_answers_by_quiz_question( $question->question_id, false );
		$answer_required   = (bool) tutor_utils()->array_get( 'answer_required', $question_settings );

		$attempt_id = (int) ( is_object( $is_started_quiz ) ? ( $is_started_quiz->attempt_id ?? 0 ) : 0 );

		$question_for_template = array_merge(
			(array) $question,
			array(
				'question_answers' => $answers,
			)
		);

		$this->load_pro_quiz_template(
			'learning-area.quiz.questions.scale',
			array(
				'question'                 => $question_for_template,
				'quiz_settings'            => array(),
				'answer_is_required'       => $answer_required,
				'required_message'         => __( 'The answer for this question is required', 'tutor-pro' ),
				'question_field_name_base' => sprintf( 'attempt[%d][quiz_question][%d]', $attempt_id, $question_id ),
			)
		);
	}

	/**
	 * Enqueue the coordinates question script for student quiz attempt.
	 *
	 * Script URL can be overridden via filter tutor_pro_coordinates_question_script_url.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_coordinates_question_script() {
		/**
		 * Filter the coordinates question script URL.
		 *
		 * @param string $script_url Default plugin asset URL.
		 */
		$script_url = apply_filters(
			'tutor_pro_coordinates_question_script_url',
			tutor_pro()->url . 'assets/js/coordinates-question.js'
		);

		wp_enqueue_script(
			'tutor-pro-coordinates-question',
			$script_url,
			array(),
			TUTOR_PRO_VERSION,
			true
		);
	}

	/**
	 * Render "Coordinates" question type UI (learning-area quiz attempt only).
	 *
	 * @since 4.0.0
	 *
	 * @param string $question_type   Question type.
	 * @param object $is_started_quiz Quiz attempt object.
	 * @param object $question        Question object.
	 *
	 * @return void
	 */
	public function render_coordinates_question( $question_type, $is_started_quiz, $question ) {
		if ( QuizModel::QUESTION_TYPE_COORDINATES !== $question_type ) {
			return;
		}

		if ( tutor_utils()->is_legacy_learning_mode() ) {
			return;
		}

		$question_id = (int) ( $question->question_id ?? 0 );
		if ( $question_id > 0 && ! empty( $GLOBALS['tutor_learning_area_coordinates_rendered'][ $question_id ] ) ) {
			return;
		}

		$this->enqueue_coordinates_question_script();

		$question_settings = maybe_unserialize( $question->question_settings );
		$answers           = QuizModel::get_answers_by_quiz_question( $question->question_id, false );
		$answer_required   = (bool) tutor_utils()->array_get( 'answer_required', $question_settings );

		$attempt_id = (int) ( is_object( $is_started_quiz ) ? ( $is_started_quiz->attempt_id ?? 0 ) : 0 );

		$question_for_template = array_merge(
			(array) $question,
			array(
				'question_answers' => $answers,
			)
		);

		$this->load_pro_quiz_template(
			'learning-area.quiz.questions.coordinates',
			array(
				'question'                 => $question_for_template,
				'quiz_settings'            => array(),
				'answer_is_required'       => $answer_required,
				'required_message'         => __( 'The answer for this question is required', 'tutor-pro' ),
				'question_field_name_base' => sprintf( 'attempt[%d][quiz_question][%d]', $attempt_id, $question_id ),
			)
		);
	}

	/**
	 * Enqueue the puzzle question script for student quiz attempt.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_puzzle_question_script() {
		$script_url = apply_filters(
			'tutor_pro_puzzle_question_script_url',
			tutor_pro()->url . 'assets/js/puzzle-question.js'
		);

		wp_enqueue_script(
			'tutor-pro-puzzle-question',
			$script_url,
			array(),
			TUTOR_PRO_VERSION,
			true
		);
	}

	/**
	 * Render "Puzzle" question type UI (learning-area quiz attempt only).
	 *
	 * @since 4.0.0
	 *
	 * @param string $question_type   Question type.
	 * @param object $is_started_quiz Quiz attempt object.
	 * @param object $question        Question object.
	 *
	 * @return void
	 */
	public function render_puzzle_question( $question_type, $is_started_quiz, $question ) {
		if ( QuizModel::QUESTION_TYPE_PUZZLE !== $question_type ) {
			return;
		}

		if ( tutor_utils()->is_legacy_learning_mode() ) {
			return;
		}

		$question_id = (int) ( $question->question_id ?? 0 );
		if ( $question_id > 0 && ! empty( $GLOBALS['tutor_learning_area_puzzle_rendered'][ $question_id ] ) ) {
			return;
		}

		$this->enqueue_puzzle_question_script();

		$question_settings             = maybe_unserialize( $question->question_settings );
		$answers                       = QuizModel::get_answers_by_quiz_question( $question->question_id, false );
		$answer_required               = (bool) tutor_utils()->array_get( 'answer_required', $question_settings );
		$attempt_id                    = (int) ( is_object( $is_started_quiz ) ? ( $is_started_quiz->attempt_id ?? 0 ) : 0 );
		$grid_size                     = isset( $question_settings['puzzle_grid_size'] ) ? (int) $question_settings['puzzle_grid_size'] : 4;
		$grid_size                     = max( 2, min( 7, $grid_size ) );
		$total_pieces                  = $grid_size * $grid_size;
		$puzzle_token                  = $this->generate_puzzle_session_token( $attempt_id, $question_id, $grid_size, $total_pieces );
		$show_puzzle_answer_background = ! isset( $question_settings['enable_puzzle_answer_background'] )
			|| '1' === (string) $question_settings['enable_puzzle_answer_background'];

		$question_for_template = array_merge(
			(array) $question,
			array(
				'question_answers' => $answers,
			)
		);

		$this->load_pro_quiz_template(
			'learning-area.quiz.questions.puzzle',
			array(
				'question'                      => $question_for_template,
				'show_puzzle_answer_background' => $show_puzzle_answer_background,
				'answer_is_required'            => $answer_required,
				'required_message'              => __( 'The answer for this question is required', 'tutor-pro' ),
				'question_field_name_base'      => sprintf( 'attempt[%d][quiz_question][%d]', $attempt_id, $question_id ),
				'puzzle_session_token'          => $puzzle_token,
			)
		);
	}

	/**
	 * Grade "Draw on Image" question type automatically based on mask similarity.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $answers_data  Prepared answer data (given_answer, marks...).
	 * @param int    $question_id   Question ID.
	 * @param string $question_type Question type.
	 * @param int    $user_id       User ID (optional).
	 * @param int    $attempt_id    Quiz attempt ID (optional).
	 *
	 * @return array
	 */
	public function grade_draw_image_question( $answers_data, $question_id, $question_type, $user_id = null, $attempt_id = null ) {
		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE !== $question_type ) {
			return $answers_data;
		}

		$student_mask = $answers_data['given_answer'] ?? '';

		if ( empty( $student_mask ) ) {
			// No drawing submitted; leave as incorrect with 0 marks.
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		// Load instructor mask from question answers.
		$question = QuizModel::get_quiz_question_by_id( $question_id );
		if ( ! is_object( $question ) ) {
			return $answers_data;
		}

		$answers = QuizModel::get_question_answers( $question_id, QuizModel::QUESTION_TYPE_DRAW_IMAGE );
		if ( empty( $answers ) || empty( $answers[0]->answer_two_gap_match ) ) {
			return $answers_data;
		}

		$instructor_mask = $answers[0]->answer_two_gap_match;

		// Compute similarity score (0.0–1.0) between instructor mask and student drawing.
		$score_ratio = $this->compare_draw_image_masks( $instructor_mask, $student_mask );

		/**
		 * Filter the similarity threshold for draw-image questions.
		 *
		 * The JS R&D prototype treats score >= 50 as correct, so the default
		 * threshold here is 0.5.
		 *
		 * @param float $threshold Default 0.5 (50% score).
		 */
		// Per-question setting (quiz builder).
		$question_settings = maybe_unserialize( $question->question_settings );
		$question_setting  = is_array( $question_settings ) ? tutor_utils()->array_get( 'draw_image_threshold_percent', $question_settings ) : null;
		$threshold_percent = is_numeric( $question_setting ) ? (float) $question_setting : 70.0;
		$threshold_percent = max( 40.0, min( 100.0, $threshold_percent ) );
		$threshold         = $threshold_percent / 100.0;
		$threshold         = (float) apply_filters( 'tutor_pro_draw_image_threshold', $threshold, $question_id );

		if ( $score_ratio >= $threshold ) {
			$answers_data['achieved_mark'] = (float) $answers_data['question_mark'];
			$answers_data['is_correct']    = 1;
		} else {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
		}

		return $answers_data;
	}

	/**
	 * Grade scale question via tutor_filter_quiz_answer_data (sets achieved_mark and is_correct).
	 *
	 * @since 4.0.0
	 *
	 * @param array  $answers_data  Answer row to insert (user_id, quiz_id, question_id, given_answer, question_mark, achieved_mark, is_correct, etc.).
	 * @param int    $question_id   Question ID.
	 * @param string $question_type Question type.
	 * @param int    $user_id       User ID (optional).
	 * @param int    $attempt_id    Quiz attempt ID (optional).
	 *
	 * @return array Modified answers_data.
	 */
	public function grade_scale_question( $answers_data, $question_id, $question_type, $user_id = null, $attempt_id = null ) {
		if ( QuizModel::QUESTION_TYPE_SCALE !== $question_type ) {
			return $answers_data;
		}

		$given_answer = $answers_data['given_answer'] ?? '';
		$student_data = json_decode( stripslashes( (string) $given_answer ), true );
		if ( ! is_array( $student_data ) || ! isset( $student_data['value'] ) ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$answers = QuizModel::get_question_answers( $question_id, QuizModel::QUESTION_TYPE_SCALE );
		if ( empty( $answers ) || empty( $answers[0]->answer_two_gap_match ) ) {
			return $answers_data;
		}

		$target_json = $answers[0]->answer_two_gap_match;
		$target      = json_decode( stripslashes( (string) $target_json ), true );
		if ( ! is_array( $target ) || ! isset( $target['value'] ) ) {
			return $answers_data;
		}

		$student_value = (float) $student_data['value'];
		$correct_value = (float) $target['value'];

		$scale_config = array();
		if ( isset( $target['config'] ) && is_array( $target['config'] ) ) {
			$scale_config = $target['config'];
		}

		$step      = isset( $scale_config['step'] ) ? (float) $scale_config['step'] : 1.0;
		$precision = isset( $scale_config['precision'] ) ? (int) $scale_config['precision'] : ( $step < 1 ? 2 : 0 );

		$student_rounded = (float) number_format( $student_value, $precision, '.', '' );
		$correct_rounded = (float) number_format( $correct_value, $precision, '.', '' );

		// Tolerance scaled to precision (e.g. 0.0001 for precision 3).
		$tolerance  = pow( 10, - max( 3, $precision + 1 ) );
		$is_correct = abs( $student_rounded - $correct_rounded ) <= $tolerance;

		$answers_data['achieved_mark'] = $is_correct ? (float) $answers_data['question_mark'] : 0;
		$answers_data['is_correct']    = $is_correct ? 1 : 0;

		return $answers_data;
	}

	/**
	 * Grade pin_image question: only the area inside the instructor-drawn circle is correct.
	 *
	 * The student places a single pin (point). The pin is correct only if it falls inside
	 * the zone defined by the instructor's drawn circle. No consideration is given to
	 * anything outside that circle—pins outside the drawn circle are always wrong.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $answers_data  Prepared answer data (given_answer = JSON {x,y}, marks...).
	 * @param int    $question_id   Question ID.
	 * @param string $question_type Question type.
	 * @param int    $user_id       User ID (optional).
	 * @param int    $attempt_id    Quiz attempt ID (optional).
	 *
	 * @return array
	 */
	public function grade_pin_image_question( $answers_data, $question_id, $question_type, $user_id = null, $attempt_id = null ) {
		if ( QuizModel::QUESTION_TYPE_PIN_IMAGE !== $question_type ) {
			return $answers_data;
		}

		$given_answer = $answers_data['given_answer'] ?? '';
		if ( '' === $given_answer ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$coords = json_decode( stripslashes( (string) $given_answer ), true );
		if ( ! is_array( $coords ) || ! isset( $coords['x'], $coords['y'] ) ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$x = max( 0.0, min( 1.0, (float) $coords['x'] ) );
		$y = max( 0.0, min( 1.0, (float) $coords['y'] ) );

		$answers = QuizModel::get_question_answers( $question_id, QuizModel::QUESTION_TYPE_PIN_IMAGE );
		if ( empty( $answers ) || empty( $answers[0]->answer_two_gap_match ) ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$instructor_mask_url = $answers[0]->answer_two_gap_match;
		$is_inside           = $this->is_pin_inside_mask( $instructor_mask_url, $x, $y );

		if ( $is_inside ) {
			$answers_data['achieved_mark'] = (float) $answers_data['question_mark'];
			$answers_data['is_correct']    = 1;
		} else {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
		}

		return $answers_data;
	}

	/**
	 * Grade coordinates question: exact match on integer grid point (-10..10).
	 *
	 * @since 4.0.0
	 *
	 * @param array  $answers_data  Prepared answer data.
	 * @param int    $question_id   Question ID.
	 * @param string $question_type Question type.
	 * @param int    $user_id       User ID (optional).
	 * @param int    $attempt_id    Quiz attempt ID (optional).
	 *
	 * @return array
	 */
	public function grade_coordinates_question( $answers_data, $question_id, $question_type, $user_id = null, $attempt_id = null ) {
		if ( QuizModel::QUESTION_TYPE_COORDINATES !== $question_type ) {
			return $answers_data;
		}

		$given_answer = $answers_data['given_answer'] ?? '';
		if ( '' === $given_answer ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$student = json_decode( stripslashes( (string) $given_answer ), true );
		if ( ! is_array( $student ) ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$answers = QuizModel::get_question_answers( $question_id, QuizModel::QUESTION_TYPE_COORDINATES );
		if ( empty( $answers ) || empty( $answers[0]->answer_two_gap_match ) ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$target = json_decode( stripslashes( (string) $answers[0]->answer_two_gap_match ), true );
		if ( ! is_array( $target ) ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}
		$axis_range = 10;
		$question   = QuizModel::get_quiz_question_by_id( $question_id );
		if ( $question && isset( $question->question_settings ) ) {
			$question_settings = maybe_unserialize( $question->question_settings );
			if ( is_array( $question_settings ) && isset( $question_settings['coordinates_axis_range'] ) ) {
				$axis_range = 20 === (int) $question_settings['coordinates_axis_range'] ? 20 : 10;
			}
		}

		$min = -1 * $axis_range;
		$max = $axis_range;
		if ( isset( $target['config'] ) && is_array( $target['config'] ) ) {
			$maybe_min = isset( $target['config']['min'] ) ? (int) $target['config']['min'] : -10;
			$maybe_max = isset( $target['config']['max'] ) ? (int) $target['config']['max'] : 10;
			if ( $maybe_max > $maybe_min ) {
				$min = max( -1000, min( 1000, $maybe_min ) );
				$max = max( -1000, min( 1000, $maybe_max ) );
			}
		}

		$target_points = array();
		if ( isset( $target['points'] ) && is_array( $target['points'] ) ) {
			$target_points = $target['points'];
		} elseif ( array_keys( $target ) === range( 0, count( $target ) - 1 ) ) {
			$target_points = $target;
		} elseif ( isset( $target['x'], $target['y'] ) ) {
			$target_points = array( $target );
		}

		$student_points = array();
		if ( isset( $student['points'] ) && is_array( $student['points'] ) ) {
			$student_points = $student['points'];
		} elseif ( array_keys( $student ) === range( 0, count( $student ) - 1 ) ) {
			$student_points = $student;
		} elseif ( isset( $student['x'], $student['y'] ) ) {
			$student_points = array( $student );
		}

		$normalize = static function ( $points, $min_value, $max_value ) {
			$out = array();
			if ( ! is_array( $points ) ) {
				return $out;
			}
			foreach ( $points as $point ) {
				if ( ! is_array( $point ) || ! isset( $point['x'], $point['y'] ) ) {
					continue;
				}
				$x     = max( $min_value, min( $max_value, (int) $point['x'] ) );
				$y     = max( $min_value, min( $max_value, (int) $point['y'] ) );
				$out[] = $x . ',' . $y;
			}
			$out = array_values( array_unique( $out ) );
			sort( $out );
			return $out;
		};

		$normalized_target  = $normalize( $target_points, $min, $max );
		$normalized_student = $normalize( $student_points, $min, $max );
		$is_correct         = ! empty( $normalized_target ) && $normalized_target === $normalized_student;

		$answers_data['achieved_mark'] = $is_correct ? (float) $answers_data['question_mark'] : 0;
		$answers_data['is_correct']    = $is_correct ? 1 : 0;

		return $answers_data;
	}

	/**
	 * Grade puzzle question: full mark only when all pieces are correctly locked.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $answers_data  Prepared answer data.
	 * @param int    $question_id   Question ID.
	 * @param string $question_type Question type.
	 * @param int    $user_id       User ID (optional).
	 * @param int    $attempt_id    Quiz attempt ID (optional).
	 *
	 * @return array
	 */
	public function grade_puzzle_question( $answers_data, $question_id, $question_type, $user_id = null, $attempt_id = null ) {
		if ( QuizModel::QUESTION_TYPE_PUZZLE !== $question_type ) {
			return $answers_data;
		}

		$given_answer = $answers_data['given_answer'] ?? '';
		if ( '' === $given_answer ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$payload = json_decode( stripslashes( (string) $given_answer ), true );
		if ( ! is_array( $payload ) ) {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
			return $answers_data;
		}

		$is_completed = ! empty( $payload['is_completed'] ) && ! empty( $payload['is_verified'] );
		if ( $is_completed ) {
			$answers_data['achieved_mark'] = (float) $answers_data['question_mark'];
			$answers_data['is_correct']    = 1;
		} else {
			$answers_data['achieved_mark'] = 0;
			$answers_data['is_correct']    = 0;
		}

		return $answers_data;
	}

	/**
	 * Create signed token for puzzle answer payload verification.
	 *
	 * @since 4.0.0
	 *
	 * @param int $attempt_id   Quiz attempt ID.
	 * @param int $question_id  Question ID.
	 * @param int $grid_size    Puzzle grid size.
	 * @param int $total_pieces Total puzzle pieces.
	 *
	 * @return string
	 */
	private function generate_puzzle_session_token( $attempt_id, $question_id, $grid_size, $total_pieces ) {
		$attempt_id   = (int) $attempt_id;
		$question_id  = (int) $question_id;
		$grid_size    = (int) $grid_size;
		$total_pieces = (int) $total_pieces;
		$user_id      = (int) get_current_user_id();
		$issued_at    = (int) time();
		$payload      = implode( ':', array( $attempt_id, $question_id, $grid_size, $total_pieces, $user_id, $issued_at ) );
		$signature    = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		return $payload . '.' . $signature;
	}

	/**
	 * Verify puzzle token and claims against attempt and question.
	 *
	 * @since 4.0.0
	 *
	 * @param string $token        Submitted token.
	 * @param int    $attempt_id   Quiz attempt ID.
	 * @param int    $question_id  Question ID.
	 * @param int    $grid_size    Submitted grid size.
	 * @param int    $total_pieces Submitted total pieces.
	 *
	 * @return bool
	 */
	private function verify_puzzle_session_token( $token, $attempt_id, $question_id, $grid_size, $total_pieces ) {
		if ( ! is_string( $token ) || '' === trim( $token ) ) {
			return false;
		}

		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$payload   = (string) $parts[0];
		$signature = (string) $parts[1];
		$expected  = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		if ( ! hash_equals( $expected, $signature ) ) {
			return false;
		}

		$payload_parts = explode( ':', $payload );
		if ( 6 !== count( $payload_parts ) ) {
			return false;
		}

		$token_attempt_id   = (int) $payload_parts[0];
		$token_question_id  = (int) $payload_parts[1];
		$token_grid_size    = (int) $payload_parts[2];
		$token_total_pieces = (int) $payload_parts[3];
		$token_user_id      = (int) $payload_parts[4];
		$token_issued_at    = (int) $payload_parts[5];

		if ( $token_attempt_id !== (int) $attempt_id || $token_question_id !== (int) $question_id ) {
			return false;
		}
		if ( $token_grid_size !== (int) $grid_size || $token_total_pieces !== (int) $total_pieces ) {
			return false;
		}
		if ( (int) get_current_user_id() !== $token_user_id ) {
			return false;
		}

		$max_age_seconds = DAY_IN_SECONDS;
		if ( $token_issued_at <= 0 || ( time() - $token_issued_at ) > $max_age_seconds ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate puzzle lock map and return normalized result.
	 *
	 * @since 4.0.0
	 *
	 * @param array $locks        Submitted lock mapping.
	 * @param int   $total_pieces Expected piece count.
	 *
	 * @return array{valid:bool,locked_count:int,is_completed:bool,locks:array}
	 */
	private function validate_puzzle_locks( $locks, $total_pieces ) {
		if ( ! is_array( $locks ) || $total_pieces <= 0 ) {
			return array(
				'valid'        => false,
				'locked_count' => 0,
				'is_completed' => false,
				'locks'        => array(),
			);
		}

		$normalized = array();
		$seen_piece = array();
		foreach ( $locks as $slot_index_raw => $piece_index_raw ) {
			$slot_index  = (int) $slot_index_raw;
			$piece_index = (int) $piece_index_raw;

			if ( $slot_index < 0 || $slot_index >= $total_pieces || $piece_index < 0 || $piece_index >= $total_pieces ) {
				return array(
					'valid'        => false,
					'locked_count' => 0,
					'is_completed' => false,
					'locks'        => array(),
				);
			}
			if ( isset( $seen_piece[ $piece_index ] ) ) {
				return array(
					'valid'        => false,
					'locked_count' => 0,
					'is_completed' => false,
					'locks'        => array(),
				);
			}

			$seen_piece[ $piece_index ]         = true;
			$normalized[ (string) $slot_index ] = $piece_index;
		}

		$locked_count = count( $normalized );
		$is_completed = true;
		if ( $locked_count !== $total_pieces ) {
			$is_completed = false;
		} else {
			for ( $i = 0; $i < $total_pieces; $i++ ) {
				if ( ! isset( $normalized[ (string) $i ] ) || (int) $normalized[ (string) $i ] !== $i ) {
					$is_completed = false;
					break;
				}
			}
		}

		return array(
			'valid'        => true,
			'locked_count' => $locked_count,
			'is_completed' => $is_completed,
			'locks'        => $normalized,
		);
	}

	/**
	 * Check if normalized point (x,y) in [0,1] falls inside the instructor-drawn zone only.
	 *
	 * The correct answer is the area inside the drawn circle (or shape). Only pins that
	 * fall inside this zone are correct. No consideration is given to anything outside
	 * the drawn circle—pins outside are always wrong. The mask stores the drawn stroke;
	 * we build the convex hull of painted pixels and test point-in-polygon so that only
	 * the interior of the drawn shape counts as correct.
	 *
	 * @since 4.0.0
	 *
	 * @param string $mask_url Instructor mask file URL or data:image/...;base64,... data URL.
	 * @param float  $x        Normalized x (0–1).
	 * @param float  $y        Normalized y (0–1).
	 *
	 * @return bool True only if the pin is inside the drawn zone; false otherwise.
	 */
	private function is_pin_inside_mask( $mask_url, $x, $y ) {
		if ( ! is_string( $mask_url ) || '' === trim( $mask_url ) ) {
			return false;
		}

		$mask_url = trim( $mask_url );
		$body     = '';

		// Support data URL (e.g. base64 mask from canvas) so only the drawn zone is considered.
		if ( preg_match( '#^data:image/[^;]+;base64,(.+)$#is', $mask_url, $m ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding data URL image payload, not user input for execution.
			$decoded = base64_decode( $m[1], true );
			$body    = false !== $decoded ? $decoded : '';
		} else {
			$path = QuizImageStorage::quiz_image_stored_value_to_path( $mask_url );
			if ( '' === $path || ! is_readable( $path ) ) {
				return false;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read for GD.
			$body = file_get_contents( $path );
		}
		if ( '' === $body || ! function_exists( 'imagecreatefromstring' ) ) {
			return false;
		}

		$img = imagecreatefromstring( $body );
		if ( ! $img ) {
			return false;
		}

		$w = imagesx( $img );
		$h = imagesy( $img );
		if ( $w <= 0 || $h <= 0 ) {
			return false;
		}

		$px = (int) floor( $x * ( $w - 1 ) );
		$py = (int) floor( $y * ( $h - 1 ) );
		$px = max( 0, min( $w - 1, $px ) );
		$py = max( 0, min( $h - 1, $py ) );

		// Collect painted pixels (sample every 2nd to keep count manageable for large images).
		$painted = array();
		$sample  = ( $w * $h > 400000 ) ? 2 : 1; // Sample step for big images.
		$step_y  = $sample;
		$step_x  = $sample;
		for ( $iy = 0; $iy < $h; $iy += $step_y ) {
			for ( $ix = 0; $ix < $w; $ix += $step_x ) {
				if ( $this->is_mask_pixel_painted( $img, $ix, $iy ) ) {
					$painted[] = array( $ix, $iy );
				}
			}
		}

		if ( count( $painted ) < 3 ) {
			return false;
		}

		$hull = $this->convex_hull_graham( $painted );
		if ( count( $hull ) < 3 ) {
			return false;
		}

		// Pin must be inside the hull and actually on a painted pixel (strict: only the drawn zone counts).
		$inside_hull = $this->point_in_polygon( $px, $py, $hull );
		if ( ! $inside_hull ) {
			return false;
		}

		// More lenient approach: check for painted pixels in a larger radius.
		/**
		 * Filter the pixel tolerance radius for pin image questions.
		 *
		 * @param int $radius Default pixel radius to check around the pin location.
		 */
		$tolerance_radius = (int) apply_filters( 'tutor_pro_pin_image_tolerance_radius', 25 );
		$has_painted_near = $this->has_painted_pixel_near( $img, $px, $py, $w, $h, $tolerance_radius );

		// If still not found with the tolerance radius, try a more lenient approach.
		// Check if the pin is at least within the general area of the drawn shape.
		if ( ! $has_painted_near ) {
			// Calculate the bounding box of all painted pixels.
			$min_x = min( array_column( $painted, 0 ) );
			$max_x = max( array_column( $painted, 0 ) );
			$min_y = min( array_column( $painted, 1 ) );
			$max_y = max( array_column( $painted, 1 ) );

			// Add some padding to the bounding box (20% of width/height).
			$padding_x = ( $max_x - $min_x ) * 0.2;
			$padding_y = ( $max_y - $min_y ) * 0.2;

			$expanded_min_x = max( 0, $min_x - $padding_x );
			$expanded_max_x = min( $w - 1, $max_x + $padding_x );
			$expanded_min_y = max( 0, $min_y - $padding_y );
			$expanded_max_y = min( $h - 1, $max_y + $padding_y );

			// Check if pin is within the expanded bounding box.
			$has_painted_near = ( $px >= $expanded_min_x && $px <= $expanded_max_x &&
								$py >= $expanded_min_y && $py <= $expanded_max_y );
		}

		return $has_painted_near;
	}


	/**
	 * Return true if there is at least one painted pixel within the given radius of (px, py).
	 * Used to ensure the pin actually lands on the instructor-drawn zone, not just inside the convex hull.
	 *
	 * @since 4.0.0
	 *
	 * @param \GdImage|resource $img    GD image.
	 * @param int               $px     Pin x (pixel).
	 * @param int               $py     Pin y (pixel).
	 * @param int               $w      Image width.
	 * @param int               $h      Image height.
	 * @param int               $radius Max pixel distance to consider (e.g. 3).
	 *
	 * @return bool
	 */
	private function has_painted_pixel_near( $img, $px, $py, $w, $h, $radius = 3 ) {
		$radius = max( 0, (int) $radius );
		for ( $dy = -$radius; $dy <= $radius; $dy++ ) {
			for ( $dx = -$radius; $dx <= $radius; $dx++ ) {
				$ix = $px + $dx;
				$iy = $py + $dy;
				if ( $ix >= 0 && $ix < $w && $iy >= 0 && $iy < $h && $this->is_mask_pixel_painted( $img, $ix, $iy ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Return true if the pixel at (ix, iy) in the image is painted (non-transparent, not white).
	 *
	 * @since 4.0.0
	 *
	 * @param \GdImage|resource $img GD image (GdImage in PHP 8+, resource in PHP 7.4).
	 * @param int               $ix  X coordinate.
	 * @param int               $iy  Y coordinate.
	 *
	 * @return bool
	 */
	private function is_mask_pixel_painted( $img, $ix, $iy ) {
		if ( ! function_exists( 'imagecolorat' ) ) {
			return false;
		}
		$color = imagecolorat( $img, $ix, $iy );
		$alpha = ( $color >> 24 ) & 0x7F;
		$r     = ( $color >> 16 ) & 0xFF;
		$g     = ( $color >> 8 ) & 0xFF;
		$b     = $color & 0xFF;

		// Very lenient color detection - consider pixels painted if they're not fully transparent
		// and not very close to white (allowing for anti-aliasing and color variations).
		$is_painted = ( $alpha < 127 ) && ( $r < 220 || $g < 220 || $b < 220 );

		return $is_painted;
	}

	/**
	 * Graham scan convex hull. Returns ordered vertices of the convex hull.
	 *
	 * @since 4.0.0
	 *
	 * @param array $points Array of [x, y] points.
	 *
	 * @return array Array of [x, y] hull vertices in counter-clockwise order.
	 */
	private function convex_hull_graham( array $points ) {
		$n = count( $points );
		if ( $n < 3 ) {
			return $points;
		}

		// Find lowest (then leftmost) point.
		$start = 0;
		for ( $i = 1; $i < $n; $i++ ) {
			if ( $points[ $i ][1] < $points[ $start ][1] ||
				( $points[ $i ][1] === $points[ $start ][1] && $points[ $i ][0] < $points[ $start ][0] ) ) {
				$start = $i;
			}
		}
		$p0 = $points[ $start ];

		// Sort by polar angle from p0.
		$sorted = array();
		foreach ( $points as $i => $p ) {
			if ( $i === $start ) {
				continue;
			}
			$dx       = $p[0] - $p0[0];
			$dy       = $p[1] - $p0[1];
			$angle    = atan2( $dy, $dx );
			$sorted[] = array( $p[0], $p[1], $angle );
		}
		usort(
			$sorted,
			function ( $a, $b ) {
				return $a[2] < $b[2] ? -1 : ( $a[2] > $b[2] ? 1 : 0 );
			}
		);

		$hull = array( array( $p0[0], $p0[1] ) );
		foreach ( $sorted as $p ) {
			$hull[] = array( $p[0], $p[1] );
			$m      = count( $hull );
			while ( $m >= 3 ) {
				$o = $this->cross( $hull[ $m - 3 ], $hull[ $m - 2 ], $hull[ $m - 1 ] );
				if ( $o >= 0 ) {
					break;
				}
				array_splice( $hull, $m - 2, 1 );
				$m = count( $hull );
			}
		}

		return $hull;
	}

	/**
	 * Cross product (orientation) of vectors (p1-p0) and (p2-p0). Positive = counter-clockwise.
	 *
	 * @since 4.0.0
	 *
	 * @param array $p0 [x,y].
	 * @param array $p1 [x,y].
	 * @param array $p2 [x,y].
	 *
	 * @return float
	 */
	private function cross( $p0, $p1, $p2 ) {
		return ( $p1[0] - $p0[0] ) * ( $p2[1] - $p0[1] ) - ( $p1[1] - $p0[1] ) * ( $p2[0] - $p0[0] );
	}

	/**
	 * Ray-casting point-in-polygon test.
	 *
	 * @since 4.0.0
	 *
	 * @param int   $px     Point x.
	 * @param int   $py     Point y.
	 * @param array $hull   Polygon as array of [x, y] vertices (counter-clockwise).
	 *
	 * @return bool
	 */
	private function point_in_polygon( $px, $py, array $hull ) {
		$n      = count( $hull );
		$inside = false;
		for ( $i = 0, $j = $n - 1; $i < $n; $j = $i++ ) {
			$xi               = $hull[ $i ][0];
			$yi               = $hull[ $i ][1];
			$xj               = $hull[ $j ][0];
			$yj               = $hull[ $j ][1];
			$edge_crosses_ray = ( $yi > $py ) !== ( $yj > $py );
			$denom            = $yj - $yi + 1e-10;
			$intersection_x   = ( $xj - $xi ) * ( $py - $yi ) / $denom + $xi;
			if ( $edge_crosses_ray && $px < $intersection_x ) {
				$inside = ! $inside;
			}
		}
		return $inside;
	}

	/**
	 * Naive mask comparison for draw-image question type.
	 *
	 * @since 4.0.0
	 *
	 * This implementation compares decoded PNGs and returns a ratio of how well
	 * the student drawing matches the instructor's mask, using a similar
	 * heuristic to the JS R&D prototype (bounding boxes, centroids, size, etc.).
	 * If anything fails, it safely returns 0.
	 *
	 * Masks are stored values resolved via QuizImageStorage::quiz_image_stored_value_to_path().
	 *
	 * @param string $instructor_mask Instructor mask stored value.
	 * @param string $student_mask    Student mask stored value.
	 *
	 * @return float Similarity ratio between 0 and 1.
	 */
	private function compare_draw_image_masks( $instructor_mask, $student_mask ) {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return 0.0;
		}

		/**
		 * Load mask image from local path (basename or uploads-relative stored value).
		 *
		 * @param string $mask Stored mask reference.
		 * @return resource|false GD image resource or false on failure.
		 */
		$load_mask_image = static function ( $mask ) {
			if ( ! is_string( $mask ) || '' === trim( $mask ) ) {
				return false;
			}
			$mask = trim( $mask );
			$path = QuizImageStorage::quiz_image_stored_value_to_path( $mask );
			if ( '' === $path || ! is_readable( $path ) ) {
				return false;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read for GD.
			$body = file_get_contents( $path );
			if ( false === $body || '' === $body ) {
				return false;
			}
			return imagecreatefromstring( $body );
		};

		$img_teacher = $load_mask_image( $instructor_mask );
		$img_student = $load_mask_image( $student_mask );

		if ( ! $img_teacher || ! $img_student ) {
			return 0.0;
		}

		$w = imagesx( $img_teacher );
		$h = imagesy( $img_teacher );

		// Resize student image to match teacher dimensions if needed.
		if ( imagesx( $img_student ) !== $w || imagesy( $img_student ) !== $h ) {
			$old_student = $img_student;
			$resized     = imagecreatetruecolor( $w, $h );
			imagealphablending( $resized, false );
			imagesavealpha( $resized, true );
			imagecopyresampled( $resized, $img_student, 0, 0, 0, 0, $w, $h, imagesx( $img_student ), imagesy( $img_student ) );
			$img_student = $resized;
			unset( $old_student );
		}

		$teacher_total = 0;
		$student_total = 0;
		$overlap       = 0;

		for ( $y = 0; $y < $h; $y++ ) {
			for ( $x = 0; $x < $w; $x++ ) {
				$rgba_teacher  = imagecolorat( $img_teacher, $x, $y );
				$alpha_teacher = ( $rgba_teacher & 0x7F000000 ) >> 24; // 0 opaque, 127 transparent.
				$rgba_student  = imagecolorat( $img_student, $x, $y );
				$alpha_student = ( $rgba_student & 0x7F000000 ) >> 24;
				$teacher_on    = $alpha_teacher < 127;
				$student_on    = $alpha_student < 127;

				if ( $teacher_on ) {
					++$teacher_total;
				}

				if ( $student_on ) {
					++$student_total;
				}

				if ( $teacher_on && $student_on ) {
					++$overlap;
				}
			}
		}

		if ( $teacher_total <= 0 ) {
			unset( $img_teacher, $img_student );
			return 0.0;
		}

		$union = $teacher_total + $student_total - $overlap;
		if ( $union <= 0 ) {
			unset( $img_teacher, $img_student );
			return 0.0;
		}

		// Use IoU so both over-drawing and under-drawing are penalized.
		$ratio = $overlap / $union;

		unset( $img_teacher, $img_student );

		return max( 0.0, min( 1.0, (float) $ratio ) );
	}

	/**
	 * Process custom question-type answer when quiz is submitted (draw_image, etc.).
	 *
	 * @since 4.0.0
	 *
	 * @param array  $custom_answer_data  Array with given_answer and is_answer_was_correct.
	 * @param string $question_type      Question type.
	 * @param array  $answers            Answer data from request.
	 * @param object $question           Question object (reserved for filter compatibility).
	 * @param int    $question_id        Question ID (reserved for filter compatibility).
	 * @param int    $attempt_id         Quiz attempt ID (reserved for filter compatibility).
	 *
	 * @return array Modified custom_answer_data.
	 */
	public function process_custom_question_answer( $custom_answer_data, $question_type, $answers, $question, $question_id, $attempt_id ) {
		// Handle scale first: extract [answers][scale][value] and return.
		if ( QuizModel::QUESTION_TYPE_SCALE === $question_type ) {
			$given_answer = '';

			if ( is_array( $answers ) && isset( $answers['answers']['scale'] ) && is_array( $answers['answers']['scale'] ) ) {
				$raw   = $answers['answers']['scale'];
				$value = isset( $raw['value'] ) ? $raw['value'] : '';

				// The JavaScript already sends JSON, so we need to decode and re-encode to ensure proper format.
				if ( is_string( $value ) ) {
					$decoded = json_decode( stripslashes( $value ), true );
					if ( is_array( $decoded ) && isset( $decoded['value'] ) ) {
						// Validate and sanitize the numeric value.
						$numeric_value = (float) $decoded['value'];
						$given_answer  = wp_json_encode( array( 'value' => $numeric_value ) );
					}
				} elseif ( is_array( $value ) && isset( $value['value'] ) ) {
					$numeric_value = (float) $value['value'];
					$given_answer  = wp_json_encode( array( 'value' => $numeric_value ) );
				}
			}

			$custom_answer_data['given_answer']          = $given_answer;
			$custom_answer_data['is_answer_was_correct'] = false;

			return $custom_answer_data;
		}

		// Draw on image: store the mask.
		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE === $question_type ) {
			$given_answer = '';

			// Structure from POST: attempt[attempt_id][quiz_question][question_id][answers][mask].
			if ( is_array( $answers ) ) {
				if ( isset( $answers['answers'] ) && is_array( $answers['answers'] ) && isset( $answers['answers']['mask'] ) ) {
					$given_answer = wp_unslash( $answers['answers']['mask'] );
				} elseif ( isset( $answers['mask'] ) ) {
					$given_answer = wp_unslash( $answers['mask'] );
				}
			}

			$given_answer = is_string( $given_answer ) ? trim( $given_answer ) : '';

			if ( '' !== $given_answer ) {
				$given_answer = apply_filters( 'tutor_save_quiz_draw_image_mask', $given_answer, $question_type );
			}

			$custom_answer_data['given_answer']          = $given_answer;
			$custom_answer_data['is_answer_was_correct'] = false;

			return $custom_answer_data;
		}

		// Pin on image: store normalized point.
		if ( QuizModel::QUESTION_TYPE_PIN_IMAGE === $question_type ) {
			$given_answer = '';

			// Pin coordinates are posted under the answers.pin key in the quiz submission payload.
			if ( is_array( $answers ) && isset( $answers['answers']['pin'] ) && is_array( $answers['answers']['pin'] ) ) {
				$raw_pin = $answers['answers']['pin'];
			} elseif ( is_array( $answers ) && isset( $answers['pin'] ) && is_array( $answers['pin'] ) ) {
				$raw_pin = $answers['pin'];
			} else {
				$raw_pin = null;
			}

			if ( is_array( $raw_pin ) ) {
				// Hidden inputs submit '' until the student places a pin; casting '' to float becomes 0,
				// which wrongly grades as top-left. Only persist when both values are explicitly set.
				$x_raw = isset( $raw_pin['x'] ) ? trim( (string) $raw_pin['x'] ) : '';
				$y_raw = isset( $raw_pin['y'] ) ? trim( (string) $raw_pin['y'] ) : '';

				if ( '' !== $x_raw && '' !== $y_raw && is_numeric( $x_raw ) && is_numeric( $y_raw ) ) {
					$x = max( 0.0, min( 1.0, (float) $x_raw ) );
					$y = max( 0.0, min( 1.0, (float) $y_raw ) );

					$given_answer = wp_json_encode(
						array(
							'x' => $x,
							'y' => $y,
						)
					);
				}
			}

			$custom_answer_data['given_answer']          = $given_answer;
			$custom_answer_data['is_answer_was_correct'] = false;

			return $custom_answer_data;
		}

		// Coordinates: store selected points as JSON.
		if ( QuizModel::QUESTION_TYPE_COORDINATES === $question_type ) {
			$given_answer = '';
			$raw_coord    = null;

			if ( is_array( $answers ) && isset( $answers['answers']['coordinates'] ) && is_array( $answers['answers']['coordinates'] ) ) {
				$raw_coord = $answers['answers']['coordinates'];
			} elseif ( is_array( $answers ) && isset( $answers['coordinates'] ) && is_array( $answers['coordinates'] ) ) {
				$raw_coord = $answers['coordinates'];
			}

			if ( is_array( $raw_coord ) && isset( $raw_coord['points'] ) ) {
				$decoded_points = json_decode( stripslashes( (string) $raw_coord['points'] ), true );
				if ( is_array( $decoded_points ) ) {
					$normalized = array();
					foreach ( $decoded_points as $point ) {
						if ( ! is_array( $point ) || ! isset( $point['x'], $point['y'] ) ) {
							continue;
						}
						$normalized[] = array(
							'x' => max( -1000, min( 1000, (int) $point['x'] ) ),
							'y' => max( -1000, min( 1000, (int) $point['y'] ) ),
						);
						if ( count( $normalized ) >= 5 ) {
							break;
						}
					}
					$given_answer = ! empty( $normalized ) ? wp_json_encode( array( 'points' => $normalized ) ) : '';
				}
			} elseif ( is_array( $raw_coord ) && isset( $raw_coord['x'], $raw_coord['y'] ) ) {
				// Backward compatibility with x/y pair payload.
				$x            = max( -1000, min( 1000, (int) $raw_coord['x'] ) );
				$y            = max( -1000, min( 1000, (int) $raw_coord['y'] ) );
				$given_answer = wp_json_encode(
					array(
						'points' => array(
							array(
								'x' => $x,
								'y' => $y,
							),
						),
					)
				);
			}

			$custom_answer_data['given_answer']          = $given_answer;
			$custom_answer_data['is_answer_was_correct'] = false;

			return $custom_answer_data;
		}

		// Puzzle: store puzzle completion payload as JSON.
		if ( QuizModel::QUESTION_TYPE_PUZZLE === $question_type ) {
			$given_answer = '';
			$raw_puzzle   = null;

			if ( is_array( $answers ) && isset( $answers['answers']['puzzle']['value'] ) ) {
				$raw_puzzle = $answers['answers']['puzzle']['value'];
			} elseif ( is_array( $answers ) && isset( $answers['puzzle']['value'] ) ) {
				$raw_puzzle = $answers['puzzle']['value'];
			}

			if ( is_string( $raw_puzzle ) && '' !== trim( $raw_puzzle ) ) {
				$decoded = json_decode( stripslashes( $raw_puzzle ), true );
				if ( is_array( $decoded ) ) {
					$grid_size    = isset( $decoded['grid_size'] ) ? (int) $decoded['grid_size'] : 4;
					$grid_size    = max( 2, min( 7, $grid_size ) );
					$total_pieces = $grid_size * $grid_size;
					$token        = isset( $decoded['token'] ) ? (string) $decoded['token'] : '';
					$locks        = isset( $decoded['locks'] ) && is_array( $decoded['locks'] ) ? $decoded['locks'] : array();
					$snapshot     = isset( $decoded['playground_snapshot'] ) ? trim( (string) $decoded['playground_snapshot'] ) : '';

					$token_valid  = $this->verify_puzzle_session_token( $token, (int) $attempt_id, (int) $question_id, $grid_size, $total_pieces );
					$locks_result = $this->validate_puzzle_locks( $locks, $total_pieces );

					if ( $token_valid && ! empty( $locks_result['valid'] ) ) {
						$snapshot_file = '';
						if ( '' !== $snapshot && 0 === strpos( $snapshot, 'data:image/' ) && false !== strpos( $snapshot, ';base64,' ) ) {
							$max_snapshot_length = 1024 * 1024 * 2;
							if ( strlen( $snapshot ) <= $max_snapshot_length ) {
								$snapshot_file = apply_filters( 'tutor_save_quiz_draw_image_mask', $snapshot, QuizModel::QUESTION_TYPE_PUZZLE );
							}
						}

						$given_answer = wp_json_encode(
							array(
								'is_completed'             => (bool) $locks_result['is_completed'],
								'is_verified'              => true,
								'locked_count'             => (int) $locks_result['locked_count'],
								'total_pieces'             => $total_pieces,
								'grid_size'                => $grid_size,
								'locks'                    => $locks_result['locks'],
								'playground_snapshot_file' => $snapshot_file,
							)
						);
					}
				}
			}

			$custom_answer_data['given_answer']          = $given_answer;
			$custom_answer_data['is_answer_was_correct'] = false;

			return $custom_answer_data;
		}

		return $custom_answer_data;
	}

	/**
	 * Adjust total marks for custom question types after grading (draw_image, pin_image).
	 *
	 * Hooked to tutor_filter_quiz_total_marks which runs after grading, so achieved_mark
	 * is available. For these types, core adds 0 to total_marks; we add the graded achieved_mark.
	 *
	 * @since 4.0.0
	 *
	 * @param float      $total_marks   Current total marks.
	 * @param int        $question_id   Question ID.
	 * @param string     $question_type Question type.
	 * @param int        $user_id       User ID.
	 * @param int        $attempt_id    Attempt ID.
	 * @param array|null $answers_data  Answer data with achieved_mark (optional 6th param).
	 *
	 * @return float Adjusted total marks.
	 */
	public function adjust_custom_question_total_marks( $total_marks, $question_id, $question_type, $user_id, $attempt_id, $answers_data = null ) {
		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE !== $question_type && QuizModel::QUESTION_TYPE_SCALE !== $question_type && QuizModel::QUESTION_TYPE_PIN_IMAGE !== $question_type && QuizModel::QUESTION_TYPE_COORDINATES !== $question_type && QuizModel::QUESTION_TYPE_PUZZLE !== $question_type ) {
			return $total_marks;
		}

		if ( ! is_array( $answers_data ) ) {
			return $total_marks;
		}

		// Core adds 0 for custom types; add the achieved_mark from Pro grading.
		$total_marks += (float) ( $answers_data['achieved_mark'] ?? 0 );

		return $total_marks;
	}

	/**
	 * Attach attempt-answer fields that attempt-details templates read from `$question`.
	 *
	 * Pro templates use `$question->given_answer` (and scale review uses `is_correct` on the
	 * same object), but `given_answer` lives on the attempt answer row, not `tutor_quiz_questions`.
	 *
	 * @since 4.0.0
	 *
	 * @param object      $question       Question row from tutor_quiz_questions.
	 * @param object|null $attempt_answer Attempt answer row (e.g. from get_quiz_answers_by_attempt_id()).
	 *
	 * @return object|null The question object, or null if `$question` is not an object.
	 */
	private function hydrate_question_with_attempt_answer_for_review( $question, $attempt_answer ) {
		if ( ! is_object( $question ) ) {
			return null;
		}

		if ( is_object( $attempt_answer ) ) {
			if ( property_exists( $attempt_answer, 'given_answer' ) ) {
				$question->given_answer = $attempt_answer->given_answer;
			}
			if ( property_exists( $attempt_answer, 'is_correct' ) ) {
				$question->is_correct = $attempt_answer->is_correct;
			}
		}

		return $question;
	}

	/**
	 * Render given answer for custom question types in attempt details (draw_image, scale, pin_image).
	 *
	 * @since 4.0.0
	 *
	 * @param object $answer Answer object.
	 *
	 * @return void
	 */
	public function render_custom_question_given_answer( $answer ) {
		$question_type = $answer->question_type ?? '';
		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE !== $question_type && QuizModel::QUESTION_TYPE_SCALE !== $question_type && QuizModel::QUESTION_TYPE_PIN_IMAGE !== $question_type && QuizModel::QUESTION_TYPE_COORDINATES !== $question_type && QuizModel::QUESTION_TYPE_PUZZLE !== $question_type ) {
			return;
		}

		$question = QuizModel::get_quiz_question_by_id( (int) ( $answer->question_id ?? 0 ) );
		if ( ! $question ) {
			return;
		}

		$question = $this->hydrate_question_with_attempt_answer_for_review( $question, $answer );
		if ( ! $question ) {
			return;
		}

		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.draw-image',
				array(
					'question'                 => $question,
					'attempt_answer'           => $answer,
					'draw_image_review_column' => 'given',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_PIN_IMAGE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.pin-image',
				array(
					'question'                => $question,
					'attempt_answer'          => $answer,
					'pin_image_review_column' => 'given',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_SCALE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.scale',
				array(
					'question'            => $question,
					'attempt_answer'      => $answer,
					'scale_review_column' => 'given',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_COORDINATES === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.coordinates',
				array(
					'question'                  => $question,
					'attempt_answer'            => $answer,
					'coordinates_review_column' => 'given',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_PUZZLE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.puzzle',
				array(
					'question'             => $question,
					'attempt_answer'       => $answer,
					'puzzle_review_column' => 'given',
				)
			);
		}
	}

	/**
	 * Render correct answer for custom question types in attempt details (draw_image, scale, pin_image).
	 *
	 * @since 4.0.0
	 *
	 * @param object $answer Answer object.
	 *
	 * @return void
	 */
	public function render_custom_question_correct_answer( $answer ) {
		$question_type = $answer->question_type ?? '';
		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE !== $question_type && QuizModel::QUESTION_TYPE_SCALE !== $question_type && QuizModel::QUESTION_TYPE_PIN_IMAGE !== $question_type && QuizModel::QUESTION_TYPE_COORDINATES !== $question_type && QuizModel::QUESTION_TYPE_PUZZLE !== $question_type ) {
			return;
		}

		$question = QuizModel::get_quiz_question_by_id( (int) ( $answer->question_id ?? 0 ) );
		if ( ! $question ) {
			return;
		}

		$question = $this->hydrate_question_with_attempt_answer_for_review( $question, $answer );
		if ( ! $question ) {
			return;
		}

		if ( QuizModel::QUESTION_TYPE_DRAW_IMAGE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.draw-image',
				array(
					'question'                 => $question,
					'attempt_answer'           => $answer,
					'draw_image_review_column' => 'correct',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_PIN_IMAGE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.pin-image',
				array(
					'question'                => $question,
					'attempt_answer'          => $answer,
					'pin_image_review_column' => 'correct',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_SCALE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.scale',
				array(
					'question'            => $question,
					'attempt_answer'      => $answer,
					'scale_review_column' => 'correct',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_COORDINATES === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.coordinates',
				array(
					'question'                  => $question,
					'attempt_answer'            => $answer,
					'coordinates_review_column' => 'correct',
				)
			);
			return;
		}

		if ( QuizModel::QUESTION_TYPE_PUZZLE === $question_type ) {
			$this->load_pro_quiz_template(
				'shared.components.quiz.attempt-details.questions.puzzle',
				array(
					'question'             => $question,
					'attempt_answer'       => $answer,
					'puzzle_review_column' => 'correct',
				)
			);
		}
	}

	/**
	 * Render Pro-only attempt-details partials right after core template call.
	 *
	 * Core intentionally skips addon-only templates during tutor_load_template().
	 * This hook injects Pro templates in the same location where free templates render,
	 * ensuring question content appears before answer explanation.
	 *
	 * @since 4.0.0
	 *
	 * @param object $question          Question object.
	 * @param string $question_template Question partial slug.
	 * @param int    $index             Question index.
	 *
	 * @return void
	 */
	public function render_pro_attempt_details_inside_question_wrapper( $question, $question_template, $index ) {
		if ( ! is_object( $question ) ) {
			return;
		}

		$pro_partials = array(
			'scale'       => 'shared.components.quiz.attempt-details.questions.scale',
			'draw-image'  => 'shared.components.quiz.attempt-details.questions.draw-image',
			'pin-image'   => 'shared.components.quiz.attempt-details.questions.pin-image',
			'coordinates' => 'shared.components.quiz.attempt-details.questions.coordinates',
			'puzzle'      => 'shared.components.quiz.attempt-details.questions.puzzle',
		);

		if ( ! isset( $pro_partials[ $question_template ] ) ) {
			return;
		}

		$this->load_pro_quiz_template(
			$pro_partials[ $question_template ],
			array(
				'question' => $question,
				'index'    => (int) $index,
			)
		);
	}

	/**
	 * Provide unsupported question types.
	 *
	 * @since 4.0.0
	 *
	 * @param array $types array of question types.
	 *
	 * @return array
	 */
	public function unsupported_quiz_question_types( $types ) {
		if ( tutor_utils()->is_legacy_learning_mode() ) {
			$modern_types = array_keys( QuizModel::get_modern_mode_quiz_types() );
			return array_merge( $types, $modern_types );
		}

		return $types;
	}

	/**
	 * Grade partial and negative marking for supported question types.
	 *
	 * @since 4.1.0
	 *
	 * @param array  $answers_data  Answers data.
	 * @param int    $question_id   Question ID.
	 * @param string $question_type Question type.
	 * @param int    $user_id       User ID.
	 * @param int    $attempt_id    Attempt ID.
	 *
	 * @return array
	 */
	public function grade_partial_negative_answer( $answers_data, $question_id, $question_type, $user_id, $attempt_id ) {
		return QuizGrader::grade_answer_data( $answers_data, $question_id, $question_type, $user_id, $attempt_id );
	}

	/**
	 * Adjust total marks for partial and negative marking.
	 *
	 * @since 4.1.0
	 *
	 * @param float       $total_marks   Total marks.
	 * @param int         $question_id   Question ID.
	 * @param string|null $question_type Question type.
	 *
	 * @return float
	 */
	public function adjust_partial_negative_total_marks( $total_marks, $question_id, $question_type = null ) {
		return QuizGrader::adjust_total_marks( $total_marks, $question_id, $question_type );
	}

	/**
	 * Ensure final attempt earned marks are non-negative.
	 *
	 * Individual question scores can be negative due to negative marking, but a
	 * student's final earned score on a quiz can never be less than 0.
	 *
	 * @since 4.1.0
	 *
	 * @param int $attempt_id Quiz attempt ID.
	 *
	 * @return void
	 */
	public function clamp_attempt_earned_marks( $attempt_id ) {
		$attempt = tutor_utils()->get_attempt( $attempt_id );
		if ( is_object( $attempt ) && (float) $attempt->earned_marks <= 0 ) {
			QueryHelper::update(
				'tutor_quiz_attempts',
				array( 'earned_marks' => 0.0 ),
				array( 'attempt_id' => (int) $attempt_id )
			);
			QuizModel::update_attempt_result( (int) $attempt_id );
		}
	}

	/**
	 * Turn-off confirmation configuration for grading toggles.
	 *
	 * Localized to the settings admin page so the Free plugin's generic
	 * toggle turn-off confirmation mechanism stays config-driven.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	private function get_turnoff_confirmations() {
		return array(
			'enable_quiz_partial_marking'  => array(
				'title'   => __( 'Turn off Partial marking?', 'tutor-pro' ),
				'message' => __( 'Partial marking will be disabled for new quizzes. Quizzes that already have it enabled will continue to work as they are.', 'tutor-pro' ),
				'cancel'  => __( 'No, keep it', 'tutor-pro' ),
				'confirm' => __( 'Yes, turn off', 'tutor-pro' ),
			),
			'enable_quiz_negative_marking' => array(
				'title'   => __( 'Turn off Negative marking?', 'tutor-pro' ),
				'message' => __( 'This hides negative marking across all quizzes. Your question-level values stay saved until you turn it back on.', 'tutor-pro' ),
				'cancel'  => __( 'No, keep it', 'tutor-pro' ),
				'confirm' => __( 'Yes, turn off', 'tutor-pro' ),
			),
		);
	}

	/**
	 * Localize grading toggle turn-off confirmations on the settings page.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function localize_turnoff_confirmations() {
		if ( 'tutor_settings' !== Input::get( 'page', '' ) ) {
			return;
		}

		wp_localize_script( 'tutor-admin', 'tutorTurnoffConfirm', $this->get_turnoff_confirmations() );
	}

	/**
	 * Add Grading and Automatic Assessment options.
	 *
	 * @since 4.1.0
	 *
	 * @param array $attr Options attributes.
	 * @return array
	 */
	public function add_grading_options( $attr ) {
		$automatic_assessment_block = array(
			'label'      => __( 'Automatic Assessment', 'tutor-pro' ),
			'slug'       => 'automatic_assessment',
			'block_type' => 'uniform',
			'fields'     => array(
				array(
					'key'     => 'enable_quiz_partial_marking',
					'type'    => 'toggle_switch',
					'label'   => __( 'Partial Marking', 'tutor-pro' ),
					'default' => 'off',
					'desc'    => __( 'Award points for each correctly answered part of a multi-part question', 'tutor-pro' ),
					'tooltip' => __( 'Applies to question types with multiple answer parts, including Matching, Ordering, Image Matching, Fill in the Blanks, and multiple-answer questions.', 'tutor-pro' ),
				),
				array(
					'key'           => 'enable_quiz_negative_marking',
					'type'          => 'toggle_switch',
					'label'         => __( 'Negative Marking', 'tutor-pro' ),
					'default'       => 'off',
					'desc'          => __( 'Deducts points for each wrong answer once enabled', 'tutor-pro' ),
					'toggle_fields' => 'quiz_negative_mark_type,quiz_negative_mark_value',
					'tooltip'       => __( 'Individual question scores can fall below 0 with negative marking. However, the final quiz score will never be lower than 0.', 'tutor-pro' ),
				),
				array(
					'key'            => 'quiz_negative_mark_type',
					'type'           => 'select',
					'label'          => __( 'Penalty Unit', 'tutor-pro' ),
					'desc'           => __( 'Choose whether penalties are deducted as a percentage or fixed points', 'tutor-pro' ),
					'default'        => 'percent',
					'select_options' => false,
					'options'        => array(
						'percent' => __( 'Percentage (%)', 'tutor-pro' ),
						'fixed'   => __( 'Points (pts)', 'tutor-pro' ),
					),
				),
				array(
					'key'     => 'quiz_negative_mark_value',
					'type'    => 'number',
					'label'   => __( 'Default Penalty', 'tutor-pro' ),
					'default' => 15,
				),
			),
		);

		if ( isset( $attr['tutor_gradebook'] ) ) {
			$attr['tutor_gradebook']['label']    = __( 'Grading', 'tutor-pro' );
			$attr['tutor_gradebook']['desc']     = __( 'Grading Settings', 'tutor-pro' );
			$attr['tutor_gradebook']['slug']     = 'tutor_gradebook';
			$attr['tutor_gradebook']['blocks'][] = $automatic_assessment_block;
		} else {
			$attr['tutor_gradebook'] = array(
				'label'    => __( 'Grading', 'tutor-pro' ),
				'slug'     => 'tutor_gradebook',
				'desc'     => __( 'Grading Settings', 'tutor-pro' ),
				'template' => 'basic',
				'icon'     => 'tutor-icon-grade-book',
				'blocks'   => array(
					$automatic_assessment_block,
				),
			);
		}

		return $attr;
	}

	/**
	 * Reject an out-of-range negative marking penalty before options are saved.
	 *
	 * Only enforced while negative marking is enabled: the stored value is
	 * ignored entirely when the feature is off, so blocking the save there would
	 * lock the whole settings form for a value that has no effect.
	 *
	 * @since 4.1.0
	 *
	 * @param array $options option submitted to save.
	 *
	 * @return void
	 */
	public function validate_negative_mark_settings( $options ) {
		if ( 'on' !== ( $options['enable_quiz_negative_marking'] ?? 'off' ) ) {
			return;
		}

		$value = (float) ( $options['quiz_negative_mark_value'] ?? 0 );

		if ( $value <= 0 ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Penalty must be greater than 0', 'tutor-pro' ),
				)
			);
		}

		if ( 'percent' === ( $options['quiz_negative_mark_type'] ?? 'percent' ) && $value > 100 ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Penalty cannot be greater than 100%', 'tutor-pro' ),
				)
			);
		}
	}

	/**
	 * Add Pro-only quiz scoring defaults without changing free quiz behaviour.
	 *
	 * @since 4.1.0
	 *
	 * @param array $settings Quiz settings.
	 *
	 * @return array
	 */
	public function add_quiz_grading_defaults( $settings ) {
		$settings['enable_partial_marking']  = $settings['enable_partial_marking'] ?? '0';
		$settings['enable_negative_marking'] = $settings['enable_negative_marking'] ?? '0';
		$settings['negative_mark_type']      = in_array( $settings['negative_mark_type'] ?? '', array( 'percent', 'fixed' ), true ) ? $settings['negative_mark_type'] : 'percent';
		$settings['negative_mark_value']     = max( 0, (float) ( $settings['negative_mark_value'] ?? 0 ) );

		return $settings;
	}

	/**
	 * Keep per-quiz grading markings locked to their stored value when the
	 * admin has disabled partial or negative marking globally.
	 *
	 * Tampered payload changes to these keys are discarded before the quiz
	 * options are persisted.
	 *
	 * @since 4.1.0
	 *
	 * @param array $quiz_settings Quiz settings about to be saved.
	 * @param int   $quiz_id       Quiz post ID.
	 *
	 * @return array
	 */
	public function filter_quiz_settings( array $quiz_settings, int $quiz_id ): array {
		$admin_settings = Options_V2::get_only(
			array(
				'enable_quiz_partial_marking',
				'enable_quiz_negative_marking',
			)
		);

		$stored_settings = tutor_utils()->get_quiz_option( $quiz_id );

		if ( 'on' !== ( $admin_settings['enable_quiz_partial_marking'] ?? 'off' ) ) {
			$quiz_settings['enable_partial_marking'] = $stored_settings['enable_partial_marking'] ?? '0';
		}

		if ( 'on' !== ( $admin_settings['enable_quiz_negative_marking'] ?? 'off' ) ) {
			$quiz_settings['enable_negative_marking'] = $stored_settings['enable_negative_marking'] ?? '0';
			$quiz_settings['negative_mark_type']      = $stored_settings['negative_mark_type'] ?? 'percent';
			$quiz_settings['negative_mark_value']     = $stored_settings['negative_mark_value'] ?? 0;
		}

		return $quiz_settings;
	}

	/**
	 * Inject Pro quiz scoring parameters into the Learning Area quiz summary table.
	 *
	 * @since 4.1.0
	 *
	 * @param array $quiz_summary Quiz summary rows.
	 * @param int   $quiz_id      Quiz post ID.
	 *
	 * @return array
	 */
	public function add_quiz_summary_parameters( $quiz_summary, $quiz_id ) {
		if ( ! $quiz_id || ! is_array( $quiz_summary ) ) {
			return $quiz_summary;
		}

		$quiz_options = tutor_utils()->get_quiz_option( $quiz_id, '', array() );
		$rows         = array();

		if ( '1' === (string) ( $quiz_options['enable_partial_marking'] ?? '0' ) ) {
			$rows[] = array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-gap-3 tutor-items-center">' . SvgIcon::make()->name( Icon::PARTIAL )->size( 20 )->get() . esc_html__( 'Partial marking', 'tutor-pro' ) . '</div>',
					),
					array( 'content' => esc_html__( 'Enabled', 'tutor-pro' ) ),
				),
			);
		}

		if ( '1' === (string) ( $quiz_options['enable_negative_marking'] ?? '0' ) ) {
			$negative_value = max( 0, (float) ( $quiz_options['negative_mark_value'] ?? 0 ) );
			$negative_type  = $quiz_options['negative_mark_type'] ?? 'percent';
			$penalty_label  = $this->get_negative_marking_summary_label( $quiz_id, $negative_type, $negative_value );

			$rows[] = array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-gap-3 tutor-items-center">' . SvgIcon::make()->name( Icon::MINUS_CIRCLE )->size( 20 )->get() . esc_html__( 'Negative marking', 'tutor-pro' ) . '</div>',
					),
					array( 'content' => esc_html( $penalty_label ) ),
				),
			);
		}

		if ( empty( $rows ) ) {
			return $quiz_summary;
		}

		$insert_index = null;

		foreach ( $quiz_summary as $index => $row ) {
			if ( isset( $row['key'] ) && 'passing_grade' === $row['key'] ) {
				$insert_index = $index;
				break;
			}
		}

		if ( null !== $insert_index ) {
			array_splice( $quiz_summary, $insert_index, 0, $rows );
		} else {
			$quiz_summary = array_merge( $quiz_summary, $rows );
		}

		return $quiz_summary;
	}

	/**
	 * Render partial and negative marking rows on the classic quiz start screen.
	 *
	 * @since 4.1.0
	 *
	 * @param int $quiz_id Quiz post ID.
	 *
	 * @return void
	 */
	public function render_marking_info( $quiz_id ) {
		$quiz_options = tutor_utils()->get_quiz_option( $quiz_id, '', array() );
		$rows         = array();

		if ( '1' === (string) ( $quiz_options['enable_partial_marking'] ?? '0' ) ) {
			$rows[] = array( __( 'Partial marking:', 'tutor-pro' ), __( 'Enabled', 'tutor-pro' ) );
		}

		if ( '1' === (string) ( $quiz_options['enable_negative_marking'] ?? '0' ) ) {
			$rows[] = array(
				__( 'Negative marking:', 'tutor-pro' ),
				$this->get_negative_marking_summary_label(
					$quiz_id,
					$quiz_options['negative_mark_type'] ?? 'percent',
					max( 0, (float) ( $quiz_options['negative_mark_value'] ?? 0 ) )
				),
			);
		}

		foreach ( $rows as $row ) {
			printf(
				'<div class="tutor-quiz-info"><span class="tutor-fs-6 tutor-color-muted">%s</span> <span class="tutor-fs-6 tutor-color-black">%s</span></div>',
				esc_html( $row[0] ),
				esc_html( $row[1] )
			);
		}
	}

	/**
	 * Get the Learning Area negative marking description.
	 *
	 * @since 4.1.0
	 *
	 * @param int    $quiz_id        Quiz post ID.
	 * @param string $negative_type  Fixed or percent.
	 * @param float  $negative_value Configured penalty value.
	 *
	 * @return string
	 */
	private function get_negative_marking_summary_label( $quiz_id, $negative_type, $negative_value ) {
		if ( 'fixed' === $negative_type ) {
			/* translators: %s: fixed negative marking penalty. */
			return sprintf( __( '%s points per incorrect answer', 'tutor-pro' ), number_format_i18n( $negative_value, 2 ) );
		}

		$bounds = (array) QueryHelper::query(
			'tutor_quiz_questions',
			array(
				'select' => 'MIN(question_mark) AS min_mark, MAX(question_mark) AS max_mark',
				'where'  => array( 'quiz_id' => (int) $quiz_id ),
				'single' => true,
				'output' => 'ARRAY_A',
			)
		);

		$minimum_penalty = round( ( $negative_value / 100 ) * (float) ( $bounds['min_mark'] ?? 0 ), 2 );
		$maximum_penalty = round( ( $negative_value / 100 ) * (float) ( $bounds['max_mark'] ?? 0 ), 2 );

		if ( $minimum_penalty !== $maximum_penalty ) {
			return sprintf(
				/* translators: 1: minimum penalty, 2: maximum penalty. */
				__( '%1$s – %2$s points per incorrect answer', 'tutor-pro' ),
				number_format_i18n( $minimum_penalty, 2 ),
				number_format_i18n( $maximum_penalty, 2 )
			);
		}

		/* translators: %s: negative marking penalty. */
		return sprintf( __( '%s points per incorrect answer', 'tutor-pro' ), number_format_i18n( $minimum_penalty, 2 ) );
	}
}
