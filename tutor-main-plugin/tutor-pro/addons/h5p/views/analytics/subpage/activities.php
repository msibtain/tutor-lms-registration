<?php
/**
 * H5P Analytics Activities Table.
 *
 * @package TutorPro\Addons
 * @subpackage H5P\Views\Analytics
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


<div class="tutor-admin-page-wrapper">
	<div class="tutor-mx-n20">
		<?php
		/**
		 * Load Templates with data.
		 */
		$filters_template = tutor()->path . 'views/elements/filters.php';
		tutor_load_template_from_custom_path( $filters_template, $filters );
		?>
	</div>

	<?php if ( is_array( $all_activity_statements ) && count( $all_activity_statements ) ) : ?>
		<div class="tutor-table-responsive tutor-mt-24">
			<table class="tutor-table">
				<thead>
					<tr>
						<th width="40%">
							<?php esc_html_e( 'Activity Name', 'tutor-pro' ); ?>
						</th>
						<th width="20%">
							<?php esc_html_e( 'Number of Statements', 'tutor-pro' ); ?>
						</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_activity_statements as $statement ) : ?>
					<tr>
						<td><?php echo esc_html( $statement->activity_name ); ?></td>
						<td><?php echo esc_html( $statement->statement_count ); ?></td>
						<td>
							<div class=" tutor-d-flex tutor-align-center tutor-gap-2 tutor-flex-grow-1 tutor-justify-end">
								<a class="tutor-btn tutor-btn-outline-primary tutor-btn-sm open-verbs-modal" role="button" data-activity-name="<?php echo esc_attr( $statement->activity_name ); ?>">
									<?php echo esc_html__( 'Verbs', 'tutor-pro' ); ?>
								</a>
								<a class="tutor-btn tutor-btn-outline-primary tutor-btn-sm open-learners-modal" role="button" data-activity-name="<?php echo esc_attr( $statement->activity_name ); ?>">
									<?php echo esc_html__( 'Learners', 'tutor-pro' ); ?>
								</a>
								<a class=" tutor-btn tutor-btn-outline-primary tutor-btn-sm open-last-ten-statements-modal" role="button" data-activity-name="<?php echo esc_attr( $statement->activity_name ); ?>">
									<?php echo esc_html__( 'Last 10 statements', 'tutor-pro' ); ?>
								</a>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
	<?php endif; ?>
	<div class="tutor-admin-page-pagination-wrapper tutor-mt-32">
		<?php
		/**
		 * Prepare pagination data & load template
		 */
		if ( $all_activities_count > $limit ) {
			$pagination_data     = array(
				'total_items' => $all_activities_count,
				'per_page'    => $limit,
				'paged'       => $paged_filter,
			);
			$pagination_template = tutor()->path . 'views/elements/pagination.php';
			tutor_load_template_from_custom_path( $pagination_template, $pagination_data );
		}
		?>
	</div>
	<div class="tutor-modal tutor-modal-scrollable<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?> h5p-verbs-statements-modal" role="dialog" aria-modal="true" aria-labelledby="h5p-verbs-statements-title" aria-hidden="true">
		<div class="tutor-modal-overlay"></div>
		<div class="tutor-modal-window">
			<div class="tutor-modal-content">
				<div class="tutor-modal-header">
					<div id="h5p-verbs-statements-title" class="tutor-modal-title">
						<?php esc_html_e( 'Verbs', 'tutor-pro' ); ?>
					</div>
					<button type="button" class="tutor-iconic-btn tutor-modal-close" data-tutor-modal-close aria-label="<?php esc_attr_e( 'Close', 'tutor-pro' ); ?>">
						<span class="tutor-icon-times" aria-hidden="true"></span>
					</button>
				</div>
			<div class="tutor-modal-body tutor-modal-container"></div>
			</div>
		</div>
	</div>
		<div class="tutor-modal tutor-modal-scrollable<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?> h5p-learners-statements-modal" role="dialog" aria-modal="true" aria-labelledby="h5p-learners-statements-title" aria-hidden="true">
		<div class="tutor-modal-overlay"></div>
		<div class="tutor-modal-window">
				<div class="tutor-modal-content">
					<div class="tutor-modal-header">
						<div id="h5p-learners-statements-title" class="tutor-modal-title">
							<?php esc_html_e( 'Learners', 'tutor-pro' ); ?>
						</div>
						<button type="button" class="tutor-iconic-btn tutor-modal-close" data-tutor-modal-close aria-label="<?php esc_attr_e( 'Close', 'tutor-pro' ); ?>">
							<span class="tutor-icon-times" aria-hidden="true"></span>
						</button>
					</div>
				<div class="tutor-modal-body tutor-modal-container"></div>
			</div>
		</div>
	</div>
	<div class="tutor-modal tutor-modal-scrollable<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?> h5p-last-ten-statements-modal" role="dialog" aria-modal="true" aria-labelledby="h5p-last-ten-statements-title" aria-hidden="true">
		<div class="tutor-modal-overlay"></div>
		<div class="tutor-modal-window">
				<div class="tutor-modal-content">
					<div class="tutor-modal-header">
						<div id="h5p-last-ten-statements-title" class="tutor-modal-title">
							<?php esc_html_e( 'Last 10 statements', 'tutor-pro' ); ?>
						</div>
						<button type="button" class="tutor-iconic-btn tutor-modal-close" data-tutor-modal-close aria-label="<?php esc_attr_e( 'Close', 'tutor-pro' ); ?>">
							<span class="tutor-icon-times" aria-hidden="true"></span>
						</button>
					</div>
				<div class="tutor-modal-body tutor-modal-container"></div>
			</div>
		</div>
	</div>

</div>
