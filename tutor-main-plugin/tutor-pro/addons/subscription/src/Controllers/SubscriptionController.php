<?php
/**
 * Handler of user subscription
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Controllers;

use Tutor\Ecommerce\Ecommerce;
use Tutor\Ecommerce\OrderController;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\HttpHelper;
use Tutor\Helpers\ValidationHelper;
use TUTOR\Input;
use Tutor\Models\OrderMetaModel;
use Tutor\Models\OrderModel;
use Tutor\Traits\JsonResponse;
use TUTOR\User;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;
use TutorPro\Subscription\Utils;

/**
 * SubscriptionController Class.
 *
 * @since 3.0.0
 */
class SubscriptionController {
	use JsonResponse;

	/**
	 * Subscription model.
	 *
	 * @var SubscriptionModel
	 */
	private $subscription_model;

	/**
	 * Order model.
	 *
	 * @var OrderModel
	 */
	private $order_model;

	/**
	 * Order controller instance.
	 *
	 * @var OrderController
	 */
	private $order_ctrl;

	/**
	 * Plan model
	 *
	 * @var PlanModel
	 */
	private $plan_model;


	/**
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 *
	 * @param bool $register_hooks whether to register hooks or not.
	 */
	public function __construct( $register_hooks = true ) {
		$this->subscription_model = new SubscriptionModel();
		$this->order_model        = new OrderModel();
		$this->order_ctrl         = new OrderController();
		$this->plan_model         = new PlanModel();

		if ( ! $register_hooks ) {
			return;
		}

		add_action( 'tutor_order_placed', array( $this, 'handle_order_placed' ) );
		add_action( 'tutor_order_payment_status_changed', array( $this, 'handle_order_payment_status_changed' ), 10, 3 );

		add_filter( 'tutor_order_details', array( $this, 'extend_order_details' ) );
		add_filter( 'tutor_checkout_plan_info', array( $this, 'checkout_plan_info' ), 10, 2 );
		add_filter( 'tutor_checkout_user_has_subscription', array( $this, 'check_user_has_subscription_on_checkout_page' ), 9, 3 );
		add_filter( 'tutor_subscription_course_by_plan', array( $this, 'get_course_id_for_plan' ) );
		add_filter( 'tutor_subscription_plan_price', array( $this, 'subscription_plan_price' ), 10, 2 );

		add_action( 'tutor_order_enrolled', array( $this, 'handle_tutor_order_enrolled' ), 10, 2 );
		add_action( 'tutor_enrollment_row_course_info_meta', array( $this, 'add_subscription_badge_on_enrollment_list' ) );
		add_action( 'tutor_before_bulk_enrollment_delete', array( $this, 'handle_before_delete_bulk_enrollment' ), 10, 2 );
		add_action( 'tutor_enrollment/after/cancel', array( $this, 'handle_subscription_enrollment_cancel' ) );

		add_action( 'tutor_subscription_expired', array( $this, 'handle_subscription_expired' ) );

		/**
		 * Ajax API
		 */
		add_action( 'wp_ajax_tutor_subscription_update', array( $this, 'ajax_tutor_subscription_update' ) );
		add_action( 'wp_ajax_tutor_subscription_status_update', array( $this, 'ajax_tutor_subscription_status_update' ) );
		add_action( 'wp_ajax_tutor_subscription_early_renew', array( $this, 'ajax_tutor_subscription_early_renew' ) );
	}

	/**
	 * Extend order details data.
	 *
	 * @param object $order_data order details data.
	 *
	 * @return object
	 */
	public function extend_order_details( $order_data ) {
		if ( $this->order_model::TYPE_SINGLE_ORDER !== $order_data->order_type ) {
			foreach ( $order_data->items as $item ) {
				$plan_info = $this->plan_model->get_plan( $item->id );
				if ( $plan_info ) {
					$item->title = $plan_info->plan_name;
					$item->type  = 'plan';

					$course_id = $this->plan_model->get_course_id_by_plan( $item->id );
					if ( $course_id ) {
						$item->type      = 'course_plan';
						$item->title     = get_the_title( $course_id );
						$item->image     = get_the_post_thumbnail_url( $course_id );
						$item->plan_info = array(
							'id'        => $plan_info->id,
							'plan_name' => $plan_info->plan_name,
						);
					}
				}
			}

			if ( $this->order_model::TYPE_SUBSCRIPTION === $order_data->order_type ) {
				$enrollment_fee = OrderMetaModel::get_meta_value( $order_data->id, $this->plan_model::META_ENROLLMENT_FEE, true );

				$order_data->subscription_fees = array();
				if ( $enrollment_fee ) {
					$order_data->subscription_fees[] = array(
						'title' => __( 'Enrollment Fee', 'tutor-pro' ),
						'value' => $enrollment_fee,
					);
				}
			}
		}

		return $order_data;
	}

	/**
	 * Add plan info to checkout.
	 *
	 * @since 3.0.0
	 *
	 * @param object $plan_info plan info.
	 * @param int    $plan_id plan id.
	 *
	 * @return object
	 */
	public function checkout_plan_info( $plan_info, $plan_id ) {
		if ( $plan_id ) {
			$plan = $this->plan_model->get_plan( (int) $plan_id );
			if ( ! $plan ) {
				return $plan_info;
			}

			$plan->in_sale_price = $this->plan_model->in_sale_price( $plan );
			if ( $this->plan_model::TYPE_COURSE === $plan->plan_type ) {
				$plan->course_id = $this->plan_model->get_course_id_by_plan( $plan->id );
			}

			return $plan;
		}

		return $plan_info;
	}

	/**
	 * Check user has already plan purchased during checkout page.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $has_subscription has subscription or not.
	 * @param int  $plan_id plan id.
	 * @param bool $echo echo something before return value.
	 *
	 * @return bool
	 */
	public function check_user_has_subscription_on_checkout_page( $has_subscription, $plan_id, $echo ) {
		$subscription = $this->subscription_model->get_row(
			array(
				'plan_id' => $plan_id,
				'user_id' => get_current_user_id(),
			)
		);

		if ( ! $subscription ) {
			return $has_subscription;
		}

		$plan = $this->plan_model->get_plan( $plan_id );

		if ( ! $plan ) {
			return $has_subscription;
		}

		if ( $echo ) {
			tutor_load_template_from_custom_path(
				Utils::template_path( 'subscription-exist-alert.php' ),
				array(
					'plan_name'           => $plan->plan_name,
					'subscription_status' => $subscription->status,
					'subscription_url'    => $this->subscription_model->get_subscription_details_url( $subscription->id ),
				)
			);
		}

		return true;
	}

	/**
	 * Subscription plan price
	 *
	 * @since 3.0.0
	 *
	 * @param object $course_price contains regular_price and sale_price.
	 * @param int    $plan_id plan id.
	 *
	 * @return object
	 */
	public function subscription_plan_price( $course_price, $plan_id ) {
		if ( ! $plan_id ) {
			return $course_price;
		}

		$plan = $this->plan_model->get_plan( (int) $plan_id );
		if ( ! $plan ) {
			return $course_price;
		}

		$course_price->regular_price = floatval( $plan->regular_price );
		$course_price->sale_price    = 0;
		if ( $this->plan_model->in_sale_price( $plan ) ) {
			$course_price->sale_price = floatval( $plan->sale_price );
		}

		return $course_price;
	}

	/**
	 * Get course id for plan.
	 *
	 * @since 3.0.0
	 *
	 * @param int $item_id course id or plan id.
	 *
	 * @return int
	 */
	public function get_course_id_for_plan( $item_id ) {
		$course_id = $this->plan_model->get_course_id_by_plan( $item_id );
		if ( $course_id ) {
			return $course_id;
		}

		return $item_id;
	}

	/**
	 * Subscription info update.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function ajax_tutor_subscription_update() {
		tutor_utils()->check_nonce();

		if ( ! User::is_admin() ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$subscription_id       = Input::post( 'subscription_id', 0, Input::TYPE_INT );
		$trial_end_date_gmt    = Input::post( 'trial_end_date_gmt', '' );
		$next_payment_date_gmt = Input::post( 'next_payment_date_gmt', '' );

		$inputs = Input::sanitize_array( $_POST ); //phpcs:ignore --sanitized.

		$rules = array(
			'subscription_id'       => "required|numeric|has_record:{$this->subscription_model->get_table_name()},id",
			'trial_end_date_gmt'    => 'if_input|date_format:Y-m-d H:i:s',
			'next_payment_date_gmt' => 'if_input|date_format:Y-m-d H:i:s',
		);

		$validation = ValidationHelper::validate( $rules, $inputs );
		if ( ! $validation->success ) {
			$this->json_response(
				tutor_utils()->error_message( 'validation_error' ),
				$validation->errors,
				HttpHelper::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$subscription = $this->subscription_model->get_subscription( $subscription_id );

		// Trial end date can be change only for first subscription order.
		if ( ! empty( $trial_end_date_gmt ) ) {
			if ( $subscription->active_order_id !== $subscription->first_order_id ) {
				$this->json_response(
					__( 'Trial end date can only be updated for the first subscription order', 'tutor-pro' ),
					null,
					HttpHelper::STATUS_BAD_REQUEST
				);
			}
		}

		$update_data = array();
		if ( ! empty( $trial_end_date_gmt ) ) {
			$update_data['trial_end_date_gmt']    = $trial_end_date_gmt;
			$update_data['next_payment_date_gmt'] = $trial_end_date_gmt;
			$update_data['end_date_gmt']          = $trial_end_date_gmt;
		}

		if ( ! empty( $next_payment_date_gmt ) ) {
			$update_data['next_payment_date_gmt'] = $next_payment_date_gmt;
			$update_data['end_date_gmt']          = $next_payment_date_gmt;
		}

		if ( count( $update_data ) ) {
			$this->subscription_model->update( $subscription_id, $update_data );
			$this->json_response( __( 'Successfully updated', 'tutor-pro' ) );
		} else {
			$this->json_response( __( 'Nothing to update', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}
	}

	/**
	 * Update subscription status.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function ajax_tutor_subscription_status_update() {
		tutor_utils()->check_nonce();

		$subscription_id = Input::post( 'subscription_id', 0, Input::TYPE_INT );
		$to_status       = Input::post( 'status', '' );
		$is_admin        = User::is_admin();
		$user_id         = get_current_user_id();

		// Student can only cancel subscription.
		if ( ! $is_admin ) {
			$can_cancel_anytime = (bool) tutor_utils()->get_option( 'subscription_cancel_anytime', true );
			if ( ! $can_cancel_anytime ) {
				$this->json_response( __( 'You are not allowed to cancel subscription', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
			}

			$to_status = SubscriptionModel::STATUS_CANCELLED;
		}

		$allowed_status = $this->subscription_model->get_status_list();
		if ( ! in_array( $to_status, array_keys( $allowed_status ), true ) ) {
			$this->json_response( __( 'Invalid status selected', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$where = array( 'id' => $subscription_id );
		if ( ! $is_admin ) {
			$where['user_id'] = $user_id;
		}

		$subscription = $this->subscription_model->get_row( $where );
		if ( ! $subscription ) {
			$this->json_response( __( 'Invalid subscription', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$from_status = $subscription->status;

		$update = $this->subscription_model->update( $subscription_id, array( 'status' => $to_status ) );
		if ( $update ) {
			do_action( 'tutor_subscription_status_changed', $subscription_id, $from_status, $to_status );
			$this->json_response( __( 'Status updated', 'tutor-pro' ), null, HttpHelper::STATUS_OK );
		} else {
			$this->json_response( __( 'Failed to update status', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}
	}

	/**
	 * Handle subscription early renew.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function ajax_tutor_subscription_early_renew() {
		tutor_utils()->check_nonce();

		$subscription_id   = Input::post( 'subscription_id', 0, Input::TYPE_INT );
		$user_id           = get_current_user_id();
		$can_early_renewal = (bool) tutor_utils()->get_option( 'subscription_early_renewal', false );

		if ( ! $can_early_renewal ) {
			$this->json_response(
				__( 'Early renewal is not allowed', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$where = array(
			'id'      => $subscription_id,
			'user_id' => $user_id,
		);

		$subscription = $this->subscription_model->get_row( $where );
		if ( ! $subscription ) {
			$this->json_response( __( 'Invalid subscription', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$plan = $this->plan_model->get_plan( $subscription->plan_id );
		if ( PlanModel::PAYMENT_ONETIME === $plan->payment_type ) {
			$this->json_response(
				__( 'Early renewal is not allowed for onetime payment plan', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		if ( false === $this->subscription_model->should_renew_subscription( $subscription ) ) {
			$this->json_response(
			/* translators: %d: number of renewal allowed */
				sprintf( __( 'This subscription plan not allowed more than %d renewal.', 'tutor-pro' ), $plan->recurring_limit ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		try {
			$new_order_id = $this->create_renewal_order( $subscription );
			$parent_order = $this->subscription_model->get_parent_order( $subscription );
			$this->make_silent_payment( $new_order_id, $parent_order->payment_method );

			$this->json_response( __( 'Subscription Successfully Renewed', 'tutor-pro' ), null, HttpHelper::STATUS_OK );
		} catch ( \Throwable $th ) {
			tutor_log( $th );
			$this->json_response(
				tutor_utils()->error_message( 'server_error' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

	}

	/**
	 * Handle tutor order placed.
	 *
	 * @since 3.0.0
	 *
	 * @param array $order order array.
	 *
	 * @return void
	 */
	public function handle_order_placed( $order ) {
		$order = (object) $order;

		// Only create subscription record if it's subscription type order.
		if ( OrderModel::TYPE_SUBSCRIPTION !== $order->order_type ) {
			return;
		}

		$order_id    = $order->id;
		$order_items = $order->items;

		$plan_id = $order_items[0]['item_id'];
		$plan    = $this->plan_model->get_plan( $plan_id );
		if ( ! $plan ) {
			return;
		}

		/**
		 * Delete the subscription record
		 * If user has same plan subscription in pending status.
		 */
		$pending_subscription = $this->subscription_model->get_row(
			array(
				'user_id' => $order->user_id,
				'plan_id' => $plan_id,
				'status'  => SubscriptionModel::STATUS_PENDING,
			)
		);

		if ( $pending_subscription ) {
			$deleted = $this->subscription_model->delete_subscription( $pending_subscription->id );
			if ( $deleted ) {
				do_action( 'tutor_subscription_deleted', $pending_subscription );
			}
		}

		$parent_id    = $order->id;
		$gmt_datetime = DateTimeHelper::now()->to_date_time_string();

		$subscription_data = array(
			'user_id'         => $order->user_id,
			'plan_id'         => $plan->id,
			'status'          => SubscriptionModel::STATUS_PENDING,
			'first_order_id'  => $order_id,
			'active_order_id' => $order_id,
			'created_at_gmt'  => $gmt_datetime,
			'updated_at_gmt'  => $gmt_datetime,
		);

		/**
		 * Calculate time if plan is recurring type.
		 * For onetime payment plan, no need to calculate.
		 *
		 * @since 3.0.0
		 */
		if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) {
			$calculated_plan_times = $this->plan_model->calculate_plan_times( $plan_id, $order );
			$subscription_data     = array_merge( $subscription_data, $calculated_plan_times );
		}

		$this->subscription_model->create( $subscription_data );

		/**
		 * Update the order parent id.
		 * First first order, the order ID and parent ID same.
		 */
		$this->order_model->update_order(
			$order->id,
			array( 'parent_id' => $parent_id )
		);

		/**
		 * Add enrollment fee to meta.
		 */
		if ( $plan->enrollment_fee > 0 ) {
			OrderMetaModel::update_meta( $order_id, $this->plan_model::META_ENROLLMENT_FEE, $plan->enrollment_fee );
		}
	}

	/**
	 * Handle order payment status changed.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $order_id order id.
	 * @param string $from_status from status.
	 * @param string $to_status to status.
	 *
	 * @return void
	 */
	public function handle_order_payment_status_changed( $order_id, $from_status, $to_status ) {
		$order = $this->order_model->get_order_by_id( $order_id );
		if ( ! $this->subscription_model->is_subscription_order_type( $order->order_type ) ) {
			return;
		}

		$subscription = $this->subscription_model->get_subscription_by_order( $order );
		if ( ! $subscription ) {
			return;
		}

		$plan = $this->plan_model->get_plan( $subscription->plan_id );
		if ( ! $plan ) {
			return false;
		}

		// Status: unpaid -> paid Active the subscription when order status changed.
		if ( OrderModel::PAYMENT_UNPAID === $from_status
		&& OrderModel::PAYMENT_PAID === $to_status ) {
			$note = __( 'Subscription is activated', 'tutor-pro' );
			$this->subscription_model->update_subscription_status_by_order( $order, $this->subscription_model::STATUS_ACTIVE, $note );

			// Recalculate the subscription times if it's recurring plan.
			if ( PlanModel::PAYMENT_RECURRING === $plan->payment_type ) {
				$calculated_plan_times = $this->plan_model->calculate_plan_times( $plan->id, $order );
				$this->subscription_model->update( $subscription->id, $calculated_plan_times );
			}

			// Pull latest subscription data.
			$subscription = $this->subscription_model->get_subscription( $subscription->id );
			if ( OrderModel::TYPE_SUBSCRIPTION === $order->order_type ) {
				do_action( 'tutor_subscription_activated', $subscription );
			} elseif ( OrderModel::TYPE_RENEWAL === $order->order_type ) {
				do_action( 'tutor_subscription_renewed', $subscription );
			}
		}

		// Status: unpaid -> payment_failed Hold the subscription when order status changed.
		if ( OrderModel::PAYMENT_UNPAID === $from_status
		&& OrderModel::PAYMENT_FAILED === $to_status ) {
			$note = __( 'Subscription on hold due to payment fail', 'tutor-pro' );
			$this->subscription_model->update_subscription_status_by_order( $order, $this->subscription_model::STATUS_HOLD, $note );

			do_action( 'tutor_subscription_hold', $subscription );
		}

		// Status: paid -> refunded Hold the subscription when order status changed.
		if ( OrderModel::PAYMENT_PAID === $from_status
		&& OrderModel::PAYMENT_REFUNDED === $to_status ) {
			$note = __( 'Subscription on hold due to payment refund', 'tutor-pro' );
			$this->subscription_model->update_subscription_status_by_order( $order, $this->subscription_model::STATUS_HOLD, $note );

			do_action( 'tutor_subscription_hold', $subscription );
		}
	}

	/**
	 * Create a renewal order for subscription.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription.
	 *
	 * @return int order id.
	 *
	 * @throws \Exception If order fail to create.
	 */
	public function create_renewal_order( $subscription ) {
		$active_order_id = $subscription->active_order_id;
		$old_order       = $this->order_model->get_order_by_id( $active_order_id );
		$parent_order    = $this->subscription_model->get_parent_order( $subscription );

		$plan = $this->plan_model->get_plan( $subscription->plan_id );

		$items = array(
			'item_id'        => $plan->id,
			'regular_price'  => $plan->regular_price,
			'sale_price'     => $this->plan_model->in_sale_price( $plan ) ? $plan->sale_price : null,
			'discount_price' => null,
			'coupon_code'    => null,
		);

		if ( empty( $parent_order->payment_method )
		|| empty( $parent_order->transaction_id )
		|| empty( $parent_order->payment_payloads ) ) {
			throw new \Exception( 'Payment information not found' );
		}

		$renewal_order_id = $this->order_ctrl->create_order(
			$old_order->user_id,
			$items,
			OrderModel::PAYMENT_UNPAID,
			OrderModel::TYPE_RENEWAL,
			null,
			array(
				'parent_id'        => $subscription->first_order_id,
				'payment_method'   => $parent_order->payment_method,
				'payment_payloads' => $parent_order->payment_payloads,
			)
		);

		if ( $renewal_order_id ) {
			$this->subscription_model->update( $subscription->id, array( 'active_order_id' => $renewal_order_id ) );
		}

		return $renewal_order_id;
	}

	/**
	 * Make a silent payment.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $order_id order id.
	 * @param string $payment_method payment method.
	 *
	 * @return void
	 *
	 * @throws \Exception If order not found or payment gateway not found.
	 */
	private function make_silent_payment( $order_id, $payment_method ) {
		$order = $this->order_model->get_order_by_id( $order_id );
		if ( ! $order ) {
			throw new \Exception( 'Order not found' );
		}

		$gateway_ref = Ecommerce::payment_gateways_with_ref( $payment_method );
		if ( ! $gateway_ref ) {
			throw new \Exception( 'Payment gateway not found' );
		}

		$gateway_object = Ecommerce::get_payment_gateway_object( $gateway_ref['gateway_class'] );
		$gateway_object->make_recurring_payment( $order_id );
	}

	/**
	 * Add badge for subscription enrollment.
	 *
	 * @since 3.0.0
	 *
	 * @param object $enrollment enrollment object.
	 *
	 * @return void
	 */
	public function add_subscription_badge_on_enrollment_list( $enrollment ) {
		$subscription_id = (int) get_post_meta( $enrollment->enrol_id, $this->subscription_model::SUBSCRIPTION_ENROLLMENT_META, true );
		if ( $subscription_id ) {
			?>
		<div>
			<span class="tutor-fs-8 tutor-color-muted"><?php esc_html_e( 'Enrolled by subscription', 'tutor-pro' ); ?></span>
		</div>
			<?php
		}
	}

	/**
	 * Restrict subscription enrollment delete.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $enrollment_ids enrollment ids.
	 * @param string $enrollment_page_url enrollment page url.
	 *
	 * @return array
	 */
	public function handle_before_delete_bulk_enrollment( $enrollment_ids, $enrollment_page_url ) {
		if ( empty( $enrollment_ids ) ) {
			return;
		}

		foreach ( $enrollment_ids as $key => $enrollment_id ) {
			$subscription_id = (int) get_post_meta( $enrollment_id, $this->subscription_model::SUBSCRIPTION_ENROLLMENT_META, true );
			if ( $subscription_id ) {
				tutor_utils()->redirect_to(
					$enrollment_page_url,
					__( 'Deletion unsuccessful due to one or more subscription enrollments are selected', 'tutor-pro' ),
					'error'
				);
				exit;
			}
		}
	}

	/**
	 * Handle subscription enrollment cancel.
	 *
	 * @since 3.0.0
	 *
	 * @param int $enrollment_id enrollment id.
	 *
	 * @return void
	 */
	public function handle_subscription_enrollment_cancel( $enrollment_id ) {
		$subscription_id = (int) get_post_meta( $enrollment_id, $this->subscription_model::SUBSCRIPTION_ENROLLMENT_META, true );
		if ( $subscription_id ) {
			$subscription = $this->subscription_model->get_subscription( $subscription_id );
			if ( $subscription ) {
				$this->subscription_model->update( $subscription->id, array( 'status' => SubscriptionModel::STATUS_CANCELLED ) );
			}
		}
	}

	/**
	 * Handle tutor order enrolled.
	 *
	 * @since 3.0.0
	 *
	 * @param object $order tutor order.
	 * @param int    $enrollment_id enrollment id.
	 *
	 * @return void
	 */
	public function handle_tutor_order_enrolled( $order, $enrollment_id ) {
		if ( OrderModel::TYPE_SUBSCRIPTION === $order->order_type ) {
			$subscription = $this->subscription_model->get_subscription_by_order( $order );
			if ( $subscription ) {
				update_post_meta( $enrollment_id, $this->subscription_model::SUBSCRIPTION_ENROLLMENT_META, $subscription->id );
			}
		}
	}

	/**
	 * Handle subscription expired.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription object.
	 *
	 * @return void
	 */
	public function handle_subscription_expired( $subscription ) {
		if ( $this->subscription_model->should_renew_subscription( $subscription ) ) {
			try {
				$new_order_id = $this->create_renewal_order( $subscription );
				$parent_order = $this->subscription_model->get_parent_order( $subscription );
				$this->make_silent_payment( $new_order_id, $parent_order->payment_method );
			} catch ( \Throwable $th ) {
				tutor_log( $th->getMessage() );
				$this->subscription_model->update( $subscription->id, array( 'note' => 'Auto renew failed' ) );
				$this->cancel_course_enrollment( $subscription );
			}
		} else {
			$this->cancel_course_enrollment( $subscription );
		}
	}

	/**
	 * Cancel enrollment of subscription course.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription object.
	 *
	 * @return void
	 */
	public function cancel_course_enrollment( $subscription ) {
		if ( $subscription ) {
			return;
		}

		$course_id = $this->plan_model->get_course_id_by_plan( $subscription->plan_id );
		if ( $course_id ) {
			$enrollment_info = tutor_utils()->is_enrolled( $course_id, $subscription->user_id );
			if ( $enrollment_info ) {
				tutor_utils()->update_enrollments( 'cancel', array( $enrollment_info->ID ) );
			}
		}
	}

}
