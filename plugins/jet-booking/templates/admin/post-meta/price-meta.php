<div id='jet-abaf-price-meta-box'>
	<cx-vui-component-wrapper
		label="<?php esc_html_e( 'Price per 1 day/night', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Name: _apartment_price.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'width-30-40', 'apartment-price' ]"
	>
		<cx-vui-input
			type="number"
			:min="0"
			:step="0.1"
			v-model="meta._apartment_price"
			@on-blur="updateSetting"
		></cx-vui-input>

		<cx-vui-button
			:class="{ 'jet-abaf-price-active': meta._pricing_rates.length }"
			button-style="accent-border"
			size="mini"
			@click="showPopUp( meta._pricing_rates, meta._apartment_price, 'rates' )"
		>
			<template slot="label">
				<?php esc_html_e( 'Add rates', 'jet-booking' ); ?>
			</template>
		</cx-vui-button>

		<cx-vui-button
			:class="{ 'jet-abaf-price-active': isWeekendPriceActive( meta._weekend_prices ) }"
			button-style="accent-border"
			size="mini"
			@click="showPopUp( meta._weekend_prices, meta._apartment_price, 'weekend' )"
		>
			<template slot="label">
				<?php esc_html_e( 'Add weekend ', 'jet-booking' ); ?>
			</template>
		</cx-vui-button>
	</cx-vui-component-wrapper>

	<div class="cx-vui-component cx-vui-component--width-30-40">
		<div class="cx-vui-component__meta">
			<div class="cx-vui-component__label">
				<?php esc_html_e( 'Seasonal prices', 'jet-booking' ); ?>
			</div>

			<div class="cx-vui-component__meta" style="margin-top: 10px;">
				<a class="jet-abaf-help-link" href="https://crocoblock.com/knowledge-base/jetbooking/how-to-set-up-the-seasonal-pricing/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help" target="_blank">
					<span class="dashicons dashicons-editor-help"></span>
					<?php esc_html_e( 'What is this and how it works?', 'jet-booking' ); ?>
				</a>
			</div>
		</div>

		<div class="cx-vui-component__control">
			<cx-vui-repeater
				button-label="<?php esc_html_e( 'Add price', 'jet-booking' ); ?>"
				button-style="accent-border"
				button-size="mini"
				v-model="meta._seasonal_prices"
				@add-new-item="addSP"
			>
				<cx-vui-repeater-item
					v-for="( item, index ) in meta._seasonal_prices"
					:title="getRepeaterTitle( item )"
					:index="index"
					:key="index"
					:collapsed="isCollapsed( item )"
					@clone-item="cloneSP( item )"
					@delete-item="deleteSP( index )"
				>
					<cx-vui-input
						label="<?php esc_html_e( 'Title', 'jet-booking' ); ?>"
						size="fullwidth"
						:value="item.title"
						@on-blur="changeSPValue( $event, 'title', index )"
					></cx-vui-input>

					<cx-vui-component-wrapper
						label="<?php esc_html_e( 'Price per 1 day/night', 'jet-booking' ); ?>"
						:wrapper-css="[ 'width-30-40', 'apartment-price' ]"
					>
						<cx-vui-input
							type="number"
							min="0"
							step="0.1"
							:value="item.price"
							@on-blur="changeSPValue( $event, 'price', index )"
						></cx-vui-input>

						<cx-vui-button
							:class="{ 'jet-abaf-price-active': item._pricing_rates.length }"
							button-style="accent-border"
							size="mini"
							@click="showPopUp( item._pricing_rates, item.price, 'rates' )"
						>
							<template slot="label"><?php esc_html_e( 'Add rates', 'jet-booking' ); ?></template>
						</cx-vui-button>

						<cx-vui-button
							:class="{ 'jet-abaf-price-active': isWeekendPriceActive( item._weekend_prices ) }"
							button-style="accent-border"
							size="mini"
							@click="showPopUp( item._weekend_prices, item.price, 'weekend' )"
						>
							<template slot="label"><?php esc_html_e( 'Add weekend ', 'jet-booking' ); ?></template>
						</cx-vui-button>
					</cx-vui-component-wrapper>

					<cx-vui-switcher
						label="<?php esc_html_e( 'Date Picker Configuration', 'jet-booking' ); ?>"
						description="<?php esc_html_e( 'You can enable and setup datepicker configuration for apartment season.', 'jet-booking' ); ?>"
						:wrapper-css="[ 'equalwidth' ]"
						:value="item.enable_config"
						@input="changeSPValue( $event, 'enable_config', index )"
					></cx-vui-switcher>

					<div v-if="item.enable_config">
						<cx-vui-input
							type="number"
							label="<?php esc_html_e( 'Starting day offset', 'jet-booking' ); ?>"
							description="<?php esc_html_e( 'This string defines offset for the earliest date which is available to the user.', 'jet-booking' ); ?>"
							:wrapper-css="[ 'equalwidth' ]"
							size="fullwidth"
							min="0"
							:value="item.start_day_offset"
							@on-blur="changeSPValue( $event, 'start_day_offset', index )"
						></cx-vui-input>

						<cx-vui-input
							type="number"
							label="<?php esc_html_e( 'Min days', 'jet-booking' ); ?>"
							description="<?php esc_html_e( 'This number defines the minimum days of the selected range. If it equals 0, it means minimum days are not limited.', 'jet-booking' ); ?>"
							:wrapper-css="[ 'equalwidth' ]"
							size="fullwidth"
							min="0"
							:value="item.min_days"
							@on-blur="changeSPValue( $event, 'min_days', index )"
						></cx-vui-input>

						<cx-vui-input
							type="number"
							label="<?php esc_html_e( 'Max days', 'jet-booking' ); ?>"
							description="<?php esc_html_e( 'This number defines the maximum days of the selected range. If it equals 0, it means maximum days are not limited.', 'jet-booking' ); ?>"
							:wrapper-css="[ 'equalwidth' ]"
							size="fullwidth"
							min="0"
							:value="item.max_days"
							@on-blur="changeSPValue( $event, 'max_days', index )"
						></cx-vui-input>
					</div>

					<cx-vui-component-wrapper
						label="<?php esc_html_e( 'Start Date', 'jet-booking' ); ?>"
						:wrapper-css="[ 'width-30-40' ]"
					>
						<vuejs-datepicker
							input-class="cx-vui-input size-fullwidth"
							placeholder="<?php esc_html_e( 'Select Date', 'jet-booking' ); ?>"
							:format="timestampToDate( item.startTimestamp, dateMomentFormat )"
							:value="secondsToMilliseconds( item.startTimestamp )"
							@selected="changeSPValue( $event, 'startTimestamp', index )"
						></vuejs-datepicker>
					</cx-vui-component-wrapper>

					<cx-vui-component-wrapper
						label="<?php esc_html_e( 'End Date', 'jet-booking' ); ?>"
						:wrapper-css="[ 'width-30-40' ]"
					>
						<vuejs-datepicker
							input-class="cx-vui-input size-fullwidth"
							placeholder="<?php esc_html_e( 'Select Date', 'jet-booking' ); ?>"
							:format="timestampToDate( item.endTimestamp, dateMomentFormat )"
							:value="secondsToMilliseconds( item.endTimestamp )"
							@selected="changeSPValue( $event, 'endTimestamp', index )"
						></vuejs-datepicker>
					</cx-vui-component-wrapper>
					<?php /*
				<cx-vui-select
					label="<?php esc_html_e( 'Repeat Season', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Select the aging period of the season. For example every year, month or week.', 'jet-booking' ); ?>"
					:options-list="period_repeats_seasons"
					:wrapper-css="[ 'width-30-40' ]"
					:size="'fullwidth'"
					:value="item.repeatSeason"
					@input="changeSPValue( $event, 'repeatSeason', index )"
				></cx-vui-select>
				*/ ?>

				</cx-vui-repeater-item>
			</cx-vui-repeater>
		</div>
	</div>

	<cx-vui-popup
		v-model="popUpActive_rates"
		body-width="600px"
		:footer="false"
		@on-cancel="hidePopUp('rates')"
		class="jet-apb-popup"
	>
		<div class="cx-vui-subtitle" slot="title">
			<?php esc_html_e( 'Set up advanced pricing rates', 'jet-booking' ); ?>
		</div>

		<div slot="content">
			<div class="jet-abaf-rates-list">
				<div class="jet-abaf-rates-list__item default">
					<div class="jet-abaf-rates-list__col col-title">
						<?php esc_html_e( 'From', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" value="1" disabled>&nbsp;&nbsp;
						<?php esc_html_e( 'days/nights', 'jet-booking' ); ?>
					</div>

					<div class="jet-abaf-rates-list__col col-price">
						<?php esc_html_e( 'Price:', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" min="0" :value="popUpData.price" disabled>&nbsp;&nbsp;
						<?php esc_html_e( 'per day/night', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-rates-list__col col-delete">&nbsp;</div>
				</div>

				<div
					class="jet-abaf-rates-list__item"
					v-for="( rate, index ) in popUpData.items"
					:key="'rate-' + index">
					<div class="jet-abaf-rates-list__col col-title">
						<?php esc_html_e( 'From', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" min="2" step="1" v-model="rate.duration">&nbsp;&nbsp;
						<?php esc_html_e( 'days/nights', 'jet-booking' ); ?>
					</div>

					<div class="jet-abaf-rates-list__col col-price">
						<?php esc_html_e( 'Price:', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" min="0" step="0.1" v-model="rate.value">&nbsp;&nbsp;
						<?php esc_html_e( 'per day/night', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-rates-list__col col-delete">
						<span @click="deleteRate( index )" class="dashicons dashicons-trash"></span>
					</div>
				</div>
			</div>

			<a href="#" class="jet-abaf-add-rate" @click.prevent="newRate">
				<?php esc_html_e( '+ Add new rate', 'jet-booking' ); ?>
			</a>

			<div class="jet-abaf-popup-actions">
				<button class="button button-primary" type="button" aria-expanded="true" @click="saveMeta">
					<span v-if="!saving">
						<?php esc_html_e( 'Save', 'jet-booking' ); ?>
					</span>
					<span v-else>
						<?php esc_html_e( 'Saving...', 'jet-booking' ); ?>
					</span>
				</button>

				<button class="button-link" type="button" aria-expanded="true" @click="hidePopUp('rates')">
					<?php esc_html_e( 'Cancel', 'jet-booking' ); ?>
				</button>
			</div>
		</div>
	</cx-vui-popup>

	<cx-vui-popup
		v-model="popUpActive_weekend"
		body-width="400px"
		class="jet-apb-popup"
		@on-ok="saveMeta"
		@on-cancel="hidePopUp('weekend')"
		ok-label="<?php esc_html_e( 'Save', 'jet-booking' ) ?>"
		cancel-label="<?php esc_html_e( 'Close', 'jet-booking' ) ?>"
	>
		<div class="cx-vui-subtitle" slot="title">
			<?php esc_html_e( 'Set up weekend pricing', 'jet-appointments-booking' ); ?>
		</div>

		<template slot="content">
			<div class="jet-abaf-weekend-list__item" v-for="( item, index ) in popUpData.items" :key="'price-' + index">
				<div class="jet-abaf-weekend-list__col col-title">
					<strong>{{ weekdays_label[ index ] }}</strong>
				</div>

				<div class="jet-abaf-weekend-list__col col-price">
					<cx-vui-switcher
						v-model="item.active"
						class="jet-abaf-weekend-switcher"
					></cx-vui-switcher>

					<label>
						<?php esc_html_e( 'Price:', 'jet-booking' ); ?>
					</label>

					<cx-vui-input
						type="number"
						:min="0"
						:step="0.1"
						v-model="item.price"
					></cx-vui-input>&nbsp;&nbsp;

					<?php esc_html_e( 'per day/night', 'jet-booking' ); ?>
				</div>
			</div>

			<div class="cx-vui-component__meta" style="align-items: flex-end; margin-top: 10px;">
				<a class="jet-abaf-help-link" href="https://crocoblock.com/knowledge-base/jetbooking/jetbooking-how-to-configure-the-weekend-pricing/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help" target="_blank">
					<span class="dashicons dashicons-editor-help"></span>
					<?php esc_html_e( 'What is this and how it works?', 'jet-booking' ); ?>
				</a>
			</div>
		</template>
	</cx-vui-popup>
</div>
