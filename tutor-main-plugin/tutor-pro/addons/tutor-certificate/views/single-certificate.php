<?php
/**
 * Template for displaying certificate
 *
 * @since 1.5.1
 *
 * @author Themeum
 * @link https://themeum.com
 * @package TutorPro/Addons
 * @subpackage Certificate
 */

tutor_utils()->tutor_custom_header();
?>

<div class="tutor-single-certificate-wrapper">
	<?php tutor_load_template_from_custom_path( TUTOR_CERT()->path . 'templates/certificate.php' ); ?>
</div>

<?php
tutor_utils()->tutor_custom_footer();
