<?php

namespace JET_ABAF\Workflows\Events;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Created extends Base {

	/**
	 * Get event ID.
	 *
	 * Returns the unique identifier for this event.
	 *
	 * @since 3.7.0
	 *
	 * @return string Event ID.
	 */
	public function get_id() {
		return 'booking-created';
	}

	/**
	 * Get event name.
	 *
	 * Returns the human-readable name for this event.
	 *
	 * @since 3.7.0
	 *
	 * @return string Event name.
	 */
	public function get_name() {
		return __( 'Booking Created', 'jet-booking' );
	}

	/**
	 * Get hook name.
	 *
	 * Returns the hook name for the booking creation event.
	 *
	 * @since 3.7.0
	 *
	 * @return string Hook name.
	 */
	public function get_hook() {
		return 'jet-booking/db/booking-inserted';
	}

}
