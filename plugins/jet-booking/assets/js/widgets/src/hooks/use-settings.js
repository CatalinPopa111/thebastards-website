import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';

const useSettings = () => {
	const [ settings, setSettings ] = useState( () => window.jetBookingWidgets?.settings ?? {} );
	const [ saving, setSaving ] = useState( false );
	const { createErrorNotice, createSuccessNotice, removeNotice } = useDispatch( noticesStore );

	const updateSetting = ( key, value ) => {
		setSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const saveSettings = () => {
		setSaving( true );

		const formData = new FormData();

		formData.append( 'action', 'jet_abaf_save_settings' );
		formData.append( 'nonce', window.jetBookingWidgets?.nonce || '' );
		formData.append( 'settings', JSON.stringify( settings ) );

		apiFetch( {
			url: window.jetBookingWidgets?.ajax_url || '',
			method: 'POST',
			body: formData,
		} ).then( ( result ) => {
			const noticeMethod = result.success ? createSuccessNotice : createErrorNotice;

			noticeMethod( result.data.message, { isDismissible: false } )
				.then( ( notice ) => setTimeout( () => removeNotice( notice.notice.id ), 7000 ) );
		} ).catch( ( error ) => {
			createErrorNotice( error.message, { isDismissible: false } )
				.then( ( notice ) => setTimeout( () => removeNotice( notice.notice.id ), 7000 ) );
		} ).finally( () => setSaving( false ) );
	};

	return {
		saving,
		settings,
		saveSettings,
		updateSetting,
	};
};

export default useSettings;
