<?php
/**
 * Show Last 10 H5P Statements List.
 *
 * @package TutorPro\Addons
 * @subpackage H5P\Views\Analytics
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( is_array( $last_ten_statements ) && count( $last_ten_statements ) ) : ?>
		<div class="tutor-table-responsive tutor-mt-10">
			<table class="tutor-table tutor-table-middle" style="min-width: fit-content;">
				<thead class="tutor-text-sm tutor-text-400">
					<tr>
						<th width="20%">
							<?php esc_html_e( 'Learner', 'tutor-pro' ); ?>
						</th>
						<th width="10%">
							<?php esc_html_e( 'Verb', 'tutor-pro' ); ?>
						</th>
						<th width="20%">
							<?php esc_html_e( 'Activity Name', 'tutor-pro' ); ?>
						</th>
						<th width="20%">
							<?php esc_html_e( 'Saved at', 'tutor-pro' ); ?>
						</th>
					</tr>
				</thead>
				<tbody >
					<?php
					foreach ( $last_ten_statements as $statement ) :
						$user = tutor_utils()->get_tutor_user( $statement->user_id );
						?>
					<tr>
						
						<td>
							<div class="tutor-d-flex tutor-align-center">
								<?php
								echo wp_kses(
									tutor_utils()->get_tutor_avatar( $user, 'sm' ),
									tutor_utils()->allowed_avatar_tags()
								)
								?>
								<div class="tutor-ml-12">
									<a target="_blank" class="tutor-fs-7 tutor-table-link" href="<?php echo esc_url( tutor_utils()->profile_url( $user, true ) ); ?>">
										<?php echo esc_html( $user ? $user->display_name : '' ); ?>
									</a>
								</div>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal tutor-fs-7">
								<?php echo esc_html( $statement->verb ); ?>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal tutor-fs-7">
								<?php echo esc_html( $statement->activity_name ); ?>
							</div>
						</td>
						<td>
							<div class=" tutor-fw-normal tutor-fs-7">
								<?php
								echo esc_html( tutor_utils()->convert_date_into_wp_timezone( $statement->created_at ) );
								?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
<?php else : ?>
	<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
<?php endif; ?>