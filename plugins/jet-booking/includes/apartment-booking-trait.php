<?php

namespace JET_ABAF;

use JET_ABAF\Vendor\Actions_Core\Base_Handler_Exception;

/**
 * @method setRequest( $key, $value )
 * @method getSettings()
 * @method hasGateway()
 * @method getRequest( $key = '', $ifNotExist = false )
 * @method issetRequest( $key )
 * @method getFieldSettingsByName( $field_name, $setting_name, $if_not_exist = false )
 *
 * Trait Apartment_Booking_Trait
 *
 * @package JET_ABAF
 */
trait Apartment_Booking_Trait {

	/**
	 * Run a booking action.
	 *
	 * @since  2.8.0 Refactored. Remove additional columns handling.
	 * @since  3.7.0 Added `user_email` handling.
	 * @since  3.8.0 Added multiple bookings handling.
	 * @since  4.0.0 Added related post handling for vendors.
	 *
	 * @return array|void
	 * @throws Base_Handler_Exception
	 */
	public function run_action() {

		if ( 'plain' !== jet_abaf()->settings->get( 'booking_mode' ) ) {
			return;
		}

		$args = $this->getSettings();

		if ( empty( $args ) || empty( $args['booking_apartment_field'] ) || empty( $args['booking_dates_field'] ) ) {
			return;
		}

		$post_id = $this->getRequest( $args['booking_apartment_field'] );

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $post_id, 'jet-booking', 60 );

		$dates_field = $args['booking_dates_field'];

		if ( $this->issetRequest( $dates_field . '__in' ) ) {
			$key_in  = $dates_field . '__in';
			$key_out = $dates_field . '__out';
			$date_in = $this->getRequest( $key_in );

			if ( ! empty( $this->getRequest( $key_out ) ) ) {
				$date_out = $this->getRequest( $key_out );
			} else {
				$date_out = $date_in;
			}

			$dates = [ $date_in, $date_out ];
		} else {
			$dates = $this->getRequest( $dates_field );
			$dates = explode( ' - ', $dates );

			if ( 1 === count( $dates ) ) {
				$dates[] = $dates[0];
			}
		}

		if ( empty( $dates ) || 2 !== count( $dates ) ) {
			throw new Base_Handler_Exception( 'failed' );
		}

		$fields_separator = $this->getFieldSettingsByName( $dates_field, 'cio_fields_separator', '-' );
		$fields_separator = 'space' === $fields_separator ? ' ' : $fields_separator;
		$date_format      = '!' . $this->getFieldSettingsByName( $dates_field, 'cio_fields_format', 'Y-m-d' );
		$date_format      = jet_abaf()->tools->date_format_js_to_php( $date_format );

		if ( ! empty( $dates[0] ) ) {
			$dates[0] = str_replace( $fields_separator, '-', $dates[0] );
		}

		if ( ! empty( $dates[1] ) ) {
			$dates[1] = str_replace( $fields_separator, '-', $dates[1] );
		}

		$this->setRequest( '_check_in_date', $dates[0] );
		$this->setRequest( '_check_out_date', $dates[1] );

		$in_object  = \DateTime::createFromFormat( $date_format, $dates[0] );
		$out_object = \DateTime::createFromFormat( $date_format, $dates[1] );

		if ( ! $in_object || ! $out_object ) {
			$in  = strtotime( $dates[0] );
			$out = strtotime( $dates[1] );
		} else {
			$in  = $in_object->getTimestamp();
			$out = $out_object->getTimestamp();
		}

		if ( ! $in || ! $out ) {
			throw new Base_Handler_Exception( 'failed', '', array_map( 'esc_html', $dates ), esc_html( $in ), esc_html( $out ), esc_html( $fields_separator ) );
		}

		$now = current_time( 'timestamp' );

		if ( date( 'Ymd', $in ) < date( 'Ymd', $now ) ) {
			throw new Base_Handler_Exception( esc_html__( 'You must choose a future date.', 'jet-booking' ), 'error' );
		}

		$start_day_offset = jet_abaf()->settings->get_config_setting( $post_id, 'start_day_offset' );

		if ( $start_day_offset ) {
			$start_date = strtotime( "midnight +{$start_day_offset} days", $now );

			if ( $in < $start_date ) {
				$possible_date = date_i18n( get_option( 'date_format', 'F j, Y' ), $start_date );

				/* translators: next available date */
				throw new Base_Handler_Exception( sprintf( esc_html__( 'The earliest booking possible is currently %s.', 'jet-booking' ), esc_html( $possible_date ) ), 'error' );
			}
		}

		$user_email = ! empty( $args['booking_email_field'] ) ? $this->getRequest( $args['booking_email_field'] ) : '';

		if ( empty( $user_email ) && is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_email = $user->user_email ?? '';
		}

		$related_post_id = $this->getRequest( 'inserted_post_id' );

		$booking = [
			'status'         => 'pending',
			'apartment_id'   => $post_id,
			'check_in_date'  => $in,
			'check_out_date' => $out,
			'user_email'     => $user_email,
			'order_id'       => $related_post_id ?: null,
		];

		if ( $related_post_id ) {
			$related_post_author = get_post_field( 'post_author', $post_id );

			wp_update_post( [
				'ID'          => $related_post_id,
				'post_author' => $related_post_author ?: get_current_user_id(),
			] );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		if ( jet_abaf()->settings->get( 'timepicker' ) ) {
			$check_in_time  = ! empty( $_REQUEST['check-in-time'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['check-in-time'] ) ) : '';
			$check_out_time = ! empty( $_REQUEST['check-out-time'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['check-out-time'] ) ) : '';

			if ( empty( $check_in_time ) || empty( $check_out_time ) ) {
				throw new Base_Handler_Exception( esc_html__( 'Time is required.', 'jet-booking' ), 'error' );
			}

			$booking['check_in_time']  = $check_in_time;
			$booking['check_out_time'] = $check_out_time;
		}
		// phpcs:enable WordPress.Security.NonceVerification

		jet_abaf()->settings->hook_db_columns();

		if ( jet_abaf()->db->get_additional_db_columns() ) {
			foreach ( jet_abaf()->db->get_additional_db_columns() as $column ) {
				$data_key = $args[ 'db_columns_map_' . $column ] ?? false;

				if ( $data_key && $this->getRequest( $data_key ) ) {
					$custom_data = $this->getRequest( $data_key );

					if ( is_array( $custom_data ) ) {
						$custom_data = implode( ', ', $custom_data );
					}

					$booking[ $column ] = $custom_data;
				}
			}
		}

		if ( is_user_logged_in() ) {
			$booking['user_id'] = get_current_user_id();
		}

		// Allow custom booking processing.
		$pre_processed = apply_filters( 'jet-booking/form-action/pre-process', false, $booking, $this );

		if ( $pre_processed ) {
			return $pre_processed;
		}

		$capacity       = 1;
		$multiple_units = ! empty( $args['booking_multiple_units'] ) && filter_var( $args['booking_multiple_units'], FILTER_VALIDATE_BOOLEAN );

		if ( $multiple_units ) {
			$units = jet_abaf()->db->get_apartment_units( $booking['apartment_id'] );

			if ( ! empty( $units ) && count( $units ) > 1 ) {
				$capacity = ! empty( $args['booking_capacity_field'] ) && ! empty( $this->getRequest( $args['booking_capacity_field'] ) ) ? $this->getRequest( $args['booking_capacity_field'] ) : 1;

				$default_booking = $booking;

				if ( $booking['check_in_date'] >= $booking['check_out_date'] ) {
					$booking['check_out_date'] = $booking['check_in_date'] + 12 * HOUR_IN_SECONDS;
				}

				$booking['check_in_date'] ++;

				$available_units       = jet_abaf()->db->get_available_units( $booking );
				$available_units_count = count( $available_units );

				if ( $available_units_count < absint( $capacity ) ) {
					/* translators: available units number */
					throw new Base_Handler_Exception( sprintf( esc_html__( 'Not enough available units. Only %d available.', 'jet-booking' ), esc_html( $available_units_count ) ), 'error' );
				}

				$booking = $default_booking;
			}
		}

		$bookings    = [];
		$booking_ids = [];

		for ( $i = 0; $i < absint( $capacity ); $i ++ ) {
			$booking_id = jet_abaf()->db->insert_booking( $booking );

			if ( $booking_id ) {
				$bookings[]    = jet_abaf()->db->inserted_booking;
				$booking_ids[] = $booking_id;

				do_action( 'jet-booking/form-action/booking-inserted', $booking_id );
			} else {
				throw new Base_Handler_Exception( esc_html__( 'Booking dates already taken', 'jet-booking' ), 'error' );
			}
		}

		$this->setRequest( 'booking_id', end( $booking_ids ) );
		$this->setRequest( 'booking_ids', $booking_ids );

		return $bookings;

	}

}
