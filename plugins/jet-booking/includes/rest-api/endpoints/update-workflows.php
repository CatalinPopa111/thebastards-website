<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Update_Workflows extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  3.7.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'update-workflows';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  3.7.0
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params    = $request->get_params();
		$workflows = ! empty( $params['workflows'] ) ? $params['workflows'] : [];

		jet_abaf()->workflows->collection->update_workflows( $workflows );

		return rest_ensure_response( [ 'success' => true, ] );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  3.7.0
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get method.
	 *
	 * Returns endpoint request method - GET/POST/PUT/DELETE.
	 *
	 * @since  3.7.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

	/**
	 * Get args.
	 *
	 * Returns arguments config.
	 *
	 * @since 3.7.0
	 *
	 * @return array
	 */
	public function get_args() {
		return [
			'workflows' => [
				'default'  => [],
				'required' => true,
			],
		];
	}

}