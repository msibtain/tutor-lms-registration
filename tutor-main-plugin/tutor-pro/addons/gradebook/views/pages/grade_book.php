<?php
/**
 * Helper functions
 *
 * @package TutorPro\Addons
 * @subpackage Gradebook\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.4.2
 */

use TUTOR\Input;

$_page    = 'overview.php';
$sub_page = Input::get( 'sub_page' );

if ( $sub_page ) {
	$_page = $sub_page . '.php';
}

$view_path = TUTOR_GB()->path . "views/pages/{$_page}";
if ( file_exists( $view_path ) ) {
	require $view_path;
}