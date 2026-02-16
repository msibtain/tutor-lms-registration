<?php
/**
 * Show H5P Verbs Statements List.
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

<?php if ( is_array( $all_verb_statements ) && count( $all_verb_statements ) ) : ?>
		<div class="tutor-table-responsive tutor-mt-10">
			<table class="tutor-table tutor-table-middle" style="min-width: fit-content;">
				<thead class="tutor-text-sm tutor-text-400">
					<tr>
						<th>
							<?php esc_html_e( 'Verbs', 'tutor-pro' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Verb ID', 'tutor-pro' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Number of Statements', 'tutor-pro' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_verb_statements as $statement ) : ?>
					<tr>
						<td><?php echo esc_html( $statement->verb ); ?></td>
						<td><?php echo esc_html( $statement->verb_id ); ?></td>
						<td><?php echo esc_html( $statement->statement_count ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
<?php else : ?>
	<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
<?php endif; ?>