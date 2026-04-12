<?php

namespace JET_ABAF\DB\Tables;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Bookings extends Base {

	/**
	 * Return table slug.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function table_slug() {
		return 'apartment_bookings';
	}

	/**
	 * Schema.
	 *
	 * Returns booking table columns schema.
	 *
	 * @since  3.3.0
	 * @since  3.7.0 Added `user_email`, `check_in_time`, `check_out_time` columns.
	 * @since  4.0.0 Added `booking_vendor` column.
	 *
	 * @return string[]
	 */
	public function schema() {
		return [
			'booking_id'     => "bigint(20) NOT NULL AUTO_INCREMENT",
			'booking_vendor' => "bigint(20)",
			'status'         => "text",
			'apartment_id'   => "bigint(20)",
			'apartment_unit' => "bigint(20)",
			'check_in_date'  => "bigint(20)",
			'check_in_time'  => "bigint(20)",
			'check_out_date' => "bigint(20)",
			'check_out_time' => "bigint(20)",
			'user_id'        => "bigint(20)",
			'user_email'     => "text",
			'order_id'       => "bigint(20)",
			'import_id'      => "text",
		];
	}

	/**
	 * Returns table schema.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_table_schema() {

		$default_columns    = $this->schema();
		$additional_columns = jet_abaf()->db->get_additional_db_columns();
		$columns_schema     = '';

		foreach ( $default_columns as $column => $desc ) {
			$columns_schema .= "$column $desc,";
		}

		if ( is_array( $additional_columns ) && ! empty( $additional_columns ) ) {
			foreach ( $additional_columns as $column ) {
				$columns_schema .= "$column text,";
			}
		}

		$table           = $this->table();
		$charset_collate = $this->wpdb()->get_charset_collate();

		return "CREATE TABLE $table ( $columns_schema PRIMARY KEY ( booking_id ) ) $charset_collate;";

	}

	/**
	 * Sanitize data before database.
	 *
	 * Allow child classes to do their own sanitizing of the data before writing it into DB.
	 *
	 * @since  3.3.0
	 * @since  3.7.0 Added timepicker sanitization.
	 * @since  4.0.0 Added `booking_vendor` handling.
	 * @since  4.0.1 Added Arabic time normalization..
	 * @access public
	 *
	 * @param array $data Table data.
	 *
	 * @return array
	 */
	public function sanitize_data_before_db( $data = [] ) {

		if ( jet_abaf()->settings->get( 'timepicker' ) ) {
			$time_format = get_option( 'time_format', 'g:i a' );

			if ( ! empty( $data['check_in_time'] ) && ! jet_abaf()->tools->is_valid_timestamp( $data['check_in_time'] ) ) {
				$check_in_time = jet_abaf()->tools->normalize_arabic_time( $data['check_in_time'] );
				$check_in_time = \DateTime::createFromFormat( $time_format, $check_in_time );

				$data['check_in_time'] = $check_in_time->getTimestamp() - strtotime( 'today' );
			}

			if ( ! empty( $data['check_out_time'] ) && ! jet_abaf()->tools->is_valid_timestamp( $data['check_out_time'] ) ) {
				$check_out_time = jet_abaf()->tools->normalize_arabic_time( $data['check_out_time'] );
				$check_out_time = \DateTime::createFromFormat( $time_format, $check_out_time );

				$data['check_out_time'] = $check_out_time->getTimestamp() - strtotime( 'today' );
			}
		} else {
			unset( $data['check_in_time'] );
			unset( $data['check_out_time'] );
		}

		$data['booking_vendor'] = ! empty( $data['apartment_id'] ) ? jet_abaf()->vendors->get_vendor( $data['apartment_id'] ) : null;

		return $data;

	}

	/**
	 * Update table info.
	 *
	 * Updates a booking record in the database on the provided data and conditions.
	 *
	 * @since 3.7.0
	 *
	 * @param array $data  New data to update.
	 * @param array $where Update identification.
	 *
	 * @return void
	 */
	public function update( $data = [], $where = [] ) {

		$old_data = [];

		if ( ! empty( $where['booking_id'] ) ) {
			$old_data = jet_abaf()->db->query( $where );

			if ( ! empty( $old_data ) ) {
				$old_data = $old_data[0];
				$data     = array_merge( $old_data, $data );
			}
		}

		$data = $this->sanitize_data_before_db( $data );

		$this->wpdb()->update( $this->table(), $data, $where );

		do_action( 'jet-booking/db/update/bookings', $data, $where, $old_data );

		if ( isset( $data['status'] ) ) {
			$status     = $data['status'];
			$old_status = $old_data['status'] ?: false;

			if ( $status !== $old_status ) {
				do_action( 'jet-booking/db/update/bookings/status', $data );
			}
		}

	}

}
