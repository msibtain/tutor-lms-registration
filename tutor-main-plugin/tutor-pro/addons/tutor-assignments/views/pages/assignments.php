<?php
/**
 * Assignment List Page
 *
 * @author themeum
 * @link https://themeum.com
 * @package TutorPro\Assignment
 * @since 1.8.0
 */

use TUTOR\Input;
use TUTOR_ASSIGNMENTS\Assignments_List;
use Tutor\Models\CourseModel;

$assignments = new Assignments_List();

$course_id = Input::get( 'course-id', '' );
$user_id   = current_user_can( 'administrator' ) ? 0 : get_current_user_id();
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$order = Input::get( 'order', 'DESC' );
$date  = ! empty( Input::get( 'date' ) ) ? tutor_get_formated_date( 'Y-m-d', Input::get( 'date' ) ) : '';
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$search = Input::get( 'search', '' );

/**
 * Determine active tab
 */
$active_tab = esc_html( Input::get( 'data', 'all' ) );

/**
 * Pagination data
 */
$paged    = Input::get( 'paged', 1, Input::TYPE_INT );
$per_page = tutor_utils()->get_option( 'pagination_per_page' );
$offset   = ( $per_page * max( 1, $paged ) ) - $per_page;

/**
 * Navbar data to make nav menu
 */
$assignments_list          = array();
$submitted_assignment_list = Assignments_List::get_submitted_assignment_list( $active_tab, $course_id, $date, $date, $search, $offset, $per_page, $order, $user_id );

$assignments_list = $submitted_assignment_list->results;

$total = $submitted_assignment_list->total_count;


$navbar_data = array(
	'page_title' => $assignments->page_title,
);

/**
 * Bulk action & filters
 */
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
			'label'      => __( 'Status', 'tutor-pro' ),
			'field_type' => 'select',
			'field_name' => 'data',
			'options'    => $assignments->tabs_key_value( $course_id, $date, $date, $search ),
			'searchable' => false,
			'value'      => Input::get( 'data', '' ),
		),
		array(
			'label'      => __( 'Start Date', 'tutor-pro' ),
			'field_type' => 'date',
			'field_name' => 'date',
			'show_label' => true,
			'value'      => Input::get( 'date', '' ),
		),
	),
);

?>

<div class="tutor-admin-wrap">
	<?php
		/**
		 * Load Templates with data.
		 */
		$navbar_template  = tutor()->path . 'views/elements/list-navbar.php';
		$filters_template = tutor()->path . 'views/elements/list-filters.php';
		tutor_load_template_from_custom_path( $navbar_template, $navbar_data );
		tutor_load_template_from_custom_path( $filters_template, $filters );
	?>

	<div class="tutor-admin-container tutor-admin-container-lg tutor-mt-16">
		<?php if ( is_array( $assignments_list ) && count( $assignments_list ) ) : ?>
			<div class="tutor-table-responsive tutor-dashboard-list-table">
				<table class="tutor-table tutor-table-middle">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Assignment Name', 'tutor-pro' ); ?></th>
							<th class="tutor-table-rows-sorting">
								<?php esc_html_e( 'Student', 'tutor-pro' ); ?>
								<span class="tutor-icon-ordering-a-z a-to-z-sort-icon"></span>
							</th>
							<th><?php esc_html_e( 'Total Points', 'tutor-pro' ); ?></th>
							<th><?php esc_html_e( 'Passing Points', 'tutor-pro' ); ?></th>
							<th><?php esc_html_e( 'Duration', 'tutor-pro' ); ?></th>
							<th><?php esc_html_e( 'Date', 'tutor-pro' ); ?></th>
							<th></th>
						</tr>
					</thead>

					<tbody>
						<?php foreach ( $assignments_list as $index => $list ) : ?>
							<?php $assignment_row_id = 'tutor-assignment-row-' . $index; ?>
							<tr id="<?php echo esc_attr( $assignment_row_id ); ?>">
								<td><?php $assignments->column_title( $list ); ?></td>
								<td><?php $assignments->column_student( $list ); ?></td>
								<td><?php $assignments->column_mark( $list ); ?></td>
								<td><?php $assignments->column_passing_mark( $list ); ?></td>
								<td><?php $assignments->column_duration( $list ); ?></td>
								<td><?php $assignments->column_date( $list ); ?></td>
								<td>
									<div class="tutor-d-flex tutor-align-center tutor-gap-1">
										<?php $assignments->column_action_evaluate( $list, $list->comment_post_ID ); ?>
										<?php $assignments->column_action_delete( $list ); ?>
									</div>
									<?php
										// Modal Assignment Delete.
										tutor_load_template(
											'modal.confirm',
											array(
												'id'      => "assignment-$list->comment_ID",
												'image'   => 'icon-trash.svg',
												'title'   => __( 'Do You Want to Delete This Assignment?', 'tutor-pro' ),
												'content' => __( 'Are you sure you want to delete this assignment permanently from the course? Please confirm your choice.', 'tutor-pro' ),
												'yes'     => array(
													'text' => __( 'Yes, Delete This', 'tutor-pro' ),
													'class' => 'tutor-list-ajax-action',
													'attr' => array( 'data-request_data=\'{"action":"delete_tutor_course_assignment_submission", "assignment_id":"' . $list->comment_ID . '"}\'', 'data-delete_element_id="' . $assignment_row_id . '"' ),
												),
											)
										);
									?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if ( count( $assignments_list ) === 0 ) : ?>
							<tr>
								<td colspan="100%">
									<div class="td-empty-state">
										<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
									</div>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<?php tutils()->render_list_empty_state(); ?>
		<?php endif; ?>

		<div class="tutor-admin-page-pagination-wrapper tutor-mt-32">
			<?php
				/**
				 * Prepare pagination data & load template
				 */
			if ( $total > $per_page ) {
				$pagination_data     = array(
					'total_items' => $total,
					'per_page'    => $per_page,
					'paged'       => $paged,
				);
				$pagination_template = tutor()->path . 'views/elements/pagination.php';
				tutor_load_template_from_custom_path( $pagination_template, $pagination_data );
			}
			?>
		</div>
	</div>
</div>
