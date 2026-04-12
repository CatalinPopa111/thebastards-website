<?php

namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Data_Trait {

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
		return 'booking_data';
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
		return __( 'Booking Data', 'jet-booking' );
	}

	/**
	 * Macros args.
	 *
	 * Return custom macros attributes list.
	 *
	 * @since 3.2.0
	 * @since 3.7.0 Added `user_email`, `check_in_time` and `check_out_time` options.
	 *
	 * @return array
	 */
	public function macros_args() {
		return [
			'booking_data_type'   => [
				'type'        => 'select',
				'label'       => __( 'Data Type', 'jet-booking' ),
				'description' => __( 'Select data type to get macros value from.', 'jet-booking' ),
				'options'     => [
					'ID'             => __( 'ID', 'jet-booking' ),
					'apartment_id'   => __( 'Instance', 'jet-booking' ),
					'order_id'       => __( 'Order ID', 'jet-booking' ),
					'user_id'        => __( 'User ID', 'jet-booking' ),
					'user_email'     => __( 'User E-mail', 'jet-booking' ),
					'check_in_date'  => __( 'Check In Date', 'jet-booking' ),
					'check_in_time'  => __( 'Check In Time', 'jet-booking' ),
					'check_out_date' => __( 'Check Out Date', 'jet-booking' ),
					'check_out_time' => __( 'Check Out Time', 'jet-booking' ),
				],
			],
			'booking_data_format' => [
				'type'        => 'select',
				'label'       => __( 'Data Format', 'jet-booking' ),
				'description' => __( 'Select data format to get formatted macros value.', 'jet-booking' ),
				'options'     => [
					'plain'    => __( 'Plain', 'jet-booking' ),
					'readable' => __( 'Readable', 'jet-booking' ),
				],
				'default'     => 'plain',
				'condition'   => [
					'booking_data_type' => [
						'apartment_id',
						'check_in_date',
						'check_in_time',
						'check_out_date',
						'check_out_time',
					],
				],
			],
			'booking_date_format' => [
				'type'        => 'text',
				'label'       => __( 'Date/Time Format', 'jet-booking' ),
				'description' => __( 'Specify the date/time format in which check-in and check-out data should be displayed.', 'jet-booking' ),
				'condition'   => [
					'booking_data_type'   => [ 'check_in_date', 'check_in_time', 'check_out_date', 'check_out_time' ],
					'booking_data_format' => 'readable'
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
	 * @since 3.7.0 Added `check_in_time` and `check_out_time` handling.
	 *
	 * @param array                       $args    Macros arguments list.
	 * @param \JET_ABAF\Resources\Booking $booking Booking instance.
	 *
	 * @return string
	 */
	public function macros_callback( $args = [], $booking = null ) {

		$data_type   = ! empty( $args['booking_data_type'] ) ? $args['booking_data_type'] : '';
		$data_format = ! empty( $args['booking_data_format'] ) ? $args['booking_data_format'] : 'plain';
		$date_format = ! empty( $args['booking_date_format'] ) ? $args['booking_date_format'] : get_option( 'date_format' );

		if ( ! $booking ) {
			$booking = apply_filters( 'jet-booking/macros/booking-object', $booking );
		}

		if ( ! $booking || ! is_a( $booking, 'JET_ABAF\Resources\Booking' ) ) {
			return '';
		}

		switch ( $data_type ) {
			case 'apartment_id':
				$result = $booking->get_apartment_id();

				if ( 'readable' === $data_format ) {
					$result = get_the_title( $result );
				}

				break;

			case 'check_in_date':
			case 'check_in_time':
			case 'check_out_date':
			case 'check_out_time':
				$result = $booking->get_data( $data_type );

				if ( 'readable' === $data_format ) {
					$result = date_i18n( $date_format, $result );
				}

				break;

			default:
				$result = $booking->get_data( $data_type );
				break;
		}

		return $result;

	}

}
