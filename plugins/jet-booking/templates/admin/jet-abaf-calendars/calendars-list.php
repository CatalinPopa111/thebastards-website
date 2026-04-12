<div :class="[ 'jet-abaf-calendars-list', { 'jet-abaf-loading': isLoading } ]">
	<div class="jet-abaf-header">
		<h1 class="jet-abaf-title">
			<?php esc_html_e( 'Calendars', 'jet-booking' ); ?>
		</h1>

		<cx-vui-button button-style="accent" size="mini" @click="showICalTemplateDialog()">
			<span slot="label">
				<?php esc_html_e( 'iCalendar Template', 'jet-appoinments-booking' ); ?>
			</span>
		</cx-vui-button>

		<div class="cx-vui-component__meta" style="flex: 1 0 auto; align-items: flex-end;">
			<a class="jet-abaf-help-link" href="https://crocoblock.com/knowledge-base/jetbooking/setting-two-way-booking-and-google-calendar-ical-synchronization/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help" target="_blank">
				<span class="dashicons dashicons-editor-help"></span>
				<?php esc_html_e( 'What is this and how it works?', 'jet-booking' ); ?>
			</a>
		</div>
	</div>

	<cx-vui-list-table
		:is-empty="! itemsList.length"
		empty-message="<?php esc_html_e( 'No calendars found', 'jet-booking' ); ?>"
	>
		<cx-vui-list-table-heading
			slot="heading"
			:slots="[ 'post_title', 'unit_title', 'export_url', 'import_url', 'actions' ]"
		>
			<span slot="post_title">
				<?php esc_html_e( 'Post Title', 'jet-booking' ); ?>
			</span>
			<span slot="unit_title">
				<?php esc_html_e( 'Unit Title', 'jet-booking' ); ?>
			</span>
			<span slot="export_url">
				<?php esc_html_e( 'Export URL', 'jet-booking' ); ?>
			</span>
			<span slot="import_url">
				<?php esc_html_e( 'External Calendars', 'jet-booking' ); ?>
			</span>
			<span slot="actions">
				<?php esc_html_e( 'Actions', 'jet-booking' ); ?>
			</span>
		</cx-vui-list-table-heading>

		<cx-vui-list-table-item
			slot="items"
			:slots="[ 'post_title', 'unit_title', 'export_url', 'import_url', 'actions' ]"
			v-for="( item, index ) in itemsList"
			:key="item.post_id + item.unit_id"
		>
			<span slot="post_title">{{ item.title }}</span>
			<span slot="unit_title">{{ item.unit_title }}</span>
			<code slot="export_url">
				{{ item.export_url }}
				<a class="export-icon" :href="item.export_url">
					<svg slot="label" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill-rule="evenodd" clip-rule="evenodd"><path d="M23 0v20h-8v-2h6v-16h-18v16h6v2h-8v-20h22zm-12 13h-4l5-6 5 6h-4v11h-2v-11z" fill="currentColor"/></svg>
				</a>
			</code>

			<div slot="import_url" class="jet-abaf-links">
				<ul v-if="item.import_url && item.import_url.length">
					<li v-for="url in item.import_url" :key="url">
						<a :href="url">{{ url }}</a>
					</li>
				</ul>
				<div v-else>--</div>
			</div>

			<div slot="actions" class="jet-abaf-actions">
				<cx-vui-button
					v-if="item.import_url && item.import_url.length"
					button-style="accent-border"
					size="mini"
					@click="showSynchDialog( item )"
				>
					<span slot="label">
						<?php esc_html_e( 'Synch', 'jet-appoinments-booking' ); ?>
					</span>
				</cx-vui-button>

				<cx-vui-button button-style="accent" size="mini" @click="showEditDialog( item, index )">
					<span slot="label">
						<?php esc_html_e( 'Edit Calendars', 'jet-appoinments-booking' ); ?>
					</span>
				</cx-vui-button>
			</div>
		</cx-vui-list-table-item>
	</cx-vui-list-table>

	<cx-vui-popup
		:class="[ 'jet-abaf-popup', { 'jet-abaf-submitting': submitting } ]"
		v-model="editDialog"
		body-width="400px"
		ok-label="<?php esc_html_e( 'Save', 'jet-booking' ) ?>"
		@on-cancel="editDialog = false"
		@on-ok="handleEdit"
	>
		<div slot="title" class="cx-vui-subtitle">
			<?php esc_html_e( 'Edit Calendars:', 'jet-booking' ); ?>
		</div>
		<div slot="content" class="jet-abaf-calendars jet-abaf-calendars-edit">
			<div class="jet-abaf-details__field" v-for="( url, index ) in currentItem.import_url">
				<div class="jet-abaf-details__content">
					<input type="url" placeholder="https://calendar-link.com" v-model="currentItem.import_url[ index ]">
					<span class="dashicons dashicons-trash" @click="removeURL( index )"></span>
				</div>
			</div>

			<a href="#" @click.prevent="addURL" :style="{ textDecoration: 'none' }">
				<b><?php esc_html_e( '+ New URL', 'jet-booking' ); ?></b>
			</a>
		</div>
	</cx-vui-popup>

	<cx-vui-popup
		class="jet-abaf-popup"
		v-model="synchDialog"
		body-width="600px"
		cancel-label="<?php esc_html_e( 'Close', 'jet-booking' ) ?>"
		@on-cancel="synchDialog = false"
		:show-ok="false"
	>
		<div slot="title" class="cx-vui-subtitle">
			<?php esc_html_e( 'Synchronizing Calendars:', 'jet-booking' ); ?>
		</div>
		<div slot="content" class="jet-abaf-calendars">
			<div v-if="! synchLog">
				<?php esc_html_e( 'Processing...', 'jet-booking' ); ?>
			</div>
			<div v-else v-html="synchLog" class="jet-abaf-synch-log"></div>
		</div>
	</cx-vui-popup>

	<cx-vui-popup
		:class="[ 'jet-abaf-popup', { 'jet-abaf-submitting': submitting } ]"
		v-model="iCalTemplateDialog"
		body-width="750px"
		ok-label="<?php esc_html_e( 'Save', 'jet-booking' ) ?>"
		@on-cancel="editDialog = false"
		@on-ok="handleICalTemplate"
	>
		<div slot="title" class="cx-vui-subtitle">
			<?php esc_html_e( 'iCalendar Template:', 'jet-booking' ); ?>
		</div>
		<div slot="content" class="jet-abaf-calendars">
			<div class="jet-abaf-details__field">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Summary:', 'jet-booking' ); ?>
				</div>
				<div class="jet-abaf-details__content has-macros">
					<input type="text" class="cx-vui-input" v-model="iCalTemplate.summary" ref="summary">
					<jet-abaf-settings-macros-inserter @input="addMacros( 'summary', $event )"></jet-abaf-settings-macros-inserter>
				</div>
			</div>

			<div class="jet-abaf-details__field">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Description:', 'jet-booking' ); ?>
				</div>
				<div class="jet-abaf-details__content has-macros">
					<textarea class="cx-vui-textarea" v-model="iCalTemplate.description" rows="5" ref="description"></textarea>
					<jet-abaf-settings-macros-inserter @input="addMacros( 'description', $event )"></jet-abaf-settings-macros-inserter>
				</div>
			</div>

			<?php if ( class_exists( 'Jet_Engine' ) ) : ?>
				<div class="cx-vui-component__meta">
					<a class="jet-abaf-help-link" href="<?php echo esc_url( add_query_arg( [ 'page' => 'jet-engine#macros_generator' ], admin_url( 'admin.php' ) ) ); ?>" target="_blank">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Need some more dynamic? Generate macros here.', 'jet-booking' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</cx-vui-popup>
</div>