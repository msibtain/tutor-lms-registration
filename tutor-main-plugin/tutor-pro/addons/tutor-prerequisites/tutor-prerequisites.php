<?php
/**
 * Prerequisites Addon
 *
 * @package TutorPro/Addons
 * @subpackage Prerequisites
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defined the tutor main file
 */
define( 'TUTOR_PREREQUISITES_VERSION', '1.0.0' );
define( 'TUTOR_PREREQUISITES_FILE', __FILE__ );

add_filter( 'tutor_addons_lists_config', 'tutor_prerequisites_config' );
/**
 * Showing config for addons central lists
 *
 * @param array $config config.
 */
function tutor_prerequisites_config( $config ) {
	$new_config   = array(
		'name'        => __( 'Prerequisites', 'tutor-pro' ),
		'description' => __( 'Set course prerequisites to guide learning paths effectively.', 'tutor-pro' ),
	);
	$basic_config = (array) TUTOR_PREREQUISITES();
	$new_config   = array_merge( $new_config, $basic_config );

	$config[ plugin_basename( TUTOR_PREREQUISITES_FILE ) ] = $new_config;
	return $config;
}

if ( ! function_exists( 'TUTOR_PREREQUISITES' ) ) {
	/**
	 * Addon helper
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	//phpcs:ignore
	function TUTOR_PREREQUISITES() {
		$info = array(
			'path'         => plugin_dir_path( TUTOR_PREREQUISITES_FILE ),
			'url'          => plugin_dir_url( TUTOR_PREREQUISITES_FILE ),
			'basename'     => plugin_basename( TUTOR_PREREQUISITES_FILE ),
			'version'      => TUTOR_PREREQUISITES_VERSION,
			'nonce_action' => 'tutor_nonce_action',
			'nonce'        => '_wpnonce',
		);

		return (object) $info;
	}
}

require 'classes/Init.php';
$tutor = new TUTOR_PREREQUISITES\Init();
$tutor->run();
