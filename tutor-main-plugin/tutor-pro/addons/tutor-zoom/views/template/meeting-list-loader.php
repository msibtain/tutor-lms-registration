<?php
/**
 * Zoom meeting expired list at frontend dashboard
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

use TUTOR\Input;
use Tutor\Models\CourseModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

// Pagination.
$per_page = tutor_utils()->get_option( 'pagination_per_page' );
$paged    = Input::get( 'paged', 1, Input::TYPE_INT );
$paged    = max( 1, $paged );

// Search Filter.
$_search    = Input::get( 'search', '' );
$_course_id = Input::get( 'course-id', '' );
$_date      = ! empty( Input::get( 'date' ) ) ? tutor_get_formated_date( 'Y-m-d', Input::get( 'date' ) ) : '';
$search     = Input::get( 'search', '' );

$orderby = 'post_title' === Input::get( 'orderby' ) ? '_meeting.post_title' : null;
$order   = 'DESC' === Input::get( 'order' ) ? 'desc' : 'asc';

// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

$user_id     = get_current_user_id();
$_filter     = isset( $_filter ) ? $_filter : 'expired';
$zoom_object = new \TUTOR_ZOOM\Zoom( false );

// Get meetings for current page listing.
$meetings = $zoom_object->get_meetings(
	$per_page,
	$paged,
	$_filter,
	array(
		'author'    => sanitize_text_field( $user_id ),
		'search'    => sanitize_text_field( $_search ),
		'course_id' => sanitize_text_field( $_course_id ),
		'date'      => sanitize_text_field( $_date ),
		'orderby'   => sanitize_text_field( $orderby ),
		'order'     => sanitize_text_field( $order ),
	)
);

// Get total meeting list count.
$total_items = count(
	$zoom_object->get_meetings(
		null,
		null,
		$_filter,
		array(
			'author'    => sanitize_text_field( $user_id ),
			'search'    => sanitize_text_field( $_search ),
			'course_id' => sanitize_text_field( $_course_id ),
			'date'      => sanitize_text_field( $_date ),
		)
	)
);

if ( is_admin() ) {
	$filters = array(
		'bulk_action' => false,
		'filters'     => array(
			array(
				'label'      => __( 'Course', 'tutor-pro' ),
				'field_type' => 'select',
				'field_name' => 'course-id',
				'options'    => CourseModel::get_course_dropdown_options(),
				'searchable' => true,
				'value'      => Input::get( 'course-id', '' ),
			),
			array(
				'label'      => __( 'Date', 'tutor-pro' ),
				'field_type' => 'date',
				'field_name' => 'date',
				'show_label' => true,
				'value'      => Input::get( 'date', '' ),
			),
		),
	);

	$filters_template = tutor()->path . 'views/elements/list-filters.php';
	tutor_load_template_from_custom_path( $filters_template, $filters );

} else {
	?>
	<form action="" method="get" id="tutor-zoom-search-filter-form" class="tutor-wp-dashboard-filter tutor-mb-24">
		<div class="tutor-wp-dashboard-filter-items tutor-d-flex tutor-flex-xl-nowrap tutor-flex-wrap tutor-justify-between">
			<div class="tutor-wp-dashboard-filter-item tutor-col">
				<label class="tutor-form-label">
					<?php esc_html_e( 'Search', 'tutor-pro' ); ?>
				</label>
				<div class="tutor-form-wrap">
					<span class="tutor-icon-search tutor-form-icon" aria-hidden="true"></span>
					<input name="search" type="search" class="tutor-form-control" value="<?php echo esc_attr( $_search ); ?>" autocomplete="off" placeholder="<?php esc_html_e( 'Search meeting', 'tutor-pro' ); ?>">
				</div>
			</div>
			<div class="tutor-wp-dashboard-filter-item tutor-col tutor-my-lg-0 tutor-my-12">
				<label class="tutor-form-label">
					<?php esc_html_e( 'Course', 'tutor-pro' ); ?>
				</label>
				<select name="course-id" class="tutor-zoom-course tutor-form-select" data-searchable style="width:100%; max-width:100%;">
					<option value=""><?php esc_html_e( 'All', 'tutor-pro' ); ?></option>
					<?php
					$courses = current_user_can( 'administrator' ) ? CourseModel::get_courses() : CourseModel::get_courses_by_instructor();
					if ( ! empty( $courses ) ) {
						foreach ( $courses as $key => $course ) {
							printf(
								'<option value="%1$s"%2$s>%3$s</option>',
								esc_attr( $course->ID ),
								selected( $_course_id, $course->ID, false ),
								esc_html( $course->post_title )
							);
						}
					}
					?>
				</select>
			</div>
			<div class="tutor-wp-dashboard-filter-item">
				<label class="tutor-form-label">
					<?php esc_html_e( 'Date', 'tutor-pro' ); ?>
				</label>
				<div class="tutor-v2-date-picker">
					<div class="tutor-form-wrap">
						<span class="tutor-form-icon tutor-form-icon-reverse">
							<span class="tutor-icon-calender-line" aria-hidden="true"></span>
						</span>
						<input class="tutor-form-control" placeholder="<?php esc_attr_e( 'Loading...', 'tutor-pro' ); ?>">
					</div>
				</div>
			</div>
		</div>
	</form>
	<?php
}
?>

<div class="<?php echo is_admin() ? 'tutor-mt-24' : ''; ?>">
	<?php
	if ( ! empty( $meetings ) ) {
		$sort_order = array( 'order' => 'asc' === $order ? 'desc' : 'asc' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$time_sort = http_build_query( array_merge( $_GET, ( 'datetime' === $orderby ? $sort_order : array() ), array( 'orderby' => 'datetime' ) ) );
		$name_sort = http_build_query( array_merge( $_GET, ( 'post_title' === $orderby ? $sort_order : array() ), array( 'orderby' => 'post_title' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$time_icon = 'datetime' === $orderby ? ( 'asc' === strtolower( $order ) ? 'tutor-icon-order-up' : 'tutor-icon-order-down' ) : 'tutor-icon-order-up';
		$name_icon = 'post_title' === $orderby ? ( 'asc' === strtolower( $order ) ? 'tutor-icon-order-up' : 'tutor-icon-order-down' ) : 'tutor-icon-order-up';

		$context = 'frontend-expired';

		echo is_admin() ? '<div class="tutor-admin-container tutor-admin-container-lg">' : '';
		include dirname( __DIR__ ) . '/template/meeting-list.php';
		echo is_admin() ? '</div>' : '';
	} else {
		$empty_state_class = is_admin() ? 'tutor-admin-container tutor-admin-container-lg' : 'tutor-mt-24';
		?>
		<div class="<?php echo esc_attr( $empty_state_class ); ?>">
			<?php tutils()->render_list_empty_state(); ?>
		</div>
		<?php
	}

	if ( count( $meetings ) ) {
		$base_url = '';

		if ( is_admin() ) {
			$base_url = str_replace( 1, '%#%', "admin.php?page=tutor_zoom&sub_page=$current_page&paged=%#%" );
		} else {
			$current_page = ( isset( $_filter ) && 'expired' === $_filter ) ? '/expired' : '';
			$url          = esc_url( tutor_utils()->get_tutor_dashboard_page_permalink() . 'zoom' . $current_page . '/?paged=%#%' );
			$base_url     = str_replace( 1, '%#%', $url );
		}

		if ( $total_items > $per_page ) {
			$pagination_data = array(
				'base'        => $base_url,
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'paged'       => $paged,
			);

			$pagination_template = tutor()->path . 'views/elements/pagination.php';
			?>
			<div class="<?php echo is_admin() ? 'tutor-admin-container tutor-admin-container-lg tutor-mt-48' : ''; ?>">
				<?php tutor_load_template_from_custom_path( $pagination_template, $pagination_data ); ?>
			</div>
			<?php
		}
	}

	do_action( 'tutor_zoom/after/meetings' );
	?>
</div>
