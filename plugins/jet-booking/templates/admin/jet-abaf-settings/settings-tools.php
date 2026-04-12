<div>
	<p v-if="! dbTablesExists">
		<?php esc_html_e( 'Before start you need to create required DB tables.', 'jet-booking' ); ?>
	</p>

	<cx-vui-component-wrapper
		v-if="! toolsSettings.hide_columns_manager"
		:wrapper-css="[ 'fullwidth-control' ]"
	>
		<div class="cx-vui-inner-panel">
			<div class="cx-vui-subtitle">
				<?php esc_html_e( 'Additional table columns', 'jet-booking' ); ?>
			</div>
			<div class="cx-vui-component__desc">
				<?php esc_html_e( 'You can add custom columns to the booking table, which is best to do before table creation. Such columns are booking-specific and indicate additional services, details, etc. Once added, you need to map (connect) these new columns to the corresponding form fields in the related booking form.', 'jet-booking' ); ?>
			</div>
			
			<br>

			<cx-vui-repeater
				button-label="<?php esc_html_e( 'New DB Column', 'jet-booking' ); ?>"
				button-style="accent"
				button-size="mini"
				v-model="toolsSettings.additional_columns"
				@add-new-item="addNewColumn"
			>
				<cx-vui-repeater-item
					v-for="( column, columnIndex ) in toolsSettings.additional_columns"
					:title="toolsSettings.additional_columns[ columnIndex ].column"
					:collapsed="isCollapsed( column )"
					:index="columnIndex"
					@clone-item="cloneColumn( $event, columnIndex )"
					@delete-item="deleteColumn( $event, columnIndex )"
					:key="'column' + columnIndex"
				>
					<cx-vui-input
						label="<?php esc_html_e( 'Column name', 'jet-booking' ); ?>"
						description="<?php esc_html_e( 'Name for additional DB column', 'jet-booking' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
						size="fullwidth"
						:value="toolsSettings.additional_columns[ columnIndex ].column"
						@on-input-change="setColumnProp( columnIndex, 'column', $event.target.value )"
					></cx-vui-input>
				</cx-vui-repeater-item>
			</cx-vui-repeater>

			<div style="margin: 20px 0 -5px;">
				<?php _e( '<b>Warning:</b> If you change or remove any columns, all data stored in these columns will be lost!', 'jet-booking' ); // phpcs:ignore ?>
			</div>
		</div>
	</cx-vui-component-wrapper>

	<cx-vui-button
		:button-style="'accent'"
		:loading="processingTables"
		@click="processTables"
	>
		<span v-if="! dbTablesExists" slot="label">
			<?php esc_html_e( 'Create Booking Tables', 'jet-booking' ); ?>
		</span>
		<span v-else slot="label">
			<?php esc_html_e( 'Update Booking Tables', 'jet-booking' ); ?>
		</span>
	</cx-vui-button>
</div>