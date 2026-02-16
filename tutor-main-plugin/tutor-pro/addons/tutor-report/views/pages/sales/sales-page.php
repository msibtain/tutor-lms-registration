<?php
/**
 * Sale Report Page
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

use Tutor\Ecommerce\Ecommerce;
use Tutor\Models\OrderModel;

?>
<div id="tutor-report-sales" class="tutor-report-common">
	<div class="tutor-mx-n20">
		<?php tutor_load_template_from_custom_path( $filters_template, $filters ); ?>
	</div>

	<div class="tutor-report-sales-data-table tutor-mt-24">
		<?php if ( is_array( $lists ) && count( $lists ) ) : ?>
			<div class="tutor-table-responsive">
				<table class="tutor-table tutor-table-middle">
					<thead>
						<tr>
							<th width="10%">
								<?php esc_html_e( 'Order ID', 'tutor-pro' ); ?>
							</th>
							<th width="35%">
								<?php esc_html_e( 'Course', 'tutor-pro' ); ?>
							</th>
							<th width="20%">
								<?php esc_html_e( 'Instructor', 'tutor-pro' ); ?>
							</th>
							<th width="15%">
								<?php esc_html_e( 'Date', 'tutor-pro' ); ?>
							</th>
							<th width="10%">
								<?php esc_html_e( 'Status', 'tutor-pro' ); ?>
							</th>
							<th width="10%">
								<?php esc_html_e( 'Price', 'tutor-pro' ); ?>
							</th>
						</tr>
					</thead>

					<tbody>
						<?php foreach ( $lists as $report ) : ?>
							<?php
								$is_tutor_monetization = tutor_utils()->is_monetize_by_tutor();

								$tutor_order = $is_tutor_monetization ? ( new OrderModel() )->get_order_by_id( $report->order_id ) : ( function_exists( 'wc_get_order' ) ? wc_get_order( $report->order_id ) : null );

								$instructor = get_post_field( 'post_author', $report->post_parent );
								$user_info  = get_userdata( $instructor );
								// $tutor_order->get_item_count()
								$price = is_object( $tutor_order ) && method_exists( $tutor_order, 'get_total' ) ? $tutor_order->get_total() : ( is_object( $tutor_order ) && property_exists( $tutor_order, 'total_price' ) ? $tutor_order->total_price : 0 );
								$alert = ( 'processing' == $report->post_status ? 'warning'
											: ( 'on-hold' == $report->post_status ? 'warning'
											: ( 'completed' == $report->post_status ? 'success'
											: ( 'cancelled' == $report->post_status || 'canceled' == $report->post_status ? 'danger' : 'default' ) ) )
										);
							?>
							<tr>
								<td>
									<?php echo esc_html( '#' . $report->order_id ); ?>
								</td>
								<td>
									<div class="tutor-d-flex tutor-align-center tutor-gap-1">
										<?php echo esc_html( get_the_title( $report->post_parent ) ); ?>
										<a href="<?php echo esc_url( get_permalink( $report->post_parent ) ); ?>" class="tutor-iconic-btn" target="_blank">
											<span class="tutor-icon-external-link" area-hidden="true"></span>
										</a>
									</div>
								</td>
								<td>
									<div class="tutor-d-flex tutor-align-center tutor-gap-2">
										<?php echo wp_kses( tutor_utils()->get_tutor_avatar( $user_info->ID ), tutor_utils()->allowed_avatar_tags() ); ?>
										<?php echo esc_html( $user_info->display_name ); ?>
									</div>
								</td>
								<td>
									<div class="tutor-fs-7">
										<?php echo esc_html( tutor_i18n_get_formated_date( $report->post_date, get_option( 'date_format' ) ) ); ?>,
										<div class="tutor-fw-normal tutor-color-muted"><?php echo esc_html( tutor_i18n_get_formated_date( $report->post_date, get_option( 'time_format' ) ) ); ?></div>
									</div>
								</td>
								<td>
									<span class="tutor-badge-label label-<?php echo esc_attr( $alert ); ?> tutor-m-4">
										<?php echo esc_html( tutor_utils()->translate_dynamic_text( $report->post_status ) ); ?>
									</span>
								</td>
								<td>
									<?php
									if ( tutor_utils()->is_monetize_by_tutor() ) {
										$order_details = ( new OrderModel() )->get_order_by_id( $report->order_id );
										$item_id       = OrderModel::TYPE_SUBSCRIPTION === $order_details->order_type
															// Plan ID.
															? $order_details->items[0]->id ?? 0
															// Course ID.
															: $report->post_parent;

										$price = OrderModel::get_item_sold_price( $item_id, false );
									}

									echo wp_kses_post( tutor_utils()->tutor_price( $price ) );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
		<?php endif; ?>
	</div>

	<div class="tutor-report-sales-data-table-pagination tutor-report-content-common-pagination tutor-mt-32">
		<?php
		if ( $total_items > $item_per_page ) {
			$pagination_data = array(
				'base'        => str_replace( $current_page, '%#%', 'admin.php?page=tutor_report&sub_page=sales&paged=%#%' ),
				'total_items' => $total_items,
				'per_page'    => $item_per_page,
				'paged'       => $current_page,
			);
			tutor_load_template_from_custom_path( tutor()->path . 'views/elements/pagination.php', $pagination_data );
		}
		?>
	</div>
</div>
