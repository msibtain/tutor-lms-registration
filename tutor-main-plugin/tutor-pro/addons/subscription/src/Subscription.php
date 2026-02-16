<?php
/**
 * Subscription Main Class
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

use TUTOR\Addons;
use TUTOR\Singleton;
use TutorPro\Subscription\Controllers\CoursePlanController;
use TutorPro\Subscription\Controllers\FrontendController;
use TutorPro\Subscription\Controllers\CronController;
use TutorPro\Subscription\Controllers\EmailController;
use TutorPro\Subscription\Controllers\SubscriptionController;
use TutorPro\Subscription\Controllers\SubscriptionListController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Subscription
 *
 * @since 3.0.0
 */
final class Subscription extends Singleton {
	/**
	 * Register dependencies
	 */
	protected function __construct() {
		new AddonRegister();
		new Database();

		/**
		 * Disable the subscription addon if not monetized by tutor
		 *
		 * @since 3.0.0
		 */
		if ( self::is_enabled() && ! tutor_utils()->is_monetize_by_tutor() ) {
			Addons::update_addon_status( plugin_basename( TUTOR_SUBSCRIPTION_FILE ), 0 );
		}

		if ( ! self::is_enabled() || ! tutor_utils()->is_monetize_by_tutor() ) {
			return;
		}

		new Assets();
		new Menu();
		new Settings();
		new CoursePlanController();
		new FrontendController();
		new SubscriptionController();
		new SubscriptionListController();
		new CronController();

		add_action(
			'tutor_email_addon_loaded',
			function() {
				if ( class_exists( 'TUTOR_EMAIL\EmailNotification' ) ) {
					new EmailController();
				}
			}
		);
	}

	/**
	 * Check addon is enabled or not.
	 *
	 * @since 3.0.0.
	 *
	 * @return boolean
	 */
	public static function is_enabled() {
		$basename   = plugin_basename( TUTOR_SUBSCRIPTION_FILE );
		$is_enabled = tutor_utils()->is_addon_enabled( $basename );
		return $is_enabled;
	}

}
