<?php

namespace JET_ABAF\Cron;

class Sync_Calendars extends Base {

	public function __construct() {

		add_action( 'jet-booking/settings/before-update', [ $this, 'clear_scheduled_on_interval_change' ], 10, 3 );

		parent::__construct();

	}

	/**
	 * Is enable.
	 *
	 * Check if recurrent event is active.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return jet_abaf()->settings->get( 'ical_synch' );
	}

	/**
	 * Event timestamp.
	 *
	 * Returns unix timestamp (UTC) for when to next run the event.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return int
	 */
	public function event_timestamp() {

		$sync_start_h = jet_abaf()->settings->get( 'synch_interval_hours' );
		$sync_start_m = jet_abaf()->settings->get( 'synch_interval_mins' );

		if ( ! $sync_start_h || ! $sync_start_m ) {
			return time();
		}

		return strtotime( 'today ' . $sync_start_h . ':' . $sync_start_m );

	}

	/**
	 * Event interval.
	 *
	 * Returns event interval.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function event_interval() {
		return jet_abaf()->settings->get( 'synch_interval' );
	}

	/**
	 * Event name.
	 *
	 * Returns event name.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function event_name() {
		return 'jet-booking-sync-calendars';
	}

	/**
	 * Event callback.
	 *
	 * Method to execute when the event is run.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function event_callback() {

		$calendars = jet_abaf()->ical->get_calendars();

		if ( empty( $calendars ) ) {
			return;
		}

		foreach ( $calendars as $calendar ) {
			jet_abaf()->ical->synch( $calendar['post_id'], $calendar['unit_id'] );
		}

	}

	/**
	 * Clear scheduled on interval change.
	 *
	 * Reschedule calendar sync functionality on settings changes.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array  $settings All settings list.
	 * @param string $setting  Settings key name.
	 * @param mixed  $value    Setting value.
	 *
	 * @return void
	 */
	public function clear_scheduled_on_interval_change( $settings, $setting, $value ) {
		if ( 'synch_interval' === $setting || 'synch_interval_hours' === $setting || 'synch_interval_mins' === $setting ) {
			$old_value = $settings[ $setting ] ?? false;

			if ( $old_value && $old_value !== $value ) {
				$this->unschedule_event();
			}
		}
	}

}