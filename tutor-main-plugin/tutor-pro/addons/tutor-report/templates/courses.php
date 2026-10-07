<?php
/**
 * Course report page
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

use TUTOR_REPORT\Analytics;
use Tutor\Components\Constants\Size;
use Tutor\Components\EmptyState;
use Tutor\Components\Pagination;
use Tutor\Components\SearchFilter;
use Tutor\Components\Sorting;
use Tutor\Components\Table;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;

$current_url         = tutor_utils()->get_tutor_dashboard_page_permalink( 'analytics/courses' );
$user                = wp_get_current_user();
$current_page        = max( 1, Input::get( 'current_page', 0, Input::TYPE_INT ) );
$pagination_per_page = tutor_utils()->get_option( 'pagination_per_page' );
$offset              = ( $pagination_per_page * $current_page ) - $pagination_per_page;

$order_filter  = QueryHelper::get_valid_sort_order( Input::get( 'order', '' ) );
$search_filter = Input::get( 'search', '', Input::TYPE_STRING );

$courses      = Analytics::get_courses_with_total_enroll_earning( $user->ID, $order_filter, 'post.post_date', $offset, $pagination_per_page, $search_filter, array( 'publish', 'private' ) );
$total_course = Analytics::get_courses_with_search_by_user( $user->ID, $search_filter, array( 'publish', 'private' ) );
?>

<div class="tutor-analytics-courses tutor-surface-l1 tutor-mt-5 tutor-border tutor-rounded-2xl tutor-overflow-hidden">
	<!-- Filters -->
	<div class="tutor-flex tutor-flex-wrap tutor-gap-4 tutor-items-center tutor-justify-between tutor-py-5 tutor-px-6 tutor-sm-p-5 tutor-border-b">
		<div class="tutor-analytics-courses-search">
			<?php
			SearchFilter::make()
				->form_id( 'tutor-analytics-courses-form' )
				->placeholder( __( 'Search courses...', 'tutor-pro' ) )
				->action( $current_url )
				->size( Size::SMALL )
				->render();
			?>
		</div>

		<?php Sorting::make()->order( $order_filter )->size( Size::SMALL )->render(); ?>
	</div>
	<!-- End Of Filters -->

	<!-- Table -->
	<?php
	if ( tutor_utils()->count( $courses ) ) :
		$headings = array_map(
			fn( $content ) => array(
				'content' => $content,
			),
			array(
				esc_html__( 'Course Name', 'tutor-pro' ),
				esc_html__( 'Total Learners', 'tutor-pro' ),
				esc_html__( 'Earnings', 'tutor-pro' ),
				'',
			)
		);

		$course_ids       = array_column( $courses, 'ID' );
		$course_meta_data = tutor_utils()->get_course_meta_data( $course_ids );

		$total_learners_label = sprintf(
			'<span class="tutor-analytics-courses-mobile-label">%s</span>',
			esc_html__( 'Total Learners:', 'tutor-pro' )
		);
		$earnings_label       = sprintf(
			'<span class="tutor-analytics-courses-mobile-label">%s</span>',
			esc_html__( 'Earnings:', 'tutor-pro' )
		);
		$contents             = array();

		foreach ( $courses as $course ) {

			$course->lesson     = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['lesson'] : 0;
			$course->quiz       = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['tutor_quiz'] : 0;
			$course->assignment = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['tutor_assignments'] : 0;

			$course_summary        = TUTOR_REPORT()->path . 'templates/elements/course-summary.php';
			$earnings              = Analytics::get_earnings_by_user( $user->ID, '', '', '', $course->ID );
			$span_template         = '<span class="tutor-text-tiny tutor-font-medium">%s</span>';
			$course_details_button = TUTOR_REPORT()->path . 'templates/elements/course-details-button.php';

			$contents[] = array(
				'columns' => array(
					array( 'content' => get_template_buffer( $course_summary, $course, false ) ),
					array( 'content' => $total_learners_label . sprintf( $span_template, esc_html( $course->learner ) ) ),
					array(
						'content' => $earnings_label . sprintf( $span_template, wp_kses( tutor_utils()->tutor_price( $earnings['total_earnings'] ), tutor_price_allowed_html() ) ),
					),
					array( 'content' => get_template_buffer( $course_details_button, $course, false ) ),
				),
			);
		}
		?>

	<div class="tutor-table-wrapper">
		<?php Table::make()->headings( $headings )->contents( $contents )->render(); ?>
	</div>
	<!-- End Of Table -->
	<?php else : ?>
		<?php
			EmptyState::make()
				->title( __( 'No Courses Found!', 'tutor-pro' ) )
				->icon( tutor_utils()->get_themed_svg( 'images/illustrations/learning-empty.svg' ) )
				->render();
		?>
	<?php endif; ?>
</div>

<!-- Pagination -->
<?php
Pagination::make()
	->attr( 'class', 'tutor-mt-7' )
	->current( $current_page )
	->total( (int) $total_course )
	->limit( $pagination_per_page )
	->render();
?>
<!-- End Of Pagination -->
