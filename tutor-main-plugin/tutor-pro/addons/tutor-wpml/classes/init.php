<?php
/**
 * Class WPML init
 *
 * @package Tutor
 * @subpackage Tutor_WPML
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.1
 */

namespace TUTOR_WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class init
 */
class init {

	/**
	 * WPML version.
	 *
	 * @var string
	 * @since 1.9.1
	 */
	public $version = TUTOR_WPML_VERSION;

	/**
	 * Path to the plugin directory.
	 *
	 * @var string
	 * @since 1.9.1
	 */
	public $path;

	/**
	 * URL of the plugin directory.
	 *
	 * @var string
	 * @since 1.9.1
	 */
	public $url;

	/**
	 * Basename of the plugin.
	 *
	 * @var string
	 * @since 1.9.1
	 */
	public $basename;

	/**
	 * Module instance for WPML duplicator.
	 *
	 * @var Wpml_Translation
	 * @since 1.9.1
	 */
	private $wpml_duplicator;

	/**
	 * Class constructor.
	 *
	 * @since 1.9.1
	 */
	public function __construct() {
		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		$addon_config = tutor_utils()->get_addon_config( TUTOR_WPML()->basename );
		$is_enable    = (bool) tutor_utils()->avalue_dot( 'is_enable', $addon_config );
		if ( ! $is_enable ) {
			return;
		}

		$this->path     = plugin_dir_path( TUTOR_WPML_FILE );
		$this->url      = plugin_dir_url( TUTOR_WPML_FILE );
		$this->basename = plugin_basename( TUTOR_WPML_FILE );

		$this->load_tutor_wpml();
	}

	/**
	 * Tutor LMS autoload.
	 *
	 * @since 1.9.1
	 */
	public function load_tutor_wpml() {
		// SPL Auto loader.
		spl_autoload_register( array( $this, 'loader' ) );
		$this->wpml_duplicator = new Wpml_Translation();
	}

	/**
	 * Auto Load class and the files.
	 *
	 * @param string $class_name Class name to load.
	 * @since 1.9.1
	 */
	private function loader( $class_name ) {
		if ( class_exists( $class_name ) ) {
			return;
		}

		$class_name = preg_replace(
			array( '/([a-z])([A-Z])/', '/\\\/' ),
			array( '$1$2', DIRECTORY_SEPARATOR ),
			$class_name
		);

		// Make file path.
		$class_name = str_replace( 'TUTOR_WPML' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
		$file_name = $this->path . $class_name . '.php';

		// Load class.
		if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
			require_once $file_name;
		}
	}

	/**
	 * Register tutor addon.
	 *
	 * Run the TUTOR right now.
	 *
	 * @since 1.9.1
	 */
	public function run() {
		register_activation_hook( TUTOR_WPML_FILE, array( $this, 'tutor_activate' ) );
	}

	/**
	 * Compare Tutor wpml version with current Tutor wpml version.
	 *
	 * Update Tutor WPML version.
	 *
	 * @since 1.9.1
	 */
	public function tutor_activate() {
		$version = get_option( 'TUTOR_WPML_VERSION' );
		if ( version_compare( $version, TUTOR_WPML_VERSION, '<' ) ) {
			update_option( 'TUTOR_WPML_VERSION', TUTOR_WPML_VERSION );
		}
	}
}
