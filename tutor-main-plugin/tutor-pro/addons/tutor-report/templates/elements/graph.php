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

$data = $data ?? array();
?>

<?php if ( is_admin() && ! wp_doing_ajax() ) : ?>
	<div class="tutor-analytics-graph tutor-mb-48">
	<?php if ( $data ) : ?>
		<div class="tutor-nav-tabs-container">
			<div class="tutor-nav tutor-nav-tabs">
				<?php foreach ( $data as $key => $value ) : ?>
					<?php $active = $value['active']; ?>
					<div class="tutor-nav-item">
						<div class="tutor-nav-link<?php echo esc_attr( $active ); ?>" data-tutor-nav-target="<?php echo esc_attr( $value['data_attr'] ); ?>" role="button">
							<div class="tutor-fs-7 tutor-color-secondary">
								<?php echo esc_html( $value['tab_title'] ); ?>
							</div>
							<div class="tutor-fs-5 tutor-fw-bold tutor-color-black tutor-mt-4">
								<?php if ( $value['price'] ) : ?>
									<?php echo $value['tab_value'] ? wp_kses( tutor_utils()->tutor_price( $value['tab_value'] ), tutor_price_allowed_html() ) : '-'; ?>
								<?php else : ?>
									<?php echo esc_html( $value['tab_value'] ? $value['tab_value'] : '-' ); ?>
								<?php endif; ?>    
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

	<!-- Graph For Front-End -->
	<?php else : ?>
	<div class="tutor-analytics-graph">
		<?php
		if ( $data ) :
			$page_tab_items = array();

			foreach ( $data as $key => $value ) {

				$page_tab_items[] = array(
					'id'        => $value['data_attr'],
					'label'     => $value['tab_title'],
					'sub_label' => wp_kses( $value['tab_value'], tutor_price_allowed_html() ) ?? '-',
					'content'   => $value['graph_data'] ?? '',
				);
			}
			?>

			<div 
				class="tutor-analytics-graph-tab"
				x-data="tutorTabs({
					tabs: <?php echo esc_attr( tutor_json_encode( $page_tab_items ) ); ?>,
					orientation: 'horizontal',
					defaultTab: 'ta_total_earnings',
				})"
			>
				<div x-ref="tablist" role="tablist" aria-orientation="horizontal" class="tutor-analytics-graph-tab-items">
					<template x-for="tab in tabs" :key="tab.id">
						<button
							type="button"
							role="tab"
							class="tutor-analytics-graph-tab-items-button"
							x-bind:aria-selected="isActive(tab.id)"
							:class='getTabClass(tab)'
							@click="selectTab(tab.id)"
							style="height:78px;"
						>
							<div class="tutor-flex tutor-flex-column tutor-items-start">
								<span x-text="tab.label" class="tutor-text-tiny"></span>
								<span x-html="tab.sub_label" class="tutor-medium tutor-font-bold"></span>
							</div>
						</button>
					</template>
				</div>	

				<div class="tutor-tabs-content">
					<template x-for="tab in tabs" :key="tab.id">
						<div
							class="tutor-tab-panel"
							role="tabpanel"
							x-show="activeTab === tab.id"
							x-cloak
						>			
							<canvas x-data="tutorOverviewChart(tab.content)" x-ref="canvas">								
							</canvas>						
						</div>
					</template>
				</div>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
