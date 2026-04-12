<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Add_Booking extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.5.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'add-booking';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.5.0
	 * @since  4.0.0 Added booking vendor capability check.
	 * @access public
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

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $item['apartment_id'], 'jet-booking', 60 );

		$user_id   = get_current_user_id();
		$author_id = get_post_field( 'post_author', $item['apartment_id'] );

		if ( jet_abaf()->vendors->is_booking_vendor( $user_id ) && (int) $author_id !== $user_id ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'You are not allowed to create bookings for posts you do not own.', 'jet-booking' ),
			] );
		}

		if ( empty( $item['check_in_date'] ) || empty( $item['check_out_date'] ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Check-in and check-out dates are required.', 'jet-booking' ),
			] );
		}

		$item['check_in_date']  = strtotime( $item['check_in_date'] );
		$item['check_out_date'] = strtotime( $item['check_out_date'] );

		if ( $item['check_in_date'] >= $item['check_out_date'] ) {
			$item['check_out_date'] = $item['check_in_date'] + 12 * HOUR_IN_SECONDS;
		}

		if ( empty( $item['apartment_unit'] ) ) {
			$item['apartment_unit'] = jet_abaf()->db->get_available_unit( $item );
		}

		$is_available       = jet_abaf()->db->booking_availability( $item );
		$is_dates_available = jet_abaf()->db->is_booking_dates_available( $item );
		$is_days_available  = jet_abaf()->tools->is_booking_period_available( $item );

		if ( ! $is_available && ! $is_dates_available || ! $is_days_available ) {
			ob_start();

			esc_html_e( 'Selected dates are not available.', 'jet-booking' ) . '<br>';

			if ( jet_abaf()->db->latest_result ) {
				esc_html_e( 'Overlapping bookings: ', 'jet-booking' );

				$result = [];

				foreach ( jet_abaf()->db->latest_result as $ob ) {
					if ( ! empty( $ob['order_id'] ) ) {
						$result[] = sprintf( '<a href="%s" target="_blank">#%s</a>', esc_url( get_edit_post_link( $ob['order_id'] ) ), esc_html( $ob['order_id'] ) );
					} else {
						$result[] = '#' . esc_html( $ob['booking_id'] );
					}
				}

				echo implode( ', ', $result ) . '.'; // phpcs:ignore
			}

			return rest_ensure_response( [
				'success'              => false,
				'overlapping_bookings' => true,
				'html'                 => ob_get_clean(),
				'data'                 => __( 'The selected dates are not available for booking.', 'jet-booking' ),
			] );
		}

		remove_all_actions( 'jet-booking/db/booking-inserted' );

		$booking_id = jet_abaf()->db->insert_booking( $item );

		if ( $booking_id && isset( $params['createRelatedOrder'] ) && filter_var( $params['createRelatedOrder'], FILTER_VALIDATE_BOOLEAN ) ) {
			$order_data = ! empty( $params['relatedOrder'] ) ? $params['relatedOrder'] : [];

			$this->set_related_order_data( $order_data, $booking_id, $item );
		}

		return rest_ensure_response( [ 'success' => true ] );

	}

	/**
	 * Set related order data.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added `$booking` parameter.
	 * @since  4.0.0 Added post author handling.
	 *
	 * @param array      $order_data Order data list.
	 * @param string|int $booking_id Created booking ID.
	 * @param array      $booking    Booking data list.
	 *
	 * @return void
	 */
	public function set_related_order_data( $order_data, $booking_id, $booking ) {
		if ( 'plain' === jet_abaf()->settings->get( 'booking_mode' ) && ! jet_abaf()->settings->wc_integration_enabled() ) {
			$post_type        = jet_abaf()->settings->get( 'related_post_type' );
			$post_type_object = get_post_type_object( $post_type );
			$post_author      = get_post_field( 'post_author', $booking['apartment_id'] );

			$args = [
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_author' => $post_author ?: get_current_user_id(),
			];

			if ( post_type_supports( $post_type, 'excerpt' ) ) {
				/* translators: Related post type singular name. */
				$args['post_excerpt'] = sprintf( __( 'This is %s post.', 'jet-booking' ), $post_type_object->labels->singular_name );
			}

			$post_id = wp_insert_post( $args );

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				return;
			}

			wp_update_post( [
				'ID'         => $post_id,
				'post_title' => $post_type_object->labels->singular_name . ' #' . $post_id,
				'post_name'  => $post_type_object->labels->singular_name . '-' . $post_id,
			] );

			jet_abaf()->db->update_booking( $booking_id, [ 'order_id' => $post_id ] );
		} else {
			do_action( 'jet-booking/rest-api/add-booking/set-related-order-data', $order_data, $booking_id, $booking );
		}
	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.5.0
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
	 * @since  2.5.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

}