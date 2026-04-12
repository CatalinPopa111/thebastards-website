<?php

namespace JET_ABAF\Visibility_Conditions;

use JET_ABAF\Resources\Booking;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Booking_Is_Cancellable {

	/**
	 * Check condition.
	 *
	 * Check if booking item is cancelable by passed arguments.
	 *
	 * @since 3.4.1
	 *
	 * @param array   $args    List of visibility condition arguments.
	 * @param Booking $booking Booking object.
	 *
	 * @return bool
	 */
	public function check( $args = [], $booking = null ) {

		$type = ! empty( $args['type'] ) ? $args['type'] : 'show';

		if ( ! is_a( $booking, '\JET_ABAF\Resources\Booking' ) ) {
			return 'hide' === $type;
		}

		return 'hide' === $type ? ! $booking->is_cancellable() : $booking->is_cancellable();

	}

}