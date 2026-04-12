<?php

namespace JET_ABAF\Compatibility\Packages;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Jet_Smart_Filters {

	public function __construct() {

		add_filter( 'jet-smart-filters/query/final-query', [ $this, 'set_booking_params' ] );
		add_filter( 'jet-booking/elementor-views/dynamic-tags/booking-price/period', [ $this, 'set_booking_period' ] );
		add_filter( 'jet-booking/elementor-views/dynamic-tags/units-count/period', [ $this, 'set_booking_period' ] );
		add_filter( 'jet-booking/macros/booking-price/period', [ $this, 'set_booking_period' ] );
		add_filter( 'jet-booking/macros/units-count/period', [ $this, 'set_booking_period' ] );

		// before JSF version 3.0.0
		add_action( 'jet-smart-filters/post-type/filter-notes-after', [ $this, 'add_booking_notes' ] );
		// after JSF version 3.0.0
		add_action( 'jet-smart-filters/admin/register-dynamic-query', [ $this, 'add_booking_dynamic_query' ] );

	}

	/**
	 * Set booking params.
	 *
	 * Check if booking var presented in query - unset it and add apartments unavailable for this period into query.
	 *
	 * @since 1.0.0
	 * @since 3.1.0 Moved to proper compatibility file.
	 * @since 3.6.1 Set `jet_booking_period` query parameter for listing filtration.
	 *
	 * @param array $query List of query parameters.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function set_booking_params( $query ) {

		if ( empty( $query['meta_query'] ) ) {
			return $query;
		}

		$store_type = jet_abaf()->settings->get( 'filters_store_type' );

		foreach ( $query['meta_query'] as $index => $meta_query ) {
			if ( isset( $meta_query['key'] ) && ( 'chekin_checkout' === $meta_query['key'] || 'checkin_checkout' === $meta_query['key'] ) ) {
				[ $from, $to ] = $query['jet_booking_period'] = $meta_query['value'];

				unset( $query['meta_query'][ $index ] );

				jet_abaf()->stores->get_store( $store_type )->set( 'searched_dates', $from . ' - ' . $to );

				$exclude = jet_abaf()->tools->get_unavailable_apartments( $from, $to );

				if ( $exclude ) {
					$query['post__not_in'] = $exclude;
				}
			}
		}

		return $query;

	}

	/**
	 * Set booking period.
	 *
	 * Returns the booking period after JetSmartFilters Date Range filter triggers.
	 *
	 * @since 3.6.1
	 *
	 * @param array $period List of period start and end dates.
	 *
	 * @return mixed
	 */
	public function set_booking_period( $period ) {

		$query = jet_smart_filters()->query->get_query_args();

		if ( ! empty( $query['jet_booking_period'] ) ) {
			$period = $query['jet_booking_period'];
		}

		return $period;

	}

	/**
	 * Add booking notes.
	 *
	 * @since  2.0.1
	 * @since  3.1.0 Moved to compatibility class & refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function add_booking_notes() {
		printf( '<h5>%s</h5>', esc_html__( 'JetBooking:', 'jet-booking' ) );
		printf( '<ul><li><b>checkin_checkout</b>: %s</li></ul>', esc_html__( 'filter available instances by checkin/checkout dates (allowed only for Date Range filter).', 'jet-booking' ) );
	}

	/**
	 * Add booking dynamic query.
	 *
	 * @since  2.2.5
	 * @since  3.1.0 Moved to compatibility class. Added new dynamic query items.
	 * @access public
	 *
	 * @return void
	 */
	public function add_booking_dynamic_query( $dynamic_query_manager ) {

		$items = [
			'status'           => __( 'JetBooking: status - filter by booking status', 'jet-booking' ),
			'apartment_id'     => __( 'JetBooking: apartment_id - filter by booking apartment ID', 'jet-booking' ),
			'apartment_unit'   => __( 'JetBooking: apartment_unit - filter by booking apartment unit ID', 'jet-booking' ),
			'check_in_date'    => __( 'JetBooking: check_in_date - filter by booking check in date', 'jet-booking' ),
			'check_out_date'   => __( 'JetBooking: check_out_date - filter by booking check out date', 'jet-booking' ),
			'checkin_checkout' => __( 'JetBooking: checkin_checkout - filter available instances by checkin/checkout dates (allowed only for Date Range filter)', 'jet-booking' ),
		];

		$additional_columns = jet_abaf()->settings->get_clean_columns();

		if ( ! empty( $additional_columns ) ) {
			foreach ( $additional_columns as $column ) {
				/* translators: additional column name */
				$items[ $column ] = sprintf( __( 'JetBooking: %s - filter by booking additional column', 'jet-booking' ), $column );
			}
		}

		$dynamic_query_manager->register_items( $items );

	}

}

new Jet_Smart_Filters();