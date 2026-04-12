<?php

namespace JET_ABAF\Compatibility\Packages\Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Is_Not_Cancellable extends Is_Cancellable {

	/**
	 * Returns condition ID.
	 *
	 * @since 3.4.1
	 *
	 * @return string
	 */
	public function get_id() {
		return 'is-not-cancellable';
	}

	/**
	 * Returns condition name.
	 *
	 * @since 3.4.1
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Booking is not Cancellable', 'jet-booking' );
	}

	/**
	 * Check condition by passed arguments.
	 *
	 * @since 3.4.1
	 *
	 * @param array $args List of visibility condition arguments.
	 *
	 * @return boolean
	 */
	public function check( $args = [] ) {
		return ! parent::check( $args );
	}

}
