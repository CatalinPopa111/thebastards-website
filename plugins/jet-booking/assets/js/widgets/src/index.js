import { Disabled } from '@wordpress/components';
import { subscribe, select } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { createElement, createRoot } from '@wordpress/element';

import SettingsConfiguration from './widgets/settings-configuration';
import SettingsSchedule from './widgets/settings-schedule';

import './style.scss';

const initWidget = ( element, selector, isEditorContext = false ) => {
	const widgets = document.querySelectorAll( selector );

	if ( ! widgets.length ) return;

	widgets.forEach( ( widget ) => {
		const root = createRoot( widget );
		const data = widget?.dataset?.settings ? JSON.parse( widget.dataset.settings ) : {};

		if ( isEditorContext ) {
			root.render( createElement( Disabled, { isDisabled: true }, createElement( element, { ...data, isEditorContext: true } ) ) );
		} else {
			root.render( createElement( element, { ...data } ) );
		}
	} );
};

const init = ( isEditorContext = false ) => {
	initWidget( SettingsConfiguration, '.jet-abaf-settings-configuration-root', isEditorContext );
	initWidget( SettingsSchedule, '.jet-abaf-settings-schedule-root', isEditorContext );
};

// Handle Blocks editor preview.
const initBlockEditor = () => {
	subscribe( () => {
		if ( select( 'core/block-editor' ).getBlocks().length ) init( true );
	} );
};

// Handle Elementor editor preview.
window.addEventListener( 'elementor/frontend/init', function () {
	if ( 'undefined' === typeof elementor ) return;

	window.elementorFrontend.hooks.addAction( 'frontend/element_ready/settings-configuration.default', () => {
		initWidget( SettingsConfiguration, '.jet-abaf-settings-configuration-root', true )
	} );

	window.elementorFrontend.hooks.addAction( 'frontend/element_ready/settings-schedule.default', () => {
		initWidget( SettingsSchedule, '.jet-abaf-settings-schedule-root', true )
	} );
} );

// Handle Bricks editor preview.
window.jetBookingWidgetsInit = () => init( true );

// Render the widget when DOM is ready.
domReady( () => document.body.classList.contains( 'block-editor-page' ) ? initBlockEditor() : init() );
