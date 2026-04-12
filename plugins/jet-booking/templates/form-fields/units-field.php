<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div class="jet-abaf-product-units form-field">
	<label for="jet_abaf_units">
		<?php esc_html_e( 'Units:', 'jet-booking' ); ?>
	</label>

	<select name="jet_abaf_units" id="jet_abaf_units">
		<?php foreach ( $units as $unit ) {
			/* translators: 1: unit ID, 2: unit title */
			printf( '<option value="%1$s">%2$s</option>', esc_attr( $unit['unit_id'] ), esc_html( $unit['unit_title'] ) );
		} ?>
	</select>
</div>