<div class="jet-abaf-bookings-timeline">
	<cx-vui-component-wrapper
		:wrapper-css="[ 'jet-abaf-gc-datepicker' ]"
		label="<?php esc_html_e('Period', 'jet-booking'); ?>"
	>
		<vuejs-datepicker
			input-class="cx-vui-input size-default"
			:format="dateFormat"
			minimum-view="month"
			v-model="selectedDate"
			@selected="changePeriod"
		></vuejs-datepicker>
	</cx-vui-component-wrapper>

	<v-gantt-chart
		:startTime="startTime"
		:endTime="endTime"
		cellWidth="75"
		titleWidth="0"
		scale="1440"
		:datas="itemsList"
		hideYScrollBar="true"
		ref="gantt"
	>
		<template v-slot:timeline="{ day , getTimeScales }">
			{{ day.format( 'DD MMM' ) }}
		</template>

		<template v-slot:block="{ data, item }">
			<div class="jet-abaf-gantt-block-item" :class="statusClass( item.customData.status )" @click="callPopup( 'info', item.customData )">
				{{ getItemLabel( item.customData.apartment_id ) }}
				<template v-if="item.customData.apartment_unit"> | {{ getItemUnitLabel( item.customData.apartment_id, item.customData.apartment_unit ) }}</template>
			</div>
		</template>
	</v-gantt-chart>
</div>
