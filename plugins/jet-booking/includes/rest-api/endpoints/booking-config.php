<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Booking_Config extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.2.5
	 *
	 * @return string
	 */
	public function get_name() {
		return 'booking-config';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.2.5
	 * @since  3.2.0 Refactored.
	 * @since  3.6.0 Added attributes & guests handling.
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 * @throws \Exception
	 */
	public function callback( $request ) {

		$params = $request->get_params();
		$item   = $params['item'] ?? [];

		if ( empty( $item ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Booking item data is required but was not provided.', 'jet-booking' ),
			] );
		}

		if ( empty( $item['apartment_id'] ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Missing required apartment identifier.', 'jet-booking' ),
			] );
		}

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $item['apartment_id'], 'jet-booking', 60 );

		$localized_data = jet_abaf()->assets->get_localized_data( $item['apartment_id'] );
		$response       = [
			'success'       => true,
			'start_of_week' => get_option( 'start_of_week' ) ? 'monday' : 'sunday',
			'units'         => jet_abaf()->db->get_apartment_units( $item['apartment_id'] ),
		];

		if ( 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			$has_guests = get_post_meta( $item['apartment_id'], '_jet_booking_has_guests', true );

			if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) ) {
				$response['guests_settings'] = [
					'min' => get_post_meta( $item['apartment_id'], '_jet_booking_min_guests', true ) ?: 1,
					'max' => get_post_meta( $item['apartment_id'], '_jet_booking_max_guests', true ) ?: 1,
				];
			} else {
				$response['guests_settings'] = [];
			}

			$response['attributes_list'] = jet_abaf()->wc->mode->attributes->get_attributes( $item['apartment_id'] );
		}

		return rest_ensure_response( wp_parse_args( $localized_data, $response ) );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.2.5
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
	 * @since  2.2.5
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

}