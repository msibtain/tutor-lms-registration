<?php
/**
 * Plugin Name: Tutor LMS Pro
 * Plugin URI: https://tutorlms.com
 * Description: Unlock advanced LMS features: content drip, live classes, certificates, course bundles, subscriptions, analytics, gradebook, and 30+ integrations.
 * Author: Themeum
 * Version: 4.1.1
 * Author URI: http://themeum.com
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 7.1
 * Text Domain: tutor-pro
 * Domain Path: /languages/
 * Requires Plugins: tutor
 *
 * @package TutorPro
 */

use TUTOR_PRO\Init as TutorProPlugin;

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Tutor Pro dependency on Tutor core
 *
 * Define Tutor core version on that Tutor Pro is dependent to run,
 * without require version pro will just show admin notice to install require core version.
 *
 * @since 2.0.0
 */
define( 'TUTOR_CORE_REQ_VERSION', '4.1.1' );
define( 'TUTOR_PRO_VERSION', '4.1.1' );
define( 'TUTOR_PRO_FILE', __FILE__ );

/**
 * Load tutor-pro text domain for translation
 *
 * @since 1.0.0
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'tutor-pro', false, basename( __DIR__ ) . '/languages' );
	}
);

( new TutorProPlugin() )->run();
