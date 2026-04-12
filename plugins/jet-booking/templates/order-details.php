<?php
/**
 * Order details.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/order-details.php.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<h2 class="woocommerce-order-details__title">
	<?php esc_html_e( 'Booking Details', 'jet-booking' ); ?>
</h2>

<?php foreach ( $details as $detail ) : ?>
	<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
		<?php foreach ( $detail as $item ) {
			echo '<li>';

			if ( ! empty( $item['key'] ) ) {
				echo esc_html( $item['key'] ) . ': ';
			}

			if ( ! empty( $item['is_html'] ) ) {
				echo wp_kses_post( $item['display'] );
			} else {
				echo '<strong>' . esc_html( $item['display'] ) . '</strong>';
			}

			echo '</li>';
		} ?>
	</ul>
<?php endforeach; ?>