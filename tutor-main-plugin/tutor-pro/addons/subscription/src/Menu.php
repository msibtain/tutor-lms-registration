<?php
/**
 * Menu handler.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

use TUTOR\Input;
use Tutor\Models\OrderModel;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;

/**
 * Menu Class.
 *
 * @since 3.0.0
 */
class Menu {
	const PAGE_SLUG = 'tutor-subscriptions';

	/**
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 *
	 * @param bool $register_hooks whether to register hooks or not.
	 */
	public function __construct( $register_hooks = true ) {
		if ( ! $register_hooks ) {
			return;
		}

		add_action( 'tutor_after_orders_admin_menu', array( $this, 'register_admin_menu' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_submenu_page( 'tutor', __( 'Subscriptions', 'tutor-pro' ), __( 'Subscriptions', 'tutor-pro' ), 'manage_options', self::PAGE_SLUG, array( $this, 'admin_subscriptions_view' ) );
	}

	/**
	 * Show admin subscriptions list page.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function admin_subscriptions_view() {
		$current_page = Input::get( 'page' );
		$action       = Input::get( 'action' );

		if ( self::PAGE_SLUG === $current_page && 'edit' === $action ) {
			include_once Utils::view_path( 'pages/subscription-edit.php' );
			return;
		}

		include_once Utils::view_path( 'pages/subscription-list.php' );
	}
}
