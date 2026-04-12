<?php
namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Update_ICal_Template extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.7.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'update-ical-template';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.7.0
	 * @since  4.0.0 Refactored.
	 * @access public
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params   = $request->get_params();
		$template = ! empty( $params['template'] ) ? $params['template'] : [];

		jet_abaf()->ical->set_ical_template( $template );

		return rest_ensure_response( [
			'success' => true,
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
		return 'POST';
	}

}