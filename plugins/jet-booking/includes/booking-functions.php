<?php

use \JET_ABAF\Resources\Booking_Query;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Get bookings.
 *
 * Standard way of retrieving bookings based on certain parameters.
 *
 * This function should be used for booking retrieval so that we have a data agnostic
 * way to get a list of booking.
 *
 * @since 3.3.0
 *
 * @param array $args Array of arguments.
 *
 * @return array|object
 */
function jet_abaf_get_bookings( $args = [] ) {
	$query = new Booking_Query( $args );

	return $query->get_bookings();
}

/**
 * Get booking.
 *
 * Main function for returning products.
 *
 * @since 3.3.0
 *
 * @param string|int $id ID of the booking.
 *
 * @return mixed
 */
function jet_abaf_get_booking( $id ) {
	$bookings = jet_abaf_get_bookings( [ 'include' => $id ] );

	return reset( $bookings );
}

/**
 * Get timepicker field.
 *
 * This function generates a timepicker field based on the provided parameters.
 *
 * @since 3.7.0
 *
 * @param string $type   Type of the timepicker field.
 * @param object $caller Optional. The caller object.
 *
 * @return false|string
 */
function jet_abaf_get_timepicker_field( $type = 'check-in', $caller = null ) {

	if ( ! jet_abaf()->settings->get( 'timepicker' ) ) {
		return '';
	}

	$labeled       = apply_filters( 'jet-booking/timepicker-field/labeled', false, $type );
	$label_classes = [];
	$field_classes = [];

	if ( $caller && is_a( $caller, 'JET_ABAF\Formbuilder_Plugin\Blocks\Check_In_Out_Render' ) ) {
		$label_classes[] = 'jet-form-builder__label';
		$field_classes[] = 'jet-form-builder__field';
	}

	ob_start();

	include JET_ABAF_PATH . 'templates/form-fields/timepicker-field.php';

	return ob_get_clean();

}
