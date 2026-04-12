<?php
namespace JET_ABAF\Dashboard;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Units_Manager {

	/**
	 * Post-type holder.
	 *
	 * @var array
	 */
	public $post_type = [];

	/**
	 * Base URL holder.
	 *
	 * @var null|string
	 */
	private $base_url = null;

	public function __construct( $post_type ) {

		$this->post_type = $post_type;

		// Registers a meta-box for managing units data in the post-edit screen.
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );

		// Retrieve units for a specified instance.
		add_action( 'wp_ajax_jet_abaf_get_units', [ $this, 'get_units' ] );
		add_action( 'wp_ajax_jet_abaf_insert_units', [ $this, 'insert_units' ] );
		add_action( 'wp_ajax_jet_abaf_update_unit', [ $this, 'update_unit' ] );
		add_action( 'wp_ajax_jet_abaf_delete_unit', [ $this, 'delete_unit' ] );

		// Initializes units manager functionality.
		add_action( 'admin_enqueue_scripts', [ $this, 'init_units_manager' ] );

	}

	/**
	 * Register a meta-box.
	 *
	 * Registers a meta-box for managing units data in the post-edit screen.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'jet-abaf-units',
			__( 'Units manager', 'jet-booking' ),
			[ $this, 'render_meta_box' ],
			$this->post_type
		);
	}

	/**
	 * Render the meta-box content.
	 *
	 * Outputs the HTML structure for the meta-box.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function render_meta_box() {
		echo '<div id="jet_abaf_apartment_units"></div>';
	}

	/**
	 * Retrieve units for a specified instance.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added security check.
	 * @since  3.8.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function get_units() {

		// phpcs:disable WordPress.Security.NonceVerification
		$this->request_security_check( $_REQUEST );

		if ( empty( $_REQUEST['apartment'] ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( [ 'units' => jet_abaf()->db->get_apartment_units( absint( $_REQUEST['apartment'] ) ) ] );
		// phpcs:enable WordPress.Security.NonceVerification

	}

	/**
	 * Inserts new units for a given instance.
	 *
	 * @since 1.0.0
	 * @since 2.6.2 Added security check.
	 * @since  3.8.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function insert_units() {

		$request = file_get_contents( 'php://input' );
		$request = json_decode( $request, true );

		if ( empty( $request ) ) {
			wp_send_json_error();
		}

		$this->request_security_check( $request );

		$apartment = ! empty( $request['apartment'] ) ? absint( $request['apartment'] ) : false;

		if ( ! $apartment ) {
			wp_send_json_error();
		}

		$title = ! empty( $request['title'] ) ? esc_attr( $request['title'] ) : false;

		if ( ! $title ) {
			$title = get_the_title( $apartment );
		}

		$units_number = ! empty( $request['number'] ) ? absint( $request['number'] ) : 1;
		$units_count  = count( jet_abaf()->db->get_apartment_units( $apartment ) );

		for ( $i = 1; $i <= $units_number; $i ++ ) {
			$index = $units_count + $i;

			jet_abaf()->db::wpdb()->insert( jet_abaf()->db->units->table(), [
				'apartment_id' => $apartment,
				'unit_title'   => $title . ' ' . $index,
			] );
		}

		wp_send_json_success( [ 'units' => jet_abaf()->db->get_apartment_units( $apartment ) ] );

	}

	/**
	 * Updates unit information for a specified instance.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added security check.
	 * @access public
	 *
	 * @return void
	 */
	public function update_unit() {

		$request = file_get_contents( 'php://input' );
		$request = json_decode( $request, true );

		$this->request_security_check( $request );

		$apartment = ! empty( $request['apartment'] ) ? $request['apartment'] : false;
		$unit      = ! empty( $request['unit'] ) ? $request['unit'] : false;

		if ( ! $apartment || ! $unit ) {
			wp_send_json_error();
		}

		$unit_id = $unit['unit_id'] ?? false;

		if ( ! $unit_id ) {
			wp_send_json_error();
		}

		jet_abaf()->db->units->update( $unit, [ 'unit_id' => $unit_id ] );

		wp_send_json_success( [ 'units' => jet_abaf()->db->get_apartment_units( $apartment ) ] );

	}

	/**
	 * Deletes a specified unit from an instance.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added security check.
	 * @access public
	 *
	 * @return void
	 */
	public function delete_unit() {

		$request = file_get_contents( 'php://input' );
		$request = json_decode( $request, true );

		$this->request_security_check( $request );

		$apartment = ! empty( $request['apartment'] ) ? absint( $request['apartment'] ) : false;
		$unit      = ! empty( $request['unit'] ) ? absint( $request['unit'] ) : false;

		if ( ! $apartment || ! $unit ) {
			wp_send_json_error();
		}

		jet_abaf()->db->units->delete( [ 'apartment_id' => $apartment, 'unit_id' => $unit ] );

		wp_send_json_success( [ 'units' => jet_abaf()->db->get_apartment_units( $apartment ) ] );

	}

	/**
	 * Initialize the units manager.
	 *
	 * Enqueues necessary scripts and assets, processes post data, and localizes it for use in the admin interface.
	 *
	 * @since 2.0.0
	 *
	 * @param string $hook The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function init_units_manager( $hook ) {

		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) || ! in_array( get_post_type(), $this->post_type ) ) {
			return;
		}

		$ui_data = jet_abaf()->framework->get_included_module_data( 'cherry-x-vue-ui.php' );
		$ui      = new \CX_Vue_UI( $ui_data );

		$ui->enqueue_assets();

		wp_enqueue_script(
			'jet-abaf-units-manager',
			JET_ABAF_URL . 'assets/js/admin/units-manager.js',
			[ 'cx-vue-ui', 'wp-api-fetch' ],
			JET_ABAF_VERSION,
			true
		);

		global $post;

		$post_id = jet_abaf()->db->get_initial_booking_item_id( $post->ID );
		$nonce   = wp_create_nonce( 'jet-abaf-manage-units' );

		wp_localize_script( 'jet-abaf-units-manager', 'JetABAFUnitsData', [
			'apartment'    => $post_id,
			'get_units'    => $this->get_action_url( 'get_units', [ 'apartment' => $post_id, 'nonce' => $nonce ] ),
			'insert_units' => $this->get_action_url( 'insert_units' ),
			'update_unit'  => $this->get_action_url( 'update_unit' ),
			'delete_unit'  => $this->get_action_url( 'delete_unit' ),
			'nonce'        => $nonce,
		] );

		add_action( 'admin_footer', [ $this, 'unit_manager_template' ] );

	}

	/**
	 * Get the action URL with specified parameters.
	 *
	 * @param string|null $action The action name to include in the URL.
	 * @param array       $args   Additional arguments to include in the query string.
	 *
	 * @return string The generated action URL with the provided parameters.
	 */
	public function get_action_url( $action = null, $args = [] ) {

		if ( ! $this->base_url ) {
			$this->base_url = admin_url( 'admin-ajax.php' );
		}

		return add_query_arg( array_merge( [ 'action' => 'jet_abaf_' . $action ], $args ), $this->base_url );

	}

	/**
	 * Performs a security check to validate the request.
	 *
	 * @since  2.6.2
	 * @since  3.8.0 Refactored.
	 * @since  4.0.0 Added booking vendor capability check.
	 * @access public
	 *
	 * @param array $request The request data containing the necessary parameters for the security check.
	 *
	 * @return void
	 */
	public function request_security_check( $request ) {

		if ( empty( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'jet-abaf-manage-units' ) ) {
			wp_send_json_error( [ 'message' => __( 'Page is expired. Please reload it and try again.', 'jet-booking' ) ] );
		}

		if ( ! current_user_can( \JET_ABAF\Capabilities::CAP_MANAGE_POST ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied. Not enough permissions.', 'jet-booking' ) ] );
		}

	}

	/**
	 * Units manager template.
	 *
	 * Outputs the units manager template for use in the admin interface.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function unit_manager_template() {
		ob_start();
		include JET_ABAF_PATH . 'templates/units-manager.php';
		printf( '<script type="text/x-template" id="jet-abaf-units-manager">%s</script>', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}