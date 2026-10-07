<?php
/**
 * Enrollment Addon Init
 *
 * @package TutorPro\Addons
 * @subpackage Enrollment
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

namespace TUTOR_ENROLLMENTS;

defined( 'ABSPATH' ) || exit;

use TUTOR\Input;

/**
 * Class Init
 *
 * @since 2.0.0
 */
class Init {
	/**
	 * Version
	 *
	 * @var string
	 */
	public $version = TUTOR_ENROLLMENTS_VERSION;

	/**
	 * Path
	 *
	 * @var string
	 */
	public $path;

	/**
	 * URL
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Base name
	 *
	 * @var string
	 */
	public $basename;

	// Module.
	/**
	 * Enrollment instance
	 *
	 * @var Enrollments
	 */
	private $enrollments;

	/**
	 * Enrollment list instance
	 *
	 * @var Enrollments_List
	 */
	public $enrollment_list;

	/**
	 * Enrollment expiry instance
	 *
	 * @var Enrollment_Expiry
	 */
	public $enrollment_expiry;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		$addon_config = tutor_utils()->get_addon_config( TUTOR_ENROLLMENTS()->basename );
		$is_enable    = (bool) tutor_utils()->array_get( 'is_enable', $addon_config );
		if ( ! $is_enable ) {
			return;
		}

		$this->path     = plugin_dir_path( TUTOR_ENROLLMENTS_FILE );
		$this->url      = plugin_dir_url( TUTOR_ENROLLMENTS_FILE );
		$this->basename = plugin_basename( TUTOR_ENROLLMENTS_FILE );

		add_action( 'admin_enqueue_scripts', array( $this, 'register_scritps' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_scripts' ) );

		$this->load_enrollment();
	}

	/**
	 * Register scripts
	 *
	 * @return void
	 */
	public function register_scritps() {
		if ( is_admin() && 'enrollments' === Input::get( 'page' ) ) {
			wp_enqueue_style( 'enrollment-admin-style', TUTOR_ENROLLMENTS()->url . 'assets/css/admin.css', array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-enrollment-admin-script', TUTOR_ENROLLMENTS()->url . 'assets/js/admin.js', array(), TUTOR_PRO_VERSION, true );
		}

		if ( is_admin() && 'enrollments' === Input::get( 'page' ) && 'add_new' === Input::get( 'action' ) ) {
			wp_enqueue_script( 'tutor-create-enrollment', TUTOR_ENROLLMENTS()->url . 'assets/js/create-enrollment/index.js', array( 'wp-i18n', 'wp-element' ), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Register frontend scripts
	 *
	 * @return void
	 */
	public function register_frontend_scripts() {
		wp_enqueue_style( 'enrollment-frontend-css', TUTOR_ENROLLMENTS()->url . 'assets/css/enroll.css', array(), TUTOR_PRO_VERSION );
	}

	/**
	 * Auto loader.
	 *
	 * @return void
	 */
	public function load_enrollment() {
		spl_autoload_register( array( $this, 'loader' ) );
		$this->enrollments       = new Enrollments();
		$this->enrollment_list   = new Enrollments_List();
		$this->enrollment_expiry = new Enrollment_Expiry();
	}

	/**
	 * Auto Load class and the files
	 *
	 * @param string $class_name class name.
	 *
	 * @return void
	 */
	private function loader( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			$class_name = preg_replace(
				array( '/([a-z])([A-Z])/', '/\\\/' ),
				array( '$1$2', DIRECTORY_SEPARATOR ),
				$class_name
			);

			$class_name = str_replace( 'TUTOR_ENROLLMENTS' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
			$file_name  = $this->path . $class_name . '.php';

			if ( file_exists( $file_name ) ) {
				require_once $file_name;
			}
		}
	}

}
