<?php
/**
 * Handle WC Subscription
 *
 * @package TutorPro\Addons
 * @subpackage WCSubscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.3.5
 */

namespace TUTOR_WCS;

defined( 'ABSPATH' ) || exit;

use Tutor\Helpers\DateTimeHelper;
use Tutor\Models\EnrollmentModel;
use \WC_Product;

/**
 * Manage WC subscriptions
 */
class WCSubscriptions {

	/**
	 * Register hooks
	 */
	public function __construct() {
		add_filter( 'tutor_is_enrolled', array( $this, 'tutor_is_enrolled' ), 10, 3 );

		/**
		 * Cancel enrollment if Woocommerce subscription is not active
		 *
		 * @since 2.0.3
		 */
		add_action( 'woocommerce_subscription_status_updated', array( $this, 'update_enrollment_status' ), 10, 3 );

		/**
		 * Filter WC product price for Tutor course
		 */
		add_filter( 'tutor_course_details_wc_add_to_cart_price', array( $this, 'filter_price' ), 10, 2 );

		add_filter( 'tutor_should_fire_after_enrolled_for_wc_order', array( $this, 'skip_hook_fire_for_renewal_order' ), 10, 3 );
		add_filter( 'tutor_can_gift_course', array( $this, 'filter_can_gift_course' ), 10, 2 );
		add_filter( 'tutor_wc_should_process_checkout_order_item', array( $this, 'filter_should_process_checkout_order_item' ), 10, 3 );

		add_action( 'tutor_course/single/entry/after', array( $this, 'subscription_expire_info' ), 9 );
	}

	/**
	 * Filter tutor_is_enrolled
	 *
	 * @param mixed $get_enrolled_info  enrollment info.
	 * @param int   $course_id  tutor course id.
	 * @param int   $user_id  user id.
	 *
	 * @return mixed
	 */
	public function tutor_is_enrolled( $get_enrolled_info, $course_id, $user_id ) {
		$product_id = tutor_utils()->get_course_product_id( $course_id );
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			$type    = is_object( $product ) && isset( $product->get_type ) ? $product->get_type() : null;

			if ( 'subscription' === $type || 'variable-subscription' === $type ) {
				$subscriptions           = $this->get_user_subscriptions( $user_id );
				$has_active_subscription = false;
				foreach ( $subscriptions as $subscription_id => $subscription ) {
					if ( $subscription->has_product( $product_id ) ) {
						$has_active_subscription = true;
					}
				}
				if ( $has_active_subscription ) {
					return $get_enrolled_info;
				} else {
					return false;
				}
			}
		}

		return $get_enrolled_info;
	}

	/**
	 * Get list of user subscriptions
	 *
	 * @since 3.8.0 param $status is added.
	 *
	 * @param integer $user_id  user id.
	 * @param string  $status subscription status | default 'active'.
	 *
	 * @return array
	 */
	public static function get_user_subscriptions( $user_id = 0, $status = 'active' ) {
		$user_id = tutor_utils()->get_user_id( $user_id );

		$subscriptions = wcs_get_subscriptions(
			array(
				'subscriptions_per_page' => -1,
				'subscription_status'    => $status,
				'customer_id'            => $user_id,
			)
		);

		return $subscriptions;
	}

	/**
	 * Update enrollment status based on Woocommerce subscription status
	 *
	 * @param \WC_Subscription $wc_subscription  wc subscription.
	 * @param string           $new_status  new status.
	 * @param string           $old_status  opd status.
	 *
	 * @return void
	 */
	public function update_enrollment_status( \WC_Subscription $wc_subscription, string $new_status, string $old_status ): void {

		$order_id = method_exists( $wc_subscription, 'get_parent_id' ) ? $wc_subscription->get_parent_id() : $wc_subscription->order->id;
		if ( $order_id && tutor_utils()->is_tutor_order( $order_id ) ) {
			$enrollment_status = 'active' === $new_status ? 'completed' : 'cancel';
			$enrollments       = tutor_utils()->get_course_enrolled_ids_by_order_id( $order_id );
			$enrolled_ids      = tutor_utils()->count( $enrollments ) ? array_column( $enrollments, 'enrolled_id' ) : array();

			if ( tutor_utils()->count( $enrolled_ids ) ) {
				EnrollmentModel::update_enrollments( $enrollment_status, $enrolled_ids );
			}
		}
	}

	/**
	 * Filter course price to show subscription details along with price
	 *
	 * @param string     $price  price html.
	 * @param WC_Product $product  WC Product.
	 *
	 * @return string
	 */
	public static function filter_price( string $price, WC_Product $product ) {
		$product_type = $product->get_type();
		if ( 'subscription' === $product_type || 'variable-subscription' === $product_type ) {
			ob_start();
			?>
			<div>
				<span class="tutor-course-price tutor-fs-6 tutor-fw-bold tutor-color-black">
					<?php echo $product->get_price_html(); //phpcs:ignore ?>
				</span>
			</div>
			<?php
			$price = ob_get_clean();
		}
		return $price;
	}

	/**
	 * Filter tutor_should_fire_after_enrolled_for_wc_order for WC subscription renewal order
	 *
	 * @since 3.8.2
	 *
	 * @param bool $fire_hook should fire hook.
	 * @param int  $order_id order id.
	 * @param int  $enrolled_id enrolled id.
	 *
	 * @return bool
	 */
	public function skip_hook_fire_for_renewal_order( $fire_hook, $order_id, $enrolled_id ) {
		$is_renewal_order = function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order_id );
		if ( $is_renewal_order ) {
			$fire_hook = false;
		}

		return $fire_hook;
	}

	/**
	 * Filter tutor_can_gift_course for WC subscription product.
	 *
	 * @since 3.8.2
	 *
	 * @param bool $can_gift can gift.
	 * @param int  $course_id course id.
	 *
	 * @return bool
	 */
	public function filter_can_gift_course( $can_gift, $course_id ) {
		$product_id = tutor_utils()->get_course_product_id( $course_id );
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_type( array( 'subscription', 'variable-subscription' ) ) ) {
				// WC subscription product can't be gifted.
				return false;
			}
		}

		return $can_gift;
	}

	/**
	 * Filter should process checkout order item for WC subscription product.
	 *
	 * @since 4.0.0
	 *
	 * @param bool           $should_process should process.
	 * @param \WC_Order_Item $item item.
	 * @param \WC_Order      $order order.
	 *
	 * @return bool
	 */
	public function filter_should_process_checkout_order_item( $should_process, $item, $order ) {
		if ( $order && 'shop_subscription' === $order->get_type() ) {
			$should_process = false;
		}

		return $should_process;
	}

	/**
	 * Show subscription expire info for WC subscription.
	 *
	 * @since 4.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function subscription_expire_info( $course_id ) {
		$enrollment = EnrollmentModel::is_enrolled( $course_id, get_current_user_id() );
		if ( ! $enrollment || ! $enrollment->product_id || ! $enrollment->order_id ) {
			return;
		}

		$product = wc_get_product( $enrollment->product_id );
		if ( ! $product || ! method_exists( $product, 'is_type' ) || ! $product->is_type( array( 'subscription', 'variable-subscription' ) ) ) {
			return;
		}

		$subscriptions = wcs_get_subscriptions_for_order( $enrollment->order_id );
		if ( ! tutor_utils()->count( $subscriptions ) ) {
			return;
		}

		$end_date_gmt = null;
		$validity     = null;
		$subscription = $subscriptions[0] ?? null;
		if ( ! is_object( $subscription ) ) {
			return;
		}

		$end_date_gmt = $subscription->get_date( 'next_payment', 'gmt' );
		if ( empty( $end_date_gmt ) ) {
			$start_date_gmt = $subscription->get_date( 'start', 'gmt' );
			$interval       = $subscription->get_billing_interval();
			$period         = $subscription->get_billing_period();
			$end_date_gmt   = DateTimeHelper::create( $start_date_gmt )->add( $interval, $period )->to_date_time_string();
		}

		if ( $end_date_gmt ) {
			remove_all_filters( 'tutor_course/single/entry/after' );
			$expire_text = DateTimeHelper::get_gmt_to_user_timezone_date( $end_date_gmt );
			?>
			<div class="enrolment-expire-info tutor-fs-7 tutor-color-muted tutor-d-flex tutor-align-center tutor-mt-12">
				<i class="tutor-icon-calender-line tutor-mr-8"></i>
				<?php
				// translators: %s: expire date.
				echo esc_html( sprintf( __( 'Subscription validity: %s', 'tutor-pro' ), $expire_text ) );
				?>
			</div>
			<?php
		}
	}
}
