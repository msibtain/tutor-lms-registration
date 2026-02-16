<?php
/**
 * WC Subscription Addon Init
 *
 * @package TutorPro\Addons
 * @subpackage WCSubscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.3.5
 */

namespace TUTOR_WCS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 */
class Init {
	//phpcs:disable
	public $version = TUTOR_WCS_VERSION;
	public $path;
	public $url;
	public $basename;
	private $paid_memberships_pro;
	//phpcs:enable

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		$addon_config = tutor_utils()->get_addon_config( TUTOR_WCS()->basename );

		$monetize_by = tutor_utils()->get_option( 'monetize_by' );
		$is_enable   = (bool) tutor_utils()->array_get( 'is_enable', $addon_config );
		$has_wcs     = tutor_utils()->has_wcs();

		if ( ! $is_enable || ! $has_wcs || 'wc' !== $monetize_by ) {
			return;
		}

		$this->path     = plugin_dir_path( TUTOR_WCS_FILE );
		$this->url      = plugin_dir_url( TUTOR_WCS_FILE );
		$this->basename = plugin_basename( TUTOR_WCS_FILE );

		$this->load_addon();
	}

	/**
	 * Load addon
	 *
	 * @return void
	 */
	public function load_addon() {
		spl_autoload_register( array( $this, 'loader' ) );
		$this->paid_memberships_pro = new WCSubscriptions();
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

			$class_name = str_replace( 'TUTOR_WCS' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
			$file_name  = $this->path . $class_name . '.php';

			if ( file_exists( $file_name ) ) {
				require_once $file_name;
			}
		}
	}

}
