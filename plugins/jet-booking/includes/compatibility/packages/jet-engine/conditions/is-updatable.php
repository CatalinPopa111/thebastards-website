<?php

namespace JET_ABAF\Compatibility\Packages\Conditions;

use JET_ABAF\Visibility_Conditions\Booking_Is_Updatable;
use Jet_Engine\Modules\Dynamic_Visibility\Conditions\Base;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Is_Updatable extends Base {

	/**
	 * Returns condition ID.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'is-updatable';
	}

	/**
	 * Returns condition name.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Booking is Updatable', 'jet-booking' );
	}

	/**
	 * Returns group for current condition.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_group() {
		return 'jet_booking';
	}

	/**
	 * Check condition by passed arguments.
	 *
	 * @since 3.4.0
	 * @since 3.4.1 Refactored.
	 *
	 * @param array $args List of visibility condition arguments.
	 *
	 * @return boolean
	 */
	public function check( $args = [] ) {

		$booking   = jet_engine()->listings->data->get_current_object();
		$condition = new Booking_Is_Updatable();

		return $condition->check( $args, $booking );

	}

	/**
	 * Check if is condition available for meta fields control.
	 *
	 * @since 3.4.0
	 *
	 * @return boolean
	 */
	public function is_for_fields() {
		return false;
	}

	/**
	 * Check if is condition available for meta value control.
	 *
	 * @since 3.4.0
	 *
	 * @return boolean
	 */
	public function need_value_detect() {
		return false;
	}

}
