<div>
	<jet-abaf-settings-common-config
		:settings="settings"
	></jet-abaf-settings-common-config>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Clear button', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked, clear button in the date-range picker filed will show.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="configurationSettings.show_clear_button"
		@input="updateSetting( $event, 'show_clear_button' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Month select', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked, you can quickly change month by clicking on month name.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="configurationSettings.month_select"
		@input="updateSetting( $event, 'month_select' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Year select', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked, you can quickly change year by clicking on year number.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="configurationSettings.year_select"
		@input="updateSetting( $event, 'year_select' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Show price in calendar', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked, each date in the date-range picker will show its price.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="configurationSettings.show_calendar_price"
		@input="updateSetting( $event, 'show_calendar_price' )"
	></cx-vui-switcher>

	<div class="notice notice-info" style="margin: 20px 0; padding: 10px;">
		<span class="dashicons dashicons-info-outline"></span>
		<?php esc_html_e( 'This section can also be edited through its corresponding front-end widget.', 'jet-booking' ); // phpcs:ignore ?>
	</div>
</div>
