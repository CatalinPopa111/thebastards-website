<?php

namespace JET_ABAF;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Assets {

	/**
	 * Script dependencies enqueue status holder.
	 *
	 * @var bool
	 */
	private $deps_added = false;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	/**
	 * Register assets.
	 *
	 * Register common assets.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_assets() {

		$asset = require_once JET_ABAF_PATH . "assets/js/widgets/build/index.asset.php";

		wp_register_script(
			'jet-booking-widgets',
			JET_ABAF_URL . 'assets/js/widgets/build/index.js',
			$asset['dependencies'] ?? [],
			$asset['version'] ?? JET_ABAF_VERSION,
			true
		);

		wp_register_style(
			'jet-booking-widgets',
			JET_ABAF_URL . 'assets/js/widgets/build/style-index.css',
			[],
			$asset['version'],
		);

		wp_localize_script( 'jet-booking-widgets', 'jetBookingWidgets', [
			'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'nonce'    => wp_create_nonce( 'jet-abaf-settings' ),
			'settings' => jet_abaf()->settings->get_all(),
			'i18n'     => jet_abaf()->tools->get_settings_widgets_strings(),
		] );

	}

	/**
	 * Enqueue deps.
	 *
	 * Enqueue booking post type script dependencies.
	 *
	 * @since 2.1.0
	 *
	 * @param int|string $post_id Booking instance post id.
	 *
	 * @throws \Exception
	 */
	public function enqueue_deps( $post_id ) {

		if ( ! $post_id || $this->deps_added ) {
			return;
		}

		do_action( 'jet-booking/assets/before' );

		ob_start();

		include JET_ABAF_PATH . 'assets/js/booking-init.js';

		$init_datepicker = ob_get_clean();

		wp_register_script(
			'jet-plugins',
			JET_ABAF_URL . 'assets/lib/jet-plugins/jet-plugins.js',
			[ 'jquery' ],
			'1.1.0',
			true
		);

		wp_enqueue_script(
			'moment-js',
			JET_ABAF_URL . 'assets/lib/moment/js/moment.js',
			[],
			'2.4.0',
			true
		);

		wp_enqueue_script(
			'jquery-date-range-picker',
			JET_ABAF_URL . 'assets/lib/jquery-date-range-picker/js/daterangepicker.min.js',
			[ 'jquery', 'moment-js', 'jet-plugins' ],
			JET_ABAF_VERSION,
			true
		);

		wp_add_inline_script( 'jquery-date-range-picker', $init_datepicker );

		$localized_data = $this->get_localized_data( $post_id );

		wp_localize_script( 'jquery-date-range-picker', 'JetABAFData', apply_filters( 'jet-booking/assets/config', wp_parse_args( $localized_data, [
			'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'css_url'  => add_query_arg( [ 'v' => JET_ABAF_VERSION ], JET_ABAF_URL . 'assets/lib/jquery-date-range-picker/css/daterangepicker.css' ),
			'post_id'  => $post_id,
		] ) ) );

		do_action( 'jet-booking/assets/after' );

		$this->deps_added = true;

	}

	/**
	 * Get localized data.
	 *
	 * Returns booking localized configuration data list.
	 *
	 * @since 3.2.0
	 *
	 * @param int|string $post_id Booking post type instance ID.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_localized_data( $post_id ) {

		$booked_dates       = jet_abaf()->settings->get_off_dates( $post_id );
		$min_days_config    = jet_abaf()->settings->get_config_setting( $post_id, 'min_days' );
		$per_nights_booking = jet_abaf()->settings->is_per_nights_booking( $post_id );
		$apartment_price    = new Price( $post_id );
		$time_format        = get_option( 'time_format', 'g:i a' );

		return [
			'base_price'             => [
				'price'         => $apartment_price->get_default_price(),
				'price_rates'   => $apartment_price->rates_price->get_rates(),
				'weekend_price' => $apartment_price->weekend_price->get_price(),
			],
			'booked_dates'           => $booked_dates,
			'booked_next'            => jet_abaf()->tools->get_next_booked_dates( $booked_dates, $post_id ),
			'check_in_days'          => jet_abaf()->settings->get_days_by_rule( $post_id, 'check_in' ),
			'check_out_days'         => jet_abaf()->settings->get_days_by_rule( $post_id, 'check_out' ),
			'checkout_only'          => jet_abaf()->settings->checkout_only_allowed( $post_id ),
			'custom_labels'          => jet_abaf()->settings->get( 'use_custom_labels' ),
			'days_off'               => jet_abaf()->settings->get_booking_days_off( $post_id ),
			'disabled_days'          => jet_abaf()->settings->get_days_by_rule( $post_id ),
			'labels'                 => apply_filters( 'jet-booking/compatibility/translate-labels', jet_abaf()->settings->get_labels() ),
			'max_days'               => jet_abaf()->settings->get_config_setting( $post_id, 'max_days' ),
			'min_days'               => ! empty( $min_days_config ) ? $min_days_config : ( $per_nights_booking ? 1 : '' ),
			'one_day_bookings'       => jet_abaf()->settings->is_one_day_bookings( $post_id ),
			'per_nights'             => $per_nights_booking,
			'seasonal_price'         => $apartment_price->seasonal_price->get_price(),
			'start_day_offset'       => jet_abaf()->settings->get_config_setting( $post_id, 'start_day_offset' ),
			'end_date'               => jet_abaf()->settings->get_end_date( $post_id ),
			'calendar_price'         => jet_abaf()->settings->get( 'show_calendar_price' ),
			'clear_button'           => jet_abaf()->settings->get( 'show_clear_button' ),
			'calendar_currency_sign' => __( '$', 'jet-booking' ),
			'month_select'           => jet_abaf()->settings->get( 'month_select' ),
			'year_select'            => jet_abaf()->settings->get( 'year_select' ),
			'week_offset'            => jet_abaf()->settings->get_config_setting( $post_id, 'week_offset' ),
			'weekly_bookings'        => jet_abaf()->settings->is_weekly_bookings( $post_id ),
			'timepicker'             => jet_abaf()->settings->get( 'timepicker' ),
			'time_format'            => jet_abaf()->tools->date_format_php_to_js( $time_format ),
		];

	}

	/**
	 * Ensure ajax JS.
	 *
	 * Make sure that date range picker script enqueue after ajax load.
	 *
	 * @since 2.1.0
	 */
	public function ensure_ajax_js() {
		if ( wp_doing_ajax() ) {
			wp_scripts()->done[] = 'jquery';
			wp_scripts()->print_scripts( 'jquery-date-range-picker' );
		}
	}

}
