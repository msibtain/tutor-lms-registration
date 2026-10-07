<?php
/**
 * Paid Membership Pro Integration Addon
 *
 * @package TutorPro\Addons
 * @subpackage PmPro
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.3.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defined the tutor main file
 */
define( 'TUTOR_PMPRO_VERSION', '1.0.0' );
define( 'TUTOR_PMPRO_FILE', __FILE__ );

add_filter( 'tutor_addons_lists_config', 'tutor_pmpro_config' );

/**
 * Showing config for addons central lists
 *
 * @since 1.3.5
 *
 * @param array $config config.
 *
 * @return array
 */
function tutor_pmpro_config( $config ) {
	$new_config = array(
		'name'           => __( 'Paid Memberships Pro', 'tutor-pro' ),
		'description'    => __( 'Boost revenue by selling course memberships.', 'tutor-pro' ),
		'depend_plugins' => array( 'paid-memberships-pro/paid-memberships-pro.php' => 'Paid Memberships Pro' ),
	);

	$basic_config = (array) TUTOR_PMPRO();
	$new_config   = array_merge( $new_config, $basic_config );

	$config[ plugin_basename( TUTOR_PMPRO_FILE ) ] = $new_config;
	return $config;
}

if ( ! function_exists( 'TUTOR_PMPRO' ) ) {
	/**
	 * Addon helper
	 *
	 * @since 1.3.5
	 *
	 * @return object
	 */
	//phpcs:ignore
	function TUTOR_PMPRO() {
		$info = array(
			'path'                => plugin_dir_path( TUTOR_PMPRO_FILE ),
			'url'                 => plugin_dir_url( TUTOR_PMPRO_FILE ),
			'basename'            => plugin_basename( TUTOR_PMPRO_FILE ),
			'version'             => TUTOR_PMPRO_VERSION,
			'nonce_action'        => 'tutor_nonce_action',
			'nonce'               => '_wpnonce',
			'required_pro_plugin' => true,
		);

		return (object) $info;
	}
}

require 'classes/Init.php';
new \TUTOR_PMPRO\Init();
