<?php
/**
 * Subscription List Page
 *
 * @package TutorPro\Addons
 * @subpackage Subscriptions\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Tutor\Helpers\DateTimeHelper;
use TUTOR\Input;
use TutorPro\Subscription\Controllers\SubscriptionListController;
use TutorPro\Subscription\Models\PlanModel;

/**
 * Determine active tab
 */
$active_tab = Input::get( 'data', 'all' );

$current_page = max( Input::get( 'paged', 1, Input::TYPE_INT ), 1 );
$limit        = (int) tutor_utils()->get_option( 'pagination_per_page', 10 );
$offset       = ( $limit * $current_page ) - $limit;

$search_query = Input::get( 'search', '' );

$controller         = new SubscriptionListController( false );
$subscription_query = $controller->get_list( $limit, $offset );
$subscriptions      = $subscription_query['results'];
$total_items        = $subscription_query['total_count'];

$navbar_data = array(
	'page_title'   => __( 'Subscriptions', 'tutor-pro' ),
	'tabs'         => $controller->tabs_key_value(),
	'active'       => $active_tab,
	'add_button'   => false,
	'button_title' => __( 'Add New', 'tutor-pro' ),
	'button_url'   => '#',
);

/**
 * Bulk action & filters
 */
$filters = array(
	'bulk_action'  => true,
	'bulk_actions' => $controller->prepare_bulk_actions(),
	'ajax_action'  => 'tutor_subscription_bulk_action',
	'filters'      => true,
);

?>

<div class="tutor-admin-wrap">
	<?php
		/**
		 * Load Templates with data.
		 */
		$navbar_template  = tutor()->path . 'views/elements/navbar.php';
		$filters_template = tutor()->path . 'views/elements/filters.php';
		tutor_load_template_from_custom_path( $navbar_template, $navbar_data );
		tutor_load_template_from_custom_path( $filters_template, $filters );
	?>
	<div class="tutor-admin-body">
		<div class="tutor-mt-24">
			<div class="tutor-table-responsive">

				<table class="tutor-table tutor-table-middle">
					<thead class="tutor-text-sm tutor-text-400">
						<tr>
							<th>
								<div class="tutor-d-flex">
									<input type="checkbox" id="tutor-bulk-checkbox-all" class="tutor-form-check-input" />
								</div>
							</th>
							<th class="tutor-table-rows-sorting">
								<?php esc_html_e( 'ID', 'tutor-pro' ); ?>
								<span class="a-to-z-sort-icon tutor-icon-ordering-a-z"></span>
							</th>
							<th>
								<?php esc_html_e( 'Plan', 'tutor-pro' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Amount', 'tutor-pro' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Student', 'tutor-pro' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Next Payment Date', 'tutor-pro' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Status', 'tutor-pro' ); ?>
							</th>
							<th  width="10%">
							<?php esc_html_e( 'Action', 'tutor-pro' ); ?>
							</th>
						</tr>
					</thead>

					<tbody>
						<?php if ( is_array( $subscriptions ) && count( $subscriptions ) ) : ?>
							<?php
							foreach ( $subscriptions as $key => $subscription ) :
								$plan      = $controller->plan_model->get_plan( $subscription->plan_id );
								$user_data = get_userdata( $subscription->user_id );
								?>
								<tr>
									<td>
										<div class="td-checkbox tutor-d-flex ">
											<input type="checkbox" class="tutor-form-check-input tutor-bulk-checkbox" name="tutor-bulk-checkbox-all" value="<?php echo esc_attr( $subscription->id ); ?>" />
										</div>
									</td>

									<td>
										<div class="tutor-fs-7">
											<?php echo esc_html( '#' . $subscription->id ); ?>
										</div>
									</td>

									<td>
										<div class="tutor-fs-7">
											<?php
											echo esc_html( $subscription->plan_name );
											$course_id = $controller->plan_model->get_course_id_by_plan( $subscription->plan_id );
											if ( $course_id ) :
												?>
												<div class="tutor-fs-7 tutor-fw-normal tutor-color-secondary tutor-mt-8">
												<strong class="tutor-fs-7 tutor-fw-medium"><?php esc_html_e( 'Course:', 'tutor-pro' ); ?> </strong>
													<a target="_blank" href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a>
												</div>
												<?php
											endif;
											?>
										</div>
									</td>

									<td>
										<div class="tutor-fs-7">
											<?php
												$controller->subscription_model->formatted_subscription_price( $subscription );
											?>
										</div>
									</td>

									<td>
										<div class="tutor-d-flex tutor-align-center">
											<?php
											echo wp_kses(
												tutor_utils()->get_tutor_avatar( $user_data, 'sm' ),
												tutor_utils()->allowed_avatar_tags()
											)
											?>
											<div class="tutor-ml-12">
												<a target="_blank" class="tutor-fs-7 tutor-table-link" href="<?php echo esc_url( tutor_utils()->profile_url( $user_data, true ) ); ?>">
													<?php echo esc_html( $user_data ? $user_data->display_name : '' ); ?>
												</a>
											</div>
										</div>
									</td>

									<td>
										<span class="tutor-fw-normal tutor-fs-7">
											<?php
											if ( ! empty( $subscription->next_payment_date_gmt ) ) :
												echo esc_html(
													PlanModel::PAYMENT_ONETIME === $plan->payment_type
													? __( 'N/A', 'tutor-pro' )
													: DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt )
												);
											endif;
											?>

										</span>
									</td>

									<td>
									<?php echo wp_kses_post( tutor_utils()->translate_dynamic_text( $subscription->status, true ) ); ?>
									</td>

									<td>
										<a href="<?php echo esc_url( $controller->get_subscription_page_url() . '&action=edit&id=' . $subscription->id ); ?>" class="tutor-btn tutor-btn-outline-primary tutor-btn-sm">
											<?php esc_html_e( 'Edit', 'tutor-pro' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="100%" class="column-empty-state">
									<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

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
			<!-- end table responsive -->
		</div>
	</div>
</div>
