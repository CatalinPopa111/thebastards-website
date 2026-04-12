<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Instance_Meta extends Base_Macros {

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
		return 'current_meta';
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
		return __( 'Booking Instance Meta', 'jet-booking' );
	}

	/**
	 * Macros args.
	 *
	 * Return custom macros attributes list.
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	public function macros_args() {
		return [
			'booking_meta_key' => [
				'type'    => 'text',
				'label'   => __( 'Meta field', 'jet-booking' ),
			],
		];
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

		$meta_key = ! empty( $args['booking_meta_key'] ) ? $args['booking_meta_key'] : '';

		return get_post_meta( $booking->get_apartment_id(), $meta_key, true );

	}

}
