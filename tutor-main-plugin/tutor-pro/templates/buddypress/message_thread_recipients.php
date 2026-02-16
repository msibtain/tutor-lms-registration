<?php
/**
 * Buddy press view page
 *
 * @package TutorPro\BuddyPress
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.4.8
 */

use Tutor\Models\CourseModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user_id = get_current_user_id();

foreach ( $recipients as $recipient ) {
	$user_id = $recipient->user_id;

	$instructor_type = false;
	$student_type    = get_user_meta( $user_id, '_is_tutor_student', true );

	$courses_ids_by_instructor = array();
	if ( user_can( $user_id, 'tutor_instructor' ) ) {
		$instructor_type = __( 'Instructor', 'tutor-pro' );
	}

	$courses_by_instructor = CourseModel::get_courses_by_instructor( $current_user_id );
	if ( tutor_utils()->count( $courses_by_instructor ) ) {
		$courses_ids_by_instructor = wp_list_pluck( $courses_by_instructor, 'ID' );
	}

	$enrolled_course_ids        = array_unique( tutor_utils()->get_enrolled_courses_ids_by_user( $user_id ) );
	$enrolled_common_course_ids = array_intersect( $enrolled_course_ids, $courses_ids_by_instructor );
	?>

	<div class="tutor-bp-message-recipient-header">

		<div class="tutor-bp-message-recipient-avatar-wrap">
			<?php
			echo wp_kses_post(
				bp_get_displayed_user_avatar(
					array(
						'item_id' => $user_id,
						'width'   => 100,
						'height'  => 100,
					)
				)
			);
			?>
		</div>

		<div class="tutor-bp-recipient-info-wrap">
			<div class="tutor-bp-thread-recipient-name">
				<h3>
					<?php echo esc_html( bp_core_get_user_displayname( $user_id ) ); ?>
				</h3>
				<?php
				if ( $instructor_type || $student_type ) {
					echo '<h4>';
					echo esc_html( $instructor_type ? $instructor_type : '' );

					if ( $instructor_type && $student_type ) {
						echo esc_html__( ' , ', 'tutor-pro' );
					}

					if ( $student_type ) {
						esc_html_e( 'Student', 'tutor-pro' );
					}

					echo '</h4>';
				}
				?>
			</div>

			<?php
			$count_common_courses = tutor_utils()->count( $enrolled_common_course_ids );
			if ( $count_common_courses ) {
				?>
				<div class="tutor-bp-enrolled-courses-wrap">
					<p class="tutor-bp-enrolled-total-course-notice">
						<?php
						echo sprintf(
							// translators: %s is a placeholder for the number of courses.
							esc_html__( 'Enrolled in total %s courses by you', 'tutor-pro' ),
							esc_html( $count_common_courses )
						);
						?>
					:</p>
					<ul class="tutor-bp-enrolled-course-list" style="display: none;">
						<?php
						foreach ( $enrolled_common_course_ids as $course_id ) {
							$course_title = get_the_title( $course_id );
							if ( $course_title ) {
								?>
								<li>
									<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" class="bp-tooltip"
										data-bp-tooltip="<?php echo esc_attr( $course_title ); ?>">
										<?php echo esc_html( $course_title ); ?>
									</a>
								</li>
								<?php
							}
						}
						?>
					</ul>
					<div class="thread-participant-enrolled-info"></div>
				</div>
				<?php
			}
			?>

		</div>

	</div>
	<?php
}
