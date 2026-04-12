<?php

namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Unit_Title_Trait {

	/**
	 * Macros tag.
	 *
	 * Returns macros tag.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function macros_tag() {
		return 'booking_unit_title';
	}

	/**
	 * Macros name.
	 *
	 * Returns macros name.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function macros_name() {
		return __( 'Booking Unit Title', 'jet-booking' );
	}

	/**
	 * Macros callback.
	 *
	 * Callback function to return macros value.
	 *
	 * @since 3.2.0
	 *
	 * @param array                       $args    Macros arguments list.
	 * @param \JET_ABAF\Resources\Booking $booking Booking instance.
	 *
	 * @return string
	 */
	public function macros_callback( $args = [], $booking = null ) {

		if ( ! $booking ) {
			$booking = apply_filters( 'jet-booking/macros/booking-object', $booking );
		}

		if ( ! $booking || ! is_a( $booking, 'JET_ABAF\Resources\Booking' ) ) {
			return '';
		}

		$apartment_id = $booking->get_apartment_id();
		$unit_id      = $booking->get_apartment_unit();

		if ( ! $apartment_id || ! $unit_id ) {
			return '';
		}

		$unit = jet_abaf()->db->get_apartment_unit( $apartment_id, $unit_id );

		if ( empty( $unit ) ) {
			return '';
		}

		return ! empty( $unit[0]['unit_title'] ) ? $unit[0]['unit_title'] : __( 'Unit-', 'jet-booking' ) . $unit_id;

	}

}
