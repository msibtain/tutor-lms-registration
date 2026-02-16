<?php
/**
 * Invoice template
 *
 * @package TutorPro\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

use Tutor\Ecommerce\BillingController;
use Tutor\Helpers\DateTimeHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;
use TutorPro\Ecommerce\Invoice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$order_model = new OrderModel();
$order_id    = Input::get( 'invoice', 0, Input::TYPE_INT );
$order_data  = $order_model->get_order_by_id( $order_id );
if ( ! Invoice::should_show_invoice( $order_data ) ) {
	tutor_utils()->tutor_empty_state( __( 'No data found!', 'tutor-pro' ) );
	return;
}

$back_link       = tutor_utils()->get_tutor_dashboard_page_permalink( 'purchase_history' );
$site_url        = site_url();
$site_name       = get_bloginfo( 'name' );
$placeholder_url = tutor()->url . 'assets/images/placeholder.svg';

$user_data          = get_userdata( $order_data->user_id );
$billing_controller = new BillingController( false );
$billing_info       = $billing_controller->get_billing_info();
$billing_first_name = $billing_info->billing_first_name ?? '';
$billing_last_name  = $billing_info->billing_last_name ?? '';
$billing_email      = $billing_info->billing_email ?? '';
$billing_phone      = $billing_info->billing_phone ?? '';
$billing_zip_code   = $billing_info->billing_zip_code ?? '';
$billing_address    = $billing_info->billing_address ?? '';
$billing_country    = $billing_info->billing_country ?? '';
$billing_state      = $billing_info->billing_state ?? '';
$billing_city       = $billing_info->billing_city ?? '';

$from_address               = tutor_utils()->get_option( 'invoice_from_address', '' );
$subscription_order         = OrderModel::TYPE_SUBSCRIPTION === $order_data->order_type;
$subscription_addon_enabled = tutor_utils()->is_addon_enabled( 'subscription' );
$plan_info                  = null;
?>

<div class="tutor-d-flex tutor-justify-between tutor-mb-24">
	<a class="tutor-btn tutor-btn-ghost" href="<?php echo esc_url( $back_link ); ?>">
		<span class="tutor-icon-previous tutor-mr-8" area-hidden="true"></span>
		<?php esc_html_e( 'Back', 'tutor-pro' ); ?>
	</a>
	<button id="tutor-download-invoice" class="tutor-btn tutor-btn-secondary" data-order-id="<?php echo esc_attr( $order_data->id ); ?>">
		<i class="tutor-icon-download tutor-mr-4"></i>
		<?php esc_html_e( 'Download Invoice', 'tutor-pro' ); ?>
	</button>
</div>

<div class="tutor-invoice-wrapper">
	<div id="tutor-invoice-content" class="tutor-invoice">
		<div class="invoice-header">
			<div>
				<h1><?php esc_html_e( 'INVOICE', 'tutor-pro' ); ?> <span class="status"><?php echo esc_html( ucwords( $order_data->payment_status ) ); ?></span></h1>
				<p class="invoice-number">#<?php echo esc_html( $order_id ); ?></p>
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

		<div class="invoice-info tutor-fs-8 tutor-border-top tutor-border-bottom">
			<div class="tutor-p-16 tutor-pl-0 tutor-fw-bold tutor-border-right">
				<div class="tutor-mb-16">
					<div class="tutor-mb-4"><?php esc_html_e( 'Invoice date', 'tutor-pro' ); ?></div>
					<div class="tutor-color-subdued"><?php echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $order_data->created_at_gmt, 'd M, Y' ) ); ?></div>
				</div>
				<div class="tutor-mb-16">
					<div class="tutor-mb-4"><?php esc_html_e( 'Payment method', 'tutor-pro' ); ?></div>
					<div class="tutor-color-subdued"><?php echo esc_html( $order_data->payment_method ); ?></div>
				</div>
			</div>
			<div class="tutor-p-16 tutor-border-right">
				<div class="tutor-mb-12">
					<div class="tutor-fw-bold tutor-mb-4"><?php esc_html_e( 'Billed to', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-bold tutor-color-subdued"><?php echo esc_html( $billing_first_name . ' ' . $billing_last_name ); ?></div>
					<div class="tutor-color-subdued">
						<?php echo esc_html( $billing_email ); ?><br>
						<?php echo esc_html( $billing_phone ); ?><br>
						<?php echo esc_html( $billing_address ); ?><br>
						<?php echo esc_html( $billing_city ) . '-' . esc_html( $billing_zip_code ); ?>
					</div>
				</div>
			</div>
			<div class="tutor-p-16">
				<div class="tutor-mb-12">
					<div class="tutor-fw-bold tutor-mb-4"><?php esc_html_e( 'From', 'tutor-pro' ); ?></div>
					<div class="tutor-fw-bold tutor-color-subdued"><?php echo esc_html( $site_name ); ?></div>
					<div class="tutor-color-subdued">
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
			</div>
		</div>

		<table class="invoice-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Item', 'tutor-pro' ); ?></th>
					<?php if ( $subscription_order && $subscription_addon_enabled ) : ?>
						<th><?php esc_html_e( 'Plan', 'tutor-pro' ); ?></th> 
					<?php endif; ?>
					<th class="price"><?php esc_html_e( 'Price', 'tutor-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $order_data->items as $item ) :
					$display_price  = $item->sale_price
										? $item->sale_price
										: ( $item->discount_price ? $item->discount_price : $item->regular_price );
					$has_sale_price = $item->sale_price > 0 || $item->discount_price > 0;
					$thumbnail_url  = $item->image ? $item->image : $placeholder_url;
					$plan_info      = $item->plan_info ?? null;
					?>
				<tr>
					<td>
						<div class="tutor-d-flex tutor-gap-1">
						<?php
						if ( ! empty( $thumbnail_url ) ) :
							?>
						<div class="item-image">
							<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $item->title ); ?>">
						</div>

						<?php endif; ?>
							<div class="tutor-fw-bold"><?php echo esc_html( $item->title ); ?>
								<?php
								if ( 'course-bundle' === $item->type ) :
									$term_text = $item->total_courses > 1 ? __( 'Courses', 'tutor-pro' ) : __( 'Course', 'tutor-pro' );
									?>
									<div class="tutor-fw-normal tutor-fs-8 tutor-color-hints">
									<?php
									/* translators: %s: total, %s: courses */
									echo esc_html( sprintf( __( '%1$s %2$s', 'tutor-pro' ), $item->total_courses, $term_text ) );
									?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</td>

					<?php
					if ( $subscription_order && $subscription_addon_enabled && $plan_info ) :
						?>
						<td><?php echo esc_html( $plan_info['plan_name'] ?? '' ); ?></td> 
					<?php endif; ?>

					<td class="price tutor-fw-medium">
						<div class="tutor-color-black tutor-mb-4"><?php tutor_print_formatted_price( $display_price ); ?></div>
						<?php if ( $has_sale_price ) : ?>
						<del class="tutor-color-hints"><?php tutor_print_formatted_price( $item->regular_price ); ?></del>
						<?php endif; ?>
					</td>
				</tr>
					<?php endforeach; ?>
			</tbody>
		</table>

		<div class="invoice-summary">
			<?php
			if ( OrderModel::TYPE_SUBSCRIPTION === $order_data->order_type && isset( $order_data->subscription_fees ) ) :
				foreach ( $order_data->subscription_fees as $fee ) :
					?>
				<div>
					<div class="tutor-fw-bold tutor-color-black"><?php echo esc_html( $fee['title'] ?? '' ); ?></div>
					<div class="tutor-fw-medium tutor-color-subdued"><?php tutor_print_formatted_price( $fee['value'] ?? 0 ); ?></div>
				</div>
					<?php
				endforeach;
			endif;
			?>
			<div>
				<div class="tutor-fw-bold tutor-color-black"><?php esc_html_e( 'Subtotal', 'tutor-pro' ); ?></div>
				<div class="tutor-fw-medium tutor-color-subdued"><?php tutor_print_formatted_price( $order_data->subtotal_price ); ?></div>
			</div>
			<?php if ( $order_data->discount_amount > 0 ) : ?>
			<div>
				<div class="tutor-fw-bold tutor-color-black"><?php esc_html_e( 'Discount', 'tutor-pro' ); ?> <span class="tutor-fw-normal tutor-color-hints">(<?php echo esc_html( $order_data->discount_reason ); ?>)</span></div>
				<div class="tutor-fw-medium tutor-color-subdued">- <?php tutor_print_formatted_price( $order_data->discount_amount ); ?></div>
			</div>
			<?php endif; ?>
			<?php if ( $order_data->coupon_amount > 0 ) : ?>
			<div>
				<div class="tutor-fw-bold tutor-color-black"><?php esc_html_e( 'Coupon', 'tutor-pro' ); ?> <span class="tutor-fw-normal tutor-color-hints">(<?php echo esc_html( $order_data->coupon_code ); ?>)</span></div>
				<div class="tutor-fw-medium tutor-color-subdued">- <?php tutor_print_formatted_price( $order_data->coupon_amount ); ?></div>
			</div>
			<?php endif; ?>
			<?php if ( $order_model->has_exclusive_tax( $order_data ) ) : ?>
			<div>
				<div class="tutor-fw-bold tutor-color-black"><?php esc_html_e( 'Tax', 'tutor-pro' ); ?> <span class="tutor-fw-normal tutor-color-hints">(<?php echo esc_html( $order_data->tax_rate . '%' ); ?>)</span></div>
				<div class="tutor-fw-medium tutor-color-subdued"><?php tutor_print_formatted_price( $order_data->tax_amount ); ?></div>
			</div>
			<?php endif; ?>
			<div>
				<div class="tutor-fw-bold tutor-color-black"><?php esc_html_e( 'Total', 'tutor-pro' ); ?>
				<?php if ( $order_model->has_inclusive_tax( $order_data ) ) : ?>
				<span class="tutor-fw-normal tutor-color-hints">(<?php esc_html_e( 'Incl. tax', 'tutor-pro' ); ?> <?php tutor_print_formatted_price( $order_data->tax_amount ); ?>)</span>
				<?php endif; ?>	
				</div>
				<strong class="tutor-fw-bold tutor-color-black"><?php tutor_print_formatted_price( $order_data->total_price ); ?></strong>
			</div>
		</div>

		<div class="invoice-footer">
			<div class="footer-bottom">
				<div><?php echo esc_url( $site_url ); ?></div>
			</div>
		</div>
	</div>
</div>
