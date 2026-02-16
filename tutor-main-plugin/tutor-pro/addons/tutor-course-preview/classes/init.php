<?php
/**
 * Course Preview Addon Init
 *
 * @package TutorPro/Addons
 * @subpackage CoursePreview
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

namespace TUTOR_CP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 */
class Init {
	//phpcs:disable
	public $version = TUTOR_CP_VERSION;
	public $path;
	public $url;
	public $basename;
	public $course_preview;
	//phpcs:enable

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		$addon_config = tutor_utils()->get_addon_config( TUTOR_CP()->basename );
		$is_enable    = (bool) tutor_utils()->avalue_dot( 'is_enable', $addon_config );
		if ( ! $is_enable ) {
			return;
		}

		$this->path     = plugin_dir_path( TUTOR_CP_FILE );
		$this->url      = plugin_dir_url( TUTOR_CP_FILE );
		$this->basename = plugin_basename( TUTOR_CP_FILE );

		$this->load_course_preview_addon();
	}

	/**
	 * Load Course Preview Addon
	 *
	 * @return void
	 */
	public function load_course_preview_addon() {
		spl_autoload_register( array( $this, 'loader' ) );
		$this->course_preview = new CoursePreview();
	}

	/**
	 * Class autoloader
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

			$class_name = str_replace( 'TUTOR_CP' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
			$file_name  = $this->path . $class_name . '.php';

			if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
				require_once $file_name;
			}
		}
	}

}
