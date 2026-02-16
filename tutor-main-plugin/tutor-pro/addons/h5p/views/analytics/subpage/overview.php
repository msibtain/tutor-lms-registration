<?php
/**
 * H5P Analytics Overview page template
 *
 * @package TutorPro\Addons
 * @subpackage H5P\Views\Analytics
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

use TutorPro\H5P\Analytics;
use TutorPro\H5P\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="tutor-report-overview-wrap">
	<div class="tutor-row tutor-gx-4">
		<div class="tutor-col-md-6 tutor-col-xl-3 tutor-my-8 tutor-my-md-16">
			<div class="tutor-card tutor-card-secondary tutor-p-24">
				<div class="tutor-d-flex">
					<div class="tutor-round-box">
						<span class="tutor-icon-dashboard" area-hidden="true"></span>
					</div>

					<div class="tutor-ml-20">
						<div class="tutor-fs-4 tutor-fw-bold tutor-color-black"><?php echo esc_attr( $total_statements ); ?></div>
						<div class="tutor-fs-7 tutor-color-secondary"><?php esc_html_e( 'Statements', 'tutor-pro' ); ?></div>
					</div>
				</div>
			</div>
		</div>
		<div class="tutor-col-md-6 tutor-col-xl-3 tutor-my-8 tutor-my-md-16">
			<div class="tutor-card tutor-card-secondary tutor-p-24">
				<div class="tutor-d-flex">
					<div class="tutor-round-box">
						<span class="tutor-icon-dashboard" area-hidden="true"></span>
					</div>

					<div class="tutor-ml-20">
						<div class="tutor-fs-4 tutor-fw-bold tutor-color-black"><?php echo esc_attr( $total_monthly_statements ); ?></div>
						<div class="tutor-fs-7 tutor-color-secondary"><?php esc_html_e( 'Statements this Month', 'tutor-pro' ); ?></div>
					</div>
				</div>
			</div>
		</div>
		<div class="tutor-col-md-6 tutor-col-xl-3 tutor-my-8 tutor-my-md-16">
			<div class="tutor-card tutor-card-secondary tutor-p-24">
				<div class="tutor-d-flex">
					<div class="tutor-round-box">
						<span class="tutor-icon-book-open" area-hidden="true"></span>
					</div>

					<div class="tutor-ml-20">
						<div class="tutor-fs-4 tutor-fw-bold tutor-color-black"><?php echo esc_attr( $all_verb_count ); ?></div>
						<div class="tutor-fs-7 tutor-color-secondary"><?php esc_html_e( 'Verbs', 'tutor-pro' ); ?></div>
					</div>
				</div>
			</div>
		</div>
		<div class="tutor-col-md-6 tutor-col-xl-3 tutor-my-8 tutor-my-md-16">
			<div class="tutor-card tutor-card-secondary tutor-p-24">
				<div class="tutor-d-flex">
					<div class="tutor-round-box">
						<span class="tutor-icon-star-bold" area-hidden="true"></span>
					</div>

					<div class="tutor-ml-20">
						<div class="tutor-fs-4 tutor-fw-bold tutor-color-black"><?php echo esc_attr( $all_activities_count ); ?></div>
						<div class="tutor-fs-7 tutor-color-secondary"><?php esc_html_e( 'Activities', 'tutor-pro' ); ?></div>
					</div>
				</div>
			</div>
		</div>
		<div class="tutor-col-md-6 tutor-col-xl-3 tutor-my-8 tutor-my-md-16">
			<div class="tutor-card tutor-card-secondary tutor-p-24">
				<div class="tutor-d-flex">
					<div class="tutor-round-box">
						<span class="tutor-icon-user-graduate" area-hidden="true"></span>
					</div>

					<div class="tutor-ml-20">
						<div class="tutor-fs-4 tutor-fw-bold tutor-color-black"><?php echo esc_attr( $all_learners_count ); ?></div>
						<div class="tutor-fs-7 tutor-color-secondary"><?php esc_html_e( 'Learners', 'tutor-pro' ); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="tutor-analytics-wrapper tutor-analytics-graph tutor-mt-12">

		<div class="tutor-fs-5 tutor-fw-medium tutor-color-black tutor-d-flex tutor-align-center tutor-justify-between tutor-mb-16">
			<div>
				<?php esc_html_e( 'Experience graph', 'tutor-pro' ); ?>
			</div>
			<div class="tutor-admin-report-frequency-wrapper" style="min-width: 260px;">
				<?php require_once Utils::addon_config()->path . 'views/analytics/graph/frequency.php'; ?>
				<div class="tutor-v2-date-range-picker inactive" style="width: 305px; position:absolute; z-index: 99;"></div>
			</div>
		</div>
		<div class="tutor-overview-month-graph">
			<!--analytics graph -->
			<?php

				/* translators: %s: frequencies */
				$content_title  = sprintf( __( 'for %s', 'tutor-pro' ), $frequencies[ $current_frequency ] );
				$statements     = Analytics::get_h5p_statements_count( $time_period, $start_date, $end_date );
				$graph_tabs     = array(
					array(
						'tab_title'     => __( 'Total Statements', 'tutor-pro' ),
						'tab_value'     => $total_statements,
						'data_attr'     => 'ta_total_statements',
						'active'        => 'is-active',
						'price'         => true,
						/* translators: %s: content title */
						'content_title' => sprintf( __( 'Statements Chart %s', 'tutor-pro' ), $content_title ),
					),
				);
				$graph_template = Utils::addon_config()->path . 'views/analytics/graph/graph.php';
				tutor_load_template_from_custom_path( $graph_template, $graph_tabs );
				?>
			<!--analytics graph end -->
		</div>
	</div>
</div>