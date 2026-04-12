<?php
/**
 * JetEngine compatibility package Query Builder Editor class.
 *
 * @package JET_ABAF\Compatibility\Packages\Jet_Engine\Query_Builder
 */

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Query_Builder;

use \Jet_Engine\Query_Builder\Query_Editor\Base_Query;
use \JET_ABAF\Compatibility\Packages\Jet_Engine;

class Editor extends Base_Query {

	/**
	 * Get ID.
	 *
	 * Returns query type ID.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function get_id() {
		return Manager::instance()->slug;
	}

	/**
	 * Get name.
	 *
	 * Returns name of the query type.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return string|null
	 */
	public function get_name() {
		return __( 'JetBooking Query', 'jet-booking' );
	}

	/**
	 * Editor component_name.
	 *
	 * Returns Vue component name of the Query editor for the current type.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return string
	 */
	public function editor_component_name() {
		return 'jet-booking-query';
	}

	/**
	 * Editor component template.
	 *
	 * Returns Vue component template of the Query editor for the current type.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return false|string
	 */
	public function editor_component_template() {

		ob_start();
		include Jet_Engine::instance()->package_path( 'templates/admin/query-editor.php' );

		return ob_get_clean();

	}

	/**
	 * Editor component data.
	 *
	 * Returns Vue component data of the Query editor for the current type.
	 *
	 * @since  3.1.0
	 * @since  4.0.0 Added `vendors` data.
	 * @access public
	 *
	 * @return mixed|void
	 */
	public function editor_component_data() {

		$statuses           = jet_abaf()->statuses->get_statuses();
		$columns            = jet_abaf()->db->get_default_fields();
		$additional_columns = jet_abaf()->settings->get_clean_columns();

		return apply_filters( 'jet-engine/query-builder/types/jet-booking-query/data', [
			'statuses'           => \Jet_Engine_Tools::prepare_list_for_js( $statuses, ARRAY_A ),
			'vendors'            => \Jet_Engine_Tools::prepare_list_for_js( jet_abaf()->vendors->get_vendors_list(), ARRAY_A ),
			'booking_instances'  => \Jet_Engine_Tools::prepare_list_for_js( jet_abaf()->tools->get_booking_posts_list(), ARRAY_A ),
			'columns'            => \Jet_Engine_Tools::prepare_list_for_js( $columns ),
			'additional_columns' => \Jet_Engine_Tools::prepare_list_for_js( $additional_columns ),
		] );

	}

	/**
	 * Editor component file.
	 *
	 * Returns Vue component template file of the Query editor for the current type.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return string
	 */
	public function editor_component_file() {
		return Jet_Engine::instance()->package_url( 'assets/js/admin/query-editor.js' );
	}

}
