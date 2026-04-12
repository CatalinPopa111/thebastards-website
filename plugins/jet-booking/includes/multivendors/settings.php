<?php
namespace JET_ABAF\Multivendors;

class Settings {

	/**
	 * Vendor settings meta key.
	 *
	 * @var string
	 */
	const META_KEY = 'jet_booking_settings';

	/**
	 * Settings that vendors are allowed to override.
	 *
	 * @var array
	 */
	private $settings = [
		// Configuration
		'booking_period',
		'allow_checkout_only',
		'one_day_bookings',
		'weekly_bookings',
		'week_offset',
		'start_day_offset',
		'min_days',
		'max_days',
		'end_date_type',
		'end_date_range_number',
		'end_date_range_unit',
		'show_clear_button',
		'month_select',
		'year_select',
		'show_calendar_price',

		// Schedule
		'timepicker',
		'timepicker_restrictions',
		'timepicker_buffer',
		'timepicker_range_start',
		'timepicker_range_end',
		'timepicker_interval',
		'disable_weekday_1',
		'check_in_weekday_1',
		'check_out_weekday_1',
		'disable_weekday_2',
		'check_in_weekday_2',
		'check_out_weekday_2',
		'disable_weekday_3',
		'check_in_weekday_3',
		'check_out_weekday_3',
		'disable_weekday_4',
		'check_in_weekday_4',
		'check_out_weekday_4',
		'disable_weekday_5',
		'check_in_weekday_5',
		'check_out_weekday_5',
		'disable_weekend_1',
		'check_in_weekend_1',
		'check_out_weekend_1',
		'disable_weekend_2',
		'check_in_weekend_2',
		'check_out_weekend_2',
		'days_off',
	];

	public function __construct() {
		add_filter( 'jet-booking/settings', [ $this, 'maybe_set_vendor_settings' ] );
	}

	/**
	 * Retrieves the list of allowed vendor settings.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Maybe set vendor settings.
	 *
	 * Adjusts the provided settings by merging vendor-specific settings, if applicable.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array $settings The settings array.
	 *
	 * @return array Adjusted settings array.
	 */
	public function maybe_set_vendor_settings( $settings ) {

		$post_id         = wp_cache_get( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		$user_id         = $post_id ? get_post_field( 'post_author', $post_id ) : get_current_user_id();
		$vendor_settings = [];

		if ( $user_id && jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			$vendor_settings = get_user_meta( $user_id, self::META_KEY, true );
		}

		if ( ! empty( $vendor_settings ) && is_array( $vendor_settings ) ) {
			$settings = wp_parse_args( $vendor_settings, $settings );
		}

		return $settings;

	}

}
