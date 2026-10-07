<?php
/**
 * Invoice template
 *
 * @package TutorPro\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

use Tutor\Helpers\DateTimeHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;
use TutorPro\Ecommerce\Invoice;
use Tutor\Ecommerce\Ecommerce;
use TUTOR\Icon;
use Tutor\Components\SvgIcon;
use TUTOR\User;

$order_model = new OrderModel();
$user_id     = get_current_user_id();
$order_id    = Input::get( Invoice::QUERY_PARAM, 0, Input::TYPE_INT );
$params      = array( 'id' => $order_id );

if ( ! User::is_admin() ) {
	$params['user_id'] = $user_id;
}

$record = $order_model->get_row( $params );

if ( ! $record ) {
	wp_die( esc_html( tutor_utils()->error_message() ) );
}

$order_data = $order_model->get_order_by_id( $order_id );

if ( ! $order_data || ! Invoice::should_show_invoice( $order_data ) ) {
	wp_die( esc_html( tutor_utils()->error_message() ) );
	return;
}

$site_url        = site_url();
$site_name       = get_bloginfo( 'name' );
$placeholder_url = tutor()->url . 'assets/images/placeholder.svg';

$billing_info = OrderModel::get_order_billing_address( $order_id, $order_data->user_id );

$billing_full_name = $billing_info->full_name;
$billing_email     = $billing_info->email;
$billing_phone     = $billing_info->phone;
$billing_zip_code  = $billing_info->zip_code;
$billing_address   = $billing_info->address;
$billing_city      = $billing_info->city;

$from_address               = tutor_utils()->get_option( 'invoice_from_address', '' );
$subscription_order         = in_array( $order_data->order_type, array( OrderModel::TYPE_SUBSCRIPTION, OrderModel::TYPE_RENEWAL ), true );
$subscription_addon_enabled = tutor_utils()->is_addon_enabled( 'subscription' );

$plan_id            = null;
$plan_info          = null;
$is_membership_plan = false;
if ( $subscription_order ) {
	$plan_id   = $order_data->items[0]->id ?? 0;
	$plan_info = apply_filters( 'tutor_get_plan_info', null, $plan_id );
	if ( $plan_info && isset( $plan_info->is_membership_plan ) && $plan_info->is_membership_plan ) {
		$is_membership_plan = true;
	}
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>

	<!-- We need this style to generate pdf correctly using html2canvas -->
	<style>
		body {
			line-height: 0.5 !important;
		}
	</style>
</head>
<body <?php body_class( '' ); ?>>
	<?php wp_body_open(); ?>
<div class="tutor-invoice-page-wrapper">
<div class="tutor-invoice-container">
	<div class="tutor-invoice-card tutor-p-4">
		<div class="tutor-flex tutor-items-center tutor-justify-between">
			<a href="<?php echo esc_url( tutor_utils()->tutor_dashboard_url() ); ?>" class="tutor-btn tutor-btn-secondary tutor-btn-x-small tutor-btn-icon">
				<?php SvgIcon::make()->name( Icon::LEFT )->flip_rtl()->render(); ?>
			</a>

			<button type="button" id="tutor-download-invoice" data-order-id="<?php echo esc_attr( $order_data->id ); ?>" class="tutor-btn tutor-gap-1 tutor-btn-primary tutor-btn-x-small">
				<?php SvgIcon::make()->name( Icon::DOWNLOAD_2 )->render(); ?>
				<?php esc_html_e( 'Download Invoice', 'tutor-pro' ); ?>
			</button>
		</div>
	</div>

	<div class="tutor-invoice-card tutor-mt-8 tutor-p-10">
		<div class="tutor-invoice-wrapper">
			<div id="tutor-invoice-content" class="tutor-invoice">
				<div class="invoice-header tutor-border-b tutor-pb-8">
					<div class="invoice-header-left">
						<div class="tutor-text-tiny tutor-text-subdued tutor-mb-4"><?php esc_html_e( 'BILL FORM', 'tutor-pro' ); ?></div>
						<div class="tutor-font-bold tutor-text-tiny tutor-text-primary"><?php echo esc_html( $site_name ); ?></div>
						<div class="tutor-text-tiny-2 tutor-color-subdued">
							<?php
							echo wp_kses(
								nl2br( $from_address ),
								array(
									'br' => array(),
								)
							);
							?>
						</div>
					</div>
					<?php
					if ( has_custom_logo() ) :
						$custom_logo_id = get_theme_mod( 'custom_logo' );
						$logo_url       = wp_get_attachment_image_url( $custom_logo_id, 'full' );
						?>
						<div class="site-logo">
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>">
						</div>
					<?php endif; ?>
				</div>

				<div class="invoice-info tutor-flex tutor-justify-between tutor-flex-wrap tutor-gap-8 tutor-fs-8 tutor-py-8">
					<div class="">
						<div class="tutor-text-tiny tutor-text-subdued tutor-mb-4"><?php esc_html_e( 'BILL TO', 'tutor-pro' ); ?></div>
						<div class="tutor-font-bold tutor-text-tiny tutor-text-primary"><?php echo esc_html( $billing_full_name ); ?></div>
						<div class="tutor-text-tiny-2 tutor-text-primary tutor-font-regular">
							<?php echo esc_html( $billing_email ); ?><br>
							<?php echo esc_html( $billing_phone ); ?><br>
							<?php echo esc_html( $billing_address ); ?><br>
							<?php echo esc_html( $billing_city ) . '-' . esc_html( $billing_zip_code ); ?>
						</div>
					</div>
					<div class="invoice-box">
						<div class="invoice-row">
							<span class="label tutor-text-tiny-2 tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Invoice No.', 'tutor-pro' ); ?></span>
							<span class="value tutor-text-tiny-2 tutor-text-primary tutor-font-regular">#<?php echo esc_html( $order_id ); ?></span>
						</div>

						<div class="invoice-row">
							<span class="label tutor-text-tiny-2 tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Invoice Date', 'tutor-pro' ); ?></span>
							<span class="value tutor-text-tiny-2 tutor-text-primary tutor-font-regular"><?php echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $order_data->created_at_gmt, 'd M, Y' ) ); ?></span>
						</div>

						<div class="invoice-row">
							<span class="label tutor-text-tiny-2 tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Payment Method', 'tutor-pro' ); ?></span>
							<span class="value tutor-text-tiny-2 tutor-text-primary tutor-font-regular"><?php echo esc_html( Ecommerce::get_payment_method_label( $order_data->payment_method ) ); ?></span>
						</div>

						<div class="invoice-row">
							<span class="label tutor-text-tiny-2 tutor-text-secondary"><?php esc_html_e( 'Status', 'tutor-pro' ); ?></span>
							<span class="status paid tutor-text-tiny tutor-fw-medium">
								<?php echo esc_html( ucwords( tutor_utils()->translate_dynamic_text( $order_data->payment_status ) ) ); ?>
							</span>
						</div>
					</div>
				</div>

				<table class="invoice-table">
					<thead>
						<tr class="tutor-border-b">
							<th class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Item', 'tutor-pro' ); ?></th>
							<?php
							if ( $subscription_addon_enabled && $plan_info ) :
								$label = __( 'Plan', 'tutor-pro' );
								if ( $is_membership_plan ) {
									$label = __( 'Access', 'tutor-pro' );
								}
								?>
								<th class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php echo esc_html( $label ); ?></th>
							<?php endif; ?>
							<th class="price tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Price', 'tutor-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $order_data->items as $item ) :
							$display_price  = $order_model->get_order_item_display_price( $item );
							$has_sale_price = is_numeric( $item->sale_price ) || is_numeric( $item->discount_price );
							$thumbnail_url  = $item->image ? $item->image : $placeholder_url;
							?>
						<tr class="tutor-border-b">
							<td>
								<div class="tutor-flex tutor-gap-4 tutor-pr-4">
								<?php
								if ( ! empty( $thumbnail_url ) ) :
									?>
								<div class="item-image">
									<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $item->title ); ?>">
								</div>

								<?php endif; ?>
									<div class="tutor-font-bold tutor-text-tiny tutor-text-primary"><?php echo esc_html( $item->title ); ?>
										<?php
										if ( 'course-bundle' === $item->type ) :
											$term_text = $item->total_courses > 1 ? __( 'Courses', 'tutor-pro' ) : __( 'Course', 'tutor-pro' );
											?>
											<div class="tutor-font-regular tutor-fs-8 tutor-color-hints">
											<?php
											// translators: %1$s: number of courses, %2$s: term text.
											echo esc_html( sprintf( __( '%1$s %2$s', 'tutor-pro' ), $item->total_courses, $term_text ) );
											?>
											</div>
										<?php endif; ?>
										<?php do_action( 'tutor_after_order_invoice_item_name', $order_data ); ?>
									</div>
								</div>
							</td>

							<?php
							if ( $subscription_addon_enabled && $plan_info ) :
								$plan_name = $plan_info->plan_name;
								if ( $is_membership_plan ) {
									$plan_name = 'full_site' === $plan_info->plan_type
											? __( 'Full Site', 'tutor-pro' )
											: __( 'Category', 'tutor-pro' );
								}
								?>
								<td class="tutor-text-tiny">
									<span class="tutor-pr-4"><?php echo esc_html( $plan_name ); ?></span>
								</td> 
							<?php endif; ?>

							<td class="price tutor-fw-medium tutor-text-tiny tutor-text-primary">
								<div class="tutor-text-primary tutor-mb-1 tutor-font-regular"><?php tutor_print_formatted_price( $display_price ); ?></div>
								<?php if ( $has_sale_price ) : ?>
								<del class="tutor-text-subdued"><?php tutor_print_formatted_price( $item->regular_price ); ?></del>
								<?php endif; ?>
							</td>
						</tr>
							<?php endforeach; ?>
					</tbody>
				</table>

				<div class="invoice-summary">
					<?php
					if ( isset( $order_data->subscription_fees ) ) :
						foreach ( $order_data->subscription_fees as $fee ) :
							?>
						<div>
							<div class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php echo esc_html( $fee['title'] ?? '' ); ?></div>
							<div class="tutor-text-tiny tutor-fw-medium tutor-text-primary"><?php tutor_print_formatted_price( $fee['value'] ?? 0 ); ?></div>
						</div>
							<?php
						endforeach;
					endif;
					?>
					<div>
						<div class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Subtotal', 'tutor-pro' ); ?></div>
						<div class="tutor-text-tiny tutor-fw-medium tutor-text-primary"><?php tutor_print_formatted_price( $order_data->subtotal_price ); ?></div>
					</div>
					<?php if ( $order_data->discount_amount > 0 ) : ?>
					<div>
						<div class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Discount', 'tutor-pro' ); ?> <span class="tutor-font-regular tutor-text-tiny-2">(<?php echo esc_html( $order_data->discount_reason ); ?>)</span></div>
						<div class="tutor-text-tiny tutor-fw-medium tutor-text-primary">- <?php tutor_print_formatted_price( $order_data->discount_amount ); ?></div>
					</div>
					<?php endif; ?>
					<?php if ( $order_data->coupon_amount > 0 ) : ?>
					<div>
						<div class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Coupon', 'tutor-pro' ); ?> <span class="tutor-font-regular tutor-text-tiny-2">(<?php echo esc_html( $order_data->coupon_code ); ?>)</span></div>
						<div class="tutor-text-tiny tutor-fw-medium tutor-text-primary">- <?php tutor_print_formatted_price( $order_data->coupon_amount ); ?></div>
					</div>
					<?php endif; ?>
					<?php if ( $order_model->has_exclusive_tax( $order_data ) ) : ?>
					<div>
						<div class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Tax', 'tutor-pro' ); ?> <span class="tutor-font-regular tutor-text-tiny-2">(<?php echo esc_html( $order_data->tax_rate . '%' ); ?>)</span></div>
						<div class="tutor-text-tiny tutor-fw-medium tutor-text-primary"><?php tutor_print_formatted_price( $order_data->tax_amount ); ?></div>
					</div>
					<?php endif; ?>
					<div class="tutor-border-t tutor-mt-5">
						<div class="tutor-text-tiny tutor-text-secondary tutor-font-regular"><?php esc_html_e( 'Total', 'tutor-pro' ); ?>
							<?php if ( $order_model->has_inclusive_tax( $order_data ) ) : ?>
								<span class="tutor-font-regular tutor-text-tiny-2">
								<?php
								// translators: %s: tax amount.
								echo esc_html( sprintf( __( '(Incl. tax %s)', 'tutor-pro' ), tutor_get_formatted_price( $order_data->tax_amount ) ) );
								?>
								</span>
							<?php endif; ?>	
						</div>
						<strong class="tutor-text-tiny tutor-text-primary tutor-font-bold"><?php tutor_print_formatted_price( $order_data->total_price ); ?></strong>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
