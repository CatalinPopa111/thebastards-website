<?php
/**
 * Booking list add new booking template.
 *
 * @package JET_ABAF
 */
?>

<div class="jet-abaf-bookings-add-new">
	<cx-vui-button
		button-style="accent"
		size="mini"
		@click="showAddDialog()"
	>
		<template slot="label">
			<?php esc_html_e( 'Add New', 'jet-booking' ); ?>
		</template>
	</cx-vui-button>

	<cx-vui-popup
		:class="[ 'jet-abaf-popup', { 'jet-abaf-submitting': submitting } ]"
		v-model="addDialog"
		body-width="500px"
		ok-label="<?php esc_html_e( 'Add New', 'jet-booking' ) ?>"
		@on-ok="handleAdd"
		@on-cancel="cancelPopup"
	>
		<div slot="title" class="cx-vui-subtitle">
			<?php esc_html_e( 'Add New Booking:', 'jet-booking' ); ?>
		</div>

		<div
			slot="content"
			v-if="overlappingBookings"
			class="jet-abaf-bookings-error"
			v-html="overlappingBookings"
		></div>

		<div slot="content" class="jet-abaf-details">
			<div class="jet-abaf-details__field jet-abaf-details__field-status">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Status:', 'jet-booking' ) ?>
				</div>
				<div class="jet-abaf-details__content">
					<select v-model="newItem.status">
						<option v-for="( label, value ) in statuses" :value="value" :key="value">
							{{ label }}
						</option>
					</select>
				</div>
			</div>

			<div class="jet-abaf-details__field jet-abaf-details__field-apartment_id">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Booking Item:', 'jet-booking' ) ?>
				</div>
				<div class="jet-abaf-details__content">
					<select v-model="newItem.apartment_id" @change="initDateRangePicker()" >
						<optgroup v-for="( postType, slug ) in bookingInstances" :label="postType.name" :key="slug">
							<option v-for="( postName, id ) in postType.posts" :value="id" :key="id">{{ postName }}</option>
						</optgroup>
					</select>

					<?php if ( jet_abaf()->wc->has_woocommerce() && 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) && ! jet_abaf()->tools->get_booking_posts() ) {
						printf(
							__( 'No booking products found. Create booking products to start using this functionality. <a href="%s" target="_blank">Create your first product</a>.', 'jet-booking' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							esc_url( add_query_arg( [ 'post_type' => 'product', 'jet_booking_product' => 1 ], admin_url( 'post-new.php' ) ) )
						);
					} ?>
				</div>
			</div>

			<div
				ref="jetABAFDatePicker"
				:class="[ 'jet-abaf-details__field', 'jet-abaf-details__booking-dates', { 'jet-abaf-details__booking-dates--has-timepicker': timepicker }, { 'jet-abaf-disabled': isDisabled } ]"
			>
				<div class="jet-abaf-details__check-in-date">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Check in:', 'jet-booking' ) ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="text" v-model="newItem.check_in_date"/>

						<div v-if="timepicker" :class="[ 'jet-abaf-timepicker', { 'loading': timeLoading }, { 'jet-abaf-disabled': ! timepickerSlots.check_in_slots.length } ]" >
							<select v-model="newItem.check_in_time">
								<option v-for="( value, index ) in timepickerSlots.check_in_slots" :key="index" :value="value">{{ value }}</option>
							</select>
						</div>
					</div>
				</div>

				<div class="jet-abaf-details__check-out-date">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Check out:', 'jet-booking' ) ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="text" v-model="newItem.check_out_date"/>

						<div v-if="timepicker" :class="[ 'jet-abaf-timepicker', { 'loading': timeLoading }, { 'jet-abaf-disabled': ! timepickerSlots.check_out_slots.length } ]" >
							<select v-model="newItem.check_out_time">
								<option v-for="( value, index ) in timepickerSlots.check_out_slots" :key="index" :value="value">{{ value }}</option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="jet-abaf-details__field jet-abaf-details__field-user-email">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'User E-mail:', 'jet-booking' ); ?>
				</div>
				<div class="jet-abaf-details__content">
					<input type="text" v-model="newItem.user_email"/>
				</div>
			</div>

			<template v-if="'wc_based' === bookingMode">
				<div v-if="hasGuestsSettings()" :class="[ 'jet-abaf-details__field', 'jet-abaf-details__guests',  { 'jet-abaf-disabled': isDisabled } ]">
					<div class="jet-abaf-details__label"><?php esc_html_e( 'Guests:', 'jet-booking' ); ?></div>
					<div class="jet-abaf-details__content">
						<select v-model="newItem.__guests">
							<option v-for="n in getGuestsRange( +guestsSettings.min, +guestsSettings.max, 1 )" :value="n" :key="n">{{ n }}</option>
						</select>
					</div>
				</div>

				<div v-if="hasAttributesList()" :class="[ 'jet-abaf-details__attributes',  { 'jet-abaf-disabled': isDisabled } ]">
					<div v-for="( attribute, name ) in attributesList" class="jet-abaf-details__field">
						<div class="jet-abaf-details__label">{{ attribute.label }}:</div>
						<div class="jet-abaf-details__content">
							<div v-for="( term, slug ) in attribute.terms">
								<label :for="slug">
									<input :id="slug" :name="slug" type="checkbox" :value="slug" v-model="newItem.attributes[ name ]" @change="updateAttribute( $event, newItem )" />
									<span v-html="term.label"></span>
								</label>
								<div class="cx-vui-component__desc">{{ term.description }}</div>
							</div>
						</div>
					</div>
				</div>
			</template>

			<div class="jet-abaf-details__field">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Booking Price:', 'jet-booking' ) ?>
				</div>
				<div class="jet-abaf-details__content" v-html="bookingPrice"></div>
			</div>

			<div class="jet-abaf-details__fields">
				<template v-for="field in fields">
					<div
						v-if="beVisible( field )"
						:key="field"
						:class="[ 'jet-abaf-details__field', 'jet-abaf-details__field-' + field ]"
					>
						<div class="jet-abaf-details__label">{{ field }}:</div>
						<div class="jet-abaf-details__content">
							<input type="text" v-model="newItem[ field ]"/>
						</div>
					</div>
				</template>
			</div>

			<div v-if="( 'plain' === bookingMode && ( orderPostType || wcIntegration ) ) || 'wc_based' === bookingMode" class="jet-abaf-details__field">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Create Related Order', 'jet-booking' ) ?>
				</div>

				<div class="jet-abaf-details__content">
					<template v-if="'wc_based' === bookingMode">
						<cx-vui-radio
							v-model="createRelatedOrder"
							:options-list="[
								{
									value: '',
									label: '<?php esc_html_e( 'Don\'t create an order', 'jet-booking' ); ?>',
								},
								{
									value: 'new',
									label: '<?php esc_html_e( 'Create a new order', 'jet-booking' ); ?>',
								},
								{
									value: 'existing',
									label: '<?php esc_html_e( 'Assign this booking to an existing order with ID:', 'jet-booking' ); ?>',
								}
							]"
						></cx-vui-radio>

						<input type="number" v-model="relatedOrderID"/>
					</template>

					<cx-vui-switcher v-else v-model="createRelatedOrder"></cx-vui-switcher>
				</div>
			</div>
		</div>
	</cx-vui-popup>
</div>