<?php
/**
 * H5P Analytics Lesson Report Table.
 *
 * @package TutorPro\Addons
 * @subpackage H5P\Views\Analytics
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

use TutorPro\H5P\Utils;

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

	<?php if ( is_array( $all_lesson_statements ) && count( $all_lesson_statements ) ) : ?>
		<div class="tutor-table-responsive tutor-mt-24">
			<table class="tutor-table tutor-table-middle">
				<thead>
					<tr>
						<th width="12%">
							<?php esc_html_e( 'Course', 'tutor-pro' ); ?>
						</th>
						<th width="12%">
							<?php esc_html_e( 'Lesson', 'tutor-pro' ); ?>
						</th>
						<th width="12%">
							<?php esc_html_e( 'Learner', 'tutor-pro' ); ?>
						</th>
						<th width="12%">
							<?php esc_html_e( 'Verb', 'tutor-pro' ); ?>
						</th>
						<th width="20%">
							<?php esc_html_e( 'Activity', 'tutor-pro' ); ?>
						</th>
						<th width="12%">
							<?php esc_html_e( 'Result', 'tutor-pro' ); ?>
						</th>
						<th width="12%">
							<?php esc_html_e( 'Score', 'tutor-pro' ); ?>
						</th>
						<th width="12%">
							<?php esc_html_e( 'Percentage', 'tutor-pro' ); ?>
						</th>
						<th width="10%">
							<?php esc_html_e( 'Min', 'tutor-pro' ); ?>
						</th>
						<th width="10%">
							<?php esc_html_e( 'Max', 'tutor-pro' ); ?>
						</th>
						<th width="20%">
							<?php esc_html_e( 'Time Spent', 'tutor-pro' ); ?>
						</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $all_lesson_statements as $lesson_statement ) {
						$course     = get_post( $lesson_statement->course_id );
						$lesson     = get_post( $lesson_statement->lesson_id );
						$user       = tutor_utils()->get_tutor_user( $lesson_statement->user_id );
						$verb       = $lesson_statement->verb;
						$activity   = $lesson_statement->activity_name;
						$result     = $lesson_statement->result_success;
						$score      = (int) $lesson_statement->result_raw_score;
						$max        = (int) $lesson_statement->result_max_score;
						$min        = (int) $lesson_statement->result_min_score;
						$percentage = $max > 0 ? (float) round( ( $score / $max ) * 100 ) : 0;
						$duration   = Utils::convert_iso8601_to_string( $lesson_statement->result_duration );
						$completed  = $lesson_statement->result_completion;
						?>
					<tr>
						<td>
							<div class="tutor-fs-6 tutor-fw-normal">
								<?php
								if ( isset( $course ) && tutor()->course_post_type === $course->post_type ) {
									echo esc_html( $course->post_title );
								}
								?>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal">
								<?php
								if ( isset( $lesson ) && tutor()->lesson_post_type === $lesson->post_type ) {
									echo esc_html( $lesson->post_title );
								}
								?>
							</div>
						</td>
						<td>
							<div class="tutor-d-flex tutor-align-center">
								<?php
								echo wp_kses(
									tutor_utils()->get_tutor_avatar( $user, 'sm' ),
									tutor_utils()->allowed_avatar_tags()
								)
								?>
								<div class="tutor-ml-12">
									<a target="_blank" class="tutor-fs-7 tutor-table-link" href="<?php echo esc_url( tutor_utils()->profile_url( $user, false ) ); ?>">
										<?php echo esc_html( $user ? $user->display_name : '' ); ?>
									</a>
								</div>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal">
								<?php
									echo esc_html( $verb );
								?>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal">
								<?php
									echo esc_html( $activity );
								?>
							</div>
						</td>
						<td>
								<?php
								if ( isset( $result ) || $max > 0 ) {
									if ( $result || $score === $max ) {
										?>
											<div class="tutor-d-flex tutor-align-center tutor-gap-2">
												<div>
													<span class="tutor-icon-trophy-o tutor-fs-4">
													</span>
												</div>
												<div class=" tutor-fw-normal">
												<?php esc_html_e( 'Passed', 'tutor-pro' ); ?> 
												</div>
											</div>
										<?php
									} else {
										?>
											<div class="tutor-d-flex tutor-align-center tutor-gap-2">
												<div>
													<span class="tutor-icon-circle-times-bold tutor-fs-4">
													</span>
												</div>
												<div class="tutor-fw-normal">
												<?php esc_html_e( 'Failed', 'tutor-pro' ); ?> 
												</div>
											</div>
										<?php
									}
								} elseif ( $completed ) {
									?>
										<div class="tutor-fs-6 tutor-fw-normal">
										<?php esc_html_e( 'Completed', 'tutor-pro' ); ?> 
										</div>
										<?php
								} else {
									echo '';
								}
								?>
						</td>
						<td>
							<div class=" tutor-fw-normal">
								<?php echo isset( $result ) || isset( $completed ) || $max > 0 ? esc_html( $score ) : ''; ?>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal">
								<?php echo isset( $result ) || isset( $completed ) || $max > 0 ? esc_html( $percentage . '%' ) : ''; ?>
							</div>
						</td>
						<td>
							<div class="  tutor-fw-normal">
								<?php echo isset( $result ) || isset( $completed ) || $max > 0 ? esc_html( $min ) : ''; ?>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal">
								<?php echo isset( $result ) || isset( $completed ) || $max > 0 ? esc_html( $max ) : ''; ?>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal">
								<?php echo isset( $result ) || isset( $completed ) || $max > 0 ? esc_html( $duration ) : ''; ?>
							</div>
						</td>
						<td>
							<?php if ( isset( $lesson_statement->result_response ) ) : ?>
								<a class=" tutor-btn tutor-btn-sm tutor-btn-outline-primary open-tutor-h5p-statement-result-modal-btn" 
									data-statement-id="<?php echo esc_attr( $lesson_statement->statement_id ); ?>" 
									data-content-id="<?php echo esc_attr( $lesson_statement->content_id ); ?>" 
									data-is-lesson="true"
								>
								<?php esc_html_e( 'View', 'tutor-pro' ); ?>
								</a>
							<?php endif; ?>
						</td>
						
					</tr>
							<?php
					}
					?>
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
		if ( $all_lesson_statements_count > $limit ) {
			$pagination_data     = array(
				'total_items' => $all_lesson_statements_count,
				'per_page'    => $limit,
				'paged'       => $paged_filter,
			);
			$pagination_template = tutor()->path . 'views/elements/pagination.php';
			tutor_load_template_from_custom_path( $pagination_template, $pagination_data );
		}
		?>
	</div>
	<div class="tutor-modal tutor-modal-scrollable<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?> h5p-statement-result-modal">
		<div class="tutor-modal-overlay"></div>
		<div class="tutor-modal-window">
				<div class="tutor-modal-content">
					<div class="tutor-modal-header">
						<div class="tutor-modal-title">
				<?php esc_html_e( 'H5P Lesson Result', 'tutor' ); ?>
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