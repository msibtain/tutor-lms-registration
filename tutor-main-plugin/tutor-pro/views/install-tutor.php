<?php
/**
 * Installer notice
 *
 * @package TutorPro\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Install / Active Tutor LMS', 'tutor-pro' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	$tutor_file = WP_PLUGIN_DIR . '/tutor/tutor.php';
	if ( file_exists( $tutor_file ) && ! is_plugin_active( 'tutor/tutor.php' ) ) {
		?>
		<div class="tutor-install-notice-wrap notice-warning notice" style="background: #ffffff; padding: 30px 20px; font-size: 20px;">
			<?php
			printf(
				esc_html__(
					'You must have %1$sTutor LMS%2$s Free version installed and activated on this website in order to use Tutor LMS Pro. You %3$s can activate Tutor LMS%4$s.',
					'tutor-pro'
				),
				'<a href="https://wordpress.org/plugins/tutor/" target="_blank">',
				'</a>',
				'<a href="' . esc_url( add_query_arg( array( 'action' => 'activate_tutor_free' ), admin_url() ) ) . '">',
				'</a>'
			);
			?>
		</div>
		<?php
	} elseif ( ! file_exists( $tutor_file ) ) {
		?>
		<div class="tutor-install-notice-wrap notice-warning notice" style="background: #ffffff; padding: 30px 20px; font-size: 20px;">
			<?php
			printf(
				esc_html__(
					'You must have %1$sTutor LMS%2$s Free version installed and activated on this website in order to use Tutor LMS Pro. You can %3$sInstall Tutor LMS Now%4$s',
					'tutor-pro'
				),
				'<a href="https://wordpress.org/plugins/tutor/" target="_blank">',
				'</a>',
				'<a class="install-tutor-btn" data-slug="tutor" href="' . esc_url( add_query_arg( array( 'action' => 'install_tutor_free' ), admin_url() ) ) . '">',
				'</a>'
			);
			?>
		</div>

		<div id="tutor_install_msg"></div>
		<?php
	}
	?>
</div>
