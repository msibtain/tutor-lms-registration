<?php
/**
 * Handler of CRON to check expire subscription.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Controllers;

use TutorPro\Subscription\Models\SubscriptionModel;

/**
 * CronController Class.
 *
 * @since 3.0.0
 */
class CronController {

	const CRON_EXPIRE = 'tutor_cron_subscription_expire';

	/**
	 * Subscription model.
	 *
	 * @var SubscriptionModel
	 */
	private $subscription_model;

	/**
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 *
	 * @param bool $register_hooks whether to register hooks or not.
	 */
	public function __construct( $register_hooks = true ) {
		$this->subscription_model = new SubscriptionModel();

		if ( ! $register_hooks ) {
			return;
		}

		add_action( 'init', array( $this, 'register_cron' ) );

		// Handle CRON hook.
		add_action( self::CRON_EXPIRE, array( $this, 'check_expire_subscription' ) );
	}

	/**
	 * Register cron scheduler for subscription
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_cron() {
		$expire_checker = wp_next_scheduled( self::CRON_EXPIRE );
		if ( false === $expire_checker ) {
			wp_schedule_event( time(), 'hourly', self::CRON_EXPIRE );
		}
	}

	/**
	 * Check expire subscription
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function check_expire_subscription() {
		$subscriptions = $this->subscription_model->get_all_expired_subscriptions();
		foreach ( $subscriptions as $row ) {
			$this->subscription_model->set_subscription_expired( $row );
		}
	}
}
