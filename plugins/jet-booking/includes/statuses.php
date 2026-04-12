<?php

namespace JET_ABAF;

class Statuses {

	/**
	 * Statuses.
	 *
	 * Holds all available booking statuses.
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $statuses = [];

	public function __construct() {
		$this->statuses = [
			'completed'  => _x( 'Completed', 'Order status', 'jet-booking' ),
			'processing' => _x( 'Processing', 'Order status', 'jet-booking' ),
			'on-hold'    => _x( 'On hold', 'Order status', 'jet-booking' ),
			'pending'    => _x( 'Pending', 'Order status', 'jet-booking' ),
			'refunded'   => _x( 'Refunded', 'Order status', 'jet-booking' ),
			'cancelled'  => _x( 'Cancelled', 'Order status', 'jet-booking' ),
			'failed'     => _x( 'Failed', 'Order status', 'jet-booking' ),
			'created'    => _x( 'Created', 'Order status', 'jet-booking' ),
		];
	}

	/**
	 * Get scheme.
	 *
	 * Returns list of categorized statuses.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_schema() {
		return [
			'valid'       => $this->valid_statuses(),
			'in_progress' => $this->in_progress_statuses(),
			'finished'    => $this->finished_statuses(),
			'invalid'     => $this->invalid_statuses(),
		];
	}

	/**
	 * Valid statuses.
	 *
	 * Returns list of valid statuses.
	 *
	 * @since 2.0.0
	 * @since 3.5.1 Added `jet-booking/statuses/valid` filter hook.
	 *
	 * @return array
	 */
	public function valid_statuses() {
		return apply_filters( 'jet-booking/statuses/valid', [ 'pending', 'processing', 'completed', 'on-hold' ] );
	}

	/**
	 * In progress statuses.
	 *
	 * Returns list of valid but not finalized statuses.
	 *
	 * @since 2.0.0
	 * @since 3.5.1 Added `jet-booking/statuses/in-progress` filter hook.
	 *
	 * @return array
	 */
	public function in_progress_statuses() {
		return apply_filters( 'jet-booking/statuses/in-progress', [ 'pending', 'processing', 'on-hold' ] );
	}

	/**
	 * Finished statuses.
	 *
	 * Returns list of valid and finished statuses.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function finished_statuses() {
		return array_values( array_diff( $this->valid_statuses(), $this->in_progress_statuses() ) );
	}

	/**
	 * Invalid statuses.
	 *
	 * Returns list of invalid statuses.
	 *
	 * @since 2.0.0
	 * @since 3.5.1 Added `jet-booking/statuses/invalid` filter hook.
	 *
	 * @return array
	 */
	public function invalid_statuses() {
		return apply_filters( 'jet-booking/statuses/invalid', [ 'cancelled', 'refunded', 'failed' ] );
	}

	/**
	 * Temporary status.
	 *
	 * Returns temporary status for WC and Payment orders.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function temporary_status() {
		return 'created';
	}

	/**
	 * Get statuses.
	 *
	 * Returns list of all statuses.
	 *
	 * @since 2.0.0
	 * @since 3.5.1 Added `jet-booking/statuses` filter hook.
	 *
	 * @return array
	 */
	public function get_statuses() {
		return apply_filters( 'jet-booking/statuses', $this->statuses );
	}

	/**
	 * Get statuses IDs.
	 *
	 * Returns all the keys or a subset of the keys of an array .
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_statuses_ids() {
		return array_keys( $this->statuses );
	}

}
