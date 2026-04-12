<div>
	<cx-vui-component-wrapper
		label="<?php esc_html_e( 'Enable Workflows', 'jet-booking' ); ?>"
		description="<?php _e( 'Enable/disable plugin workflows. </br><b>Note:</b> New workflow item will affect only bookings created after adding this item.', 'jet-booking' ); // phpcs:ignore ?>"
		:wrapper-css="[ 'equalwidth' ]"
	>
		<cx-vui-switcher
			:prevent-wrap="true"
			:value="settings.enable_workflows"
			@input="updateSetting( $event, 'enable_workflows' )"
		></cx-vui-switcher>

		<div class="cx-vui-component__meta" style="margin-top: 10px;">
			<a class="jet-abaf-help-link" href="https://crocoblock.com/knowledge-base/jetbooking/creating-automatic-notification-system-with-jetbooking/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help" target="_blank">
				<span class="dashicons dashicons-editor-help"></span>
				<?php esc_html_e( 'What is this and how it works?', 'jet-booking' ); ?>
			</a>
		</div>
	</cx-vui-component-wrapper>

	<jet-abaf-settings-workflow-item
		v-for="( item, index ) in workflows"
		:key="item.hash"
		v-model="workflows[ index ]"
		@delete="deleteWorkflowItem( index )"
	></jet-abaf-settings-workflow-item>

	<cx-vui-button button-style="accent" size="mini" @click="newWorkflowItem()">
		<template slot="label">
			<?php esc_html_e( '+ New Workflow Item', 'jet-booking' ); ?>
		</template>
	</cx-vui-button>

	<div v-if="'plain' === settings.booking_mode" class="notice notice-warning" style="margin: 20px 0; padding: 10px; line-height: 20px;">
		<?php _e( '<b>Note:</b> To ensure proper functionality, please create and configure the email field in the booking form.', 'jet-booking' ); // phpcs:ignore ?>
	</div>
</div>