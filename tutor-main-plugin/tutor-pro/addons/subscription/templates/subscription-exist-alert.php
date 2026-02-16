<?php
/**
 * Subscription exist alert.
 *
 * @package TutorPro\Addons
 * @subpackage Subscriptions\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

$subscription_status = $data['subscription_status'] ?? 'pending';
$plan_name           = $data['plan_name'] ?? '';
$subscription_url    = $data['subscription_url'] ?? '';
?>
<div class="tutor-alert tutor-info">
	<div class="tutor-alert-text">
		<span class="tutor-alert-icon tutor-fs-4 tutor-icon-circle-info tutor-mr-12"></span>
		<div class="tutor-w-100 tutor-d-flex tutor-align-center tutor-justify-between">
			<span>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: status, %s:strong tag start, %s: plan name, %s: strong tag close */
						__( 'You have a %1$s subscription of %2$s %3$s %4$s', 'tutor-pro' ),
						tutor_utils()->translate_dynamic_text( $subscription_status, true ),
						'<strong>',
						$plan_name,
						'</strong>'
					)
				)
				?>
			</span>
			<a	href="<?php echo esc_url( $subscription_url ); ?>" 
				class="tutor-btn tutor-btn-sm tutor-btn-primary">
				<?php esc_html_e( 'View Subscription', 'tutor-pro' ); ?>
			</a>
		</div>
	</div>
</div>
