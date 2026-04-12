<div
	class="jet-booking-macros"
	v-click-outside.capture="onClickOutside"
	v-click-outside:mousedown.capture="onClickOutside"
	v-click-outside:touchstart.capture="onClickOutside"
	@keydown.esc="onClickOutside"
>
	<div class="jet-booking-macros__trigger" @click="switchIsActive()">
		<span v-if="isActive" class="dashicons dashicons-no-alt"></span>
		<span v-else class="dashicons dashicons-database-add"></span>
	</div>

	<div v-if="isActive" class="jet-booking-macros__popup">
		<div v-if="editMacros" class="jet-booking-macros__content">
			<div class="jet-booking-macros__title">
				<span class="jet-booking-macros__back" @click="resetEdit()">
					<?php esc_html_e( 'All Macros', 'jet-engine' ); ?>
				</span> > {{ currentMacros.name }}:
			</div>

			<div class="jet-booking-macros__controls">
				<div class="jet-booking-macros__control" v-for="control in getPreparedControls()">
					<component
						v-if="checkCondition( control.condition )"
						:is="control.type"
						:label="control.label"
						:wrapper-css="[ 'mini-label' ]"
						size="fullwidth"
						:options-list="control.optionsList"
						:groups-list="control.groupsList"
						:value="getControlValue( control )"
						@input="setMacrosArg( $event, control.name )"
					></component>
				</div>
			</div>

			<cx-vui-button button-style="accent" size="mini" @click="applyMacros( false, true )">
				<span slot="label">
					<?php esc_html_e( 'Apply', 'jet-engine' ); ?>
				</span>
			</cx-vui-button>
		</div>
		<div v-else class="jet-booking-macros__content">
			<div class="jet-booking-macros__list">
				<div class="jet-booking-macros-item" v-for="macros in macrosList">
					<div class="jet-booking-macros-item__name" @click="applyMacros( macros )">
						<span class="jet-booking-macros-item__mark">≫</span> {{ macros.name }}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>