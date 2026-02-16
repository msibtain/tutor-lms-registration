<?php
/**
 * Show H5P Learners Statements List.
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

<?php if ( is_array( $all_learners_statements ) && count( $all_learners_statements ) ) : ?>
		<div class="tutor-table-responsive tutor-mt-10">
			<table class="tutor-table tutor-table-middle" style="min-width: fit-content;">
				<thead class="tutor-text-sm tutor-text-400">
					<tr>
						<th >
							<?php esc_html_e( 'Learner', 'tutor-pro' ); ?>
						</th>
						<th >
							<?php esc_html_e( 'Learner Email/ID', 'tutor-pro' ); ?>
						</th>
						<th >
							<?php esc_html_e( 'Number of Statements', 'tutor-pro' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $all_learners_statements as $statement ) :
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
							<?php echo esc_html( $user->user_email ); ?>
						</td>
						<td>
							<?php echo esc_html( $statement->statement_count ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
<?php else : ?>
	<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
<?php endif; ?>