<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Instance_Title extends Base_Macros {

	/**
	 * Macros tag.
	 *
	 * Returns macros tag.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function macros_tag() {
		return 'title';
	}

	/**
	 * Macros name.
	 *
	 * Returns macros name.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function macros_name() {
		return __( 'Booking Instance Title', 'jet-booking' );
	}

	/**
	 * Macros callback.
	 *
	 * Callback function to return macros value.
	 *
	 * @since 3.2.0
	 *
	 * @param array                       $args    Macros arguments list.
	 * @param \JET_ABAF\Resources\Booking $booking Booking instance.
	 *
	 * @return string
	 */
	public function macros_callback( $args = [], $booking = null ) {
		return get_the_title( $booking->get_apartment_id() );
	}

}
