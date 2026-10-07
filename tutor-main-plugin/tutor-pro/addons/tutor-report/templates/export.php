<?php
/**
 * Export Template
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

use TUTOR\Icon;
use Tutor\Components\SvgIcon;
use TUTOR_REPORT\Analytics;

$analytics = new Analytics();
$data      = $analytics->analytics_data();
$students  = $data['students'];
$earnings  = $data['earnings'];
$discounts = $data['discounts'];
$refunds   = $data['refunds'];

$disabled = ( empty( $students ) && empty( $earnings ) && empty( $discounts ) && empty( $refunds ) ) ? ' disabled' : '';

?>
<div class="tutor-surface-l1 tutor-mt-7 tutor-border tutor-rounded-2xl">
	<div class="tutor-flex tutor-justify-between tutor-items-center tutor-sm-flex-column tutor-sm-items-start tutor-p-9 tutor-sm-p-6 tutor-gap-12 tutor-sm-gap-10">
		<div>
			<h4 class="tutor-h4 tutor-font-medium" style="max-width: 240px;">
				<?php esc_html_e( 'Detailed Report of Your Sales & Students', 'tutor-pro' ); ?>
			</h4>
			<div class="tutor-p2 tutor-text-secondary tutor-mt-4">
				<?php esc_html_e( 'Export to keep a copy of your analytics data.', 'tutor-pro' ); ?>
			</div>
			<div class="tutor-mt-8">
				<button type="button" id="download_analytics" class="tutor-btn tutor-btn-primary tutor-btn-small tutor-gap-2" <?php echo esc_attr( $disabled ); ?>>
					<?php SvgIcon::make()->name( Icon::DOWNLOAD_2 )->render(); ?>
					<?php esc_html_e( 'Download CSV', 'tutor-pro' ); ?>
				</button>
			</div>
		</div>

		<div class="tutor-surface-brand-tertiary tutor-rounded-lg">
			<?php tutor_utils()->render_themed_svg( 'images/illustrations/detailed-report.svg' ); ?>
		</div>
	</div>
</div>
