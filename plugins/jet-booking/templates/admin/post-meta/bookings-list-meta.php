<div class="jet-abaf-bookings-list-meta">
	<table v-if="bookings.length" class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'jet-booking' ); ?></th>
				<th><?php esc_html_e( 'Status', 'jet-booking' ); ?></th>
				<th><?php esc_html_e( 'Unit ID', 'jet-booking' ); ?></th>
				<th v-if="isOrder"><?php esc_html_e( 'Instance', 'jet-booking' ); ?></th>
				<th v-else><?php esc_html_e( 'Related Order', 'jet-booking' ); ?></th>
				<th><?php esc_html_e( 'Check In', 'jet-booking' ); ?></th>
				<th><?php esc_html_e( 'Check Out', 'jet-booking' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'jet-booking' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<tr v-for="( booking, index ) in bookings">
				<td>{{ booking.ID }}</td>
				<td>{{ booking.status }}</td>
				<td>{{ booking.apartment_unit }}</td>
				<td v-if="isOrder">{{ getInstanceLabel( booking.apartment_id ) }}</td>
				<td v-else>
					<a v-if="booking.order_id" :href="getOrderLink( booking.order_id )" target="_blank">
						#{{ booking.order_id }}
					</a>
				</td>
				<td>{{ booking.check_in_date }}</td>
				<td>{{ booking.check_out_date }}</td>
				<td>
					<div class="action-wrapper">
						<a :href="getDetailsLink( booking.ID )" target="_blank">
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path
									d="M8.16667 4.83333H9.83333V6.5H8.16667V4.83333ZM8.16667 8.16666H9.83333V13.1667H8.16667V8.16666ZM9 0.666664C4.4 0.666664 0.666668 4.4 0.666668 9C0.666668 13.6 4.4 17.3333 9 17.3333C13.6 17.3333 17.3333 13.6 17.3333 9C17.3333 4.4 13.6 0.666664 9 0.666664ZM9 15.6667C5.325 15.6667 2.33333 12.675 2.33333 9C2.33333 5.325 5.325 2.33333 9 2.33333C12.675 2.33333 15.6667 5.325 15.6667 9C15.6667 12.675 12.675 15.6667 9 15.6667Z"
									fill="#007CBA"
								/>
							</svg>
						</a>

						<a href="#" @click.prevent="handleDelete( booking.ID, index )">
							<svg width="12" height="16" viewBox="0 0 12 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path
									d="M0.999998 13.8333C0.999998 14.75 1.75 15.5 2.66666 15.5H9.33333C10.25 15.5 11 14.75 11 13.8333V3.83333H0.999998V13.8333ZM2.66666 5.5H9.33333V13.8333H2.66666V5.5ZM8.91667 1.33333L8.08333 0.5H3.91666L3.08333 1.33333H0.166664V3H11.8333V1.33333H8.91667Z"
									fill="#D6336C"
								/>
							</svg>
						</a>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<p v-else>
		<?php esc_html_e( 'There are no related bookings.', 'jet-booking' );  ?>
	</p>

	<p>
		<cx-vui-button button-style="accent-border" size="mini" tag-name="a" :url="bookingsLink" target="_blank">
			<template slot="label">
				<?php esc_html_e( 'Go to All Bookings', 'jet-booking' ); ?>
			</template>
		</cx-vui-button>
	</p>
</div>