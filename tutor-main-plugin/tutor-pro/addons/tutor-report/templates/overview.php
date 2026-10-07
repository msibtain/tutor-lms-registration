<?php
/**
 * Overview template
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

use TUTOR\Input;
use Tutor\Components\Constants\Size;
use Tutor\Components\DateFilter;
use Tutor\Components\Nav;
use Tutor\Components\Skeleton;

$time_period = Input::get( 'period', 'today' );
$start_date  = Input::get( 'start_date', '' );
$end_date    = Input::get( 'end_date', '' );

$start_date  = $start_date ? tutor_get_formated_date( 'Y-m-d', $start_date ) : '';
$end_date    = $end_date ? tutor_get_formated_date( 'Y-m-d', $end_date ) : '';
$time_period = ( $start_date && $end_date ) ? '' : $time_period;
?>

<div class="tutor-analytics-overview tutor-mt-5" data-tutor-ajax-dashboard="true">

	<!-- Section 1: Overview Cards (Loads in ~30ms) -->
	<div 
		x-data="tutorLazySection({
			section: 'report_overview_cards',
			dateDependent: false
		})"
	>
		<div x-show="isLoading">
			<?php Skeleton::make()->type( Skeleton::TYPE_BOX_CARD )->count( 3 )->render(); ?>
		</div>
		<div x-show="!isLoading && hasData" x-html="content" x-ref="contentContainer"></div>
	</div>

	<!-- Section 2: Earnings & Enrollments Graph (Concurrent Lazyload with in-place Date Filtering) -->
	<div class="tutor-surface-l1 tutor-mt-7 tutor-border tutor-rounded-2xl">
		<div class="tutor-small tutor-border-b tutor-py-5 tutor-pl-6">
			<?php esc_html_e( 'Earnings Graph', 'tutor-pro' ); ?>
		</div>
		<div 
			class="tutor-flex tutor-items-center tutor-justify-between tutor-p-6 tutor-border-b"
			x-data="{
				activePeriod: <?php echo esc_attr( wp_json_encode( ! empty( $time_period ) ? $time_period : 'today' ) ); ?>,
				setPeriod(period) {
					this.activePeriod = period;
					const url = new URL(window.location.href);
					url.searchParams.delete('start_date');
					url.searchParams.delete('end_date');
					url.searchParams.set('period', period);
					window.history.pushState({}, '', url.toString());
					window.dispatchEvent(new CustomEvent(window.TutorCore?.constants?.TUTOR_CUSTOM_EVENTS?.DATE_FILTER_CHANGED || 'tutor:date-filter-changed', {
						detail: {
							period: period,
							startDate: '',
							endDate: '',
							url: url.toString()
						}
					}));
				}
			}"
		>
			<div class="tutor-nav tutor-nav-small tutor-nav-secondary">
				<a 
					href="<?php echo esc_url( add_query_arg( 'period', 'today' ) ); ?>"
					class="tutor-nav-item" 
					:class="{ 'active': activePeriod === 'today' }"
					@click.prevent="setPeriod('today')"
				>
					<?php esc_html_e( 'Today', 'tutor-pro' ); ?>
				</a>
				<a 
					href="<?php echo esc_url( add_query_arg( 'period', 'monthly' ) ); ?>"
					class="tutor-nav-item" 
					:class="{ 'active': activePeriod === 'monthly' }"
					@click.prevent="setPeriod('monthly')"
				>
					<?php esc_html_e( 'Monthly', 'tutor-pro' ); ?>
				</a>
				<a 
					href="<?php echo esc_url( add_query_arg( 'period', 'yearly' ) ); ?>"
					class="tutor-nav-item" 
					:class="{ 'active': activePeriod === 'yearly' }"
					@click.prevent="setPeriod('yearly')"
				>
					<?php esc_html_e( 'Yearly', 'tutor-pro' ); ?>
				</a>
			</div>

			<?php
			DateFilter::make()
				->type( DateFilter::TYPE_RANGE )
				->hide_initial_label()
				->placement( DateFilter::PLACEMENT_BOTTOM_END )
				->clear_params( array( 'period' ) )
				->ajax_mode( true )
				->render();
			?>
		</div>

		<div 
			x-data="tutorLazySection({
				section: 'report_graph',
				dateDependent: true
			})"
		>
			<div x-show="isLoading" class="tutor-analytics-graph">
				<div class="tutor-analytics-graph-tab">
					<div class="tutor-analytics-graph-tab-items">
						<?php
						$tab_skeletons = array(
							array(
								'title' => '75px',
								'value' => '60px',
							),
							array(
								'title' => '85px',
								'value' => '24px',
							),
							array(
								'title' => '70px',
								'value' => '52px',
							),
							array(
								'title' => '80px',
								'value' => '72px',
							),
						);
						foreach ( $tab_skeletons as $index => $skel ) :
							?>
							<div class="tutor-analytics-graph-tab-items-button tutor-flex tutor-flex-column tutor-items-start tutor-justify-center tutor-px-6 <?php echo 0 === $index ? 'tutor-active' : ''; ?>" style="height: 78px;">
								<div class="tutor-flex tutor-flex-column tutor-items-start">
									<span class="tutor-skeleton" style="width: <?php echo esc_attr( $skel['title'] ); ?>; height: 12px; display: inline-block;"></span>
									<span class="tutor-skeleton tutor-mt-2" style="width: <?php echo esc_attr( $skel['value'] ); ?>; height: 18px; display: inline-block;"></span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="tutor-tabs-content">
						<div class="tutor-tab-panel" style="padding: 24px;">
							<div class="tutor-skeleton tutor-rounded-lg" style="width: 100%; height: 200px;"></div>
						</div>
					</div>
				</div>
			</div>
			<div x-show="!isLoading && hasData" x-html="content" x-ref="contentContainer"></div>
		</div>
	</div>

	<!-- Section 3: Most Popular Courses Table -->
	<div 
		x-data="tutorLazySection({
			section: 'report_popular_courses',
			dateDependent: false
		})"
	>
		<div x-show="isLoading" class="tutor-mt-7">
			<?php Skeleton::make()->type( Skeleton::TYPE_TABLE )->count( 5 )->render(); ?>
		</div>
		<div x-show="!isLoading && hasData" x-html="content" x-ref="contentContainer"></div>
	</div>
</div>
