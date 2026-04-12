<div class="jet-abaf-bookings-filter">
	<div class="cx-vui-panel">
		<div class="jet-abaf-navigation-row">
			<div>
				<cx-vui-button @click="setMode( 'all' )" :button-style="modeButtonStyle( 'all' )" size="mini">
					<template slot="label">
						<?php esc_html_e( 'All', 'jet-booking' ); ?>
					</template>
				</cx-vui-button>

				<cx-vui-button @click="setMode( 'upcoming' )" :button-style="modeButtonStyle( 'upcoming' )" size="mini">
					<template slot="label">
						<?php esc_html_e( 'Upcoming', 'jet-booking' ); ?>
					</template>
				</cx-vui-button>

				<cx-vui-button @click="setMode( 'past' )" :button-style="modeButtonStyle( 'past' )" size="mini">
					<template slot="label">
						<?php esc_html_e( 'Past', 'jet-booking' ); ?>
					</template>
				</cx-vui-button>
			</div>

			<div>
				<cx-vui-button
					class="jet-abaf-show-filters"
					@click="expandFilters = ! expandFilters"
					button-style="link-accent"
					size="mini"
				>
					<svg slot="label" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="margin:0 5px 0 0;"> <path d="M19.479 2l-7.479 12.543v5.924l-1-.6v-5.324l-7.479-12.543h15.958zm3.521-2h-23l9 15.094v5.906l5 3v-8.906l9-15.094z" fill="currentColor"/> </svg>

					<span slot="label">
						<?php esc_html_e( 'Filters', 'jet-booking' ); ?>
					</span>
				</cx-vui-button>

				<cx-vui-button
					class="jet-abaf-show-filters"
					@click="showExportPopup = ! showExportPopup"
					button-style="link-accent"
					size="mini"
				>
					<svg slot="label" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill-rule="evenodd" clip-rule="evenodd" style="margin:0 5px 0 0;"> <path d="M23 0v20h-8v-2h6v-16h-18v16h6v2h-8v-20h22zm-12 13h-4l5-6 5 6h-4v11h-2v-11z" fill="currentColor"/> </svg>

					<span slot="label">
						<?php esc_html_e( 'Export', 'jet-booking' ); ?>
					</span>
				</cx-vui-button>
			</div>
		</div>

		<div v-if="expandFilters" class="jet-abaf-filters-row">
			<template v-for="( filter, name ) in filters" :key="name">
				<cx-vui-select
					v-if="isVisible( name, filter, 'select' )"
					:label="filter.label"
					:wrapper-css="[ 'jet-abaf-filter' ]"
					:options-list="prepareObjectForOptions( filter.value )"
					:value="currentFilters[ name ]"
					@input="updateFilters( $event, name, filter.type )"
				></cx-vui-select>

				<cx-vui-component-wrapper
					v-else-if="isVisible( name, filter, 'date-picker' )"
					:wrapper-css="[ 'jet-abaf-filter' ]"
					:label="filter.label"
				>
					<vuejs-datepicker
						input-class="cx-vui-input size-fullwidth"
						:value="currentFilters[ name ]"
						:format="dateFormat"
						:monday-first="!! monday_first"
						placeholder="<?php esc_html_e( 'dd/mm/yyyy', 'jet-booking' ); ?>"
						@input="updateFilters( $event, name, filter.type )"
					></vuejs-datepicker>
					<span
						v-if="currentFilters[ name ]"
						class="jet-abaf-date-clear"
						@click="updateFilters( '', name, filter.type )"
					>
						<svg width="12" height="16" viewBox="0 0 12 16" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M0.999998 13.8333C0.999998 14.75 1.75 15.5 2.66666 15.5H9.33333C10.25 15.5 11 14.75 11 13.8333V3.83333H0.999998V13.8333ZM2.66666 5.5H9.33333V13.8333H2.66666V5.5ZM8.91667 1.33333L8.08333 0.5H3.91666L3.08333 1.33333H0.166664V3H11.8333V1.33333H8.91667Z" fill="#D6336C"/> </svg>
					</span>
				</cx-vui-component-wrapper>
			</template>

			<cx-vui-button
				class="jet-abaf-clear-filters"
				@click="clearFilter()"
				button-style="accent-border"
				size="mini"
			>
				<template slot="label">
					<?php esc_html_e( 'Clear', 'jet-booking' ); ?>
				</template>
			</cx-vui-button>
		</div>
	</div>

	<cx-vui-popup
		v-model="showExportPopup"
		:ok-label="'<?php esc_html_e( 'Export', 'jet-booking' ) ?>'"
		:cancel-label="'<?php esc_html_e( 'Cancel', 'jet-booking' ) ?>'"
		body-width="500px"
		@on-cancel="showExportPopup = false"
		@on-ok="doExport"
	>
		<div class="cx-vui-subtitle" slot="title">
			<?php esc_html_e( 'Export bookings', 'jet-booking' ); ?>
		</div>

		<cx-vui-select
			slot="content"
			label="<?php esc_html_e( 'Bookings to export', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Select type of bookings to export.', 'jet-booking' ); ?>"
			:options-list="[
				{
					value: 'all',
					label: '<?php esc_html_e( 'All bookings', 'jet-booking' ); ?>'
				},
				{
					value: 'filtered',
					label: '<?php esc_html_e( 'Filtered bookings', 'jet-booking' ); ?>'
				}
			]"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportType"
		></cx-vui-select>

		<cx-vui-select
			slot="content"
			label="<?php esc_html_e( 'Export format', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Select format of exported data.', 'jet-booking' ); ?>"
			:options-list="[
				{
					value: 'csv',
					label: '<?php esc_html_e( 'CSV', 'jet-booking' ); ?>'
				},
				{
					value: 'ical',
					label: '<?php esc_html_e( 'iCal', 'jet-booking' ); ?>'
				}
			]"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportFormat"
		></cx-vui-select>

		<cx-vui-select
			slot="content"
			v-if="'csv' === exportFormat"
			label="<?php esc_html_e( 'Booking instance returns', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Select type of information that should be returned in booking instance columns.', 'jet-booking' ); ?>"
			:options-list="[
				{
					value: 'id',
					label: '<?php esc_html_e( 'ID', 'jet-booking' ); ?>'
				},
				{
					value: 'title',
					label: '<?php esc_html_e( 'Title', 'jet-booking' ); ?>'
				}
			]"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportDataReturnType"
		></cx-vui-select>

		<cx-vui-input
			slot="content"
			v-if="'csv' === exportFormat"
			label="<?php esc_html_e( 'Date format', 'jet-booking' ); ?>"
			description="<?php _e( 'Specify the date format in which check-in and check-out dates columns should be displayed. <a href=\'https://wordpress.org/support/article/formatting-date-and-time/\' target=\'_blank\'>Documentation on date and time formatting</a>.' , 'jet-booking' ); // phpcs:ignore ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportDateFormat"
		></cx-vui-input>

		<div slot="content" class="cx-vui-component__meta" style="align-items: flex-end;">
			<a class="jet-abaf-help-link" href="https://crocoblock.com/knowledge-base/jetbooking/how-to-export-bookings-from-dashboard/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help" target="_blank">
				<span class="dashicons dashicons-editor-help"></span>
				<?php esc_html_e( 'How it works?', 'jet-booking' ); ?>
			</a>
		</div>
	</cx-vui-popup>
</div>