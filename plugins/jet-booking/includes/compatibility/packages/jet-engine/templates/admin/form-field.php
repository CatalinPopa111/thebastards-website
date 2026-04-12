<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Layout:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_field_layout">
			<option value="single"> <?php esc_html_e( 'Single field', 'jet-booking' ); ?> </option>
			<option value="separate"> <?php esc_html_e( 'Separate fields for check in and check out dates.', 'jet-booking' ); ?> </option>
		</select>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Fields position:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_fields_position">
			<option value="inline"> <?php esc_html_e( 'Inline', 'jet-booking' ); ?> </option>
			<option value="list"> <?php esc_html_e( 'List', 'jet-booking' ); ?> </option>
		</select>

		<div class="jet-form-editor__row-notice">
			<i> <?php esc_html_e( '* - For separate fields layout.', 'jet-booking' ); ?> </i>
		</div>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Check In field label:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" v-model="currentItem.settings.first_field_label">
		<div class="jet-form-editor__row-notice">
			<i> <?php esc_html_e( '* - if you are using separate fields for check in and check out dates,', 'jet-booking' ); ?> </i>
			<br>
			<i> <?php esc_html_e( 'you need to leave the default "Label" empty and use this option for the field label.', 'jet-booking' ); ?> </i>
		</div>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Placeholder:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" v-model="currentItem.settings.first_field_placeholder">
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Check Out field label:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" placeholder="For separate fields layout" v-model="currentItem.settings.second_field_label">
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Check Out field placeholder:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" placeholder="For separate fields layout" v-model="currentItem.settings.second_field_placeholder">
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Date format:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_fields_format">
			<option value="YYYY-MM-DD"> <?php esc_html_e( 'YYYY-MM-DD', 'jet-booking' ); ?> </option>
			<option value="MM-DD-YYYY"> <?php esc_html_e( 'MM-DD-YYYY', 'jet-booking' ); ?> </option>
			<option value="DD-MM-YYYY"> <?php esc_html_e( 'DD-MM-YYYY', 'jet-booking' ); ?> </option>
		</select>

		<div class="jet-form-editor__row-notice">
			<i> <?php esc_html_e( '* - applies only for date in the form checkin/checkout fields ', 'jet-booking' ); ?> </i>
			<br>
			<i> <?php esc_html_e( '* - for `MM-DD-YYYY` date format use the `/` date separator', 'jet-booking' ); ?> </i>
		</div>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'Date field separator:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_fields_separator">
			<option value="-"> <?php esc_html_e( '-', 'jet-booking' ); ?> </option>
			<option value="."> <?php esc_html_e( '.', 'jet-booking' ); ?> </option>
			<option value="/"> <?php esc_html_e( '/', 'jet-booking' ); ?> </option>
			<option value="space"> <?php esc_html_e( 'Space', 'jet-booking' ); ?> </option>
		</select>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php esc_html_e( 'First day of the week:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.start_of_week">
			<option value="monday"> <?php esc_html_e( 'Monday', 'jet-booking' ); ?> </option>
			<option value="sunday"> <?php esc_html_e( 'Sunday', 'jet-booking' ); ?> </option>
		</select>
	</div>
</div>
