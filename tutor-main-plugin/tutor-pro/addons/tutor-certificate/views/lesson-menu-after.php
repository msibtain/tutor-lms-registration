<?php
/**
 * View certificate button
 *
 * @package TutorPro\Addon
 * @subpackage Certificate
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id            = get_the_ID();
$disable_certificate  = get_post_meta( $course_id, '_tutor_disable_certificate', true ); // This setting is no more. But used here in favour of backward compatibility.
$certificate_template = get_post_meta( $course_id, 'tutor_course_certificate_template', true );

if ( 'none' === $certificate_template || ( ! $certificate_template && 'yes' == $disable_certificate ) ) {
	/*
		Conditions when not to show certificate section in course
		-------
		1. If certificate template explicitly set as off (After certificate builder release)
		2. No certificate template is set for the course and old setting is off
	*/
	return;
}

$completed  = $this->completed_course( $certificate_hash );
$has_access = (bool) apply_filters( 'tutor_pro_certificate_access', true, $completed );
if ( ! $has_access ) {
	return;
}
?>

<a href="<?php echo esc_url( add_query_arg( array( 'regenerate' => 1 ), $certificate_url ) ); ?>" class="tutor-btn tutor-btn-primary tutor-btn-block tutor-mb-20 tutor-btn-view-certificate">
	<?php esc_html_e( 'View Certificate', 'tutor-pro' ); ?>
</a>
