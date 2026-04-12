<?php

namespace JET_ABAF\Workflows;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Actions_Dispatcher {

	/**
	 * List of actions to be executed.
	 *
	 * @var array
	 */
	private $actions = [];

	/**
	 * Booking data associated with the actions.
	 *
	 * @var array
	 */
	private $booking = [];

	public function __construct( $actions = [], $booking = [] ) {
		$this->actions = $actions;
		$this->booking = $booking;
	}

	/**
	 * Run dispatcher.
	 *
	 * Executes the actions associated with the booking.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->actions as $action_data ) {
			if ( empty( $action_data['action_id'] ) ) {
				continue;
			}

			$action = jet_abaf()->workflows->get_action( $action_data['action_id'] );

			if ( ! $action ) {
				continue;
			}

			$action->setup( $action_data, $this->booking );
			$action->do_action();
		}
	}

}
