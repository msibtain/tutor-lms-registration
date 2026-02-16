<?php
/**
 * Display Topics and Lesson lists for learn
 *
 * @since v.1.0.0
 * @author themeum
 * @url https://themeum.com
 *
 * @package TutorLMS/Templates
 * @version 1.4.3
 */

use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$course_id    = Input::post( 'course_id', '' );
$student_id   = Input::post( 'student_id', '' );
$post         = get_post( $course_id );
$currentPost  = $post;
$checked_icon = 'tutor-icon-circle-mark';

$completed_lesson = Input::post( 'completed_lesson', 0 );
$total_lesson     = Input::post( 'total_lesson', 0 );

$completed_assignment = Input::post( 'completed_assignment', 0 );
$total_assignment     = Input::post( 'total_assignment', 0 );

$completed_quiz = Input::post( 'completed_quiz', 0 );
$total_quiz     = Input::post( 'total_quiz', 0 );

$total_progress = Input::post( 'total_progress', 0 );
?>
<div class="tutor-analytics-progress-popup">
	<div class="tutor-fs-4 tutor-fw-medium tutor-color-black tutor-mb-24"><?php echo esc_html( $post->post_title ); ?></div>
	<div class="tutor-row tutor-align-center">
		<div class="tutor-col-xl-8 tutor-d-flex tutor-fs-6 tutor-color-muted">
			<span>
				<?php esc_html_e( 'Lesson', 'tutor-pro' ); ?>: <?php echo esc_html( $completed_lesson . '/' . $total_lesson ); ?>
			</span>
			
			<span class="tutor-ml-16">
				<?php esc_html_e( 'Assignment', 'tutor-pro' ); ?>: <?php echo esc_html( $completed_assignment . '/' . $total_assignment ); ?>
			</span>
			
			<span class="tutor-ml-16">
				<?php esc_html_e( 'Quiz', 'tutor-pro' ); ?>: <?php echo esc_html( $completed_quiz . '/' . $total_quiz ); ?>
			</span>
		</div>

		<div class="tutor-col-xl-4">
			<div class="course-total-completed course-progress tutor-d-flex tutor-align-center">
				<div class="tutor-progress-bar" style="--tutor-progress-value:<?php esc_attr( $total_progress . '%;' ); ?>">
					<div class="tutor-progress-value" area-hidden="true"></div>
				</div>
				<div class="tutor-color-black tutor-fs-7 tutor-fw-medium tutor-ml-12 tutor-text-nowrap">
					<?php echo esc_html( $total_progress ); ?>% <?php esc_html_e( ' Complete', 'tutor-pro' ); ?>
				</div>
			</div>
		</div>
	</div>

	<div class="tutor-sidebar-tabs-content">
		<div id="tutor-lesson-sidebar-tab-content" class="tutor-lesson-sidebar-tab-item">
			<div class="tutor-accordion tutor-mt-24">
				<?php
				$topics = tutor_utils()->get_topics( $course_id );
				$i      = 0;
				if ( $topics->have_posts() ) {
					while ( $topics->have_posts() ) {
						$topics->the_post();
						$i++;
						$topic_id      = get_the_ID();
						$topic_summery = get_the_content();
						?>

						<div class="tutor-accordion-item tutor-topics-<?php echo esc_attr( $topic_id ); ?>">
							<div class="tutor-accordion-item-header <?php echo $topic_summery ? 'has-summery' : ''; ?>">
								<?php echo esc_html( '0.' . $i ); ?>
								<?php the_title(); ?>
							</div>

							<div class="tutor-accordion-item-body" style="display: none;">
							<div class="tutor-accordion-item-body-content">
								<?php
									do_action( 'tutor/lesson_list/before/topic', $topic_id );

									$lessons = tutor_utils()->get_course_contents_by_topic( get_the_ID(), -1 );

								if ( $lessons->have_posts() ) {
									?>

										<ul class="tutor-course-content-list">
										<?php
										foreach ( $lessons->posts as $post ) {

											if ( 'tutor_quiz' === $post->post_type ) {
												$quiz = $post;
												?>

													<li class="tutor-course-content-list-item quiz-single-item quiz-single-item-<?php echo esc_attr(  $quiz->ID ); ?> <?php echo esc_attr( ( $currentPost->ID === get_the_ID() ) ? 'active' : '' ); ?>" data-quiz-id="<?php echo esc_attr( $quiz->ID ); ?>">
														<div class="tutor-d-flex tutor-align-center">
															<span class="tutor-icon-circle-question-mark tutor-color-muted tutor-mr-16"></span>
															<span class="tutor-course-content-list-item-title">
																<a href="<?php echo esc_url( get_permalink( $quiz->ID ) ); ?>" class="sidebar-single-quiz-a" data-quiz-id="<?php echo esc_attr( $quiz->ID ); ?>"> <?php echo esc_attr( $quiz->post_title ); ?> </a>
															</span>
														</div>
														
														<div>
															<span class="tutor-fs-7 tutor-color-muted">
															<?php
															do_action( 'tutor/lesson_list/right_icon_area', $post );

															$has_attempt = tutor_utils()->has_attempted_quiz( $student_id, $quiz->ID );
															$time_limit  = tutor_utils()->get_quiz_option( $quiz->ID, 'time_limit.time_value' );

															if ( $has_attempt ) {
																echo '<input type="checkbox" class="tutor-form-check-input tutor-form-check-circle" disabled="disabled" readonly="readonly" checked="&quot;checked&quot;/">';
															}

															if ( $time_limit ) {
																$time_type = tutor_utils()->get_quiz_option( $quiz->ID, 'time_limit.time_type' );
															}
															?>
															</span>
														</div>
													</li>

												<?php

											} elseif ( 'tutor_assignments' === $post->post_type ) {
												/**
												 * Assignments
												 *
												 * @since this block v.1.3.3
												 */
												$assignment_submitted = tutor_utils()->get_submitted_assignment_count( $post->ID, $student_id );
												?>
												<li class="tutor-course-content-list-item assignments-single-item assignment-single-item-<?php echo esc_attr( $post->ID ); ?> <?php echo esc_attr( ( $currentPost->ID === get_the_ID() ) ? 'active' : '' ); ?>" data-assignment-id="<?php echo esc_attr( $post->ID ); ?>">
													<div class="tutor-d-flex tutor-align-center">
														<span class="tutor-icon-clipboard tutor-color-muted tutor-mr-16"></span>
														<span class="tutor-course-content-list-item-title">
															<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="sidebar-single-assignment-a" data-assignment-id="<?php echo esc_attr( $post->ID ); ?>"> <?php echo esc_attr( $post->post_title ); ?> </a>
														</span>
													</div>

													<div>
														<span class="tutor-fs-7 tutor-color-muted">
														<?php
														if ( $assignment_submitted ) {
															echo '<input type="checkbox" class="tutor-form-check-input tutor-form-check-circle" disabled="disabled" readonly="readonly" checked="&quot;checked&quot;/">';
														}
														?>
														</span>
													</div>
												</li>

												<?php

											} elseif ( 'tutor_zoom_meeting' === $post->post_type ) {
												/**
												 * Zoom Meeting
												 *
												 * @since this block v.1.7.1
												 */

												?>

												<li class="tutor-course-content-list-item zoom-meeting-single-item zoom-meeting-single-item-<?php echo esc_attr( $post->ID ); ?> <?php echo esc_attr( ( $currentPost->ID === get_the_ID() ) ? 'active' : '' ); ?>>" data-assignment-id="<?php echo esc_attr( $post->ID ); ?>">
													<div class="tutor-d-flex tutor-align-center">
														<span class="tutor-icon-brand-zoom tutor-color-muted tutor-mr-16"></span>
														<span class="tutor-course-content-list-item-title">
															<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="sidebar-single-zoom-meeting-a" data-assignment-id="<?php echo esc_attr( $post->ID ); ?>"> <?php echo esc_attr( $post->post_title ); ?> </a>
														</span>
													</div>

													<div>
														<span class="tutor-fs-7 tutor-color-muted">
															<?php do_action( 'tutor/lesson_list/right_icon_area', $post ); ?>
														</span>
													</div>
												</li>

												<?php

											} else {

												/**
												 * Lesson
												 */
												$video = tutor_utils()->get_video_info( $post->ID );

												$play_time = false;
												if ( $video ) {
													$play_time = $video->playtime;
												}
												$is_completed_lesson = tutor_utils()->is_completed_lesson( $post->ID, $student_id );
												?>

												<li class="tutor-course-content-list-item <?php echo esc_attr( ( $post->ID ) ? 'active' : '' ); ?>" data-assignment-id="<?php echo esc_attr( $post->ID ); ?>">
													<div class="tutor-d-flex tutor-align-center">
														<?php
														$tutor_lesson_type_icon = $play_time ? 'brand-youtube-bold' : 'clipboard-list';
														?>
														<span class='tutor-icon-<?php echo esc_attr( $tutor_lesson_type_icon ); ?> tutor-color-muted tutor-mr-16'></span>
														<span class="tutor-course-content-list-item-title">
															<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="tutor-single-lesson-a" data-assignment-id="<?php echo esc_attr( $post->ID ); ?>"> <?php echo esc_html( $post->post_title ); ?> </a>
														</span>
													</div>
													
													<div>
														<?php

														if ( $play_time ) {
															echo "<span class='tutor-play-duration tutor-mr-20'>" . esc_html( tutor_utils()->get_optimized_duration( $play_time ) ) . '</span>';
														}
															do_action( 'tutor/lesson_list/right_icon_area', $post );
														if ( $is_completed_lesson ) {
															echo '<input type="checkbox" class="tutor-form-check-input tutor-form-check-circle" disabled="disabled" readonly="readonly" checked="&quot;checked&quot;/">';
														}
														?>
													</div>
												</li>


												<?php
											}
										}
										$lessons->reset_postdata();
										?>
									</ul>

									<?php

								}
								?>

									<?php do_action( 'tutor/lesson_list/after/topic', $topic_id ); ?>
							</div>
							</div>
						</div>

						<?php
					}
					$topics->reset_postdata();
					wp_reset_postdata();
				}
				?>
			</div>
		</div>
	</div>
</div>
