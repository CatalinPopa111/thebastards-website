<div>
	<cx-vui-switcher
		label="<?php esc_html_e( 'Hide DB columns manager', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Check this to hide the columns manager option to prevent accidental DB changes.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.hide_columns_manager"
		@input="updateSetting( $event, 'hide_columns_manager' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Enable iCal synchronization', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Check this to allow export your bookings into iCal format and synchronize all your data with external calendars in iCal format.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.ical_synch"
		@input="updateSetting( $event, 'ical_synch' )"
	></cx-vui-switcher>

	<cx-vui-select
		label="<?php esc_html_e( 'Calendar sync interval', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Select interval between synchronizing calendars.', 'jet-booking' ); ?>"
		:options-list="cronSchedules"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="advancedSettings.synch_interval"
		@input="updateSetting( $event, 'synch_interval' )"
		v-if="advancedSettings.ical_synch"
	></cx-vui-select>

	<cx-vui-component-wrapper
		v-if="advancedSettings.ical_synch"
		label="<?php esc_html_e( 'Calendar sync start', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Start calendar synchronization from this time.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
	>
		<div style="display: flex; gap: 5px; align-items: center;">
			<cx-vui-select
				:options-list="getInterval( 23 )"
				:value="advancedSettings.synch_interval_hours"
				@input="updateSetting( $event, 'synch_interval_hours' )"
				:prevent-wrap="true"
				:size="'fullwidth'"
				style="width: 55px;"
			></cx-vui-select>
			<span>:</span>
			<cx-vui-select
				:options-list="getInterval( 59 )"
				:value="advancedSettings.synch_interval_mins"
				@input="updateSetting( $event, 'synch_interval_mins' )"
				:prevent-wrap="true"
				:size="'fullwidth'"
				style="width: 55px;"
			></cx-vui-select>
			<span>HH:MM</span>
		</div>
	</cx-vui-component-wrapper>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Automatically switch bookings statuses', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Check this to allow automatically change in progress statuses: \'Pending\', \'On hold\', \'Processing\' to invalid statuses: \'Cancelled\', \'Failed\' or temporary status: \'Created\' after selected period of time.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.switch_status"
		@input="updateSetting( $event, 'switch_status' )"
	></cx-vui-switcher>

	<cx-vui-select
		v-if="advancedSettings.switch_status"
		label="<?php esc_html_e( 'Switch interval', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Select interval between switching bookings statuses.', 'jet-booking' ); ?>"
		:options-list="cronSchedules"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="advancedSettings.switch_status_interval"
		@input="updateSetting( $event, 'switch_status_interval' )"
	></cx-vui-select>

	<cx-vui-f-select
		v-if="advancedSettings.switch_status"
		label="<?php esc_html_e( 'Target Status(es)', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Select the specific status or statuses that will be changed.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'processing',
				label: '<?php esc_html_e( 'Processing', 'jet-booking' ); ?>',
			},
			{
				value: 'on-hold',
				label: '<?php esc_html_e( 'On hold', 'jet-booking' ); ?>',
			},
			{
				value: 'pending',
				label: '<?php esc_html_e( 'Pending', 'jet-booking' ); ?>',
			},
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:multiple="true"
		:value="advancedSettings.switch_status_from"
		@input="updateSetting( $event, 'switch_status_from' )"
	></cx-vui-f-select>

	<cx-vui-select
		v-if="advancedSettings.switch_status"
		label="<?php esc_html_e( 'New Status', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Select the new status to which the selected target status(es) will be changed.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'cancelled',
				label: '<?php esc_html_e( 'Cancelled', 'jet-booking' ); ?>',
			},
			{
				value: 'failed',
				label: '<?php esc_html_e( 'Failed', 'jet-booking' ); ?>',
			},
			{
				value: 'created',
				label: '<?php esc_html_e( 'Created', 'jet-booking' ); ?>',
			},
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="advancedSettings.switch_status_to"
		@input="updateSetting( $event, 'switch_status_to' )"
	></cx-vui-select>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Automatically remove temporary bookings', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Check this to allow automatically remove bookings from database with temporary status: \'Created\' after selected period of time.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.remove_temporary_bookings"
		@input="updateSetting( $event, 'remove_temporary_bookings' )"
	></cx-vui-switcher>

	<cx-vui-select
		v-if="advancedSettings.remove_temporary_bookings"
		label="<?php esc_html_e( 'Remove interval', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Select interval between removing bookings with temporary status.', 'jet-booking' ); ?>"
		:options-list="cronSchedules"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="advancedSettings.remove_interval"
		@input="updateSetting( $event, 'remove_interval' )"
	></cx-vui-select>

	<cx-vui-component-wrapper
		label="<?php esc_html_e( 'Booking modification deadline', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Specify a modification time limit before the start of the reservation. Once this deadline passes, modification requests will no longer be accepted.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
	>
		<div style="display: flex; gap: 5px;">
			<cx-vui-input
				type="number"
				min="1"
				:value="advancedSettings.modification_limit"
				@on-input-change="updateSetting( $event.target.value, 'modification_limit' )"
				:prevent-wrap="true"
				style="width: 55px;"
			></cx-vui-input>

			<cx-vui-select
				:options-list="[
					{
						value: 'day',
						label: '<?php esc_html_e( 'Day(s)', 'jet-booking' ); ?>'
					},
					{
						value: 'week',
						label: '<?php esc_html_e( 'Week(s)', 'jet-booking' ); ?>'
					},
					{
						value: 'month',
						label: '<?php esc_html_e( 'Month(s)', 'jet-booking' ); ?>'
					}
				]"
				:value="advancedSettings.modification_unit"
				@input="updateSetting( $event, 'modification_unit' )"
				:prevent-wrap="true"
				:size="'fullwidth'"
				style="width: 100px;"
			></cx-vui-select>
		</div>
	</cx-vui-component-wrapper>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Booking cancellation', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Check this if the booking can be cancelled by the customer after it has been reserved. A refund will not be sent automatically.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.booking_cancellation"
		@input="updateSetting( $event, 'booking_cancellation' )"
	></cx-vui-switcher>

	<cx-vui-component-wrapper
		v-if="advancedSettings.booking_cancellation"
		label="<?php esc_html_e( 'Cancellation deadline', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Specify a cancellations time limit before the start of the reservation. Once this deadline passes, cancellation requests will no longer be accepted.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
	>
		<div style="display: flex; gap: 5px;">
			<cx-vui-input
				type="number"
				min="1"
				:value="advancedSettings.cancellation_limit"
				@on-input-change="updateSetting( $event.target.value, 'cancellation_limit' )"
				:prevent-wrap="true"
				style="width: 55px;"
			></cx-vui-input>

			<cx-vui-select
				:options-list="[
				{
					value: 'day',
					label: '<?php esc_html_e( 'Day(s)', 'jet-booking' ); ?>'
				},
				{
					value: 'week',
					label: '<?php esc_html_e( 'Week(s)', 'jet-booking' ); ?>'
				},
				{
					value: 'month',
					label: '<?php esc_html_e( 'Month(s)', 'jet-booking' ); ?>'
				}
			]"
				:value="advancedSettings.cancellation_unit"
				@input="updateSetting( $event, 'cancellation_unit' )"
				:prevent-wrap="true"
				:size="'fullwidth'"
				style="width: 100px;"
			></cx-vui-select>
		</div>
	</cx-vui-component-wrapper>

	<cx-vui-component-wrapper
		label="<?php esc_html_e( 'Hide Setup Wizard', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Enable the toggle to hide Set Up page and avoid unnecessary plugin resets.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
	>
		<cx-vui-switcher
			:prevent-wrap="true"
			:value="advancedSettings.hide_setup"
			@input="updateSetting( $event, 'hide_setup' )"
		></cx-vui-switcher>

		<div v-if="! advancedSettings.hide_setup" class="cx-vui-component__meta" style="margin-top: 10px;">
			<a class="jet-abaf-help-link" href="https://crocoblock.com/knowledge-base/jetbooking/jetbooking-how-to-setup-booking-with-wizard-set-up/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help" target="_blank">
				<span class="dashicons dashicons-editor-help"></span>
				<?php esc_html_e( 'What is this and how it works?', 'jet-booking' ); ?>
			</a>
		</div>
	</cx-vui-component-wrapper>
</div>