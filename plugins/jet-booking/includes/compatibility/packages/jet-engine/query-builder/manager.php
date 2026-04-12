<?php
/**
 * JetEngine compatibility package Query Builder manager.
 *
 * @package JET_ABAF\Compatibility\Packages\Jet_Engine\Query_Builder
 */

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Query_Builder;

use \Jet_Engine\Query_Builder\Query_Editor;
use \JET_ABAF\Compatibility\Packages\Jet_Engine;

class Manager {

	/**
	 * A reference to an instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Query builder component slug.
	 *
	 * @var string
	 */
	public $slug = 'jet-booking-query';

	public function __construct() {

		add_action( 'jet-engine/query-builder/query-editor/register', [ $this, 'register_editor_component' ] );
		add_action( 'jet-engine/query-builder/queries/register', [ $this, 'register_query' ] );

		add_filter( 'jet-engine/query-builder/types/sql-query/cast-objects', [ $this, 'add_booking_to_cast' ] );

		/**
		 * Fix `cx-vui-f-select` component with `jet-query-dynamic-args`. This is temporary solution until vue-ui module fix this.
		 */
		add_action('admin_head', function () {

			$screen = get_current_screen();

			if ( 'jetengine_page_jet-engine-query' === $screen->id ) {
				echo '<style>
						.cx-vui-component.cx-vui-component--has-macros .cx-vui-f-select {
							flex: 0 0 calc(100% - 32px);
							width: 100%;
						}
				</style>';
			}

		} );

	}

	/**
	 * Register editor components.
	 *
	 * Register editor component for the query builder
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param Query_Editor $manager Query editor instance.
	 *
	 * @return void
	 */
	public function register_editor_component( $manager ) {
		require_once Jet_Engine::instance()->package_path( 'query-builder/editor.php' );
		$manager->register_type( new Editor() );
	}

	/**
	 * Register query class
	 *
	 * @param  $manager
	 *
	 * @return void
	 */
	public function register_query( $manager ) {
		require_once Jet_Engine::instance()->package_path( 'query-builder/query.php' );
		$manager::register_query( $this->slug, __NAMESPACE__ . '\Query' );
	}

	/**
	 * Add booking to cast.
	 *
	 * Add booking object to cast objects.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param array $objects List of cast objects.
	 *
	 * @return array
	 */
	public function add_booking_to_cast( $objects ) {

		$objects['\JET_ABAF\Resources\Booking'] = __( 'Booking', 'jet-booking' );

		return $objects;

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