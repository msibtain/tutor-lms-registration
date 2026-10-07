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

use Tutor\Cache\TutorCache;
use TUTOR\Course;
use Tutor\Ecommerce\Tax;
use Tutor\Models\BaseModel;
use Tutor\Models\OrderModel;
use Tutor\Models\CourseModel;
use Tutor\Helpers\QueryHelper;
use Tutor\Helpers\DateTimeHelper;

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
	 * Plan status
	 *
	 * @since 3.2.0
	 */
	const STATUS_ALL      = -1;
	const STATUS_ACTIVE   = 1;
	const STATUS_INACTIVE = 0;

	/**
	 * Payment type.
	 */
	const PAYMENT_ONETIME   = 'onetime';
	const PAYMENT_RECURRING = 'recurring';

	/**
	 * Plan types
	 */
	const TYPE_COURSE    = 'course';
	const TYPE_BUNDLE    = 'bundle';
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
			self::TYPE_BUNDLE    => __( 'Bundle', 'tutor-pro' ),
			self::TYPE_CATEGORY  => __( 'Category', 'tutor-pro' ),
			self::TYPE_FULL_SITE => __( 'Full Site', 'tutor-pro' ),
		);
	}

	/**
	 * Get plan type
	 *
	 * @since 3.0.0
	 * @since 3.2.0 param suffix added.
	 *
	 * @param string $type plan type.
	 * @param string $suffix suffix.
	 *
	 * @return string
	 */
	public function get_type_label( $type, $suffix = '' ) {
		$label     = '';
		$type_list = $this->get_type_list();
		if ( $type_list[ $type ] ) {
			$label = $type_list[ $type ] . ( ! empty( $suffix ) ? " $suffix" : '' );
		}

		return $label;
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
		$cache_key = 'tutor_get_plan_' . $id;
		$cached    = TutorCache::get( $cache_key );

		if ( $cached ) {
			return $cached;
		}

		$plan = $this->get_row( array( 'id' => $id ) );
		if ( $plan ) {
			if ( in_array( $plan->plan_type, array( self::TYPE_COURSE, self::TYPE_BUNDLE ) ) ) {
				$course_id = $this->get_object_id_by_plan( $id );
				if ( ! CourseModel::is_course_accessible( $course_id ) ) {
					return false;
				}
			}
			$plan->in_sale_price      = $this->in_sale_price( $plan );
			$plan->is_membership_plan = $this->is_membership_plan( $plan );
			$plan->has_trial_period   = $plan->trial_value > 0;
			$is_enabled               = (int) $plan->is_enabled;
			if ( ! $is_enabled ) {
				return false;
			}
		}

		TutorCache::set( $cache_key, $plan );

		return $plan;
	}

	/**
	 * Get plan info by order id or object.
	 *
	 * @since 3.0.1
	 *
	 * @param int|object $order order id or object.
	 *
	 * @return object
	 */
	public function get_plan_by_order( $order ) {
		$order = OrderModel::get_order( $order );

		if ( $order && OrderModel::is_subscription_order( $order ) ) {
			return $this->get_plan( $order->items[0]['item_id'] );
		}

		return false;
	}

	/**
	 * Get object id by plan.
	 *
	 * @param int $plan_id plan id.
	 *
	 * @return int course or bundle id if success, 0 on fail.
	 */
	public function get_object_id_by_plan( $plan_id ) {
		$cache_key = "get_object_id_by_plan_{$plan_id}";
		$cached    = TutorCache::get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$object_id = (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT object_id FROM {$this->db->prefix}tutor_subscription_plan_items
				WHERE plan_id = %d",
				$plan_id,
			)
		);

		TutorCache::set( $cache_key, $object_id );

		return $object_id;
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

		$renew_time      = $plan->recurring_value > 1 ? $plan->recurring_value : '';
		$renew_time     .= $plan->recurring_value > 1 ? ' ' : '';
		$renew_time     .= $plan->recurring_interval . '' . ( $plan->recurring_value > 1 ? 's' : '' );
		$recurring_limit = (int) $plan->recurring_limit;

		if ( 0 === $recurring_limit ) {
			/* translators: %s: renew time */
			$features[] = sprintf( __( 'Billed every %s until canceled', 'tutor-pro' ), $renew_time );
		} elseif ( -1 === $recurring_limit ) {
			$features[] = __( 'No renewals after initial subscription', 'tutor-pro' );
		} else {
			/* translators: %s: renew time, %s: recurring limit, %s: interval */
			$features[] = sprintf( __( 'Billed every %1$s for %2$s billing cycles', 'tutor-pro' ), $renew_time, $plan->recurring_limit, $plan->recurring_limit > 1 ? 's' : '' );
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
	 * Prepare renew text.
	 *
	 * @since 3.9.0
	 *
	 * @param int $recurring_limit recurring limit.
	 *
	 * @return string
	 */
	public function prepare_renew_text( $recurring_limit ) {
		$label = '';
		if ( -1 === $recurring_limit ) {
			$label = __( 'No renewal', 'tutor-pro' );
		} elseif ( 0 === $recurring_limit ) {
			$label = __( 'Until cancelled', 'tutor-pro' );
		} else {
			/* translators: %s: recurring limit */
			$label = sprintf( _n( '%s Time', '%s Times', $recurring_limit, 'tutor-pro' ), $recurring_limit );
		}

		return $label;
	}

	/**
	 * Check subscription plan exist.
	 *
	 * @since 3.0.0
	 *
	 * @param int $plan_id plan id.
	 * @param int $object_id object id.
	 *
	 * @return boolean
	 */
	public function has_subscription_plan( $plan_id, $object_id ) {
		$plan_count = QueryHelper::get_count(
			$this->db->prefix . 'tutor_subscription_plan_items',
			array(
				'plan_id'   => $plan_id,
				'object_id' => $object_id,
			)
		);

		return (bool) $plan_count;
	}

	/**
	 * Create a subscription plan for course or bundle
	 *
	 * @param int   $object_id object id.
	 * @param array $data data.
	 *
	 * @return int
	 */
	public function create_subscription_plan( $object_id, $data ) {
		$plan_id = $this->create( $data );

		if ( isset( $data['plan_type'] ) && in_array( $data['plan_type'], array( self::TYPE_COURSE, self::TYPE_BUNDLE ), true ) ) {
			QueryHelper::insert(
				$this->db->prefix . 'tutor_subscription_plan_items',
				array(
					'plan_id'     => $plan_id,
					'object_name' => $data['plan_type'],
					'object_id'   => $object_id,
				)
			);
		}

		return $plan_id;
	}

	/**
	 * Get all plans.
	 *
	 * @since 3.0.0
	 * @since 3.8.0 param status added.
	 *
	 * @param int $object_id object id.
	 * @param int $status -1 for all, 0 for inactive, 1 for active.
	 *
	 * @return array|object|null
	 */
	public function get_subscription_plans( $object_id, $status = self::STATUS_ALL ) {
		$cache_key = "get_subscription_plans_{$object_id}_{$status}";
		$cached    = TutorCache::get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$status_clause = '';
		if ( self::STATUS_ALL !== $status ) {
			$status_clause = $this->db->prepare( 'AND is_enabled = %d', $status );
		}

		$plans = $this->db->get_results(
			$this->db->prepare(
				"SELECT plan.* FROM {$this->db->prefix}tutor_subscription_plans AS plan
				INNER JOIN {$this->db->prefix}tutor_subscription_plan_items AS item
				ON item.plan_id = plan.id
				WHERE plan.payment_type = %s
				AND item.object_id = %d
				{$status_clause}
				ORDER BY plan.plan_order ASC",
				self::PAYMENT_RECURRING,
				$object_id
			)
		);

		TutorCache::set( $cache_key, $plans );

		return $plans;
	}

	/**
	 * Get plan category ids.
	 *
	 * @since 3.2.0
	 *
	 * @param int $plan_id plan id.
	 *
	 * @return array
	 */
	public function get_plan_category_ids( $plan_id ) {
		$cache_key = "get_plan_category_ids_{$plan_id}";
		$cached    = TutorCache::get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$category_ids = $this->db->get_col(
			$this->db->prepare(
				"SELECT object_id FROM {$this->db->prefix}tutor_subscription_plan_items AS plan_items
				WHERE plan_items.plan_id = %d
				AND plan_items.object_name = %s",
				$plan_id,
				self::TYPE_CATEGORY
			)
		);

		$category_ids = array_map( 'intval', $category_ids );
		TutorCache::set( $cache_key, $category_ids );

		return $category_ids;
	}

	/**
	 * Get plan categories.
	 *
	 * @since 3.4.0
	 *
	 * @param int $plan_id plan id.
	 *
	 * @return array
	 */
	public function get_plan_categories( $plan_id ) {
		return get_terms(
			array(
				'taxonomy' => CourseModel::COURSE_CATEGORY,
				'include'  => $this->get_plan_category_ids( $plan_id ),
			)
		);
	}

	/**
	 * Get membership plan type
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	public static function get_membership_plan_types() {
		return array(
			self::TYPE_FULL_SITE,
			self::TYPE_CATEGORY,
		);
	}

	/**
	 * Get subscription plan types
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	public static function get_subscription_plan_types() {
		return array(
			self::TYPE_COURSE,
			self::TYPE_BUNDLE,
		);
	}

	/**
	 * Get list of membership plans.
	 *
	 * @since 3.2.0
	 *
	 * @param int $status -1 for all, 0 for inactive, 1 for active.
	 *
	 * @return array|object|null
	 */
	public function get_membership_plans( $status = self::STATUS_ALL ) {
		$where = array( 'plan_type' => self::get_membership_plan_types() );
		if ( self::STATUS_ALL !== $status ) {
			$where['is_enabled'] = $status;
		}

		$list = QueryHelper::get_all( $this->table_name, $where, 'plan_order', -1, 'ASC' );

		foreach ( $list as $row ) {
			if ( self::TYPE_CATEGORY === $row->plan_type ) {
				$cat_ids    = $this->get_plan_category_ids( $row->id );
				$terms      = tutor_utils()->get_course_categories( 0, array( 'include' => $cat_ids ) );
				$categories = array();

				foreach ( $terms  as $term ) {
					$thumb_id     = get_term_meta( $term->term_id, 'thumbnail_id', true );
					$categories[] = array(
						'id'            => $term->term_id,
						'title'         => $term->name,
						'image'         => $thumb_id ? wp_get_attachment_thumb_url( $thumb_id ) : tutor()->url . 'assets/images/placeholder.svg',
						'total_courses' => (int) $term->count,
					);
				}

				$row->cat_ids    = $cat_ids;
				$row->categories = $categories;
			}
		}

		return $list;
	}

	/**
	 * Check is plan is membership plan.
	 *
	 * @since 3.2.0
	 *
	 * @param int $plan plan id or object.
	 *
	 * @return boolean
	 */
	public function is_membership_plan( $plan ) {
		if ( is_numeric( $plan ) ) {
			$plan = $this->get_plan( $plan );
		}

		return in_array( $plan->plan_type, $this->get_membership_plan_types(), true );
	}

	/**
	 * Check is plan is subscription plan.
	 *
	 * @since 3.2.0
	 *
	 * @param int $plan plan id or object.
	 *
	 * @return boolean
	 */
	public function is_subscription_plan( $plan ) {
		if ( is_numeric( $plan ) ) {
			$plan = $this->get_plan( $plan );
		}

		return in_array( $plan->plan_type, $this->get_subscription_plan_types(), true );
	}

	/**
	 * Check site has active membership plans.
	 *
	 * @since 3.2.0
	 *
	 * @return boolean
	 */
	public function has_active_membership_plans(): bool {
		$cache_key = 'tutor_has_active_membership_monetization';
		$has_cache = TutorCache::get( $cache_key );

		if ( false !== $has_cache ) {
			return (bool) $has_cache > 0;
		}

		$active_plans = QueryHelper::get_count(
			$this->table_name,
			array(
				'plan_type'  => $this->get_membership_plan_types(),
				'is_enabled' => self::STATUS_ACTIVE,
			)
		);

		TutorCache::set( $cache_key, $active_plans );

		return (bool) $active_plans > 0;
	}

	/**
	 * Check category is accessible with plan or not.
	 *
	 * @since 3.2.0
	 *
	 * @param int $plan_id plan id.
	 * @param int $course_id course id.
	 *
	 * @return boolean
	 */
	public function can_access_category_with_plan( $plan_id, $course_id ) {
		$plan_categories     = $this->get_plan_category_ids( $plan_id );
		$course_category_ids = wp_get_post_terms( $course_id, CourseModel::COURSE_CATEGORY, array( 'fields' => 'ids' ) );
		$has_access          = count( array_intersect( $plan_categories, $course_category_ids ) ) > 0;
		return $has_access;
	}

	/**
	 * Get attached plan category ids.
	 *
	 * @since 3.2.0
	 *
	 * @param int $plan_id plan id.
	 *
	 * @return array list of attache category ids.
	 */
	public function get_attached_plan_categories( $plan_id ) {
		$plan_items_table = $this->db->prefix . 'tutor_subscription_plan_items';
		$existing_ids     = $this->db->get_col(
			$this->db->prepare(
				"SELECT object_id FROM {$plan_items_table}
				WHERE plan_id = %d AND object_name = %s",
				$plan_id,
				self::TYPE_CATEGORY
			)
		);

		return array_map( 'intval', $existing_ids );
	}
	/**
	 * Attached categories to a plan.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $plan_id plan id.
	 * @param array $cat_ids category ids.
	 *
	 * @return void
	 */
	public function attach_categories_to_plan( $plan_id, $cat_ids ) {
		$plan_items_table = $this->db->prefix . 'tutor_subscription_plan_items';

		$existing_ids = $this->get_attached_plan_categories( $plan_id );

		sort( $cat_ids );
		sort( $existing_ids );

		if ( $existing_ids === $cat_ids ) {
			return;
		}

		// Delete old attached categories.
		$this->db->delete(
			$plan_items_table,
			array(
				'plan_id'     => $plan_id,
				'object_name' => self::TYPE_CATEGORY,
			)
		);

		// Now attached the categories to plan.
		foreach ( $cat_ids as $cat_id ) {
			QueryHelper::insert(
				$plan_items_table,
				array(
					'plan_id'     => $plan_id,
					'object_name' => self::TYPE_CATEGORY,
					'object_id'   => $cat_id,
				)
			);
		}
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
	 * Set a subscription plan as featured from course plans.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $object_id object id.
	 * @param int    $plan_id plan id.
	 * @param string $featured_text featured text.
	 *
	 * @return void
	 */
	public function set_subscription_plan_as_featured( $object_id, $plan_id, $featured_text = '' ) {
		global $wpdb;

		$object_name = tutor()->course_post_type === get_post_type( $object_id )
						? self::TYPE_COURSE
						: self::TYPE_BUNDLE;

		// Remove all featured flag related to $object_id.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}tutor_subscription_plans p
				JOIN {$wpdb->prefix}tutor_subscription_plan_items pi ON pi.plan_id = p.id
				SET p.is_featured = 0, p.featured_text = ''
				WHERE p.plan_type = %s
				AND pi.object_name = %s
				AND pi.object_id = %d",
				$object_name,
				$object_name,
				$object_id
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
			$apply_plan_trial = apply_filters( 'tutor_apply_plan_trial', $plan->trial_value > 0, $order->user_id, $plan );
			if ( $apply_plan_trial ) {
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

	/**
	 * Get membership plans for course ids.
	 *
	 * @since 3.3.0
	 *
	 * @param array $course_ids course ids.
	 *
	 * @return array
	 */
	public function get_membership_plans_for_course_ids( array $course_ids ) {
		$cache_key = 'get_membership_plans_for_course_ids_' . implode( '_', $course_ids );
		$cached    = TutorCache::get( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$plans_table      = $this->db->prefix . 'tutor_subscription_plans';
		$plan_items_table = $this->db->prefix . 'tutor_subscription_plan_items';

		$final_plan_list_cache_key = __FUNCTION__ . '_plan_list';
		$final_plan_list           = TutorCache::get( $final_plan_list_cache_key );
		if ( false === $final_plan_list ) {
			$final_plan_list = QueryHelper::get_all( $plans_table, array( 'plan_type' => self::TYPE_FULL_SITE ), 'id', -1 );
			TutorCache::set( $final_plan_list_cache_key, $final_plan_list );
		}

		$category_ids = array();
		foreach ( $course_ids as $course_id ) {
			$course_category_ids = wp_get_post_terms( $course_id, CourseModel::COURSE_CATEGORY, array( 'fields' => 'ids' ) );
			$category_ids        = array_merge( $category_ids, $course_category_ids );
		}

		$category_ids = array_unique( $category_ids );

		if ( count( $category_ids ) ) {
			$primary_table  = "{$plans_table} plan";
			$joining_tables = array(
				array(
					'type'  => 'INNER',
					'table' => "{$plan_items_table} item",
					'on'    => 'plan.id = item.plan_id',
				),
			);

			$where = array(
				'plan.plan_type'   => self::TYPE_CATEGORY,
				'item.object_name' => self::TYPE_CATEGORY,
				'item.object_id'   => $category_ids,
			);

			$query = QueryHelper::get_joined_data(
				$primary_table,
				$joining_tables,
				array( 'DISTINCT plan.*' ),
				$where,
				array(),
				'plan.id',
				PHP_INT_MAX
			);

			$final_plan_list = array_merge( $final_plan_list, $query['results'] );
		}

		TutorCache::set( $cache_key, $final_plan_list );

		return $final_plan_list;
	}

	/**
	 * Check tax collection is enabled for plan.
	 *
	 * @since 3.7.0
	 *
	 * @param int $plan_id plan id.
	 *
	 * @return boolean
	 */
	public function is_tax_enabled_for_plan( $plan_id ) {
		if ( ! Tax::is_individual_control_enabled() ) {
			return true;
		}

		$plan_info = $this->get_plan( $plan_id );
		if ( is_object( $plan_info ) && $plan_info->is_membership_plan ) {
			$tax_collection = (bool) $plan_info->tax_collection;
		} else {
			/**
			 * Course/Bundle subscription
			 * object id can be course or bundle id.
			 */
			$object_id      = $this->get_object_id_by_plan( $plan_info->id );
			$meta_value     = get_post_meta( $object_id, Course::TAX_ON_SUBSCRIPTION_META, true );
			$tax_collection = '0' !== $meta_value;
		}

		return $tax_collection;
	}

	/**
	 * Get subscription plans based on specific filter criteria.
	 *
	 * @since 3.8.1
	 *
	 * @param array $where Associative array of conditions for filtering subscription plans.
	 *
	 * @return array Returns an array of subscription plans and their associated plan items.
	 *               Returns an empty array if no plans match the criteria or if an error occurs.
	 */
	public static function get_filtered_subscription_plans( array $where ): array {
		global $wpdb;

		if ( empty( $where ) || ! is_array( $where ) ) {
			return array();
		}

		$primary_table  = "{$wpdb->tutor_subscription_plans} AS plans";
		$joining_tables = array(
			array(
				'type'  => 'LEFT',
				'table' => "{$wpdb->tutor_subscription_plan_items} AS plan_items",
				'on'    => 'plans.id = plan_items.plan_id',
			),
		);
		$select_columns = array(
			'plans.*,
			plan_items.object_name,
			plan_items.object_id',
		);

		$result = QueryHelper::get_joined_data( $primary_table, $joining_tables, $select_columns, $where, array(), '', -1 );

		return $result['results'];
	}

	/**
	 * Check is plan course is deleted.
	 *
	 * @since 3.8.2
	 *
	 * @param int $plan plan id or object.
	 *
	 * @return boolean
	 */
	public function is_plan_course_deleted( $plan ) {
		if ( is_numeric( $plan ) ) {
			$plan = $this->get_plan( $plan );
		}

		if ( $plan->is_membership_plan ) {
			return false;
		}

		$object_id = $this->get_object_id_by_plan( $plan->id );
		return ! get_post( $object_id );
	}
}
