<?php
/**
 * Manage Assets.
 *
 * @package TutorPro\Auth
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.9
 */

namespace TutorPro\Auth;

use TUTOR\Input;

/**
 * Assets Class.
 *
 * @since 2.1.9
 */
class Assets {
	/**
	 * Register hooks.
	 *
	 * @since 2.1.9
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	/**
	 * Load CSS and JS
	 *
	 * @since 2.1.9
	 *
	 * @return void
	 */
	public function load_admin_scripts() {
		if ( is_admin() && 'tutor_settings' === Input::get( 'page' ) ) {
			wp_enqueue_script( 'tutor-pro-auth-settings-js', tutor_auth()->url . 'assets/js/settings.js', array( 'jquery', 'wp-i18n' ), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Load scripts
	 *
	 * @since 3.8.2
	 *
	 * @return void
	 */
	public function load_scripts() {
		if ( tutor_utils()->is_tutor_frontend_dashboard( 'account' ) ) {
			wp_enqueue_script( 'tutor-change-email', tutor_auth()->url . 'assets/js/change-email.js', array( 'tutor-core' ), TUTOR_PRO_VERSION, true );
		}
	}
}
