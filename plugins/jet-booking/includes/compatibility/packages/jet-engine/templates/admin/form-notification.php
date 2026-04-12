<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div v-if="'apartment_booking' === currentItem.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Apartment ID field:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select v-model="currentItem.booking_apartment_field">
			<option value="">--</option>
			<option v-for="field in availableFields" :value="field">{{ field }}</option>
		</select>
	</div>
</div>

<div v-if="'apartment_booking' === currentItem.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Check-in/Check-out date field:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select v-model="currentItem.booking_dates_field">
			<option value="">--</option>
			<option v-for="field in availableFields" :value="field">{{ field }}</option>
		</select>
	</div>
</div>

<?php if ( jet_abaf()->db->get_additional_db_columns() ) : ?>
	<div v-if="'apartment_booking' === currentItem.type" class="jet-form-editor__row">
		<div class="jet-form-editor__row-label">
			<?php esc_html_e( 'DB columns map:', 'jet-booking' ); ?>
		</div>
		<div class="jet-form-editor__row-fields">
			<div class="jet-form-editor__row-notice">
				<?php esc_html_e( 'Set up connection between form fields and additional database table columns. This allows you to save entered field data in the corresponding DB column.', 'jet-booking' ); ?>
			</div>
			<?php foreach ( jet_abaf()->db->get_additional_db_columns() as $column ) : ?>
				<div class="jet-form-editor__row-map">
					<span><?php echo esc_html( $column ); ?></span>
					<input type="text" v-model="currentItem.db_columns_map_<?php echo esc_attr( $column ); ?>">
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<?php if ( jet_abaf()->settings->wc_integration_enabled() ) : ?>
	<div v-if="'apartment_booking' === currentItem.type" class="jet-form-editor__row">
		<div class="jet-form-editor__row-label">
			<?php esc_html_e( 'Disable WooCommerce integration:', 'jet-booking' ); ?>
		</div>
		<div class="jet-form-editor__row-control">
			<input type="checkbox" v-model="currentItem.disable_wc_integration">
			<div class="jet-form-editor__row-control-desc">
				<?php esc_html_e( 'Check to disable WooCommerce integration and disconnect the booking system with WooCommerce checkout for current form.', 'jet-booking' ); ?>
			</div>
		</div>
	</div>

	<div v-if="'apartment_booking' === currentItem.type && ! currentItem.disable_wc_integration" class="jet-form-editor__row">
		<div class="jet-form-editor__row-label">
			<?php esc_html_e( 'WooCommerce Price field:', 'jet-booking' ); ?>
		</div>
		<div class="jet-form-editor__row-control">
			<select v-model="currentItem.booking_wc_price">
				<option value="">--</option>
				<option v-for="field in availableFields" :value="field">{{ field }}</option>
			</select>

			<div class="jet-form-editor__row-notice">
				<?php esc_html_e( 'Select field to get total price from. If not selected price will be get from post meta value.', 'jet-booking' ); ?>
			</div>
		</div>
	</div>
	<div v-if="'apartment_booking' === currentItem.type && ! currentItem.disable_wc_integration" class="jet-form-editor__row">
		<div class="jet-form-editor__row-label">
			<?php esc_html_e( 'WooCommerce order details:', 'jet-booking' ); ?>
		</div>
		<div class="jet-form-editor__row-control">
			<button type="button" class="button button-secondary" id="jet-booking-wc-details">
				<?php esc_html_e( 'Set up', 'jet-booking' ); ?>
			</button>
			<div class="jet-form-editor__row-control-desc">
				<?php esc_html_e( 'Set up booking-related info you want to add to the WooCommerce orders and e-mails.', 'jet-booking' ); ?>
			</div>
		</div>
	</div>
	<div v-if="'apartment_booking' === currentItem.type && ! currentItem.disable_wc_integration" class="jet-form-editor__row">
		<div class="jet-form-editor__row-label">
			<?php esc_html_e( 'WooCommerce checkout fields map:', 'jet-booking' ); ?>
		</div>
		<div class="jet-form-editor__row-fields jet-wc-checkout-fields">
			<div class="jet-form-editor__row-notice">
				<?php esc_html_e( 'Connect WooCommerce checkout fields to appropriate form fields. This allows you to pre-fill WooCommerce checkout fields after redirect to checkout.', 'jet-booking' ); ?>
			</div>
			<?php foreach ( jet_abaf()->wc->mode->get_checkout_fields() as $field ) : ?>
				<div class="jet-form-editor__row-map">
					<span><?php echo esc_html( $field ); ?></span>
					<select v-model="currentItem.wc_fields_map__<?php echo esc_attr( $field ); ?>">
						<option value="">--</option>
						<option v-for="field in availableFields" :value="field">{{ field }}</option>
					</select>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
