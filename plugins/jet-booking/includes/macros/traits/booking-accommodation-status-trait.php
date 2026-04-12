<?php

namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Booking_Accommodation_Status_Trait {

	/**
	 * Macros tag.
	 *
	 * Returns macros tag.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function macros_tag() {
		return 'booking_accommodation_status';
	}

	/**
	 * Macros name.
	 *
	 * Returns macros name.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function macros_name() {
		return __( 'Booking Accommodation Status', 'jet-booking' );
	}

	/**
	 * Macros args.
	 *
	 * Return custom macros attributes list.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function macros_args() {
		return [
			'available_label' => [
				'type'    => 'text',
				'label'   => __( 'Available Label', 'jet-booking' ),
				'default' => __( 'Available', 'jet-booking' ),
			],
			'pending_label'   => [
				'type'    => 'text',
				'label'   => __( 'Pending Label', 'jet-booking' ),
				'default' => __( 'Available on', 'jet-booking' ),
			],
			'reserved_label'  => [
				'type'    => 'text',
				'label'   => __( 'Reserved Label', 'jet-booking' ),
				'default' => __( 'Reserved', 'jet-booking' ),
			],
		];
	}

	/**
	 * Macros callback.
	 *
	 * Callback function to return macros value.
	 *
	 * @since 3.4.0
	 *
	 * @param array $args Macros arguments list.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function macros_callback( $args = [] ) {

		$show_reserved_label = apply_filters( 'jet-booking/accommodation-status/show-reserved-label', true );
		$from           	 = strtotime( 'today' );
		$to             	 = $show_reserved_label ? strtotime( '+1 week', $from ) : strtotime( '+6 months', $from );
		$period         	 = jet_abaf()->tools->get_booking_period( $from, $to, get_the_ID() );
		$booked_dates   	 = jet_abaf()->settings->get_off_dates( get_the_ID() );
		$disabled_days  	 = jet_abaf()->settings->get_days_by_rule( get_the_ID() );
		$dates_count    	 = 0;
		$available_date 	 = '';

		foreach ( $period as $value ) {
			$available_date = date_i18n( get_option( 'date_format' ), $value->getTimestamp() );

			if ( ! in_array( $value->format( 'Y-m-d' ), $booked_dates ) && ! in_array( $value->format( 'w' ), $disabled_days ) ) {
				break;
			}

			$dates_count ++;
		}

		$format = '<span class="status %s">%s %s</span>';

		if ( ! $dates_count ) {
			return sprintf( $format, 'available', $args['available_label'], '' );
		} elseif ( $dates_count >= 7 && $show_reserved_label ) {
			return sprintf( $format, 'reserved', $args['reserved_label'], '' );
		} else {
			return sprintf( $format, 'pending', $args['pending_label'], $available_date );
		}

	}

}
