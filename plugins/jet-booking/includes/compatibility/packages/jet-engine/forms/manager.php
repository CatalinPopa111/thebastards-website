<?php

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Forms;

use \JET_ABAF\Apartment_Booking_Trait;
use \JET_ABAF\Compatibility\Packages\Jet_Engine;
use \JET_ABAF\Form_Fields\Check_In_Out_Render;
use \JET_ABAF\Vendor\Actions_Core\Smart_Notification_Trait;

class Manager {

	use Smart_Notification_Trait;
	use Apartment_Booking_Trait;

	/**
	 * A reference to an instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	public function __construct() {

		if ( 'plain' !== jet_abaf()->settings->get( 'booking_mode' ) ) {
			return;
		}

		// Register field for booking form.
		add_filter( 'jet-engine/forms/booking/field-types', [ $this, 'register_dates_field' ] );
		add_action( 'jet-engine/forms/edit-field/before', [ $this, 'edit_fields' ] );

		// Register notification for booking form.
		add_filter( 'jet-engine/forms/booking/notification-types', [ $this, 'register_booking_notification' ] );
		add_action( 'jet-engine/forms/booking/notifications/fields-after', [ $this, 'notification_fields' ] );

		// Macros %ADVANCED_PRICE% procession in the calculator field.
		add_filter( 'jet-engine/calculated-data/ADVANCED_PRICE', function ( $macros ) {
			return $macros;
		} );

		add_filter( 'jet-engine/forms/gateways/notifications-before', [ $this, 'before_form_gateway' ], 1, 2 );
		add_filter( 'jet-engine/forms/handler/query-args', [ $this, 'handler_query_args' ], 10, 3 );
		add_action( 'jet-engine/forms/editor/macros-list', [ $this, 'add_macros_list' ] );
		add_action( 'jet-engine/forms/gateways/on-payment-success', [ $this, 'on_gateway_success' ], 10, 3 );

		$check_in_out = new Check_In_Out_Render();
		// Add form field template.
		add_action( 'jet-engine/forms/booking/field-template/check_in_out', [ $check_in_out, 'getFieldTemplate' ], 10, 3 );

		// Register notification handler.
		add_filter( 'jet-engine/forms/booking/notification/apartment_booking', [ $this, 'do_action' ], 1, 2 );

	}

	/**
	 * Register dates fields.
	 *
	 * Register specific booking field type for JetEngine forms.
	 *
	 * @since 2.0.0
	 * @since 3.2.0 Moved to compatibility file.
	 *
	 * @param array $fields Fields types list.
	 *
	 * @return mixed
	 */
	public function register_dates_field( $fields ) {
		$fields['check_in_out'] = __( 'Check-in/check-out dates', 'jet-booking' );

		return $fields;
	}

	/**
	 * Edit fields.
	 *
	 * Render additional edit field for dates field.
	 *
	 * @since  2.0.0.
	 * @since  3.0.0 Moved to the separate template.
	 * @since  3.2.0 Moved to compatibility file.
	 */
	public function edit_fields() {
		include Jet_Engine::instance()->package_path( 'templates/admin/form-field.php' );
	}

	/**
	 * Register booking notifications.
	 *
	 * Register specific booking notification type for JetEngine forms.
	 *
	 * @since 2.0.0
	 * @since 3.2.0 Moved to compatibility file.
	 *
	 * @param array $notifications Forms notifications list.
	 *
	 * @return mixed
	 */
	public function register_booking_notification( $notifications ) {
		$notifications['apartment_booking'] = __( 'Apartment booking', 'jet-booking' );

		return $notifications;
	}

	/**
	 * Notification fields.
	 *
	 * Render additional JetEngine forms notification fields.
	 *
	 * @since  2.0.0.
	 * @since  3.0.0 Moved to the separate template.
	 * @since  3.2.0 Moved to compatibility file.
	 */
	public function notification_fields() {
		include Jet_Engine::instance()->package_path( 'templates/admin/form-notification.php' );
	}

	/**
	 * Add macros list.
	 *
	 * Adds a macro description to the calculator field.
	 *
	 * @since 2.0.0
	 * @since 3.2.0 Moved to compatibility file.
	 */
	function add_macros_list() {
		include Jet_Engine::instance()->package_path( 'templates/admin/macros-list.php' );
	}

	/**
	 * Before form gateway.
	 *
	 * Set booking notification before gateway.
	 *
	 * @since  1.0.0
	 * @since  3.0.0 New naming.
	 * @since  3.2.0 Moved to compatibility file.
	 *
	 * @param array $stored_notifications List of stored notifications.
	 * @param array $notifications        List of all notifications.
	 *
	 * @return array
	 */
	public function before_form_gateway( $stored_notifications, $notifications ) {

		foreach ( $notifications as $index => $notification ) {
			if ( 'apartment_booking' === $notification['type'] && ! in_array( $index, $stored_notifications ) ) {
				$stored_notifications[] = $index;
			}
		}

		return $stored_notifications;

	}

	/**
	 * Handle query args.
	 *
	 * @since  3.0.0 New naming.
	 * @since  3.2.0 Moved to compatibility file.
	 *
	 * @param array  $query_args List of query arguments.
	 * @param array  $args       List of handler arguments.
	 * @param object $handler    Handler instance.
	 *
	 * @return mixed
	 */
	public function handler_query_args( $query_args, $args, $handler ) {

		$field_name = false;

		foreach ( $handler->form_fields as $field ) {
			if ( 'check_in_out' === $field['type'] ) {
				$field_name = $field['name'];
			}
		}

		if ( $field_name ) {
			$query_args['new_date'] = $handler->notifcations->data[ $field_name ];
		}

		return $query_args;

	}

	/**
	 * On gateway success.
	 *
	 * Finalize booking on internal JetEngine form gateway success.
	 *
	 * @since 2.0.0
	 * @since 3.2.0 Moved to compatibility file.
	 *
	 * @param string|int $form_id   Form ID.
	 * @param array      $settings  Settings array.
	 * @param array      $form_data Form data array.
	 */
	public function on_gateway_success( $form_id, $settings, $form_data ) {
		if ( ! empty( $form_data['booking_id'] ) ) {
			jet_abaf()->db->update_booking( $form_data['booking_id'], [ 'status' => 'completed' ] );
		}
	}

	/**
	 * Returns the instance.
	 *
	 * @since  3.2.0
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