<div class="jet-abaf-wrap">
	<h1 class="cs-vui-title">
		<?php esc_html_e( 'Booking Settings', 'jet-booking' ); ?>
	</h1>

	<div class="cx-vui-panel">
		<cx-vui-tabs layout="vertical" :value="initialTab">
			<template v-for="( panel, key ) in settingPanels">
				<cx-vui-tabs-panel v-if="panel.capability" :name="key" :label="panel.label" :key="key">
					<keep-alive>
						<component
							:is="`jet-abaf-settings-${key}`"
							:settings="settings"
							@force-update="onUpdateSettings( $event, true )"
						></component>
					</keep-alive>
				</cx-vui-tabs-panel>
			</template>
		</cx-vui-tabs>
	</div>
</div>