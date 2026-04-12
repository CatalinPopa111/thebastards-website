<cx-vui-popup
	:class="[ 'jet-abaf-popup', { 'jet-abaf-submitting': submitting } ]"
	v-model="isShow"
	body-width="500px"
	:footer="false"
	@on-cancel="cancelPopup"
>
	<div slot="title" class="cx-vui-subtitle">
		<template v-if="'info' === popUpState">
			<?php esc_html_e( 'Booking Details:', 'jet-booking' ); ?>
		</template>
		<template v-else-if="'update' === popUpState">
			<?php esc_html_e( 'Edit Booking:', 'jet-booking' ); ?>
		</template>
		<template v-else-if="'delete' === popUpState">
			<?php esc_html_e( 'Are you sure? Deleted booking can\'t be restored.', 'jet-booking' ); ?>
		</template>
	</div>

	<div
		v-if="'update' === popUpState && overlappingBookings"
		slot="content"
		class="jet-abaf-bookings-error"
		v-html="overlappingBookings"
	></div>

	<div slot="content" class="jet-abaf-details">
		<template v-if="'info' === popUpState || 'update' === popUpState">
			<div class="jet-abaf-details__field jet-abaf-details__booking">
				<div class="jet-abaf-details__booking-id">
					<div class="jet-abaf-details__label"><?php esc_html_e( 'Booking ID:', 'jet-booking' ); ?></div>
					<div class="jet-abaf-details__content">{{ currentItem.booking_id }}</div>
				</div>

				<div class="jet-abaf-details__booking-order-id" v-if="currentItem.order_id">
					<div class="jet-abaf-details__label"><?php esc_html_e( 'Order ID:', 'jet-booking' ); ?></div>
					<div class="jet-abaf-details__content">
						<?php if ( jet_abaf()->tools->can_edit_order() ) : ?>
							<a :href="getOrderLink( currentItem.order_id )" target="_blank">#{{ currentItem.order_id }}</a>
						<?php else : ?>
							#{{ currentItem.order_id }}
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="jet-abaf-details__field jet-abaf-details__field-status">
				<div class="jet-abaf-details__label"><?php esc_html_e( 'Status:', 'jet-booking' ); ?></div>
				<div class="jet-abaf-details__content">
					<span v-if="'info' === popUpState" :class="statusClass( currentItem.status )">{{ statuses[ currentItem.status ] }}</span>
					<select v-else-if="'update' === popUpState" v-model="currentItem.status">
						<option v-for="( label, value ) in statuses" :value="value" :key="value">{{ label }}</option>
					</select>
				</div>
			</div>

			<div class="jet-abaf-details__field jet-abaf-details__field-apartment-id">
				<div class="jet-abaf-details__label"><?php esc_html_e( 'Booking Item:', 'jet-booking' ); ?></div>
				<div class="jet-abaf-details__content">
					<template v-if="'info' === popUpState">{{ getItemLabel( currentItem.apartment_id ) }}</template>
					<select v-else-if="'update' === popUpState" @change="initDateRangePicker()" v-model="currentItem.apartment_id">
						<optgroup v-for="( postType, slug ) in bookingInstances" :label="postType.name" :key="slug">
							<option v-for="( postName, id ) in postType.posts" :value="id" :key="id">{{ postName }}</option>
						</optgroup>
					</select>
				</div>
			</div>

			<div
				v-if="'update' === popUpState && itemUnits.length"
				:class="[ 'jet-abaf-details__field jet-abaf-details__field-apartment-unit',  { 'jet-abaf-disabled': isDisabled } ]"
			>
				<div class="jet-abaf-details__label"><?php esc_html_e( 'Booking Unit:', 'jet-booking' ); ?></div>
				<div class="jet-abaf-details__content">
					<select v-model="currentItem.apartment_unit">
						<option v-for="unit in itemUnits" :value="unit.value" :key="unit.value">{{ unit.label }}</option>
					</select>
				</div>
			</div>
			<div
				v-else-if="'info' === popUpState && currentItem.apartment_unit"
				class="jet-abaf-details__field jet-abaf-details__field-apartment-unit"
			>
				<div class="jet-abaf-details__label"><?php esc_html_e( 'Booking Unit:', 'jet-booking' ); ?></div>
				<div class="jet-abaf-details__content">{{ getItemUnitLabel( currentItem.apartment_id, currentItem.apartment_unit ) }}</div>
			</div>

			<div>
				<div
					ref="jetABAFDatePicker"
					:class="[ 'jet-abaf-details__field', 'jet-abaf-details__booking-dates', { 'jet-abaf-details__booking-dates--has-timepicker': timepicker },  { 'jet-abaf-disabled': isDisabled } ]"
				>
					<div class="jet-abaf-details__check-in-date">
						<div class="jet-abaf-details__label"><?php esc_html_e( 'Check in:', 'jet-booking' ); ?></div>
						<div class="jet-abaf-details__content">
							<template v-if="'info' === popUpState">
								{{ currentItem.check_in_date }} <template v-if="currentItem.check_in_time"> - {{ currentItem.check_in_time }}</template>
							</template>
							<template v-else-if="'update' === popUpState">
								<input type="text" v-model="currentItem.check_in_date"/>

								<div v-if="timepicker" :class="[ 'jet-abaf-timepicker', { 'loading': timeLoading } ]" >
									<select v-model="currentItem.check_in_time">
										<option v-for="( value, index ) in timepickerSlots.check_in_slots" :key="index" :value="value">{{ value }}</option>
									</select>
								</div>
							</template>
						</div>
					</div>

					<div class="jet-abaf-details__check-out-date">
						<div class="jet-abaf-details__label"><?php esc_html_e( 'Check out:', 'jet-booking' ); ?></div>
						<div class="jet-abaf-details__content">
							<template v-if="'info' === popUpState">
								{{ currentItem.check_out_date }} <template v-if="currentItem.check_out_time"> - {{ currentItem.check_out_time }}</template>
							</template>
							<template v-else-if="'update' === popUpState">
								<input type="text" v-model="currentItem.check_out_date"/>

								<div v-if="timepicker" :class="[ 'jet-abaf-timepicker', { 'loading': timeLoading } ]" >
									<select v-model="currentItem.check_out_time">
										<option v-for="( value, index ) in timepickerSlots.check_out_slots" :key="index" :value="value">{{ value }}</option>
									</select>
								</div>
							</template>
						</div>
					</div>
				</div>
			</div>

			<div
				v-if="'info' === popUpState && currentItem.user_email || 'update' === popUpState"
				class="jet-abaf-details__field jet-abaf-details__field-user-email"
			>
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'User E-mail:', 'jet-booking' ); ?>
				</div>
				<div  class="jet-abaf-details__content">
					<template v-if="'info' === popUpState">{{ currentItem.user_email }}</template>
					<input v-else-if="'update' === popUpState" type="text" v-model="currentItem.user_email"/>
				</div>
			</div>

			<template v-if="'wc_based' === bookingMode">
				<div v-if="'info' === popUpState && hasGuests()" class="jet-abaf-details__field jet-abaf-details__guests">
					<div class="jet-abaf-details__label"><?php esc_html_e( 'Guests:', 'jet-booking' ); ?></div>
					<div class="jet-abaf-details__content">{{ currentItem.__guests }}</div>
				</div>
				<div v-else-if="'update' === popUpState && hasGuestsSettings()" :class="[ 'jet-abaf-details__field', 'jet-abaf-details__guests',  { 'jet-abaf-disabled': isDisabled } ]">
					<div class="jet-abaf-details__label"><?php esc_html_e( 'Guests:', 'jet-booking' ); ?></div>
					<div class="jet-abaf-details__content">
						<select  v-model="currentItem.__guests">
							<option v-for="n in getGuestsRange( +guestsSettings.min, +guestsSettings.max, 1 )" :value="n" :key="n">{{ n }}</option>
						</select>
					</div>
				</div>

				<template v-if="hasAttributes()">
					<div v-if="'info' === popUpState" class="jet-abaf-details__attributes">
						<template v-for="attribute in itemAttributes">
							<div class="jet-abaf-details__field">
								<div class="jet-abaf-details__label">{{ attribute.label }}:</div>
								<div class="jet-abaf-details__content">{{ attribute.value }}</div>
							</div>
						</template>
					</div>
					<div v-else-if="'update' === popUpState && hasAttributesList()" :class="[ 'jet-abaf-details__attributes',  { 'jet-abaf-disabled': isDisabled } ]">
						<div v-for="( attribute, name ) in attributesList" class="jet-abaf-details__field">
							<div class="jet-abaf-details__label">{{ attribute.label }}:</div>
							<div class="jet-abaf-details__content">
								<div v-for="( term, slug ) in attribute.terms">
									<label :for="slug">
										<input :id="slug" :name="slug" type="checkbox" :value="slug" v-model="currentItem.attributes[ name ]" @change="updateItemAttribute( $event, currentItem )" />
										<span v-html="term.label"></span>
									</label>
									<div class="cx-vui-component__desc">{{ term.description }}</div>
								</div>
							</div>
						</div>
					</div>
				</template>
			</template>

			<div v-if="'info' === popUpState && currentItem.import_id" class="jet-abaf-details__field jet-abaf-details__import-id">
				<div class="jet-abaf-details__label"><?php esc_html_e( 'Import ID:', 'jet-booking' ); ?></div>
				<div class="jet-abaf-details__content">{{ currentItem.import_id }}</div>
			</div>

			<template v-for="( itemValue, itemKey ) in currentItem">
				<div
					v-if="beVisible( itemKey )"
					:key="itemKey"
					:class="[ 'jet-abaf-details__field', 'jet-abaf-details__field-' + itemKey ]"
				>
					<div class="jet-abaf-details__label">{{ itemKey }}:</div>
					<div class="jet-abaf-details__content">
						<template v-if="'info' === popUpState">{{ itemValue }}</template>
						<input v-else-if="'update' === popUpState" type="text" v-model="currentItem[ itemKey ]"/>
					</div>
				</div>
			</template>

			<div class="jet-abaf-details__field">
				<div class="jet-abaf-details__label"><?php esc_html_e( 'Booking Price:', 'jet-booking' ) ?></div>
				<div class="jet-abaf-details__content" v-html="bookingPrice"></div>
			</div>

			<div v-if="'update' === popUpState && recalculateTotals" class="jet-abaf-details__field">
				<div class="jet-abaf-details__label"><?php esc_html_e( 'Recalculate order totals:', 'jet-booking' ) ?></div>
				<div class="jet-abaf-details__content">
					<cx-vui-switcher v-model="calculateTotals"></cx-vui-switcher>
				</div>
			</div>
		</template>
		<template v-else-if="'delete' === popUpState && currentItem.order_id">
			<div v-if="'wc_based' === bookingMode" class="cx-vui-component__desc jet-abaf-details-info">
				<span class="dashicons dashicons-info-outline"></span>
				<span><?php esc_html_e( 'Associated order line item will be deleted. Order totals recalculated.', 'jet-booking' ); ?></span>
			</div>

			<div class="cx-vui-component__desc jet-abaf-details-info">
				<span class="dashicons dashicons-info-outline"></span>
				<span><?php esc_html_e( 'Related order will be deleted if the last order item removed and there are no more items in it.', 'jet-booking' ) ?></span>
			</div>
		</template>

		<div class="jet-abaf-popup-actions">
			<template v-if="'info' === popUpState">
				<cx-vui-button
					class="jet-abaf-popup-button-edit"
					button-style="accent"
					size="mini"
					@click="updateDetailsItem( currentItem )"
				>
					<template slot="label">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.5 12.375V15.5H3.625L12.8417 6.28333L9.71667 3.15833L0.5 12.375ZM2.93333 13.8333H2.16667V13.0667L9.71667 5.51667L10.4833 6.28333L2.93333 13.8333ZM15.2583 2.69167L13.3083 0.741667C13.1417 0.575 12.9333 0.5 12.7167 0.5C12.5 0.5 12.2917 0.583333 12.1333 0.741667L10.6083 2.26667L13.7333 5.39167L15.2583 3.86667C15.5833 3.54167 15.5833 3.01667 15.2583 2.69167Z" fill="white"/></svg>
						<?php esc_html_e( 'Edit', 'jet-booking' ); ?>
					</template>
				</cx-vui-button>
			</template>
			<template v-else-if="'update' === popUpState">
				<cx-vui-button
					class="jet-abaf-popup-button-save"
					button-style="accent"
					size="mini"
					@click="updateItem()"
				>
					<template slot="label"><?php esc_html_e('Save', 'jet-booking'); ?></template>
				</cx-vui-button>
			</template>

			<template v-if="'update' === popUpState || 'delete' === popUpState">
				<cx-vui-button
					class="jet-abaf-popup-button-cancel"
					button-style="accent-border"
					size="mini"
					@click="cancelPopup()"
				>
					<template slot="label"><?php esc_html_e('Cancel', 'jet-booking'); ?></template>
				</cx-vui-button>
			</template>

			<template v-if="'info' === popUpState || 'delete' === popUpState">
				<cx-vui-button
					class="jet-abaf-popup-button-delete"
					button-style="accent-border"
					size="mini"
					@click="deleteItem()"
				>
					<template slot="label">
						<svg width="12" height="16" viewBox="0 0 12 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.999959 13.8333C0.999959 14.75 1.74996 15.5 2.66663 15.5H9.33329C10.25 15.5 11 14.75 11 13.8333V3.83333H0.999959V13.8333ZM2.66663 5.5H9.33329V13.8333H2.66663V5.5ZM8.91663 1.33333L8.08329 0.5H3.91663L3.08329 1.33333H0.166626V3H11.8333V1.33333H8.91663Z" fill="#007CBA"/></svg>
						<?php esc_html_e( 'Delete', 'jet-booking' ); ?>
					</template>
				</cx-vui-button>
			</template>
		</div>
	</div>
</cx-vui-popup>