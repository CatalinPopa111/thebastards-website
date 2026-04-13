<?php
/**
 * Plugin Name: Bastard Admin Schedule
 * Description: Pagină admin pentru calendar rezidențiat și lista evenimente.
 * Version: 1.0.0
 * Author: The Bastards Agency
 */

defined( 'ABSPATH' ) || exit;

define( 'BAS_PATH', plugin_dir_path( __FILE__ ) );
define( 'BAS_URL',  plugin_dir_url( __FILE__ ) );

require_once BAS_PATH . 'includes/class-conflict-detector.php';
require_once BAS_PATH . 'includes/class-ajax-handler.php';
require_once BAS_PATH . 'includes/class-admin-page.php';

add_action( 'admin_menu', function () {
	add_menu_page(
		'Schedule Rezidențiat',
		'Schedule',
		'manage_options',
		'bastard-schedule',
		[ 'BAS_Admin_Page', 'render' ],
		'dashicons-calendar-alt',
		30
	);
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'toplevel_page_bastard-schedule' ) {
		return;
	}

	wp_enqueue_style(
		'bas-google-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'bas-admin',
		BAS_URL . 'assets/admin-schedule.css',
		[ 'bas-google-fonts' ],
		'1.0.0'
	);

	wp_enqueue_script(
		'bas-alpine',
		BAS_URL . 'assets/alpine.min.js',
		[],
		'3.14.1',
		true
	);

	wp_enqueue_script(
		'bas-admin',
		BAS_URL . 'assets/admin-schedule.js',
		[ 'bas-alpine' ],
		'1.0.0',
		true
	);

	wp_localize_script( 'bas-admin', 'basData', BAS_Admin_Page::get_js_data() );
} );

// Înregistrare acțiuni AJAX
BAS_Ajax_Handler::register();
