<?php

namespace JET_ABAF;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Tools {

	/**
	 * Recursively sanitizes an array or a string input.
	 *
	 * @param mixed    $input    The input to be sanitized. Can be an array or a string.
	 * @param callable $callback Optional. A user-defined callback function for custom sanitization of string values.
	 *
	 * @return mixed The sanitized array or string.
	 */
	public function sanitize_array_recursively( $input, $callback = null ) {

		if ( is_array( $input ) ) {
			foreach ( $input as $key => $value ) {
				$input[ $key ] = self::sanitize_array_recursively( $value );
			}
		} elseif ( is_string( $input ) ) {
			if ( is_callable( $callback ) ) {
				$input = call_user_func( $callback, $input );
			} elseif ( function_exists( 'sanitize_text_field' ) ) {
				// Fallback to sanitize_text_field if no callback provided
				// This is useful for sanitizing strings in WordPress context
				$input = sanitize_text_field( $input );
			}
		}

		return $input;

	}

	/**
	 * Date format JS to PHP.
	 *
	 * Returns PHP date format from JavaScript format.
	 *
	 * @access public
	 *
	 * @param null  $format JS date format.
	 * @param array $mask   Provided transform mask.
	 *
	 * @return mixed|string|string[]
	 */
	public static function date_format_js_to_php( $format = null, $mask = [] ) {

		if ( ! $format ) {
			return '';
		}

		$mask = ! empty( $mask ) ? $mask : [
			'/HH{1}/'   => 'H',
			'/hh{1}/'   => 'h',
			'/YYYY{1}/' => 'Y',
			'/YY{1}/'   => 'y',
			'/MMMM{1}/' => 'F',
			'/MMM{1}/'  => 'M',
			'/MM{1}/'   => 'm',
			'/M{1}/'    => 'n',
			'/mm{1}/'   => 'i',
			'/DD{1}/'   => 'd',
			'/D{1}/'    => 'j',
			'/dddd{1}/' => 'l',
			'/ddd{1}/'  => 'D',
		];

		foreach ( $mask as $key => $value ) {
			$format = preg_replace( $key, $value, $format );
		}

		return $format;

	}

	/**
	 * Date format PHP to JS.
	 *
	 * Returns JavaScript date format from PHP format.
	 *
	 * @access public
	 *
	 * @param null  $format PHP date format.
	 * @param array $mask   Provided transform mask.
	 *
	 * @return mixed|string|string[]
	 */
	public static function date_format_php_to_js( $format = null, $mask = [] ) {

		if ( ! $format ) {
			return '';
		}

		$mask = ! empty( $mask ) ? $mask : [
			'/H{1}/' => 'HH',
			'/h{1}/' => 'hh',
			'/Y{1}/' => 'YYYY',
			'/y{1}/' => 'YY',
			'/M{1}/' => 'MMM',
			'/n{1}/' => 'M',
			'/m{1}/' => 'MM',
			'/F{1}/' => 'MMMM',
			'/d{1}/' => 'DD',
			'/D{1}/' => 'ddd',
			'/j{1}/' => 'D',
			'/l{1}/' => 'dddd',
			'/i{1}/' => 'mm',
			'/g{1}/' => 'hh',
		];

		foreach ( $mask as $key => $value ) {
			$format = preg_replace( $key, $value, $format );
		}

		return $format;

	}

	/**
	 * Get post types for js.
	 *
	 * Returns all post types list to use in JavaScript components
	 *
	 * @since 3.2.0
	 *
	 * @param false $placeholder Placeholder value.
	 * @param false $key         Array key.
	 *
	 * @return array
	 */
	public function get_post_types_for_js( $placeholder = false, $key = false ) {

		$post_types = get_post_types( [], 'objects' );
		$types_list = $this->prepare_list_for_js( $post_types, 'name', 'label', $key );

		if ( $placeholder && is_array( $placeholder ) ) {
			$types_list = array_merge( [ $placeholder ], $types_list );
		}

		return $types_list;

	}

	/**
	 * Prepare list for js.
	 *
	 * Prepare passed array for using in JavaScript options.
	 *
	 * @since 3.2.0
	 *
	 * @param array $array     Initial list of posts.
	 * @param null  $value_key List value key.
	 * @param null  $label_key List label key.
	 * @param false $key       Array key.
	 *
	 * @return array
	 */
	public static function prepare_list_for_js( $array = [], $value_key = null, $label_key = null, $key = false ) {

		$result = [];

		if ( ! is_array( $array ) || empty( $array ) ) {
			return $result;
		}

		$array_key = false;

		foreach ( $array as $index => $item ) {
			$value = null;
			$label = null;

			if ( is_object( $item ) ) {
				$value = $item->$value_key;
				$label = $item->$label_key;

				if ( $key ) {
					$array_key = $item->$key;
				}
			} elseif ( is_array( $item ) ) {
				$value = $item[ $value_key ];
				$label = $item[ $label_key ];

				if ( $key ) {
					$array_key = $item[ $key ];
				}
			} else {
				if ( ARRAY_A === $value_key ) {
					$value = $index;
				} else {
					$value = $item;
				}

				$label = $item;

				if ( $key ) {
					$array_key = $index;
				}
			}

			if ( $key && false !== $array_key ) {
				$result[ $array_key ] = [
					'value' => $value,
					'label' => $label,
				];
			} else {
				$result[] = [
					'value' => $value,
					'label' => $label,
				];
			}
		}

		return $result;

	}

	/**
	 * Retrieve booking instances posts based on given arguments.
	 *
	 * @since  2.6.1
	 * @since  3.1.1 Added $args parameter.
	 * @since  3.8.5 Added post type existence check.
	 *
	 * @param array $args Query arguments for fetching booking instances posts.
	 *
	 * @return array List of booking instances posts.
	 */
	public function get_booking_posts( $args = [] ) {

		$post_types = array_filter( jet_abaf()->settings->get( 'apartment_post_type' ), function ( $post_type ) {
			return post_type_exists( $post_type );
		} );

		if ( empty( $post_types ) ) {
			return [];
		}

		$defaults = apply_filters( 'jet-booking/tools/post-type-args', [
			'post_type'      => $post_types,
			'posts_per_page' => - 1,
		] );

		$posts = get_posts( wp_parse_args( $args, $defaults ) );

		if ( ! $posts ) {
			return [];
		}

		return $posts;

	}

	/**
	 * Get booking posts list.
	 *
	 * Retrieve a list of booking posts with their titles and IDs.
	 *
	 * @since  3.8.0
	 * @since  4.0.0 Added vendor post handling.
	 * @access public
	 *
	 * @return array
	 */
	public function get_booking_posts_list() {

		$args    = [];
		$user_id = get_current_user_id();

		if ( jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			$args['author'] = $user_id;
		}

		$posts = $this->get_booking_posts( $args );

		if ( empty( $posts ) ) {
			return [];
		}

		return wp_list_pluck( $posts, 'post_title', 'ID' );

	}

	/**
	 * Get unavailable apartments.
	 *
	 * @since 2.0.0
	 * @since 2.6.1 New handling.
	 * @since 3.1.0 Moved to tools class.
	 *
	 * @param string $from Range start date in timestamp.
	 * @param string $to   Range end date in timestamp.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_unavailable_apartments( $from, $to ) {

		$args  = apply_filters( 'jet-booking/tools/booking-posts-args', [] );
		$posts = $this->get_booking_posts( $args );

		if ( empty( $posts ) ) {
			return [];
		}

		$booked_apartments = [];

		foreach ( $posts as $post ) {
			$invalid_dates = $this->get_invalid_dates_in_range( $from, $to, $post->ID );

			if ( ! empty( $invalid_dates ) ) {
				$booked_apartments[] = $post->ID;
			}
		}

		return $booked_apartments;

	}

	/**
	 * Get invalid dates in range.
	 *
	 * Returns list of booked, disabled and off dates in defined range.
	 *
	 * @since  2.5.5
	 * @since  2.6.1 Added `$instance_id` parameter.
	 * @since  2.7.1 Checkout only compatibility.
	 * @access public
	 *
	 * @param string        $from        First date of range in timestamp.
	 * @param string        $to          Last date of range in timestamp.
	 * @param string|number $instance_id Booking instance ID.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_invalid_dates_in_range( $from, $to, $instance_id ) {

		$period        = $this->get_booking_period( $from, $to, $instance_id );
		$booked_dates  = jet_abaf()->settings->get_off_dates( $instance_id );
		$disabled_days = jet_abaf()->settings->get_days_by_rule( $instance_id );
		$booked_range  = [];

		foreach ( $period as $key => $value ) {
			if ( in_array( $value->format( 'Y-m-d' ), $booked_dates ) || in_array( $value->format( 'w' ), $disabled_days ) ) {
				$booked_range[] = $value->format( 'Y-m-d' );
			}
		}

		sort( $booked_range );

		$days_off = jet_abaf()->settings->get_booking_days_off( $instance_id );

		if ( jet_abaf()->settings->checkout_only_allowed( $instance_id ) ) {
			if ( false !== ( $index = array_search( date( 'Y-m-d', $to ), $booked_range ) ) && 0 === $index && ! in_array( $booked_range[ $index ], $days_off ) && ! in_array( date( 'N', $to ), $disabled_days ) ) {
				unset( $booked_range[ $index ] );
			}
		}

		return array_values( $booked_range );

	}

	/**
	 * Get field default value.
	 *
	 * Returns check in check out field default values.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string     $value   Initial value.
	 * @param string     $format  Date format.
	 * @param string|int $post_id Queried post ID.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_field_default_value( $value, $format, $post_id ) {

		$result = [];

		if ( jet_abaf()->settings->is_weekly_bookings( $post_id ) ) {
			return $result;
		}

		$store_type     = jet_abaf()->settings->get( 'filters_store_type' );
		$searched_dates = jet_abaf()->stores->get_store( $store_type )->get( 'searched_dates' );
		$value          = $value ?: $searched_dates;

		if ( ! trim( $value ) ) {
			return $result;
		}

		$value = explode( ' - ', $value );

		if ( ! empty( $value[0] ) && $this->is_valid_timestamp( $value[0] ) && ! empty( $value[1] ) && $this->is_valid_timestamp( $value[1] ) ) {
			$check_in_days  = jet_abaf()->settings->get_days_by_rule( $post_id, 'check_in' );
			$check_out_days = jet_abaf()->settings->get_days_by_rule( $post_id, 'check_out' );

			if ( ! empty( $check_in_days ) && ! in_array( date( 'w', $value[0] ), $check_in_days ) || ! empty( $check_out_days ) && ! in_array( date( 'w', $value[1] ), $check_out_days ) ) {
				return $result;
			}

			$checkin  = date( 'Y-m-d', $value[0] );
			$checkout = date( 'Y-m-d', $value[1] );

			if ( $checkin === $checkout && jet_abaf()->settings->is_per_nights_booking( $post_id ) ) {
				return $result;
			}

			$interval         = $this->get_booking_period_interval( $value[0], $value[1], $post_id );
			$min_days         = jet_abaf()->settings->get_config_setting( $post_id, 'min_days' );
			$max_days         = jet_abaf()->settings->get_config_setting( $post_id, 'max_days' );
			$start_day_offset = jet_abaf()->settings->get_config_setting( $post_id, 'start_day_offset' );
			$price            = new Price( $post_id );
			$seasonal_price   = $price->seasonal_price->get_price();
			$in_season        = false;

			if ( ! empty( $seasonal_price ) ) {
				foreach ( $seasonal_price as $season ) {
					if ( ! isset( $season['enable_config'] ) || ! filter_var( $season['enable_config'], FILTER_VALIDATE_BOOLEAN ) ) {
						continue;
					}

					if ( $value[0] >= $season['start'] && $value[0] <= $season['end'] || $value[1] >= $season['start'] && $value[1] <= $season['end'] || $season['start'] >= $value[0] && $season['start'] <= $value[0] || $season['end'] >= $value[0] && $season['end'] <= $value[0] ) {
						$in_season = true;

						if ( ! empty( $season['min_days'] ) && $interval->days < $season['min_days'] || ! empty( $season['max_days'] ) && $interval->days > $season['max_days'] || ! empty( $season['start_day_offset'] ) && $interval->days <= $season['start_day_offset'] ) {
							return $result;
						}
					}
				}
			}

			if ( ! $in_season && ( $min_days && $interval->days < $min_days || $max_days && $interval->days > $max_days || $interval->days <= $start_day_offset ) ) {
				return $result;
			}

			$booked_range = $this->get_invalid_dates_in_range( $value[0], $value[1], $post_id );

			if ( $checkin >= date( 'Y-m-d' ) && ! ( in_array( $checkin, $booked_range ) && in_array( $checkout, $booked_range ) ) ) {
				if ( in_array( $checkin, $booked_range ) ) {
					$checkin = strtotime( end( $booked_range ) . ' + 1 day' );
					reset( $booked_range );
				} else {
					$checkin = $value[0];
				}

				if ( in_array( $checkout, $booked_range ) ) {
					$checkout = strtotime( $booked_range[0] . ' - 1 day' );
				} else {
					if ( ! empty( $booked_range ) && ! in_array( date( 'Y-m-d', $value[0] ), $booked_range ) ) {
						$checkout = strtotime( $booked_range[0] . ' - 1 day' );
					} else {
						$checkout = $value[1];
					}
				}

				$format = self::date_format_js_to_php( $format );

				$result['checkin']  = date( $format, $checkin );
				$result['checkout'] = jet_abaf()->settings->is_one_day_bookings( $post_id ) ? $result['checkin'] : date( $format, $checkout );

				if ( $result['checkin'] === $result['checkout'] && jet_abaf()->settings->is_per_nights_booking( $post_id ) ) {
					$result = [];
				}
			}
		}

		return $result;

	}

	/**
	 * Get booked dates.
	 *
	 * Retrieve the booked dates for a given property or unit.
	 *
	 * @since  1.0.0
	 * @since  3.8.4 Added `$unit_id` parameter and handling. Refactored booked dates with units.
	 *
	 * @param int      $post_id The ID of the property post.
	 * @param int|null $unit_id The ID of a specific unit to check bookings for.
	 *
	 * @return array An array of booked dates in 'Y-m-d' format.
	 * @throws \Exception
	 */
	public function get_booked_dates( $post_id, $unit_id = null ) {

		$bookings = jet_abaf()->db->get_future_bookings( $post_id );

		if ( empty( $bookings ) ) {
			return [];
		}

		$units           = jet_abaf()->db->get_apartment_units( $post_id );
		$units_num       = ! empty( $units ) ? count( $units ) : 0;
		$weekly_bookings = jet_abaf()->settings->is_weekly_bookings( $post_id );
		$week_offset     = jet_abaf()->settings->get_config_setting( $post_id, 'week_offset' );
		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();
		$dates           = [];

		if ( ! $units_num || 1 === $units_num ) {
			foreach ( $bookings as $booking ) {
				if ( ! empty( $booking['status'] ) && in_array( $booking['status'], $skip_statuses ) ) {
					continue;
				}

				$from = new \DateTime( date( 'F d, Y', $booking['check_in_date'] ) );
				$to   = new \DateTime( date( 'F d, Y', $booking['check_out_date'] ) );

				if ( $weekly_bookings && ! $week_offset || ! jet_abaf()->settings->is_per_nights_booking( $post_id ) ) {
					$to = $to->modify( '+1 day' );
				}

				if ( $from->format( 'Y-m-d' ) === $to->format( 'Y-m-d' ) ) {
					$dates[] = $from->format( 'Y-m-d' );
				} else {
					$period = new \DatePeriod( $from, new \DateInterval( 'P1D' ), $to );

					foreach ( $period as $value ) {
						$dates[] = $value->format( 'Y-m-d' );
					}
				}
			}
		} else {
			$booked_units = [];

			foreach ( $bookings as $booking ) {
				if ( ! empty( $booking['status'] ) && in_array( $booking['status'], $skip_statuses ) ) {
					continue;
				}

				$from = new \DateTime( date( 'F d, Y', $booking['check_in_date'] ) );
				$to   = new \DateTime( date( 'F d, Y', $booking['check_out_date'] ) );

				if ( $weekly_bookings && ! $week_offset || ! jet_abaf()->settings->is_per_nights_booking( $post_id ) ) {
					$to = $to->modify( '+1 day' );
				}

				if ( $from->format( 'Y-m-d' ) === $to->format( 'Y-m-d' ) ) {
					$booked_units[ $from->format( 'Y-m-d' ) ][] = $booking['apartment_unit'];
				} else {
					$period = new \DatePeriod( $from, new \DateInterval( 'P1D' ), $to );

					foreach ( $period as $value ) {
						$booked_units[ $value->format( 'Y-m-d' ) ][] = $booking['apartment_unit'];
					}
				}
			}

			foreach ( $booked_units as $date => $units ) {
				if ( $units_num <= count( $units ) || $unit_id && in_array( $unit_id, $units ) ) {
					$dates[] = $date;
				}
			}
		}

		return $dates;

	}

	/**
	 * Get next booked dates.
	 *
	 * Returns list of dates that booked next.
	 *
	 * @param array           $booked_dates Booked dates list.
	 * @param int|string|null $post_id      Booking instance ID.
	 *
	 * @return array
	 */
	public function get_next_booked_dates( $booked_dates = [], $post_id = null ) {

		$result = [];

		if ( ! jet_abaf()->settings->checkout_only_allowed( $post_id ) ) {
			return $result;
		}

		foreach ( $booked_dates as $index => $date ) {
			$next_date = date( 'Y-m-d', strtotime( $date ) + DAY_IN_SECONDS );
			$prev_date = date( 'Y-m-d', strtotime( $date ) - DAY_IN_SECONDS );

			if ( ! in_array( $next_date, $booked_dates ) && ! in_array( $prev_date, $booked_dates ) ) {
				$result[] = $next_date;
			}
		}

		return $result;

	}

	/**
	 * Returns booking period data.
	 *
	 * @since 3.2.1
	 * @since 3.6.0 Added booking period check.
	 *
	 * @param int|string      $from    Start period date in timestamp format.
	 * @param int|string      $to      End period date in timestamp format.
	 * @param int|string|null $post_id Booking instance ID.
	 *
	 * @return \DatePeriod
	 * @throws \Exception
	 */
	public function get_booking_period( $from, $to, $post_id = null ) {

		$start = new \DateTime( date( 'Y-m-d', $from ) );
		$end   = new \DateTime( date( 'Y-m-d', $to ) );

		if ( ! jet_abaf()->settings->is_per_nights_booking( $post_id ) ) {
			$end->modify( '+1 day' );
		}

		return new \DatePeriod( $start, new \DateInterval( 'P1D' ), $end );

	}

	/**
	 * Returns booking period interval.
	 *
	 * @since 3.6.0
	 * @since 4.0.2 Added $strict interval parameter.
	 *
	 * @param int|string      $from    Start period date in timestamp format.
	 * @param int|string      $to      End period date in timestamp format.
	 * @param int|string|null $post_id Booking instance ID.
	 * @param boolean         $strict  Whether to calculate the interval strictly without adjusting for per-night
	 *                                 bookings.
	 *
	 * @return \DateInterval|false
	 * @throws \Exception
	 */
	public function get_booking_period_interval( $from, $to, $post_id = null, $strict = false ) {

		$start = new \DateTime( date( 'Y-m-d', $from ) );
		$end   = new \DateTime( date( 'Y-m-d', $to ) );

		if ( $strict ) {
			return $start->diff( $end );
		}

		return jet_abaf()->settings->is_per_nights_booking( $post_id ) ? $start->diff( $end ) : $start->diff( $end->modify( '+1 day' ) );

	}

	/**
	 * Check in booking period containing disables days.
	 *
	 * @since 3.2.1
	 *
	 * @param array $booking Booking data.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function is_booking_period_available( $booking ) {

		$disabled_days  = jet_abaf()->settings->get_days_by_rule( $booking['apartment_id'] );
		$days_off       = jet_abaf()->settings->get_booking_days_off( $booking['apartment_id'] );
		$booking_period = $this->get_booking_period( $booking['check_in_date'], $booking['check_out_date'], $booking['apartment_id'] );
		$invalid_days   = [];

		foreach ( $booking_period as $key => $value ) {
			if ( in_array( $value->format( 'w' ), $disabled_days ) || in_array( $value->format( 'Y-m-d' ), $days_off ) ) {
				$invalid_days[] = $value->format( 'Y-m-d' );
			}
		}

		return empty( $invalid_days );

	}

	/**
	 * Is valid timestamp.
	 *
	 * Check if is valid timestamp
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $timestamp Date in timestamp format.
	 *
	 * @return boolean
	 */
	public static function is_valid_timestamp( $timestamp ) {

		if ( is_array( $timestamp ) || is_object( $timestamp ) ) {
			return false;
		}

		return ( ( string ) ( int ) $timestamp === $timestamp || ( int ) $timestamp === $timestamp ) && ( $timestamp <= PHP_INT_MAX ) && ( $timestamp >= ~PHP_INT_MAX );

	}

	/**
	 * Get timepicker slots.
	 *
	 * This function generates timepicker slots based on the given start and end timestamps.
	 *
	 * @since 3.7.0
	 *
	 * @param int|string $start        The start timestamp.
	 * @param int|string $end          The end timestamp.
	 * @param string     $default_slot The default selected slot.
	 *
	 * @return string
	 */
	public function get_timepicker_slots( $start = '', $end = '', $default_slot = '' ) {

		$range_start = jet_abaf()->settings->get( 'timepicker_range_start' );
		$range_end   = jet_abaf()->settings->get( 'timepicker_range_end' );

		if ( empty( $start ) ) {
			$start = $range_start;
		}

		if ( empty( $end ) ) {
			$end = $range_end;
		}

		$interval = jet_abaf()->settings->get( 'timepicker_interval' );
		$format   = get_option( 'time_format' );
		$options  = [];

		$start_time = intval( $range_start ) !== intval( $start ) ? $range_start : $start;

		while ( intval( $start_time ) <= intval( $end ) ) {
			if ( intval( $start_time ) >= intval( $start ) ) {
				$time     = date_i18n( $format, $start_time );
				$selected = intval( $default_slot ) === intval( $start_time ) ? 'selected' : '';

				$options[] = '<option value="' . esc_attr( $time ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $time ) . '</option>';
			}

			$start_time += $interval;
		}

		return implode( '', $options );

	}

	/**
	 * Prepare and filter the list of macros for usage.
	 *
	 * @since 2.7.0
	 *
	 * @return array Filtered macros list.
	 */
	public function get_prepared_macros_list() {

		$macros_list = jet_abaf()->macros->macros_handler->get_macros_for_js();

		foreach ( $macros_list as $key => $value ) {
			if ( in_array( $value['id'], [
				'bookings_count',
				'booking_units_count',
				'booking_price_per_day_night',
				'booking_price',
				'booking_accommodation_status'
			] ) ) {
				unset( $macros_list[ $key ] );
			}
		}

		return $macros_list;

	}

	/**
	 * Can edit order.
	 *
	 * Checks if the current user has permission to edit orders based on their role as a booking vendor.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return bool Returns true if the current user is allowed to edit orders; false otherwise.
	 */
	public function can_edit_order() {

		if ( jet_abaf()->vendors->is_booking_vendor( wp_get_current_user() ) ) {
			if ( jet_abaf()->wc->has_woocommerce() ) {
				return jet_abaf()->wc->can_edit_wc_order();
			} else {
				return true;
			}
		}

		return true;

	}

	/**
	 * Ensures the given value is converted to an array.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param mixed $value The input value to be converted to an array.
	 *
	 * @return array The value converted to an array, or an empty array if the value is empty.
	 */
	public function ensure_array( $value ) {

		if ( empty( $value ) ) {
			return [];
		}

		return (array) $value;

	}

	/**
	 * Normalize arabic time.
	 *
	 * Normalize Arabic time to a standard format with AM/PM.
	 *
	 * @since   4.0.1
	 * @access  public
	 *
	 * @param string $time The Arabic time input containing Arabic AM/PM indicators.
	 *
	 * @return string Normalized time with standard AM/PM format.
	 */
	public function normalize_arabic_time( $time ) {

		$map = [
			'ص'     => 'am',
			'صباحًا' => 'AM',
			'م'     => 'pm',
			'مساءً'  => 'PM',
		];

		return trim( str_replace( array_keys( $map ), array_values( $map ), $time ) );

	}

	/**
	 * Retrieve localization strings for settings widgets.
	 *
	 * This method returns an array of localized strings used in the various widgets
	 * of the settings interface.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array An associative array of widget-related localization strings.
	 */
	public function get_settings_widgets_strings() {
		// Allow third-parties to adjust widget related localization strings.
		return apply_filters( 'jet-booking/tools/widgets/strings', [
			// Configuration settings widget
			'booking_period_label'                   => __( 'Booking period', 'jet-booking' ),
			'booking_period_help'                    => __( 'Define how the booking period will be calculated – per night (without the last booked date) or per day (including the last booked date). NOTE: this option will affect price calculation.', 'jet-booking' ),
			'booking_period_per_night_option_label'  => __( 'Per Night (last booked date is not included)', 'jet-booking' ),
			'booking_period_per_day_option_label'    => __( 'Per Day (last booked date is included)', 'jet-booking' ),
			'allow_checkout_only_label'              => __( 'Allow checkout only days', 'jet-booking' ),
			'allow_checkout_only_help'               => __( 'If this option is checked, the first day of the already booked period will be available for checkout only.', 'jet-booking' ),
			'one_day_bookings_label'                 => __( 'One day bookings', 'jet-booking' ),
			'one_day_bookings_help'                  => __( 'If this option is checked only single days bookings are allowed. If Weekly bookings are enabled this option will not work.', 'jet-booking' ),
			'weekly_bookings_label'                  => __( 'Week-long bookings', 'jet-booking' ),
			'weekly_bookings_help'                   => __( 'If this option is checked, only week-long bookings are allowed.', 'jet-booking' ),
			'week_offset_label'                      => __( 'Weekday offset', 'jet-booking' ),
			'week_offset_help'                       => __( 'Allows you to change the first booked day of the week.', 'jet-booking' ),
			'start_day_offset_label'                 => __( 'Starting day offset', 'jet-booking' ),
			'start_day_offset_help'                  => __( 'This string defines offset for the earliest date which is available to the user.', 'jet-booking' ),
			'min_days_label'                         => __( 'Min days', 'jet-booking' ),
			'min_days_help'                          => __( 'This number defines the minimum days of the selected range. If it equals 0, it means minimum days are not limited.', 'jet-booking' ),
			'max_days_label'                         => __( 'Max days', 'jet-booking' ),
			'max_days_help'                          => __( 'This number defines the maximum days of the selected range. If it equals 0, it means maximum days are not limited.', 'jet-booking' ),
			'end_date_label'                         => __( 'End date', 'jet-booking' ),
			'end_date_help'                          => __( 'This option defines the latest date which is allowed for the user to pick.', 'jet-booking' ),
			'end_date_any_option_label'              => __( 'Any date', 'jet-booking' ),
			'end_date_range_option_label'            => __( 'Limited range', 'jet-booking' ),
			'end_date_range_unit_day_option_label'   => __( 'Day(s)', 'jet-booking' ),
			'end_date_range_unit_month_option_label' => __( 'Month(s)', 'jet-booking' ),
			'end_date_range_unit_year_option_label'  => __( 'Year(s)', 'jet-booking' ),
			'clear_button_label'                     => __( 'Clear button', 'jet-booking' ),
			'clear_button_help'                      => __( 'If this option is checked, clear button in the date-range picker filed will show.', 'jet-booking' ),
			'month_select_label'                     => __( 'Month select', 'jet-booking' ),
			'month_select_help'                      => __( 'If this option is checked, you can quickly change month by clicking on month name.', 'jet-booking' ),
			'year_select_label'                      => __( 'Year select', 'jet-booking' ),
			'year_select_help'                       => __( 'If this option is checked, you can quickly change year by clicking on year number.', 'jet-booking' ),
			'calendar_price_label'                   => __( 'Show price in calendar', 'jet-booking' ),
			'calendar_price_help'                    => __( 'If this option is checked, each date in the date-range picker will show its price.', 'jet-booking' ),
			// Schedule settings widget
			'require_date_error'                     => __( 'The start date is required.', 'jet-booking' ),
			'dates_order_error'                      => __( 'The end date must be after the start date.', 'jet-booking' ),
			'removal_confirm_message'                => __( 'Are you sure?', 'jet-booking' ),
			'timepicker_label'                       => __( 'Timepicker', 'jet-booking' ),
			'timepicker_help'                        => __( 'If enabled adds time selection controls. NOTE: Time is enabled without affecting reservations.', 'jet-booking' ),
			'timepicker_restrictions_label'          => __( 'Timepicker Restrictions', 'jet-booking' ),
			'timepicker_restrictions_help'           => __( 'If enabled, adds time reservation restrictions. Some time slots will be blocked based on other reservations and settings. NOTE: Works best with a per-night booking period.', 'jet-booking' ),
			'timepicker_buffer_label'                => __( 'Buffer Time', 'jet-booking' ),
			'timepicker_buffer_help'                 => __( 'Define a time buffer to prevent back-to-back bookings. The system will reserve this time before and/or after each booking to ensure availability.', 'jet-booking' ),
			'timepicker_range_label'                 => __( 'Time Range', 'jet-booking' ),
			'timepicker_range_help'                  => __( 'Set the time range during which bookings can be made.', 'jet-booking' ),
			'timepicker_range_start_label'           => __( 'Start', 'jet-booking' ),
			'timepicker_range_end_label'             => __( 'End', 'jet-booking' ),
			'timepicker_interval_label'              => __( 'Time Slot Interval', 'jet-booking' ),
			'timepicker_interval_help'               => __( 'Select the interval between the available time slots for booking.', 'jet-booking' ),
			'booking_rules_label'                    => __( 'Weekday Booking Rules', 'jet-booking' ),
			'booking_rules_help'                     => __( 'Configure which weekdays will be available for checking in/out and which will be disabled.', 'jet-booking' ),
			'weekday_day_column_heading'             => __( 'Day', 'jet-booking' ),
			'weekday_disable_column_heading'         => __( 'Disable', 'jet-booking' ),
			'weekday_check_in_column_heading'        => __( 'Check In', 'jet-booking' ),
			'weekday_check_out_column_heading'       => __( 'Check Out', 'jet-booking' ),
			'weekday_1_label'                        => __( 'Monday', 'jet-booking' ),
			'weekday_2_label'                        => __( 'Tuesday', 'jet-booking' ),
			'weekday_3_label'                        => __( 'Wednesday', 'jet-booking' ),
			'weekday_4_label'                        => __( 'Thursday', 'jet-booking' ),
			'weekday_5_label'                        => __( 'Friday', 'jet-booking' ),
			'weekend_1_label'                        => __( 'Saturday', 'jet-booking' ),
			'weekend_2_label'                        => __( 'Sunday', 'jet-booking' ),
			'days_off_label'                         => __( 'Days Off', 'jet-booking' ),
			'days_off_help'                          => __( 'Set the days off, holidays, and weekend dates.', 'jet-booking' ),
			'days_off_add_button_label'              => __( 'Add Days', 'jet-booking' ),
			'days_off_range_name_field_label'        => __( 'Range Label', 'jet-booking' ),
			'days_off_range_name_field_help'         => __( 'Name the range that will be unavailable for booking (e.g., name of the holiday).', 'jet-booking' ),
			'days_off_start_date_field_label'        => __( 'Start Date *', 'jet-booking' ),
			'days_off_start_date_field_help'         => __( 'Pick the start day.', 'jet-booking' ),
			'days_off_end_date_field_label'          => __( 'End Date', 'jet-booking' ),
			'days_off_end_date_field_help'           => __( 'Pick the end day.', 'jet-booking' ),
			'days_off_save_button_label'             => __( 'Save', 'jet-booking' ),
			'days_off_cancel_button_label'           => __( 'Cancel', 'jet-booking' ),
			// General
			'save_button_label'                      => __( 'Save Settings', 'jet-booking' ),
		] );
	}

}
