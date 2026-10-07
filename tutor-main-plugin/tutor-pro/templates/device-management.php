<?php
/**
 * User's logged in device management
 *
 * @package Tutor\Templates
 * @subpackage Dashboard\DeviceManagement
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;

use TUTOR\Icon;
use Tutor\Components\SvgIcon;
use Tutor\Components\Constants\Color;
use TUTOR\User;
use TUTOR_PRO\DeviceManagement;
use Tutor\Components\Button;
use Tutor\Components\EmptyState;
use Tutor\Components\ConfirmationModal;
use Tutor\Components\Popover;
use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\Constants\Positions;
use Tutor\Helpers\DateTimeHelper;


// User's logged-in devices.
$user_id = get_current_user_id();
$devices = DeviceManagement::get_logged_in_devices( $user_id );

$device_count        = is_array( $devices ) ? count( $devices ) : 0;
$current_fingerprint = DeviceManagement::get_current_device_fingerprint();
$current_device      = null;
$filtered_devices    = array();

foreach ( $devices as $device ) {
	$fingerprint = DeviceManagement::get_fingerprint( $device->meta_key );
	if ( $current_fingerprint === $fingerprint ) {
		$current_device = $device;
	} else {
		$filtered_devices[] = $device;
	}
}

if ( ! is_null( $current_device ) ) {
	array_unshift( $filtered_devices, $current_device );
}

?>

<div 
	class="tutor-flex tutor-flex-column tutor-gap-4 tutor-devices-wrapper"
	x-data="tutorDeviceManagement(<?php echo (int) $device_count; ?>)"
>
	<?php if ( is_array( $devices ) && count( $devices ) ) : ?>
		<!-- Header -->
		<div class="tutor-flex tutor-gap-4 tutor-items-center">
			<?php SvgIcon::make()->name( Icon::COMPLETED_FILL )->size( 20 )->color( Color::SUCCESS_PRIMARY )->render(); ?>
			<h5 class="tutor-h5 tutor-sm-text-medium tutor-font-medium tutor-text-secondary" x-html="formattedCountLabel"></h5>
		</div>

		<div class="tutor-devices">
			<?php

			foreach ( $filtered_devices as $device ) :
				$info          = json_decode( $device->meta_value );
				$gmt_offset    = get_option( 'gmt_offset' );
				$utc_timestamp = $info->login_time - ( $gmt_offset * HOUR_IN_SECONDS );

				$datetime_string = gmdate( 'Y-m-d H:i:s', $utc_timestamp );

				$last_active = DateTimeHelper::create( $datetime_string, 'UTC' )
				->set_timezone( User::get_user_timezone_string() )
				->get_readable_diff();
				$location    = trim( "{$info->city}, {$info->country}", ',' );
				$fingerprint = DeviceManagement::get_fingerprint( $device->meta_key );
				if ( ' ' === $location ) {
					$location = __( 'Unknown Location', 'tutor-pro' );
				}

				$is_current = $current_fingerprint === $fingerprint;
				?>
				<div class="tutor-devices-card" data-id="<?php echo esc_attr( $device->umeta_id ); ?>" <?php echo $is_current ? 'data-current="1"' : ''; ?>>
					<div class="tutor-flex tutor-items-start tutor-mt-1">
						<?php
							$icon = DeviceManagement::get_icon( $info->device, $info->os );
							SvgIcon::make()->name( $icon )->size( 20 )->render();
						?>
					</div>
					<div class="tutor-flex tutor-flex-column tutor-gap-4 tutor-flex-1">
						<!-- Os and location -->
						<div class="tutor-small">
							<?php echo esc_html( $info->os ); ?>
							<span class="tutor-icon-secondary">•</span>
							<?php echo esc_html( $location ); ?>
						</div>
						<!-- Browser and this device/session last active time -->
						<div class="tutor-tiny">
							<?php
							if ( $is_current ) {
								echo esc_html( $info->browser );
								?>
								<span class="tutor-icon-secondary">•</span>
								<?php
								printf(
									'<span class="tutor-text-success tutor-font-medium">%s</span>',
									esc_html__( 'This Device', 'tutor-pro' )
								);
							} else {
								echo esc_html( $info->browser );
								?>
								<span class="tutor-icon-secondary">•</span>
								<span class="tutor-text-subdued"><?php echo esc_html( $last_active ); ?></span>
								<?php
							}
							?>
						</div>
					</div>
					<div class="tutor-flex tutor-gap-4 tutor-items-center tutor-self-center">
						<?php
							// Sign out button for desktop.
							Button::make()
								->label( __( 'Sign out', 'tutor-pro' ) )
								->size( Size::X_SMALL )
								->variant( Variant::OUTLINE )
								->attr( 'class', 'tutor-force-sm-hidden' )
								->attr( 'data-sign-out', '' )
								->attr( '@click', 'TutorCore.modal.showModal(\'sing-out-confirm-modal\', { umetaId: \'' . $device->umeta_id . '\', deviceName: \'' . $info->device . '\', os: \'' . $info->os . '\', location: \'' . $location . '\' })' )
								->attr( ':class', '{ \'tutor-btn-loading\': signOutMutation.isPending }' )
								->render();

							// Popover for mobile.
							Popover::make()
								->trigger(
									Button::make()
										->icon( Icon::ELLIPSES )
										->variant( Variant::GHOST )
										->size( Size::X_SMALL )
										->icon_only()
										->attr( 'class', 'tutor-force-hidden tutor-force-sm-flex' )
										->attr( 'data-popover-trigger', '' )
										->attr( 'x-ref', 'trigger' )
										->attr( '@click', 'toggle()' )
										->get()
								)
								->placement( Positions::LEFT )
								->menu_item(
									array(
										'tag'     => 'button',
										'content' => __( 'Sign out', 'tutor-pro' ),
										'attr'    => array(
											'@click' => 'hide(); TutorCore.modal.showModal(\'sing-out-confirm-modal\', { umetaId: \'' . $device->umeta_id . '\', deviceName: \'' . $info->device . '\', os: \'' . $info->os . '\', location: \'' . $location . '\' })',
										),
									)
								)
								->menu_min_width( '74px' )
								->render();
						?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="tutor-sm-hidden">
				<div class="tutor-devices-signout-all">
					<?php
						Button::make()
							->label( __( 'Sign Out of All Sessions', 'tutor-pro' ) )
							->size( Size::X_SMALL )
							->variant( Variant::LINK_DESTRUCTIVE )
							->attr( '@click', 'TutorCore.modal.showModal(\'sing-out-all-confirm-modal\')' )
							->attr( ':class', '{ \'tutor-btn-loading\': clearAllSessionsMutation.isPending }' )
							->render();
					?>
				</div>
			</div>
		</div>

		<!-- Global Sign Out Button. -->
		<div class="tutor-devices-signout-all-mobile tutor-hidden tutor-sm-block">
			<?php
			// Mobile version (Full width button at bottom).
			Button::make()
				->label( __( 'Sign Out of All Sessions', 'tutor-pro' ) )
				->size( Size::LARGE )
				->variant( Variant::DESTRUCTIVE_SOFT )
				->attr( 'class', 'tutor-btn-block' )
				->attr( '@click', 'TutorCore.modal.showModal(\'sing-out-all-confirm-modal\')' )
				->attr( ':class', '{ \'tutor-btn-loading\': clearAllSessionsMutation.isPending }' )
				->render();
			?>
		</div>

		<div x-cloak>
			<?php
				ConfirmationModal::make()
					->id( 'sing-out-confirm-modal' )
					->title( __( 'Sign out From This Device?', 'tutor-pro' ) )
					->icon( tutor_utils()->get_themed_svg( 'images/illustrations/signout.svg' ), 80, 80, ConfirmationModal::ICON_TYPE_HTML )
					->message(
						sprintf(
							// translators: %1$s: device name, %2$s: device location.
							__( 'You\'r about to sign out form %1$s (%2$s) in %3$s. You\'ll need to sign in again to access your account on this device.', 'tutor-pro' ),
							'<span class="tutor-font-medium" x-text="payload?.deviceName || \'\'"></span>',
							'<span class="tutor-font-medium" x-text="payload?.os || \'\'"></span>',
							'<span class="tutor-font-medium" x-text="payload?.location || \'\'"></span>'
						)
					)
					->confirm_handler( 'handleSignOut(payload?.umetaId)' )
					->mutation_state( 'signOutMutation' )
					->confirm_text( __( 'Sign Out', 'tutor-pro' ) )
					->cancel_text( __( 'Cancel', 'tutor-pro' ) )
					->render();
			?>
		</div>
		<div x-cloak>
			<?php
				ConfirmationModal::make()
					->id( 'sing-out-all-confirm-modal' )
					->title( __( 'Sign out of All Devices?', 'tutor-pro' ) )
					->icon( tutor_utils()->get_themed_svg( 'images/illustrations/signout-all.svg' ), 80, 80, ConfirmationModal::ICON_TYPE_HTML )
					->message(
						sprintf(
							// translators: %s: highlighted text "all devices".
							__( 'This will sign you out from %s, including phones, tablets, and computers. You\'ll be logged out everywhere except this device.', 'tutor-pro' ),
							sprintf(
								'<span class="tutor-font-medium">%s</span>',
								esc_html__( 'all devices', 'tutor-pro' )
							)
						)
					)
					->confirm_handler( 'handleClearAllSessions()' )
					->mutation_state( 'clearAllSessionsMutation' )
					->confirm_text( __( 'Sign Out Everywhere', 'tutor-pro' ) )
					->cancel_text( __( 'Cancel', 'tutor-pro' ) )
					->render();
			?>
		</div>
	<?php else : ?>
		<div class="tutor-card">
		<?php
			EmptyState::make()
				->title( __( 'No Active Sessions', 'tutor-pro' ) )
				->subtitle( __( 'You have no active sessions. Please sign in to your account to manage your active sessions.', 'tutor-pro' ) )
				->render();
		?>
		</div>
	<?php endif; ?>
</div>
