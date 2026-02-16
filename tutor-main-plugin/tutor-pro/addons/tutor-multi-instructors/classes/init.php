<?php
/**
 * Multi Instructor Addon Init
 *
 * @package TutorPro\Addons
 * @subpackage MultiInstructor
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.0
 */

namespace TUTOR_MT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 */
class Init {
	//phpcs:disable
	public $version = TUTOR_MT_VERSION;
	public $path;
	public $url;
	public $basename;
	public $multi_instructors;
	//phpcs:enable

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		$addon_config = tutor_utils()->get_addon_config( TUTOR_MT()->basename );
		$is_enable    = (bool) tutor_utils()->avalue_dot( 'is_enable', $addon_config );
		if ( ! $is_enable ) {
			return;
		}

		$this->path     = plugin_dir_path( TUTOR_MT_FILE );
		$this->url      = plugin_dir_url( TUTOR_MT_FILE );
		$this->basename = plugin_basename( TUTOR_MT_FILE );

		$this->load_multi_instructor_addon();
	}

	/**
	 * Load addon
	 *
	 * @return void
	 */
	public function load_multi_instructor_addon() {
		spl_autoload_register( array( $this, 'loader' ) );
		$this->multi_instructors = new MultiInstructors();
	}

	/**
	 * Auto Load class and the files
	 *
	 * @since 2.0.0
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

			$class_name = str_replace( 'TUTOR_MT' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
			$file_name  = $this->path . $class_name . '.php';

			if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
				require_once $file_name;
			}
		}
	}

}
