<?php
/**
 * Manage database tables related to subscription
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

use Tutor\Helpers\QueryHelper;

/**
 * Database Class.
 *
 * @since 3.0.0
 */
class Database {
	/**
	 * Register hooks and dependencies
	 */
	public function __construct() {
		add_action( 'tutor_addon_before_enable_tutor-pro/addons/subscription/subscription.php', array( $this, 'create_tables' ) );
		add_action( 'admin_init', array( $this, 'migration' ) );
	}

	/**
	 * Create database tables
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$plan_table_schema = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tutor_subscription_plans (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

			payment_type VARCHAR(50) DEFAULT 'recurring' NOT NULL, -- options: recurring, onetime
            plan_type VARCHAR(50) NOT NULL, -- course, full_site
			restriction_mode VARCHAR(50), -- include, exclude or null

			plan_name VARCHAR(255) NOT NULL,
			short_description VARCHAR(255) NULL,
			description TEXT,
			is_featured TINYINT(1) DEFAULT 0, -- 0 or 1
			featured_text VARCHAR(255) NULL, -- featured badge text
            
            recurring_value INT(11) DEFAULT 1 NOT NULL,
            recurring_interval VARCHAR(10) DEFAULT 'month' NOT NULL, 	-- day, week, month, year
			recurring_limit INT(11) DEFAULT 0, 		-- how many times the plan can be recurring. Zero for until cancelled

			regular_price DECIMAL(10,2) NOT NULL,
			sale_price DECIMAL(10,2) NULL,
            sale_price_from DATETIME NULL, 			-- sale price start date-time
            sale_price_to DATETIME NULL, 			-- sale price end date-time
			
			tax_collection TINYINT(1) DEFAULT 1,      -- 0 or 1
            provide_certificate TINYINT(1) DEFAULT 1, -- 0 or 1
            enrollment_fee DECIMAL(10,2) DEFAULT 0,
            trial_value INT(11) DEFAULT 0,
            trial_interval VARCHAR(50) NULL, 		  -- hour, day, week, month, year
			is_enabled TINYINT(1) DEFAULT 1,          -- plan enable disable
            plan_order BIGINT(20) UNSIGNED DEFAULT 0, -- plan sorting order

			PRIMARY KEY (id)
		) $charset_collate;";

		$plan_items_table_schema = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tutor_subscription_plan_items (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			plan_id BIGINT(20) UNSIGNED NOT NULL,
			object_name VARCHAR(50) NOT NULL, -- category, course
			object_id BIGINT(20) UNSIGNED NOT NULL, -- course_id, category_id

			INDEX idx_object_name (object_name),
			PRIMARY KEY (id),
			FOREIGN KEY (plan_id) REFERENCES  {$wpdb->prefix}tutor_subscription_plans(id) ON DELETE CASCADE
		) $charset_collate;";

		$subscriptions_table_schema = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tutor_subscriptions (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			plan_id BIGINT(20) UNSIGNED NOT NULL,
			first_order_id BIGINT(20) UNSIGNED NOT NULL,
			active_order_id BIGINT(20) UNSIGNED NOT NULL,
			status VARCHAR(50) NOT NULL,
			auto_renew TINYINT(1) DEFAULT 1, -- 0 or 1

			is_trial_enabled TINYINT(1) DEFAULT 0,
			is_trial_used TINYINT(1) DEFAULT 0,
			trial_end_date_gmt DATETIME NULL,
			start_date_gmt DATETIME NULL,
			end_date_gmt DATETIME NULL,
			next_payment_date_gmt DATETIME NOT NULL,

			note TEXT,
			created_at_gmt DATETIME NULL,
			updated_at_gmt DATETIME NULL,

			PRIMARY KEY (id),
			UNIQUE KEY key_user_plan (user_id, plan_id),
			FOREIGN KEY (plan_id) REFERENCES {$wpdb->prefix}tutor_subscription_plans(id),
			FOREIGN KEY (first_order_id) REFERENCES {$wpdb->prefix}tutor_orders(id),
			INDEX idx_user_id (user_id),
			INDEX idx_plan_id (plan_id),
			INDEX idx_first_order_id (first_order_id),
			INDEX idx_active_order_id (active_order_id),
			INDEX idx_status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $plan_table_schema );
		dbDelta( $plan_items_table_schema );
		dbDelta( $subscriptions_table_schema );
		dbDelta( $this->get_subscription_meta_table_schema() );
	}

	/**
	 * Table migration.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function migration() {
		global $wpdb;

		$plan_table              = $wpdb->prefix . 'tutor_subscription_plans';
		$subscriptions_table     = $wpdb->prefix . 'tutor_subscriptions';
		$subscription_meta_table = $wpdb->prefix . 'tutor_subscriptionmeta';

		$plan_table_exist              = QueryHelper::table_exists( $plan_table );
		$subscription_table_exist      = QueryHelper::table_exists( $subscriptions_table );
		$subscription_meta_table_exist = QueryHelper::table_exists( $subscription_meta_table );

		/**
		 * Column `short_description`, `is_enabled` added.
		 *
		 * @since 3.2.0
		 */
		if ( $plan_table_exist && ! QueryHelper::column_exist( $plan_table, 'is_enabled' ) ) {
			$wpdb->query( "ALTER TABLE {$plan_table} ADD COLUMN is_enabled TINYINT(1) DEFAULT 1 AFTER trial_interval" ); //phpcs:ignore
			$wpdb->query( "ALTER TABLE {$plan_table} ADD COLUMN short_description VARCHAR(255) DEFAULT NULL AFTER plan_name" ); //phpcs:ignore
		}

		/**
		 * Column `trial_fee` added.
		 *
		 * @since 3.4.0
		 */
		if ( $plan_table_exist && ! QueryHelper::column_exist( $plan_table, 'trial_fee' ) ) {
			$wpdb->query( "ALTER TABLE {$plan_table} ADD COLUMN trial_fee DECIMAL(10,2) DEFAULT 0 AFTER trial_interval" ); //phpcs:ignore
		}

		/**
		 * Column `tax_collection` added.
		 *
		 * @since 3.7.0
		 */
		if ( $plan_table_exist && ! QueryHelper::column_exist( $plan_table, 'tax_collection' ) ) {
			$wpdb->query( "ALTER TABLE {$plan_table} ADD COLUMN tax_collection TINYINT(1) DEFAULT 1 AFTER sale_price_to" ); //phpcs:ignore
		}

		/**
		 * Create subscription meta table for who has subscription addon enabled.
		 *
		 * @since 3.2.0
		 */
		if ( $subscription_table_exist && ! $subscription_meta_table_exist ) {
			$subscription_meta_table_schema = $this->get_subscription_meta_table_schema();

			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			dbDelta( $subscription_meta_table_schema );
		}

		/**
		 * Column `auto_renew` added.
		 *
		 * @since 3.3.0
		 */
		if ( $subscription_table_exist && ! QueryHelper::column_exist( $subscriptions_table, 'auto_renew' ) ) {
			$wpdb->query( "ALTER TABLE {$subscriptions_table} ADD COLUMN auto_renew TINYINT(1) DEFAULT 1 AFTER status" ); //phpcs:ignore
		}

		/**
		 * Column `is_trial_enabled` and `is_trial_used` added.
		 *
		 * @since 3.4.0
		 */
		if ( $subscription_table_exist && ! QueryHelper::column_exist( $subscriptions_table, 'is_trial_enabled' ) ) {
			$wpdb->query( "ALTER TABLE {$subscriptions_table} ADD COLUMN is_trial_enabled TINYINT(1) DEFAULT 0 AFTER auto_renew" ); //phpcs:ignore
			$wpdb->query( "ALTER TABLE {$subscriptions_table} ADD COLUMN is_trial_used TINYINT(1) DEFAULT 0 AFTER is_trial_enabled" ); //phpcs:ignore
		}
	}

	/**
	 * Get subscription meta table schema.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_subscription_meta_table_schema() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$subscription_meta_table_schema = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tutor_subscriptionmeta (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			subscription_id BIGINT(20) UNSIGNED NOT NULL,
			meta_key VARCHAR(255) NOT NULL,
			meta_value LONGTEXT,

			PRIMARY KEY (id),
			KEY subscription_id (subscription_id),
			KEY meta_key (meta_key),
			CONSTRAINT fk_tutor_subscriptionmeta_subscription_id 
						FOREIGN KEY (subscription_id) 
						REFERENCES {$wpdb->prefix}tutor_subscriptions(id) 
						ON DELETE CASCADE
		) $charset_collate;";

		return $subscription_meta_table_schema;
	}
}
