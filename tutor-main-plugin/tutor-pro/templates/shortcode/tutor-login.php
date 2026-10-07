<?php
/**
 * Login page ShortCode
 *
 * @package TutorPro\ShortCode
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() && ! is_admin() ) {
	tutor_load_template( 'dashboard.logged-in' );
	return;
}

add_filter(
	'tutor_after_login_redirect_url',
	function() {
		return tutor_utils()->tutor_dashboard_url();
	}
);
?>

<?php do_action( 'tutor/template/login/before/wrap' ); ?>
<div <?php tutor_post_class( 'tutor-page-wrap' ); ?>>
	<div class="tutor-template-segment tutor-login-wrap tutor-card tutor-shadow-md tutor-px-none tutor-py-9" style="max-width: 100%; width : 520px; margin: 40px auto;">
		<div class="tutor-login-form-wrapper tutor-p-8">
			<div class="tutor-h3 tutor-font-medium tutor-mb-9">
				<?php esc_html_e( 'Hi, Welcome back!', 'tutor-pro' ); ?>
			</div>
			<?php
				$login_form = trailingslashit( tutor()->path ) . 'templates/login-form.php';
				tutor_load_template_from_custom_path( $login_form, false );
			?>
			<?php do_action( 'tutor_after_login_form' ); ?>
		</div>
		<?php do_action( 'tutor_after_login_form_wrapper' ); ?>
	</div>
</div>
<?php do_action( 'tutor/template/login/after/wrap' ); ?>
