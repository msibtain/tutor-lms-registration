<?php
/**
 * Buddypress Group Course View
 *
 * @package TutorPro\Addons
 * @subpackage Buddypress\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.3.5
 */

$groups         = groups_get_groups( array( 'show_hidden' => true ) );
$attached_group = (array) \TUTOR_BP\BuddyPressGroups::get_group_ids_by_course( get_the_ID() );

?>

<div class="tutor-row">
	<div class="tutor-col-12 tutor-col-md-5">
		<label class="tutor-course-setting-label">
			<?php esc_html_e( 'BuddyPress Groups', 'tutor-pro' ); ?>
		</label>
	</div>
	<div class="tutor-col-12 tutor-col-md-7 tutor-mb-32">
		<?php if ( isset( $groups['groups'] ) && isset( $groups['groups'][0] ) ) : ?>
			<select name="_tutor_bp_course_attached_groups[]" class="tutor_select2" multiple="multiple">
				<?php
				foreach ( $groups['groups'] as $group ) {
					$selected = in_array( $group->id, $attached_group, true ) ? 'selected="selected"' : '';
					echo wp_kses(
						"<option value='{$group->id}' {$selected} > {$group->name} </option>",
						array(
							'option' => array(
								'value'    => 1,
								'selected' => 1,
							),
						)
					);
				}
				?>
			</select>
			<div class="tutor-form-feedback">
				<i class="tutor-icon-icon-circle-info tutor-form-feedback-icon"></i>
				<div>
					<?php esc_html_e( 'Assign this course to BuddyPress Groups', 'tutor-pro' ); ?>
				</div>	
			</div>
		<?php else : ?>
			<div class="tutor-form-feedback">
				<div>
					<?php esc_html_e( 'No group found, please add.', 'tutor-pro' ); ?>
				</div>	
			</div>
		<?php endif; ?>
	</div>
</div>
