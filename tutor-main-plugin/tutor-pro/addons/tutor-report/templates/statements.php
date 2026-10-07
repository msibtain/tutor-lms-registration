<?php
/**
 * Statements Template
 *
 * @package TutorPro\Addon
 * @subpackage TutorReport\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

use TUTOR_REPORT\Analytics;
use Tutor\Components\Button;
use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\CourseFilter;
use Tutor\Components\DateFilter;
use Tutor\Components\EmptyState;
use Tutor\Components\Pagination;
use Tutor\Components\Sorting;
use Tutor\Components\Table;
use Tutor\Ecommerce\Tax;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;

$user                = wp_get_current_user();
$current_page        = max( 1, Input::get( 'current_page', 0, Input::TYPE_INT ) );
$pagination_per_page = tutor_utils()->get_option( 'pagination_per_page' );
$offset              = ( $pagination_per_page * $current_page ) - $pagination_per_page;

$order_by     = Input::get( 'order', '' );
$course_id    = Input::get( 'course-id', '' );
$order_filter = QueryHelper::get_valid_sort_order( $order_by ?? 'DESC' );
$start_date   = Input::get( 'start_date', '' );
$end_date     = Input::get( 'end_date', '' );

$statements    = Analytics::get_statements_by_user( $user->ID, $offset, $pagination_per_page, $course_id, $start_date, $end_date, $order_filter );
$courses       = CourseModel::get_courses_by_instructor( $user->ID, array( 'publish', 'private' ) );
$total_courses = ! empty( $courses ) && empty( $course_id ) ? count( $courses ) : $statements['total_statements'];

$filter_count      = count( array_filter( array( $course_id, $start_date, $end_date, $order_by ) ) );
$clear_button_text = $filter_count > 1 ? __( 'Clear All', 'tutor-pro' ) : __( 'Clear', 'tutor-pro' );
?>

<div class="tutor-analytics-statements tutor-surface-l1 tutor-mt-5 tutor-border tutor-rounded-2xl tutor-overflow-hidden">
	<!-- Filters -->
	<div class="tutor-flex tutor-items-center tutor-justify-between tutor-py-5 tutor-px-6 tutor-border-b">
		<?php
		CourseFilter::make()
			->courses( $courses )
			->count( $total_courses )
			->variant( Variant::LINK )
			->render()
		?>
		<div class="tutor-flex tutor-items-center tutor-gap-3">
			<?php
			if ( $filter_count > 0 ) {
				Button::make()
				->tag( 'a' )
				->attr( 'href', tutor_utils()->tutor_dashboard_url() . 'analytics/statements' )
				->attr( 'class', 'tutor-text-brand' )
				->label( $clear_button_text )
				->variant( Variant::LINK )
				->render();
			}
			?>
			<?php
			DateFilter::make()
				->type( DateFilter::TYPE_RANGE )
				->trigger_size( Size::X_SMALL )
				->placement( DateFilter::PLACEMENT_BOTTOM_END )
				->hide_initial_label()
				->render();
			?>

			<?php Sorting::make()->order( $order_filter )->render(); ?>
		</div>
	</div>
	<!-- Filters -->

	<!-- Table -->
	<?php if ( count( $statements['statements'] ?? array() ) ) : ?>
		<?php
		$headings = array_map(
			fn( $content ) => array(
				'content' => $content,
			),
			array(
				esc_html__( 'Transaction Details', 'tutor-pro' ),
				esc_html__( 'Price Breakdown', 'tutor-pro' ),
				esc_html__( 'Net Earnings', 'tutor-pro' ),
				esc_html__( 'Admin Share', 'tutor-pro' ),
			)
		);

		$contents                  = array();
		$breakdown_label           = sprintf(
			'<span class="tutor-analytics-statements-mobile-label">%s</span>',
			esc_html__( 'Breakdown', 'tutor-pro' )
		);
		$instructor_earnings_label = sprintf(
			'<span class="tutor-analytics-statements-mobile-label">%s</span>',
			esc_html__( 'My Earnings', 'tutor-pro' )
		);
		$admin_earnings_label      = sprintf(
			'<span class="tutor-analytics-statements-mobile-label">%s</span>',
			esc_html__( 'Admin Gets', 'tutor-pro' )
		);

		foreach ( $statements['statements'] as $statement ) {
			$wc_order                 = function_exists( 'wc_get_order' ) ? wc_get_order( $statement->order_id ) : false;
			$customer                 = $wc_order ? $wc_order->get_user() : null;
			$is_inclusive_tax         = Tax::TYPE_INCLUSIVE === $statement->order_tax_type;
			$course_price_grand_total = $is_inclusive_tax ? max( $statement->course_price_grand_total - $statement->order_tax_amount, 0 ) : $statement->course_price_grand_total;
			$instructor_amount        = $is_inclusive_tax ? ( $course_price_grand_total * ( $statement->instructor_rate / 100 ) ) : $statement->instructor_amount;
			$admin_amount             = $is_inclusive_tax ? ( $course_price_grand_total * ( $statement->admin_rate / 100 ) ) : $statement->admin_amount;

			$statement_table = TUTOR_REPORT()->path . 'templates/elements/statement-table.php';
			$contents[]      = array(
				'columns' => array(
					array(
						'content' => get_template_buffer(
							$statement_table,
							array(
								'statement' => $statement,
								'template'  => 'statement_info',
							),
							false
						),
					),
					array(
						'content' => $breakdown_label . get_template_buffer(
							$statement_table,
							array(
								'statement' => $statement,
								'template'  => 'statement_breakdown',
							),
							false
						),
					),
					array(
						'content' => $instructor_earnings_label . get_template_buffer(
							$statement_table,
							array(
								'statement' => $statement,
								'template'  => 'instructor_earnings',
							),
							false
						),
					),
					array(
						'content' => $admin_earnings_label . get_template_buffer(
							$statement_table,
							array(
								'statement' => $statement,
								'template'  => 'admin_earnings',
							),
							false
						),
					),
				),
			);
		}
		?>
		<div class="tutor-table-wrapper">
			<?php Table::make()->headings( $headings )->contents( $contents )->render(); ?>
		</div>
		<!-- Table -->
	<?php else : ?>
		<?php
			EmptyState::make()
				->title( __( 'No Statements Found!', 'tutor-pro' ) )
				->render();
		?>
	<?php endif; ?>
</div>

<!-- Pagination -->
<?php
Pagination::make()
	->attr( 'class', 'tutor-mt-7' )
	->current( $current_page )
	->total( $statements['total_statements'] ?? 0 )
	->limit( $pagination_per_page )
	->render();
?>
