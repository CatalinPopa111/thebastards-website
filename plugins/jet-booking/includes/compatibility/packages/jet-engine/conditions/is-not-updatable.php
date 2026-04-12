<?php

namespace JET_ABAF\Compatibility\Packages\Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Is_Not_Updatable extends Is_Updatable {

	/**
	 * Returns condition ID.
	 *
	 * @since 3.4.1
	 *
	 * @return string
	 */
	public function get_id() {
		return 'is-not-updatable';
	}

	/**
	 * Returns condition name.
	 *
	 * @since 3.4.1
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Booking is not Updatable', 'jet-booking' );
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
