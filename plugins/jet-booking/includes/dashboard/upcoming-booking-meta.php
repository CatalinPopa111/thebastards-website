<?php
namespace JET_ABAF\Dashboard;

use JET_ABAF\Dashboard\Traits\Bookings_List_Meta_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Upcoming_Booking_Meta {

	use Bookings_List_Meta_Trait;

	public function __construct() {

		$this->post_type = jet_abaf()->settings->get( 'apartment_post_type' );

		if ( empty( $this->post_type ) || ! is_array( $this->post_type ) ) {
			return;
		}

		// Registers a meta-box for displaying booking data in the post-edit screen.
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );

		// Initializes the bookings list meta functionality.
		add_action( 'admin_enqueue_scripts', [ $this, 'init_bookings_list_meta' ] );

		new Units_Manager( $this->post_type );

	}

	/**
	 * Register a meta-box.
	 *
	 * Registers a meta-box for displaying upcoming booking data in the post-edit screen.
	 *
	 * @since  2.0.0
	 * @since  3.2.0 Refactored.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'jet-abaf-upcoming-booking-meta',
			__( 'Upcoming Bookings', 'jet-booking' ),
			[ $this, 'render_meta_box' ],
			$this->post_type
		);
	}

}
