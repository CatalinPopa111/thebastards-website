<div id='jet-abaf-custom-schedule-meta-box'>
	<cx-vui-switcher
		label="<?php esc_html_e( 'Use Custom Schedule', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'You can use a custom schedule for apartment.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:return-true="true"
		:return-false="false"
		v-model="settings.custom_schedule.use_custom_schedule"
		@input="updateSetting( $event, 'use_custom_schedule' )"
	></cx-vui-switcher>

	<jet-abaf-custom-schedule
		v-if="settings.custom_schedule.use_custom_schedule"
		class="cx-vui-panel"
	></jet-abaf-custom-schedule>
</div>