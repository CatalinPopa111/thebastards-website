<?php

namespace JET_ABAF\Vendor\Actions_Core;

use Jet_Form_Builder\Actions\Action_Handler;
use Jet_Form_Builder\Exceptions\Action_Exception;
use Jet_Form_Builder\Form_Messages\Manager;
use Jet_Form_Builder\Gateways\Gateway_Manager;

/**
 * @property array settings
 * @method Action_Handler getInstance()
 *
 * Trait SmartActionTrait
 * @package JET_APB\Vendor\Actions_Core
 */
trait Smart_Action_Trait {

	use Smart_Notification_Action_Trait;

	public function setRequest( $key, $value ) {
		$this->getInstance()->request_data[ $key ] = $value;

		return $this;
	}

	public function hasGateway() {
		return Gateway_Manager::instance()->has_gateway( $this->getInstance()->form_id );
	}

	public function parseDynamicException( $type, $message ) {
		switch ( $type ) {
			case 'error':
				return Manager::dynamic_error( $message );
			case 'success':
				return Manager::dynamic_success( $message );
			default:
				return $message;

		}
	}

	public function getFormId() {
		return $this->getInstance()->form_id;
	}

	public function filterQueryArgs( callable $callable ) {
		add_filter( 'jet-fb/response-handler/query-args', function ( $query_args, $handler ) use ( $callable ) {
			if ( 'success' !== $handler->args['status'] ) {
				return $query_args;
			}

			return call_user_func( $callable, $query_args, $handler, $handler->args );
		}, 10, 2 );
	}

	public function isAjax() {
		return $this->getInstance()->request_data['__is_ajax'];
	}

	public function getFieldSettingsByName( $field_name, $setting_name, $if_not_exist = false ) {
		return jet_form_builder()->form_handler->request_handler->get_field_attrs_by_name( $field_name, $setting_name, $if_not_exist );
	}

	/**
	 * @param array $request
	 * @param Action_Handler $handler
	 *
	 * @return mixed|void
	 * @throws Action_Exception
	 */
	public function do_action( array $request, Action_Handler $handler ) {
		try {
			$this->_requestData = $request;
			$this->_instance    = $handler;
			$this->_settings    = $this->settings;

			$bookings = $this->run_action();

			if ( empty( $bookings ) ) {
				throw new Base_Handler_Exception( __( 'Booking can`t be added.', 'jet-booking' ), 'error' );
			}

			do_action( 'jet-abaf/jet-fb/action/success', end( $bookings ), $this, $bookings );

		} catch ( Base_Handler_Exception $exception ) {
			throw new Action_Exception( $this->parseDynamicException( $exception->type, $exception->getMessage() ), $exception->getAdditional() );
		}
	}

}