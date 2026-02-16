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

use TUTOR\Backend_Page_Trait;
use Tutor\Ecommerce\OrderController;
use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;
use Tutor\Traits\JsonResponse;
use TUTOR\User;
use TutorPro\Subscription\Menu;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;

/**
 * SubscriptionListController Class.
 *
 * @since 3.0.0
 */
class SubscriptionListController {
	use JsonResponse;

	/**
	 * Trait for utilities
	 *
	 * @var $page_title
	 */
	use Backend_Page_Trait;

	/**
	 * Subscription model.
	 *
	 * @var SubscriptionModel
	 */
	public $subscription_model;

	/**
	 * Order model.
	 *
	 * @var OrderModel
	 */
	public $order_model;

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
	public $plan_model;


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

		add_action( 'wp_ajax_tutor_subscription_bulk_action', array( $this, 'ajax_tutor_subscription_bulk_action' ) );
		add_action( 'tutor_subscription_deleted', array( $this, 'on_subscription_delete' ) );
		add_action( 'tutor_subscription_status_changed', array( $this, 'on_subscription_status_changed' ), 10, 3 );
	}

	/**
	 * Get subscriptions page URL.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public static function get_subscription_page_url() {
		if ( is_admin() ) {
			return admin_url( 'admin.php?page=' . Menu::PAGE_SLUG );
		} else {
			return tutor_utils()->get_tutor_dashboard_url() . '/subscriptions';
		}
	}

	/**
	 * Available tabs that will visible on the right side of page navbar
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function tabs_key_value(): array {
		$url = get_pagenum_link();

		$date   = Input::get( 'date', '' );
		$search = Input::get( 'search', '' );
		$status = Input::get( 'status', '' );

		$where = array();

		if ( ! empty( $date ) ) {
			$where['date(s.next_payment_date_gmt)'] = tutor_get_formated_date( '', $date );
		}

		if ( ! empty( $status ) ) {
			$where['status'] = $status;
		}

		if ( ! User::is_admin() ) {
			$where['user_id'] = get_current_user_id();
		}

		$tabs = array();

		$tabs [] = array(
			'key'   => 'all',
			'title' => __( 'All', 'tutor-pro' ),
			'value' => $this->subscription_model->get_subscription_count( $where, $search ),
			'url'   => $url . '&data=all',
		);

		$subscription_status = array(
			$this->subscription_model::STATUS_PENDING   => __( 'Pending', 'tutor-pro' ),
			$this->subscription_model::STATUS_ACTIVE    => __( 'Active', 'tutor-pro' ),
			$this->subscription_model::STATUS_HOLD      => __( 'On Hold', 'tutor-pro' ),
			$this->subscription_model::STATUS_EXPIRED   => __( 'Expired', 'tutor-pro' ),
			$this->subscription_model::STATUS_CANCELLED => __( 'Cancelled', 'tutor-pro' ),
		);

		foreach ( $subscription_status as $key => $value ) {
			$where['status'] = $key;

			$tabs[] = array(
				'key'   => $key,
				'title' => $value,
				'value' => $this->subscription_model->get_subscription_count( $where, $search ),
				'url'   => $url . '&data=' . $key,
			);
		}

		return apply_filters( 'tutor_subscription_tabs', $tabs );
	}

	/**
	 * Get subscriptions
	 *
	 * @since 3.0.0
	 *
	 * @param integer $per_page per page.
	 * @param integer $current_page current page.
	 *
	 * @return array
	 */
	public function get_list( $per_page = 10, $current_page = 1 ) {
		$active_tab  = Input::get( 'data', 'all' );
		$date        = Input::get( 'date', '' );
		$search_term = Input::get( 'search', '' );

		$where_clause = array();

		if ( $date ) {
			$where_clause['date(s.next_payment_date_gmt)'] = tutor_get_formated_date( '', $date );
		}

		if ( 'all' !== $active_tab ) {
			$where_clause['s.status'] = $active_tab;
		}

		if ( ! User::is_admin() ) {
			$where_clause['s.user_id'] = get_current_user_id();
		}

		return $this->subscription_model->get_subscriptions( $where_clause, $search_term, $per_page, $current_page );
	}

	/**
	 * Prepare bulk actions that will show on dropdown options
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function prepare_bulk_actions(): array {
		$actions = array(
			$this->bulk_action_default(),
		);

		$active_tab = Input::get( 'data', 'all' );

		$actions[] = array(
			'value'  => $this->subscription_model::STATUS_PENDING,
			'option' => __( 'Pending', 'tutor-pro' ),
		);

		$actions[] = array(
			'value'  => $this->subscription_model::STATUS_CANCELLED,
			'option' => __( 'Cancel', 'tutor-pro' ),
		);

		$actions[] = array(
			'value'  => $this->subscription_model::STATUS_ACTIVE,
			'option' => __( 'Active', 'tutor-pro' ),
		);

		$actions[] = array(
			'value'  => $this->subscription_model::STATUS_HOLD,
			'option' => __( 'Hold', 'tutor-pro' ),
		);

		if ( $this->subscription_model::STATUS_CANCELLED === $active_tab ) {
			$actions = array(
				$this->bulk_action_default(),
			);

			$actions[] = array(
				'value'  => $this->subscription_model::STATUS_ACTIVE,
				'option' => __( 'Active', 'tutor-pro' ),
			);
			$actions[] = $this->bulk_action_delete();
		}

		$actions = array_filter( $actions, fn( $action ) => $action['value'] !== $active_tab );

		return apply_filters( 'tutor_subscription_bulk_action_list', $actions );
	}

	/**
	 * Bulk delete subscription.
	 *
	 * @since 3.0.0
	 *
	 * @param array $records subscriptions.
	 *
	 * @return void
	 */
	public function bulk_delete_subscriptions( $records ) {
		foreach ( $records as $row ) {
			if ( SubscriptionModel::STATUS_CANCELLED === $row->status ) {
				$deleted = $this->subscription_model->delete_subscription( $row->id );
				if ( $deleted ) {
					do_action( 'tutor_subscription_deleted', $row );
				}
			}
		}
	}

	/**
	 * Bulk status update.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $records records.
	 * @param string $status status.
	 *
	 * @return void
	 */
	public function bulk_update_status( $records, $status ) {
		foreach ( $records as $row ) {
			$updated = $this->subscription_model->update( $row->id, array( 'status' => $status ) );
			if ( $updated ) {
				do_action( 'tutor_subscription_status_changed', $row->id, $row->status, $status );
			}
		}
	}

	/**
	 * Bulk action for subscription list.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function ajax_tutor_subscription_bulk_action() {
		tutor_utils()->check_nonce();

		if ( ! User::is_admin() ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_FORBIDDEN
			);
		}

		$request          = Input::sanitize_array( $_POST );//phpcs:ignore
		$bulk_action      = $request['bulk-action'] ?? '';
		$bulk_ids         = isset( $request['bulk-ids'] ) ? explode( ',', $request['bulk-ids'] ) : array();
		$subscription_ids = array_filter( $bulk_ids, 'is_numeric' );

		$allowed_status = array(
			SubscriptionModel::STATUS_PENDING,
			SubscriptionModel::STATUS_ACTIVE,
			SubscriptionModel::STATUS_HOLD,
			SubscriptionModel::STATUS_CANCELLED,
			'delete',
		);

		if ( ! in_array( $bulk_action, $allowed_status, true ) ) {
			$this->json_response(
				__( 'Invalid status type selected', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$selected_records = $this->subscription_model->get_all( array( 'id' => $subscription_ids ) );
		if ( ! is_array( $selected_records ) || empty( $selected_records ) ) {
			$this->json_response(
				tutor_utils()->error_message( 'invalid_req' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		if ( 'delete' === $bulk_action ) {
			$this->bulk_delete_subscriptions( $selected_records );
		} else {
			$this->bulk_update_status( $selected_records, $bulk_action );
		}

		$this->json_response( __( 'Bulk action completed', 'tutor-pro' ) );
	}

	/**
	 * On subscription delete, clear subscription related data.
	 *
	 * @since 3.0.0
	 *
	 * @param object $subscription subscription object.
	 *
	 * @return void
	 */
	public function on_subscription_delete( $subscription ) {
		$orders = $this->subscription_model->get_subscription_orders( $subscription, PHP_INT_MAX );
		if ( 0 === $orders['total_count'] ) {
			return;
		}

		$order_ids = array_column( $orders['results'], 'id' );
		if ( count( $order_ids ) ) {
			/**
			 * Delete order will clear earnings, enrollment
			 */
			$this->order_model->delete_order( $order_ids );
		}
	}

	/**
	 * On subscription status changed.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $id subscription id.
	 * @param string $from_status from status.
	 * @param string $to_status to status.
	 *
	 * @return void
	 */
	public function on_subscription_status_changed( $id, $from_status, $to_status ) {
		$subscription    = $this->subscription_model->get_subscription( $id );
		$parent_order    = $this->subscription_model->get_parent_order( $id );
		$enrollment_ids  = $this->order_model->get_enrollment_ids( $parent_order->id );
		$has_enrollments = count( $enrollment_ids ) > 0;

		if ( SubscriptionModel::STATUS_ACTIVE === $to_status && $has_enrollments ) {
			tutor_utils()->update_enrollments( 'completed', $enrollment_ids );
		} elseif ( SubscriptionModel::STATUS_ACTIVE !== $to_status && $has_enrollments ) {
			tutor_utils()->update_enrollments( 'cancel', $enrollment_ids );
		}

		if ( $from_status !== $to_status ) {
			switch ( $to_status ) {
				case SubscriptionModel::STATUS_ACTIVE:
					do_action( 'tutor_subscription_activated', $subscription );
					break;
				case SubscriptionModel::STATUS_HOLD:
					do_action( 'tutor_subscription_hold', $subscription );
					break;
				case SubscriptionModel::STATUS_CANCELLED:
					do_action( 'tutor_subscription_cancelled', $subscription );
					break;
			}
		}
	}
}
