<div class="jet-booking-workflow__item">
	<div class="jet-booking-workflow__item-header">
		<div class="jet-abaf-remove-tooltip-wrapper">
			<span
				class="jet-abaf-remove-tooltip-button dashicons dashicons-trash"
				@click="confirmDelete = ! confirmDelete">
			</span>

			<div class="cx-vui-tooltip" v-if="confirmDelete">
				<?php esc_html_e( 'Are you sure?', 'jet-booking' ); ?>
				<br>
				<span class="cx-vui-repeater-item__confrim-del" @click="deleteItem()">
					<?php esc_html_e( 'Yes', 'jet-booking' ); ?>
				</span>
				/
				<span class="cx-vui-repeater-item__cancel-del" @click="confirmDelete = false">
					<?php esc_html_e( 'No', 'jet-booking' ); ?>
				</span>
			</div>
		</div>
	</div>

	<cx-vui-select
		label="<?php esc_html_e( 'Event', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Select event to trigger a workflow item.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:options-list="events"
		:value="item.event"
		@input="updateItem( $event, 'event' )"
	></cx-vui-select>

	<?php do_action( 'jet-booking/workflows/event-controls' ); ?>

	<cx-vui-select
		label="<?php esc_html_e( 'Start', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Select when start to run current workflow.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:options-list="[
			{
				value: 'immediately',
				label: '<?php esc_html_e( 'Immediately', 'jet-booking' ); ?>'
			},
			{
				value: 'scheduled',
				label: '<?php esc_html_e( 'Scheduled', 'jet-booking' ); ?>'
			}
		]"
		:value="item.schedule"
		@input="updateItem( $event, 'schedule' )"
	></cx-vui-select>

	<template v-if="'scheduled' === item.schedule">
		<cx-vui-select
			label="<?php esc_html_e( 'Date', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Select the date type according to which the current workflow will work.', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			:options-list="[
			{
				value: 'check_in_date',
				label: '<?php esc_html_e( 'Check in', 'jet-booking' ); ?>'
			},
			{
				value: 'check_out_date',
				label: '<?php esc_html_e( 'Check out', 'jet-booking' ); ?>'
			}
		]"
			:value="item.date_type"
			@input="updateItem( $event, 'date_type' )"
		></cx-vui-select>

		<cx-vui-select
			label="<?php esc_html_e( 'Condition', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Select date threshold condition according to which the current workflow will work.', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			:options-list="[
			{
				value: 'before',
				label: '<?php esc_html_e( 'Before', 'jet-booking' ); ?>'
			},
			{
				value: 'after',
				label: '<?php esc_html_e( 'After', 'jet-booking' ); ?>'
			}
		]"
			:value="item.condition"
			@input="updateItem( $event, 'condition' )"
		></cx-vui-select>

		<cx-vui-input
			type="number"
			label="<?php esc_html_e( 'Days', 'jet-booking' ); ?>"
			description="<?php esc_html_e( 'Run this item in selected number of days.', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			min="1"
			max="14"
			:value="item.days"
			@input="updateItem( $event, 'days' )"
		></cx-vui-input>
	</template>

	<div class="cx-vui-inner-panel">
		<cx-vui-repeater
			button-label="<?php esc_html_e( '+ New Action', 'jet-booking' ); ?>"
			button-style="accent"
			button-size="mini"
			:value="item.actions"
			@input="updateActions( $event )"
			@add-new-item="addAction"
		>
			<cx-vui-repeater-item
				v-for="( action, index ) in item.actions"
				:title="getActionTitle( action )"
				:collapsed="isCollapsed( action )"
				:index="index"
				:key="action.hash"
				@clone-item="cloneAction( $event, index )"
				@delete-item="deleteAction( $event, index )"
			>
				<cx-vui-input
					label="<?php esc_html_e( 'Name', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Name of the action to visually identify it in the list.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth' ]"
					size="fullwidth"
					:value="item.actions[ index ].title"
					@on-input-change="setActionProp( index, 'title', $event.target.value )"
				></cx-vui-input>

				<cx-vui-select
					label="<?php esc_html_e( 'Action', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Select action to run.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth' ]"
					size="fullwidth"
					:options-list="actions"
					:value="item.actions[ index ].action_id"
					@input="setActionProp( index, 'action_id', $event )"
				></cx-vui-select>

				<?php do_action( 'jet-booking/workflows/action-controls' ); ?>
			</cx-vui-repeater-item>
		</cx-vui-repeater>
	</div>
</div>