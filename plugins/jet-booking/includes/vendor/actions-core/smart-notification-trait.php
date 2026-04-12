<?php

namespace JET_ABAF\Vendor\Actions_Core;

/**
 * @method \Jet_Engine_Booking_Forms_Notifications getInstance()
 *
 * Trait SmartNotificationTrait
 * @package JET_APB\Vendor\Actions_Core
 */
trait Smart_Notification_Trait {

	use Smart_Notification_Action_Trait;

	public function setRequest( $key, $value ) {
		$this->getInstance()->data[ $key ]               = $value;
		$this->getInstance()->handler->form_data[ $key ] = $value;

		return $this;
	}

	public function hasGateway() {
		return $this->getInstance()->handler->has_gateway();
	}

	public function getFormId() {
		return $this->getInstance()->form;
	}

	public function filterQueryArgs( callable $callable ) {
		add_filter( 'jet-engine/forms/handler/query-args', function ( $query_args, $args, $handler ) use ( $callable ) {
			if ( 'success' !== $args['status'] ) {
				return $query_args;
			}

			return call_user_func( $callable, $query_args, $handler, $args );
		}, 10, 3 );
	}

	public function isAjax() {
		return $this->getInstance()->handler->is_ajax;
	}

	public function getFieldSettingsByName( $field_name, $setting_name, $if_not_exist = false ) {
		if ( $this->getSettings( 'name' ) === $field_name ) {
			return $this->getSettings( $setting_name );
		}
		$fields = $this->getInstance()->handler->form_fields;

		if ( ! isset( $fields[ $field_name ] ) || ! is_array( $fields[ $field_name ] ) ) {
			return $if_not_exist;
		}

		return $fields[ $field_name ][ $setting_name ] ?? $if_not_exist;
	}


	/**
	 * @param $settings
	 * @param $notifications
	 *
	 * @return void
	 */
	public function do_action( $settings, $notifications ) {
		try {
			$this->_requestData = $notifications->data;
			$this->_instance    = $notifications;
			$this->_settings    = $settings;

			$bookings             = $this->run_action();
			$notifications->log[] = true;

			do_action( 'jet-abaf/form/notification/success', end( $bookings ), $this, $bookings );
		} catch ( Base_Handler_Exception $exception ) {
			return $notifications->set_specific_status( $exception->getMessage() );
		}
	}

}