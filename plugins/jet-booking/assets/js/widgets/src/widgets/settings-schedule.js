import {
	__experimentalGrid as Grid,
	__experimentalItem as Item,
	__experimentalText as Text,
	BaseControl,
	Button,
	Card,
	CardHeader,
	CardFooter,
	Panel,
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Icon, check, close, edit, plus, trash } from '@wordpress/icons';

import { Notices } from '../components/notices';
import { useSettings } from '../hooks';

const SettingsSchedule = ( props ) => {
	const { saving, settings, saveSettings, updateSetting } = useSettings();
	const i18n = window.jetBookingWidgets?.i18n || {};

	const [ isOpen, setIsOpen ] = useState( props?.isEditorContext || false );
	const [ date, setDate ] = useState( {
		start: null,
		startTimeStamp: null,
		end: null,
		endTimeStamp: null,
		name: null,
		type: null,
		editIndex: null,
	} );

	const getTimeSettings = ( setting, fallback = 3600 ) => {
		const time = settings?.[ setting ] ?? fallback;

		let hours = Math.floor( time / 3600 ),
			minutes = Math.floor(( time % 3600 ) / 60);

		hours = String( hours ).padStart( 2, '0' );
		minutes = String( minutes ).padStart( 2, '0' );

		return `${ hours }:${ minutes }`;
	};

	const handleTimeChange = ( type, value, fallback = '01:00' ) => {
		if ( ! value ) {
			value = fallback;
		}

		let [ hours, minutes ] = value.split( ':' ).map( Number );

		minutes = Math.round( minutes / 15 ) * 15;

		if ( 60 === minutes ) {
			hours = ( hours + 1 ) % 24;
			minutes = 0;
		}

		value = hours * 3600 + minutes * 60;

		updateSetting( type, value );
	};

	const timestampToDate = ( timestamp ) => {
		if ( ! timestamp ) return;

		const selectedDate = new Date( timestamp * 1000 );
		const year = selectedDate.getFullYear();
		const month = String( selectedDate.getMonth() + 1 ).padStart( 2, '0' );
		const day = String( selectedDate.getDate() ).padStart( 2, '0' );

		return `${ year }-${ month }-${ day }`;
	};

	const handleDateChange = ( type, value ) => {
		if ( ! value ) return;

		const [ year, month, day ] = value.split( '-' );

		setDate( ( prev ) => ( {
			...prev,
			[ type ]: `${ day }-${ month }-${ year }`,
			[ `${ type }TimeStamp` ]: Math.floor( new Date( value ).getTime() / 1000 )
		} ) );
	};

	const handleDayOffSave = () => {
		if ( ! date.start ) {
			window.alert( i18n.require_date_error );
			return;
		}

		const newDate = { ...date };

		if ( newDate.end && newDate.startTimeStamp > newDate.endTimeStamp ) {
			window.alert( i18n.dates_order_error );
			return;
		}

		if ( ! newDate.type ) newDate.type = 'days_off';

		const dates = settings?.days_off || [];
		const index = null !== newDate.editIndex ? newDate.editIndex : dates.length;

		dates.splice( index, 1, newDate );

		updateSetting( 'days_off', dates );
		handleDayOffCancel();
	};

	const removeDayOff = ( index ) => {
		if ( ! window.confirm( i18n.removal_confirm_message ) ) return;

		updateSetting( 'days_off', settings.days_off.filter( ( _, i ) => i !== index ) );
	};

	const handleDayOffCancel = () => {
		for ( const key in date ) {
			if ( date.hasOwnProperty( key ) ) {
				setDate( ( prev ) => ( { ...prev, [ key ]: null } ) );
			}
		}

		setIsOpen( false );
	};

	return ( <>
		{ props?.show_timepicker_controls && ( <>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ i18n.timepicker_label }
				help={ i18n.timepicker_help }
				checked={ settings.timepicker || false }
				onChange={ ( value ) => updateSetting( 'timepicker', value ) }
			/>

			{ settings.timepicker && (
				<>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ i18n.timepicker_restrictions_label }
						help={ i18n.timepicker_restrictions_help }
						checked={ settings.timepicker_restrictions || false }
						onChange={ ( value ) => updateSetting( 'timepicker_restrictions', value ) }
					/>

					{ settings.timepicker_restrictions && (
						<>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								type="time"
								label={ i18n.timepicker_buffer_label }
								help={ i18n.timepicker_buffer_help }
								value={ getTimeSettings( 'timepicker_buffer', 7200 ) }
								onChange={ ( value ) => handleTimeChange( 'timepicker_buffer', value, '02:00' ) }
							/>
						</>
					) }

					<BaseControl
						__nextHasNoMarginBottom
						className="components-complex-control"
						label={ i18n.timepicker_range_label }
						help={ i18n.timepicker_range_help }
					>
						<div className="components-paired-control">
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								type="time"
								label={ i18n.timepicker_range_start_label }
								value={ getTimeSettings( 'timepicker_range_start', 32400 ) }
								onChange={ ( value ) => handleTimeChange( 'timepicker_range_start', value, '09:00' ) }
							/>

							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								type="time"
								label={ i18n.timepicker_range_end_label }
								value={ getTimeSettings( 'timepicker_range_end', 64800 ) }
								onChange={ ( value ) => handleTimeChange( 'timepicker_range_end', value, '18:00' ) }
							/>
						</div>
					</BaseControl>

					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="time"
						label={ i18n.timepicker_interval_label }
						help={ i18n.timepicker_interval_help }
						value={ getTimeSettings( 'timepicker_interval', 3600 ) }
						onChange={ ( value ) => handleTimeChange( 'timepicker_interval', value, '01:00' ) }
					/>
				</>
			) }
		</> ) }

		{ props?.show_rules_controls && ( <>
			<BaseControl
				__nextHasNoMarginBottom
				label={ i18n.booking_rules_label }
				help={ i18n.booking_rules_help }
			/>

			<Grid className="components-base-control components-booking-rules-control" columns={ 1 } gap={ 0 }>
				<Grid columns={ 4 } gap={ 0 }>
					<Item> { i18n.weekday_day_column_heading } </Item>
					<Item> { i18n.weekday_disable_column_heading } </Item>
					<Item> { i18n.weekday_check_in_column_heading } </Item>
					<Item> { i18n.weekday_check_out_column_heading } </Item>
				</Grid>

				{ [
					{ key: 'weekday_1', label: i18n.weekday_1_label },
					{ key: 'weekday_2', label: i18n.weekday_2_label },
					{ key: 'weekday_3', label: i18n.weekday_3_label },
					{ key: 'weekday_4', label: i18n.weekday_4_label },
					{ key: 'weekday_5', label: i18n.weekday_5_label },
					{ key: 'weekend_1', label: i18n.weekend_1_label },
					{ key: 'weekend_2', label: i18n.weekend_2_label },
				].map( ( day ) => (
					<Grid columns={ 4 } gap={ 0 } key={ day.key }>
						<Item> { day.label } </Item>

						<Item>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={ settings[ `disable_${ day.key }` ] || false }
								onChange={ ( value ) => updateSetting( `disable_${ day.key }`, value ) }
							/>
						</Item>

						<Item>
							{ ! settings[ `disable_${ day.key }` ] ? (
								<ToggleControl
									__nextHasNoMarginBottom
									checked={ settings[ `check_in_${ day.key }` ] || false }
									onChange={ ( value ) => updateSetting( `check_in_${ day.key }`, value ) }
								/>
							) : <Icon icon={ close } size={ 16 } /> }
						</Item>

						<Item>
							{ ! settings[ `disable_${ day.key }` ] ? (
								<ToggleControl
									__nextHasNoMarginBottom
									checked={ settings[ `check_out_${ day.key }` ] || false }
									onChange={ ( value ) => updateSetting( `check_out_${ day.key }`, value ) }
								/>
							) : <Icon icon={ close } size={ 16 } /> }
						</Item>
					</Grid>
				) ) }
			</Grid>
		</> ) }

		{ props?.show_days_off_controls && ( <>
			<Panel className="components-days-off-control">
				<PanelBody title={ i18n.days_off_label } >
					<BaseControl
						__nextHasNoMarginBottom
						help={ i18n.days_off_help }
					/>

					<Panel className="components-days-off-control__add-days">
						<PanelBody
							title={ i18n.days_off_add_button_label }
							icon={ plus }
							opened={ isOpen }
							onToggle={ () => setIsOpen( ( prev ) => ! prev ) }
						>
							<PanelRow>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ i18n.days_off_range_name_field_label }
									help={ i18n.days_off_range_name_field_help }
									value={ date.name }
									onChange={ ( value ) => setDate( ( prev ) => ( { ...prev, name: value } ) ) }
								/>

								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									type="date"
									label={ i18n.days_off_start_date_field_label }
									help={ i18n.days_off_start_date_field_help }
									value={ timestampToDate( date.startTimeStamp ) }
									onChange={ ( value ) => { handleDateChange( 'start', value ) } }
								/>

								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									type="date"
									label={ i18n.days_off_end_date_field_label }
									help={ i18n.days_off_end_date_field_help }
									value={ timestampToDate( date.endTimeStamp ) }
									onChange={ ( value ) => { handleDateChange( 'end', value ) } }
								/>
							</PanelRow>

							<PanelRow className="components-days-off-control__add-days-actions">
								<Button
									__next40pxDefaultSize
									variant="primary"
									onClick={ handleDayOffSave }
								>
									{ i18n.days_off_save_button_label }
								</Button>

								<Button
									__next40pxDefaultSize
									variant="secondary"
									onClick={ handleDayOffCancel }
								>
									{ i18n.days_off_cancel_button_label }
								</Button>
							</PanelRow>
						</PanelBody>
					</Panel>

					{ !! settings?.days_off?.length && (
						<Grid className="components-days-off-control__days-list" columns={ 3 } gap={ 2 }>
							{ settings.days_off.map( ( dayOff, index ) => (
								<Card key={ dayOff.startTimeStamp }>
									{ index !== date?.editIndex ? (
										<>
											<CardHeader>
												<Text> { dayOff.name } </Text>

												<Grid className="components-card__actions" columns={ 2 } gap={ 1 }>
													<Button
														__next40pxDefaultSize
														icon={ edit }
														variant="tertiary"
														size="compact"
														onClick={ () => setDate( ( prev ) => ( { ...prev, ...dayOff, editIndex: index } ) ) }
													/>

													<Button
														__next40pxDefaultSize
														icon={ trash }
														variant="tertiary"
														size="compact"
														isDestructive
														onClick={ () => removeDayOff( index ) }
													/>
												</Grid>
											</CardHeader>

											<CardFooter>
												<Text>
													{ dayOff.start } { dayOff.end && dayOff.endTimeStamp !== dayOff.startTimeStamp && ` - ${ dayOff.end }` }
												</Text>
											</CardFooter>
										</>
									) : (
										<>
											<CardHeader>
												<TextControl
													__next40pxDefaultSize
													__nextHasNoMarginBottom
													value={ date.name }
													onChange={ ( value ) => setDate( ( prev ) => ( { ...prev, name: value } ) ) }
												/>

												<Grid className="components-card__actions" columns={ 2 } gap={ 1 }>
													<Button
														__next40pxDefaultSize
														icon={ check }
														variant="tertiary"
														size="compact"
														onClick={ handleDayOffSave }
													/>

													<Button
														__next40pxDefaultSize
														icon={ close }
														variant="tertiary"
														size="compact"
														isDestructive
														onClick={ handleDayOffCancel }
													/>
												</Grid>
											</CardHeader>

											<CardFooter>
												<TextControl
													__next40pxDefaultSize
													__nextHasNoMarginBottom
													type="date"
													value={ timestampToDate( date.startTimeStamp ) }
													onChange={ ( value ) => { handleDateChange( 'start', value ) } }
												/>

												<TextControl
													__next40pxDefaultSize
													__nextHasNoMarginBottom
													type="date"
													value={ timestampToDate( date.endTimeStamp ) }
													onChange={ ( value ) => { handleDateChange( 'end', value ) } }
												/>
											</CardFooter>
										</>
									) }
								</Card>
							) ) }
						</Grid>
					) }
				</PanelBody>
			</Panel>
		</> ) }

		{ ( props?.show_timepicker_controls || props?.show_rules_controls || props?.show_days_off_controls ) && <>
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

export default SettingsSchedule;
