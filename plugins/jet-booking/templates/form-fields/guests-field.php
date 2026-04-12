<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div class="jet-abaf-product-guests form-field">
	<label for="jet_abaf_guests">
		<?php esc_html_e( 'Guests:', 'jet-booking' ); ?>
	</label>

	<select name="jet_abaf_guests" id="jet_abaf_guests">
		<?php foreach ( range( $min_guests, $max_guests ) as $i ) {
			/* translators: 1: guests number */
			printf( '<option value="%1$s">%1$s</option>', esc_html( $i ) );
		} ?>
	</select>
</div>