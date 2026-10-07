<?php
/**
 * Manage google meet admin sub-menu
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\Admin
 */

namespace TutorPro\GoogleMeet\Admin;

use TutorPro\GoogleMeet\GoogleMeet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage admin side features
 */
class SubMenu {

	/**
	 * Register hooks & manage dependency
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter( 'tutor_admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Add sub-menu.
	 *
	 * @since 3.8.0
	 *
	 * @param array $menu menu.
	 *
	 * @return array
	 */
	public function register_menu( $menu ) {
		$menu['group_three']['google_meet'] = array(
			'parent_slug' => 'tutor',
			'page_title'  => __( 'Google Meet', 'tutor-pro' ),
			'menu_title'  => __( 'Google Meet', 'tutor-pro' ),
			'capability'  => 'manage_tutor_instructor',
			'menu_slug'   => 'google-meet',
			'callback'    => array( $this, 'render_view' ),
		);

		return $menu;
	}

	/**
	 * Render menu view
	 *
	 * @return void
	 */
	public static function render_view() {
		$plugin_data = GoogleMeet::meta_data();
		$file        = $plugin_data['views'] . 'pages/main.php';
		if ( file_exists( $file ) ) {
			tutor_load_template_from_custom_path(
				$file
			);
		} else {
			echo esc_html( $file . ' is not exists' );
		}
	}
}
