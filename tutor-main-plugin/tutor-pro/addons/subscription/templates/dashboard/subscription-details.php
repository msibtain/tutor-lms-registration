<?php
/**
 * Subscription details
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

use Tutor\Ecommerce\Ecommerce;
use Tutor\Helpers\DateTimeHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;
use TUTOR\User;
use TutorPro\Subscription\Controllers\SubscriptionListController;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;

// Pagination.

$current_page = Input::get( 'current_page', 1, Input::TYPE_INT );
$limit        = (int) tutor_utils()->get_option( 'pagination_per_page', 10 );
$offset       = ( $limit * max( $current_page, 1 ) ) - $limit;

$page_link = tutor_utils()->get_tutor_dashboard_page_permalink( 'subscriptions' );

$subscription_id = max( Input::get( 'id', 1, Input::TYPE_INT ), 1 );
$controller      = new SubscriptionListController( false );
$subscription    = $controller->subscription_model->get_subscription( $subscription_id );
if ( ! $subscription ) {
	tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() );
	return;
}

$plan                = $controller->plan_model->get_plan( $subscription->plan_id );
$order_history_query = $controller->subscription_model->get_subscription_orders( $subscription, $limit, $offset );
$order_history       = $order_history_query['results'];
$total_items         = $order_history_query['total_count'];
$date_format         = 'd M, Y h:i a';

// Settings.
$can_cancel_anytime = (bool) tutor_utils()->get_option( 'subscription_cancel_anytime', true );
$can_early_renewal  = (bool) tutor_utils()->get_option( 'subscription_early_renewal', false );

$order_model = new OrderModel();
?>

<div class="tutor-subscription-info tutor-mb-32">
	<a class="tutor-btn tutor-btn-ghost" href="<?php echo esc_url( $page_link ); ?>">
		<span class="tutor-icon-previous tutor-mr-8" area-hidden="true"></span>
		<?php esc_html_e( 'Back', 'tutor-pro ' ); ?>
	</a>

	<div class="tutor-card tutor-mt-20 tutor-mb-48">
		<div class="tutor-d-flex tutor-align-start tutor-justify-between tutor-p-24 tutor-border-bottom">
			<div>
				<div class="tutor-d-flex tutor-align-center tutor-gap-1 tutor-mb-4 tutor-text-capitalize">
					<div class="tutor-fs-5 tutor-fw-medium tutor-color-black">
						<?php echo esc_html( $plan->plan_name ); ?>
					</div>
					<?php echo wp_kses_post( tutor_utils()->translate_dynamic_text( $subscription->status, true ) ); ?>
				</div>

				<?php
				$course_id = $controller->plan_model->get_course_id_by_plan( $subscription->plan_id );
				if ( $course_id ) :
					?>
					<div class="tutor-fs-7 tutor-fw-normal tutor-mb-16">
						<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" target="_blank" class="tutor-color-primary">
							<?php echo esc_html( get_the_title( $course_id ) ); ?>
						</a>
					</div>
					<?php
				endif;
				?>

				<div class="tutor-d-flex tutor-gap-3 tutor-fs-7">
					<div>
						<?php esc_html_e( 'Subscription ID:' ); ?> <span class="tutor-fw-bold">#<?php echo esc_html( $subscription->id ); ?></span>
					</div>
					<div>
					<?php esc_html_e( 'Timezone:' ); ?> <span class="tutor-fw-bold"><?php echo esc_html( User::get_user_timezone_string() ); ?></span>
					</div>
					<div>
						<?php esc_html_e( 'Activated on:' ); ?> <span class="tutor-fw-bold"><?php echo empty( $subscription->start_date_gmt ) ? '' : esc_attr( DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->start_date_gmt, $date_format ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="tutor-d-flex tutor-gap-2">
				<?php if ( $can_cancel_anytime && ( SubscriptionModel::STATUS_ACTIVE === $subscription->status || SubscriptionModel::STATUS_PENDING === $subscription->status ) ) : ?>
				<button id="tutor-subscription-cancel-plan-button" class="tutor-btn tutor-btn-sm tutor-btn-outline-primary">
					<?php esc_html_e( 'Cancel Plan', 'tutor-pro' ); ?>
				</button>
				<?php endif; ?>
				<?php if ( $can_early_renewal && SubscriptionModel::STATUS_ACTIVE === $subscription->status && $controller->subscription_model->should_renew_subscription( $subscription ) ) : ?>
				<button class="tutor-btn tutor-btn-sm tutor-btn-secondary" data-tutor-modal-target="tutor-subscription-early-renewal-modal">
					<?php esc_html_e( 'Renew Now', 'tutor-pro' ); ?>
				</button>
				<?php endif; ?>
				<!-- <button class="tutor-btn tutor-btn-sm tutor-btn-primary">
					<?php esc_html_e( 'Change Plan', 'tutor-pro' ); ?>
				</button> -->
			</div>
		</div>

		<div class="tutor-p-24">
			<div class="tutor-row tutor-g-4">
				<div class="tutor-col-3">
					<div class="tutor-color-subdued tutor-mb-4"><?php esc_html_e( 'Amount', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-medium">
					<?php
						$controller->subscription_model->formatted_subscription_price( $subscription );
					?>
					</div>
				</div>

				<div class="tutor-col-3">
					<div class="tutor-color-subdued tutor-mb-4"><?php esc_html_e( 'Payment', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-medium tutor-text-capitalize">
						<?php echo esc_html( $plan->payment_type ); ?>
					</div>
				</div>

				<div class="tutor-col-3">
					<div class="tutor-color-subdued tutor-mb-4"><?php esc_html_e( 'Subscription Status', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-medium tutor-text-capitalize">
						<?php echo esc_html( $subscription->status ); ?>
					</div>
				</div>

				<?php if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) : ?>
					<?php
					// TODO: later will implement trial.
					if ( false ) :
						?>
				<div class="tutor-col-3">
					<div class="tutor-color-subdued tutor-mb-4"><?php esc_html_e( 'Trial End', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-medium">
						<?php
						if ( ! empty( $subscription->trial_end_date_gmt ) ) :
							echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->trial_end_date_gmt, $date_format ) );
						endif;
						?>
					</div>
				</div>
				<?php endif; ?>
				<?php endif; ?>

				<?php if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) : ?>
				<div class="tutor-col-3">
					<div class="tutor-color-subdued tutor-mb-4"><?php esc_html_e( 'Start Date', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-medium">
						<?php
						if ( ! empty( $subscription->start_date_gmt ) ) :
							echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->start_date_gmt, $date_format ) );
						endif;
						?>
					</div>
				</div>
				<?php endif; ?>

				<?php
				if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) :
					;
					?>
				<div class="tutor-col-3">
					<div class="tutor-color-subdued tutor-mb-4"><?php esc_html_e( 'Next Payment Date', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-medium">
						<?php
						if ( ! empty( $subscription->next_payment_date_gmt ) ) :
							echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt, $date_format ) );
						endif;
						?>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) : ?>
				<div class="tutor-col-3">
					<div class="tutor-color-subdued tutor-mb-4"><?php esc_html_e( 'Renew', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-medium">
						<?php
							echo '0' === $plan->recurring_limit ? esc_html_e( 'Until cancelled', 'tutor-pro' ) : esc_attr( sprintf( _n( '%s Time', '%s Times', $plan->recurring_limit, 'tutor-pro' ), $plan->recurring_limit ) ); //phpcs:ignore
						?>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php if ( count( $order_history ) ) : ?>
		<div class="tutor-subscription-payment-history">
				<div class="tutor-fs-5 tutor-fw-medium tutor-color-black tutor-mb-16 tutor-text-capitalize"><?php esc_html_e( 'Payment History', 'tutor-pro' ); ?></div>
				<div class="tutor-table-responsive">
					<table class="tutor-table tutor-table-middle">
						<thead>
							<tr>
								<th>
									<?php esc_html_e( 'Order ID', 'tutor-pro' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Type', 'tutor-pro' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Date', 'tutor-pro' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Amount', 'tutor-pro' ); ?>
								</th>
								<th><?php esc_html_e( 'Payment Method', 'tutor-pro' ); ?></th>
								<th>
									<?php esc_html_e( 'Status', 'tutor-pro' ); ?>
								</th>
								<th class="tutor-text-center"></th>
							</tr>
						</thead>

						<tbody>
							<?php
							foreach ( $order_history as $item ) :
								$order_details = $order_model->get_order_by_id( $item->id );
								?>
								<tr>
									<td>
										<?php echo esc_html( '#' . $item->id ); ?>
									</td>
									<td>
										<?php echo esc_html( ucwords( $item->order_type ) ); ?>
									</td>
									<td>
										<?php echo empty( $item->created_at_gmt ) ? '' : esc_attr( DateTimeHelper::get_gmt_to_user_timezone_date( $item->created_at_gmt ) ); ?>
									</td>
									<td>
										<?php echo esc_html( tutor_get_formatted_price( $item->total_price ) ); ?>
									</td>
									<td>
										<?php echo esc_html( Ecommerce::get_payment_method_label( $item->payment_method ?? '' ) ); ?>
									</td>
									<td>
										<?php echo wp_kses_post( tutor_utils()->translate_dynamic_text( $item->order_status, true ) ); ?>
									</td>
									<td class="tutor-text-center">
										<?php
										OrderModel::render_pay_button( $order_details );
										do_action( 'tutor_dashboard_invoice_button', $order_details );
										?>
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
						$pagination_data = array(
							'total_items' => $total_items,
							'per_page'    => $limit,
							'paged'       => $current_page,
						);

						$pagination_template = tutor()->path . 'templates/dashboard/elements/pagination.php';
						tutor_load_template_from_custom_path( $pagination_template, $pagination_data );
					}
					?>
				</div>
		</div>
				<?php else : ?>
					<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
<?php endif; ?>

<div id="tutor-subscription-cancel-plan-modal" class="tutor-modal">
	<div class="tutor-modal-overlay"></div>
	<div class="tutor-modal-window">
		<div class="tutor-modal-content tutor-modal-content-white">
			<button class="tutor-iconic-btn tutor-modal-close-o" data-tutor-modal-close>
				<span class="tutor-icon-times" area-hidden="true"></span>
			</button>

			<div class="tutor-modal-body tutor-text-center">
				<div class="tutor-fs-3 tutor-fw-medium tutor-color-black tutor-mb-12 tutor-mt-40">
					<?php esc_html_e( 'Cancel plan?', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-fs-6 tutor-color-muted">
					<?php esc_html_e( 'Are you sure you want to cancel the plan? Please confirm your choice.', 'tutor-pro' ); ?>
				</div>

				<div class="tutor-d-flex tutor-justify-center tutor-my-48">
					<button class="tutor-btn tutor-btn-outline-primary" data-tutor-modal-close>
						<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
					</button>
					<button id="tutor-subscription-cancel-plan-submit" class="tutor-btn tutor-btn-primary tutor-ml-20" data-subscription-id="<?php echo esc_attr( $subscription_id ); ?>">
						<?php esc_html_e( 'Yes, I’m sure', 'tutor-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- subscription early renewal modal -->
<div id="tutor-subscription-early-renewal-modal" class="tutor-modal">
	<div class="tutor-modal-overlay"></div>
	<div class="tutor-modal-window">
		<div class="tutor-modal-content tutor-modal-content-white">
			<button class="tutor-iconic-btn tutor-modal-close-o" data-tutor-modal-close>
				<span class="tutor-icon-times" area-hidden="true"></span>
			</button>

			<div class="tutor-modal-body tutor-text-center">
				<div class="tutor-fs-3 tutor-fw-medium tutor-color-black tutor-mb-12 tutor-mt-40">
					<?php esc_html_e( 'Early Renewal', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-fs-6 tutor-color-muted">
					<p>
						<?php esc_html_e( 'By renewing your subscription early, your next payment date will be', 'tutor-pro' ); ?>
						<strong>
						<br>
						<?php
						if ( ! empty( $subscription->next_payment_date_gmt ) ) :
							$current_next_payment_date_gmt = $subscription->next_payment_date_gmt;
							$next_payment_date_gmt         = DateTimeHelper::create( $current_next_payment_date_gmt )
																->add( $plan->recurring_value, $plan->recurring_interval )
																->to_date_time_string();
							echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $next_payment_date_gmt ) );
						endif;
						?>
						</strong>
					</p>
				</div>

				<div class="tutor-d-flex tutor-justify-center tutor-my-48">
					<button class="tutor-btn tutor-btn-outline-primary" data-tutor-modal-close>
						<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
					</button>
					<button id="tutor-subscription-early-renewal-submit" class="tutor-btn tutor-btn-primary tutor-ml-20" data-subscription-id="<?php echo esc_attr( $subscription_id ); ?>">
						<?php esc_html_e( 'Pay Now', 'tutor-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
