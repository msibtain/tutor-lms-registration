<?php
/**
 * Course subscription price
 *
 * @package TutorPro\Addons
 * @subpackage Subscription\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

$regular_price = $lowest_price_plan->regular_price;
$sale_price    = $plan_model->in_sale_price( $lowest_price_plan ) ? $lowest_price_plan->sale_price : null;

$tax_collection = $plan_model->is_tax_enabled_for_plan( $lowest_price_plan->id );
$price_info     = tutor_utils()->get_prices_with_tax_info( $lowest_price_plan->regular_price, $sale_price, $tax_collection );
?>

<div class="list-item-price tutor-d-flex tutor-align-center">
	<span class="price">
		<div class="tutor-course-price-tax tutor-fs-8 tutor-fw-normal tutor-color-hints"><?php esc_html_e( 'Starts from', 'tutor-pro' ); ?></div>
		<div class="list-item-price tutor-item-price">
			<span><?php tutor_print_formatted_price( $price_info->display_price ); ?></span>
			<?php if ( $plan_model->in_sale_price( $lowest_price_plan ) ) : ?>
			<del><?php tutor_print_formatted_price( $price_info->regular_price ); ?></del>
			<?php endif; ?>
		</div>
		<?php if ( $price_info->show_incl_tax_label ) : ?>
		<div class="tutor-course-price-tax tutor-fs-8 tutor-fw-normal tutor-color-black"><?php esc_html_e( 'Incl. tax', 'tutor-pro' ); ?></div>
		<?php endif; ?>
	</span>
</div>
