<?php
/**
 * Course Enrolled Student List
 *
 * @package TutorPro\Addons
 * @subpackage Report
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $data['student_list'], $data['course_id'], $data['pagination'], $data['details_url'] ) ) {
	return;
}

$data            = (object) $data;
$certificate     = tutor_utils()->is_addon_enabled( 'tutor-certificate' ) ? ( new TUTOR_CERT\Certificate( true ) ) : null;
$user_ids        = $data->student_list ? array_map( fn( $student ) => $student->ID, $data->student_list ) : array();
$has_certificate = isset( $certificate ) && $certificate->has_course_certificate( $data->course_id ?? 0, $user_ids );
?>

<div id="tutor-course-details-student-list" class="tutor-mb-48">
	<div class="tutor-fs-5 tutor-fw-medium tutor-color-black tutor-mb-24">
		<?php esc_html_e( 'Students', 'tutor-pro' ); ?>
	</div>
	<?php if ( is_array( $data->student_list ) && count( $data->student_list ) ) : ?>
		<div class="tutor-course-details-student-list-table tutor-mb-48">
			<div class="tutor-table-responsive">
				<table class="tutor-table tutor-table-middle table-students">
					<thead>
						<tr>
							<th width="20%">
								<?php esc_html_e( 'Student', 'tutor-pro' ); ?>
							</th>
							<th width="20%">
								<?php esc_html_e( 'Enroll Date', 'tutor-pro' ); ?>
							</th>
							<th width="10%">
								<?php esc_html_e( 'Lesson', 'tutor-pro' ); ?>
							</th>
							<th width="10%">
								<?php esc_html_e( 'Assignment', 'tutor-pro' ); ?>
							</th>
							<th width="30%">
								<?php esc_html_e( 'Progress', 'tutor-pro' ); ?>
							</th>
							<?php if ( $has_certificate ) : ?>
							<th>
								<?php esc_html_e( 'Certificate', 'tutor-pro' ); ?>
								<div class="tooltip-wrap">
									<i class="tutor-icon-circle-info-o tutor-color-muted tutor-ml-4 tutor-fs-7"></i>
									<span class="tooltip-txt tooltip-left"><?php esc_html_e( 'Certificate Issued', 'tutor-pro' ); ?></span>
								</div>
							</th>
							<?php endif; ?>
							<th></th>
						</tr>
					</thead>

					<tbody>
						<?php foreach ( $user_ids as $user_id ) : ?>
							<?php
							$enrolled_data = tutor_utils()->get_enrolled_data( $user_id, $data->course_id );
							$user_info     = get_userdata( $user_id );
							if ( ! $user_info ) {
								continue;
							}
							?>
							<tr>
								<td>
									<div class="tutor-d-flex tutor-align-center tutor-gap-2">
										<?php echo wp_kses( tutor_utils()->get_tutor_avatar( $user_id ), tutor_utils()->allowed_avatar_tags() ); ?>
										<div>
											<div class="tutor-d-flex">
												<?php echo esc_html( $user_info->display_name ); ?>
												<a href="<?php echo esc_url( tutor_utils()->profile_url( $user_id, true ) ); ?>" class="tutor-iconic-btn tutor-ml-4">
													<span class="tutor-icon-external-link"></span>
												</a>
											</div>
											<div class="tutor-fs-7 tutor-fw-normal tutor-color-muted">
												<?php echo esc_html( $user_info->user_email ); ?>
											</div>
										</div>
									</div>
								</td>
								<td>
									<?php echo esc_html( tutor_i18n_get_formated_date( tutor_utils()->get_local_time_from_unix( $enrolled_data->post_date_gmt ), get_option( 'date_format' ) ) ); ?>
								</td>
								<td>
									<?php echo esc_html( tutor_utils()->get_completed_lesson_count_by_course( $data->course_id, $user_id ) ); ?></span>/<span class="tutor-color-muted"><?php echo esc_html( tutor_utils()->get_lesson_count_by_course( $data->course_id ) ); ?></span>
								</td>
								<td>
									<?php echo esc_html( tutor_utils()->count_completed_assignment( $data->course_id, $user_id ) ); ?></span>/<span class="tutor-color-muted"><?php echo esc_html( tutor_utils()->get_assignments_by_course( $data->course_id )->count ); ?></span>
								</td>
								<td>
									<?php $percentage = tutor_utils()->get_course_completed_percent( $data->course_id, $user_id ); ?>
									<div class="tutor-d-flex tutor-align-center">
										<div class="tutor-progress-bar" style="min-width: 50px; --tutor-progress-value:<?php echo esc_attr( $percentage ); ?>%;">
											<div class="tutor-progress-value"></div>
										</div>
										<div class="tutor-fs-7 tutor-ml-12">
											<?php echo esc_html( $percentage ); ?>%
										</div>
									</div>
								</td>
								<?php if ( $has_certificate ) : ?>
								<td>
									<?php if ( get_user_meta( $user_id, 'tutor_certificate_generated', true ) ) : ?>
										<?php do_action( 'tutor_report_course_certificate', $data->course_id ?? 0, $user_id ?? 0 ); ?>
									<?php endif; ?>
								</td>
								<?php endif; ?>
								<td>
									<div class="tutor-text-right">
										<a href="<?php echo esc_url( $data->details_url . $user_id ); ?>" class="tutor-btn tutor-btn-primary" target="_blank">
											<?php esc_html_e( 'Details', 'tutor-pro' ); ?>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php else : ?>
		<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
	<?php endif; ?>

	<?php
		tutor_load_template_from_custom_path( tutor()->path . 'views/elements/pagination.php', $data->pagination );
	?>
</div>
