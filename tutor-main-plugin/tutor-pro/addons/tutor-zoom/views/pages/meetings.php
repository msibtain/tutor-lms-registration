<?php
/**
 * Active meeting list.
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page = Input::get( 'sub_page', '' );
$_filter      = 'expired' === $current_page ? 'expired' : 'active';

require dirname( __DIR__ ) . '/template/meeting-list-loader.php';
