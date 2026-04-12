<?php

namespace JET_ABAF\Formbuilder_Plugin\Actions\Types;

use JET_ABAF\Apartment_Booking_Trait;
use JET_ABAF\Vendor\Actions_Core\Smart_Action_Trait;
use Jet_Form_Builder\Actions\Types\Base;

class Insert_Booking extends Base {

	use Apartment_Booking_Trait;
	use Smart_Action_Trait;

	public function get_id() {
		return 'apartment_booking';
	}

	public function get_name() {
		return __( 'Apartment Booking', 'jet-booking' );
	}

	public function visible_attributes_for_gateway_editor() {
		return [ 'booking_apartment_field' ];
	}

	public function self_script_name() {
		return 'JetBookingActionData';
	}

	/**
	 * Action data.
	 *
	 * @since  2.8.0 Refactored. Added initial select type.
	 * @access public
	 *
	 * @return array
	 */
	public function action_data() {

		$wc_integration = jet_abaf()->settings->wc_integration_enabled();
		$post_id        = get_the_ID();
		$wc_fields      = [];
		$details        = [];
		$nonce          = '';

		if ( $wc_integration ) {
			$nonce     = wp_create_nonce( jet_abaf()->wc->mode->details->meta_key );
			$wc_fields = jet_abaf()->wc->mode->get_checkout_fields();
			$details   = jet_abaf()->wc->mode->details->get_details_schema( $post_id );
		}

		return [
			'columns'        => jet_abaf()->db->get_additional_db_columns(),
			'wc_integration' => $wc_integration,
			'apartment'      => $post_id,
			'wc_fields'      => $wc_fields,
			'details'        => $details,
			'nonce'          => $nonce,
			'details_types'  => [
				[
					'value' => '',
					'label' => __( 'Select type...', 'jet-booking' ),
				],
				[
					'value' => 'booked-inst',
					'label' => __( 'Booked instance name', 'jet-booking' ),
				],
				[
					'value' => 'check-in',
					'label' => __( 'Check in', 'jet-booking' ),
				],
				[
					'value' => 'check-out',
					'label' => __( 'Check out', 'jet-booking' ),
				],
				[
					'value' => 'unit',
					'label' => __( 'Booking unit', 'jet-booking' ),
				],
				[
					'value' => 'field',
					'label' => __( 'Form field', 'jet-booking' ),
				],
				[
					'value' => 'add_to_calendar',
					'label' => __( 'Add to Google calendar link', 'jet-booking' ),
				],
			],
		];

	}

	public function editor_labels() {
		return [
			'booking_apartment_field' => __( 'Apartment ID field:', 'jet-booking' ),
			'booking_dates_field'     => __( 'Check-in/Check-out date field:', 'jet-booking' ),
			'booking_email_field'     => __( 'E-mail field:', 'jet-booking' ),
			'db_columns_map'          => __( 'DB columns map:', 'jet-booking' ),
			'disable_wc_integration'  => __( 'Disable WooCommerce integration:', 'jet-booking' ),
			'booking_wc_price'        => __( 'WooCommerce Price field:', 'jet-booking' ),
			'wc_order_details'        => __( 'WooCommerce order details:', 'jet-booking' ),
			'wc_fields_map'           => __( 'WooCommerce checkout fields map:', 'jet-booking' ),
			'wc_details__type'        => __( 'Type', 'jet-booking' ),
			'wc_details__label'       => __( 'Label', 'jet-booking' ),
			'wc_details__format'      => __( 'Date format', 'jet-booking' ),
			'wc_details__field'       => __( 'Select form field', 'jet-booking' ),
			'wc_details__link_label'  => __( 'Link text', 'jet-booking' ),
		];
	}

	public function editor_labels_help() {
		return [
			'db_columns_map'         => __( 'Set up connection between form fields and additional database table columns. This allows you to save entered field data in the corresponding DB column.', 'jet-booking' ),
			'disable_wc_integration' => __( 'Check to disable WooCommerce integration and disconnect the booking system with WooCommerce checkout for current form.', 'jet-booking' ),
			'booking_wc_price'       => __( 'Select field to get total price from. If not selected price will be get from post meta value.', 'jet-booking' ),
			'wc_order_details'       => __( 'Set up booking-related info you want to add to the WooCommerce orders and e-mails.', 'jet-booking' ),
			'wc_fields_map'          => __( 'Connect WooCommerce checkout fields to appropriate form fields. This allows you to pre-fill WooCommerce checkout fields after redirect to checkout.', 'jet-booking' ),
		];
	}

}