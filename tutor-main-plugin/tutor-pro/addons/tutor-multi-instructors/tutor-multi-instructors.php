<?php
/**
 * Tutor Multi Instructor Addon
 *
 * @package TutorPro\Addons
 * @subpackage MultiInstructor
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defined the tutor main file
 */
define( 'TUTOR_MT_VERSION', '1.0.0' );
define( 'TUTOR_MT_FILE', __FILE__ );

/**
 * Showing config for addons central lists
 */
add_filter( 'tutor_addons_lists_config', 'tutor_multi_instructors_config' );

/**
 * Get multi instructor configurations
 *
 * @param array $config  merge config .
 * @return array  multi instructor config
 */
function tutor_multi_instructors_config( $config ) {
	$new_config   = array(
		'name'        => __( 'Multi Instructors', 'tutor-pro' ),
		'description' => __( 'Collaborate and add multiple instructors to a course.', 'tutor-pro' ),
	);
	$basic_config = (array) TUTOR_MT();
	$new_config   = array_merge( $new_config, $basic_config );

	$config[ plugin_basename( TUTOR_MT_FILE ) ] = $new_config;
	return $config;
}

if ( ! function_exists( 'TUTOR_MT' ) ) {
	/**
	 * Get add-on meta info
	 *
	 * @return object
	 */
	//phpcs:ignore
	function TUTOR_MT() {
		$info = array(
			'path'         => plugin_dir_path( TUTOR_MT_FILE ),
			'url'          => plugin_dir_url( TUTOR_MT_FILE ),
			'basename'     => plugin_basename( TUTOR_MT_FILE ),
			'version'      => TUTOR_MT_VERSION,
			'nonce_action' => 'tutor_nonce_action',
			'nonce'        => '_wpnonce',
		);

		return (object) $info;
	}
}

require 'classes/init.php';
new TUTOR_MT\Init();
