<?php

namespace JET_ABAF;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Class Plugin.
 *
 * The main plugin handler class is responsible for initializing JetBooking. The
 * class registers and all the components required to run the plugin.
 *
 * @property Compatibility\Manager  compatibility
 * @property Components\Manager     components
 * @property Dashboard\Manager      dashboard
 * @property DB\Manager             db
 * @property Assets                 assets
 * @property Export                 export
 * @property Framework\Loader       framework
 * @property Google_Calendar        google_cal
 * @property iCal                   ical
 * @property Macros\Manager         macros
 * @property Multivendors\Manager   vendors
 * @property Rest_API\Manager       rest_api
 * @property Settings               settings
 * @property Set_Up                 setup
 * @property Statuses               statuses
 * @property Stores\Manager         stores
 * @property Tools                  tools
 * @property WC_Integration\Manager wc
 * @property Workflows\Manager      workflows
 *
 * @package JET_ABAF
 */
#[\AllowDynamicProperties]
class Plugin {

	/**
	 * Instance.
	 *
	 * Holds the plugin instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @var Plugin
	 */
	public static $instance = null;

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Framework loader.
	 *
	 * Load framework modules.
	 *
	 * @since 3.2.0
	 */
	public function framework_loader() {

		require JET_ABAF_PATH . 'includes/framework/loader.php';

		$this->framework = new Framework\Loader( [
			JET_ABAF_PATH . 'includes/framework/blocks-style-manager/style-manager.php',
			JET_ABAF_PATH . 'includes/framework/db-updater/cherry-x-db-updater.php',
			JET_ABAF_PATH . 'includes/framework/jet-dashboard/jet-dashboard.php',
			JET_ABAF_PATH . 'includes/framework/macros/base-macros.php',
			JET_ABAF_PATH . 'includes/framework/macros/macros-handler.php',
			JET_ABAF_PATH . 'includes/framework/vue-ui/cherry-x-vue-ui.php',
		] );

	}

	/**
	 * Register autoloader.
	 *
	 * JetBooking autoloader loads all the classes needed to run the plugin.
	 *
	 * @since  2.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function register_autoloader() {
		require JET_ABAF_PATH . 'includes/autoloader.php';

		Autoloader::run();
	}

	/**
	 * Init components.
	 *
	 * Initialize JetBooking components. Register actions, run setting manager,
	 * initialize all the components that run plugin, and if in the admin page,
	 * initialize admin components.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function init_components() {

		require JET_ABAF_PATH . 'includes/booking-functions.php';

		$this->settings      = new Settings();
		$this->statuses      = new Statuses();
		$this->db            = new DB\Manager();
		$this->assets        = new Assets();
		$this->compatibility = new Compatibility\Manager();
		$this->export        = new Export();
		$this->google_cal    = new Google_Calendar();
		$this->macros        = new Macros\Manager();
		$this->vendors       = new Multivendors\Manager();
		$this->ical          = new iCal();
		$this->rest_api      = new Rest_API\Manager();
		$this->setup         = new Set_Up();
		$this->stores        = new Stores\Manager();
		$this->tools         = new Tools();
		$this->wc            = new WC_Integration\Manager();
		$this->workflows     = new Workflows\Manager();
		$this->components    = new Components\Manager();
		$this->dashboard     = new Dashboard\Manager( [
			new Dashboard\Pages\Bookings(),
			new Dashboard\Pages\Settings(),
			new Dashboard\Pages\Calendars(),
			new Dashboard\Pages\Set_Up(),
			new Dashboard\Pages\Vendors(),
		] );

		new Ajax_Handlers();
		new Dashboard\Post_Meta\Price_Meta();
		new Dashboard\Post_Meta\Custom_Schedule_Meta();
		new Dashboard\Post_Meta\Configuration_Meta();
		new Dashboard\Upcoming_Booking_Meta();
		new Dashboard\Order_Meta();
		new Formbuilder_Plugin\Jfb_Plugin();
		new Actions\Manager();

		if ( is_admin() ) {
			new Upgrade();
		}

		Cron\Manager::instance();

	}

	/**
	 * Get template.
	 *
	 * Returns a path to a template file.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @param string $name Template name.
	 *
	 * @return string|bool
	 */
	public function get_template( $name = null ) {

		$template_path = apply_filters( 'jet-abaf/template-path', 'jet-booking/' );
		$template      = locate_template( $template_path . $name );

		if ( ! $template ) {
			$template = JET_ABAF_PATH . 'templates/' . $name;
		}

		return file_exists( $template ) ? $template : false;

	}

	/**
	 * Init the JetDashboard module.
	 *
	 * @return void
	 */
	public function jet_dashboard_init() {
		if ( is_admin() ) {
			$jet_dashboard_module_data = $this->framework->get_included_module_data( 'jet-dashboard.php' );
			$jet_dashboard             = \Jet_Dashboard\Dashboard::get_instance();

			$jet_dashboard->init( [
				'path'           => $jet_dashboard_module_data['path'],
				'url'            => $jet_dashboard_module_data['url'],
				'cx_ui_instance' => [ $this, 'jet_dashboard_ui_instance_init' ],
				'plugin_data'    => [
					'slug'         => 'jet-booking',
					'file'         => JET_ABAF_PLUGIN_BASE,
					'version'      => JET_ABAF_VERSION,
					'plugin_links' => [],
				],
			] );
		}
	}

	/**
	 * JetDashboard UI instance initialization.
	 *
	 * @return \CX_Vue_UI
	 */
	public function jet_dashboard_ui_instance_init() {
		$cx_ui_module_data = $this->framework->get_included_module_data( 'cherry-x-vue-ui.php' );

		return new \CX_Vue_UI( $cx_ui_module_data );
	}

	/**
	 * Activation.
	 *
	 * Ran when the plugin is activated.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	function activation() {
		// Create custom user roles when the plugin is activated.
		$this->vendors->create_roles();
	}

	/**
	 * Deactivation.
	 *
	 * Ran when the plugin is deactivated.
	 *
	 * @since  3.0.0
	 * @since  3.2.0 Added new Remove temporary booking event handling.
	 * @access public
	 *
	 * @return void
	 */
	function deactivation() {

		if ( $this->settings->get( 'ical_synch' ) ) {
			$sync_calendars = Cron\Manager::instance()->get_schedules( 'jet-booking-sync-calendars' );
			$sync_calendars->unschedule_event();
		}

		if ( $this->settings->get( 'remove_temporary_bookings' ) ) {
			$remove_temporary_bookings = Cron\Manager::instance()->get_schedules( 'jet-booking-remove-temporary' );
			$remove_temporary_bookings->unschedule_event();
		}

		if ( $this->settings->get( 'switch_status' ) ) {
			$switch_statuses = Cron\Manager::instance()->get_schedules( 'jet-booking-switch-statuses' );
			$switch_statuses->unschedule_event();
		}

		// Remove custom user roles when the plugin is deactivated.
		$this->vendors->remove_roles();

	}

	private function __construct() {

		add_action( 'after_setup_theme', [ $this, 'framework_loader' ], -20 );

		$this->register_autoloader();

		add_action( 'init', [ $this, 'init_components' ], - 999 );
		add_action( 'init', [ $this, 'jet_dashboard_init' ], - 999 );

		// Register a deactivation hook.
		register_deactivation_hook( JET_ABAF__FILE__, [ $this, 'deactivation' ] );

	}

}

Plugin::instance();
