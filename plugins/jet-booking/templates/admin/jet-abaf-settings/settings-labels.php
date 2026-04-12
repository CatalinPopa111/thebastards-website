<div>
	<cx-vui-switcher
		label="<?php esc_html_e( 'Use custom labels', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Check this to change default check-in/check-out calendar field labels.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.use_custom_labels"
		@input="updateSetting( $event, 'use_custom_labels' )"
	></cx-vui-switcher>

	<template v-if="advancedSettings.use_custom_labels">
		<cx-vui-input
			label="<?php esc_html_e( 'Excluded dates', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Tooltip text for already booked dates. <i> Default: Sold out</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_booked"
			@on-input-change="updateSetting( $event.target.value, 'labels_booked' )"
		></cx-vui-input>

		<cx-vui-input
			v-if="'per_nights' === advancedSettings.booking_period && advancedSettings.allow_checkout_only"
			label="<?php esc_html_e( 'Only checkout allowed', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Tooltip for dates when only checkout is allowed. <i>Default: Only checkout</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_only_checkout"
			@on-input-change="updateSetting( $event.target.value, 'labels_only_checkout' )"
		></cx-vui-input>
		<cx-vui-component-wrapper
			v-else
			label="<?php esc_html_e( 'Only checkout allowed', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Tooltip for dates when only checkout is allowed. <i>Default: Only checkout</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
		>
			<i><?php esc_html_e( 'This option is allowed only for \'Per Nights\' bookings with \'Allow checkout only days\' option enabled.', 'jet-booking' ) ?></i>
		</cx-vui-component-wrapper>

		<cx-vui-input
			label="<?php esc_html_e( 'Selected dates', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label before selected dates range. <i>Default: Chosen</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_selected"
			@on-input-change="updateSetting( $event.target.value, 'labels_selected' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Days', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Tooltip label after days number in per day booking period. <i>Default: Days</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_days"
			@on-input-change="updateSetting( $event.target.value, 'labels_days' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Nights', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Tooltip label after nights number in per night booking period. <i>Default: Nights</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_nights"
			@on-input-change="updateSetting( $event.target.value, 'labels_nights' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Apply button', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label for apply button. <i>Default: Close</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_apply"
			@on-input-change="updateSetting( $event.target.value, 'labels_apply' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Clear button', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label for clear button. <i>Default: Clear</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_clear"
			@on-input-change="updateSetting( $event.target.value, 'labels_clear' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Monday', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label/translation of Monday. <i>Default: Mon</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_week_1"
			@on-input-change="updateSetting( $event.target.value, 'labels_week_1' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Tuesday', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label/translation of Tuesday. <i>Default: Tue</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_week_2"
			@on-input-change="updateSetting( $event.target.value, 'labels_week_2' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Wednesday', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label/translation of Wednesday. <i>Default: Wed</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_week_3"
			@on-input-change="updateSetting( $event.target.value, 'labels_week_3' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Thursday', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label/translation of Thursday. <i>Default: Thu</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_week_4"
			@on-input-change="updateSetting( $event.target.value, 'labels_week_4' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Friday', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label/translation of Friday. <i>Default: Fri</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_week_5"
			@on-input-change="updateSetting( $event.target.value, 'labels_week_5' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Saturday', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label/translation of Saturday. <i>Default: Sat</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_week_6"
			@on-input-change="updateSetting( $event.target.value, 'labels_week_6' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Sunday', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label/translation of Sunday. <i>Default: Sun</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_week_7"
			@on-input-change="updateSetting( $event.target.value, 'labels_week_7' )"
		></cx-vui-input>

		<cx-vui-textarea
			label="<?php esc_html_e( 'Month names', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Comma-separated list of month names. <i>Default: January, February, March, April, May, June, July, August, September, October, November, December</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_month_name"
			@on-input-change="updateSetting( $event.target.value, 'labels_month_name' )"
		></cx-vui-textarea>

		<cx-vui-input
			label="<?php esc_html_e( 'Past', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label for past dates shortcuts. <i>Default: Past</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_past"
			@on-input-change="updateSetting( $event.target.value, 'labels_past' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Following', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label for following dates shortcuts. <i>Default: Following</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_following"
			@on-input-change="updateSetting( $event.target.value, 'labels_following' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Previous', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label for previous dates shortcuts. <i>Default: Previous</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_previous"
			@on-input-change="updateSetting( $event.target.value, 'labels_previous' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Previous week', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Previous week shortcuts label. <i>Default: Week</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_prev_week"
			@on-input-change="updateSetting( $event.target.value, 'labels_prev_week' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Previous month', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Previous month shortcuts label. <i>Default: Month</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_prev_month"
			@on-input-change="updateSetting( $event.target.value, 'labels_prev_month' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Previous year', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Previous year shortcuts label. <i>Default: Year</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_prev_year"
			@on-input-change="updateSetting( $event.target.value, 'labels_prev_year' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Next', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Label for next dates shortcuts. <i>Default: Next</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_next"
			@on-input-change="updateSetting( $event.target.value, 'labels_next' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Next week', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Next week shortcuts label. <i>Default: Week</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_next_week"
			@on-input-change="updateSetting( $event.target.value, 'labels_next_week' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Next month', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Next month shortcuts label. <i>Default: Month</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_next_month"
			@on-input-change="updateSetting( $event.target.value, 'labels_next_month' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Next year', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Next year shortcuts label. <i>Default: Year</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_next_year"
			@on-input-change="updateSetting( $event.target.value, 'labels_next_year' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Max days warning notice', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Notification text of exceeding the maximum number of days. <i>Default: Date range should not be more than %d days (%d will be replaced with days count)</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_less_than"
			@on-input-change="updateSetting( $event.target.value, 'labels_less_than' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Min days warning notice', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Notification text of not reaching the minimum number of days. <i>Default: Date range should not be less than %d days (%d will be replaced with days count)</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_more_than"
			@on-input-change="updateSetting( $event.target.value, 'labels_more_than' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Min days notice', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Notification text about the minimum days number in the date range. <i>Default: Please select a date range longer than %d days (%d will be replaced with days count)</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_more"
			@on-input-change="updateSetting( $event.target.value, 'labels_more' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Single day notice', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Notification text about choose a single date instead of a date range. <i>Default: Please select a date</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_single"
			@on-input-change="updateSetting( $event.target.value, 'labels_single' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Max days notice', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Notification text about the maximum days number in the date range. <i>Default: Please select a date range less than %d days (%d will be replaced with days count)</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_less"
			@on-input-change="updateSetting( $event.target.value, 'labels_less' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Days range notice', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Notification text about minimum and maximum days number in the date range. <i>Default: Please select a date range between %d and %d days (%d will be replaced with days count)</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_range"
			@on-input-change="updateSetting( $event.target.value, 'labels_range' )"
		></cx-vui-input>

		<cx-vui-input
			label="<?php esc_html_e( 'Default notice', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Default notification text. <i>Default: Please select a date range</i>', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="advancedSettings.labels_default"
			@on-input-change="updateSetting( $event.target.value, 'labels_default' )"
		></cx-vui-input>
	</template>
</div>