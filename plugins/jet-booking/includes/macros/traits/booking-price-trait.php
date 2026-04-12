<?php

namespace JET_ABAF\Macros\Traits;

use JET_ABAF\Price;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Price_Trait {

	/**
	 * Macros tag.
	 *
	 * Returns macros tag.
	 *
	 * @since 3.6.1
	 *
	 * @return string
	 */
	public function macros_tag() {
		return 'booking_price';
	}

	/**
	 * Macros name.
	 *
	 * Returns macros name.
	 *
	 * @since 3.6.1
	 *
	 * @return string
	 */
	public function macros_name() {
		return __( 'Booking Price', 'jet-booking' );
	}

	/**
	 * Macros args.
	 *
	 * Return custom macros attributes list.
	 *
	 * @since 3.6.1
	 *
	 * @return array
	 */
	public function macros_args() {
		return [
			'booking_period_start'            => [
				'type'        => 'text',
				'label'       => __( 'Start Date', 'jet-booking' ),
				'description' => __( 'Enter about any English textual datetime description. Example: `1996-04-09`, `first day of this month`.', 'jet-booking' ),
			],
			'booking_period_end'              => [
				'type'        => 'text',
				'label'       => __( 'End Date', 'jet-booking' ),
				'description' => __( 'Enter about any English textual datetime description. Example: `1996-04-09`, `first day of this month`.', 'jet-booking' ),
			],
			'booking_price_currency'          => [
				'type'    => 'text',
				'label'   => __( 'Currency', 'jet-booking' ),
				'default' => __( '$', 'jet-booking' ),
			],
			'booking_price_currency_position' => [
				'type'    => 'select',
				'label'   => __( 'Currency Position', 'jet-booking' ),
				'default' => 'before',
				'options' => [
					'before' => __( 'Before', 'jet-booking' ),
					'after'  => __( 'After', 'jet-booking' ),
				],
			],
		];
	}

	/**
	 * Macros callback.
	 *
	 * Callback function to return macros value.
	 *
	 * @since 3.6.1
	 *
	 * @param array $args Macros arguments list.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function macros_callback( $args = [] ) {

		$currency          = ! empty( $args['booking_price_currency'] ) ? $args['booking_price_currency'] : __( '$', 'jet-booking' );
		$currency_position = ! empty( $args['booking_price_currency_position'] ) ? $args['booking_price_currency_position'] : 'before';
		$price             = new Price( get_the_ID() );
		$period            = apply_filters( 'jet-booking/macros/booking-price/period', [] );

		if ( empty( $period ) ) {
			$from = ! empty( $args['booking_period_start'] ) ? strtotime( $args['booking_period_start'] ) : '';
			$to   = ! empty( $args['booking_period_end'] ) ? strtotime( $args['booking_period_end'] ) : '';

			if ( empty( $from ) ) {
				return $price->get_min_price( $currency, $currency_position );
			}

			if ( empty( $to ) ) {
				$to = $from;
			}
		} else {
			[ $from, $to ] = $period;
		}

		$data = [
			'apartment_id'   => get_the_ID(),
			'check_in_date'  => intval( $from ),
			'check_out_date' => intval( $to ),
		];

		return $price->formatted_price( $price->get_booking_price( $data ), $currency, $currency_position );

	}

}
