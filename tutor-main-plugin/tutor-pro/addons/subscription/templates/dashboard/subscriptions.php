<?php
/**
 * Frontend dashboard subscriptions page.
 *
 * @package TutorPro\Subscription
 * @subpackage Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Tutor\Helpers\DateTimeHelper;
use TUTOR\Input;
use TutorPro\Subscription\Controllers\SubscriptionListController;
use TutorPro\Subscription\Models\PlanModel;

// Pagination.
$current_page = max( Input::get( 'paged', 1, Input::TYPE_INT ), 1 );
$limit        = (int) tutor_utils()->get_option( 'pagination_per_page', 10 );
$offset       = ( $limit * $current_page ) - $limit;

$active_tab = Input::get( 'data', 'all' );

$controller         = new SubscriptionListController( false );
$subscription_query = $controller->get_list( $limit, $offset );
$subscriptions      = $subscription_query['results'];
$total_items        = $subscription_query['total_count'];

$page_link = tutor_utils()->get_tutor_dashboard_page_permalink( 'subscriptions' );
$page_tabs = $controller->tabs_key_value();
foreach ( $page_tabs as $index => $item ) {
	if ( 'trash' === $item['key'] ) {
		unset( $page_tabs[ $index ] );
		break;
	}
}
?>

<div class="tutor-fs-5 tutor-fw-medium tutor-color-black tutor-mb-16 tutor-text-capitalize"><?php esc_html_e( 'Subscriptions', 'tutor-pro' ); ?></div>
<div class="tutor-dashboard-content-inner enrolled-courses">
	<div class="tutor-mb-32">
		<ul class="tutor-nav" tutor-priority-nav>
			<?php foreach ( $page_tabs as $tab_item ) : ?>
				<li class="tutor-nav-item">
					<a class="tutor-nav-link<?php echo $tab_item['key'] === $active_tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'data' => $tab_item['key'] ), $page_link ) ); ?>">
						<?php echo esc_html( $tab_item['title'] ); ?>
						(<?php echo esc_html( $tab_item['value'] ); ?>)
					</a>
				</li>
			<?php endforeach; ?>

			<li class="tutor-nav-item tutor-nav-more tutor-d-none">
				<a class="tutor-nav-link tutor-nav-more-item" href="#"><span class="tutor-mr-4"><?php esc_html_e( 'More', 'tutor-pro' ); ?></span> <span class="tutor-nav-more-icon tutor-icon-times"></span></a>
				<ul class="tutor-nav-more-list tutor-dropdown"></ul>
			</li>
		</ul>
	</div>

	<?php if ( count( $subscriptions ) ) : ?>
		<div class="tutor-subscription-list">
				<div class="tutor-table-responsive">
					<table class="tutor-table tutor-table-middle">
						<thead>
							<tr>
								<th>
									<?php esc_html_e( 'Plan Name', 'tutor-pro' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Amount', 'tutor-pro' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Next Payment Date', 'tutor-pro' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Status', 'tutor-pro' ); ?>
								</th>
								<th></th>
							</tr>
						</thead>

						<tbody>
							<?php
							foreach ( $subscriptions as $subscription ) :
								$plan = $controller->plan_model->get_plan( $subscription->plan_id );
								?>
								<tr>
									<td>
										<?php
										echo esc_html( $subscription->plan_name );
										$course_id = $controller->plan_model->get_course_id_by_plan( $subscription->plan_id );
										if ( $course_id ) :
											?>
												<div class="tutor-fs-7 tutor-fw-normal tutor-color-secondary tutor-mt-8">
												<strong class="tutor-fs-7 tutor-fw-medium"><?php esc_html_e( 'Course:', 'tutor-pro' ); ?> </strong><?php echo esc_html( get_the_title( $course_id ) ); ?></div>
												<?php
											endif;
										?>
									</td>

									<td>
										<?php $controller->subscription_model->formatted_subscription_price( $subscription ); ?>
									</td>

									<td>
										<?php
										if ( ! empty( $subscription->next_payment_date_gmt ) ) :
											echo esc_html(
												PlanModel::PAYMENT_ONETIME === $plan->payment_type
												? __( 'N/A', 'tutor-pro' )
												: DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt )
											);
										endif;
										?>
									</td>

									<td>
										<?php echo wp_kses_post( tutor_utils()->translate_dynamic_text( $subscription->status, true ) ); ?>
									</td>

									<td class="tutor-text-right">
										<a 
											href="<?php echo esc_url( add_query_arg( array( 'id' => $subscription->id ), $page_link ) ); ?>" 
											class="tutor-btn tutor-btn-outline-primary tutor-btn-sm">
										<?php esc_html_e( 'Details', 'tutor' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
		</div>

		<div class="tutor-mt-20">
				<div class="tutor-admin-page-pagination-wrapper tutor-mt-32">
					<?php
					/**
					 * Prepare pagination data & load template
					 */
					if ( $total_items > $limit ) {
						$pagination_data     = array(
							'total_items' => $total_items,
							'per_page'    => $limit,
							'paged'       => $current_page,
						);
						$pagination_template = tutor()->path . 'views/elements/pagination.php';
						tutor_load_template_from_custom_path( $pagination_template, $pagination_data );
					}
					?>
				</div>
		</div>
				<?php else : ?>
					<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
	<?php endif; ?>
</div>
