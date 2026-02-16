<?php
/**
 * H5P Integration
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @link https://themeum.com
 * @since 3.0.0
 */

use TutorPro\H5P\H5P;
use TutorPro\H5P\Init;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


require_once tutor_pro()->path . '/vendor/autoload.php';

define( 'TUTOR_H5P_VERSION', '1.0.0' );
define( 'TUTOR_H5P_FILE', __FILE__ );

H5P::get_instance();
