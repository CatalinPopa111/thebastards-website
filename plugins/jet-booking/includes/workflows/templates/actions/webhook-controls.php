<cx-vui-input
	v-if="'webhook' === item.actions[ index ].action_id"
	label="<?php esc_html_e( 'URL', 'jet-booking' ); ?>"
	description="<?php _e( 'Enter the webhook URL where event data will be sent.</br> <b>Note:</b> Ensure the URL is valid and accessible.', 'jet-booking' ); // phpcs:ignore ?>"
	:wrapper-css="[ 'equalwidth' ]"
	size="fullwidth"
	:value="item.actions[ index ].webhook_url"
	@on-input-change="setActionProp( index, 'webhook_url', $event.target.value )"
></cx-vui-input>