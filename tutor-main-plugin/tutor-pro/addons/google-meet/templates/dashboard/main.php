<?php
/**
 * Google meet dashboard main page
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\Templates
 */

defined( 'ABSPATH' ) || exit;

use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\Nav;
use TUTOR\Input;
use TUTOR_PRO\Dashboard;
use TutorPro\GoogleMeet\GoogleEvent\GoogleEvent;
use TutorPro\GoogleMeet\GoogleMeet;
use TutorPro\GoogleMeet\Utilities\Utilities;

global $wp_query;

$google_client   = new GoogleEvent();
$delete_modal_id = 'tutor-google-meet-delete-modal';
$edit_modal_id   = 'tutor-google-meet-edit-modal';
$query_vars      = $wp_query->query_vars;
$dashboard_url   = tutor_utils()->tutor_dashboard_url( Dashboard::LIVE_CLASSES_MENU );
$active_tab      = Input::get( 'tab', 'active-meeting' );

$plugin_data = GoogleMeet::meta_data();
$page_tab    = 'meetings';
if ( ! $google_client->is_app_permitted() ) {
	// Filter sub pages.
	Utilities::not_permitted_sub_pages();

	// Only set-api page access enable if app not permitted.
	if ( 'active-meeting' === $active_tab ) {
		$active_tab = 'set-api';
	}
}

if ( 'active-meeting' !== $active_tab && 'expired' !== $active_tab ) {
	$page_tab = $active_tab;
}

$sub_pages                      = Utilities::frontend_tabs_key_value( $dashboard_url, $active_tab );
$template                       = trailingslashit( $plugin_data['views'] . 'pages/frontend' ) . $page_tab . '.php';
$plugin_data['delete_modal_id'] = $delete_modal_id;
$plugin_data['edit_modal_id']   = $edit_modal_id;
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
	<div x-data="<?php printf( "tutorLiveClass('%s', '%s')", esc_attr( $delete_modal_id ), esc_attr( $edit_modal_id ) ); ?>">
		<?php
			tutor_load_template_from_custom_path( $template, $plugin_data );
		?>
	</div>
</div>