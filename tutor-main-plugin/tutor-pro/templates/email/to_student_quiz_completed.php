<?php
/**
 * E-mail template for student when quiz completed.
 *
 * @package TutorPro
 * @subpackage Templates\Email
 *
 * @since 2.0.0
 */

?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
	<?php require TUTOR()->path . 'templates/email/email_styles.php'; ?>
</head>

<body>
	<div class="tutor-email-body">
		<div class="tutor-email-wrapper" style="background-color: #fff;">
		<?php require TUTOR_PRO()->path . 'templates/email/email_header.php'; ?>
		<div class="tutor-email-content">
			<div style="margin-bottom: 30px">
				<h6 data-source="email-heading" class="tutor-email-heading">{email_heading}</h6>
			</div>

			<div class="tutor-greetings-content">
				<p class="tutor-email-greetings">
					<?php
					/* translators: %s: student name placeholder */
					echo esc_html( sprintf( __( 'Hi %s,', 'tutor-pro' ), '{user_name}' ) );
					?>
				</p>
				<div class="email-user-content" data-source="email-additional-message">{email_message}</div>
			</div>

			<div class="tutor-email-buttons">
				<a target="_blank" class="tutor-email-button" href="{attempt_url}"><?php esc_html_e( 'View Quiz Results', 'tutor-pro' ); ?></a>
			</div>

			</div>
		</div>
	</div>
</body>
</html>
