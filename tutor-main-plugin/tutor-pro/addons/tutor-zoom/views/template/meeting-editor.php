<?php
/**
 * Meeting editor.
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

$meeting_host     = $this->get_users_options();
$timezone_options = require dirname( dirname( __DIR__ ) ) . '/includes/timezone.php';
?>
<div class="tutor-zoom-meeting-editor tutor-modal tutor-modal-scrollable tutor-zoom-meeting-modal-wrap<?php echo is_admin() ? ' tutor-admin-design-init' : ''; ?>" id="<?php echo esc_attr( $modal_id ); ?>">
	<div class="tutor-modal-overlay"></div>
	<div class="tutor-modal-window">
		<div class="tutor-modal-content">
			<div class="tutor-modal-header">
				<div class="tutor-modal-title">
					<?php esc_html_e( 'Zoom Meeting', 'tutor-pro' ); ?>
				</div>
				<button class="tutor-iconic-btn tutor-modal-close" data-tutor-modal-close>
					<span class="tutor-icon-times" area-hidden="true"></span>
				</button>
			</div>

			<div class="tutor-modal-body tutor-modal-container">
				<div id="tutor-zoom-meeting-modal-form">
					<input type="hidden" data-name="action" value="tutor_zoom_save_meeting">
					<input type="hidden" data-name="meeting_id" value="<?php echo esc_attr( $meeting_id ); ?>">
					<input type="hidden" data-name="topic_id" value="<?php echo esc_attr( $topic_id ); ?>">
					<input type="hidden" data-name="course_id" value="<?php echo esc_attr( $course_id ); ?>">
					<input type="hidden" data-name="click_form" value="<?php echo esc_attr( $click_form ); ?>">

					<div class="meeting-modal-form-wrap">
						<div class="tutor-mb-16">
							<label class="tutor-form-label"><?php esc_html_e( 'Meeting Name', 'tutor-pro' ); ?></label>
							<input class="tutor-form-control" type="text" data-name="meeting_title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_html_e( 'Enter Meeting Name', 'tutor-pro' ); ?>">
						</div>

						<div class="tutor-mb-16">
							<label class="tutor-form-label"><?php esc_html_e( 'Meeting Summary', 'tutor-pro' ); ?></label>
							<textarea class="tutor-form-control" type="text" data-name="meeting_summary" rows="4"><?php echo esc_textarea( $summary ); ?></textarea>
						</div>

						<div class="tutor-mb-16 tutor-row">
							<div class="tutor-col-6">
								<div class="tutor-row">
									<div class="tutor-col-12">
										<label class="tutor-form-label"><?php esc_html_e( 'Meeting Time', 'tutor-pro' ); ?></label>
									</div>
									<div class="tutor-col">
										<div class="tutor-mb-12">
											<div class="tutor-v2-date-picker tutor-v2-date-picker-fd" style="width: 100%;" data-prevent_redirect="1" data-input_name="meeting_date" data-input_value="<?php echo $start_date ? esc_attr( tutor_get_formated_date( 'd-m-Y', $start_date ) ) : ''; ?>"></div>
										</div>
										<div class="tutor-form-wrap">
											<span class="tutor-icon-clock-line tutor-form-icon tutor-form-icon-reverse"></span>
											<input type="text" data-name="meeting_time" class="tutor_zoom_timepicker tutor-form-control" value="<?php echo esc_attr( $start_time ); ?>" autocomplete="off" placeholder="08:30 PM">
										</div>
									</div>
								</div>
							</div>

							<div class="tutor-col-6">
								<div class="tutor-row">
									<div class="tutor-col-12">
										<label class="tutor-form-label"><?php esc_html_e( 'Meeting Duration', 'tutor-pro' ); ?></label>
									</div>
									<div class="tutor-col">
										<input class="tutor-form-control tutor-mb-12" type="number" min="0" data-name="meeting_duration"  value="<?php echo esc_attr( $duration ); ?>" autocomplete="off" placeholder="30"/>
										<select class="tutor-form-control" data-name="meeting_duration_unit">
											<option value="min" <?php selected( $duration_unit, 'min' ); ?>><?php esc_html_e( 'Minutes', 'tutor-pro' ); ?></option>
											<option value="hr" <?php selected( $duration_unit, 'hr' ); ?>><?php esc_html_e( 'Hours', 'tutor-pro' ); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="tutor-mb-16 tutor-row">
							<div class="tutor-col-6">
								<label class="tutor-form-label"><?php esc_html_e( 'Time Zone', 'tutor-pro' ); ?></label>
								<select data-name="meeting_timezone" class="tutor-form-select" data-searchable>
									<?php foreach ( $timezone_options as $_id => $option ) : ?>
										<option value="<?php echo esc_attr( $_id ); ?>" <?php selected( $timezone, $_id ); ?>>
											<?php echo esc_html( $option ); ?>
										</option>
									<?php endforeach ?>
								</select>
							</div>
							<div class="tutor-col-6">
								<label class="tutor-form-label"><?php esc_html_e( 'Auto Recording', 'tutor-pro' ); ?></label>
								<div class="tutor-mb-12">
									<select class="tutor-form-control" data-name="auto_recording">
										<option value="none" <?php selected( $auto_recording, 'none' ); ?>><?php esc_html_e( 'No Recordings', 'tutor-pro' ); ?></option>
										<option value="local" <?php selected( $auto_recording, 'local' ); ?>><?php esc_html_e( 'Local', 'tutor-pro' ); ?></option>
										<option value="cloud" <?php selected( $auto_recording, 'cloud' ); ?>><?php esc_html_e( 'Cloud', 'tutor-pro' ); ?></option>
									</select>
								</div>
							</div>
						</div>

						<div class="tutor-mb-16">
							<label class="tutor-form-label"><?php esc_html_e( 'Password', 'tutor-pro' ); ?></label>
							<div class="tutor-form-wrap tutor-mb-4">
								<span class="tutor-icon-lock-bold tutor-form-icon tutor-form-icon-reverse"></span>
								<input type="text" data-name="meeting_password" class="tutor-form-control" value="<?php echo esc_attr( $password ); ?>" autocomplete="off" placeholder="<?php esc_html_e( 'Create a Password', 'tutor-pro' ); ?>" />
							</div>
						</div>

						<div class="tutor-mb-16">
							<label class="tutor-form-label"><?php esc_html_e( 'Meeting Host', 'tutor-pro' ); ?></label>
							<?php
							if ( empty( $host_id ) ) {
								$host_id = is_array( $meeting_host ) ? array_keys( $meeting_host ) : array();
								$host_id = isset( $host_id[0] ) ? $host_id[0] : null;
							}

							if ( $host_id ) {
								$meeting_host_name = isset( $meeting_host[ $host_id ] ) ? $meeting_host[ $host_id ] : '';
								?>
										<input type="hidden" data-name="meeting_host" value="<?php echo esc_attr( $host_id ); ?>"/>
										<input class="tutor-form-control" type="text" disabled="disabled" value="<?php echo esc_attr( $meeting_host_name ); ?>" />
									<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>

			<div class="tutor-modal-footer">
				<button class="tutor-btn tutor-btn-outline-primary" data-tutor-modal-close>
					<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
				</button>

				<button class="tutor-btn tutor-btn-primary update_zoom_meeting_modal_btn">
					<?php $meeting_id ? esc_html_e( 'Update Meeting', 'tutor-pro' ) : esc_html_e( 'Create Meeting', 'tutor-pro' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
