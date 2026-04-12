<?php

namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Column_Trait {

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
		return 'booking_column';
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
		return __( 'Booking Additional Column', 'jet-booking' );
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

		$additional_columns = jet_abaf()->settings->get_clean_columns();

		return [
			'booking_additional_column' => [
				'type'        => 'select',
				'label'       => __( 'Column', 'jet-booking' ),
				'description' => __( 'Select additional database column to get macros data from.', 'jet-booking' ),
				'options'     => array_combine( $additional_columns, $additional_columns ),
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

		$column = ! empty( $args['booking_additional_column'] ) ? $args['booking_additional_column'] : '';

		if ( ! $booking ) {
			$booking = apply_filters( 'jet-booking/macros/booking-object', $booking );
		}

		if ( $booking && is_a( $booking, 'JET_ABAF\Resources\Booking' ) ) {
			return  $booking->get_column( $column );
		}

		return '';

	}

}
