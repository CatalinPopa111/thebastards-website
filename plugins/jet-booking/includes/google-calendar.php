<?php
namespace JET_ABAF;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Google_Calendar {

	public $query_var = 'jet_abaf_add_to_calendar';

	public function __construct() {

		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! empty( $_GET[ $this->query_var ] ) ) {
			add_action( 'init', [ $this, 'redirect_to_calendar' ] );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		// Returns an array of hosts that plugin will redirect to.
		add_filter( 'allowed_redirect_hosts', function ( $domains ) {
			$domains[] = 'calendar.google.com';
			return array_unique( $domains );
		} );

		add_action( 'jet-abaf/form/notification/success', [ $this, 'set_default_cookie' ], 20 );
		add_action( 'jet-booking/wc-integration/process-order', [ $this, 'set_wc_cookie' ], 10, 4 );

	}

	public function get_secure_key() {

		$key = get_option( $this->query_var );

		if ( ! $key ) {
			$key = time() % 100000;
			update_option( $this->query_var, $key, false );
		}

		return $key;

	}

	/**
	 * Set WC cookies.
	 *
	 * Set default cookies on booking form submission success with WooCommerce integration enable.
	 *
	 * @since  3.0.0
	 *
	 * @param array      $booking   Process booking data list.
	 * @param string|int $order_id  Process order ID.
	 * @param \WC_Order  $order     WC order object instance.
	 * @param array      $cart_item Processed cart item data list.
	 *
	 * @return void
	 */
	public function set_wc_cookie( $booking, $order_id, $order, $cart_item ) {

		$data_key     = jet_abaf()->wc->data_key;
		$booking_data = ! empty( $cart_item[ $data_key ] ) ? $cart_item[ $data_key ] : false;

		if ( ! $booking_data ) {
			return;
		}

		$this->set_default_cookie( $booking_data );

	}

	/**
	 * Set default cookies.
	 *
	 * Set default cookies on booking form submission success.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param array $booking Booking arguments.
	 *
	 * @return void
	 */
	public function set_default_cookie( $booking ) {

		$booking_id = ! empty( $booking['booking_id'] ) ? $booking['booking_id'] : false;

		if ( ! $booking_id ) {
			return;
		}

		$expire = time() + YEAR_IN_SECONDS;
		$secure = ( false !== strstr( get_option( 'home' ), 'https:' ) && is_ssl() );

		setcookie(
			$this->query_var,
			$booking_id,
			$expire,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			$secure,
			true
		);

	}

	public function get_id_from_cookies() {
		return ! empty( $_COOKIE[ $this->query_var ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $this->query_var ] ) ) : false;
	}

	/**
	 * Redirect to calendar.
	 *
	 * Redirect to the event creation calendar page.
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @return void
	 */
	public function redirect_to_calendar() {

		$booking_id = ! empty( $_GET[ $this->query_var ] ) ? absint( $_GET[ $this->query_var ] ) : null; // phpcs:ignore

		if ( ! $booking_id ) {
			wp_die( esc_html__( 'Booking ID not found in the request.', 'jet-booking' ), esc_html__( 'Error', 'jet-booking' ) );
		}

		$booking_id = $this->get_booking_id_from_secure_id( $booking_id );

		if ( ! $booking_id ) {
			wp_die( esc_html__( 'Booking ID not found in the request.', 'jet-booking' ), esc_html__( 'Error', 'jet-booking' ) );
		}

		$booking = jet_abaf()->db->get_booking_by( 'booking_id', $booking_id );

		if ( ! $booking ) {
			wp_die( esc_html__( 'Booking not found in the database.', 'jet-booking' ), esc_html__( 'Error', 'jet-booking' ) );
		}

		$url = $this->get_calendar_url_by_booking( $booking );

		if ( ! $url ) {
			wp_die( esc_html__( 'Can`t build add to calendar URL.', 'jet-booking' ), esc_html__( 'Error', 'jet-booking' ) );
		}

		wp_safe_redirect( $url );
		exit;

	}

	/**
	 * Get calendar url by booking.
	 *
	 * Creates a link to automatically create an event in Google calendar.
	 *
	 * @since  2.1.0
	 * @since  2.5.4 Added `jet-booking/google-calendar-url/utc-timezone` hook.
	 * @access public
	 *
	 * @param array $booking Parameters list.
	 *
	 * @return string
	 */
	public function get_calendar_url_by_booking( $booking ) {

		$args = [
			'action'   => 'TEMPLATE',
			'text'     => '',
			'dates'    => '',
			'details'  => '',
			'location' => '',
		];

		$args['text'] = urlencode( sprintf( '%s %s: %s', __( 'Your booking at', 'jet-booking' ), get_option( 'blogname' ), get_the_title( $booking['apartment_id'] ) ) );

		$utc_timezone = apply_filters( 'jet-booking/google-calendar-url/utc-timezone', false );

		$args['dates'] = sprintf(
			'%1$sT120000%3$s/%2$sT120000%3$s',
			date( 'Ymd', $booking['check_in_date'] ),
			date( 'Ymd', $booking['check_out_date'] ),
			$utc_timezone ? 'Z' : ''
		);

		$args = apply_filters( 'jet-booking/google-calendar-url/args', $args, $booking );

		return add_query_arg( array_filter( $args ), 'https://calendar.google.com/calendar/render' );

	}

	public function get_booking_id_from_secure_id( $secure_id ) {

		$secure_id  = absint( $secure_id );
		$booking_id = apply_filters( 'jet-booking/google-calendar-url/booking-id', false, $secure_id, $this );

		if ( ! $booking_id ) {
			$key        = $this->get_secure_key();
			$booking_id = $secure_id - $key;
		}

		return $booking_id;

	}

	public function secure_id( $booking_id ) {

		$secured_id = apply_filters( 'jet-booking/google-calendar-url/secure-id', false, $booking_id, $this );

		if ( ! $secured_id ) {
			$key        = $this->get_secure_key();
			$secured_id = $key + absint( $booking_id );
		}

		return $secured_id;

	}

	/**
	 * Get internal link.
	 *
	 * Returns creation calendar event link for queried booking.
	 *
	 * @since  2.1.0
	 * @since  3.1.0 Refactored.
	 * @access public
	 *
	 * @param mixed $booking_id Booking ID.
	 *
	 * @return false|string
	 */
	public function get_internal_link( $booking_id ) {

		if ( ! $booking_id ) {
			$booking_id = $this->get_id_from_cookies();
		}

		if ( ! $booking_id ) {
			return false;
		}

		$booking_id = $this->secure_id( $booking_id );

		return add_query_arg( [ $this->query_var => $booking_id ], home_url( '/' ) );

	}

}