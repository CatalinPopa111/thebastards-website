<div class="jet-abaf-bookings-list">
	<cx-vui-list-table
		:is-empty="! itemsList.length"
		empty-message="<?php esc_html_e( 'No bookings found', 'jet-booking' ); ?>"
	>
		<cx-vui-list-table-heading
			slot="heading"
			:slots="[ 'booking_id', 'apartment_id', 'apartment_unit', 'check_in_date', 'check_out_date', 'order_id', 'status', 'actions' ]"
		>
			<span
				slot="booking_id"
				class="list-table-heading__cell-content list-table-heading__cell-clickable"
				:class="classColumn( 'booking_id' )"
				@click="sortColumn( 'booking_id' )"
			>
				<?php esc_html_e( 'ID', 'jet-booking' ); ?>
			</span>

			<span slot="apartment_id"><?php esc_html_e( 'Instance', 'jet-booking' ); ?></span>
			<span slot="apartment_unit"><?php esc_html_e( 'Unit', 'jet-booking' ); ?></span>

			<span
				slot="check_in_date"
				class="list-table-heading__cell-content list-table-heading__cell-clickable"
				:class="classColumn( 'check_in_date' )"
				@click="sortColumn( 'check_in_date' )"
			>
				<?php esc_html_e( 'Check In', 'jet-booking' ); ?>
			</span>

			<span
				slot="check_out_date"
				class="list-table-heading__cell-content list-table-heading__cell-clickable"
				:class="classColumn( 'check_out_date' )"
				@click="sortColumn( 'check_out_date' )"
			>
				<?php esc_html_e( 'Check Out', 'jet-booking' ); ?>
			</span>

			<span
				slot="order_id"
				class="list-table-heading__cell-content list-table-heading__cell-clickable <?php echo ! jet_abaf()->tools->can_edit_order() ? 'list-table-heading__cell-info' : ''; ?>"
				:class="classColumn( 'order_id' )"
				@click="sortColumn( 'order_id' )"
			>
				<?php esc_html_e( 'Related Order', 'jet-booking' ); ?>
				<?php if ( ! jet_abaf()->tools->can_edit_order() ) : ?>
					<span class="tooltip"><?php esc_attr_e( 'Access to orders allowed only for website admin.', 'jet-booking' ); ?></span>
				<?php endif; ?>
			</span>

			<span
				slot="status"
				class="list-table-heading__cell-content list-table-heading__cell-clickable"
				:class="classColumn( 'status' )"
				@click="sortColumn( 'status' )"
			>
				<?php esc_html_e( 'Status', 'jet-booking' ); ?>
			</span>

			<span slot="actions"><?php esc_html_e( 'Actions', 'jet-booking' ); ?></span>
		</cx-vui-list-table-heading>

		<cx-vui-list-table-item
			slot="items"
			:slots="[ 'booking_id', 'apartment_id', 'apartment_unit', 'check_in_date', 'check_out_date', 'order_id', 'status', 'actions' ]"
			v-for="( item, index ) in itemsList"
			:key="item.booking_id"
		>
			<span slot="booking_id">{{ item.booking_id }}</span>
			<span slot="apartment_id">{{ getItemLabel( item.apartment_id ) }}</span>
			<span slot="apartment_unit">{{ getItemUnitLabel( item.apartment_id, item.apartment_unit ) }}</span>
			<span slot="check_in_date">
				{{ item.check_in_date }}
				<span v-if="item.check_in_time">
					<br> {{ item.check_in_time }}
				</span>
			</span>
			<span slot="check_out_date">
				{{ item.check_out_date }}
				<span v-if="item.check_out_time">
					<br> {{ item.check_out_time }}
				</span>
			</span>
			<span slot="order_id">
				<?php if ( jet_abaf()->tools->can_edit_order() ) : ?>
					<a v-if="item.order_id" :href="getOrderLink( item.order_id )" target="_blank">#{{ item.order_id }}</a>
				<?php else : ?>
					#{{ item.order_id }}
				<?php endif; ?>
			</span>
			<span slot="status" :class="statusClass( item.status )">{{ statuses[ item.status ] }}</span>

			<div slot="actions" class="jet-abaf-actions">
				<cx-vui-button
					button-style="accent"
					size="mini"
					@click="callPopup( 'update', item )"
				>
					<template slot="label">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.5 12.375V15.5H3.625L12.8417 6.28333L9.71667 3.15833L0.5 12.375ZM2.93333 13.8333H2.16667V13.0667L9.71667 5.51667L10.4833 6.28333L2.93333 13.8333ZM15.2583 2.69167L13.3083 0.741667C13.1417 0.575 12.9333 0.5 12.7167 0.5C12.5 0.5 12.2917 0.583333 12.1333 0.741667L10.6083 2.26667L13.7333 5.39167L15.2583 3.86667C15.5833 3.54167 15.5833 3.01667 15.2583 2.69167Z" fill="white"/></svg>
						<?php esc_html_e( 'Edit', 'jet-booking' ); ?>
					</template>
				</cx-vui-button>

				<cx-vui-button
					button-style="link-accent"
					size="link"
					@click="callPopup( 'info', item )"
				>
					<span slot="label">
						<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.16667 4.83333H9.83333V6.5H8.16667V4.83333ZM8.16667 8.16666H9.83333V13.1667H8.16667V8.16666ZM9 0.666664C4.4 0.666664 0.666668 4.4 0.666668 9C0.666668 13.6 4.4 17.3333 9 17.3333C13.6 17.3333 17.3333 13.6 17.3333 9C17.3333 4.4 13.6 0.666664 9 0.666664ZM9 15.6667C5.325 15.6667 2.33333 12.675 2.33333 9C2.33333 5.325 5.325 2.33333 9 2.33333C12.675 2.33333 15.6667 5.325 15.6667 9C15.6667 12.675 12.675 15.6667 9 15.6667Z" fill="#007CBA"/></svg>
					</span>
				</cx-vui-button>

				<cx-vui-button
					button-style="link-error"
					size="link"
					@click="callPopup( 'delete', item )"
				>
					<span slot="label">
						<svg width="12" height="16" viewBox="0 0 12 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.999998 13.8333C0.999998 14.75 1.75 15.5 2.66666 15.5H9.33333C10.25 15.5 11 14.75 11 13.8333V3.83333H0.999998V13.8333ZM2.66666 5.5H9.33333V13.8333H2.66666V5.5ZM8.91667 1.33333L8.08333 0.5H3.91666L3.08333 1.33333H0.166664V3H11.8333V1.33333H8.91667Z" fill="#D6336C"/></svg>
					</span>
				</cx-vui-button>
			</div>
		</cx-vui-list-table-item>
	</cx-vui-list-table>

	<cx-vui-pagination
		v-if="perPage < totalItems"
		:total="totalItems"
		:page-size="perPage"
		:current="pageNumber"
		@on-change="changePage"
	></cx-vui-pagination>
</div>
