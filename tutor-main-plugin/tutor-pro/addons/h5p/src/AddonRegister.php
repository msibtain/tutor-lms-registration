<?php
/**
 * H5P Addon Register
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @link https://themeum.com
 * @since 3.0.0
 */


namespace TutorPro\H5P;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Tutor H5P Addon Register class
 *
 * @since 3.0.0
 */
class AddonRegister {

	/**
	 * Register hooks and dependencies
	 */
	public function __construct() {
		add_filter( 'tutor_addons_lists_config', array( $this, 'addon_config' ) );
	}

	/**
	 * Add config for Tutor H5P Addon.
	 *
	 * @since 3.0.0
	 *
	 * @param array $config array of addons.
	 * @return array
	 */
	public function addon_config( $config ) {
		$new_config = array(
			'name'           => __( 'H5P Integration', 'tutor-pro' ),
			'description'    => __( 'Integrate H5P to add interactivity and engagement to your courses.', 'tutor-pro' ),
			'depend_plugins' => array( 'h5p/h5p.php' => 'H5P' ),
		);

		$basic_config = (array) Utils::addon_config();
		$new_config   = array_merge( $new_config, $basic_config );

		$config[ plugin_basename( TUTOR_H5P_FILE ) ] = $new_config;
		return $config;
	}
}
