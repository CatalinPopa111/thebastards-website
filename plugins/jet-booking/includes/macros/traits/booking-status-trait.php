<?php

namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Status_Trait {

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
		return 'booking_status';
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
		return __( 'Booking Status', 'jet-booking' );
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

		if ( $booking && is_a( $booking, 'JET_ABAF\Resources\Booking' ) ) {
			return  $booking->get_status();
		}

		return '';

	}

}
