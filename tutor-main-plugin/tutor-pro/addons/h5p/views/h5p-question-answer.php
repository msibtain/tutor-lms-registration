<?php
/**
 * H5P Question Answer Area
 *
 * @package TutorPro\Addons
 * @subpackage H5P\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


<div id="quiz-matching-ans-area" hidden class="quiz-question-ans-choice-area tutor-mt-40 question-type-<?php echo esc_attr( $question_type ); ?>">
	<input class="" name="<?php echo 'attempt[' . esc_attr( $is_started_quiz->attempt_id ) . '][quiz_question][' . esc_attr( $question->question_id ) . '][]'; ?>" />
</div>