<?php

namespace JET_ABAF\Workflows\Events;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Status_Changed extends Base {

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
		return 'booking-status-changed';
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
		return __( 'Booking Status Changed', 'jet-booking' );
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
		return 'jet-booking/db/update/bookings/status';
	}

	/**
	 * Can dispatch.
	 *
	 * Determines if the event can be dispatched based on the booking status.
	 *
	 * @since 3.7.0
	 *
	 * @param array $data    Additional data for the event.
	 * @param array $booking The booking data.
	 *
	 * @return bool
	 */
	public function can_dispatch( $data = [], $booking = [] ) {

		$status = $data['status'] ?? false;

		if ( ! $status ) {
			return true;
		}

		return $status === $booking['status'];

	}

	/**
	 * Registers event controls for the workflow.
	 *
	 * This method is responsible for adding controls to the booking status changed event.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function register_event_controls() {

		$statuses = jet_abaf()->tools->prepare_list_for_js( jet_abaf()->statuses->get_statuses(), ARRAY_A );

		include JET_ABAF_PATH . 'includes/workflows/templates/events/booking-status-changed-controls.php';

	}

}
