<?php
/**
 * Init class
 *
 * @package TutorPro\Ecommerce
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TutorPro\Ecommerce;

/**
 * Init class
 */
final class Init {

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init packages
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		new Settings();

		if ( tutor_utils()->is_monetize_by_tutor() ) {
			// Init dependent classes.
			self::load_dependencies();
		}
	}

	/**
	 * Init class
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function load_dependencies() {
		$packages = array(
			Config::class,
			PackageDownloader::class,
			Invoice::class,
		);

		foreach ( $packages as $package ) {
			new $package();
		}
	}
}

new Init();
