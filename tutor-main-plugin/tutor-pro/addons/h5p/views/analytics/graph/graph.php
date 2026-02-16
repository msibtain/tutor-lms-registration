<?php
/**
 * H5P Analytics Graph.
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

<div class="tutor-analytics-graph tutor-mb-48">
	<?php if ( $data ) : ?>
		<div class="tutor-nav-tabs-container">
			<div class="tutor-nav tutor-nav-tabs">
				<?php foreach ( $data as $key => $value ) : ?>
					<?php $active = $value['active']; ?>
					<div class="tutor-nav-item tutor-p-12">
						<div class="tutor-nav-link<?php echo esc_attr( $active ); ?>" data-tutor-nav-target="<?php echo esc_attr( $value['data_attr'] ); ?>" role="button">
							<div class="tutor-fs-7 tutor-color-secondary">
								<?php echo esc_html( $value['tab_title'] ); ?>
							</div>
							<div class="tutor-fs-5 tutor-fw-bold tutor-color-black tutor-mt-4">
								<?php echo $value['tab_value'] ? esc_html( $value['tab_value'] ) : '-'; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="tutor-tab">
				<?php foreach ( $data as $key => $value ) : ?>
					<?php $active = $value['active']; ?>
					<div class="tutor-tab-item<?php echo esc_attr( $active ); ?>" id="<?php echo esc_attr( $value['data_attr'] ); ?>">
						<div class="tutor-py-24 tutor-px-32">
							<div class="tutor-fs-5 tutor-fw-medium tutor-color-black tutor-mb-24">
								<?php echo esc_html( $value['content_title'] ); ?>
							</div>
							<canvas id="<?php echo esc_attr( $value['data_attr'] . '_canvas' ); ?>"></canvas>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
