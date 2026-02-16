<?php
/**
 * Report views part
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.8
 */

use TUTOR\Input;
use TUTOR_REPORT\PageController;

$page_ctrl = new PageController();

if ( Input::has( 'course_id' ) ) {
	$page_ctrl->handle_single_course_page();
} else {
	$page_ctrl->handle_course_table_page();
}
