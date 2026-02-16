<?php
/**
 * Subscription addon
 *
 * @package TutorPro\Addons
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

use TutorPro\Subscription\Subscription;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once tutor_pro()->path . '/vendor/autoload.php';

define( 'TUTOR_SUBSCRIPTION_FILE', __FILE__ );
define( 'TUTOR_SUBSCRIPTION_DIR', plugin_dir_path( __FILE__ ) );

Subscription::get_instance();


