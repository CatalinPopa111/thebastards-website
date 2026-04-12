<?php

namespace JET_ABAF\Formbuilder_Plugin;

use JET_ABAF\Formbuilder_Plugin\Actions\Action_Manager;
use JET_ABAF\Formbuilder_Plugin\Blocks\Blocks_Manager;
use Jet_Form_Builder\Classes\Tools;
use Jet_Form_Builder\Presets\Preset_Manager;
use Jet_Form_Builder\Blocks\Block_Helper;
use Jet_Form_Builder\Blocks\Render\Form_Builder;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Jfb_Plugin {

	const PACKAGE = 'https://downloads.wordpress.org/plugin/jetformbuilder.zip';
	const PLUGIN = 'jetformbuilder/jet-form-builder.php';

	public function __construct() {

		if ( ! defined( 'JET_FORM_BUILDER_VERSION' ) ) {
			return false;
		}

		new Blocks_Manager();
		new Action_Manager();
		new Manage_Calculated_Data();
		new Gateway_Manager();

		// Register form generators.
		add_filter( 'jet-form-builder/forms/options-generators', function ( $generators ) {
			$generators[] = new Generators\Get_From_Booking_Statuses();

			return $generators;
		} );

		// Register booking preset config.
		add_action( 'jet-form-builder/editor/preset-config', [ $this, 'preset_configuration' ] );

		// Register form preset sources.
		if ( class_exists( '\Jet_Form_Builder\Presets\Preset_Manager' ) && method_exists( Preset_Manager::instance(), 'register_source_type' ) ) {
			Preset_Manager::instance()->register_source_type( new Presets\Source_Booking() );
			Preset_Manager::instance()->register_source_type( new Presets\Source_Apartments() );
		}

		// Set booking queried post id.
		add_filter( 'jet-booking/form-fields/queried-post-id', [ $this, 'set_booking_queried_post_id' ] );
		// Set check in/out field attributes.
		add_filter( 'jet-booking/form-fields/check-in-out/attributes', [ $this, 'set_check_in_out_field_attrs' ] );

		// Print form notice.
		add_filter( 'jet-form-builder/before-start-form', [ $this, 'maybe_print_form_notice' ], 10, 2 );

	}

	/**
	 * Returns modified preset configurations.
	 *
	 * @since 3.3.0
	 * @since 3.8.0 Added Post Units Number option for a Post source.
	 *
	 * @param array $config List of default presets configurations.
	 *
	 * @return array
	 */
	public function preset_configuration( $config ) {

		$config['global_fields'][0]['options'][] = [
			'value' => 'jet_booking',
			'label' => __( 'JetBooking', 'jet-booking' ),
		];

		$config['global_fields'][] = [
			'name'      => 'booking_source',
			'type'      => 'select',
			'label'     => __( 'Get Booking ID From:', 'jet-booking' ),
			'options'   => Tools::with_placeholder( [
				[
					'value' => 'current_post',
					'label' => __( 'Current Post', 'jet-booking' ),
				],
				[
					'value' => 'query_var',
					'label' => __( 'URL Query Variable', 'jet-booking' ),
				],
			] ),
			'condition' => [
				'field' => 'from',
				'value' => 'jet_booking',
			],
		];

		$config['global_fields'][] = [
			'name'             => 'query_var',
			'type'             => 'text',
			'label'            => __( 'Query Variable Name:', 'jet-booking' ),
			'parent_condition' => [
				'field' => 'from',
				'value' => 'jet-booking',
			],
			'condition'        => [
				'field' => 'booking_source',
				'value' => 'query_var',
			],
		];

		$config['map_fields'][] = [
			'name'             => 'prop',
			'type'             => 'select',
			'label'            => __( 'Booking Property:', 'jet-booking' ),
			'options'          => Tools::with_placeholder( [
				[
					'value' => 'status',
					'label' => __( 'Status', 'jet-booking' ),
				],
				[
					'value' => 'apartment_id',
					'label' => __( 'Apartment', 'jet-booking' ),
				],
				[
					'value' => 'dates',
					'label' => __( 'Dates', 'jet-booking' ),
				],
				[
					'value' => 'user_email',
					'label' => __( 'User E-mail', 'jet-booking' ),
				],
				[
					'value' => 'additional_column',
					'label' => __( 'Additional Column', 'jet-booking' ),
				],
			] ),
			'parent_condition' => [
				'field' => 'from',
				'value' => 'jet_booking',
			],
		];

		$additional_columns = jet_abaf()->tools->prepare_list_for_js( jet_abaf()->settings->get_clean_columns() );

		$config['map_fields'][] = [
			'name'             => 'column_name',
			'type'             => 'select',
			'label'            => __( 'Column Name:', 'jet-booking' ),
			'options'          => Tools::with_placeholder( $additional_columns ),
			'parent_condition' => [
				'field' => 'from',
				'value' => 'jet_booking',
			],
			'condition'        => [
				'field' => 'prop',
				'value' => 'additional_column',
			],
		];

		$config['map_fields'][1]['options'][] = [
			'value' => 'post_units_number',
			'label' => __( 'Post Units Number', 'jet-booking' ),
		];

		return $config;

	}

	/**
	 * Get booking queried object.
	 *
	 * Retrieves the booking object based on the hidden field value in the form.
	 *
	 * @since 3.7.0
	 *
	 * @return \JET_ABAF\Resources\Booking|mixed|null
	 */
	public function get_booking_queried_object() {

		$block = Block_Helper::find_by_block_name( jet_fb_live()->blocks, 'jet-forms/hidden-field' );

		if ( empty( $block ) ) {
			return null;
		}

		$attrs = $block['attrs'];

		if ( empty( $attrs['field_value'] ) ) {
			return null;
		}

		$object = null;

		switch ( $attrs['field_value'] ) {
			case 'booking_id':
				$object = apply_filters( 'jet-booking/formbuilder-plugin/actions/object', $object );
				break;

			case 'query_var':
				$var        = ! empty( $attrs['query_var_key'] ) ? $attrs['query_var_key'] : '';
				$booking_id = $var && isset( $_REQUEST[ $var ] ) ? absint( $_REQUEST[ $var ] ) : false; // phpcs:ignore
				$object     = jet_abaf_get_booking( $booking_id );

				break;

			default:
				break;
		}

		if ( $object && is_a( $object, '\JET_ABAF\Resources\Booking' ) ) {
			return $object;
		}

		return null;

	}

	/**
	 * Set booking queried post id.
	 *
	 * Setup bookings form actions queried post id.
	 *
	 * @since 3.3.2
	 * @since 3.7.0 Refactored.
	 *
	 * @param int $id Post ID.
	 *
	 * @return int|null
	 */
	public function set_booking_queried_post_id( $id ) {

		$post_type  = get_post_type( $id );
		$post_types = jet_abaf()->settings->get( 'apartment_post_type' );

		if ( $post_type && is_array( $post_types ) && in_array( $post_type, $post_types ) ) {
			return $id;
		}

		$object = $this->get_booking_queried_object();

		if ( ! $object ) {
			return $id;
		}

		return $object->get_apartment_id();

	}

	/**
	 * Set check in/out field attributes.
	 *
	 * Sets attributes for check-in/out fields in a booking form.
	 *
	 * @since 3.7.0
	 *
	 * @param string $attrs The current attributes of the check-in/out fields.
	 *
	 * @return string
	 */
	public function set_check_in_out_field_attrs( $attrs ) {

		$form_actions = get_post_meta( jet_fb_live()->form_id, '_jf_actions', true );

		if ( empty( $form_actions ) ) {
			return $attrs;
		}

		$object = null;

		foreach ( json_decode( $form_actions ) as $action ) {
			if ( 'update_booking' !== $action->type ) {
				continue;
			}

			$object = $this->get_booking_queried_object();
		}

		if ( ! $object ) {
			return $attrs;
		}

		$attrs .= 'data-booking-id=' . $object->get_id();

		return $attrs;

	}

	/**
	 * Maybe print form notice.
	 *
	 * Checks if a booking form notice should be printed.
	 *
	 * @since 3.7.0
	 *
	 * @param string       $markup       The current form markup.
	 * @param Form_Builder $form_builder The form builder object.
	 *
	 * @return string The updated form markup.
	 */
	public function maybe_print_form_notice( $markup, $form_builder ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return $markup;
		}

		if ( ! jet_abaf()->settings->get( 'enable_workflows' ) ) {
			return $markup;
		}

		if ( empty( jet_abaf()->workflows->collection->to_array() ) ) {
			return $markup;
		}

		$form_actions = get_post_meta( $form_builder->args['form_id'], '_jf_actions', true );

		if ( empty( $form_actions ) ) {
			return $markup;
		}

		foreach ( json_decode( $form_actions ) as $action ) {
			if ( 'apartment_booking' === $action->type && empty( $action->settings->apartment_booking->booking_email_field ) ) {
				$markup .= sprintf(
					'<div class="notice notice-warning" style="background-color: #fcf8e3; color: #8a6d3b; border: 1px solid #faebcc; border-left-width: 4px; margin: 10px 0; padding: 10px;">%s</div>',
					__( 'Workflows is enabled and configured. To ensure proper functionality, create and set up an email field in the booking form.', 'jet-booking' )
				);
			}
		}

		return $markup;

	}

	public static function get_path( $path = '' ) {
		return JET_ABAF_PATH . '/includes/formbuilder-plugin/' . $path;
	}

	public static function install_and_activate() {
		if ( file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN ) ) {
			return self::activate_plugin();
		}

		$installed = self::install_plugin();
		if ( $installed['success'] ) {
			$activated = self::activate_plugin();

			if ( $activated['success'] && ! function_exists( 'jet_form_builder' ) ) {
				require_once WP_PLUGIN_DIR . '/' . self::PLUGIN;
			}

			return $activated;
		}

		return $installed;
	}

	public static function activate_plugin() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return [
				'success' => false,
				'message' => esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'jet-form-builder' ),
				'data'    => [],
			];
		}

		$activate = null;

		if ( ! is_plugin_active( self::PLUGIN ) ) {
			$activate = activate_plugin( self::PLUGIN );
		}

		return is_null( $activate ) ? [ 'success' => true ] : [ 'success' => false ];
	}

	public static function install_plugin() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return [
				'success' => false,
				'message' => esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'jet-form-builder' ),
				'data'    => [],
			];
		}

		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( self::PACKAGE );

		if ( is_wp_error( $result ) ) {
			return [
				'success' => false,
				'message' => $result->get_error_message(),
				'data'    => [],
			];
		} elseif ( is_wp_error( $skin->result ) ) {
			return [
				'success' => false,
				'message' => $skin->result->get_error_message(),
				'data'    => [],
			];
		} elseif ( $skin->get_errors()->get_error_code() ) {
			return [
				'success' => false,
				'message' => $skin->get_error_messages(),
				'data'    => [],
			];
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorMessage'] = 'Unable to connect to the filesystem. Please confirm your credentials.';

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			return [
				'success' => false,
				'message' => $status['errorMessage'],
				'data'    => [],
			];
		}

		return [
			'success' => true,
			'message' => esc_html__( 'JetFormBuilder has been installed.', 'jet-form-builder' ),
		];
	}

}