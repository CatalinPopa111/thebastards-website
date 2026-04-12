<?php
/**
 * JetEngine compatibility package manager.
 *
 * @package JET_ABAF\Compatibility\Packages
 */

namespace JET_ABAF\Compatibility\Packages;

use \Jet_Engine\Modules\Dynamic_Visibility\Conditions\Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Jet_Engine {

	/**
	 * A reference to an instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	public function __construct() {

		if ( ! class_exists( '\Jet_Engine_Listings' ) ) {
			require_once jet_engine()->plugin_path( 'includes/components/listings/manager.php' );

			jet_engine()->listings = new \Jet_Engine_Listings();
		}

		Jet_Engine\Forms\Manager::instance();
		Jet_Engine\Listings\Manager::instance();
		Jet_Engine\Macros\Manager::instance();
		Jet_Engine\Query_Builder\Manager::instance();

		// Register dynamic visibility conditions group.
		add_filter( 'jet-engine/modules/dynamic-visibility/conditions/groups', [ $this, 'register_conditions_group' ] );
		// Register dynamic visibility conditions.
		add_action( 'jet-engine/modules/dynamic-visibility/conditions/register', [ $this, 'register_conditions' ] );

	}

	/**
	 * Register condition group.
	 *
	 * Register and returns specific JetBooking dynamic visibility conditions group.
	 *
	 * @since  3.3.0
	 *
	 * @param array $groups Predefined groups list.
	 *
	 * @return mixed
	 */
	public function register_conditions_group( $groups ) {

		$groups['jet_booking'] = [
			'label'   => __( 'JetBooking', 'jet-booking' ),
			'options' => [],
		];

		return $groups;

	}

	/**
	 * Register conditions.
	 *
	 * Register specific JetBooking dynamic visibility conditions.
	 *
	 * @since  3.3.0
	 * @since  3.8.0 Registered apartment units related conditions.
	 *
	 * @param Manager $conditions_manager Dynamic visibility condition manager instance.
	 */
	public function register_conditions( $conditions_manager ) {

		require_once JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/conditions/has-units.php';
		require_once JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/conditions/has-no-units.php';
		require_once JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/conditions/is-cancellable.php';
		require_once JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/conditions/is-not-cancellable.php';
		require_once JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/conditions/is-updatable.php';
		require_once JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/conditions/is-not-updatable.php';

		$conditions_manager->register_condition( new Conditions\Has_Units() );
		$conditions_manager->register_condition( new Conditions\Has_No_Units() );
		$conditions_manager->register_condition( new Conditions\Is_Cancellable() );
		$conditions_manager->register_condition( new Conditions\Is_Not_Cancellable() );
		$conditions_manager->register_condition( new Conditions\Is_Updatable() );
		$conditions_manager->register_condition( new Conditions\Is_Not_Updatable() );

	}

	/**
	 * Package path.
	 *
	 * Return path inside package.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param string $path Relative package path.
	 *
	 * @return string
	 */
	public function package_path( $path = '' ) {
		return JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/' . $path;
	}

	/**
	 * Package URL.
	 *
	 * Return URL inside package.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param string $url Relative package URL.
	 *
	 * @return string
	 */
	public function package_url( $url = '' ) {
		return JET_ABAF_URL . 'includes/compatibility/packages/jet-engine/' . $url;
	}

	/**
	 * Returns the instance.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return object
	 */
	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

}

new Jet_Engine();
