<?php
/**
 * Base Endpoint.
 *
 * Base class for REST API endpoint.
 *
 * @since   3.2.0
 *
 * @package JET_ABAF\Rest_API\Endpoints
 */

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

abstract class Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since 3.2.0
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return mixed
	 */
	abstract public function callback( $request );

	/**
	 * Get method.
	 *
	 * Returns endpoint request method - GET/POST/PUT/DELETE
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current endpoint.
	 *
	 * @since 3.2.0
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return true;
	}

	/**
	 * Get query params.
	 *
	 * Get query param. Regex with query parameters.
	 * Example: (?P<id>[\d]+)/(?P<meta_key>[\w-]+)
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_query_params() {
		return '';
	}

	/**
	 * Get args.
	 *
	 * Returns arguments config.
	 * Example:
	 *    array(
	 *        array(
	 *            'type' => array(
	 *            'default'  => '',
	 *            'required' => false,
	 *        ),
	 *    )
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	public function get_args() {
		return [];
	}

}
