<?php

namespace JET_ABAF\Cron;

class Events_Dispatcher extends Base {

	/**
	 * Event name.
	 *
	 * Returns event name.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @return string
	 */
	public function event_name() {
		return 'jet-booking-events-dispatcher';
	}

	/**
	 * Event callback.
	 *
	 * Method to execute when the event is run.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @param int|string $booking_id Booking ID.
	 *
	 * @return void
	 */
	public function event_callback( $booking_id = null ) {
		jet_abaf()->workflows->collection->dispatch_workflows( true );
	}

	/**
	 * Event interval.
	 *
	 * Returns event interval.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @return string
	 */
	public function event_interval() {
		return 'twicedaily';
	}

	/**
	 * Is enable.
	 *
	 * Check if recurrent event is active.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return jet_abaf()->settings->get( 'enable_workflows' );
	}

}

