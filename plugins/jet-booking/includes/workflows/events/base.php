<?php

namespace JET_ABAF\Workflows\Events;

use JET_ABAF\Cron\Manager as Cron_Manager;
use JET_ABAF\Workflows\Actions_Dispatcher;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

abstract class Base {

	public function __construct() {
		add_action( 'jet-booking/workflows/event-controls', [ $this, 'register_event_controls' ] );
	}

	/**
	 * Get event ID.
	 *
	 * This method should return the unique identifier for the event.
	 *
	 * @since 3.7.0
	 *
	 * @return string Unique identifier for the event.
	 */
	abstract public function get_id();

	/**
	 * Get event name.
	 *
	 * This method should return the human-readable name for the event.
	 *
	 * @since 3.7.0
	 *
	 * @return string The name of the event.
	 */
	abstract public function get_name();

	/**
	 * Get event hook.
	 *
	 * This method should return the name for the event hook.
	 *
	 * @since 3.7.0
	 *
	 * @return string The name of the hook.
	 */
	abstract public function get_hook();

	/**
	 * Registers event controls for the workflow.
	 *
	 * This method is responsible for adding any necessary controls or settings to the workflow
	 * event controls section.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function register_event_controls() {}

	/**
	 * Is relevant status.
	 *
	 * Checks if the given booking status is relevant for the event.
	 *
	 * @since 3.7.0
	 *
	 * @param string $status The booking status to check.
	 *
	 * @return bool
	 */
	public function is_relevant_status( $status ) {

		if ( in_array( $status, jet_abaf()->statuses->invalid_statuses() ) ) {
			return false;
		}

		return true;

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

		$booking = wp_parse_args( $booking, [ 'status' => '' ] );

		return apply_filters( 'jet-booking/workflows/events/can-dispatch', $this->is_relevant_status( $booking['status'] ), $data, $booking );

	}

	/**
	 * Dispatch.
	 *
	 * Dispatches actions based on the event schedule and conditions.
	 *
	 * @since 3.7.0
	 *
	 * @param array $data Additional data for the event.
	 *
	 * @return void
	 */
	public function dispatch( $data = [] ) {

		if ( empty( $data['actions'] ) ) {
			return;
		}

		add_action( $this->get_hook(), function ( $booking ) use ( $data ) {
			if ( ! $this->can_dispatch( $data, $booking ) ) {
				return;
			}

			switch ( $data['schedule'] ) {
				case 'immediately':
					$actions = new Actions_Dispatcher( $data['actions'], $booking );
					$actions->run();

					break;

				case 'scheduled':
					jet_abaf()->db->bookings_meta->insert( [
						'booking_id' => $booking['booking_id'],
						'meta_key'   => '__schedule_id_' . $data['hash'],
						'meta_value' => 1,
					] );

					$this->schedule_dispatcher();

					break;

				default:
					break;
			}
		} );

	}

	/**
	 * Dispatches actions for scheduled events.
	 *
	 * This function retrieves bookings that have been marked for scheduling and dispatches
	 * actions based on the event conditions.
	 *
	 * @since 3.7.0
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections. Improved performance.
	 *
	 * @param array $data Additional data for the event.
	 *
	 * @return void
	 */
	public function dispatch_scheduled( $data = [] ) {

		if ( 'scheduled' !== ( $data['schedule'] ?? '' ) ) {
			return;
		}

		$wpdb           = jet_abaf()->db->bookings_meta->wpdb();
		$bookings_table = jet_abaf()->db->bookings->table();
		$meta_table     = jet_abaf()->db->bookings_meta->table();

		$date      = ! empty( $data['date_type'] ) ? $data['date_type'] : 'check_in_date';
		$condition = ! empty( $data['condition'] ) ? $data['condition'] : 'before';
		$days      = ! empty( $data['days'] ) ? absint( $data['days'] ) : 1;
		$threshold = 'before' === $condition ? time() + $days * DAY_IN_SECONDS : time() - $days * DAY_IN_SECONDS;
		$operator  = 'before' === $condition ? '<=' : '<';
		$hash      = ! empty( $data['hash'] ) ? $data['hash'] : '';

		$bookings = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT bt.*, mt.meta_key
				FROM %i AS mt 
				INNER JOIN %i AS bt ON bt.booking_id = mt.booking_id
				WHERE mt.meta_key = %s AND bt.%i $operator %d",
				$meta_table,
				$bookings_table,
				'__schedule_id_' . $hash,
				$date,
				$threshold
			),
			ARRAY_A
		);

		if ( empty( $bookings ) ) {
			return;
		}

		foreach ( $bookings as $booking ) {
			if ( ! $this->can_dispatch( $data, $booking ) ) {
				continue;
			}

			$actions = new Actions_Dispatcher( $data['actions'], $booking );
			$actions->run();

			jet_abaf()->db->bookings_meta->delete( [
				'booking_id' => $booking['booking_id'],
				'meta_key'   => '__schedule_id_' . $hash
			] );
		}

	}

	/**
	 * Schedule dispatcher.
	 *
	 * Schedules the event dispatcher cron job.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function schedule_dispatcher() {
		$cron_schedule = Cron_Manager::instance()->get_schedules( 'jet-booking-events-dispatcher' );
		$cron_schedule->schedule_event();
	}

}