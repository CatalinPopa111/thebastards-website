<?php
/**
 * Admin order details.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/admin/order/details.php.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$first_iteration = true;
?>
<table class="widefat fixed striped">
	<?php foreach ( $details as $detail ) : ?>
		<?php if ( $first_iteration ) : ?>
			<thead>
				<tr>
					<?php foreach ( $detail as $item ) {
						printf( '<th>%s</th>', ! empty( $item['key'] ) ? esc_html( $item['key'] ) : '' );
					} ?>
				</tr>
			</thead>

			<?php $first_iteration = false; ?>
		<?php endif; ?>

		<tr>
			<?php foreach ( $detail as $item ) {
				printf( '<td>%s</td>', ! empty( $item['is_html'] ) ? wp_kses_post( $item['display'] ) : esc_html( $item['display'] ) );
			} ?>
		</tr>
	<?php endforeach; ?>
</table>
