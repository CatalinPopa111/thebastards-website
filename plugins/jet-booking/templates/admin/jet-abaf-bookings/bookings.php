<div :class="[ 'jet-abaf-wrap', { 'jet-abaf-loading': isLoading } ]">
	<header class="jet-abaf-header">
		<h1 class="jet-abaf-title">
			<?php esc_html_e( 'Bookings', 'jet-booking' ); ?>
		</h1>

		<jet-abaf-add-new-booking v-if="isSet"></jet-abaf-add-new-booking>
		<jet-abaf-bookings-view v-if="isSet"></jet-abaf-bookings-view>
	</header>

	<div v-if="isSet">
		<jet-abaf-bookings-filter></jet-abaf-bookings-filter>
		<component :is="currentView"></component>
		<jet-abaf-popup></jet-abaf-popup>
	</div>
	<div v-else class="cx-vui-panel">
		<jet-abaf-go-to-setup></jet-abaf-go-to-setup>
	</div>
</div>