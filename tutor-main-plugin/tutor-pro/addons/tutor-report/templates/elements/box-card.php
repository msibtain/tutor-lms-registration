<?php
/**
 * Template parts
 *
 * @package TutorPro\Report
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.9
 */

defined( 'ABSPATH' ) || exit;

?>
<?php if ( is_array( $data ) && count( $data ) ) : ?>
	<div class="tutor-analytics-info-cards">
		<!-- <div class="tutor-flex tutor-gap-5"> -->
		<?php foreach ( $data as $key => $card ) : ?>
			<!-- <div class="tutor-flex-1"> -->
			<?php
			tutor_load_template(
				'dashboard.instructor.analytics.stat-card',
				array(
					'card_title' => $card['title'] ?? '',
					'icon'       => $card['icon'] ?? '',
					'content'    => $card['sub_title'] ?? '',
					'variation'  => $card['variation'] ?? '',
					'value'      => $card['value'] ?? '',
					'icon_size'  => $card['icon_size'] ?? 20,
					'show_graph' => $card['show_graph'] ?? '',
				)
			);
			?>
			<!-- </div> -->
		<?php endforeach; ?>
		<!-- </div> -->
	</div>
<?php endif; ?>
