<?php
/**
 * Invoice class
 *
 * @package TutorPro\Invoice
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Ecommerce;

use TUTOR\Input;
use Tutor\Models\OrderModel;
/**
 * Invoice class
 */
class Invoice {

	/**
	 * Register hooks
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'tutor_dashboard_invoice_button', array( $this, 'load_invoice_button' ) );
		add_filter( 'load_dashboard_template_part_from_other_location', array( $this, 'load_invoice_template' ) );
	}

	/**
	 * Load invoice button.
	 *
	 * @since 3.0.0
	 *
	 * @param object $order order.
	 *
	 * @return void
	 */
	public function load_invoice_button( $order ) {
		if ( self::should_show_invoice( $order ) ) {
			$invoice_url = add_query_arg( 'invoice', $order->id, tutor_utils()->get_tutor_dashboard_page_permalink( 'purchase_history' ) );

			echo '<a href="' . esc_url( $invoice_url ) . '" class="tutor-btn tutor-btn-outline-primary tutor-btn-sm" target="_blank">' .
				esc_html__( 'Invoice', 'tutor-pro' ) .
				'</a>';
		}
	}

	/**
	 * Load invoice templates.
	 *
	 * @since 3.0.0
	 *
	 * @param string $template template path.
	 *
	 * @return string
	 */
	public function load_invoice_template( $template ) {
		$invoice_id = Input::get( 'invoice', 0, Input::TYPE_INT );
		if ( get_query_var( 'tutor_dashboard_page' ) === 'purchase_history' && $invoice_id ) {
			$template = tutor_pro()->path . 'templates/invoice.php';
			if ( file_exists( $template ) ) {
				return $template;
			}
		}
		return $template;
	}

	/**
	 * Determine whether to show invoice button.
	 *
	 * @since 3.0.0
	 *
	 * @param object $order Order object.
	 *
	 * @return boolean
	 */
	public static function should_show_invoice( $order ) {
		$status = array( OrderModel::ORDER_COMPLETED );
		return is_object( $order ) && in_array( $order->order_status, $status );
	}
}
