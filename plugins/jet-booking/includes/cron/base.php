<?php

namespace JET_ABAF\Cron;

abstract class Base {

	public function __construct() {
		add_action( 'init', [ $this, 'init' ], 99 );
	}

	/**
	 * Init.
	 *
	 * Initialize schedule events.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->is_enabled() ) {
			$this->schedule_event();
			add_action( $this->event_name(), [ $this, 'event_callback' ] );
		} else {
			$this->unschedule_event();
		}
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
		return time();
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
		return 'daily';
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
	abstract public function event_name();

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
	abstract public function event_callback();

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
		return true;
	}

	/**
	 * Next schedule.
	 *
	 * Retrieves the next timestamp for an event.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return false|int
	 */
	public function next_schedule() {
		return wp_next_scheduled( $this->event_name() );
	}

	/**
	 * Schedule event.
	 *
	 * Schedules a recurring event.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function schedule_event() {
		if ( ! $this->next_schedule() ) {
			$event = wp_schedule_event( $this->event_timestamp(), $this->event_interval(), $this->event_name(), [], true );

			if ( is_wp_error( $event ) && 'invalid_schedule' === $event->get_error_code() ) {
				wp_schedule_event( time(), 'daily', $this->event_name(), [], true );
			}
		}
	}

	/**
	 * Schedule single event.
	 *
	 * Schedules an event to run only once.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $args Event arguments.
	 *
	 * @return void
	 */
	public function schedule_single_event( $args = [] ) {

		$event = wp_schedule_single_event( $this->event_timestamp(), $this->event_name(), $args, true );

		if ( is_wp_error( $event ) && 'invalid_schedule' === $event->get_error_code() ) {
			wp_schedule_single_event( time(), $this->event_name(), $args, true );
		}

	}

	/**
	 * Unschedule event.
	 *
	 * Unschedules a previously scheduled event.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function unschedule_event() {

		$timestamp = $this->next_schedule();

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->event_name() );
		}

	}

	/**
	 * Unschedule a single event.
	 *
	 * Unschedules all events attached to the hook with the specified arguments.
	 *
	 * @since  3.0.0
	 * @since  3.8.3 Refactored.
	 * @access public
	 *
	 * @param array $args Event arguments.
	 *
	 * @return void
	 */
	public function unschedule_single_event( $args = [] ) {

		$timestamp = wp_next_scheduled( $this->event_name(), $args );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->event_name(), $args );
		}

	}

}