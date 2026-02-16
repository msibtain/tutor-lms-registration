<?php
/**
 * Plan Model
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Models;

use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\QueryHelper;
use Tutor\Models\OrderModel;

/**
 * PlanModel Class.
 *
 * @since 3.0.0
 */
class PlanModel extends BaseModel {
	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table_name = 'tutor_subscription_plans';

	/**
	 * Payment type.
	 */
	const PAYMENT_ONETIME   = 'onetime';
	const PAYMENT_RECURRING = 'recurring';

	/**
	 * Plan types
	 */
	const TYPE_COURSE    = 'course';
	const TYPE_CATEGORY  = 'category';
	const TYPE_FULL_SITE = 'full_site';

	/**
	 * Interval constant
	 */
	const INTERVAL_HOUR  = 'hour';
	const INTERVAL_DAY   = 'day';
	const INTERVAL_WEEK  = 'week';
	const INTERVAL_MONTH = 'month';
	const INTERVAL_YEAR  = 'year';

	/**
	 * Order meta.
	 */
	const META_ENROLLMENT_FEE = 'plan_enrollment_fee';

	/**
	 * Get interval list
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_interval_list() {
		return array(
			self::INTERVAL_HOUR  => __( 'Hour', 'tutor-pro' ),
			self::INTERVAL_DAY   => __( 'Day', 'tutor-pro' ),
			self::INTERVAL_WEEK  => __( 'Week', 'tutor-pro' ),
			self::INTERVAL_MONTH => __( 'Month', 'tutor-pro' ),
			self::INTERVAL_YEAR  => __( 'Year', 'tutor-pro' ),
		);
	}

	/**
	 * Get payment type list
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_payment_type_list() {
		return array(
			self::PAYMENT_ONETIME   => __( 'Onetime', 'tutor-pro' ),
			self::PAYMENT_RECURRING => __( 'Recurring', 'tutor-pro' ),
		);
	}

	/**
	 * Get interval value.
	 *
	 * @since 3.0.0
	 *
	 * @param string $interval interval.
	 *
	 * @return string
	 */
	public function get_interval( $interval ) {
		$interval_list = $this->get_interval_list();
		return $interval_list[ $interval ] ?? '';
	}

	/**
	 * Get plan types
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_type_list() {
		return array(
			self::TYPE_COURSE    => __( 'Course', 'tutor-pro' ),
			self::TYPE_CATEGORY  => __( 'Category', 'tutor-pro' ),
			self::TYPE_FULL_SITE => __( 'Full Site', 'tutor-pro' ),
		);
	}

	/**
	 * Get plan type
	 *
	 * @since 3.0.0
	 *
	 * @param string $type plan type.
	 *
	 * @return string
	 */
	public function get_type( $type ) {
		$type_list = $this->get_type_list();
		return $type_list[ $type ] ?? '';
	}

	/**
	 * Get a plan by ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id plan id.
	 *
	 * @return object|false
	 */
	public function get_plan( $id ) {
		return $this->get_row( array( 'id' => $id ) );
	}

	/**
	 * Get course id by plan.
	 *
	 * @param int $plan_id plan id.
	 *
	 * @return int course id if success, 0 on fail.
	 */
	public function get_course_id_by_plan( $plan_id ) {
		return (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT object_id FROM {$this->db->prefix}tutor_subscription_plan_items
				WHERE plan_id = %d AND object_name = %s",
				$plan_id,
				self::TYPE_COURSE
			)
		);
	}

	/**
	 * Get lowest price plan.
	 *
	 * @since 3.0.0
	 *
	 * @param array $plans plan list.
	 *
	 * @return numeric|null
	 */
	public function get_lowest_plan_price( $plans ) {
		$lowest_price = null;

		if ( ! is_array( $plans ) && 0 === count( $plans ) ) {
			return $lowest_price;
		}

		foreach ( $plans as $plan ) {
			$price = $this->in_sale_price( $plan ) ? $plan->sale_price : $plan->regular_price;
			// Update lowest price if it's null or current price is lower.
			if ( is_null( $lowest_price ) || $price < $lowest_price ) {
				$lowest_price = $price;
			}
		}

		return (float) $lowest_price;
	}

	/**
	 * Get lowest price plan.
	 *
	 * @param array $plans plan list.
	 *
	 * @return object|null
	 */
	public function get_lowest_price_plan( $plans ) {
		$lowest_price      = null;
		$lowest_price_plan = null;

		if ( ! is_array( $plans ) && 0 === count( $plans ) ) {
			return $lowest_price_plan;
		}

		foreach ( $plans as $plan ) {
			$price = $this->in_sale_price( $plan ) ? $plan->sale_price : $plan->regular_price;
			// Update lowest price if it's null or current price is lower.
			if ( is_null( $lowest_price ) || $price < $lowest_price ) {
				$lowest_price      = $price;
				$lowest_price_plan = $plan;
			}
		}

		return $lowest_price_plan;
	}

	/**
	 * Prepare plan features
	 *
	 * @since 3.0.0
	 *
	 * @param object $plan plan object.
	 *
	 * @return array
	 */
	public function prepare_plan_features( $plan ) {
		$features = array();

		$renew_time  = $plan->recurring_value > 1 ? $plan->recurring_value : '';
		$renew_time .= $plan->recurring_value > 1 ? ' ' : '';
		$renew_time .= $plan->recurring_interval . '' . ( $plan->recurring_value > 1 ? 's' : '' );

		if ( $plan->recurring_limit > 0 ) {
			/* translators: %s: renew time, %s: recurring limit, %s: interval */
			$features[] = sprintf( __( 'Billed every %1$s for %2$s billing cycles', 'tutor-pro' ), $renew_time, $plan->recurring_limit, $plan->recurring_limit > 1 ? 's' : '' );
		} else {
			/* translators: %s: renew time */
			$features[] = sprintf( __( 'Billed every %s until canceled', 'tutor-pro' ), $renew_time );
		}

		if ( $plan->trial_value ) {
			/* translators: %s: trial value, %s: interval */
			$features[] = sprintf( __( '%1$s %2$s trial', 'tutor-pro' ), $plan->trial_value, $plan->trial_interval . ( $plan->trial_value > 1 ? 's' : '' ) );
		}

		if ( $plan->enrollment_fee > 0 ) {
			/* translators: %s: enrollment fee */
			$features[] = sprintf( __( '%s enrollment fee added at checkout', 'tutor-pro' ), tutor_get_formatted_price( $plan->enrollment_fee ) );
		}

		if ( (bool) $plan->provide_certificate ) {
			$features[] = __( 'Certificate available', 'tutor-pro' );
		}

		return $features;
	}

	/**
	 * Check course plan exist.
	 *
	 * @since 3.0.0
	 *
	 * @param int $plan_id plan id.
	 * @param int $course_id course id.
	 *
	 * @return boolean
	 */
	public function has_course_plan( $plan_id, $course_id ) {
		$plan_count = QueryHelper::get_count(
			$this->db->prefix . 'tutor_subscription_plan_items',
			array(
				'plan_id'     => $plan_id,
				'object_name' => self::TYPE_COURSE,
				'object_id'   => $course_id,
			)
		);

		return (bool) $plan_count;
	}

	/**
	 * Create a plan for course.
	 *
	 * @param int   $course_id course id.
	 * @param array $data data.
	 *
	 * @return int
	 */
	public function create_course_plan( $course_id, $data ) {
		$plan_id = $this->create( $data );

		QueryHelper::insert(
			$this->db->prefix . 'tutor_subscription_plan_items',
			array(
				'plan_id'     => $plan_id,
				'object_name' => self::TYPE_COURSE,
				'object_id'   => $course_id,
			)
		);

		return $plan_id;
	}

	/**
	 * Get all plans.
	 *
	 * @param int $course_id course id.
	 *
	 * @return array|object|null
	 */
	public function get_course_plans( $course_id ) {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}tutor_subscription_plans AS plan
				INNER JOIN {$this->db->prefix}tutor_subscription_plan_items AS item
				ON item.plan_id = plan.id
				WHERE item.object_name = %s
				AND plan.payment_type = %s
				AND item.object_id = %d
				ORDER BY plan.plan_order ASC",
				self::TYPE_COURSE,
				self::PAYMENT_RECURRING,
				$course_id
			)
		);
	}

	/**
	 * Duplicate a plan.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $id plan id.
	 * @param array $override override data.
	 *
	 * @return int|false
	 */
	public function duplicate( $id, $override = array() ) {
		$plan = $this->get_row( array( 'id' => $id ) );

		if ( ! $plan ) {
			return false;
		}

		$new_data            = $plan;
		$new_data->plan_name = $new_data->plan_name . ' (Copy)';
		unset( $new_data->id );

		$new_data = wp_parse_args( $new_data, $override );
		$plan_id  = $this->create( $new_data );

		// Duplicate plan items.
		$items = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}tutor_subscription_plan_items
				WHERE plan_id = %d",
				$id
			)
		);

		foreach ( $items as $item ) {
			QueryHelper::insert(
				$this->db->prefix . 'tutor_subscription_plan_items',
				array(
					'plan_id'     => $plan_id,
					'object_name' => $item->object_name,
					'object_id'   => $item->object_id,
				)
			);
		}

		return $plan_id;
	}

	/**
	 * Check a plan is in sale price.
	 *
	 * @since 3.0.0
	 *
	 * @param int|object $plan plan id or object.
	 *
	 * @return mixed
	 */
	public function in_sale_price( $plan ) {
		if ( is_numeric( $plan ) ) {
			$plan = $this->get_row( array( 'id' => $plan ) );
		}

		$has_sale_price = floatval( $plan->sale_price ) > 0;
		$has_schedule   = ! empty( $plan->sale_price_from ) && ! empty( $plan->sale_price_to );

		if ( ! $has_sale_price ) {
			return false;
		}

		if ( $has_sale_price && $has_schedule ) {
			$current_timestamp = strtotime( 'now' );
			$from_timestamp    = strtotime( $plan->sale_price_from );
			$to_timestamp      = strtotime( $plan->sale_price_to );

			if ( $current_timestamp >= $from_timestamp && $current_timestamp <= $to_timestamp ) {
				return true;
			}
		} elseif ( $has_sale_price && ! $has_schedule ) {
			return true;
		}

		return false;
	}

	/**
	 * Set a plan as featured from course plans.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $course_id course id.
	 * @param int    $plan_id plan id.
	 * @param string $featured_text featured text.
	 *
	 * @return void
	 */
	public function set_course_plan_as_featured( $course_id, $plan_id, $featured_text = '' ) {
		global $wpdb;

		// Remove all featured flag related to $course_id.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}tutor_subscription_plans p
				JOIN {$wpdb->prefix}tutor_subscription_plan_items pi ON pi.plan_id = p.id
				SET p.is_featured = 0, p.featured_text = ''
				WHERE p.plan_type = %s
				AND pi.object_name = %s
				AND pi.object_id = %d",
				'course',
				'course',
				$course_id
			)
		);

		// Add featured flag.
		$this->update(
			$plan_id,
			array(
				'is_featured'   => 1,
				'featured_text' => $featured_text,
			),
		);
	}

	/**
	 * Calculate plan times.
	 *
	 * @since 3.0.0
	 *
	 * @param object|int $plan plan object or id.
	 * @param object     $order order object.
	 *
	 * @return array
	 */
	public function calculate_plan_times( $plan, $order ) {
		if ( is_numeric( $plan ) ) {
			$plan = $this->get_plan( $plan );
		}

		$gmt_datetime       = DateTimeHelper::now()->to_date_time_string();
		$trial_end_date_gmt = null;

		if ( OrderModel::TYPE_SUBSCRIPTION === $order->order_type ) {
			if ( $plan->trial_value ) {
				$trial_end_date_gmt = DateTimeHelper::now()->add( $plan->trial_value, $plan->trial_interval )->to_date_time_string();

				$start_date_gmt        = $trial_end_date_gmt;
				$next_payment_date_gmt = $trial_end_date_gmt;
				$end_date_gmt          = $trial_end_date_gmt;
			} else {
				$start_date_gmt        = $gmt_datetime;
				$end_date_gmt          = DateTimeHelper::create( $start_date_gmt )->add( $plan->recurring_value, $plan->recurring_interval )->to_date_time_string();
				$next_payment_date_gmt = $end_date_gmt;
			}
		} else {
			// For renewal.
			$subscription_model = new SubscriptionModel();
			$subscription       = $subscription_model->get_subscription_by_order( $order );

			$trial_end_date_gmt    = $subscription->trial_end_date_gmt;
			$start_date_gmt        = $subscription->start_date_gmt;
			$end_date_gmt          = DateTimeHelper::create( $subscription->end_date_gmt )->add( $plan->recurring_value, $plan->recurring_interval )->to_date_time_string();
			$next_payment_date_gmt = $end_date_gmt;
		}

		return array(
			'trial_end_date_gmt'    => $trial_end_date_gmt,
			'start_date_gmt'        => $start_date_gmt,
			'end_date_gmt'          => $end_date_gmt,
			'next_payment_date_gmt' => $next_payment_date_gmt,
		);
	}
}
