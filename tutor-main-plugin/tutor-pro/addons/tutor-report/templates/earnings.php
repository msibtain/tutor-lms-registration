<?php
/**
 * Earnings template
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TUTOR\Icon;
use TUTOR\Input;
use TUTOR_REPORT\Analytics;
use Tutor\Components\Nav;
use Tutor\Components\Constants\Size;
use Tutor\Components\DateFilter;

// global variables.
$user        = wp_get_current_user();
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
?>

<div class="tutor-analytics-earnings tutor-mt-5">
	<!--analytics graph -->
	<?php
		/**
		 * Earnings card info
		 *
		 * @since 1.9.9
		 */
		$card_template = TUTOR_REPORT()->path . 'templates/elements/box-card.php';
		$user          = wp_get_current_user();
		$earnings      = tutor_utils()->get_earning_sum( $user->ID );

		$card_data = array(
			array(
				'icon'      => Icon::EARNING,
				'title'     => __( 'Earnings', 'tutor-pro' ),
				'sub_title' => __( 'Total Earning', 'tutor-pro' ),
				'value'     => wp_kses( tutor_utils()->tutor_price( $earnings->instructor_amount ?? 0 ), tutor_price_allowed_html() ),
			),

			array(
				'icon'      => Icon::WALLET,
				'title'     => __( 'Balance', 'tutor-pro' ),
				'sub_title' => __( 'Current Balance', 'tutor-pro' ),
				'value'     => wp_kses( tutor_utils()->tutor_price( $earnings->balance ?? 0 ), tutor_price_allowed_html() ),
			),
			array(
				'icon'      => Icon::WITHDRAW,
				'title'     => __( 'Withdraws', 'tutor-pro' ),
				'sub_title' => __( 'Total Withdraws', 'tutor-pro' ),
				'value'     => wp_kses( tutor_utils()->tutor_price( $earnings->withdraws_amount ?? 0 ), tutor_price_allowed_html() ),
			),
			array(
				'icon'      => Icon::SALE,
				'title'     => __( 'Sale', 'tutor-pro' ),
				'sub_title' => __( 'Total Sale', 'tutor-pro' ),
				'value'     => wp_kses( tutor_utils()->tutor_price( $earnings->course_price_total ?? 0 ), tutor_price_allowed_html() ),
			),
			array(
				'icon'      => Icon::COMMISSION,
				'title'     => __( 'Commissions', 'tutor-pro' ),
				'sub_title' => __( 'Deducted Commissions', 'tutor-pro' ),
				'value'     => wp_kses( tutor_utils()->tutor_price( $earnings->admin_amount ?? 0 ), tutor_price_allowed_html() ),
			),
			array(
				'icon'      => Icon::FEES,
				'title'     => __( 'Fees', 'tutor-pro' ),
				'sub_title' => __( 'Deducted Fees', 'tutor-pro' ),
				'value'     => wp_kses( tutor_utils()->tutor_price( $earnings->deduct_fees_amount ?? 0 ), tutor_price_allowed_html() ),
			),
		);

		tutor_load_template_from_custom_path( $card_template, $card_data, false );
		?>
	<!--card info end -->

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
					'url'    => tutor_utils()->tutor_dashboard_url() . 'analytics/earnings?period=today',
					'active' => 'today' === $time_period,
				),
				array(
					'label'  => __( 'Monthly', 'tutor-pro' ),
					'url'    => tutor_utils()->tutor_dashboard_url() . 'analytics/earnings?period=monthly',
					'active' => 'monthly' === $time_period,
				),
				array(
					'label'  => __( 'Yearly', 'tutor-pro' ),
					'url'    => tutor_utils()->tutor_dashboard_url() . 'analytics/earnings?period=yearly',
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

		<!--analytics graph -->
		<?php
		/**
		 * Get analytics data
		 *
		 * @since 1.9.9
		 */
		$commission_fees = Analytics::commission_fees_by_user( $user->ID, $time_period, $start_date, $end_date );

		$earnings = Analytics::get_earnings_by_user( $user->ID, $time_period, $start_date, $end_date );
		$sales    = Analytics::number_of_sales( $user->ID, $time_period, $start_date, $end_date );

		$graph_tabs     = array(
			array(
				'tab_title'  => __( 'Total Earning', 'tutor-pro' ),
				'tab_value'  => empty( $earnings['total_earnings'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $earnings['total_earnings'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_earnings',
				'graph_data' => Analytics::prepare_chart_data( $earnings['earnings'] ),
			),
			array(
				'tab_title'  => __( 'Number of Sales', 'tutor-pro' ),
				'tab_value'  => $sales['total_sales'] ?? '-',
				'data_attr'  => 'ta_total_course_enrolled',
				'graph_data' => Analytics::prepare_chart_data( $sales['sales'], false ),
			),
			array(
				'tab_title'  => __( 'Commission', 'tutor-pro' ),
				'tab_value'  => empty( $commission_fees['total'] ) ? '-' : wp_kses( tutor_utils()->tutor_price( $commission_fees['total'] ), tutor_price_allowed_html() ),
				'data_attr'  => 'ta_total_refund',
				'graph_data' => Analytics::prepare_chart_data( $commission_fees['commission_fees'] ),
			),
		);
		$graph_template = TUTOR_REPORT()->path . 'templates/elements/graph.php';
		tutor_load_template_from_custom_path( $graph_template, $graph_tabs );
		?>
		<!--analytics graph end -->  
	</div>
	<!--filter button tabs end-->  
</div>
