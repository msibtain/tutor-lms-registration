<?php
/**
 * Manage Assets.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

use TUTOR\Input;

/**
 * Assets Class.
 *
 * @since 3.0.0
 */
class Assets {
	/**
	 * Register hooks and dependency
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_script' ) );
	}

	/**
	 * Load admin assets
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function admin_script() {
		if ( 'tutor-subscriptions' === Input::get( 'page' ) ) {
			wp_enqueue_script( 'tutor-subscription-backend', Utils::asset_url( 'js/backend.js' ), array(), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Load frontend assets
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function frontend_script() {
		global $post;
		$is_subscriptions_page = tutor_utils()->is_tutor_frontend_dashboard( 'subscriptions' );
		if ( $is_subscriptions_page || ( is_single() && tutor()->course_post_type === $post->post_type ) ) {
			wp_enqueue_style( 'tutor-subscription-frontend', Utils::asset_url( 'css/frontend.css' ), array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-subscription-frontend', Utils::asset_url( 'js/frontend.js' ), array(), TUTOR_PRO_VERSION, true );
		}
	}
}
