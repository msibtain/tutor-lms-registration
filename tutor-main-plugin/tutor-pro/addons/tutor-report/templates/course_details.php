<?php
/**
 * Overview tempate
 *
 * @package TutorPro\Addons
 * @subpackage Report
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

use Tutor\Components\Constants\Color;
use TUTOR\Icon;
use Tutor\Components\SvgIcon;
use TUTOR\Input;
use TUTOR_REPORT\Analytics;
use TUTOR_REPORT\CourseAnalytics;
use Tutor\Components\Nav;
use Tutor\Components\Constants\Size;
use Tutor\Components\DateFilter;

// global variables.
$user           = wp_get_current_user();
$course_id      = Input::get( 'course_id', 0, Input::TYPE_INT );
$course_details = '';
if ( $course_id ) {
	$course_details = get_post( $course_id, OBJECT );
}
// if not valid course or not author of this course the return.
if ( '' === $course_details || is_null( $course_details ) ) {
	return esc_html_e( 'Invalid course', 'tutor-pro' );
}
if ( $course_details->post_author != $user->ID ) {
	return esc_html_e( 'Invalid course', 'tutor-pro' );
}

$time_period = Input::get( 'period', 'today' );
$active      = $time_period;
$start_date  = Input::get( 'start_date', '' );
$end_date    = Input::get( 'end_date', '' );
if ( '' !== $start_date ) {
	$start_date = tutor_get_formated_date( 'Y-m-d', $start_date );
}
if ( '' !== $end_date ) {
	$end_date = tutor_get_formated_date( 'Y-m-d', $end_date );
}
$previous_url    = esc_url( tutor_utils()->tutor_dashboard_url() . 'courses' );
$back_button_url = tutor_utils()->tutor_dashboard_url() . 'analytics/courses';

?>
<div class="analytics-course-details">
	<div class="tutor-mb-7">
		<div>
			<a class="tutor-btn tutor-btn-secondary tutor-btn-small" href="<?php echo esc_url( $back_button_url ); ?>">			
				<?php SvgIcon::make()->name( Icon::ARROW_LEFT_2 )->flip_rtl()->render(); ?>
				<?php esc_html_e( 'Back', 'tutor-pro' ); ?>
			</a>
		</div>
		<div class="tutor-flex tutor-flex-column tutor-gap-4 tutor-mt-6">
			<h4 class="tutor-h4 tutor-font-medium">
				<?php echo esc_html( $course_details->post_title ); ?>
			</h4>
			<div class="tutor-tiny tutor-flex tutor-items-center tutor-gap-8 tutor-sm-flex-column tutor-sm-items-start tutor-sm-gap-2">
				<div class="tutor-flex tutor-gap-2 tutor-items-center">
					<span class="tutor-text-subdued">
						<?php esc_html_e( 'Published Date', 'tutor-pro' ); ?>:
					</span>
					<?php echo esc_html( tutor_i18n_get_formated_date( $course_details->post_date ) ); ?>
				</div>
				<div class="tutor-flex tutor-gap-2 tutor-items-center">
					<?php SvgIcon::make()->name( Icon::CALENDAR_CHECK )->color( Color::BRAND )->render(); ?>
					<span class="tutor-text-subdued">
						<?php esc_html_e( 'Last Update', 'tutor-pro' ); ?>:
					</span>
					<?php echo esc_html( tutor_i18n_get_formated_date( $course_details->post_modified ) ); ?>
				</div>
			</div>
		</div>
	</div>
	<!-- box cards -->
	<?php
	$card_template    = TUTOR_REPORT()->path . 'templates/elements/box-card.php';
	$total_student    = CourseAnalytics::course_enrollments_with_student_details( $course_id );
	$total_ratings    = tutor_utils()->get_course_rating( $course_id );
	$total_qa         = CourseAnalytics::course_question_answer( $course_id );
	$total_assignment = CourseAnalytics::submitted_assignment_by_course( $course_id );

	$card_data = array(
		array(
			'icon'      => Icon::PASSED,
			'title'     => __( 'Students', 'tutor-pro' ),
			'sub_title' => __( 'Total Student', 'tutor-pro' ),
			'value'     => esc_html( $total_student['total_enrollments'] ),
		),
		array(
			'icon'      => Icon::PROGRESS,
			'title'     => __( 'Progress', 'tutor-pro' ),
			'sub_title' => __( 'Progress Courses', 'tutor-pro' ),
			'value'     => esc_html( $total_student['total_inprogress'] ),
		),
		array(
			'icon'      => Icon::COMPLETED,
			'title'     => __( 'Completed', 'tutor-pro' ),
			'sub_title' => __( 'Completed Courses', 'tutor-pro' ),
			'value'     => esc_html( $total_student['total_completed'] ),
		),
		array(
			'icon'      => Icon::QA,
			'title'     => __( 'Questions', 'tutor-pro' ),
			'sub_title' => __( 'Total Questions', 'tutor-pro' ),
			'value'     => esc_html( $total_qa['total_q_a'] ),
			'price'     => false,
		),
		array(
			'icon'      => Icon::STAR_LINE,
			'title'     => __( 'Reviews', 'tutor-pro' ),
			'sub_title' => __( 'Total Reviews', 'tutor-pro' ),
			'value'     => $total_ratings->rating_count,
			'price'     => false,
		),
		array(
			'icon'      => Icon::BOOK_2,
			'title'     => __( 'Assignment', 'tutor-pro' ),
			'sub_title' => __( 'Assignment Submit', 'tutor-pro' ),
			'value'     => $total_assignment['total_assignments'],
			'price'     => false,
		),
	);

	tutor_load_template_from_custom_path( $card_template, $card_data, false );
	?>
	<!-- box cards end -->

	<!--filter buttons tabs-->
	<div class="tutor-surface-l1 tutor-mt-7 tutor-border tutor-rounded-2xl">
		<div class="tutor-small tutor-border-b tutor-py-5 tutor-pl-6">
			<?php esc_html_e( 'Earnings Graph', 'tutor-pro' ); ?>
		</div>
		<div class="tutor-flex tutor-items-center tutor-justify-between tutor-p-6 tutor-border-b">
			<?php
			$nav_items = array(
				array(
					'label'  => __( 'Today', 'tutor-pro' ),
					'url'    => tutor_utils()->tutor_dashboard_url() . "analytics/course-details?course_id=$course_id&period=today",
					'active' => 'today' === $time_period,
				),
				array(
					'label'  => __( 'Monthly', 'tutor-pro' ),
					'url'    => tutor_utils()->tutor_dashboard_url() . "analytics/course-details?course_id=$course_id&period=monthly",
					'active' => 'monthly' === $time_period,
				),
				array(
					'label'  => __( 'Yearly', 'tutor-pro' ),
					'url'    => tutor_utils()->tutor_dashboard_url() . "analytics/course-details?course_id=$course_id&period=yearly",
					'active' => 'yearly' === $time_period,
				),
			);

			Nav::make()->items( $nav_items )->size( Size::SMALL )->render();

			DateFilter::make()
				->type( DateFilter::TYPE_RANGE )
				->hide_initial_label()
				->placement( DateFilter::PLACEMENT_BOTTOM_END )
				->clear_params( array( 'period' ) )
				->render();
			?>
		</div>
		<!--filter button tabs end-->
		<!--analytics graph -->
		<?php
		/**
		 * Get analytics data
		 *
		 * @since 1.9.9
		 */
		$earnings    = Analytics::get_earnings_by_user( $user->ID, $time_period, $start_date, $end_date, $course_id );
		$discounts   = Analytics::get_discounts_by_user( $user->ID, $time_period, $start_date, $end_date, $course_id );
		$refunds     = Analytics::get_refunds_by_user( $user->ID, $time_period, $start_date, $end_date, $course_id );
		$enrollments = Analytics::get_total_students_by_user( $user->ID, $time_period, $start_date, $end_date, $course_id );

		$graph_tabs = array(
			array(
				'tab_title'  => esc_html__( 'Total Earning', 'tutor-pro' ),
				'tab_value'  => empty( $earnings['total_earnings'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $earnings['total_earnings'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_earnings',
				'graph_data' => Analytics::prepare_chart_data( $earnings['earnings'] ),
			),
			array(
				'tab_title'  => esc_html__( 'Course Enrolled', 'tutor-pro' ),
				'tab_value'  => $enrollments['total_enrollments'] ?? 0,
				'data_attr'  => 'ta_total_course_enrolled',
				'graph_data' => Analytics::prepare_chart_data( $enrollments['enrollments'], false ),
			),
			array(
				'tab_title'  => __( 'Discount', 'tutor-pro' ),
				'tab_value'  => empty( $discounts['total_discounts'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $discounts['total_discounts'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_discount',
				'graph_data' => Analytics::prepare_chart_data( $discounts['discounts'] ),
			),
			array(
				'tab_title'  => __( 'Refund', 'tutor-pro' ),
				'tab_value'  => empty( $refunds['total_refunds'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $refunds['total_refunds'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_refund',
				'graph_data' => Analytics::prepare_chart_data( $refunds['refunds'] ),
			),
		);

		$graph_template = TUTOR_REPORT()->path . 'templates/elements/graph.php';
		tutor_load_template_from_custom_path( $graph_template, $graph_tabs );
		?>
	</div>  
	<!--analytics graph end -->   
</div>
