<?php
/**
 * Prerequisites init class
 *
 * @package Tutor\Prerequisite
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TUTOR_PREREQUISITES;

defined( 'ABSPATH' ) || exit;

/**
 * Class Init
 *
 * @since 1.0.0
 */
class Init {
	/**
	 * Version
	 *
	 * @var string
	 */
	public $version = TUTOR_PREREQUISITES_VERSION;
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
	 * Basename
	 *
	 * @var string
	 */
	public $basename;

	/**
	 * Prerequisites class instance
	 *
	 * @var Prerequisites
	 */
	public $prerequisites;

	/**
	 * Init constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		$addon_config = tutor_utils()->get_addon_config( TUTOR_PREREQUISITES()->basename );
		$is_enable    = (bool) tutor_utils()->avalue_dot( 'is_enable', $addon_config );
		if ( ! $is_enable ) {
			return;
		}

		$this->path     = plugin_dir_path( TUTOR_PREREQUISITES_FILE );
		$this->url      = plugin_dir_url( TUTOR_PREREQUISITES_FILE );
		$this->basename = plugin_basename( TUTOR_PREREQUISITES_FILE );

		$this->load_tutor_prerequisites();
	}

	/**
	 * Load tutor prerequisites
	 *
	 * @since 1.0.0
	 */
	public function load_tutor_prerequisites() {
		spl_autoload_register( array( $this, 'loader' ) );
		$this->prerequisites = new Prerequisites();
	}

	/**
	 * SPL auto loader
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Class name.
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

			$class_name = str_replace( 'TUTOR_PREREQUISITES' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
			$file_name  = $this->path . $class_name . '.php';

			if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
				require_once $file_name;
			}
		}
	}

	/**
	 * Run the addon
	 *
	 * @since 1.0.0
	 */
	public function run() {
		register_activation_hook( TUTOR_PREREQUISITES_FILE, array( $this, 'tutor_activate' ) );
	}

	/**
	 * Do some task during plugin activation
	 *
	 * @since 1.0.0
	 */
	public function tutor_activate() {
		$version = get_option( 'TUTOR_PREREQUISITES_version' );
		// Save Option.
		if ( ! $version ) {
			update_option( 'TUTOR_PREREQUISITES_version', TUTOR_PREREQUISITES_VERSION );
		}
	}
}
