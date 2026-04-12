<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Delete_Booking extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'delete-booking';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.0.0
	 * @since  4.0.0 Added booking vendor capability check.
	 * @access public
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params     = $request->get_params();
		$booking_id = ! empty( $params['id'] ) ? absint( $params['id'] ) : 0;

		if ( ! $booking_id ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Booking ID is not found in request.', 'jet-booking' ),
			] );
		}

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'No booking found with the specified ID.', 'jet-booking' ),
			] );
		}

		$user_id = get_current_user_id();

		if ( jet_abaf()->vendors->is_booking_vendor( $user_id ) && (int) $booking->get_booking_vendor() !== $user_id ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'You are not allowed to delete bookings created by other vendors.', 'jet-booking' ),
			] );
		}

		jet_abaf()->db->delete_booking( [ 'booking_id' => $booking_id ] );

		return rest_ensure_response( [ 'success' => true ] );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.0.0
	 * @since  4.0.0 Added booking vendor capability check.
	 * @access public
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( \JET_ABAF\Capabilities::CAP_MANAGE_POST );
	}

	/**
	 * Get method.
	 *
	 * Returns endpoint request method - GET/POST/PUT/DELETE.
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'DELETE';
	}

	/**
	 * Get args.
	 *
	 * Returns arguments config.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_query_params() {
		return '(?P<id>[\d]+)';
	}

}