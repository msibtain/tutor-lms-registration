<?php
/**
 * Subscription details
 *
 * @package TutorPro\Subscription
 * @subpackage Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 * @since 4.0.0 template updated.
 */

defined( 'ABSPATH' ) || exit;

use Tutor\Components\Badge;
use Tutor\Components\Button;
use Tutor\Components\ConfirmationModal;
use Tutor\Components\Constants\Positions;
use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\Pagination;
use Tutor\Components\Popover;
use TUTOR\Dashboard;
use Tutor\Ecommerce\CheckoutController;
use TUTOR\Icon;
use Tutor\Components\SvgIcon;
use Tutor\Components\EmptyState;
use Tutor\Ecommerce\Ecommerce;
use Tutor\Ecommerce\Settings;
use Tutor\Helpers\ComponentHelper;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\UrlHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;
use TUTOR\User;
use TutorPro\Subscription\Controllers\SubscriptionListController;
use TutorPro\Subscription\Models\SubscriptionModel;

$user_id       = get_current_user_id();
$current_page  = Input::get( 'current_page', 1, Input::TYPE_INT );
$item_per_page = (int) tutor_utils()->get_option( 'pagination_per_page', 10 );
$offset        = ( $item_per_page * max( $current_page, 1 ) ) - $item_per_page;

$page_link = tutor_utils()->get_tutor_dashboard_page_permalink( 'subscriptions' );

$subscription_id = max( Input::get( 'id', 1, Input::TYPE_INT ), 1 );
$controller      = new SubscriptionListController( false );
$subscription    = $controller->subscription_model->get_row(
	array(
		'user_id' => $user_id,
		'id'      => $subscription_id,
	)
);

if ( ! $subscription ) {
	EmptyState::make()->title( tutor_utils()->not_found_text() )->render();
	return;
}

$plan                = $controller->plan_model->get_plan( $subscription->plan_id );
$payment_types       = $controller->plan_model->get_payment_type_list();
$order_history_query = $controller->subscription_model->get_subscription_order_history( $subscription_id, $item_per_page, $offset );
$order_history       = $order_history_query['results'];
$total_items         = $order_history_query['total_count'];
$date_format         = 'd M, Y h:i a';

// Settings.
$can_cancel_anytime = (bool) tutor_utils()->get_option( 'subscription_cancel_anytime', true );
$can_early_renewal  = (bool) tutor_utils()->get_option( 'subscription_early_renewal', false );

$active_payment_method = $controller->subscription_model->get_active_payment_method( $subscription );
$show_start_date       = $subscription->start_date_gmt !== $subscription->next_payment_date_gmt;

$order_type_list        = $controller->order_model->get_order_type_list();
$is_plan_course_deleted = $controller->plan_model->is_plan_course_deleted( $plan );
$recurring_limit        = (int) $plan->recurring_limit;

$plan_access = '';
if ( $controller->plan_model->is_membership_plan( $plan ) ) {
	$plan_access = $controller->plan_model->get_type_label( $plan->plan_type, __( 'Access', 'tutor-pro' ) );
} else {
	$object_id = $controller->plan_model->get_object_id_by_plan( $subscription->plan_id );
	if ( $object_id ) :
		$plan_access = $controller->plan_model->get_type_label( $plan->plan_type );
	endif;
}
?>

<div class="tutor-subscription-details" x-data="tutorDashboardSubscriptions()">
	<div class="tutor-subscription-overview">
		<!-- Overview -->
		<div class="tutor-flex tutor-justify-between tutor-w-full">
			<div class="tutor-flex tutor-flex-column">
				<div class="tutor-subscription-overview-subtitle">
					<?php
					printf(
						// translators: Subscription ID.
						esc_html__( 'Subscription ID: #%s', 'tutor-pro' ),
						esc_html( $subscription->id )
					);
					?>
				</div>
				<div class="tutor-subscription-overview-title">
					<?php echo esc_html( $plan->plan_name ); ?>
				</div>
				<div class="tutor-subscription-overview-badge">
					<?php SvgIcon::make()->name( Icon::CHECK_2 )->render(); ?>
					<?php
					if ( $controller->plan_model->is_membership_plan( $plan ) ) {
						echo esc_html( $plan_access );
					} else {
						$object_id = $controller->plan_model->get_object_id_by_plan( $subscription->plan_id );
						if ( $object_id ) {
							echo esc_html( get_the_title( $object_id ) );
						}
					}
					?>
				</div>
			</div>
			<div class="tutor-flex tutor-flex-column tutor-items-end tutor-justify-between">
				<div class="tutor-flex tutor-items-center tutor-gap-4 tutor-mb-auto">
					<div>
						<?php SubscriptionModel::render_subscription_status_badge( $subscription->status ); ?>
					</div>

					<?php
					$option_items = array();
					if ( $can_early_renewal
							&& SubscriptionModel::STATUS_ACTIVE === $subscription->status
							&& $controller->subscription_model->should_renew_subscription( $subscription )
							&& $controller->subscription_model->can_process_silent_payment( $subscription )
							&& ! $is_plan_course_deleted
						) {

						$option_items[] = array(
							'tag'     => 'a',
							'content' => __( 'Renew Now', 'tutor-pro' ),
							'attr'    => array(
								'href'   => '#',
								'@click' => "TutorCore.modal.showModal('renew-subscription-modal', { subscriptionId:  {$subscription->id} });",
							),
						);

						// Renew Modal.
						$renewal_message = '';
						if ( ! empty( $subscription->next_payment_date_gmt ) ) {
							$next_payment_date_gmt = DateTimeHelper::create( $subscription->next_payment_date_gmt )
																->add( $plan->recurring_value, $plan->recurring_interval )
																->to_date_time_string();

							$renewal_message = sprintf(
								// translators: %s: next payment date.
								__( 'By renewing your subscription early, your next payment date will be %s', 'tutor-pro' ),
								DateTimeHelper::get_gmt_to_user_timezone_date( $next_payment_date_gmt )
							);
						}

						ConfirmationModal::make()
								->id( 'renew-subscription-modal' )
								->title( __( 'Early Renewal', 'tutor-pro' ) )
								->icon( tutor_utils()->get_themed_svg( 'images/illustrations/early-renewal.svg' ), 80, 80, ConfirmationModal::ICON_TYPE_HTML )
								->message( $renewal_message )
								->confirm_handler( 'handleEarlyRenewSubscription(payload?.subscriptionId)' )
								->mutation_state( 'earlyRenewSubscriptionMutation' )
								->confirm_text( __( 'Confirm Renewal', 'tutor-pro' ) )
								->render();
					}

					if ( ! $is_plan_course_deleted
						&& in_array( $subscription->status, array( SubscriptionModel::STATUS_CANCELLED, SubscriptionModel::STATUS_EXPIRED ), true )
					) {
						$plan_buy_link  = add_query_arg( array( 'plan' => $plan->id ), CheckoutController::get_page_url() );
						$option_items[] = array(
							'tag'     => 'a',
							'content' => __( 'Resubscribe', 'tutor-pro' ),
							'attr'    => array( 'href' => $plan_buy_link ),
						);
					}

					$cancellable_status = array( SubscriptionModel::STATUS_PENDING, SubscriptionModel::STATUS_ACTIVE );
					if ( $can_cancel_anytime
						&& in_array( $subscription->status, $cancellable_status, true )
						&& $subscription->auto_renew
					) {
						$option_items[] = array(
							'tag'     => 'a',
							'content' => __( 'Cancel Plan', 'tutor-pro' ),
							'attr'    => array(
								'href'   => '#',
								'@click' => "TutorCore.modal.showModal('subscription-cancel-modal', { subscriptionId:  {$subscription->id} });",
							),
						);

						// Cancel modal.
						ConfirmationModal::make()
								->id( 'subscription-cancel-modal' )
								->title( __( 'Cancel Plan', 'tutor-pro' ) )
								->icon( tutor_utils()->get_themed_svg( 'images/illustrations/cancel-plan.svg' ), 80, 80, ConfirmationModal::ICON_TYPE_HTML )
								->message( __( 'Are you sure you want to cancel the plan? Please confirm your choice', 'tutor-pro' ) )
								->confirm_handler( 'handleCancelSubscription(payload?.subscriptionId)' )
								->mutation_state( 'cancelSubscriptionMutation' )
								->confirm_text( __( 'Cancel Plan', 'tutor-pro' ) )
								->cancel_text( __( 'Keep Plan', 'tutor-pro' ) )
								->render();
					}

					if ( count( $option_items ) ) {
						$trigger_button = Button::make()
							->label( __( 'More options', 'tutor-pro' ) )
							->variant( Variant::GHOST )
							->size( Size::X_SMALL )
							->icon( Icon::ELLIPSES )
							->icon_only()
							->attr( 'x-ref', 'trigger' )
							->attr( '@click', 'toggle()' )
							->get();

						$options = Popover::make()->placement( Positions::BOTTOM_END )->trigger( $trigger_button );
						foreach ( $option_items as $option ) {
							$options->menu_item( $option );
						}
						$options->render();
					}
					?>
				</div>
				<div class="tutor-text-small tutor-flex tutor-items-center tutor-gap-2">
					<span class="tutor-text-subdued"><?php esc_html_e( 'Timezone: ', 'tutor-pro' ); ?></span>
					<span class="tutor-text-secondary"><?php echo esc_html( User::get_user_timezone_string() ); ?></span>
				</div>
			</div>
		</div>

		<?php if ( $subscription->is_trial_enabled && ! empty( $subscription->trial_end_date_gmt ) ) : ?>
		<div class="tutor-w-full">
			<?php
			// translators: %s: trial end date.
			$trial_end_date = sprintf( __( 'Your trial will be end on %s', 'tutor-pro' ), DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->trial_end_date_gmt ) );
			?>
			<div class="tutor-alert tutor-alert-warning tutor-rounded-md tutor-py-3 tutor-min-h-fit">
				<div class="tutor-alert-content">
					<div class="tutor-alert-icon">
						<?php
						Badge::make()->label( __( 'Trial', 'tutor-pro' ) )
								->attr( 'class', 'tutor-border tutor-border-warning-secondary' )
								->variant( Badge::WARNING )->rounded()->render();
						?>
					</div>
					<div class="tutor-alert-text tutor-m-auto"><?php echo esc_html( $trial_end_date ); ?></div>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Overview Cards -->
		<div class="tutor-subscription-overview-cards">
			<!-- Amount -->
			<div class="tutor-subscription-overview-card">
				<div class="tutor-text-tiny tutor-text-secondary">
					<?php esc_html_e( 'Amount', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-subscription-overview-card-title">
					<?php $controller->subscription_model->formatted_subscription_price( $subscription ); ?>
				</div>
			</div>

			<!-- Renew -->
			<div class="tutor-subscription-overview-card">
				<div class="tutor-text-tiny tutor-text-secondary">
					<?php esc_html_e( 'Renew', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-subscription-overview-card-title">
					<?php echo esc_html( $controller->plan_model->prepare_renew_text( $recurring_limit ) ); ?>
				</div>
			</div>

			<!-- Active payment method -->
			<div class="tutor-subscription-overview-card">
				<div class="tutor-text-tiny tutor-text-secondary">
					<?php esc_html_e( 'Active Payment Method', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-subscription-overview-card-title">
					<?php
					$gateway_config       = Settings::get_payment_gateway_settings( $active_payment_method ?? '' );
					$icon_url             = $gateway_config['icon'] ?? '';
					$payment_method_label = Ecommerce::get_payment_method_label( $active_payment_method );
					?>
					<?php if ( ! empty( $icon_url ) ) : ?>
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $payment_method_label ); ?>">
					<?php endif; ?>
					<?php echo esc_html( $payment_method_label ); ?>
				</div>
			</div>
		</div>

		<?php
		if ( $is_plan_course_deleted ) :
			?>
			<div class="tutor-subscription-details-alert">	
				<div>
					<div><?php SvgIcon::make()->name( Icon::WARNING )->size( 20 )->render(); ?></div>
					<div class="tutor-text-tiny">
						<?php esc_html_e( 'This course has been deleted. No further actions are available for this subscription.', 'tutor-pro' ); ?>
					</div>
				</div>			
			</div>
			<?php
		endif;

		if ( SubscriptionModel::STATUS_ACTIVE === $subscription->status
							&& 0 === (int) $subscription->auto_renew
							&& ! in_array( $active_payment_method, array( OrderModel::PAYMENT_METHOD_MANUAL, OrderModel::PAYMENT_METHOD_FREE ), true )
						) {
			?>
		<div class="tutor-subscription-details-alert">
			<div>
				<div><?php SvgIcon::make()->name( Icon::WARNING )->size( 20 )->render(); ?></div>
				<div class="tutor-text-tiny">
				<?php
				echo esc_html(
					sprintf(
					// translators: %s: date time.
						__( 'Your plan will be cancelled on %s. You\'ll have access until then and can resume anytime before that.', 'tutor-pro' ),
						empty( $subscription->next_payment_date_gmt ) ? '' : DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt, $date_format )
					)
				);
				?>
				</div>
			</div>
			<div>
			<?php
			Button::make()
					->label( __( 'Resume Plan', 'tutor-pro' ) )
					->attr( '@click', 'handleResumeSubscription(' . $subscription->id . ')' )
					->attr( ':class', 'resumeSubscriptionMutation?.isPending ? "tutor-btn-loading" : ""' )
					->attr( ':disabled', 'resumeSubscriptionMutation?.isPending' )
					->render();
			?>
			</div>
		</div>
		<?php } else { ?>
		<!-- Info -->
		<div class="tutor-subscription-overview-info">
			<!-- Active Since -->
			<div class="tutor-text-tiny">
				<span class="tutor-text-subdued">
					<?php esc_html_e( 'Active Since', 'tutor-pro' ); ?>
				</span>
				<span class="tutor-text-secondary">
					<?php echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->created_at_gmt ) ); ?>
				</span>
			</div>

			<!-- Next Payment -->
			<div class="tutor-text-tiny">
				<span class="tutor-text-subdued">
					<?php esc_html_e( 'Next Payment', 'tutor-pro' ); ?>
				</span>
				<span class="tutor-text-success">
					<?php echo esc_html( empty( $subscription->next_payment_date_gmt ) ? '' : DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt ) ); ?>
				</span>
			</div>
		</div>
			<?php } ?>
	</div>

	<!-- Payment History -->
	<div class="tutor-subscription-payments">
		<div class="tutor-subscription-payments-header">
			<div class="tutor-subscription-payments-title">
				<?php SvgIcon::make()->name( Icon::BILLING )->size( 20 )->render(); ?>
				<?php esc_html_e( 'Payment History', 'tutor-pro' ); ?>
			</div>
			<?php
			Badge::make()
				->attr( 'class', 'tutor-py-none' )
				->rounded()
				->label(
					sprintf(
						// translators: %s is either 'On' or 'Off'.
						__( 'Auto-Renewal %s', 'tutor-pro' ),
						$subscription->auto_renew ? esc_html__( 'On', 'tutor-pro' ) : esc_html__( 'Off', 'tutor-pro' )
					)
				)
				->render();
			?>
		</div>

		<?php if ( count( $order_history ) ) : ?>
		<!-- Payment History Cards -->
		<div class="tutor-subscription-payments-list">
			<?php
			foreach ( $order_history as $item ) :
				$order_details = $controller->order_model->get_order_by_id( $item->id );
				?>
				<div class="tutor-subscription-payments-item">
					<div class="tutor-billing-card-details tutor-flex-wrap">
						<div class="tutor-billing-card-id">
							<?php echo esc_html( '#' . $item->id ); ?>
						</div>

						<div class="tutor-flex tutor-items-center tutor-flex-wrap tutor-gap-3">
							<span class="tutor-text-tiny">
								<?php echo empty( $item->created_at_gmt ) ? '' : esc_attr( DateTimeHelper::get_gmt_to_user_timezone_date( $item->created_at_gmt ) ); ?>
							</span>

							<span class="tutor-section-separator-vertical tutor-sm-hidden"></span>

							<div class="tutor-billing-card-payment-method">
								<?php ComponentHelper::render_payment_method_badge( $item->payment_method ); ?>
							</div>
						</div>
					</div>

					<div class="tutor-flex tutor-gap-3 tutor-justify-end tutor-items-center tutor-flex-wrap">
						<div class="tutor-text-secondary tutor-text-small tutor-font-semibold"><?php echo esc_html( tutor_get_formatted_price( $item->total_price ) ); ?></div>
						<div>
						<?php
						if ( ! $is_plan_course_deleted ) {
							OrderModel::render_pay_button( $order_details );
						}
						do_action( 'tutor_dashboard_invoice_button', $order_details );
						?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

			<?php
			Pagination::make()
			->attr( 'class', 'tutor-px-6 tutor-py-6 tutor-border-t' )
			->current( $current_page )
			->total( $total_items )
			->limit( $item_per_page )
			->render();
			?>
		</div>
		<?php endif; ?>
	</div>
</div>
