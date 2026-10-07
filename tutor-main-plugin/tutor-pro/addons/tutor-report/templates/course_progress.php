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

defined( 'ABSPATH' ) || exit;

use TUTOR\Icon;
use Tutor\Components\Tooltip;
use Tutor\Components\SvgIcon;
use Tutor\Components\Constants\Color;
use Tutor\Components\Constants\Size;

$icons = array(
	'lesson'       => Icon::COURSES,
	'quiz'         => Icon::QUIZ_2,
	'assignment'   => Icon::BOOK_2,
	'zoom_meeting' => Icon::ZOOM,
	'google_meet'  => Icon::GOOGLE_MEET,
);

$summary_icon = SvgIcon::make()
	->name( Icon::INFO_OCTAGON )
	->size( 16 )
	->color( Color::SUBDUED )
	->attr( 'class', '-tutor-mb-2 tutor-ml-4' )
	->get();
?>

<div class="tutor-analytics-course-progress-modal">
	<div class="tutor-px-7 tutor-sm-px-5 tutor-pt-10 tutor-pb-6 tutor-border-b">
		<div class="tutor-medium">
			<span x-text="payload?.courseProgress?.completed_count + '%' ?? '0%';" class="tutor-font-semibold">%</span> 
			<span class="tutor-text-secondary tutor-font-medium">
				<?php esc_html_e( 'Completed', 'tutor-pro' ); ?>
			</span>
		</div>
		<div class="tutor-progress-bar tutor-mt-4 tutor-mb-6" data-tutor-animated="">
			<div class="tutor-progress-bar-fill" 
				:style="`--tutor-progress-width: ${parseInt(payload?.courseProgress?.completed_count ?? 0)}%`">
			</div>
		</div>
		<!-- Course Info -->
		<div class="tutor-flex tutor-items-center tutor-gap-5 tutor-sm-gap-4">
			<div class="tutor-analytics-course-image">
				<img :src="payload?.courseProgress?.course_image" :alt="payload?.courseProgress?.course?.post_title" loading="lazy" />
			</div>
			<div class="tutor-flex tutor-flex-column tutor-gap-1">
				<div class="tutor-medium tutor-font-medium" x-text="payload?.courseProgress?.course?.post_title"></div>
				<div class="tutor-flex tutor-items-center tutor-gap-5 tutor-sm-gap-4 tutor-tiny">
					<div class="tutor-flex tutor-gap-2">
						<span class="tutor-text-subdued">
							<?php esc_html_e( 'Lesson', 'tutor-pro' ); ?>:
						</span>
						<span x-text="`${payload?.courseProgress?.completed_lessons ?? 0}/${payload?.courseProgress?.total_lessons ?? 0}`"></span>
					</div>         		

					<?php if ( tutor_utils()->is_addon_enabled( 'tutor-assignments' ) ) : ?>
					<div class="tutor-flex tutor-gap-2">
						<span class="tutor-text-subdued">
							<?php esc_html_e( 'Assignment', 'tutor-pro' ); ?>:
						</span>
						<span x-text="`${payload?.courseProgress?.completed_assignment ?? 0}/${payload?.courseProgress?.total_assignments ?? 0}`"></span>
					</div>
					<?php endif; ?>

					<div class="tutor-flex tutor-gap-2">
						<span class="tutor-text-subdued">
							<?php esc_html_e( 'Quiz', 'tutor-pro' ); ?>:
						</span>
						<span x-text="`${payload?.courseProgress?.completed_quiz ?? 0 }/${payload?.courseProgress?.total_quiz ?? 0}`"></span>
					</div>
				</div>
			</div>
		</div>
		<!-- End Of Course Info -->
	</div>

	<!-- Course Topics -->
	<div class="tutor-analytics-course-progress-nav">
		<template x-for="topic in payload?.courseProgress?.topics" :key="topic.id">
			<div x-data="{ expanded: true }" class="tutor-analytics-course-progress-nav-topic active">
				<div role="button" @click="expanded = !expanded" class="tutor-analytics-course-progress-nav-header">				
					<!-- Progress Icon -->
					<div class="tutor-analytics-course-progress-nav-header-progress">
						<div x-data="tutorStatics({ 
							value: topic.completion_percentage,
							size: 'x-small',
							type: 'progress',
							showLabel: false,
							background: 'var(--tutor-actions-gray-empty)',
							strokeColor: 'var(--tutor-border-hover)' })"
							x-show="topic.completion_percentage > 0 && topic.completion_percentage < 100"
						>
							<div x-html="render()"></div>
						</div>
						<div class="tutor-analytics-course-progress-nav-header-progress-inner" x-show="topic.completion_percentage == 0">
						</div>
						<div class="tutor-analytics-course-progress-nav-item" x-show="topic.completion_percentage == 100 ">
							<a href="#">
								<?php SvgIcon::make()->name( Icon::COMPLETED_COLORIZE )->size( 20 )->render(); ?>
							</a>
						</div>
					</div>

					<!-- Completed Icon -->
					<div class="tutor-analytics-course-progress-nav-item" x-show="topic.completed">
						<a href="#">
							<?php SvgIcon::make()->name( Icon::COMPLETED_COLORIZE )->size( 20 )->render(); ?>
						</a>
					</div>

					<div class="tutor-analytics-course-progress-nav-header-title">
						<span class="tutor-font-medium tutor-text-small" x-text="topic.title"></span>
						<template x-if="topic.summary">
							<?php
							Tooltip::make()
							->content(
								'<span x-text="topic.summary"></span>',
								array(
									'span' => array( esc_attr( 'x-text' ) => true ),
								)
							)
							->placement( Tooltip::PLACEMENT_BOTTOM )
							->arrow( Tooltip::ARROW_CENTER )
							->size( Size::LARGE )
							->trigger_element( $summary_icon )
							->render();
							?>
						</template>
					</div>
					<div class="tutor-analytics-course-progress-nav-header-arrow" :class="{ 'is-expanded': expanded }">
						<?php SvgIcon::make()->name( Icon::CHEVRON_DOWN_2 )->size( 20 )->render(); ?>
					</div>
				</div>
				<div x-show="expanded" x-collapse x-cloak class="tutor-analytics-course-progress-nav-body">
					<template x-for="topic_item in topic.items">
						<div class="tutor-analytics-course-progress-nav-item">
							<!-- Incomplete icon -->
							<span 
								:class="topic_item?.icon_class || 'tutor-text-subdued'" 
								x-show="!topic_item.is_completed"
							>
								<span x-data="tutorIcon({ name:topic_item.icon,width: 20, height: 20 })"></span>
							</span>

							<!-- Completed icon -->
							<span x-show="topic_item.is_completed">
								<?php SvgIcon::make()->name( Icon::COMPLETED_COLORIZE )->size( 20 )->render(); ?>
							</span>

							<!-- Text -->
							<div class="tutor-flex tutor-items-center tutor-justify-between tutor-gap-5">
								<div class="tutor-flex tutor-flex-column tutor-justify-center tutor-items-start">							
									<a :href="topic_item.link"
										class="tutor-font-medium tutor-text-small tutor-no-underline tutor-text-secondary"
										x-text="topic_item.title"
									>										
									</a>

									<div class="tutor-flex tutor-gap-2">
										<span class="tutor-text-regular tutor-tiny-2 tutor-text-subdued" x-text="topic_item.label"></span>									
										<span 
											class="tutor-text-regular tutor-tiny-2 tutor-text-subdued"
											x-show="topic_item.video_play_time"
											x-text="topic_item.video_play_time"
										>
										</span>
									</div>
								</div>
							</div>													
						</div>
					</template>				
				</div>
			</div>
		</template>
	</div>
	<!-- End Of Course Topics -->
</div>
