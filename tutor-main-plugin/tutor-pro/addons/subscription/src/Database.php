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
			
            provide_certificate TINYINT(1) DEFAULT 1, -- 0 or 1
            enrollment_fee DECIMAL(10,2) DEFAULT 0,
            trial_value INT(11) DEFAULT 0,
            trial_interval VARCHAR(50) NULL, 		  -- hour, day, week, month, year
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
	}
}
