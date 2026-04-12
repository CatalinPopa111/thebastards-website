import { BaseControl, Button, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import { Notices } from '../components/notices';
import { useSettings } from '../hooks';

const SettingsConfiguration = ( props ) => {
	const { saving, settings, saveSettings, updateSetting } = useSettings();
	const i18n = window.jetBookingWidgets?.i18n || {};

	return ( <>
		{ props?.show_period_controls && ( <>
			<div className="components-base-control components-select-control">
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ i18n.booking_period_label }
					labelPosition="side"
					help={ i18n.booking_period_help }
					options={ [
						{
							label: i18n.booking_period_per_night_option_label,
							value: 'per_nights'
						},
						{
							label: i18n.booking_period_per_day_option_label,
							value: 'per_days'
						}
					] }
					value={ settings.booking_period || 'per_nights' }
					onChange={ ( value ) => updateSetting( 'booking_period', value ) }
				/>
			</div>

			{ 'per_nights' === settings.booking_period && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ i18n.allow_checkout_only_label }
					help={ i18n.allow_checkout_only_help }
					checked={ settings.allow_checkout_only || false }
					onChange={ ( value ) => updateSetting( 'allow_checkout_only', value ) }
				/>
			) }

			{ 'per_days' === settings.booking_period && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ i18n.one_day_bookings_label }
					help={ i18n.one_day_bookings_help }
					checked={ settings.one_day_bookings || false }
					onChange={ ( value ) => updateSetting( 'one_day_bookings', value ) }
				/>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ i18n.weekly_bookings_label }
				help={ i18n.weekly_bookings_help }
				checked={ settings.weekly_bookings || false }
				onChange={ ( value ) => updateSetting( 'weekly_bookings', value ) }
			/>

			{ settings.weekly_bookings && (
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					type='number'
					label={ i18n.week_offset_label }
					help={ i18n.week_offset_help }
					value={ settings.week_offset || 0 }
					onChange={ ( value ) => updateSetting( 'week_offset', value ) }
				/>
			) }
		</> ) }

		{ props?.show_range_controls && ( <>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				type='number'
				label={ i18n.start_day_offset_label }
				help={ i18n.start_day_offset_help }
				value={ settings.start_day_offset || 0 }
				onChange={ ( value ) => updateSetting( 'start_day_offset', value ) }
			/>

			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				type='number'
				label={ i18n.min_days_label }
				help={ i18n.min_days_help }
				value={ settings.min_days || 0 }
				onChange={ ( value ) => updateSetting( 'min_days', value ) }
			/>

			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				type='number'
				label={ i18n.max_days_label }
				help={ i18n.max_days_help }
				value={ settings.max_days || 0 }
				onChange={ ( value ) => updateSetting( 'max_days', value ) }
			/>

			<BaseControl
				__nextHasNoMarginBottom
				className="components-complex-control"
				label={ i18n.end_date_label }
				help={ i18n.end_date_help }
			>
				<div className="components-complex-control__children">
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						options={ [
							{
								label: i18n.end_date_any_option_label,
								value: 'none'
							},
							{
								label: i18n.end_date_range_option_label,
								value: 'range'
							}
						] }
						value={ settings.end_date_type || 'none' }
						onChange={ ( value ) => updateSetting( 'end_date_type', value ) }
					/>

					{ 'range' === settings.end_date_type && (
						<div className="components-paired-control">
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								type="number"
								value={ settings.end_date_range_number || 1 }
								onChange={ ( value ) => updateSetting( 'end_date_range_number', value ) }
							/>

							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								options={ [
									{
										label: i18n.end_date_range_unit_day_option_label,
										value: 'day'
									},
									{
										label: i18n.end_date_range_unit_month_option_label,
										value: 'month'
									},
									{
										label: i18n.end_date_range_unit_year_option_label,
										value: 'year',
									}
								] }
								value={ settings.end_date_range_unit || 'year' }
								onChange={ ( value ) => updateSetting( 'end_date_range_unit', value ) }
							/>
						</div>
					) }
				</div>
			</BaseControl>
		</> ) }

		{ props?.show_ui_controls && ( <>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ i18n.clear_button_label }
				help={ i18n.clear_button_help }
				checked={ settings.show_clear_button || false }
				onChange={ ( value ) => updateSetting( 'show_clear_button', value ) }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ i18n.month_select_label }
				help={ i18n.month_select_help }
				checked={ settings.month_select || false }
				onChange={ ( value ) => updateSetting( 'month_select', value ) }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ i18n.year_select_label }
				help={ i18n.year_select_help }
				checked={ settings.year_select || false }
				onChange={ ( value ) => updateSetting( 'year_select', value ) }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ i18n.calendar_price_label }
				help={ i18n.calendar_price_help }
				checked={ settings.show_calendar_price || false }
				onChange={ ( value ) => updateSetting( 'show_calendar_price', value ) }
			/>
		</> ) }

		{ ( props?.show_period_controls || props?.show_range_controls || props?.show_ui_controls ) && <>
			<div className="components-actions-control">
				<Button
					__next40pxDefaultSize
					variant="primary"
					onClick={ saveSettings }
					disabled={ saving }
				>
					{ i18n.save_button_label }
				</Button>
			</div>
		</> }

		<Notices />
	</> );
};

export default SettingsConfiguration;
