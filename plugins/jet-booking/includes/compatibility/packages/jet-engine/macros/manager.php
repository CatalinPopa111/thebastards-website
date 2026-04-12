<?php
namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Macros;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * A reference to an instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	public function __construct() {

		// Register macros.
		add_action( 'jet-engine/register-macros', [ $this, 'register_macros' ] );

		// Set booking object from listings.
		add_filter( 'jet-booking/macros/booking-object', [ $this, 'set_listing_booking_object' ] );

	}

	/**
	 * Register macros.
	 *
	 * Registers and returns specific macros list for booking functionality.
	 *
	 * @since  3.2.0
	 * @since  3.6.1 Added `Booking_Price` macros.
	 * @since  4.0.0 Added `Booking_Vendor` macros.
	 * @access public
	 *
	 * @return void
	 */
	public function register_macros() {

		new Tags\Booking_Accommodation_Status();
		new Tags\Booking_Cancel_URL();
		new Tags\Booking_Data();
		new Tags\Booking_Price();
		new Tags\Booking_Price_Per_Day_Night();
		new Tags\Booking_Status();
		new Tags\Booking_Unit_Title();
		new Tags\Booking_Units_Count();
		new Tags\Booking_Vendor();
		new Tags\Bookings_Count();

		$additional_columns = jet_abaf()->settings->get_clean_columns();

		if ( ! empty( $additional_columns ) ) {
			new Tags\Booking_Column();
		}

	}

	/**
	 * Set listing booking object.
	 *
	 * Maybe set booking object from listing item for booking macros functionality.
	 *
	 * @since 3.2.0
	 *
	 * @return false|\JET_ABAF\Resources\Booking|object
	 */
	public function set_listing_booking_object() {

		$obj = jet_engine()->listings->data->get_current_object();

		if ( $obj && is_a( $obj, 'JET_ABAF\Resources\Booking' ) ) {
			return $obj;
		}

		return false;

	}

	/**
	 * Returns the instance.
	 *
	 * @since  3.2.0
	 *
	 * @return object
	 */
	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

}