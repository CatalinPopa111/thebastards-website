<?php

namespace JET_ABAF\Macros\Traits;

use \JET_ABAF\Price;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Price_Per_Day_Night_Trait {

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
		return 'booking_price_per_day_night';
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
		return __( 'Booking Price Per Day/Night', 'jet-booking' );
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
			'booking_shown_price'                  => [
				'type'        => 'select',
				'label'       => __( 'Shown Price', 'jet-booking' ),
				'description' => __( 'Select booking price that will be displayed.', 'jet-booking' ),
				'default'     => 'default',
				'options'     => [
					'default' => __( 'Default', 'jet-booking' ),
					'min'     => __( 'Min Price', 'jet-booking' ),
					'max'     => __( 'Max Price', 'jet-booking' ),
					'range'   => __( 'Prices Range', 'jet-booking' ),
				],
			],
			'booking_price_type'                   => [
				'type'        => 'select',
				'label'       => __( 'Price Type', 'jet-booking' ),
				'description' => __( 'Dynamic price type will change price dynamically on check-in check-out dates select. Will work correctly only when appropriate form presented on the page.', 'jet-booking' ),
				'default'     => 'dynamic',
				'options'     => [
					'static'  => __( 'Static', 'jet-booking' ),
					'dynamic' => __( 'Dynamic', 'jet-booking' ),
				],
			],
			'booking_price_currency_sign'          => [
				'type'    => 'text',
				'label'   => __( 'Currency', 'jet-booking' ),
				'default' => __( '$', 'jet-booking' ),
			],
			'booking_price_currency_sign_position' => [
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
	 * @since 3.2.0
	 * @since 3.6.1 Refactored.
	 *
	 * @param array $args Macros arguments list.
	 *
	 * @return string
	 */
	public function macros_callback( $args = [] ) {

		$price = new Price( get_the_ID() );

		return $price->get_price_for_display( [
			'show_price'             => ! empty( $args['booking_shown_price'] ) ? $args['booking_shown_price'] : 'default',
			'change_dynamically'     => ! empty( $args['booking_price_type'] ) && 'dynamic' === $args['booking_price_type'],
			'currency_sign'          => ! empty( $args['booking_price_currency_sign'] ) ? $args['booking_price_currency_sign'] : __( '$', 'jet-booking' ),
			'currency_sign_position' => ! empty( $args['booking_price_currency_sign_position'] ) ? $args['booking_price_currency_sign_position'] : 'before',
		] );

	}

}
