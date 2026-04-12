<?php
namespace JET_ABAF\Dashboard;

use JET_ABAF\Dashboard\Traits\Bookings_List_Meta_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Order_Meta {

	use Bookings_List_Meta_Trait;

	public function __construct() {

		if ( 'plain' !== jet_abaf()->settings->get( 'booking_mode' ) ) {
			return;
		}

		$post_type = jet_abaf()->settings->get( 'related_post_type' );

		if ( ! $post_type ) {
			return;
		}

		$this->post_type = is_array( $post_type ) ? $post_type : [ $post_type ];

		// Registers a meta-box for displaying booking data in the post-edit screen.
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );

		// Initializes the bookings list meta functionality.
		add_action( 'admin_enqueue_scripts', [ $this, 'init_bookings_list_meta' ] );

		// Handle post-type order delete.
		add_action( 'delete_post', [ $this, 'delete_booking_on_related_post_delete' ] );
		// Handle related order on linked booking item deletion.
		add_action( 'jet-booking/db/before-booking-delete', [ $this, 'delete_related_post_on_booking_delete' ] );

	}

	/**
	 * Register a meta-box.
	 *
	 * Registers a meta-box for displaying booking data in the post-edit screen.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'jet-abaf-order-meta',
			__( 'Booking Data', 'jet-booking' ),
			[ $this, 'render_meta_box' ],
			$this->post_type
		);
	}

	/**
	 * Delete booking on related post deletion.
	 *
	 * Delete related booking item on booking order post deletion.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Booking order post ID.
	 *
	 * @return void
	 */
	public function delete_booking_on_related_post_delete( $post_id ) {

		remove_action( 'jet-booking/db/before-booking-delete', [ $this, 'delete_related_post_on_booking_delete' ] );

		if ( in_array( get_post_type( $post_id ), $this->post_type ) ) {
			jet_abaf()->db->delete_booking( [ 'order_id' => $post_id ] );
		}

	}

	/**
	 * Delete related post on booking delete.
	 *
	 * Deletes the related post associated with a booking when the booking is deleted.
	 *
	 * @since 3.5.1
	 * @since 3.8.0 Added multiple bookings support.
	 *
	 * @param array $args Arguments containing booking details.
	 *
	 * @return void
	 */
	public function delete_related_post_on_booking_delete( $args ) {

		if ( empty( $args['booking_id'] ) ) {
			return;
		}

		$booking = jet_abaf_get_booking( $args['booking_id'] );

		if ( ! $booking ) {
			return;
		}

		$related_post_id = $booking->get_order_id();

		if ( empty( $related_post_id ) ) {
			return;
		}

		$bookings = jet_abaf_get_bookings( [ 'order_id' => $related_post_id ] );

		if ( ! empty( $bookings ) && count( $bookings ) > 1 ) {
			return;
		}

		wp_delete_post( $related_post_id, true );

	}

}