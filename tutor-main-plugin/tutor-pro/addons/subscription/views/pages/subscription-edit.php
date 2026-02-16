<?php
/**
 * Subscription Edit Page
 *
 * @package TutorPro\Addons
 * @subpackage Subscriptions\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

use Tutor\Ecommerce\BillingController;
use Tutor\Ecommerce\Ecommerce;
use Tutor\Ecommerce\OrderController;
use Tutor\Helpers\DateTimeHelper;
use TUTOR\Input;
use TutorPro\Subscription\Controllers\SubscriptionListController;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subscription_id = Input::get( 'id', 0, Input::TYPE_INT );
if ( ! $subscription_id ) {
	return;
}

$controller   = new SubscriptionListController( false );
$subscription = $controller->subscription_model->get_subscription( $subscription_id );
if ( ! $subscription ) {
	return;
}

$plan                  = $controller->plan_model->get_plan( $subscription->plan_id );
$payment_history       = $controller->subscription_model->get_subscription_orders( $subscription );
$payment_history_list  = $payment_history['results'];
$payment_history_count = $payment_history['total_count'];
$student               = get_userdata( $subscription->user_id );

$billing_controller = new BillingController( false );
$billing_info       = $billing_controller->get_billing_info( $subscription->user_id );

$course_id = $controller->plan_model->get_course_id_by_plan( $plan->id );
$course    = null;
if ( $course_id ) {
	$course = get_post( $course_id );
}

$current_page   = max( Input::get( 'paged', 1, Input::TYPE_INT ), 1 );
$limit          = (int) tutor_utils()->get_option( 'pagination_per_page', 10 );
$offset         = ( $limit * $current_page ) - $limit;
$date_format    = 'Y-m-d H:i';
$current_url    = tutor()->current_url;
$order_page_url = ( new OrderController( false ) )->get_order_page_url();
?>

<div class="tutor-admin-wrap">
	<div class="tutor-wp-dashboard-header tutor-px-24">
		<div class="tutor-row tutor-align-lg-center">
			<div class="tutor-col-lg">
				<div class="tutor-d-lg-flex tutor-align-lg-center tutor-gap-1 tutor-px-12 tutor-py-16">
					<a class="tutor-iconic-btn tutor-iconic-btn-secondary" href="<?php echo esc_url( $controller->subscription_model->get_subscription_list_url( 'backend' ) ); ?>">
						<i class="tutor-icon-previous"></i>
					</a>
					<span class="tutor-fs-5 tutor-fw-medium">
						<?php echo esc_html( $plan->plan_name ); ?>
					</span>

					<span class="tutor-fs-7 tutor-color-muted">
						<?php echo wp_kses_post( tutor_utils()->translate_dynamic_text( $subscription->status, true ) ); ?>
					</span>
				</div>
			</div>

			<div class="tutor-col-lg">
				<div class="tutor-d-flex tutor-align-center tutor-justify-end tutor-gap-2">
					<select id="tutor-subscription-status-field" class="tutor-form-control" style="max-width: 200px;">
						<?php
						$status_list = ( new SubscriptionModel() )->get_status_list( array( SubscriptionModel::STATUS_EXPIRED ) );
						foreach ( $status_list as $value => $label ) :
							?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $subscription->status, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<!-- <button class="tutor-btn tutor-btn-primary"><?php esc_html_e( 'Change plan', 'tutor-pro' ); ?></button> -->
				</div>
			</div>
		</div>
	</div>

	<div class="tutor-p-20">
		<!-- <div class="tutor-admin-container"> -->
			<div class="tutor-row tutor-g-4">
				<div class="tutor-col-md-9">
					<h3 class="tutor-fs-6 tutor-fw-medium tutor-color-subdued tutor-mt-0">
						<?php esc_html_e( 'Subscription Details', 'tutor-pro' ); ?>
					</h3>

					<div class="tutor-row">
						<div class="tutor-col-md-6">
							<div class="tutor-card tutor-no-border">
								<div class="tutor-px-16">
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-border-bottom">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Subscription ID:', 'tutor-pro' ); ?>
										</div>
										<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">#<?php echo esc_html( $subscription_id ); ?></strong>
									</div>
									<?php if ( $course ) : ?>
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-border-bottom">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Course:', 'tutor-pro' ); ?>
										</div>
										<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
											<a target="_blank" href="<?php echo esc_url( get_the_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a>
										</strong>
									</div>
									<?php endif; ?>
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-border-bottom">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Amount:', 'tutor-pro' ); ?>
										</div>
										<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
											<?php $controller->subscription_model->formatted_subscription_price( $subscription ); ?>
										</strong>
									</div>

									<?php if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) : ?>
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Renew:', 'tutor-pro' ); ?>
										</div>
										<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
											<?php echo '0' === $plan->recurring_limit ? esc_html_e( 'Until cancelled', 'tutor-pro' ) : esc_attr( sprintf( _n( '%s Time', '%s Times', $plan->recurring_limit, 'tutor-pro' ), $plan->recurring_limit ) ); //phpcs:ignore ?>
										</strong>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<div class="tutor-col-md-6">
							<div class="tutor-card tutor-no-border">
								<div class="tutor-px-16 tutor-text-capitalize">
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-border-bottom">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Payment:', 'tutor-pro' ); ?>
										</div>
										<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
											<?php echo esc_html( $plan->payment_type ); ?>
										</strong>
									</div>
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-border-bottom">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Timezone:', 'tutor-pro' ); ?>
										</div>
										<div class="tutor-d-flex tutor-align-center tutor-gap-1">
											<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
												<?php echo esc_html( wp_timezone_string() ); ?>
											</strong>
										</div>
									</div>

									<?php if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) : ?>
										<?php
										// TODO: later will implement trial.
										if ( false ) :
											?>
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-border-bottom tutor-dropdown-parent">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Trial End Date:', 'tutor-pro' ); ?>
										</div>
										<div class="tutor-d-flex tutor-align-center tutor-gap-1">
											<?php if ( empty( ! $subscription->trial_end_date_gmt ) ) : ?>
											<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
												<?php
												echo esc_html(
													DateTimeHelper::get_gmt_to_user_timezone_date(
														$subscription->trial_end_date_gmt,
														$date_format
													)
												);
												?>
											</strong>
												<?php if ( PlanModel::PAYMENT_ONETIME !== $plan->payment_type && $subscription->first_order_id === $subscription->active_order_id ) : ?>
												<button class="tutor-btn tutor-btn-ghost" action-tutor-dropdown="toggle">
													<i class="tutor-icon-pencil"></i>
												</button>

												<div class="tutor-dropdown">
													<form class="tutor-subscription-update-form" method="POST" data-tutor-copy-target>
														<?php tutor_nonce_field(); ?>
														<input type="hidden" name="action" value="tutor_subscription_update">
														<input type="hidden" name="subscription_id" value="<?php echo esc_attr( $subscription_id ); ?>">

														<div class="tutor-px-16 tutor-pt-8">
															<div 
																class="tutor-v2-date-time-picker" 
																data-inline="true"
																data-disable_previous="true"
																data-input_name="trial_end_date_gmt"
																data-input_value="
																	<?php
																	echo esc_attr(
																		DateTimeHelper::get_gmt_to_user_timezone_date(
																			$subscription->trial_end_date_gmt,
																			$date_format
																		)
																	);
																	?>
																">
															</div>
														</div>

														<div class="tutor-d-flex tutor-justify-end tutor-border-top tutor-mt-16 tutor-p-16 tutor-pb-8">
															<button class="tutor-btn tutor-btn-outline-primary" action-tutor-dropdown="toggle">
																<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
															</button>
															<button type="submit" class="tutor-btn tutor-btn-primary tutor-ml-16">
																<?php esc_html_e( 'Confirm', 'tutor-pro' ); ?>
															</button>
														</div>
													</form>
												</div>
											<?php endif; ?>
											<?php endif; ?>
										</div>
									</div>
										<?php endif; ?>
									<?php endif; ?>

									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-border-bottom">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Start Date:', 'tutor-pro' ); ?>
										</div>
										<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
											<?php echo empty( $subscription->start_date_gmt ) ? '' : esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->start_date_gmt, $date_format ) ); ?>
										</strong>
									</div>

									<?php if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) : ?>
									<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-py-12 tutor-dropdown-parent">
										<div class="tutor-color-subdued">
											<?php esc_html_e( 'Next Payment Date:', 'tutor-pro' ); ?>
										</div>
										<div class="tutor-d-flex tutor-align-center tutor-gap-1">
											<?php if ( empty( ! $subscription->next_payment_date_gmt ) ) : ?>
											<strong class="tutor-fs-7 tutor-fw-medium tutor-color-black">
												<?php
												echo esc_html(
													DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt, $date_format )
												);
												?>
											</strong>
												<?php if ( PlanModel::PAYMENT_ONETIME !== $plan->payment_type ) : ?>
											<button class="tutor-btn tutor-btn-ghost" action-tutor-dropdown="toggle">
												<i class="tutor-icon-pencil"></i>
											</button>

											<div class="tutor-dropdown">
												<form class="tutor-subscription-update-form" method="POST" data-tutor-copy-target>
													<?php tutor_nonce_field(); ?>
													<input type="hidden" name="action" value="tutor_subscription_update">
													<input type="hidden" name="subscription_id" value="<?php echo esc_attr( $subscription_id ); ?>">

													<div class="tutor-px-16 tutor-pt-8">
														<div 
															class="tutor-v2-date-time-picker" 
															data-inline="true"
															data-disable_previous="true"
															data-input_name="next_payment_date_gmt"
															data-input_value="
																<?php
																echo esc_attr(
																	DateTimeHelper::get_gmt_to_user_timezone_date(
																		$subscription->next_payment_date_gmt,
																		$date_format
																	)
																);
																?>
															">
														</div>
													</div>

													<div class="tutor-d-flex tutor-justify-end tutor-border-top tutor-mt-16 tutor-p-16 tutor-pb-8">
														<button class="tutor-btn tutor-btn-outline-primary" action-tutor-dropdown="toggle">
															<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
														</button>
														<button type="submit" class="tutor-btn tutor-btn-primary tutor-ml-16">
															<?php esc_html_e( 'Confirm', 'tutor-pro' ); ?>
														</button>
													</div>
												</form>
											</div>
											<?php endif ?>
											<?php endif; ?>
										</div>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

					<h3 class="tutor-fs-6 tutor-fw-medium tutor-color-subdued tutor-mt-24">
						<?php esc_html_e( 'Payment History', 'tutor-pro' ); ?>
					</h3>

					<?php if ( count( $payment_history_list ) ) : ?>
					<div class="tutor-table-responsive">
						<table class="tutor-table tutor-table-middle table-dashboard-course-list">
							<thead class="tutor-text-sm tutor-text-400">
								<tr>
									<th class="tutor-table-rows-sorting">
										<?php esc_html_e( 'Order ID', 'tutor-pro' ); ?>
									</th>
									<th>
										<?php esc_html_e( 'Plan Type', 'tutor-pro' ); ?>
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
									<!-- <th>
										<?php esc_html_e( 'Invoice', 'tutor-pro' ); ?>
									</th> -->
								</tr>
							</thead>

							<tbody>
								<?php
								foreach ( $payment_history_list as $item ) :
									$url_args = array(
										'action'       => 'edit',
										'id'           => $item->id,
										'redirect_url' => rawurlencode( $current_url ),
									);
									?>
									<tr>
										<td>
											<a class="tutor-btn tutor-btn-link" href="<?php echo esc_url( add_query_arg( $url_args, $order_page_url ) ); ?>">
												<?php echo esc_html( '#' . $item->id ); ?>
											</a>
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
										<!-- <td>
											<a href="#"><?php esc_html_e( 'Download', 'tutor-pro' ); ?></a>
										</td> -->
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="tutor-mt-20">
						<div class="tutor-admin-page-pagination-wrapper tutor-mt-32">
							<?php
							/**
							 * Prepare pagination data & load template
							 */
							if ( $payment_history_count > $limit ) {
								$pagination_data     = array(
									'total_items' => $payment_history_count,
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
				<div class="tutor-col-md-3">
					<div class="tutor-card">
						<div class="tutor-card-header tutor-fw-medium tutor-color-black">
							<?php esc_html_e( 'Student Details', 'tutor-pro' ); ?>
						</div>
						<div class="tutor-card-body">
							<div class="tutor-d-flex tutor-align-center tutor-mb-4">
								<?php
								echo wp_kses(
									tutor_utils()->get_tutor_avatar( $student, 'sm' ),
									tutor_utils()->allowed_avatar_tags()
								)
								?>
								<div class="tutor-ml-12">
									<a target="_blank" class="tutor-fs-7 tutor-table-link" href="<?php echo esc_url( tutor_utils()->profile_url( $student, true ) ); ?>">
										<?php echo esc_html( tutor_utils()->get_user_name( $student ) ); ?>
									</a>
								</div>
							</div>

							<?php if ( $billing_info ) : ?>
							<div class="tutor-color-black tutor-mt-16 tutor-mb-4">
								<?php esc_html_e( 'Contact information', 'tutor-pro' ); ?>
							</div>
							<div class="tutor-color-subdued">
								<?php echo esc_html( $billing_info->billing_email ?? '' ); ?>
							</div>
							<div class="tutor-color-subdued">
								<?php echo esc_html( $billing_info->billing_phone ?? '' ); ?>
							</div>

							<div class="tutor-color-black tutor-mt-16 tutor-mb-4">
								<?php esc_html_e( 'Billing Address', 'tutor-pro' ); ?>
							</div>
							<div>
								<?php echo esc_html( $billing_info->billing_address ?? '' ); ?> </br>
								<?php echo esc_html( $billing_info->billing_city ?? '' ); ?>,
								<?php echo esc_html( $billing_info->billing_state ?? '' ); ?>,</br>
								<?php echo esc_html( $billing_info->billing_country ?? '' ); ?>,
								<?php echo esc_html( $billing_info->billing_zip_code ?? '' ); ?>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<!-- </div> -->
	</div>
</div>

<div class="tutor-modal tutor-subscription-status-change-modal">
	<div class="tutor-modal-overlay"></div>
	<div class="tutor-modal-window">
		<div class="tutor-modal-content tutor-modal-content-white">
			<button class="tutor-iconic-btn tutor-modal-close-o" data-tutor-modal-close>
				<span class="tutor-icon-times" area-hidden="true"></span>
			</button>

			<div class="tutor-modal-body tutor-text-center">
				<div class="tutor-fs-3 tutor-fw-medium tutor-color-black tutor-mb-12 tutor-mt-40">
					<?php esc_html_e( 'Are you sure?', 'tutor-pro' ); ?>
				</div>
				<div class="tutor-fs-6 tutor-color-muted">
					<div><?php esc_html_e( 'Are you sure you want to change the status?', 'tutor-pro' ); ?></div>
					<div><?php esc_html_e( 'Please confirm your choice.', 'tutor-pro' ); ?></div>
				</div>

				<form id="tutor-subscription-status-change-form" class="tutor-m-0" method="POST">
					<?php tutor_nonce_field(); ?>
					<input type="hidden" name="action" value="tutor_subscription_status_update">
					<input type="hidden" name="subscription_id" value="<?php echo esc_attr( $subscription_id ); ?>">
					<input type="hidden" name="status">
					<div class="tutor-d-flex tutor-justify-center tutor-my-48">
						<button class="tutor-btn tutor-btn-outline-primary" data-tutor-modal-close>
							<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
						</button>
						<button type="submit" class="tutor-btn tutor-btn-primary tutor-ml-16">
							<?php esc_html_e( "Yes, I’m sure", 'tutor-pro' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
