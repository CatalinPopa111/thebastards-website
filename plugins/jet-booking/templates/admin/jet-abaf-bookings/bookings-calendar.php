<div class="jet-abaf-bookings-calendar">
	<v-calendar :attributes="itemsList" :masks="masks" :first-day-of-week="dayIndex" @update:to-page="changePeriod">
		<template v-slot:day-content="{ day, attributes }">
			<div class="jet-abaf-calendar-day">
				<div class="jet-abaf-calendar-day-number">{{ day.day }}</div>
				<div class="jet-abaf-calendar-day-content">
					<div
						v-for="( attr, index ) in attributes"
						v-if="index < maxItemInCell"
						:key="attr.key"
						class="jet-abaf-calendar-day-booking"
						:data-booking-id="attr.customData.booking_id"
						@click="callPopup( 'info', attr.customData )"
						@mouseenter="mouseEnter"
						@mouseleave="mouseLeave"
					>
						<div class="jet-abaf-booking-data" :class="statusClass( attr.customData.status )">
							<strong>{{ getItemLabel( attr.customData.apartment_id ) }}<span v-if="attr.customData.apartment_unit">, {{ getItemUnitLabel( attr.customData.apartment_id, attr.customData.apartment_unit ) }}</span></strong>
							<span>{{ attr.customData.check_in_date }} - {{ attr.customData.check_out_date }}</span>
						</div>
					</div>
				</div>
				<div class="jet-abaf-calendar-day-more-button" v-if="getRemainingItemCount( attributes )">
					<span @click="showMore( day )">{{ getRemainingItemCount( attributes ) }} <?php esc_html_e( 'more', 'jet-booking' ); ?></span>
				</div>
			</div>
		</template>
	</v-calendar>
</div>
