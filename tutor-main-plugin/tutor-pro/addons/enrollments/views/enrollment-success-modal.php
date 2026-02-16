<?php
/**
 * Show success modal for paid course enrollment
 *
 * @author themeum
 * @link https://themeum.com
 * @package TutorLMS/Templates
 *
 * @since 2.1.0
 */

$transient_key = 'tutor_manual_enrollment_success';
$modal_data    = get_transient( $transient_key );
if ( false !== $modal_data ) :
	?>
<div id="modal-course-save-feedback" class="tutor-modal tutor-is-active">
	<span class="tutor-modal-overlay"></span>
	<div class="tutor-modal-window tutor-modal-window-sm">
		<div class="tutor-modal-content tutor-modal-content-white">
			<button class="tutor-iconic-btn tutor-modal-close-o" data-tutor-modal-close>
				<span class="tutor-icon-times" area-hidden="true"></span>
			</button>

			<div class="tutor-modal-body">
				<div class="tutor-text-center  tutor-mt-24 tutor-mb-16">
					<img class="tutor-d-inline-block" src="<?php echo esc_url( TUTOR_ENROLLMENTS()->url . 'assets/images/complete.svg' ); ?>" alt="Enrollment complete">
				</div>
				<div class="tutor-d-flex tutor-flex-column tutor-gap-2">
					<div class="tutor-fs-4 tutor-fw-medium tutor-color-black">
						<?php esc_html_e( 'Complete the Enrollment!', 'tutor-pro' ); ?>
					</div>
					<div class="tutor-fs-6 tutor-color-muted">
						<?php
						echo sprintf(
							/* Translators: %s: course title */
							esc_html__( 'Manual student enrollment for %s has been initiated.', 'tutor-pro' ),
							'<strong>"' . esc_html( $modal_data->post_title ?? '' ) . '"</strong>'
						);
						?>
					</div>
					<div class="tutor-fs-6 tutor-color-muted">
						<?php echo esc_html_e( 'To grant student(s) access to the course, they must complete the payment online, or you can mark the order as "Completed".', 'tutor-pro' ); ?>
					</div>
				</div>
			</div>

			<div class="tutor-d-flex tutor-justify-end tutor-p-24 tutor-pt-0">
				<button class="tutor-btn tutor-btn-text tutor-color-subdued" data-tutor-modal-close>
					<?php esc_html_e( 'Maybe Later', 'tutor-pro' ); ?>
				</button>
				<a href="<?php echo esc_url( $modal_data->order_url ?? '' ); ?>" class="tutor-btn tutor-btn-primary">
					<?php esc_html_e( 'View Orders', 'tutor-pro' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<?php endif; ?>
<?php delete_transient( $transient_key ); ?>
