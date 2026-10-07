<?php
/**
 * Student list template
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

use Tutor\Components\Button;
use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\CourseFilter;
use Tutor\Components\DateFilter;
use Tutor\Components\EmptyState;
use Tutor\Components\Pagination;
use Tutor\Components\SearchFilter;
use Tutor\Components\Sorting;
use Tutor\Components\Table;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;


$current_url         = tutor_utils()->get_tutor_dashboard_page_permalink( 'analytics/students' );
$user                = wp_get_current_user();
$current_page        = max( 1, Input::get( 'current_page', 0, Input::TYPE_INT ) );
$pagination_per_page = tutor_utils()->get_option( 'pagination_per_page' );
$offset              = ( $pagination_per_page * $current_page ) - $pagination_per_page;
$order_by            = Input::get( 'order' );

$course_id     = Input::get( 'course-id', 0, Input::TYPE_INT );
$order_filter  = QueryHelper::get_valid_sort_order( $order_by ?? 'DESC' );
$search_filter = Input::get( 'search', '' );
$start_date    = Input::get( 'start_date', '' );
$end_date      = Input::get( 'end_date', '' );
$date_range    = ! empty( $start_date ) && ! empty( $end_date )
				? array(
					'from' => $start_date,
					'to'   => $end_date,
				) : array();

$students      = tutor_utils()->get_students_by_instructor( $user->ID, $offset, $pagination_per_page, $search_filter, $course_id, '', 'user.user_registered', $order_filter, array( 'publish', 'private' ), $date_range );
$courses       = CourseModel::get_courses_by_instructor( $user->ID, array( 'publish', 'private' ) );
$total_courses = ! empty( $courses ) && empty( $course_id ) ? count( $courses ) : $students['total_students'];

$filter_count      = count( array_filter( array( $course_id, $start_date, $end_date, $order_by, $search_filter ) ) );
$clear_button_text = $filter_count > 1 ? __( 'Clear All', 'tutor-pro' ) : __( 'Clear', 'tutor-pro' );
?>
<div class="tutor-analytics-students tutor-surface-l1 tutor-mt-5 tutor-border tutor-rounded-2xl tutor-overflow-hidden">
	<!-- Filters -->
	<div class="tutor-analytics-students-filter-options">
		<?php
		CourseFilter::make()
			->courses( $courses )
			->count( $total_courses )
			->variant( Variant::LINK )
			->render()
		?>

		<!-- Search, Sort and Clear All -->
		<div class="tutor-flex tutor-flex-row tutor-items-center tutor-gap-3">
			<?php
			if ( $filter_count > 0 ) {
				Button::make()
				->tag( 'a' )
				->attr( 'href', tutor_utils()->tutor_dashboard_url() . 'analytics/students' )
				->attr( 'class', 'tutor-text-brand' )
				->label( $clear_button_text )
				->variant( Variant::LINK )
				->render();
			}

			SearchFilter::make()
				->form_id( 'tutor-analytics-students-form' )
				->placeholder( __( 'Search', 'tutor-pro' ) )
				->action( $current_url )
				->size( Size::SMALL )
				->render();
			?>
		</div>

		<?php
		DateFilter::make()
			->type( DateFilter::TYPE_RANGE )
			->trigger_size( Size::X_SMALL )
			->placement( DateFilter::PLACEMENT_BOTTOM_END )
			->hide_initial_label()
			->render();
		Sorting::make()->order( $order_filter )->render();
		?>
		<!-- End Of Search and Sort -->
	</div>
	<!-- End Of Filters -->

	<?php
	if ( count( $students['students'] ) ) :
			$headings = array_map(
				fn( $content ) => array(
					'content' => $content,
				),
				array(
					esc_html__( 'Student Info', 'tutor-pro' ),
					esc_html__( 'Registration Date', 'tutor-pro' ),
					esc_html__( 'Course Enrolled', 'tutor-pro' ),
					'',
				)
			);

			$registration_date_label = sprintf(
				'<span class="tutor-analytics-students-mobile-label">%s</span>',
				esc_html__( 'Registration Date:', 'tutor-pro' )
			);
			$course_taken_label      = sprintf(
				'<span class="tutor-analytics-students-mobile-label">%s</span>',
				esc_html__( 'Course Taken:', 'tutor-pro' )
			);
			$contents                = array();

		foreach ( $students['students'] as $student ) {
			$registration_date   = tutor_i18n_get_formated_date( $student->user_registered );
			$student_table       = TUTOR_REPORT()->path . 'templates/elements/student-table.php';
			$student_details_url = tutor_utils()->tutor_dashboard_url() . "analytics/student-details?student_id=$student->ID";

			$contents[] = array(
				'columns' => array(
					array(
						'content' => get_template_buffer(
							$student_table,
							array(
								'student'  => $student,
								'template' => 'student_info',
							),
							false
						),
					),
					array( 'content' => $registration_date_label . sprintf( '<span class="tutor-text-primary tutor-font-regular tutor-tiny">%s</span>', esc_html( $registration_date ) ) ),
					array( 'content' => $course_taken_label . sprintf( '<span class="tutor-text-primary tutor-font-medium tutor-tiny">%s</span>', esc_html( $student->course_taken ) ) ),
					array(
						'content' => sprintf(
							'<div class="tutor-analytics-list-buttons">
								<a href="%s" class="tutor-btn tutor-btn-primary tutor-btn-x-small">
									<span class="tutor-text-primary-inverse">%s</span>
								</a>
							</div>',
							esc_url( $student_details_url ),
							esc_html__( 'Details', 'tutor-pro' ),
						),
					),
				),
			);
		}
		?>
		<div class="tutor-table-wrapper">
			<?php Table::make()->headings( $headings )->contents( $contents )->render(); ?>
		</div>
	<?php else : ?>
		<?php
			EmptyState::make()
				->title( __( 'No Students Found!', 'tutor-pro' ) )
				->render();
		?>
	<?php endif; ?>
</div>

<!-- Pagination -->
<?php
Pagination::make()
	->attr( 'class', 'tutor-mt-7' )
	->current( $current_page )
	->total( $students['total_students'] )
	->limit( $pagination_per_page )
	->render();
?>
