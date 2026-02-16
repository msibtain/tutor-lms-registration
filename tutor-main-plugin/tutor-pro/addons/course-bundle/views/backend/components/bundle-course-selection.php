<?php
/**
 * Course selection component for the bundle builder
 *
 * @since 2.2.0
 *
 * @package TutorPro\CourseBundle\Views
 */

use TUTOR\Course;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use TutorPro\CourseBundle\Models\BundleModel;

$args = array();
if ( ! tutor_utils()->is_monetize_by_tutor() ) {
	// Meta query clause for WC monetization.
	$args = array(
		'meta_query' => array(
			array(
				'key'     => CourseModel::WC_PRODUCT_META_KEY,
				'value'   => 0,
				'compare' => '!=',
				'type'    => 'NUMERIC',
			),
		),
	);
}

$courses        = CourseModel::get_paid_courses( $args );
$courses        = is_a( $courses, 'WP_Query' ) ? $courses->get_posts() : array();
$bundle_id      = Input::get( 'bundle-id' ) ?? get_the_ID();
$course_ids     = BundleModel::get_bundle_course_ids( $bundle_id );
$total_enrolled = BundleModel::get_total_bundle_sold( $bundle_id );
$pointer_events = $total_enrolled ? 'none' : 'auto';

?>
<div class="tutor-courses tutor-wp-dashboard-filter-item" style="pointer-events: <?php echo esc_attr( $pointer_events ); ?>">
	<div class="tutor-d-flex tutor-gap-1 tutor-align-start">
		<label class="tutor-form-label">
			<?php esc_html_e( 'Select Courses', 'tutor-pro' ); ?>
		</label>
		<span class="tutor-btn tutor-bundle-loader"></span>
	</div>
	<select class="tutor-form-select" id="tutor-bundle-course-selection" data-searchable>
		<?php if ( count( $courses ) ) : ?>
			<option value="">
				<?php esc_html_e( 'Select courses', 'tutor-pro' ); ?>
			</option>
			<?php
			foreach ( $courses as $key => $course ) :
				if ( ! tutor_utils()->is_course_purchasable( $course->ID ) ) {
					continue;
				}
				?>
				<option value="<?php echo esc_attr( $course->ID ); ?>">
					<?php echo esc_html( $course->post_title ); ?>
				</option>
			<?php endforeach; ?>
		<?php else : ?>
			<option value=""><?php esc_html_e( 'No course found', 'tutor-pro' ); ?></option>
		<?php endif; ?>
	</select>
</div>
