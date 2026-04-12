<cx-vui-select
	v-if="'booking-status-changed' === item.event"
	label="<?php esc_html_e( 'Status', 'jet-booking' ); ?>"
	description="<?php esc_html_e( 'Trigger event if bookings status changed to selected status.', 'jet-booking' ); ?>"
	:wrapper-css="[ 'equalwidth' ]"
	size="fullwidth"
	:options-list="<?php echo htmlspecialchars( json_encode( $statuses ) ); // phpcs:ignore ?>"
	:value="item.status"
	@input="updateItem( $event, 'status' )"
></cx-vui-select>