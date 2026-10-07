<?php
/**
 * Zoom Addon - main template.
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;

use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\Nav;
use TUTOR\Input;
use TUTOR_PRO\Dashboard;
use TUTOR_ZOOM\Zoom;

$current_sub_page = Input::get( 'tab', 'active' );
$delete_modal_id  = 'tutor-zoom-delete-modal';
$edit_modal_id    = 'tutor-zoom-edit-modal';
$check_api        = tutor_zoom_check_api_connection();
$dashboard_url    = tutor_utils()->tutor_dashboard_url( Dashboard::LIVE_CLASSES_MENU );
$zoom_obj         = new Zoom( false );

// Prepare sub page list.
$sub_pages = $zoom_obj->get_subpage_list( $dashboard_url, $current_sub_page );

if ( 'active' === $current_sub_page || 'expired' === $current_sub_page ) {
	$current_sub_page = 'meetings';
}

// Remove meeting list page if api key not connected.
if ( ! $check_api ) {
	unset( $sub_pages['meetings'] );
	unset( $sub_pages['expired'] );
}

$base_path = TUTOR_ZOOM()->path . 'views/pages/frontend/';
?>

<div>
	<div class="tutor-flex tutor-items-center tutor-justify-between tutor-px-6 tutor-pt-4 tutor-border-b">
		<?php
			Nav::make()
				->items( $sub_pages )
				->size( Size::SMALL )
				->variant( Variant::SECONDARY )
				->render();
		?>
	</div>
	<div x-data="<?php printf( "tutorZoomLiveClass('%s', '%s')", esc_attr( $delete_modal_id ), esc_attr( $edit_modal_id ) ); ?>">
		<?php
		$current_page = $base_path . $current_sub_page . '.php';
		if ( file_exists( $current_page ) ) {
			tutor_load_template_from_custom_path(
				$current_page,
				array(
					'check_api'       => $check_api,
					'dashboard_url'   => $dashboard_url,
					'zoom_obj'        => $zoom_obj,
					'delete_modal_id' => $delete_modal_id,
					'edit_modal_id'   => $edit_modal_id,
				)
			);
		}
		?>
	</div>
</div>