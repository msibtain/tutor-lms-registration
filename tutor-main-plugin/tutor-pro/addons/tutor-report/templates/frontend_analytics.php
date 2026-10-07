<?php
/**
 * Analytics template
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.8
 */

defined( 'ABSPATH' ) || exit;

use Tutor\Components\Nav;
use Tutor\Components\Constants\Size;

global $wp_query;
if ( ! current_user_can( tutor()->instructor_role ) ) {
	return;
}
$query_vars      = $wp_query->query_vars;
$report_instance = tutor_report_instance();

$current_page = isset( $query_vars['tutor_dashboard_sub_page'] ) ? $query_vars['tutor_dashboard_sub_page'] : 'overview';
$sub_pages    = $report_instance->analytics->sub_pages();
$arr          = explode( '/', $current_page );
if ( count( $arr ) ) {
	if ( array_key_exists( $arr[0], $sub_pages ) ) {
		$current_page = $arr[0];
	}
}
$tabs_data = array();

?>
<div class="tutor-analytics-wrapper">
	<?php
	/**
	 * Course details page design need to display as stand alone
	 *
	 * That is why it is not included as sub page
	 *
	 * @since 1.9.9
	 */
	if ( 'course-details' === $current_page ) {
		include_once TUTOR_REPORT()->path . 'templates/course_details.php';
		return;
	}
	if ( 'student-details' === $current_page ) {
		include_once TUTOR_REPORT()->path . 'templates/student_details.php';
		return;
	}

	$page_nav_items = array();

	foreach ( $sub_pages as $key => $sub_page ) {
		$page_nav_items[] = array(
			'type'   => 'link',
			'label'  => $sub_page['title'],
			'url'    => esc_url( $sub_page['url'] ),
			'active' => $current_page === $key,
		);
	}

	?>

	<h4 class="tutor-quiz-attempts-mobile-heading tutor-h4 tutor-mb-5">
		<?php esc_html_e( 'Analytics', 'tutor-pro' ); ?>
	</h4>
	<div class="tutor-report-menu tutor-surface-l1 tutor-p-4 tutor-border tutor-rounded-2xl">
		<?php Nav::make()->items( $page_nav_items )->size( Size::SMALL )->render(); ?>
	</div>
	<div class="tutor-analytics-sub-pages">
		<?php
		// @codingStandardsIgnoreStart
		echo $report_instance->analytics->load_sub_page( $current_page );
		// @codingStandardsIgnoreEnd
		?>
	</div>
</div>
