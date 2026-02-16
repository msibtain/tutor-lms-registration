<?php
/**
 * Template parts
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

use TUTOR\Input;

?>
<div class="analytics-title tutor-fs-5 tutor-fw-medium tutor-color-black tutor-my-24">
	<?php esc_html_e( 'Earnings Graph', 'tutor-pro' ); ?>
</div>
<div class="tutor-analytics-filter-tabs tutor-d-flex tutor-flex-xl-nowrap tutor-flex-wrap tutor-align-center tutor-justify-between tutor-pb-40">
	<?php
		$active     = Input::get( 'period', '' );
		$start_date = Input::get( 'start_date', '' );
		$end_date   = Input::get( 'end_date', '' );
	?>
	<?php if ( count( $data['filter_period'] ) ) : ?>
		<div class="tutor-d-flex tutor-align-center tutor-justify-between">
			<?php foreach ( $data['filter_period'] as $key => $value ) : ?>
				<?php $active_class = $active === $value['type'] ? ' is-active' : ''; ?>
				<a href="<?php echo esc_url( $value['url'] ); ?>" class="tutor-btn tutor-btn-outline-primary <?php echo esc_attr( $value['class'] . ' ' . $active_class ); ?> tutor-mr-16">
					<?php echo esc_html( $value['title'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $data['filter_calendar'] ) : ?>
		<div class="tutor-v2-date-range-picker" style="flex-basis: 40%;"></div>
	<?php endif; ?>
</div>
