<?php
/**
 * Template for bundle courses tab.
 *
 * @package TutorPro\CourseBundle
 * @subpackage Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

use TutorPro\CourseBundle\Models\BundleModel;

// Here $course_id is bundle_id passed from single-course-bundle.php.
$bundle_id    = $course_id;
$courses      = BundleModel::get_bundle_courses( $bundle_id );
$total_course = count( $courses );
?>

<h2 class="tutor-fs-5 tutor-fw-bold tutor-color-black tutor-mb-12">
	<?php
		/* translators: %s: count total courses */
		echo esc_html( sprintf( __( 'Courses in the Bundle (%s)', 'tutor-pro' ), $total_course ) );
	?>
</h2>

<ul class="tutor-bundle-courses-wrapper" id="tutor-bundle-course-list">
<?php
foreach ( $courses as $course ) :
	$thumb_url     = get_tutor_course_thumbnail_src( 'post-thumbnail', $course->ID );
	$profile_url   = tutor_utils()->profile_url( $course->post_author, true );
	$course_link   = get_permalink( $course->ID );
	$course_title  = get_the_title( $course->ID );
	$course_author = get_the_author_meta( 'display_name', $course->post_author );

	$course_categories = get_tutor_course_categories( $course->ID );
	$category_links    = array();
	if ( is_array( $course_categories ) && count( $course_categories ) ) {
		foreach ( $course_categories as $course_category ) {
			$category_name    = $course_category->name;
			$category_link    = get_term_link( $course_category->term_id );
			$category_links[] = wp_sprintf( '<a href="%1$s">%2$s</a>', esc_url( $category_link ), esc_html( $category_name ) );
		}
	} else {
		$category_links[] = '<a href="#" class="tutor-color-muted">' . esc_html__( 'Uncategorized', 'tutor-pro' ) . '</a>';
	}


	$bundle_course_subscription_access = apply_filters( 'tutor_has_bundle_course_subscription_access', true, $bundle_id, $course->ID );
	?>
	<li class="tutor-bundle-course-list-wrapper">
		<div class="tutor-bundle-course-list-counter tutor-flex-center">
			<a href="<?php echo esc_url( $course_link ); ?>" class="tutor-bundle-feature-image">
				<img class="tutor-radius-4" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $course_title ); ?>" loading="lazy">
			</a>
		</div>
		<div class="tutor-bundle-course-list-desc">
			<?php if ( ! $bundle_course_subscription_access ) : ?>
			<span class="tutor-badge-label label-warning tutor-d-flex tutor-gap-4px tutor-mb-8">
				<span class="tutor-icon-warning"></span> <span><?php esc_html_e( 'Not Available in Your Plan', 'tutor-pro' ); ?></span>
			</span>
			<?php endif; ?>

			<a href="<?php echo esc_url( $course_link ); ?>">
				<h2 class="tutor-fs-6 tutor-fw-bold tutor-color-black tutor-line-clamp-2 tutor-bundle-course-title">
					<?php echo esc_html( $course_title ); ?>
				</h2>
			</a>
			<p class="tutor-mt-4">
				<span class="tutor-color-muted"><?php esc_html_e( 'By', 'tutor-pro' ); ?></span>
				<a href="<?php echo esc_url( $profile_url ); ?>" target="_parent"><?php echo esc_html( $course_author ); ?></a>
				<span class="tutor-fs-8 tutor-px-4" style="color:#9197A8;opacity:0.2;">|</span>
				<span class="tutor-color-muted"><?php esc_html_e( 'Category:', 'tutor-pro' ); ?></span>
				<?php
				echo wp_kses(
					implode( ', ', $category_links ),
					array(
						'a' => array(
							'href'  => true,
							'class' => true,
						),
					)
				);
				?>
			</p>
		</div>
	</li>
	<?php
endforeach;
?>
</ul>

