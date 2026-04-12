<?php
namespace JET_ABAF\Formbuilder_Plugin\Actions\Types;

use \JET_ABAF\Vendor\Actions_Core\Base_Handler_Exception;
use \Jet_Form_Builder\Actions\Action_Handler;
use \Jet_Form_Builder\Actions\Types\Base;
use \Jet_Form_Builder\Exceptions\Action_Exception;
use \Jet_Form_Builder\Form_Messages\Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Update_Booking extends Base {

	/**
	 * Returns identifier of the action.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'update_booking';
	}

	/**
	 * Returns action name.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Update Booking', 'jet-booking' );
	}

	/**
	 * Executing an action.
	 *
	 * @since   3.3.0
	 * @since   3.7.0 Added check in/out times handling.
	 *
	 * @param array          $request Data from the form, in the format `field_name => field_value`.
	 * @param Action_Handler $handler Manages actions instance.
	 *
	 * @return array|void
	 * @throws Action_Exception
	 */
	public function do_action( array $request, Action_Handler $handler ) {
		try {
			if ( empty( $this->settings['booking_field_id'] ) ) {
				return;
			}

			$booking_id = $request[ $this->settings['booking_field_id'] ];
			$booking    = jet_abaf()->db->get_booking_by( 'booking_id', $booking_id );

			if ( ! current_user_can( 'manage_options' ) && intval( $booking['user_id'] ) !== get_current_user_id() ) {
				throw new Base_Handler_Exception( __( 'Access denied. Not enough permissions.', 'jet-booking' ), 'error' );
			}

			$booking_object = jet_abaf_get_booking( $booking_id );

			if ( ! $booking_object->is_updatable() ) {
				throw new Base_Handler_Exception( __( 'Booking can no longer be updated.', 'jet-booking' ), 'error' );
			}

			$data = [];

			if ( ! empty( $this->settings['booking_field_dates'] ) ) {
				$dates = explode( ' - ', $request[ $this->settings['booking_field_dates'] ] );

				if ( 1 === count( $dates ) ) {
					$dates[] = $dates[0];
				}

				if ( empty( $dates ) || 2 !== count( $dates ) ) {
					throw new Base_Handler_Exception( __( 'Invalid dates.', 'jet-booking' ), 'error' );
				}

				$separator = jet_fb_context()->get_setting( 'cio_fields_separator', $this->settings['booking_field_dates'] );

				if ( false === $separator ) {
					$separator = '-';
				}

				$separator = 'space' === $separator ? ' ' : $separator;
				$format    = '!' . jet_fb_context()->get_setting( 'cio_fields_format', $this->settings['booking_field_dates'] );
				$format    = jet_abaf()->tools->date_format_js_to_php( $format );

				if ( false === $format ) {
					$format = '!Y-m-d';
				}

				if ( ! empty( $dates[0] ) ) {
					$dates[0] = str_replace( $separator, '-', $dates[0] );
				}

				if ( ! empty( $dates[1] ) ) {
					$dates[1] = str_replace( $separator, '-', $dates[1] );
				}

				$check_in_object  = \DateTime::createFromFormat( $format, $dates[0] );
				$check_out_object = \DateTime::createFromFormat( $format, $dates[1] );

				if ( ! $check_in_object || ! $check_out_object ) {
					$check_in  = strtotime( $dates[0] );
					$check_out = strtotime( $dates[1] );
				} else {
					$check_in  = $check_in_object->getTimestamp();
					$check_out = $check_out_object->getTimestamp();
				}

				if ( ! $check_in || ! $check_out ) {
					throw new Base_Handler_Exception( __( 'Booking dates not set.', 'jet-booking' ), 'error', $dates, $check_in, $check_out, $separator );
				}

				if ( $check_in === $check_out ) {
					$check_out = $check_out + 12 * HOUR_IN_SECONDS;
				}

				$check_in ++;

				$data = [
					'check_in_date'  => $check_in,
					'check_out_date' => $check_out,
				];
			}

			foreach ( $this->settings as $key => $value ) {
				$property = str_replace( 'booking_field_', '', $key );

				if ( 'dates' === $property || 'id' === $property ) {
					continue;
				}

				if ( isset( $request[ $value ] ) ) {
					$data[ $property ] = $request[ $value ];
				}
			}

			// phpcs:disable WordPress.Security.NonceVerification
			if ( jet_abaf()->settings->get( 'timepicker' ) ) {
				$check_in_time  = ! empty( $_REQUEST['check-in-time'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['check-in-time'] ) ) : '';
				$check_out_time = ! empty( $_REQUEST['check-out-time'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['check-out-time'] ) ) : '';

				if ( empty( $check_in_time ) || empty( $check_out_time ) ) {
					throw new Base_Handler_Exception( __( 'Time is required.', 'jet-booking' ), 'error' );
				}

				$data['check_in_time']  = $check_in_time;
				$data['check_out_time'] = $check_out_time;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			// Allow custom booking update processing.
			$pre_processed = apply_filters( 'jet-booking/form-action/update-booking/pre-process', false, $data, $booking_id, $this );

			if ( $pre_processed ) {
				return $pre_processed;
			}

			$booking = wp_parse_args( $data, $booking );

			if ( ! jet_abaf()->db->booking_availability( $booking, $booking_id ) && ! jet_abaf()->db->is_booking_dates_available( $booking, $booking_id ) ) {
				throw new Base_Handler_Exception( __( 'Booking dates already taken.', 'jet-booking' ), 'error' );
			}

			jet_abaf()->db->update_booking( $booking_id, $data );

			do_action( 'jet-booking/form-action/booking-updated', $booking_id );
		} catch ( Base_Handler_Exception $exception ) {
			throw new Action_Exception( esc_html( Manager::dynamic_error( $exception->getMessage() ) ), esc_html( $exception->getAdditional() ) );
		}
	}

	/**
	 * Register custom action data for the editor.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	public function action_data() {

		$fields            = jet_abaf()->db->get_default_fields();
		$additional_fields = jet_abaf()->db->get_additional_db_columns();

		foreach ( $fields as $key => $value ) {
			if ( in_array( $value, [
				'booking_id',
				'booking_vendor',
				'order_id',
				'user_id',
				'import_id',
				'check_in_date',
				'check_in_time',
				'check_out_date',
				'check_out_time',
				'apartment_unit'
			] ) ) {
				unset( $fields[ $key ] );
			}
		}

		return [
			'bookingFields' => array_merge( $fields, $additional_fields )
		];

	}

	/**
	 * Returns array os editor labels.
	 *
	 * @since 3.3.0
	 *
	 * @return string[]
	 */
	public function editor_labels() {
		return [
			'booking_field_id'    => __( 'Booking ID:', 'jet-booking' ),
			'booking_field_dates' => __( 'Check-in/out date field:', 'jet-booking' ),
			'fields_map'          => __( 'Fields map:', 'jet-booking' ),
		];
	}

	/**
	 * Returns array os editor labels.
	 *
	 * @since 3.3.0
	 *
	 * @return string[]
	 */
	public function editor_labels_help() {
		return [
			'fields_map'      => __( 'Set booking properties to save appropriate form fields into.', 'jet-booking' ),
			'apartment_field' => __( 'Note: changing the value in this field will not reinitialize the Check in/out field.', 'jet-booking' ),
		];
	}

}