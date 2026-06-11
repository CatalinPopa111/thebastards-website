<?php
/**
 * Plugin Name: Bastard Admin Schedule
 * Description: Pagină admin pentru calendar rezidențiat și lista evenimente.
 * Version: 1.6.1
 * Author: The Bastards Agency
 */

defined( 'ABSPATH' ) || exit;

define( 'BAS_PATH', plugin_dir_path( __FILE__ ) );

// Versiune asset-uri — sursă unică de adevăr pentru cache-busting CSS/JS.
// Trebuie urcată ori de câte ori se modifică admin-schedule.js / .css,
// altfel browserele (în special mobil) servesc fișierele vechi din cache.
define( 'BAS_VERSION', '1.6.1' );

// ID-ul relației JetEngine: artist (parent) → evenimente (child)
// Stocat în wp4u_jet_rel_default.rel_id — verificat în DB, mereu '8' pentru acest site.
define( 'BAS_JET_REL_ARTIST_EVENTS', '8' );
define( 'BAS_URL',  plugin_dir_url( __FILE__ ) );

require_once BAS_PATH . 'includes/class-conflict-detector.php';
require_once BAS_PATH . 'includes/class-email-notifier.php';
require_once BAS_PATH . 'includes/class-ajax-handler.php';
require_once BAS_PATH . 'includes/class-admin-page.php';
require_once BAS_PATH . 'includes/class-vacation-request.php';
require_once BAS_PATH . 'includes/class-booking-sync.php';
require_once BAS_PATH . 'includes/class-artist-events.php';

add_action( 'admin_menu', function () {
	add_menu_page(
		'Program Artiști',
		'Program Artiști',
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
		BAS_VERSION
	);

	// bas-admin se încarcă primul (înregistrează ascultătorul alpine:init)
	// bas-alpine depinde de bas-admin, deci se încarcă după
	wp_enqueue_script(
		'bas-admin',
		BAS_URL . 'assets/admin-schedule.js',
		[],
		BAS_VERSION,
		true // footer
	);

	wp_enqueue_script(
		'bas-alpine',
		BAS_URL . 'assets/alpine.min.js',
		[ 'bas-admin' ], // se încarcă după bas-admin
		'3.14.1',
		true // footer
	);

	// Injectăm basData înainte de bas-admin.js
	wp_add_inline_script(
		'bas-admin',
		'window.basData = ' . wp_json_encode( BAS_Admin_Page::get_js_data() ) . ';',
		'before'
	);
} );

// Înregistrare acțiuni AJAX
BAS_Ajax_Handler::register();

// Înregistrare flux aprobare vacanță
BAS_Vacation_Request::register();

// Sincronizare universală booking ↔ status-eveniment
BAS_Booking_Sync::register();

// Shortcode și AJAX pentru lista de evenimente a artistului
BAS_Artist_Events::register();

// ── Shortcode frontend [bas_schedule] ──────────────────────────

// Înregistrează scripturile devreme (wp_enqueue_scripts) dacă adminul e logat
add_action( 'wp_enqueue_scripts', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_register_style(
		'bas-google-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500&display=swap',
		[],
		null
	);
	wp_register_style( 'bas-admin', BAS_URL . 'assets/admin-schedule.css', [ 'bas-google-fonts' ], BAS_VERSION );
	// bas-admin se înregistrează fără dependință de Alpine (trebuie să se încarce primul)
	wp_register_script( 'bas-admin', BAS_URL . 'assets/admin-schedule.js', [], BAS_VERSION, true );
	// bas-alpine depinde de bas-admin → se încarcă după
	wp_register_script( 'bas-alpine', BAS_URL . 'assets/alpine.min.js', [ 'bas-admin' ], '3.14.1', true );
} );

add_shortcode( 'bas_schedule', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}

	wp_enqueue_style( 'bas-google-fonts' );
	wp_enqueue_style( 'bas-admin' );
	wp_enqueue_script( 'bas-admin' );
	wp_enqueue_script( 'bas-alpine' );

	// Injectăm basData înainte de bas-admin.js (o singură dată, WP deduplicată automat)
	wp_add_inline_script(
		'bas-admin',
		'window.basData = ' . wp_json_encode( BAS_Admin_Page::get_js_data() ) . ';',
		'before'
	);

	ob_start();
	BAS_Admin_Page::render( false, true ); // is_frontend = true
	return ob_get_clean();
} );
