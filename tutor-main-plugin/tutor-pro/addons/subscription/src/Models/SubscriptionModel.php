<?php
/**
 * Subscription Model
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Models;

use TUTOR\Course;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\QueryHelper;
use Tutor\Models\OrderModel;

/**
 * SubscriptionModel Class.
 *
 * @since 3.0.0
 */
class SubscriptionModel extends BaseModel {
	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table_name = 'tutor_subscriptions';

	const STATUS_PENDING   = 'pending';
	const STATUS_ACTIVE    = 'active';
	const STATUS_EXPIRED   = 'expired';
	const STATUS_HOLD      = 'hold';
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_TRASH     = 'trash';

	/**
	 * Enrollment meta for subscription.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const SUBSCRIPTION_ENROLLMENT_META = '_tutor_subscription_id';

	/**
	 * Get searchable fields
	 *
	 * This method is intendant to use with get order list
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_searchable_fields() {
		return array(
			's.id',
			's.status',
			'p.plan_name',
			'u.display_name',
			'u.user_login',
			'u.user_email',
		);
	}

	/**
	 * Get subscription status list.
	 *
	 * @since 3.0.0
	 *
	 * @param array $exclude exclude any status.
	 *
	 * @return array
	 */
	public function get_status_list( $exclude = array() ) {
		$list = array(
			self::STATUS_PENDING   => __( 'Pending', 'tutor-pro' ),
			self::STATUS_ACTIVE    => __( 'Active', 'tutor-pro' ),
			self::STATUS_EXPIRED   => __( 'Expired', 'tutor-pro' ),
			self::STATUS_HOLD      => __( 'Hold', 'tutor-pro' ),
			self::STATUS_CANCELLED => __( 'Cancelled', 'tutor-pro' ),
		);

		if ( ! empty( $exclude ) ) {
			foreach ( $exclude as $key ) {
				unset( $list[ $key ] );
			}
		}

		return $list;
	}

	/**
	 * Check subscription order type.
	 *
	 * @param string $order_type order type.
	 *
	 * @return boolean
	 */
	public static function is_subscription_order_type( $order_type ) {
		return in_array( $order_type, array( OrderModel::TYPE_SUBSCRIPTION, OrderModel::TYPE_RENEWAL ), true );
	}

	/**
	 * Get a subscription record by ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id subscription id.
	 *
	 * @return object|false
	 */
	public function get_subscription( $id ) {
		return $this->get_row( array( 'id' => $id ) );
	}

	/**
	 * Delete subscription.
	 *
	 * @since 3.0.0
	 *
	 * @param int|array $id single id or array.
	 *
	 * @return bool
	 */
	public function delete_subscription( $id ) {
		$ids = is_array( $id ) ? $id : array( intval( $id ) );
		return QueryHelper::bulk_delete_by_ids( $this->table_name, $ids ) ? true : false;
	}

	/**
	 * Get subscription list with pagination.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $where where clause conditions.
	 * @param string $search_term search clause conditions.
	 * @param int    $limit limit default 10.
	 * @param int    $offset default 0.
	 * @param string $order_by default sorting column.
	 * @param string $order list order default 'desc'.
	 *
	 * @return array
	 */
	public function get_subscriptions( array $where = array(), $search_term = '', int $limit = 10, int $offset = 0, string $order_by = 's.id', string $order = 'desc' ) {

		global $wpdb;

		$primary_table  = "{$this->table_name} s";
		$joining_tables = array(
			array(
				'type'  => 'LEFT',
				'table' => "{$wpdb->users} u",
				'on'    => 's.user_id = u.ID',
			),
			array(
				'type'  => 'LEFT',
				'table' => "{$wpdb->prefix}tutor_subscription_plans p",
				'on'    => 's.plan_id = p.id',
			),
		);

		$select_columns = array( 's.*', 'p.plan_name', 'u.user_login' );

		$search_clause = array();
		if ( '' !== $search_term ) {
			foreach ( $this->get_searchable_fields() as $column ) {
				$search_clause[ $column ] = $search_term;
			}
		}

		$response = array(
			'results'     => array(),
			'total_count' => 0,
		);

		try {
			return QueryHelper::get_joined_data( $primary_table, $joining_tables, $select_columns, $where, $search_clause, $order_by, $limit, $offset, $order );
		} catch ( \Throwable $th ) {
			// Log with error, line & file name.
			error_log( $th->getMessage() . ' in ' . $th->getFile() . ' at line ' . $th->getLine() );
			return $response;
		}
	}

	/**
	 * Get subscription count
	 *
	 * @since 3.0.0
	 *
	 * @param array  $where Where conditions, sql esc data.
	 * @param string $search_term Search terms, sql esc data.
	 *
	 * @return int
	 */
	public function get_subscription_count( $where = array(), string $search_term = '' ) {
		global $wpdb;

		$search_clause = array();
		if ( '' !== $search_term ) {
			foreach ( $this->get_searchable_fields() as $column ) {
				$search_clause[ $column ] = $search_term;
			}
		}

		$join_table = array(
			array(
				'type'  => 'LEFT',
				'table' => "{$wpdb->users} u",
				'on'    => 's.user_id = u.ID',
			),
			array(
				'type'  => 'LEFT',
				'table' => "{$wpdb->prefix}tutor_subscription_plans p",
				'on'    => 's.plan_id = p.id',
			),
		);

		$primary_table = "{$this->table_name} s";
		return QueryHelper::get_joined_count( $primary_table, $join_table, $where, $search_clause );
	}

	/**
	 * Update subscription status by order.
	 *
	 * @param object $order order object.
	 * @param string $subscription_status status.
	 * @param string $note note.
	 *
	 * @return void
	 */
	public function update_subscription_status_by_order( $order, $subscription_status, $note = '' ) {
		$subscription = $this->get_subscription_by_order( $order );
		$gmt_datetime = DateTimeHelper::now()->to_date_time_string();

		$update_data = array(
			'status'     => $subscription_status,
			'updated_at' => $gmt_datetime,
			'note'       => $note,
		);

		$this->update( $subscription->id, $update_data );
	}

	/**
	 * Check is any course plan subscribed.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id course id.
	 * @param int $user_id user id. default is current user id.
	 *
	 * @return object|false subscription object when user subscribed, false if no subscription found.
	 */
	public function is_any_course_plan_subscribed( $course_id, $user_id = 0 ) {
		$user_id    = tutor_utils()->get_user_id( $user_id );
		$price_type = tutor_utils()->price_type( $course_id );
		if ( Course::PRICE_TYPE_PAID !== $price_type ) {
			return false;
		}

		$course_plans = ( new PlanModel() )->get_course_plans( $course_id );

		if ( ! $course_plans ) {
			return false;
		}

		$subscription = false;
		foreach ( $course_plans as $plan ) {
			$is_subscribed = $this->is_subscribed( $plan->id, $user_id );
			if ( $is_subscribed ) {
				$subscription = $is_subscribed;
				break;
			}
		}

		return $subscription;
	}

	/**
	 * Get subscription parent order.
	 *
	 * @param object|int $subscription object or id.
	 *
	 * @return object|false false on no subscription found.
	 */
	public function get_parent_order( $subscription ) {
		if ( is_numeric( $subscription ) ) {
			$subscription = $this->get_subscription( $subscription );
		}

		if ( ! $subscription ) {
			return false;
		}

		return ( new OrderModel() )->get_order_by_id( $subscription->first_order_id );
	}

	/**
	 * Get subscription active order.
	 *
	 * @param object|int $subscription object or id.
	 *
	 * @return object|false false on no subscription found.
	 */
	public function get_active_order( $subscription ) {
		if ( is_numeric( $subscription ) ) {
			$subscription = $this->get_subscription( $subscription );
		}

		if ( ! $subscription ) {
			return false;
		}

		return ( new OrderModel() )->get_order_by_id( $subscription->active_order_id );
	}

	/**
	 * Get subscription record by order object or ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int|object $order order object or ID.
	 *
	 * @return object|false
	 */
	public function get_subscription_by_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = ( new OrderModel() )->get_order_by_id( $order );
		}

		$where = array(
			'user_id' => $order->user_id,
		);

		if ( OrderModel::TYPE_SUBSCRIPTION === $order->order_type ) {
			$where['first_order_id'] = $order->id;
		} else {
			$where['first_order_id'] = $order->parent_id;
		}

		return $this->get_row( $where );
	}

	/**
	 * Check a plan is subscribed or not
	 *
	 * @since 3.0.0
	 *
	 * @param int $plan_id plan id.
	 * @param int $user_id user id.
	 *
	 * @return object|false on success return object, false on fail.
	 */
	public function is_subscribed( $plan_id, $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$record = $this->get_row(
			array(
				'user_id' => $user_id,
				'plan_id' => $plan_id,
			)
		);

		if ( ! $record ) {
			return false;
		}

		return $record;
	}

	/**
	 * Check user has active plan subscription or not
	 *
	 * @since 3.0.0
	 *
	 * @param int $plan_id plan id.
	 * @param int $user_id user id.
	 *
	 * @return boolean
	 */
	public function has_active_subscription_plan( $plan_id, $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*)
				FROM {$this->table_name}
				WHERE user_id = %d
					AND plan_id = %d
					AND status = %s",
				$user_id,
				$plan_id,
				self::STATUS_ACTIVE
			)
		);
	}

	/**
	 * Get a plan subscription status.
	 *
	 * @since 3.0.0
	 *
	 * @param int $plan_id plan id.
	 * @param int $user_id user id.
	 *
	 * @return string|false subscription status or false if record not found.
	 */
	public function get_subscription_plan_status( $plan_id, $user_id ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$record = $this->get_row(
			array(
				'user_id' => $user_id,
				'plan_id' => $plan_id,
			)
		);

		return $record ? $record->status : false;
	}

	/**
	 * Get all expired subscriptions.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_all_expired_subscriptions() {
		$gmt_datetime = current_time( 'mysql', true );

		return $this->db->get_results(
			$this->db->prepare(
				"SELECT *
				FROM {$this->table_name}
				WHERE status = %s
					AND end_date_gmt <= %s
				ORDER BY end_date_gmt ASC",
				self::STATUS_ACTIVE,
				$gmt_datetime
			)
		);
	}

	/**
	 * Check a plan is subscribed by any user or not
	 *
	 * @since 3.0.0
	 *
	 * @param int $plan_id plan id.
	 *
	 * @return boolean
	 */
	public function is_plan_subscribed_by_any_user( $plan_id ) {
		return $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*)
				FROM {$this->table_name}
				WHERE plan_id = %d",
				$plan_id
			)
		);
	}

	/**
	 * Set a subscription as expired.
	 *
	 * @since 3.0.0
	 *
	 * @param object|int $subscription subscription object or id.
	 *
	 * @return bool
	 */
	public function set_subscription_expired( $subscription ) {
		if ( is_numeric( $subscription ) ) {
			$subscription = $this->get_subscription( $subscription );
		}

		$data = array(
			'updated_at' => current_time( 'mysql', true ),
			'status'     => self::STATUS_EXPIRED,
			'note'       => __( 'Subscription expired', 'tutor-pro' ),
		);

		$updated = (bool) $this->update( $subscription->id, $data );

		if ( $updated ) {
			do_action( 'tutor_subscription_expired', $subscription );
		}

		return $updated;
	}

	/**
	 * Get subscription orders.
	 *
	 * @since 3.0.0
	 *
	 * @param object|int $subscription subscription or id.
	 * @param int        $limit limit.
	 * @param int        $offset offset.
	 *
	 * @return array contains results and total_count key.
	 */
	public function get_subscription_orders( $subscription, $limit = 10, $offset = 0 ) {
		if ( is_numeric( $subscription ) ) {
			$subscription = $this->get_subscription( $subscription );
		}

		$orders = ( new OrderModel() )->get_orders( array( 'parent_id' => $subscription->first_order_id ), '', $limit, $offset );

		return $orders;
	}

	/**
	 * Formatted subscription price.
	 *
	 * @since 3.0.0
	 *
	 * @param int|object $subscription subscription id or object.
	 * @param boolean    $echo print or not.
	 *
	 * @return void|string
	 */
	public function formatted_subscription_price( $subscription, $echo = true ) {
		if ( is_numeric( $subscription ) ) {
			$subscription = $this->get_subscription( $subscription );
		}

		$plan_model = new PlanModel();
		$plan       = $plan_model->get_plan( $subscription->plan_id );

		$total_price = tutor_get_formatted_price( $plan->regular_price );
		$price_str   = '';
		if ( $plan_model::PAYMENT_ONETIME === $plan->payment_type ) {
			/* translators: %s: price */
			$price_str = sprintf( __( '%s/One Time', 'tutor-pro' ), $total_price );
		} else {
			if ( $plan->recurring_value > 1 ) {
				/* translators: %1$s: total price, %2$s: recurring count, %3$s: recurring interval */
				$price_str = sprintf( __( '%1$s/%2$s %3$s', 'tutor-pro' ), $total_price, $plan->recurring_value, $plan->recurring_interval );
			} else {
				/* translators: %1$s: total price, %2$s: recurring interval */
				$price_str = sprintf( __( '%1$s/%2$s', 'tutor-pro' ), $total_price, $plan->recurring_interval );
			}
		}

		if ( $echo ) {
			echo esc_html( $price_str );
		} else {
			return $price_str;
		}
	}

	/**
	 * Check subscription should renew or not.
	 *
	 * @since 3.0.0
	 *
	 * @param object|int $subscription subscription or id.
	 *
	 * @return boolean
	 */
	public function should_renew_subscription( $subscription ) {
		if ( is_numeric( $subscription ) ) {
			$subscription = $this->get_subscription( $subscription );
		}

		$plan = ( new PlanModel() )->get_plan( $subscription->plan_id );
		if ( PlanModel::PAYMENT_RECURRING !== $plan->payment_type ) {
			return false;
		}

		$recurring_limit = (int) $plan->recurring_limit;
		// Until cancelled.
		if ( 0 === $recurring_limit ) {
			return true;
		}

		$orders = $this->get_subscription_orders( $subscription );
		if ( $orders['total_count'] < $recurring_limit ) {
			return true;
		}

		return false;
	}

	/**
	 * Get subscription list URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string $area area.
	 *
	 * @return string
	 */
	public static function get_subscription_list_url( $area = 'frontend' ) {
		$list_url = tutor_utils()->get_tutor_dashboard_page_permalink( 'subscriptions' );
		if ( 'backend' === $area ) {
			$list_url = admin_url( 'admin.php?page=tutor-subscriptions' );
		}
		return $list_url;
	}

	/**
	 * Subscription details page URL.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $subscription_id subscription id.
	 * @param string $area area name.
	 *
	 * @return string
	 */
	public static function get_subscription_details_url( $subscription_id, $area = 'frontend' ) {
		$list_url = tutor_utils()->get_tutor_dashboard_page_permalink( 'subscriptions' );
		$url      = add_query_arg( array( 'id' => $subscription_id ), $list_url );

		if ( 'backend' === $area ) {
			$params = array(
				'action' => 'edit',
				'id'     => $subscription_id,
			);
			$url    = add_query_arg( $params, admin_url( 'admin.php?page=tutor-subscriptions' ) );
		}

		return $url;
	}
}
