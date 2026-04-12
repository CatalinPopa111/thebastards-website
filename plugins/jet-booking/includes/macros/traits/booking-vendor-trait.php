<?php
namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Vendor_Trait {

	/**
	 * Macros tag.
	 *
	 * Returns macros tag.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function macros_tag() {
		return 'booking_vendor';
	}

	/**
	 * Macros name.
	 *
	 * Returns macros name.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function macros_name() {
		return __( 'Booking Vendor', 'jet-booking' );
	}

	/**
	 * Macros args.
	 *
	 * Return custom macros attributes list.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function macros_args() {
		return [
			'booking_vendor_data' => [
				'type'        => 'select',
				'label'       => __( 'Data Type', 'jet-booking' ),
				'description' => __( 'Select data type to get macros value from.', 'jet-booking' ),
				'default'     => 'ID',
				'options'     => [
					'ID'           => __( 'ID', 'jet-booking' ),
					'user_login'   => __( 'Login', 'jet-booking' ),
					'user_email'   => __( 'Email', 'jet-booking' ),
					'display_name' => __( 'Display Name', 'jet-booking' ),
					'first_name'   => __( 'First Name', 'jet-booking' ),
					'last_name'    => __( 'Last Name', 'jet-booking' ),
				],
			],
		];
	}

	/**
	 * Macros callback.
	 *
	 * Callback function to return macros value.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array                       $args    Macros arguments list.
	 * @param \JET_ABAF\Resources\Booking $booking Booking instance.
	 *
	 * @return string
	 */
	public function macros_callback( $args = [], $booking = null ) {

		$data_type = ! empty( $args['booking_vendor_data'] ) ? $args['booking_vendor_data'] : '';

		if ( ! $booking ) {
			$booking = apply_filters( 'jet-booking/macros/booking-object', $booking );
		}

		if ( ! $booking || ! is_a( $booking, 'JET_ABAF\Resources\Booking' ) ) {
			return '';
		}

		$vendor_id = $booking->get_booking_vendor();

		if ( ! $vendor_id ) {
			return '';
		}

		switch ( $data_type ) {
			case 'ID':
				return $vendor_id;

			case 'user_login':
			case 'user_email':
			case 'display_name':
			case 'first_name':
			case 'last_name':
				$user_data = get_userdata( $booking->get_booking_vendor() );

				return $user_data->{$data_type} ?? '';

			default:
				return '';
		}

	}

}