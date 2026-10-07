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

use Tutor\Helpers\UrlHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;

/**
 * Invoice class
 *
 * @since 3.0.0
 */
class Invoice {
	/**
	 * Query param to load invoice.
	 *
	 * @since 4.0.0
	 */
	const QUERY_PARAM = 'tutor-invoice';

	/**
	 * Register hooks
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_filter( 'template_include', array( $this, 'load_invoice_template' ), PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_invoice_scripts' ) );

		add_filter( 'tutor_should_load_legacy_scripts', array( $this, 'filter_should_load_legacy_scripts' ) );

		add_action( 'tutor_dashboard_invoice_button', array( $this, 'render_dashboard_order_invoice_button' ) );
		add_action( 'tutor_after_order_edit_link', array( $this, 'render_admin_order_invoice_button' ) );
		add_action( 'tutor_after_subscription_action_view', array( $this, 'render_admin_order_invoice_button' ) );
	}

	/**
	 * Get invoice URL.
	 *
	 * @since 4.0.0
	 *
	 * @param int|object $order order id or object.
	 *
	 * @return string
	 */
	public static function get_invoice_url( $order ) {
		if ( is_numeric( $order ) ) {
			$order = ( new OrderModel() )->get_order_by_id( $order );
		}

		$invoice_url = UrlHelper::add_query_params(
			home_url(),
			array( self::QUERY_PARAM => $order->id )
		);

		return $invoice_url;
	}

	/**
	 * Check the page is invoice or not.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function is_invoice_page() {
		return Input::has( self::QUERY_PARAM ) && Input::get( self::QUERY_PARAM ) > 0;
	}

	/**
	 * Render invoice button.
	 *
	 * @since 4.0.0
	 *
	 * @param object $order order.
	 * @param string $context context.
	 *
	 * @return void
	 */
	public static function render_invoice_button( $order, $context = 'dashboard' ) {
		if ( self::should_show_invoice( $order ) ) {
			$invoice_url = self::get_invoice_url( $order );
			$classes     = 'tutor-btn tutor-btn-link';
			if ( 'admin' === $context ) {
				$classes = 'tutor-btn tutor-btn-outline-primary tutor-btn-sm';
			}

			printf(
				'<a href="%s" class="%s" target="_blank">%s</a>',
				esc_url( $invoice_url ),
				esc_attr( $classes ),
				esc_html__( 'Invoice', 'tutor-pro' )
			);

		}
	}

	/**
	 * Enqueue script related to invoice.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_invoice_scripts() {
		if ( self::is_invoice_page() ) {
			wp_enqueue_script( 'html2canvas', tutor_pro()->url . 'assets/lib/html2canvas/html2canvas.min.js', array( 'jquery' ), TUTOR_PRO_VERSION, true );
			wp_enqueue_script( 'jsPDf', tutor_pro()->url . 'assets/lib/jspdf/jspdf.umd.min.js', array( 'jquery' ), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Filter tutor should load legacy scripts.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $load load.
	 *
	 * @return bool
	 */
	public function filter_should_load_legacy_scripts( bool $load ): bool {
		if ( self::is_invoice_page() ) {
			return false;
		}

		return $load;
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
		if ( self::is_invoice_page() ) {
			$template = tutor_pro()->path . 'templates/invoice.php';
			if ( file_exists( $template ) ) {
				return $template;
			}
		}

		return $template;
	}

	/**
	 * Render admin invoice button.
	 *
	 * @since 3.5.0
	 *
	 * @param object $order order.
	 *
	 * @return void
	 */
	public function render_dashboard_order_invoice_button( $order ) {
		self::render_invoice_button( $order, 'dashboard' );
	}

	/**
	 * Render admin invoice button.
	 *
	 * @since 3.5.0
	 *
	 * @param object $order order.
	 *
	 * @return void
	 */
	public function render_admin_order_invoice_button( $order ) {
		self::render_invoice_button( $order, 'admin' );
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
		return is_object( $order ) && in_array( $order->order_status, $status, true );
	}
}
