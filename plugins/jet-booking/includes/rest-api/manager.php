<?php
/**
 * Rest API manager.
 *
 * Controller class for all JetBooking related API endpoints.
 *
 * @since   2.0.0
 * @since   3.2.0 Updated class logic to collectors.
 *
 * @package JET_ABAF\Rest_API
 */

namespace JET_ABAF\Rest_API;

defined( 'ABSPATH' ) || exit;

class Manager {

	/**
	 * API namespace.
	 *
	 * @since 3.2.0.
	 * @var string
	 */
	private $api_namespace = 'jet-booking/v2';

	/**
	 * Endpoints.
	 *
	 * @since 3.2.0.
	 * @var bool List of endpoints.
	 */
	private $_endpoints = false;

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register routes.
	 *
	 * Register JetBooking rest API routes.
	 *
	 * @since 3.2.0
	 */
	public function register_routes() {

		$endpoints = $this->get_endpoints();

		foreach ( $endpoints as $endpoint ) {
			$args = [
				'methods'             => $endpoint->get_method(),
				'callback'            => [ $endpoint, 'callback' ],
				'permission_callback' => [ $endpoint, 'permission_callback' ],
			];

			if ( ! empty( $endpoint->get_args() ) ) {
				$args['args'] = $endpoint->get_args();
			}

			$route = '/' . $endpoint->get_name() . '/' . $endpoint->get_query_params();

			register_rest_route( $this->api_namespace, $route, $args );
		}

	}

	/**
	 * Get endpoints.
	 *
	 * Returns all registered API endpoints.
	 *
	 * @since 3.2.0
	 *
	 * @return mixed
	 */
	public function get_endpoints() {

		if ( false === $this->_endpoints ) {
			$this->init_endpoints();
		}

		return $this->_endpoints;

	}

	/**
	 * Init endpoints.
	 *
	 * Initialize all JetBooking related Rest API endpoints.
	 *
	 * @since  3.2.0
	 * @since  3.7.0 Added `Update_Workflows` endpoint.
	 * @access public
	 *
	 * @return void
	 */
	public function init_endpoints() {

		$this->_endpoints = [];

		$this->register_endpoint( new Endpoints\Add_Booking() );
		$this->register_endpoint( new Endpoints\Booking_Config() );
		$this->register_endpoint( new Endpoints\Bookings_List() );
		$this->register_endpoint( new Endpoints\Delete_Booking() );
		$this->register_endpoint( new Endpoints\Update_Booking() );
		$this->register_endpoint( new Endpoints\Update_Workflows() );

		if ( jet_abaf()->settings->get( 'ical_synch' ) ) {
			$this->register_endpoint( new Endpoints\Calendars_List() );
			$this->register_endpoint( new Endpoints\Synch_Calendar() );
			$this->register_endpoint( new Endpoints\Update_Calendar() );
			$this->register_endpoint( new Endpoints\Update_ICal_Template() );
		}

	}

	/**
	 * Register endpoint.
	 *
	 * Register new JetBooking related rest API endpoint.
	 *
	 * @since 3.2.0
	 *
	 * @param object $endpoint_instance Endpoint instance.
	 */
	public function register_endpoint( $endpoint_instance = null ) {
		if ( $endpoint_instance ) {
			$this->_endpoints[ $endpoint_instance->get_name() ] = $endpoint_instance;
		}
	}

	/**
	 * Get route.
	 *
	 * Returns route to passed endpoint.
	 *
	 * @since 3.2.0
	 *
	 * @param string  $endpoint Endpoint name.
	 * @param boolean $full     Url type.
	 *
	 * @return string
	 */
	public function get_route( $endpoint = '', $full = false ) {

		$path = '/' . $this->api_namespace . '/' . $endpoint . '/';

		if ( ! $full ) {
			return $path;
		} else {
			return get_rest_url( null, $path );
		}

	}

	/**
	 * Get urls.
	 *
	 * Returns all registered Rest API URLs
	 *
	 * @since  2.0.0
	 * @since  2.5.4 Added new route `booked_dates`.
	 * @since  2.7.0 Added new route `update-ical-template`.
	 * @since  3.2.0 Change JetEngine related methods to local.
	 * @since  3.7.0 Added new route `update-workflows`.
	 * @access public
	 *
	 * @param bool $full Url type.
	 *
	 * @return array
	 */
	public function get_urls( $full = true ) {

		$res = [
			'bookings_list'    => $this->get_route( 'bookings-list', $full ),
			'add_booking'      => $this->get_route( 'add-booking', $full ),
			'delete_booking'   => $this->get_route( 'delete-booking', $full ),
			'update_booking'   => $this->get_route( 'update-booking', $full ),
			'booking_config'   => $this->get_route( 'booking-config', $full ),
			'update_workflows' => $this->get_route( 'update-workflows', $full ),
		];

		if ( jet_abaf()->settings->get( 'ical_synch' ) ) {
			$res['calendars_list']       = $this->get_route( 'calendars-list', $full );
			$res['update_calendar']      = $this->get_route( 'update-calendar', $full );
			$res['synch_calendar']       = $this->get_route( 'synch-calendar', $full );
			$res['update_ical_template'] = $this->get_route( 'update-ical-template', $full );
		}

		return $res;

	}

}

