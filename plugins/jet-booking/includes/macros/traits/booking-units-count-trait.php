<?php

namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Units_Count_Trait {

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
		return 'booking_units_count';
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
		return __( 'Booking Units Count', 'jet-booking' );
	}

	/**
	 * Macros args.
	 *
	 * Return custom macros attributes list.
	 *
	 * @since 3.8.3
	 *
	 * @return array
	 */
	public function macros_args() {
		return [
			'units_type' => [
				'type'        => 'select',
				'label'       => __( 'Units Type', 'jet-booking' ),
				'description' => __( 'Choose whether to display available or booked units count.', 'jet-booking' ),
				'options'     => [
					'available' => __( 'Available', 'jet-booking' ),
					'booked'    => __( 'Booked', 'jet-booking' ),
				],
				'default'     => 'available',
			],
		];
	}

	/**
	 * Macros callback.
	 *
	 * Callback function to return macros value.
	 *
	 * @since 3.2.0
	 * @since 3.8.3 Added period and type handling.
	 *
	 * @param array $args Macros arguments list.
	 *
	 * @return string
	 */
	public function macros_callback( $args = [] ) {

		$period      = apply_filters( 'jet-booking/macros/units-count/period', [] );
		$units_type  = ! empty( $args['units_type'] ) ? $args['units_type'] : 'available';
		$units_count = jet_abaf()->db->get_units_count( [
			'apartment_id'   => get_the_ID(),
			'check_in_date'  => $period[0] ?? time(),
			'check_out_date' => $period[1] ?? time(),
		], $units_type );

		$units_count = $units_count ? apply_filters( 'jet-booking/macros/units-count', $units_count ) : 0;

		return sprintf( '<span data-post="%1$s" data-units-count="%2$s" data-units-type="%3$s">%2$s</span>', esc_attr( get_the_ID() ), wp_kses_post( $units_count ), esc_attr( $units_type ) );

	}

}
