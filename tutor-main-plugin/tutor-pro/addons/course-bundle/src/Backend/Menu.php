<?php
/**
 * Manage Course Bundle admin sub menu
 *
 * @package TutorPro\CourseBundle\Backend\Menu
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle\Backend;

use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menu Class
 *
 * @since 2.2.0
 */
class Menu {

	/**
	 * Register hooks
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'tutor_after_courses_admin_menu', array( $this, 'register_submenu' ) );
	}

	/**
	 * Register submenu
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function register_submenu() {
		add_submenu_page(
			'tutor-pro',
			__( 'Course Bundles', 'tutor-pro' ),
			__( 'Course Bundles', 'tutor-pro' ),
			'manage_tutor_instructor',
			'course-bundle',
			array( $this, 'bundle_list_page' )
		);
	}

	/**
	 * Bundle List
	 *
	 * @since 2.2.0
	 *
	 * @since 3.2.0
	 * Loading bundle-builder-init file
	 *
	 * @since 4.0.6
	 * Redirect to list page if the current user cannot edit the bundle.
	 *
	 * @return void
	 */
	public static function bundle_list_page() {

		$bundle_id = Utils::get_bundle_id();

		if ( ! Utils::is_bundle_editor() || ! $bundle_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=tutor' ) );
			exit;
		}

		// Redirect to list page if the current user cannot edit the bundle.
		$can_edit = get_post_type( $bundle_id ) === CourseBundle::POST_TYPE &&
					tutor_utils()->can_user_edit_course( get_current_user_id(), $bundle_id );
		if ( ! $can_edit ) {
			wp_safe_redirect( admin_url( 'admin.php?page=tutor' ) );
			exit;
		}

		echo '
			<style>
				#wpadminbar {
					z-index: 9999;
					position: fixed;
				}
				#adminmenu, 
				#adminmenuback, 
				#adminmenuwrap, 
				#wpfooter {
					display: none !important;
				}
				#wpcontent {
					margin: 0 !important;
					padding: 0 !important;
				}
				#wpbody-content {
					padding-bottom: 0px !important;
					float: none;
				}
				.notice {
					display: none;
				}
			</style>';
		include_once Utils::view_path( 'bundle-builder-init.php' );
	}
}
