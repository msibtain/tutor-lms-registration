<?php
/**
 * H5P Analytics page template
 *
 * @package TutorPro\Addons
 * @subpackage H5P\Views\Analytics
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

use TUTOR\Input;
use TutorPro\H5P\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="tutor-admin-wrap">
	<div class="tutor-wp-dashboard-header tutor-px-24 tutor-mb-24">
		<div class="tutor-row tutor-align-lg-center">
			<div class="tutor-col-lg">
				<div class="tutor-d-lg-flex tutor-align-lg-center tutor-p-12">
					<span class="tutor-fs-5 tutor-fw-medium">
						<?php esc_html_e( 'H5P Analytics', 'tutor-pro' ); ?>
					</span>

					<span class="tutor-mx-8" area-hidden="true">/</span>
					
					<span class="tutor-fs-7 tutor-color-muted">
						<?php echo esc_html( $current_name ); ?>
					</span>
				</div>
			</div>

			<div class="tutor-col-lg-auto">
				<ul class="tutor-nav tutor-nav-admin">
					<?php foreach ( $sub_pages as $key => $sub_page ) : ?>
						<?php
							$is_active = $sub_page === $current_name ? ' is-active' : '';
							$url       = add_query_arg(
								array(
									'page'     => 'tutor_h5p',
									'sub_page' => $key,
								),
								admin_url( 'admin.php' )
							);
						?>
						<li class="tutor-nav-item">
							<a class="tutor-nav-link<?php echo esc_attr( $is_active ); ?>" href="<?php echo esc_url( $url ); ?>">
								<?php echo esc_html( $sub_page ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>

	<div class="tutor-admin-body">
		<div class="report-main-wrap">
			<div class="tutor-report-content">
				<?php
				$h5p_analytics_page = 'overview';
				if ( Input::has( 'sub_page' ) ) {
					$h5p_analytics_page = Input::get( 'sub_page' );
				}

				$view_page = Utils::addon_config()->path . "views/analytics/subpage/{$h5p_analytics_page}.php";

				if ( file_exists( $view_page ) ) {
					include $view_page;
				}
				?>
			</div>
		</div>
	</div>
</div>

