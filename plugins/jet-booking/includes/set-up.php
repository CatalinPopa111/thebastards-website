<?php
namespace JET_ABAF;

use JET_ABAF\Formbuilder_Plugin\Form_Manager;
use JET_ABAF\Formbuilder_Plugin\Jfb_Plugin as Builder_Plugin;

class Set_Up {

	/**
	 * Holds the URL or identifier for a setup page.
	 *
	 * @var object|null
	 */
	private $setup_page = null;

	/**
	 * Stores the URL or identifier for a success page.
	 *
	 * @var object|null
	 */
	private $success_page = null;

	public function __construct() {
		add_filter( 'jet-abaf/dashboard/helpers/page-config/config', [ $this, 'check_setup' ] );
		add_action( 'wp_ajax_jet_abaf_setup', [ $this, 'process_setup' ] );
	}

	/**
	 * Process setup.
	 *
	 * Setup booking functionalities.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added `nonce` security check.
	 * @since  2.8.0 Removed `related_post_type_column` settings handling.
	 * @since  3.0.0 Refactored.
	 * @since  3.7.3 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function process_setup() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-set-up' ) ) { // phpcs:ignore
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'jet-booking' ) ] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied. Not enough permissions.', 'jet-booking' ) ] );
		}

		$setup_data = ! empty( $_REQUEST['setup_data'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_REQUEST['setup_data'] ) ) : []; // phpcs:ignore

		if ( ! isset( $setup_data['booking_mode'] ) ) {
			$setup_data['booking_mode'] = 'plain';
		}

		if ( ! isset( $setup_data['wc_integration'] ) ) {
			$setup_data['wc_integration'] = false;
		}

		if ( ! isset( $setup_data['wc_sync_orders'] ) ) {
			$setup_data['wc_sync_orders'] = false;
		}

		if ( ! isset( $setup_data['related_post_type'] ) ) {
			$setup_data['related_post_type'] = '';
		}

		// Clear existing settings before processing set up.
		jet_abaf()->settings->clear();

		$create_form = false;

		if ( ! empty( $setup_data ) ) {
			foreach ( $setup_data as $setting => $value ) {
				switch ( $setting ) {
					case 'wc_integration':
					case 'wc_sync_orders':
						$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
						break;
					default:
						$value = wp_unslash( $value );
						break;
				}

				if ( jet_abaf()->settings->setting_registered( $setting ) ) {
					jet_abaf()->settings->update( $setting, $value, false );
				}

				if ( 'create_single_form' === $setting ) {
					$create_form = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
				}
			}
		}

		if ( ! empty( $_REQUEST['db_columns'] ) && 'plain' === $setup_data['booking_mode'] ) {
			$columns       = jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_REQUEST['db_columns'] ) ); // phpcs:ignore
			$columns_names = [];

			foreach ( $columns as $key => $column ) {
				if ( ! in_array( $column['column'], $columns_names ) && ! empty( $column['column'] ) ) {
					$columns_names[] = $column['column'];
				} else {
					unset( $columns[ $key ] );
				}
			}

			jet_abaf()->settings->update( 'additional_columns', $columns, false );
		}

		jet_abaf()->settings->hook_db_columns();
		jet_abaf()->db->bookings->create_table( true );
		jet_abaf()->db->bookings_meta->create_table( true );
		jet_abaf()->db->units->create_table( true );
		jet_abaf()->settings->update( 'is_set', true, false );
		jet_abaf()->settings->write();

		if ( 'plain' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			$created_form = [];

			if ( $create_form ) {
				if ( function_exists( 'jet_form_builder' ) ) {
					$created_form = Form_Manager::instance()->insert_form( 'create_single_form' );
				} else {
					$result = Builder_Plugin::install_and_activate();

					if ( $result['success'] ) {
						jet_form_builder_init();

						jet_form_builder()->init_components();
						jet_form_builder()->post_type->register_post_type();

						$created_form = Form_Manager::instance()->insert_form( 'create_single_form' );
					}
				}
			}

			$instances  = [];
			$post_types = $setup_data[ 'apartment_post_type' ] ?? [];

			foreach ( $post_types as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );

				$instances[ $post_type ] = [
					'name'      => $post_type_object ? $post_type_object->labels->singular_name : '',
					'edit_link' => add_query_arg( [ 'post_type' => $post_type ], admin_url( 'edit.php' ) ),
				];
			}

			$orders_post_type = jet_abaf()->settings->get( 'related_post_type' );
			$product_id       = jet_abaf()->settings->get( 'wc_product_id' );

			$data = [
				'instances'      => $instances,
				'orders_page'    => $orders_post_type ? add_query_arg( [ 'post_type' => $orders_post_type ], admin_url( 'edit.php' ) ) : false,
				'wc_integration' => [
					'enabled'      => jet_abaf()->settings->wc_integration_enabled(),
					'product_link' => $product_id ? get_edit_post_link( $product_id, 'url' ) : false,
					'orders_page'  => add_query_arg( [ 'post_type' => 'shop_order' ], admin_url( 'edit.php' ) ),
				],
				'form'           => $created_form,
			];
		} else {
			$data = [
				'product_link'  => add_query_arg( [ 'post_type' => 'product', 'jet_booking_product' => 1 ], admin_url( 'post-new.php' ) ),
				'bookings_page' => add_query_arg( [ 'page' => 'jet-abaf-bookings' ], admin_url( 'admin.php' ) ),
			];
		}

		wp_send_json_success( wp_parse_args( $data, [
			'settings_url' => $this->success_page->get_url(),
		] ) );

	}

	/**
	 * Register the setup page.
	 *
	 * Register the setup page for the plugin. If the page is already registered will throw the error.
	 *
	 * @access public
	 *
	 * @param object $setup_page Dashboard page instance.
	 *
	 * @return void
	 */
	public function register_setup_page( $setup_page ) {
		if ( null !== $this->setup_page ) {
			trigger_error( esc_html__( 'Setup page is already registered!', 'jet-booking' ) );
		} else {
			$this->setup_page = $setup_page;
		}
	}

	/**
	 * Register the setup success page.
	 *
	 * Register the setup success page for the plugin.
	 *
	 * @access public
	 *
	 * @param object $success_page Dashboard page instance.
	 *
	 * @return void
	 */
	public function register_setup_success_page( $success_page ) {
		if ( null !== $this->success_page ) {
			trigger_error( esc_html__( 'Setup page is already registered!', 'jet-booking' ) );
		} else {
			$this->success_page = $success_page;
		}
	}

	/**
	 * Check setup.
	 *
	 * Check if the plugin is correctly configured and pass this data into appropriate localized data.
	 *
	 * @since  1.0.0
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @param array $args List of arguments.
	 *
	 * @return array
	 */
	public function check_setup( $args = [] ) {

		$args['setup'] = [
			'is_set'    => jet_abaf()->settings->get( 'is_set' ) || jet_abaf()->db->bookings->is_table_exists(),
			'setup_url' => $this->setup_page->get_url(),
		];

		// phpcs:disable WordPress.Security.NonceVerification
		if ( jet_abaf()->dashboard->is_page_now( $this->setup_page ) ) {
			$args['post_types'] = jet_abaf()->tools->get_post_types_for_js();
			$args['db_fields']  = jet_abaf()->db->get_default_fields();

			$args['reset'] = [
				'is_reset'  => ! empty( $_GET['jet_abaf_reset'] ),
				'reset_url' => add_query_arg( [ 'jet_abaf_reset' => 1 ], $this->setup_page->get_url() ),
			];
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return $args;

	}

}