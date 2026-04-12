<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div>
	<b><?php esc_html_e( 'Booking macros:', 'jet-booking' ); ?></b>
	<br>
	<i>%ADVANCED_PRICE::_check_in_out%</i><?php esc_html_e( ' - The macro will return the advanced rate times the number of days booked.', 'jet-booking' ); ?>
	<br>
	<i>_check_in_out</i><?php esc_html_e( ' - is the name of the field that returns the number of days booked.', 'jet-booking' ); ?>
	<br><br>
	<i>%BOOKING_TIME::_time_field%</i><?php esc_html_e( ' - The macro will return the selected booking time in seconds.', 'jet-booking' ); ?>
	<br>
	<i>check-in-time</i><?php esc_html_e( ' - is a required keyword to retrieve the booking check-in time (do not replace with your field name).', 'jet-booking' ); ?>
	<br>
	<i>check-out-time</i><?php esc_html_e( ' - is a required keyword to retrieve the booking check-out time (do not replace with your field name).', 'jet-booking' ); ?>
	<br><br>
	<i>%META::_apartment_price%</i><?php esc_html_e( ' - Macro returns price per 1 day / night.', 'jet-booking' ); ?>
</div>
