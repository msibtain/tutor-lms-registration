<?php
/**
 * Course Preview Addon
 *
 * @package TutorPro/Addons
 * @subpackage CoursePreview
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defined the tutor main file
 */
define( 'TUTOR_CP_VERSION', '1.0.0' );
define( 'TUTOR_CP_FILE', __FILE__ );

add_filter( 'tutor_addons_lists_config', 'tutor_course_preview_config' );

/**
 * Showing config for addons central lists
 *
 * @param array $config config.
 *
 * @return array
 */
function tutor_course_preview_config( $config ) {
	$new_config   = array(
		'name'        => __( 'Course Preview', 'tutor-pro' ),
		'description' => __( 'Offer free previews of specific lessons before enrollment.', 'tutor-pro' ),
	);

	$basic_config = (array) TUTOR_CP();
	$new_config   = array_merge( $new_config, $basic_config );

	$config[ plugin_basename( TUTOR_CP_FILE ) ] = $new_config;
	return $config;
}

if ( ! function_exists( 'TUTOR_CP' ) ) {
	/**
	 * Addon helper.
	 *
	 * @return object
	 */
	//phpcs:ignore
	function TUTOR_CP() {
		$info = array(
			'path'         => plugin_dir_path( TUTOR_CP_FILE ),
			'url'          => plugin_dir_url( TUTOR_CP_FILE ),
			'basename'     => plugin_basename( TUTOR_CP_FILE ),
			'version'      => TUTOR_CP_VERSION,
			'nonce_action' => 'tutor_nonce_action',
			'nonce'        => '_wpnonce',
		);

		return (object) $info;
	}
}

require 'classes/init.php';
new TUTOR_CP\Init();
