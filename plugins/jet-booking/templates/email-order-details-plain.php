<?php
/**
 * WooCommerce email order details plain.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/email-order-details-plain.php.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

esc_html_e( 'Booking Details', 'jet-booking' );

foreach ( $details as $detail ) {
	foreach ( $detail as $item ) {
		esc_html_e( '-', 'jet-booking' );

		if ( ! empty( $item['key'] ) ) {
			echo esc_html( $item['key'] ) . ': ';
		}

		if ( ! empty( $item['is_html'] ) ) {
			echo esc_html( $item['display_plain'] );
		} else {
			echo esc_html( $item['display'] );
		}
	}
}