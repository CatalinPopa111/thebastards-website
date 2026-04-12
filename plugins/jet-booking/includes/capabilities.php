<?php
namespace JET_ABAF;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Capabilities {

	const CAP_MANAGE_POST = 'jet_booking_manage_post';
	const CAP_MANAGE_OTHERS_POST = 'jet_booking_manage_others_post';

	/**
	 * Retrieves all available capabilities.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array An array of capability constants.
	 */
	public static function all() {
		return [
			self::CAP_MANAGE_POST,
			self::CAP_MANAGE_OTHERS_POST,
		];
	}

}
