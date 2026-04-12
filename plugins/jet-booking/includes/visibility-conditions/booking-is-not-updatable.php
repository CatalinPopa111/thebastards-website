<?php

namespace JET_ABAF\Visibility_Conditions;

use JET_ABAF\Resources\Booking;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Booking_Is_Not_Updatable extends Booking_Is_Updatable {

	/**
	 * Check condition.
	 *
	 * Check if booking item is not updatable by passed arguments.
	 *
	 * @since 3.4.1
	 *
	 * @param array   $args    List of visibility condition arguments.
	 * @param Booking $booking Booking object.
	 *
	 * @return bool
	 */
	public function check( $args = [], $booking = null ) {
		return ! parent::check( $args, $booking );
	}

}