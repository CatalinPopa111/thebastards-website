<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div class="jet-abaf-wrap">
	<jet-abaf-calendars-list v-if="isSet"></jet-abaf-calendars-list>

	<div v-else class="cx-vui-panel">
		<jet-abaf-go-to-setup></jet-abaf-go-to-setup>
	</div>
</div>