<?php
namespace JET_ABAF\Macros;

use Crocoblock\Macros_Handler;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Macros handler instance holder.
	 *
	 * @var Macros_Handler|null
	 */
	public $macros_handler = null;

	public function __construct() {

		// Initialize macros handler class.
		$this->macros_handler = new Macros_Handler( 'jet-booking' );

		// Register custom macros.
		add_action( 'jet-booking/register-macros', [ $this, 'register_macros' ] );

		// Triggers hook to register custom macros.
		$this->macros_handler->register_macros_list();

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
	 * @param Macros_Handler $handler Macros handler instance.
	 */
	public function register_macros( $handler ) {

		if ( ! class_exists( '\Crocoblock\Base_Macros' ) ) {
			require_once JET_ABAF_PATH . 'includes/framework/macros/base-macros.php';
		}

		$handler->register_macros( new Tags\Booking_Accommodation_Status() );
		$handler->register_macros( new Tags\Booking_Cancel_URL() );
		$handler->register_macros( new Tags\Booking_Data() );
		$handler->register_macros( new Tags\Booking_Instance_Meta() );
		$handler->register_macros( new Tags\Booking_Instance_Title() );
		$handler->register_macros( new Tags\Booking_Price() );
		$handler->register_macros( new Tags\Booking_Price_Per_Day_Night() );
		$handler->register_macros( new Tags\Booking_Status() );
		$handler->register_macros( new Tags\Booking_Unit_Title() );
		$handler->register_macros( new Tags\Booking_Units_Count() );
		$handler->register_macros( new Tags\Booking_Vendor() );
		$handler->register_macros( new Tags\Bookings_Count() );

		$additional_columns = jet_abaf()->settings->get_clean_columns();

		if ( ! empty( $additional_columns ) ) {
			$handler->register_macros( new Tags\Booking_Column() );
		}

	}

}
