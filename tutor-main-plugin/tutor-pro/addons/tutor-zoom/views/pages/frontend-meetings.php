<?php
/**
 * Zoom meeting active list at frontend dashboard
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$_filter = 'active';
require dirname( __DIR__ ) . '/template/meeting-list-loader.php';

do_action( 'tutor_zoom/after/meetings' );

