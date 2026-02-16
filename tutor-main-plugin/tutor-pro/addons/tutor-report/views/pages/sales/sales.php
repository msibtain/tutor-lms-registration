<?php
/**
 * Report sales list
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TUTOR_REPORT\PageController;

$page_ctrl = new PageController();
$page_ctrl->handle_sales_page();

