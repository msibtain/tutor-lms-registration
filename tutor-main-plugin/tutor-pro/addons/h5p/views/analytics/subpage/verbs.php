<?php
/**
 * H5P Analytics Verbs Table.
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

	<?php if ( is_array( $all_verb_statements ) && count( $all_verb_statements ) ) : ?>
		<div class="tutor-table-responsive tutor-mt-24">
			<table class="tutor-table">
				<thead>
					<tr>
						<th >
							<?php esc_html_e( 'Verbs', 'tutor-pro' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Verb ID', 'tutor-pro' ); ?>
						</th>
						<th >
							<?php esc_html_e( 'Number of Statements', 'tutor-pro' ); ?>
						</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_verb_statements as $statement ) : ?>
					<tr>
						<td><?php echo esc_html( $statement->verb ); ?></td>
						<td><a href="<?php echo esc_html( $statement->verb_id ); ?>" target="_blank" ><?php echo esc_html( $statement->verb_id ); ?></a></td>
						<td><?php echo esc_html( $statement->statement_count ); ?></td>
						<td>
							<div class=" tutor-d-flex tutor-align-center tutor-justify-end tutor-gap-2 tutor-flex-grow-1">
								<a class=" tutor-btn tutor-btn-outline-primary tutor-btn-sm open-activities-modal" role="button" data-verb="<?php echo esc_attr( $statement->verb ); ?>">
									<?php echo esc_html__( 'Activities', 'tutor-pro' ); ?>
								</a>
								<a class=" tutor-btn tutor-btn-outline-primary tutor-btn-sm open-learners-modal" role="button" data-verb="<?php echo esc_attr( $statement->verb ); ?>">
									<?php echo esc_html__( 'Learners', 'tutor-pro' ); ?>
								</a>
								<a class=" tutor-btn tutor-btn-outline-primary tutor-btn-sm open-last-ten-statements-modal" role="button" data-verb="<?php echo esc_attr( $statement->verb ); ?>">
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
		if ( $all_verb_count > $limit ) {
			$pagination_data     = array(
				'total_items' => $all_verb_count,
				'per_page'    => $limit,
				'paged'       => $paged_filter,
			);
			$pagination_template = tutor()->path . 'views/elements/pagination.php';
			tutor_load_template_from_custom_path( $pagination_template, $pagination_data );
		}
		?>
	</div>
	<div class="tutor-modal tutor-modal-scrollable<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?> h5p-activities-statements-modal">
		<div class="tutor-modal-overlay"></div>
		<div class="tutor-modal-window">
				<div class="tutor-modal-content">
					<div class="tutor-modal-header">
						<div class="tutor-modal-title">
				<?php esc_html_e( 'Activities', 'tutor' ); ?>
					</div>
					<button class="tutor-iconic-btn tutor-modal-close" data-tutor-modal-close>
						<span class="tutor-icon-times" area-hidden="true"></span>
					</button>
				</div>
				<div class="tutor-modal-body tutor-modal-container"></div>
			</div>
		</div>
	</div>
	<div class="tutor-modal tutor-modal-scrollable<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?> h5p-learners-statements-modal">
		<div class="tutor-modal-overlay"></div>
		<div class="tutor-modal-window">
				<div class="tutor-modal-content">
					<div class="tutor-modal-header">
						<div class="tutor-modal-title">
				<?php esc_html_e( 'Learners', 'tutor' ); ?>
					</div>
					<button class="tutor-iconic-btn tutor-modal-close" data-tutor-modal-close>
						<span class="tutor-icon-times" area-hidden="true"></span>
					</button>
				</div>
				<div class="tutor-modal-body tutor-modal-container"></div>
			</div>
		</div>
	</div>
	<div class="tutor-modal tutor-modal-scrollable<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?> h5p-last-ten-statements-modal">
		<div class="tutor-modal-overlay"></div>
		<div class="tutor-modal-window">
				<div class="tutor-modal-content">
					<div class="tutor-modal-header">
						<div class="tutor-modal-title">
				<?php esc_html_e( 'Last 10 statements', 'tutor' ); ?>
					</div>
					<button class="tutor-iconic-btn tutor-modal-close" data-tutor-modal-close>
						<span class="tutor-icon-times" area-hidden="true"></span>
					</button>
				</div>
				<div class="tutor-modal-body tutor-modal-container"></div>
			</div>
		</div>
	</div>

</div>