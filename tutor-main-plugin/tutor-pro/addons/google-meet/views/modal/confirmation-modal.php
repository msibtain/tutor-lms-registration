<?php
/**
 * Confirmation modal
 *
 * @package TutorPro\GoogleMeet\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.3
 */

?>

<div class="tutor-modal" id="tutor-google-meet-confirmation-modal" role="dialog" aria-modal="true" aria-labelledby="tutor-google-meet-confirmation-title" aria-hidden="true">
	<div class="tutor-modal-overlay"></div>
	<div class="tutor-modal-window">
		<div class="tutor-modal-content tutor-modal-content-white">
			<button type="button" class="tutor-iconic-btn tutor-modal-close-o" data-tutor-modal-close aria-label="<?php esc_attr_e( 'Close', 'tutor-pro' ); ?>">
				<span class="tutor-icon-times" aria-hidden="true"></span>
			</button>

			<div class="tutor-modal-body tutor-text-center">
				<div class="tutor-mt-48">
					<img class="tutor-d-inline-block" src="<?php echo esc_url( tutor()->url ); ?>assets/images/icon-trash.svg" alt="<?php esc_attr_e( 'Delete This?', 'tutor-pro' ); ?>" aria-hidden="true" />
				</div>

				<div id="tutor-google-meet-confirmation-title" class="tutor-fs-3 tutor-fw-medium tutor-color-black tutor-mb-12">
					<?php echo isset( $data['title'] ) ? esc_html( $data['title'] ) : esc_html__( 'Do You Want to Delete This?', 'tutor-pro' ); ?>
				</div>

				<div class="tutor-fs-6 tutor-color-muted">
					<?php echo isset( $data['message'] ) ? esc_html( $data['message'] ) : esc_html__( 'Are you sure you want to delete this permanently from the site? Please confirm your choice.', 'tutor-pro' ); ?>
				</div>

				<form id="tutor-meet-confirmation-form" class="tutor-m-0" method="POST">
					<?php tutor_nonce_field(); ?>

					<?php if ( isset( $data['action'] ) && '' !== $data['action'] ) : ?>
						<input type="hidden" name="action" value="<?php echo esc_html( $data['action'] ); ?>">
					<?php endif; ?>
						
					<div class="tutor-d-flex tutor-justify-center tutor-my-48">
						<button class="tutor-btn tutor-btn-outline-primary" data-tutor-modal-close>
							<?php esc_html_e( 'Cancel', 'tutor-pro' ); ?>
						</button>
						<button type="submit" class="tutor-btn tutor-btn-primary tutor-ml-16" data-tutor-modal-submit>
							<?php esc_html_e( "Yes, I’m sure", 'tutor-pro' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
