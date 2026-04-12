<?php
namespace JET_ABAF\Formbuilder_Plugin;

use Jet_Form_Builder\Actions\Types\Base;
use Jet_Form_Builder\Gateways\Base_Gateway;

class Gateway_Manager {

	public function __construct() {
		add_filter(
			'jet-form-builder/gateways/notifications-before',
			array( $this, 'before_form_gateway' ), 0, 2
		);
		add_action(
			'jet-form-builder/gateways/on-payment-success',
			array( $this, 'on_gateway_success' )
		);
	}

	public function before_form_gateway( $actions_ids, $actions_all ) {
		foreach ( $actions_all as $action ) {
			/** @var Base $action */
			if ( 'apartment_booking' === $action->get_id() && ! in_array( $action->_id, $actions_ids ) ) {
				$actions_ids[ $action->_id ] = array( 'active' => true );
			}
		}

		return $actions_ids;
	}

	/**
	 * Handles the successful gateway response and updates the status of related bookings to 'completed'.
	 *
	 * @since 2.2.5
	 * @since 3.8.0 Handled multiple bookings for the same form data.
	 *
	 * @param Base_Gateway $gateway The gateway instance.
	 *
	 * @return void
	 */
	public function on_gateway_success( Base_Gateway $gateway ) {

		$form_data = $gateway->property( 'data' )['form_data'];

		if ( empty( $form_data['booking_ids'] ) ) {
			return;
		}

		foreach ( $form_data['booking_ids'] as $booking_id ) {
			jet_abaf()->db->update_booking( $booking_id, [ 'status' => 'completed' ] );
		}

	}

}