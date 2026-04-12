<?php
namespace JET_ABAF\Actions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Action token keyholder.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public static $token_key = '__action_token';

	public function __construct() {

		if ( ! jet_abaf()->settings->get( 'booking_cancellation' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'cancel_booking' ], 20 );
		add_action( 'jet-booking/db/booking-inserted', [ $this, 'save_action_meta' ] );

		// Enqueue scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	}

	/**
	 * Save action meta.
	 *
	 * Save action token for booking meta.
	 *
	 * @since 3.3.0
	 *
	 * @param array $booking Booking data list.
	 */
	public function save_action_meta( $booking ) {
		jet_abaf()->db->bookings_meta->set_meta( $booking['booking_id'], self::$token_key, $this->get_token( $booking['booking_id'] ) );
	}

	/**
	 * Get token.
	 *
	 * Returns token for booking based on booking parameters.
	 *
	 * @since 3.3.0
	 *
	 * @param @param int|string $booking_id Booking ID.
	 *
	 * @return string
	 */
	public function get_token( $booking_id ) {
		$booking = jet_abaf_get_booking( $booking_id );

		return md5( $booking->get_apartment_id() . $booking->get_check_in_date() . $booking->get_check_out_date() ) . time();
	}

	/**
	 * Cancel a booking.
	 *
	 * @since 3.3.0
	 */
	public function cancel_booking() {

		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_GET['cancel_booking'] ) || ! isset( $_GET[ self::$token_key ] ) ) {
			return;
		}

		$booking = $this->get_booking_by_token( sanitize_text_field( wp_unslash( $_GET[ self::$token_key ] ) ) );;
		// phpcs:enable WordPress.Security.NonceVerification

		if ( ! $booking ) {
			do_action( 'jet-booking/actions/cancel-booking/invalid-booking' );
			return;
		}

		if ( $booking->get_user_id() !== get_current_user_id() ) {
			do_action( 'jet-booking/actions/cancel-booking/invalid-booking' );
		} elseif ( in_array( $booking->get_status(), jet_abaf()->statuses->invalid_statuses() ) ) {
			// Already cancelled - take no action.
		} else {
			jet_abaf()->db->update_booking( $booking->get_id(), [ 'status' => 'cancelled' ] );
			jet_abaf()->db->bookings_meta->delete( [ 'booking_id' => $booking->get_id(), 'meta_key' => self::$token_key ] );

			do_action( 'jet-booking/actions/cancel-booking/cancelled', $booking->get_id() );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! empty( $_GET['redirect'] ) ) {
			wp_safe_redirect( wp_unslash( $_GET['redirect'] ) );
			exit;
		}
		// phpcs:enable WordPress.Security.NonceVerification

	}

	/**
	 * Get booking by token.
	 *
	 * Return booking if it has action token.
	 *
	 * @since 3.3.0
	 *
	 * @param string $token Action token.
	 *
	 * @return false|mixed
	 */
	public function get_booking_by_token( $token ) {

		$raw_meta = jet_abaf()->db->query( [
			'meta_key'   => self::$token_key,
			'meta_value' => $token
		], jet_abaf()->db->bookings_meta->table() );

		if ( empty( $raw_meta ) ) {
			return false;
		}

		$booking_id = $raw_meta[0]['booking_id'];

		return jet_abaf_get_booking( $booking_id );

	}

	/**
	 * Enqueue scripts.
	 *
	 * Enqueue scripts and variables for actions.
	 *
	 * @since 3.3.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'jet-booking-actions', JET_ABAF_URL . 'assets/js/actions.js', [ 'jquery' ], JET_ABAF_VERSION, true );

		wp_localize_script( 'jet-booking-actions', 'JetABAFActionsData', [
			'cancel_confirmation' => __( 'Are you sure you want to cancel your booking?', 'jet-booking' ),
		] );

	}

}