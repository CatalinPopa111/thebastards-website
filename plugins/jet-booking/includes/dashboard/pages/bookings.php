<?php
namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;
use JET_ABAF\Price;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Bookings extends Base {

	/**
	 * Page slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'jet-abaf-bookings';
	}

	/**
	 * Page title.
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Bookings', 'jet-booking' );
	}

	/**
	 * Page capability.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function capability() {
		return \JET_ABAF\Capabilities::CAP_MANAGE_POST;
	}

	/**
	 * Page config.
	 *
	 * Return page config object.
	 *
	 * @since  2.8.0 Added Booking post type order & WC integration configuration parameters.
	 * @since  3.2.0 Added `apartment_id` filter.
	 * @since  3.7.0 Added timepicker related configurations.
	 * @since  4.0.0 Added a filter hook for bookings page config.
	 * @access public
	 *
	 * @return Page_Config
	 */
	public function page_config() {

		do_action( 'jet-abaf/dashboard/bookings-page/before-page-config' );

		$config = apply_filters( 'jet-booking/dashboard/bookings-page/config', [
			'api'                => jet_abaf()->rest_api->get_urls( false ),
			'ajax_url'           => esc_url( admin_url( 'admin-ajax.php' ) ),
			'booking_mode'       => jet_abaf()->settings->get( 'booking_mode' ),
			'instances_list'     => $this->get_booking_instances_list(),
			'bookings'           => jet_abaf()->tools->get_booking_posts_list(),
			'bookings_units'     => $this->get_bookings_units(),
			'bookings_list'      => jet_abaf()->db->query(),
			'statuses_schema'    => jet_abaf()->statuses->get_schema(),
			'all_statuses'       => jet_abaf()->statuses->get_statuses(),
			'columns'            => jet_abaf()->db->get_default_fields(),
			'additional_columns' => jet_abaf()->db->get_additional_db_columns(),
			'monday_first'       => get_option( 'start_of_week' ) ? true : false,
			'filters'            => [
				'apartment_id'   => [
					'type'       => 'select',
					'label'      => __( 'Instance', 'jet-booking' ),
					'value'      => jet_abaf()->tools->get_booking_posts_list(),
					'visibility' => true,
				],
				'status'         => [
					'type'       => 'select',
					'label'      => __( 'Status', 'jet-booking' ),
					'value'      => jet_abaf()->statuses->get_statuses(),
					'visibility' => true,
				],
				'check_in_date'  => [
					'type'       => 'date-picker',
					'label'      => __( 'Check In', 'jet-booking' ),
					'value'      => '',
					'visibility' => true,
				],
				'check_out_date' => [
					'type'       => 'date-picker',
					'label'      => __( 'Check Out', 'jet-booking' ),
					'value'      => '',
					'visibility' => true,
				],
			],
			'edit_link'          => add_query_arg( [
				'post'   => '%id%',
				'action' => 'edit',
			], admin_url( 'post.php' ) ),
			'export_nonce'       => jet_abaf()->export->get_nonce(),
			'export_url'         => jet_abaf()->export->base_url(),
			'timepicker'         => jet_abaf()->settings->get( 'timepicker' ),
			'timepicker_slots'   => [
				'check_in_slots'  => $this->get_timepicker_slots(),
				'check_out_slots' => $this->get_timepicker_slots(),
			],
		], $this );

		if ( jet_abaf()->settings->get( 'related_post_type' ) ) {
			$config['order_post_type'] = jet_abaf()->settings->get( 'related_post_type' );
		}

		return new Page_Config( $this->slug(), $config );

	}

	/**
	 * Get booking instances list.
	 *
	 * Retrieves a list of booking instances grouped by post type.
	 * Each booking instance includes the post type name and post titles.
	 *
	 * @since  4.0.0 Added support for vendor posts.
	 * @access public
	 *
	 * @return array An associative array where each post type is a key.
	 *               Each key contains:
	 *               - 'name': The singular name of the post type.
	 *               - 'posts': An associative array of post IDs and titles.
	 */
	public function get_booking_instances_list() {

		$result  = [];
		$user_id = get_current_user_id();

		foreach ( jet_abaf()->tools->get_booking_posts() as $post ) {
			if ( jet_abaf()->vendors->is_booking_vendor( $user_id ) && (int) $post->post_author !== $user_id ) {
				continue;
			}

			if ( empty( $result[ $post->post_type ]['name'] ) ) {
				$post_type_object = get_post_type_object( $post->post_type );

				$result[ $post->post_type ]['name'] = $post_type_object->labels->singular_name;
			}

			$result[ $post->post_type ]['posts'][ $post->ID ] = $post->post_title;
		}

		return $result;

	}

	/**
	 * Get bookings units.
	 *
	 * Returns all registered bookings units.
	 *
	 * @since  3.0.1
	 * @access public
	 *
	 * @return array
	 */
	public function get_bookings_units() {

		$posts = jet_abaf()->tools->get_booking_posts();
		$units = [];

		if ( empty( $posts ) ) {
			return $units;
		}

		foreach ( $posts as $post ) {
			$apartment_units = jet_abaf()->db->get_apartment_units( $post->ID );

			if ( ! empty( $apartment_units ) ) {
				foreach ( $apartment_units as $apartment_unit ) {
					$units[ $post->ID ][ $apartment_unit['unit_id'] ] = $apartment_unit['unit_title'];
				}
			}
		}

		return $units;

	}

	/**
	 * Get timepicker slots.
	 *
	 * Returns timepicker slots list based on the configured range and interval.
	 *
	 * @since 3.7.0
	 *
	 * @return array An array of available time slots formatted according to the WordPress time format.
	 */
	public function get_timepicker_slots( $start = '', $end = '' ) {

		if ( ! jet_abaf()->settings->get( 'timepicker' ) ) {
			return [];
		}

		$range_start = jet_abaf()->settings->get( 'timepicker_range_start' );
		$range_end   = jet_abaf()->settings->get( 'timepicker_range_end' );

		if ( empty( $start ) ) {
			$start = $range_start;
		}

		if ( empty( $end ) ) {
			$end = $range_end;
		}

		$start_time = intval( $range_start ) !== intval( $start ) ? $range_start : $start;
		$interval   = jet_abaf()->settings->get( 'timepicker_interval' );
		$format     = get_option( 'time_format' );
		$slots      = [];

		while ( intval( $start_time ) <= intval( $end ) ) {
			if ( intval( $start_time ) >= intval( $start ) ) {
				$slots[] = date_i18n( $format, $start_time );
			}

			$start_time += $interval;
		}

		return $slots;

	}

	/**
	 * Render.
	 *
	 * Page render function.
	 *
	 * @since  2.0.0
	 * @since  2.5.4 Added `jquery-date-range-picker` styles.
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="jet-abaf-bookings-page"></div>';
	}

	/**
	 * Assets.
	 *
	 * Dashboard booking page specific assets.
	 *
	 * @since  2.0.0
	 * @since  2.5.4 Added `moment-js`, `jquery-date-range-picker` scripts and style. Remove `vuejs-datepicker`.
	 * @since  2.8.0 Added `vuejs-datepicker` script for filtering.
	 * @since  3.0.0 Added `dashboard` styles from parent class.
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {

		parent::assets();

		$this->enqueue_script( 'vuex', 'assets/js/admin/lib/vuex.min.js' );
		$this->enqueue_script( 'jet-plugins', 'assets/lib/jet-plugins/jet-plugins.js', [ 'jquery' ] );
		$this->enqueue_script( 'moment-js', 'assets/lib/moment/js/moment.js' );
		$this->enqueue_script( 'jquery-date-range-picker-js', 'assets/lib/jquery-date-range-picker/js/daterangepicker.min.js', [ 'jquery', 'moment-js', 'jet-plugins' ] );
		$this->enqueue_script( 'vuejs-datepicker', 'assets/js/lib/vuejs-datepicker.min.js' );
		$this->enqueue_script( 'jet-abaf-meta-extras', 'assets/js/admin/meta-extras.js' );
		$this->enqueue_script( 'v-calendar', 'assets/js/admin/lib/v-calendar.umd.min.js' );
		$this->enqueue_script( 'v-gantt-chart', 'assets/js/admin/lib/v-gantt-chart.umd.min.js' );

		$this->enqueue_script( $this->slug(), 'assets/js/admin/bookings.js' );

		$this->enqueue_style( 'jquery-date-range-picker-css', 'assets/lib/jquery-date-range-picker/css/daterangepicker.css' );

	}

	/**
	 * Vue templates.
	 *
	 * Page components templates.
	 *
	 * @since  2.8.0 Added `bookings-filter` template.
	 * @access public
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [
			'add-new-booking',
			'bookings',
			'bookings-calendar',
			'bookings-filter',
			'bookings-list',
			'bookings-timeline',
			'bookings-view',
			'popup',
		];
	}

	public function __construct() {

		// Price calculation and display after date selection in admin area for add & edit popups.
		add_action( 'wp_ajax_jet_booking_product_get_total_price', [ $this, 'get_booking_total_price' ] );

		// Retrieves the apartment configuration status.
		add_action( 'wp_ajax_jet_booking_get_apartment_config', [ $this, 'get_booking_apartment_config' ] );

		// Retrieves the apartment timepicker slots.
		add_action( 'wp_ajax_jet_booking_get_available_timepicker_slots', [ $this, 'get_available_timepicker_slots' ] );

	}

	/**
	 * Get booking total price.
	 *
	 * Price calculation and display after date selection in admin area for add & edit popups.
	 *
	 * @since  3.0.0
	 * @since  3.2.0 Added `$booking` parameter to `jet-booking/booking-total-price` hook.
	 * @since  3.6.4 Fixed dates format issue.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function get_booking_total_price() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-bookings' ) ) { // phpcs:ignore
			wp_send_json_error();
		}

		$booking = ! empty( $_REQUEST['booking'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_REQUEST['booking'] ) ) : []; // phpcs:ignore

		if ( empty( $booking ) ) {
			wp_send_json_error();
		}

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $booking['apartment_id'], 'jet-booking', 60 );

		$booking['check_in_date']  = ! empty( $booking['check_in_date_timestamp'] ) ? $booking['check_in_date_timestamp'] : strtotime( $booking['check_in_date'] );
		$booking['check_out_date'] = ! empty( $booking['check_out_date_timestamp'] ) ? $booking['check_out_date_timestamp'] : strtotime( $booking['check_out_date'] );

		$price = new Price( $booking['apartment_id'] );

		$response['price'] = apply_filters( 'jet-booking/booking-total-price', $price->get_booking_price( $booking ), $booking );

		wp_send_json_success( $response );

	}

	/**
	 * Retrieves the apartment configuration for a given apartment ID.
	 *
	 * This function handles an AJAX request to fetch the apartment configuration for a specific post ID,
	 * and returns the apartment configuration status.
	 *
	 * @since 3.6.2
	 *
	 * @return void
	 */
	public function get_booking_apartment_config() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-bookings' ) ) { // phpcs:ignore
			wp_send_json_error();
		}

		$post_id = ! empty( $_REQUEST['postId'] ) ? absint( $_REQUEST['postId'] ) : null;

		if ( ! $post_id ) {
			wp_send_json_error();
		}

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $post_id, 'jet-booking', 60 );

		wp_send_json_success( [
			'apartment_config' => jet_abaf()->settings->get_config_setting( $post_id, 'enable_config' ),
		] );

	}

	/**
	 * Get available timepicker slots.
	 *
	 * Retrieves available time slots for booking.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function get_available_timepicker_slots() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-bookings' ) ) { // phpcs:ignore
			wp_send_json_error();
		}

		$item = ! empty( $_REQUEST['item'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_REQUEST['item'] ) ) : []; // phpcs:ignore

		if ( empty( $item ) ) {
			wp_send_json_error();
		}

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $item['apartment_id'], 'jet-booking', 60 );

		$check_in_time_slots = $check_out_time_slots = $this->get_timepicker_slots();

		if ( jet_abaf()->settings->get( 'timepicker_restrictions' ) ) {
			$range_start = jet_abaf()->settings->get( 'timepicker_range_start' );
			$range_end   = jet_abaf()->settings->get( 'timepicker_range_end' );
			$buffer      = jet_abaf()->settings->get( 'timepicker_buffer' );

			$check_in_date  = strtotime( $item['check_in_date'] );
			$check_out_date = strtotime( $item['check_out_date'] );

			if ( $check_in_date >= $check_out_date ) {
				$check_out_date = $check_in_date + 12 * HOUR_IN_SECONDS;
			}

			$check_in_bookings = jet_abaf()->db->query( [
				'apartment_id'   => $item['apartment_id'],
				'check_out_date' => $check_in_date
			] );

			if ( ! empty( $check_in_bookings ) ) {
				$check_out_time = $check_in_bookings[0]['check_out_time'];

				foreach ( $check_in_bookings as $check_in_booking ) {
					if ( $check_out_time && $check_in_booking['check_out_time'] < $check_out_time ) {
						$check_out_time = $check_in_booking['check_out_time'];
					}
				}

				$check_in_start_time = '';

				if ( $check_out_time ) {
					$check_in_start_time = $check_out_time + $buffer;
				}

				if ( $check_in_start_time ) {
					$check_in_time_slots = $this->get_timepicker_slots( $check_in_start_time, $range_end );
				}
			}

			$check_out_bookings = jet_abaf()->db->query( [
				'apartment_id'  => $item['apartment_id'],
				'check_in_date' => $check_out_date + 1,
			] );

			if ( ! empty( $check_out_bookings ) ) {
				$check_in_time = $check_out_bookings[0]['check_in_time'];

				foreach ( $check_out_bookings as $check_out_booking ) {
					if ( $check_in_time && $check_out_booking['check_in_time'] > $check_in_time ) {
						$check_in_time = $check_out_booking['check_in_time'];
					}
				}

				$check_out_end_time = '';

				if ( $check_in_time ) {
					$check_out_end_time = $check_in_time - $buffer;
				}

				if ( $check_out_end_time ) {
					$check_out_time_slots = $this->get_timepicker_slots( $range_start, $check_out_end_time );
				}
			}
		}

		wp_send_json_success( [
			'check_in_slots'  => $check_in_time_slots,
			'check_out_slots' => $check_out_time_slots,
		] );

	}

}