<?php

namespace JET_ABAF\Formbuilder_Plugin\Presets;

use \Jet_Form_Builder\Presets\Sources\Base_Source;
use \Jet_Form_Builder\Exceptions\Preset_Exception;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Source_Booking extends Base_Source {

	/**
	 * Returns booking source preset ID.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'jet_booking';
	}

	/**
	 * Query preset source.
	 *
	 * @since 3.3.0
	 *
	 * @return mixed|object|null
	 * @throws Preset_Exception
	 */
	public function query_source() {

		$booking_source = ! empty( $this->preset_data['booking_source'] ) ? $this->preset_data['booking_source'] : 'current_post';

		if ( 'current_post' === $booking_source ) {
			return apply_filters( 'jet-booking/form-builder/preset-source/object', null );
		}

		$var     = ! empty( $this->preset_data['query_var'] ) ? $this->preset_data['query_var'] : '';
		$post_id = $var && isset( $_REQUEST[ $var ] ) ? absint( $_REQUEST[ $var ] ) : false; // phpcs:ignore

		if ( ! $post_id ) {
			throw new Preset_Exception( esc_html__( 'Empty Post ID', 'jet-booking' ) );
		}

		return jet_abaf_get_booking( $post_id );

	}

	/**
	 * Check if booking default value preset can be applied.
	 *
	 * @since 3.3.0
	 *
	 * @return bool
	 * @throws Preset_Exception
	 */
	protected function can_get_preset() {

		if ( ! parent::can_get_preset() ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$object = $this->src();

		if ( ! $object || ! is_a( $object, '\JET_ABAF\Resources\Booking' ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) && empty( $object->get_user_id() ) ) {
			return false;
		}

		return current_user_can( 'manage_options' ) || $object->get_user_id() === get_current_user_id();

	}

	/**
	 * Returns default booking value for status.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function source__status() {
		return $this->src()->get_status();
	}

	/**
	 * Returns default booking value for apartment.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function source__apartment_id() {
		return $this->src()->get_apartment_id();
	}

	/**
	 * Returns default booking value for dates.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function source__dates() {

		$separator = ! empty( $this->field_args['cio_fields_separator'] ) ? $this->field_args['cio_fields_separator'] : '-';
		$separator = 'space' === $separator ? ' ' : $separator;
		$format    = ! empty( $this->field_args['cio_fields_format'] ) ? $this->field_args['cio_fields_format'] : 'YYYY-MM-DD';
		$format    = jet_abaf()->tools->date_format_js_to_php( str_replace( '-', $separator, $format ) );

		return date( $format, $this->src()->get_check_in_date() ) . ' - ' . date( $format, $this->src()->get_check_out_date() );

	}

	/**
	 * Returns default booking value for user email.
	 *
	 * @since 3.7.0
	 *
	 * @return string
	 */
	public function source__user_email() {
		return $this->src()->get_user_email();
	}

	/**
	 * Returns default booking value for additional columns.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function source__additional_column() {

		$column = false;

		if ( ! empty( $this->preset_data['current_field_column_name'] ) ) {
			$column = $this->preset_data['current_field_column_name'];
		} elseif ( ! empty( $this->field_data['column_name'] ) ) {
			$column = $this->field_data['column_name'];
		}

		if ( ! $column ) {
			return '';
		}

		return $this->src()->get_column( $column );

	}

}
