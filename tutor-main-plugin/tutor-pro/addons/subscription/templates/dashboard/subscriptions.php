<?php
/**
 * Frontend dashboard subscriptions page.
 *
 * @package TutorPro\Subscription
 * @subpackage Templates
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Tutor\Components\EmptyState;
use Tutor\Components\Pagination;
use TUTOR\Dashboard;
use Tutor\Helpers\UrlHelper;
use TUTOR\Input;
use TutorPro\Subscription\Controllers\SubscriptionListController;
use TutorPro\Subscription\Utils;

$subscription_id = Input::get( 'id', 0, Input::TYPE_INT );
if ( $subscription_id ) {
	$template = Utils::template_path( 'dashboard/subscription-details.php' );
	require_once $template;
	return;
}

// Pagination.
$current_page  = max( Input::get( 'current_page', 1, Input::TYPE_INT ), 1 );
$item_per_page = (int) tutor_utils()->get_option( 'pagination_per_page', 10 );
$offset        = ( $item_per_page * $current_page ) - $item_per_page;

$start_date      = Input::get( 'start_date' );
$end_date        = Input::get( 'end_date' );
$selected_filter = Input::get( 'data', 'all' );

$controller         = new SubscriptionListController( false );
$subscription_query = $controller->get_list( $item_per_page, $offset );
$subscriptions      = $subscription_query['results'];
$total_items        = $subscription_query['total_count'];

$page_link = UrlHelper::add_query_params( Dashboard::get_account_page_url( 'billing' ), array( 'tab' => 'subscriptions' ) );
$page_tabs = $controller->tabs_key_value();
foreach ( $page_tabs as $index => $item ) {
	if ( 'trash' === $item['key'] ) {
		unset( $page_tabs[ $index ] );
		break;
	}
}
?>

<div class="tutor-flex tutor-justify-between tutor-items-center tutor-px-6 tutor-py-5 tutor-border-b">
	<?php require_once Utils::template_path( 'dashboard/subscription-filter.php' ); ?>
</div>

<?php
if ( empty( $subscriptions ) ) :
	EmptyState::make()
		->title( 'No Subscriptions Found!' )
		->icon( tutor_utils()->get_themed_svg( 'images/illustrations/subscriptions-empty.svg' ) )
		->render();
else :
	?>
<div class="tutor-flex tutor-flex-column tutor-tabs-content tutor-subscription-history">
	<?php
	foreach ( $subscriptions as $subscription ) :
		include Utils::template_path( 'dashboard/subscription-card.php' );
	endforeach;
	?>
</div>

	<?php
	Pagination::make()
	->attr( 'class', 'tutor-px-6 tutor-py-6 tutor-border-t' )
	->current( $current_page )
	->total( $total_items )
	->limit( $item_per_page )
	->render();
endif;
