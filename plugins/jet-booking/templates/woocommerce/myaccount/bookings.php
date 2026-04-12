<?php
/**
 * Bookings.
 *
 * Shows customer bookings on the My Account > Bookings page.
 *
 * This template can be overridden by copying it to yourtheme/jet-bookings/woocommerce/myaccount/bookings.php.
 *
 * @since   3.3.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! empty( $tables ) ) :
	echo '<style>@media only screen and (max-width: 768px) { .my_account_bookings td.empty { display: none !important; } }</style>';

	foreach ( $tables as $table ) :
		$i = 0; ?>

		<h2><?php echo esc_html( $table['heading'] ); ?></h2>

		<table class="woocommerce-orders-table shop_table shop_table_responsive my_account_bookings">
			<thead>
				<tr class="woocommerce-orders-table__row">
					<th class="woocommerce-orders-table__header booking-id"><?php esc_html_e( 'ID', 'jet-booking' ); ?></th>
					<th class="woocommerce-orders-table__header booked-instance"><?php esc_html_e( 'Instance', 'jet-booking' ); ?></th>
					<th class="woocommerce-orders-table__header related-order"><?php esc_html_e( 'Order', 'jet-booking' ); ?></th>
					<th class="woocommerce-orders-table__header booking-check-in"><?php esc_html_e( 'Check In', 'jet-booking' ); ?></th>
					<th class="woocommerce-orders-table__header booking-check-out"><?php esc_html_e( 'Check Out', 'jet-booking' ); ?></th>
					<th class="woocommerce-orders-table__header booking-status"><?php esc_html_e( 'Status', 'jet-booking' ); ?></th>

					<?php if ( jet_abaf()->settings->get( 'booking_cancellation' ) ) : ?>
						<th class="woocommerce-orders-table__header booking-cancel"></th>
					<?php endif; ?>
				</tr>
			</thead>
			<tbody>

			<?php foreach ( $table['bookings'] as $booking ) :
				$i ++;

				// Skip bookings which were added to detect pagination.
				if ( $i > $bookings_per_page ) {
					break;
				} ?>

				<tr class="woocommerce-orders-table__row">
					<td data-title="<?php esc_html_e( 'ID', 'jet-booking' ); ?>" class="woocommerce-orders-table__cell booking-id">
						<?php echo esc_html( $booking->get_id() ); ?>
					</td>
					<td data-title="<?php esc_html_e( 'Instance', 'jet-booking' ); ?>" class="woocommerce-orders-table__cell booked-instance">
						<a href="<?php echo esc_url( get_permalink( $booking->get_apartment_id() ) ); ?>">
							<?php echo esc_html( get_the_title( $booking->get_apartment_id() ) ); ?>
						</a>
						<?php
							if ( ! empty( $booking->get_apartment_unit() ) ) {
								echo esc_html( jet_abaf()->macros->macros_handler->do_macros( ' - %booking_unit_title%', $booking ) );
							}

							if ( 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) {
								if ( $booking->get_guests() ) {
									/* translators: 1: guests label, 2: guests count */
									printf( '<div class="jet-booking-guests">%1$s: %2$s</div>', esc_html__( 'Guests', 'jet-booking' ), esc_html( $booking->get_guests() ) );
								}

								$interval   = jet_abaf()->tools->get_booking_period_interval( $booking->get_check_in_date(), $booking->get_check_out_date(), $booking->get_apartment_id() );
								$attributes = jet_abaf()->wc->mode->attributes->get_attributes_for_display( $booking->get_attributes(), $interval, $booking->get_apartment_id() );

								foreach ( $attributes as $key => $attribute ) {
									if ( ! empty( $attribute['value'] ) ) {
										/* translators: 1: attribute key, 2: attribute label, 3: attribute terms list */
										printf( '<div class="jet-booking-service jet-booking-service__%1$s"><span>%2$s:</span> %3$s</div>', esc_attr( $key ), esc_html( $attribute['label'] ), esc_html( $attribute['value'] ) );
									}
								}
							}
						?>
					</td>
					<td data-title="<?php esc_html_e( 'Order', 'jet-booking' ); ?>" class="woocommerce-orders-table__cell related-order <?php echo empty( $booking->get_order_id() ) ? 'empty' : ''; ?>">
						<?php if ( $booking->get_order_id() ) :
							$order = wc_get_order( $booking->get_order_id() );

							if ( is_a( $order, 'WC_Order' ) && $order->get_user_id() === get_current_user_id() ) : ?>
								<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
									<?php echo esc_html( '#' . $order->get_order_number() ); ?>
								</a>
							<?php else :
								echo esc_html( '#' . $booking->get_order_id() );
							endif;
						endif; ?>
					</td>
					<td data-title="<?php esc_html_e( 'Check In', 'jet-booking' ); ?>" class="woocommerce-orders-table__cell booking-check-in">
						<?php echo esc_html( date_i18n( get_option( 'date_format', 'F j, Y' ), $booking->get_check_in_date() ) ); ?>
					</td>
					<td data-title="<?php esc_html_e( 'Check Out', 'jet-booking' ); ?>" class="woocommerce-orders-table__cell booking-check-out">
						<?php echo esc_html( date_i18n( get_option( 'date_format', 'F j, Y' ), $booking->get_check_out_date() ) ); ?>
					</td>
					<td data-title="<?php esc_html_e( 'Status', 'jet-booking' ); ?>" class="woocommerce-orders-table__cell booking-status">
						<?php echo esc_html( $booking->get_status() ); ?>
					</td>

					<?php if ( jet_abaf()->settings->get( 'booking_cancellation' ) ) : ?>
						<td data-title="<?php esc_html_e( 'Cancel', 'jet-booking' ); ?>" class="woocommerce-orders-table__cell booking-cancel empty">
							<?php if ( $booking->is_cancellable() ) : ?>
								<a href="<?php echo esc_url( $booking->get_cancel_url( get_permalink( wc_get_page_id( 'myaccount' ) ), get_permalink( wc_get_page_id( 'myaccount' ) ) ) ); ?>" class="button" data-cancel-booking="1">
									<?php esc_html_e( 'Cancel', 'jet-booking' ); ?>
								</a>
							<?php endif; ?>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'jet-bookings', $current_page - 1 ) ); ?>">
					<?php esc_html_e( 'Previous', 'jet-booking' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( count( $table['bookings'] ) > $bookings_per_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'jet-bookings', $current_page + 1 ) ); ?>">
					<?php esc_html_e( 'Next', 'jet-booking' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
<?php else : ?>
	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Go Shop', 'jet-booking' ); ?>
		</a>

		<?php esc_html_e( 'No bookings available yet.', 'jet-booking' ); ?>
	</div>
<?php endif; ?>