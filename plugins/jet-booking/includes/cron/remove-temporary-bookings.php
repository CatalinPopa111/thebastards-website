<?php

namespace JET_ABAF\Cron;

class Remove_Temporary_Bookings extends Base {

	public function __construct() {

		// Reschedule event on related settings update.
		add_action( 'jet-booking/settings/before-update', [ $this, 'clear_scheduled_on_interval_change' ], 10, 3 );

		parent::__construct();

	}

	/**
	 * Is enable.
	 *
	 * Check if recurrent event is active.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return jet_abaf()->settings->get( 'remove_temporary_bookings' );
	}

	/**
	 * Event timestamp.
	 *
	 * Returns unix timestamp (UTC) for when to next run the event.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return int
	 */
	public function event_timestamp() {

		$schedules = wp_get_schedules();

		return time() + $schedules[ $this->event_interval() ]['interval'];

	}

	/**
	 * Event interval.
	 *
	 * Returns event interval.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return string
	 */
	public function event_interval() {
		return jet_abaf()->settings->get( 'remove_interval' );
	}

	/**
	 * Event name.
	 *
	 * Returns event name.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return string
	 */
	public function event_name() {
		return 'jet-booking-remove-temporary';
	}

	/**
	 * Event callback.
	 *
	 * Method to execute when the event is run.
	 *
	 * @since  3.2.0
	 * @since  3.8.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function event_callback() {

		$bookings = jet_abaf_get_bookings( [ 'status' => jet_abaf()->statuses->temporary_status() ] );

		if ( empty( $bookings ) ) {
			return;
		}

		foreach ( $bookings as $booking ) {
			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking->get_id() ] );
		}

	}

	/**
	 * Clear scheduled on interval change.
	 *
	 * Reschedule event functionality on related settings changes.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param array  $settings All settings list.
	 * @param string $setting  Settings key name.
	 * @param mixed  $value    Setting value.
	 *
	 * @return void
	 */
	public function clear_scheduled_on_interval_change( $settings, $setting, $value ) {
		if ( 'remove_interval' === $setting ) {
			$old_value = $settings[ $setting ] ?? false;

			if ( $old_value && $old_value !== $value ) {
				$this->unschedule_event();
			}
		}
	}

}