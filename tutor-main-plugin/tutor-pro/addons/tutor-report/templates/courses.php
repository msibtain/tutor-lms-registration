<?php
/**
 * Course report page
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

use TUTOR\Input;
use \TUTOR_REPORT\Analytics;

global $wp_query, $wp;
$user     = wp_get_current_user();
$paged    = Input::get( 'current_page', 0 ) > 1 ? Input::get( 'current_page' ) : 1;
$url      = home_url( $wp->request );
$url_path = parse_url( $url, PHP_URL_PATH );
$basename = pathinfo( $url_path, PATHINFO_BASENAME );
$per_page = tutor_utils()->get_option( 'pagination_per_page' );
$offset   = ( $per_page * $paged ) - $per_page;

$orderby    = Input::get( 'orderby', 'learner' ) === 'learner' ? 'learner' : 'earning';
$order      = Input::get( 'order', 'desc' );
$sort_order = array( 'order' => 'asc' === $order ? 'desc' : 'asc' );

//phpcs:disable WordPress.Security.NonceVerification.Recommended
$learner_sort = http_build_query( array_merge( $_GET, ( 'learner' === $orderby ? $sort_order : array() ), array( 'orderby' => 'learner' ) ) );
$earning_sort = http_build_query( array_merge( $_GET, ( 'earning' === $orderby ? $sort_order : array() ), array( 'orderby' => 'earning' ) ) );
//phpcs:enable WordPress.Security.NonceVerification.Recommended

$learner_order_icon = 'learner' === $orderby ? ( strtolower( $order ) == 'asc' ? 'up' : 'down' ) : 'up';
$earning_order_icon = 'earning' === $orderby ? ( strtolower( $order ) == 'asc' ? 'up' : 'down' ) : 'up';

$search = Input::get( 'search', '' );

$courses      = Analytics::get_courses_with_total_enroll_earning( $user->ID, $sort_order['order'], is_null( $orderby ) ? '' : $orderby, $offset, $per_page, $search );
$total_course = Analytics::get_courses_with_search_by_user( $user->ID, $search );

?>

<div class="tutor-analytics-courses">
	<?php if ( count( $courses ) ) : ?>
		<div class="tutor-mb-24">
			<form method="get" id="tutor_analytics_search_form">
				<div class="tutor-form-wrap">
					<span class="tutor-icon-search tutor-form-icon" area-hidden="true"></span>
					<input type="search" class="tutor-form-control" autocomplete="off" name="search" placeholder="<?php esc_attr_e( 'Search...', 'tutor-pro' ); ?>">
				</div>
			</form>
		</div>

		<div class="tutor-table-responsive">
			<table class="tutor-table">
				<thead>
					<th>
						<?php esc_html_e( 'Course', 'tutor-pro' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Total Learners', 'tutor-pro' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Earnings', 'tutor-pro' ); ?>
					</th>
					<th></th>
				</thead>
				<tbody>
					<?php if ( count( $courses ) ) : ?>

						<?php
							$course_ids       = array_column( $courses, 'ID' );
							$course_meta_data = tutor_utils()->get_course_meta_data( $course_ids );
						?>

						<?php foreach ( $courses as $course ) : ?>
							<?php
								$course->lesson     = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['lesson'] : 0;
								$course->quiz       = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['tutor_quiz'] : 0;
								$course->assignment = isset( $course_meta_data[ $course->ID ] ) ? $course_meta_data[ $course->ID ]['tutor_assignments'] : 0;
							?>

							<tr>
								<td>
									<span>
										<?php echo esc_html( $course->post_title ); ?>
									</span>
									<div class="tutor-meta tutor-mt-4">
										<span>
											<span class="tutor-meta-key"><?php esc_html_e( 'Lesson', 'tutor-pro' ); ?>:</span>
											<span class="tutor-meta-value"><?php echo esc_html( $course->lesson ); ?></span>
										</span>
										
										<span>
											<span class="tutor-meta-key"><?php esc_html_e( 'Assignment', 'tutor-pro' ); ?>:</span>
											<span class="tutor-meta-value"><?php echo esc_html( $course->assignment ); ?></span>
										</span>
										
										<span>
											<span class="tutor-meta-key"><?php esc_html_e( 'Quiz', 'tutor-pro' ); ?>:</span>
											<span class="tutor-meta-value"><?php echo esc_html( $course->quiz ); ?></span>
										</span>
									</div>
								</td>

								<td>
									<?php echo esc_html( $course->learner ); ?>
								</td>

								<td>
									<?php
										$earnings = Analytics::get_earnings_by_user( $user->ID, '', '', '', $course->ID );
										echo wp_kses_post( tutor_utils()->tutor_price( $earnings['total_earnings'] ) );
									?>
								</td>

								<td>
									<div class="tutor-d-flex tutor-align-center tutor-justify-end">
										<a href="<?php echo esc_url( tutor_utils()->tutor_dashboard_url() . 'analytics/course-details?course_id=' . $course->ID ); ?>" class="tutor-btn tutor-btn-outline-primary tutor-btn-sm tutor-mr-12"><?php esc_html_e( 'Details', 'tutor-pro' ); ?></a>
										<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>" class="tutor-iconic-btn" target="_blank"><span class="tutor-icon-external-link"></span></a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		
		<?php
		if ( $total_course > $per_page ) {
			$pagination_data = array(
				'total_items' => $total_course,
				'per_page'    => $per_page,
				'paged'       => $paged,
			);

			tutor_load_template_from_custom_path(
				tutor()->path . 'templates/dashboard/elements/pagination.php',
				$pagination_data
			);
		}
		?>
	<?php else : ?>
		<?php tutor_utils()->tutor_empty_state( tutor_utils()->not_found_text() ); ?>
	<?php endif; ?>
</div>
