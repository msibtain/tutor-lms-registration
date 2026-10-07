<?php
/**
 * Student details template
 *
 * @package TutorPro\Addon
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

use TUTOR\Icon;
use TUTOR\Input;
use Tutor\Components\Modal;
use Tutor\Components\Button;
use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\Table;
use TUTOR_PRO\ProgressReset;

$user            = wp_get_current_user();
$student_id      = Input::get( 'student_id', 0 );
$student_details = get_userdata( $student_id );
$student_meta    = get_user_meta( $student_id );
if ( ! $student_id || ! $student_details ) {
	esc_html_e( 'Invalid student', 'tutor-pro' );
	return;
}
$courses         = tutor_utils()->get_courses_by_student_instructor_id( $student_id, $user->ID, array( 'publish', 'private' ) );
$back_button_url = tutor_utils()->tutor_dashboard_url() . 'analytics/students';
?>

<div class="tutor-analytics-student-details tutor-user-profile">
	<?php
	Button::make()
		->tag( 'a' )
		->variant( Variant::SECONDARY )
		->size( Size::SMALL )
		->label( __( 'Back', 'tutor-pro' ) )
		->icon( Icon::ARROW_LEFT_2 )
		->flip_rtl()
		->attr( 'href', $back_button_url )
		->attr( 'class', 'tutor-mb-6' )
		->render();
	?>

	<?php tutor_load_template( 'user-profile' ); ?>

	<?php
	if ( count( $courses ) ) :
		$headings = array_map(
			fn( $content ) => array(
				'content' => $content,
			),
			array(
				esc_html__( 'Course Name', 'tutor-pro' ),
				esc_html__( 'Progress', 'tutor-pro' ),
				esc_html__( 'Actions', 'tutor-pro' ),
			)
		);

		$course_id             = array_column( $courses, 'ID' );
		$student_details_table = TUTOR_REPORT()->path . 'templates/elements/student-details-table.php';

		$contents = array();

		foreach ( $courses as $course ) {
			$course_progress = tutor_utils()->get_course_completed_percent( $course->ID, $student_id );
			$enrolled_data   = tutor_utils()->get_enrolled_data( $student_id, $course->ID );

			ob_start();
			do_action( 'tutor_enrollment_actions', $enrolled_data->ID, $course->ID, $student_id, $course_progress, 'dashboard' );
			$action_content = ob_get_clean();

			$contents[] = array(
				'columns' => array(
					array(
						'content' => get_template_buffer(
							$student_details_table,
							array(
								'course'   => $course,
								'template' => 'course_info',
							),
							false
						),
					),
					array(
						'content' => get_template_buffer(
							$student_details_table,
							array(
								'course'   => $course,
								'template' => 'progress',
							),
							false
						),
					),
					array(
						'content' => $action_content,
					),
				),
			);
		}
		?>

		<div class="tutor-table-wrapper tutor-mt-5 tutor-rounded-2xl tutor-border">
			<?php Table::make()->headings( $headings )->contents( $contents )->render(); ?>
		</div>

		<?php
		if ( ProgressReset::can_reset_progress() ) {
			Modal::make()
				->id( 'tutor-reset-progress-modal' )
				->template( tutor_pro()->path . '/views/modals/reset-progress-modal-frontend.php' )
				->render();
		}

		Modal::make()
			->id( 'modal-course-overview' )
			->template( TUTOR_REPORT()->path . 'templates/course_progress.php' )
			->render();
		?>
	<?php else : ?>
		<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
	<?php endif; ?>
</div>
