<?php
/**
 * Booking base class.
 *
 * @package JET_ABAF\Resources
 */

namespace JET_ABAF\Resources;

use \JET_ABAF\Actions\Manager as Actions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

#[\AllowDynamicProperties]
class Booking {

	/**
	 * Booking ID.
	 *
	 * @var int
	 */
	protected $ID = 0;

	/**
	 * Booking vendor.
	 *
	 * @var int
	 */
	protected $booking_vendor = null;

	/**
	 * Status.
	 *
	 * @var string
	 */
	protected $status = 'pending';

	/**
	 * Apartment ID.
	 *
	 * @var int
	 */
	protected $apartment_id = null;

	/**
	 * Apartment unit ID.
	 *
	 * @var int
	 */
	protected $apartment_unit = null;

	/**
	 * Check in date.
	 *
	 * @var int
	 */
	protected $check_in_date = null;

	/**
	 * Check in time.
	 *
	 * @var int
	 */
	protected $check_in_time = null;

	/**
	 * Check out date.
	 *
	 * @var int
	 */
	protected $check_out_date = null;

	/**
	 * Check out time.
	 *
	 * @var int
	 */
	protected $check_out_time = null;

	/**
	 * Related order ID.
	 *
	 * @var int
	 */
	protected $order_id = null;

	/**
	 * Related user ID.
	 *
	 * @var int
	 */
	protected $user_id = null;

	/**
	 * Related user e-mail.
	 *
	 * @var string
	 */
	protected $user_email = '';

	/**
	 * Calendar import ID.
	 *
	 * @var string
	 */
	protected $import_id = '';

	/**
	 * Additional database table columns.
	 *
	 * @var array
	 */
	protected $columns = [];

	/**
	 * Booking attributes list.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Number of guests.
	 *
	 * @var int|string
	 */
	protected $__guests = 0;

	public function __construct( $booking ) {

		$this->set_id( absint( $booking['booking_id'] ?? 0 ) );
		$this->set_booking_vendor( absint( $booking['booking_vendor'] ?? 0 ) );
		$this->set_status( $booking['status'] ?? 'pending' );
		$this->set_apartment_id( absint( $booking['apartment_id'] ?? 0 ) );
		$this->set_apartment_unit( absint( $booking['apartment_unit'] ?? 0 ) );
		$this->set_check_in_date( absint( $booking['check_in_date'] ?? 0 ) );
		$this->set_check_in_time( absint( $booking['check_in_time'] ?? 0 ) );
		$this->set_check_out_date( absint( $booking['check_out_date'] ?? 0 ) );
		$this->set_check_out_time( absint( $booking['check_out_time'] ?? 0 ) );
		$this->set_order_id( absint( $booking['order_id'] ?? 0 ) );
		$this->set_user_id( absint( $booking['user_id'] ?? 0 ) );
		$this->set_user_email( $booking['user_email'] ?? '' );
		$this->set_import_id( $booking['import_id'] ?? '' );

		if ( ! empty( jet_abaf()->settings->get_clean_columns() ) ) {
			$columns = [];

			foreach ( jet_abaf()->settings->get_clean_columns() as $column ) {
				$columns[ $column ] = $booking[ $column ] ?? '';
			}

			$this->set_columns( $columns );
		}

		if ( 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			$product = wc_get_product( $this->get_apartment_id() );

			if ( $product && jet_abaf()->wc->mode->is_booking_product( $product ) ) {
				$this->set_attributes( $product );
				$this->set_guests( $product );
			}
		}

	}

	/**
	 * Allows access to private or protected properties dynamically.
	 *
	 * @param string $prop Name of the property to retrieve.
	 *
	 * @return mixed
	 */
	public function __get( $prop ) {
		return $this->$prop ?? null;
	}

	/**
	 * Get data.
	 *
	 * Return specified booking data type.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Data type name.
	 *
	 * @return mixed
	 */
	public function get_data( $type ) {
		return $this->$type;
	}

	/**
	 * Get ID.
	 *
	 * Returns booking ID.
	 *
	 * @since   3.1.0
	 * @access  public
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->ID;
	}

	/**
	 * Get booking vendor.
	 *
	 * Returns booking vendor.
	 *
	 * @since   4.0.0
	 * @access  public
	 *
	 * @return int
	 */
	public function get_booking_vendor() {
		return $this->booking_vendor;
	}

	/**
	 * Get status.
	 *
	 * Returns booking status.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get apartment id.
	 *
	 * Returns booking instance post type ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return int|null
	 */
	public function get_apartment_id() {
		return $this->apartment_id;
	}

	/**
	 * Get apartment unit.
	 *
	 * Returns booking instance post type unit ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return int|null
	 */
	public function get_apartment_unit() {
		return $this->apartment_unit;
	}

	/**
	 * Get check-in date.
	 *
	 * Returns booking check-in date.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return int|null
	 */
	public function get_check_in_date() {
		return $this->check_in_date;
	}

	/**
	 * Get check-in time.
	 *
	 * Returns booking check-in time.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @return int|null
	 */
	public function get_check_in_time() {
		return $this->check_in_time;
	}

	/**
	 * Get check-out date.
	 *
	 * Returns booking check-out date.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return int|null
	 */
	public function get_check_out_date() {
		return $this->check_out_date;
	}

	/**
	 * Get check-out time.
	 *
	 * Returns booking check-out time.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @return int|null
	 */
	public function get_check_out_time() {
		return $this->check_out_time;
	}

	/**
	 * Get order ID.
	 *
	 * Returns booking related order ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return int|null
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * Get user ID.
	 *
	 * Returns booking user ID.
	 *
	 * @since  3.3.0
	 *
	 * @return int|null
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Get user e-mail.
	 *
	 * Returns booking user e-mail.
	 *
	 * @since  3.7.0
	 *
	 * @return string
	 */
	public function get_user_email() {
		return $this->user_email;
	}

	/**
	 * Get import ID.
	 *
	 * Returns booking instance post type calendar import ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_import_id() {
		return $this->import_id;
	}

	/**
	 * Get columns.
	 *
	 * Return the list of additional database table columns.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Get column.
	 *
	 * Returns the value from specified additional database table column.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param string $name Name of column to get.
	 *
	 * @return mixed
	 */
	public function get_column( $name ) {

		$value = null;

		if ( array_key_exists( $name, $this->columns ) ) {
			$value = $this->columns[ $name ];
		}

		return $value;

	}

	/**
	 * Get attributes.
	 *
	 * Returns booking attributes.
	 *
	 * @since  3.6.0
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Get guests.
	 *
	 * Returns booking guests count.
	 *
	 * @since  3.6.0
	 *
	 * @return int|string
	 */
	public function get_guests() {
		return $this->__guests;
	}

	/**
	 * Get object vars.
	 *
	 * Returns an associative array of defined object accessible non-static properties.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	public function get_object_vars() {
		return get_object_vars( $this );
	}

	/**
	 * Set ID.
	 *
	 * Set booking ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param int $id Booking ID.
	 *
	 * @return void
	 */
	public function set_id( $id ) {
		$this->ID = $id;
	}

	/**
	 * Set booking vendor.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param int $booking_vendor Booking vendor.
	 *
	 * @return void
	 */
	public function set_booking_vendor( $booking_vendor ) {
		$this->booking_vendor = $booking_vendor;
	}

	/**
	 * Set status.
	 *
	 * Set booking status.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param string $status Booking status.
	 *
	 * @return void
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * Set apartment ID.
	 *
	 * Set booking instance post type ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param int $apartment_id Booking instance post type ID.
	 *
	 * @return void
	 */
	public function set_apartment_id( $apartment_id ) {
		$this->apartment_id = $apartment_id;
	}

	/**
	 * Set apartment unit.
	 *
	 * Set booking instance post type unit ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param int $apartment_unit Booking instance post type unit ID.
	 *
	 * @return void
	 */
	public function set_apartment_unit( $apartment_unit ) {
		$this->apartment_unit = $apartment_unit;
	}

	/**
	 * Set check-in date.
	 *
	 * Set booking check-in date.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param int $check_in_date Booking check-in date.
	 *
	 * @return void
	 */
	public function set_check_in_date( $check_in_date ) {
		$this->check_in_date = $check_in_date;
	}

	/**
	 * Set check-in time.
	 *
	 * Set booking check-in time.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @param int $check_in_time Booking check-in time.
	 *
	 * @return void
	 */
	public function set_check_in_time( $check_in_time ) {
		$this->check_in_time = $check_in_time;
	}

	/**
	 * Set check-out date.
	 *
	 * Set booking check-out date.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param int $check_out_date Booking check-out date.
	 *
	 * @return void
	 */
	public function set_check_out_date( $check_out_date ) {
		$this->check_out_date = $check_out_date;
	}

	/**
	 * Set check-out time.
	 *
	 * Set booking check-out time.
	 *
	 * @since  3.7.0
	 * @access public
	 *
	 * @param int $check_out_time Booking check-out time.
	 *
	 * @return void
	 */
	public function set_check_out_time( $check_out_time ) {
		$this->check_out_time = $check_out_time;
	}

	/**
	 * Set order ID.
	 *
	 * Set booking related order ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param int $order_id Booking related order ID.
	 *
	 * @return void
	 */
	public function set_order_id( $order_id ) {
		$this->order_id = $order_id;
	}

	/**
	 * Set user ID.
	 *
	 * Set booking user ID.
	 *
	 * @since  3.3.0
	 *
	 * @param int $user_id Booking user ID.
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Set user e-mail.
	 *
	 * Set booking user e-mail.
	 *
	 * @since  3.7.0
	 *
	 * @param string $user_email Booking user e-mail.
	 */
	public function set_user_email( $user_email ) {
		$this->user_email = $user_email;
	}

	/**
	 * Set import ID.
	 *
	 * Set booking instance post type calendar import ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param string $import_id Booking instance post type calendar import ID.
	 *
	 * @return void
	 */
	public function set_import_id( $import_id ) {
		$this->import_id = $import_id;
	}

	/**
	 * Set columns.
	 *
	 * Set additional database table columns.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param array $columns Additional database table columns.
	 *
	 * @return void
	 */
	public function set_columns( $columns ) {
		$this->columns = $columns;
	}

	/**
	 * Set attributes.
	 *
	 * Set booking attributes.
	 *
	 * @since  3.6.0
	 *
	 * @param \WC_Product $product WooCommerce product instance.
	 *
	 * @return void
	 */
	public function set_attributes( $product ) {

		if ( empty( array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' ) ) ) {
			return;
		}

		$related_order_item = jet_abaf()->wc->mode->get_booking_related_order_item( $this->get_id(), $this->get_order_id() );

		if ( ! $related_order_item ) {
			return;
		}

		$attributes = $related_order_item->get_meta( '__jet_booking_additional_services' );

		if ( ! empty( $attributes ) ) {
			$this->attributes = $attributes;
		}

	}

	/**
	 * Set guests.
	 *
	 * Set booking guests.
	 *
	 * @since  3.6.0
	 *
	 * @param \WC_Product $product WooCommerce product instance.
	 *
	 * @return void
	 */
	public function set_guests( $product ) {

		$has_guests = get_post_meta( $product->get_id(), '_jet_booking_has_guests', true );

		if ( ! filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$related_order_item = jet_abaf()->wc->mode->get_booking_related_order_item( $this->get_id(), $this->get_order_id() );

		if ( ! $related_order_item ) {
			return;
		}

		$guests = $related_order_item->get_meta( '__jet_booking_guests' );

		if ( ! empty( $guests ) ) {
			$this->__guests = $guests;
		}

	}

	/**
	 * Get cancel URL.
	 *
	 * Returns the cancel URL for a booking.
	 *
	 * @since 3.3.0
	 *
	 * @param string $url      A URL to act upon.
	 * @param string $redirect The path or URL to redirect to.
	 *
	 * @return string
	 */
	public function get_cancel_url( $url = '', $redirect = '' ) {
		return add_query_arg( [
			'cancel_booking'    => 'true',
			Actions::$token_key => jet_abaf()->db->bookings_meta->get_meta( $this->get_id(), Actions::$token_key ),
			'redirect'          => $redirect,
		], ! $url ? home_url() : $url );
	}

	/**
	 * Is cancellable.
	 *
	 * Check is booking can be cancelled.
	 *
	 * @since 3.3.0
	 *
	 * @return bool
	 */
	public function is_cancellable() {

		$limit = jet_abaf()->settings->get( 'cancellation_limit' );
		$unit  = jet_abaf()->settings->get( 'cancellation_unit' );

		if ( ! jet_abaf()->settings->get( 'booking_cancellation' ) || $this->passed_deadline( $limit, $unit ) ) {
			return false;
		}

		if ( ! jet_abaf()->db->bookings_meta->get_meta( $this->get_id(), Actions::$token_key ) ) {
			return false;
		}

		if ( in_array( $this->get_status(), jet_abaf()->statuses->invalid_statuses() ) || 'completed' === $this->get_status() ) {
			return false;
		}

		return true;

	}

	/**
	 * Is updatable.
	 *
	 * Check is booking can be updated.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function is_updatable() {

		$limit = jet_abaf()->settings->get( 'modification_limit' );
		$unit  = jet_abaf()->settings->get( 'modification_unit' );

		if ( $this->passed_deadline( $limit, $unit ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Passed deadline.
	 *
	 * Check if booking can be modified based on deadline setting.
	 *
	 * @since 3.4.0
	 *
	 * @param int|string $limit Limit number.
	 * @param string     $unit  Unit name.
	 *
	 * @return bool
	 */
	public function passed_deadline( $limit, $unit ) {

		$unit        = $limit > 1 ? $unit . 's' : $unit;
		$time_string = sprintf( '%s +%d %s', current_time( 'd F Y H:i:s' ), $limit, $unit );

		if ( strtotime( $time_string ) >= $this->get_check_in_date() ) {
			return true;
		}

		return false;

	}

}
