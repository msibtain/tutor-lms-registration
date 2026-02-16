<?php
/**
 * Content Drip Lesson
 *
 * @package TutorPro\Addons
 * @subpackage ContentDrip\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.8.9
 */

use TUTOR\Input;

$post_id = get_the_ID();

/**
 * Define vars first
 * prevent undefined error
 *
 * @since 1.8.9
*/
$lesson_id     = 0;
$quiz_id       = 0;
$assignment_id = 0;

//phpcs:ignore
if ( count( $_POST ) > 0 ) {
	$lesson_id     = Input::post( 'lesson_id', 0, Input::TYPE_INT );
	$quiz_id       = Input::post( 'quiz_id', 0, Input::TYPE_INT );
	$assignment_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );
} else {

	/**
	 * Retrieve post
	 * if not null set lesson id
	 *
	 * @since 1.8.9
	*/
	$post = get_post( $post_id );
	if ( ! is_null( $post ) ) {
		if ( tutor()->lesson_post_type === $post->post_type ) {
			$lesson_id = $post->ID;
		}
	}
}



$course_item_id = 0;
if ( $lesson_id ) {
	$course_item_id = $lesson_id;
} elseif ( $quiz_id ) {
	$course_item_id = $quiz_id;
} elseif ( $assignment_id ) {
	$course_item_id = $assignment_id;
}

if ( $course_item_id ) {
	$post_id = (int) sanitize_text_field( $course_item_id );
}

/**
 * Check for $_POST
 * if not set item then get course id from utils
 * by lesson id
 *
 * @since 1.8.9
*/
$course_id = 0;
//phpcs:ignore
if ( count( $_POST ) > 0 ) {
	$course_id = Input::post( 'course_id', 0, Input::TYPE_INT );
} else {
	$course_id = tutor_utils()->get_course_id_by( 'lesson', $lesson_id );
}

$enable_content_drip = get_tutor_course_settings( $course_id, 'enable_content_drip' );
if ( ! $enable_content_drip ) {
	return;
}
$content_drip_type = get_tutor_course_settings( $course_id, 'content_drip_type', 'unlock_by_date' );
if ( 'unlock_sequentially' === $content_drip_type ) {
	return;
}
?>

<div class="lqa-content-drip-wrap">
	<span><?php esc_html_e( 'Content Drip Settings', 'tutor-pro' ); ?></span>
	<?php
	if ( 'unlock_by_date' === $content_drip_type ) {
		$unlock_date = get_item_content_drip_settings( $course_item_id, 'unlock_date' );
		?>
		<div class="">
			<label>
				<?php esc_html_e( 'Unlocking date', 'tutor-pro' ); ?>
			</label>
			<div class="tutor-mb-4 tutor-d-block" style="width: 218px;">
				<div class="tutor-v2-date-picker" data-is_clearable="true" data-prevent_redirect="1" data-input_name="content_drip_settings[unlock_date]" data-input_value="<?php echo esc_attr( $unlock_date ? tutor_get_formated_date( 'd-m-Y', $unlock_date ) : '' ); ?>">

				</div>
			</div>
		</div>
		<?php
	} elseif ( 'specific_days' === $content_drip_type ) {
		$days = get_item_content_drip_settings( $course_item_id, 'after_xdays_of_enroll', 7 );
		?>
		<div class="">
			<label class="tutor-form-label">
				<?php esc_html_e( 'Days', 'tutor-pro' ); ?>
			</label>
			<div class="tutor-mb-4 tutor-d-block">
				<input class="tutor-form-control" type="number" min="0" step="1" onkeypress='return event.charCode >= 48 && event.charCode <= 57' value="<?php echo esc_attr( $days ); ?>" name="content_drip_settings[after_xdays_of_enroll]">
				<div class="tutor-form-feedback">
					<i class="tutor-icon-circle-info-o tutor-form-feedback-icon"></i>
					<div>
						<?php esc_html_e( 'This lesson will be available after the given number of days.', 'tutor-pro' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php

	} elseif ( 'after_finishing_prerequisites' === $content_drip_type ) {
		$prerequisites = (array) get_item_content_drip_settings( $course_item_id, 'prerequisites' );
		$query_topics  = tutor_utils()->get_topics( $course_id );

		if ( tutor_utils()->count( $query_topics->posts ) ) {
			?>
			<div class="">
				<label class="tutor-form-label">
					<?php esc_html_e( 'Prerequisites', 'tutor-pro' ); ?>
				</label>
				<div class="tutor-input-group tutor-mb-4 tutor-d-block">
					<select name="content_drip_settings[prerequisites][]" multiple="multiple" class="select2_multiselect">
						<option value=""><?php esc_html_e( 'Select prerequisites item', 'tutor-pro' ); ?></option>
						<?php
						foreach ( $query_topics->posts as $topic ) {
							echo "<optgroup label='" . esc_html( $topic->post_title ) . "'>";
							$topic_items = tutor_utils()->get_course_contents_by_topic( $topic->ID, -1 );
							foreach ( $topic_items->posts as $topic_item ) {
								if ( $topic_item->ID != $course_item_id ) {

									$is_selected = '';
									if ( in_array( $topic_item->ID, $prerequisites ) ) {
										$is_selected = 'selected="selected"';
									}

									echo wp_kses(
										"<option value='{$topic_item->ID}' {$is_selected} >{$topic_item->post_title}</option>",
										array(
											'option' => array(
												'value'    => 1,
												'selected' => 1,
											),
										)
									);
								}
							}
							echo '</optgroup>';
						}
						?>
					</select>
					<div class="tutor-form-feedback">
						<i class="tutor-icon-circle-info-o tutor-form-feedback-icon"></i>
						<div>
							<?php esc_html_e( 'Select items that should be complete before this item', 'tutor-pro' ); ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
	?>
</div>
