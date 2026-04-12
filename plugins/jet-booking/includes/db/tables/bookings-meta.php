<?php

namespace JET_ABAF\DB\Tables;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Bookings_Meta extends Base {

	/**
	 * Return table slug.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function table_slug() {
		return 'bookings_meta';
	}

	/**
	 * Schema.
	 *
	 * Returns bookings meta table columns schema.
	 *
	 * @since  3.3.0
	 *
	 * @return string[]
	 */
	public function schema() {
		return [
			'ID'         => 'bigint(20) NOT NULL AUTO_INCREMENT',
			'booking_id' => 'bigint(20)',
			'meta_key'   => 'text',
			'meta_value' => 'longtext',
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

		$default_columns = $this->schema();
		$columns_schema  = '';

		foreach ( $default_columns as $column => $desc ) {
			$columns_schema .= $column . ' ' . $desc . ',';
		}

		$table           = $this->table();
		$charset_collate = $this->wpdb()->get_charset_collate();

		return "CREATE TABLE $table ( $columns_schema PRIMARY KEY (ID) ) $charset_collate;";

	}

	/**
	 * Sets a meta for a booking.
	 *
	 * @since  3.3.0
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections. Improved performance.
	 *
	 * @param int|string $booking_id Booking ID.
	 * @param string     $meta_key   Booking meta key name.
	 * @param mixed      $meta_value Booking meta value.
	 */
	public function set_meta( $booking_id, $meta_key, $meta_value ) {

		$id = $this->wpdb()->get_var(
			$this->wpdb()->prepare(
				"SELECT ID FROM %i WHERE booking_id = %d AND meta_key = %s LIMIT 1",
				$this->table(),
				absint( $booking_id ),
				$meta_key
			)
		);

		if ( $id ) {
			$this->update( [ 'meta_value' => $meta_value ], [ 'ID' => $id ] );
		} else {
			$this->insert( [
				'booking_id' => $booking_id,
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value,
			] );
		}

	}

	/**
	 * Gets a meta for a booking.
	 *
	 * @since 3.3.0
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections. Improved performance.
	 *
	 * @param int|string $booking_id Booking ID.
	 * @param string     $meta_key   Booking meta key name.
	 *
	 * @return false
	 */
	public function get_meta( $booking_id, $meta_key ) {

		$meta_value = $this->wpdb()->get_var(
			$this->wpdb()->prepare(
				"SELECT meta_value FROM %i WHERE booking_id = %d AND meta_key = %s LIMIT 1",
				$this->table(),
				absint( $booking_id ),
				$meta_key
			)
		);

		return $meta_value ?? false;

	}

}
