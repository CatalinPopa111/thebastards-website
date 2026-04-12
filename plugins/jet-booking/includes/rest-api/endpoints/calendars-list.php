<?php
namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Calendars_List extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'calendars-list';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.0.0
	 * @since  2.7.0 Added iCal template variable handling.
	 * @since  4.0.0 Refactored.
	 * @access public
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$template = jet_abaf()->ical->get_ical_template();

		return rest_ensure_response( [
			'success' => true,
			'data'    => [
				'calendars'     => jet_abaf()->ical->get_calendars(),
				'ical_template' => $template ?: [],
			],
		] );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.0.0
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
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

}
