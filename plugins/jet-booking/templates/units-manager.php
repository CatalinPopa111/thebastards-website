<div class="jet-abaf-units-manager">
	<cx-vui-button v-if="!loaded" button-style="accent" :loading="loading" @click="loadUnits">
		<span slot="label">
			<?php esc_html_e( 'Manage Units', 'jet-booking' ); ?>
		</span>
	</cx-vui-button>

	<div v-else>
		<div class="cx-vui-subtitle">
			<?php esc_html_e( 'Available Units', 'jet-booking' ); ?>
		</div>

		<cx-vui-list-table
			:is-empty="! unitsList.length"
			empty-message="<?php esc_html_e( 'No units were found for this apartment.', 'jet-booking' ); ?>"
		>
			<cx-vui-list-table-heading slot="heading" :slots="[ 'unit_id', 'name', 'actions' ]">
				<div slot="unit_id" style="width: 50px;">
					<?php esc_html_e( 'Unit ID', 'jet-booking' ); ?>
				</div>

				<div slot="name">
					<?php esc_html_e( 'Name', 'jet-booking' ); ?>
				</div>

				<div slot="actions">
					<?php esc_html_e( 'Actions', 'jet-booking' ); ?>
				</div>
			</cx-vui-list-table-heading>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'unit_id', 'name', 'actions' ]"
				v-for="unit in unitsList"
				:key="unit.unit_id"
			>
				<div slot="unit_id" style="width: 50px;">{{ unit.unit_id }}</div>

				<div slot="name">
					<cx-vui-input
						v-if="unitToEdit && unit.unit_id === unitToEdit.unit_id"
						:wrapper-css="[ 'equalwidth' ]"
						size="fullwidth"
						:prevent-wrap="true"
						:autofocus="true"
						v-model="unitToEdit.unit_title"
						@on-keyup.stop.enter="saveUnit"
					></cx-vui-input>
					<div v-else>
						{{ unit.unit_title }}
					</div>
				</div>

				<div slot="actions" class="jet-abaf-unit-actions">
					<cx-vui-button
						v-if="unitToEdit && unit.unit_id === unitToEdit.unit_id"
						button-style="link-accent"
						size="link"
						@click="saveUnit"
					>
						<span slot="label">
							<?php esc_html_e( 'Save', 'jet-booking' ); ?>
						</span>
					</cx-vui-button>

					<cx-vui-button v-else button-style="link-accent" size="link" @click="unitToEdit = unit">
						<span slot="label">
							<?php esc_html_e( 'Edit', 'jet-booking' ); ?>
						</span>
					</cx-vui-button>

					<div class="jet-abaf-delete-unit">
						<cx-vui-button
							v-if="unitToEdit && unit.unit_id === unitToEdit.unit_id"
							button-style="link-error"
							size="link"
							@click="unitToEdit = null"
						>
							<span slot="label">
								<?php esc_html_e( 'Cancel', 'jet-booking' ); ?>
							</span>
						</cx-vui-button>

						<cx-vui-button v-else button-style="link-error" size="link" @click="unitToDelete = unit.unit_id">
							<span slot="label">
								<?php esc_html_e( 'Delete', 'jet-booking' ); ?>
							</span>
						</cx-vui-button>

						<div v-if="unit.unit_id === unitToDelete" class="cx-vui-tooltip">
							<?php esc_html_e( 'Are you sure?', 'jet-booking' ); ?>
							<br>
							<span class="cx-vui-repeater-item__confrim-del" @click="deleteUnit">
								<?php esc_html_e( 'Yes', 'jet-booking' ); ?>
							</span>
							/
							<span class="cx-vui-repeater-item__cancel-del" @click="unitToDelete = null">
								<?php esc_html_e( 'No', 'jet-booking' ); ?>
							</span>
						</div>
					</div>
				</div>
			</cx-vui-list-table-item>
		</cx-vui-list-table>

		<br>

		<div class="cx-vui-subtitle">
			<?php esc_html_e( 'Add Units', 'jet-booking' ); ?>
		</div>

		<div class="cx-vui-panel">
			<cx-vui-input
				label="<?php esc_html_e( 'Number', 'jet-booking' ); ?>"
				description="<?php esc_html_e( 'Enter the number of created units.', 'jet-booking' ); ?>"
				type="number"
				:wrapper-css="[ 'equalwidth' ]"
				size="fullwidth"
				v-model="newUnitsNum"
			></cx-vui-input>

			<cx-vui-input
				label="<?php esc_html_e( 'Title', 'jet-booking' ); ?>"
				description="<?php esc_html_e( 'Enter the title for created units. If empty, the apartment title will be used.', 'jet-booking' ); ?>"
				:wrapper-css="[ 'equalwidth' ]"
				size="fullwidth"
				v-model="newUnitsTitle"
			></cx-vui-input>
		</div>

		<cx-vui-button button-style="accent" @click="insertUnits">
			<span slot="label">
				<?php esc_html_e( 'Add Units', 'jet-booking' ); ?>
			</span>
		</cx-vui-button>
	</div>
</div>
