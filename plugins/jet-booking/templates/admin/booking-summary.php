<?php
/**
 * The template for displaying a booking summary to customers.
 * It will display in:
 * - Reviewing a customer order in admin panel.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/admin/booking-summary.php.
 *
 * @since   3.0.0
 * @version 3.7.1
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<div class="jet-booking-summary">
	<div class="jet-booking-summary__number">
		<strong><?php printf( esc_html__( 'Booking #%s', 'jet-booking' ), esc_html( $booking->get_id() ) ); ?></strong>
		<?php printf( '<span class="%s">%s</span>', esc_attr( implode( ' ', $status_classes ) ), esc_html( $statuses[ $booking->get_status() ] ) ) ?>
	</div>

	<?php if ( ! empty( $booking->get_apartment_unit() ) ): ?>
		<div class="jet-booking-summary__unit">
			<?php printf( esc_html__( 'Unit: %s', 'jet-booking' ), esc_html( jet_abaf()->macros->macros_handler->do_macros( '%booking_unit_title%', $booking ) ) ); ?>
		</div>
	<?php endif; ?>

	<div class="jet-booking-summary__dates">
		<div class="jet-booking-summary__date-check-in">
			<?php
				printf( esc_html__( 'Check in: %s', 'jet-booking' ), esc_html( date_i18n( get_option( 'date_format' ), $booking->get_check_in_date() ) ) );

				if ( ! empty( $booking->get_check_in_time() ) ) {
					printf( esc_html__( ' - %s', 'jet-booking' ), esc_html( date_i18n( get_option( 'time_format' ), $booking->get_check_in_time() ) ) );
				}
			?>
		</div>
		<div class="jet-booking-summary__date-check-out">
			<?php
				printf( esc_html__( 'Check out: %s', 'jet-booking' ), esc_html( date_i18n( get_option( 'date_format' ), $booking->get_check_out_date() ) ) );

				if ( ! empty( $booking->get_check_out_time() ) ) {
					printf( esc_html__( ' - %s', 'jet-booking' ), esc_html( date_i18n( get_option( 'time_format' ), $booking->get_check_out_time() ) ) );
				}
			?>
		</div>
	</div>

	<?php if ( $booking->get_guests() ) : ?>
		<div class="jet-booking-summary__guests">
			<?php printf( esc_html__( 'Guests: %s', 'jet-booking' ), esc_html( $booking->get_guests() ) ); ?>
		</div>
	<?php endif;

		$this->display_booking_additional_services( $booking );

		/**
		 * Fires on render booking summary in order metadata.
		 * Allows to add any content to booking summary.
		 */
		do_action( 'jet-booking/wc-integration/order-summary', $booking, $item, false );
	?>
	<div class="jet-booking-summary__actions">
		<?php printf( '<a href="%s" target="_blank">%s</a>', esc_url( add_query_arg( [ 'page' => 'jet-abaf-bookings', 'booking-details' => $booking->get_id() ], admin_url( 'admin.php' ) ) ), esc_html__( 'View details &rarr;', 'jet-booking' ) ); ?>
	</div>
</div>
