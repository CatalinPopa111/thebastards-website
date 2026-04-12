<?php

namespace JET_ABAF\DB;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Bookings db table instance holder.
	 *
	 * @var Tables\Bookings
	 */
	public $bookings;

	/**
	 * Bookings meta db table instance holder.
	 *
	 * @var Tables\Bookings_Meta
	 */
	public $bookings_meta;

	/**
	 * Units db table instance holder.
	 *
	 * @var Tables\Units
	 */
	public $units;

	/**
	 * Stores latest inserted booking item.
	 *
	 * @var array
	 */
	public $inserted_booking = false;

	/**
	 * Stores latest queried result to use it.
	 *
	 * @var null
	 */
	public $latest_result = null;

	public function __construct() {

		$this->bookings      = new Tables\Bookings();
		$this->bookings_meta = new Tables\Bookings_Meta();
		$this->units         = new Tables\Units();

		// Check available unit count for booking instance post type action.
		add_action( 'wp_ajax_jet_booking_check_available_units_count', [ $this, 'check_available_units_count' ] );
		add_action( 'wp_ajax_nopriv_jet_booking_check_available_units_count', [ $this, 'check_available_units_count' ] );

	}

	/**
	 * Install table.
	 *
	 * Try to recreate DB tables by request.
	 *
	 * @since 3.7.2 Added booking meta table.
	 *
	 * @return void
	 */
	public function install_table() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->bookings->install_table();
		$this->bookings_meta->install_table();
		$this->units->install_table();

	}

	/**
	 * Returns WPDB instance.
	 *
	 * @return \QM_DB|\wpdb
	 */
	public static function wpdb() {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * Returns bookings table name.
	 *
	 * @since  3.3.0 Refactored.
	 *
	 * @return string
	 */
	public static function bookings_table() {
		return jet_abaf()->db->bookings->table();
	}

	/**
	 * Returns units table name.
	 *
	 * @since  3.3.0 Refactored.
	 *
	 * @return string
	 */
	public static function units_table() {
		return jet_abaf()->db->units->table();
	}

	/**
	 * Check if booking table already exists.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return boolean
	 */
	public function is_bookings_table_exists() {
		return $this->bookings->is_table_exists();
	}

	/**
	 * Check if units table already exists.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return boolean
	 */
	public function is_units_table_exists() {
		return $this->units->is_table_exists();
	}

	/**
	 * Check if all required DB tables are exists.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return boolean
	 */
	public function tables_exists() {
		return $this->bookings->is_table_exists() && $this->units->is_table_exists() && $this->bookings_meta->is_table_exists();
	}

	/**
	 * Get default fields.
	 *
	 * Returns default database fields list.
	 *
	 * @since   2.8.0 Added `order_id`, `import_id` fields.
	 * @since   3.3.0 Added `user_id` field.
	 * @since   3.7.0 Added `user_email`, `check_in_time` and `check_out_time` fields.
	 * @since   4.0.0 Refactored.
	 * @access  public
	 *
	 * @return string[]
	 */
	public function get_default_fields() {
		return array_keys( $this->bookings->schema() );
	}

	/**
	 * Returns additional DB fields.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return array
	 */
	public function get_additional_db_columns() {
		return apply_filters( 'jet-abaf/db/additional-db-columns', [] );
	}

	/**
	 * Create booking table.
	 *
	 * Create database table for tracked information.
	 *
	 * @since  2.8.0 Refactored
	 * @since  3.3.0 Refactored
	 */
	public function create_bookings_table( $delete_if_exists = false ) {
		$this->bookings->create_table( $delete_if_exists );
	}

	/**
	 * Create units table.
	 *
	 * Create database table for tracked information.
	 *
	 * @since  3.3.0 Refactored
	 */
	public function create_units_table( $delete_if_exists = false ) {
		$this->units->create_table( $delete_if_exists );
	}

	/**
	 * Get initial apartment id.
	 *
	 * Returns initial booking apartment ID.
	 *
	 * @since  2.5.5
	 *
	 * @param int|string $id Apartment post type ID.
	 *
	 * @return mixed|void
	 */
	public function get_initial_booking_item_id( $id ) {
		return apply_filters( 'jet-abaf/db/initial-apartment-id', $id );
	}

	/**
	 * Get apartment units.
	 *
	 * Returns all available units for an apartment.
	 *
	 * @since 3.8.5 Added initial apartment ID handling.
	 *
	 * @param int|string $apartment_id Booking instance post-type ID.
	 *
	 * @return mixed
	 */
	public function get_apartment_units( $apartment_id ) {
		$apartment_id = $this->get_initial_booking_item_id( $apartment_id );

		return $this->query( [ 'apartment_id' => $apartment_id ], $this->units->table() );
	}

	/**
	 * Get booked units.
	 *
	 * Return list of apartment booked units for passed dates.
	 *
	 * @since  2.5.2
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 * @since  4.0.4 Fixed invalid and temporary statuses handling.
	 *
	 * @param array $booking Bookings parameters.
	 *
	 * @return array
	 */
	public function get_booked_units( $booking ) {

		$table        = $this->bookings->table();
		$apartment_id = absint( $booking['apartment_id'] );
		$from         = absint( $booking['check_in_date'] );
		$to           = absint( $booking['check_out_date'] );

		$booked_units = self::wpdb()->get_results(
			self::wpdb()->prepare( "
				SELECT *
				FROM {$table}
				WHERE `apartment_id` = %d
				AND (
					( `check_in_date` >= %d AND `check_in_date` <= %d )
					OR ( `check_out_date` > %d AND `check_out_date` <= %d )
					OR ( `check_in_date` < %d AND `check_out_date` >= %d )
				)
			", $apartment_id, $from, $to, $from, $to, $from, $to ),
			ARRAY_A
		);

		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();

		$booked_units = array_filter( $booked_units, function ( $unit ) use ( $skip_statuses ) {
			return ! in_array( $unit['status'], $skip_statuses );
		} );

		return array_values( $booked_units );

	}

	/**
	 * Get available unit.
	 *
	 * Returns available unit for passed dates.
	 *
	 * @since  1.0.0
	 * @since  2.5.2 Move some logic to `get_booked_units()`.
	 * @since  4.0.4 Better available unit handling.
	 *
	 * @param array $booking Bookings parameters.
	 *
	 * @return mixed|null
	 */
	public function get_available_unit( $booking ) {

		$all_units = $this->get_apartment_units( $booking['apartment_id'] );

		if ( empty( $all_units ) ) {
			return null;
		}

		$booked_units = $this->get_booked_units( $booking );

		if ( empty( $booked_units ) ) {
			return $all_units[0]['unit_id'];
		}

		$booked_unit_ids = array_column( $booked_units, 'apartment_unit' );

		foreach ( $all_units as $unit ) {
			$unit_id = absint( $unit['unit_id'] );

			if ( ! in_array( $unit_id, $booked_unit_ids ) ) {
				return $unit_id;
			}
		}

		return null;

	}

	/**
	 * Get booked items.
	 *
	 * Returns a list of booked items.
	 *
	 * @since  2.7.1
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param array $booking Bookings data.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_booked_items( $booking ) {

		$table        = $this->bookings->table();
		$apartment_id = absint( $booking['apartment_id'] );
		$from         = absint( $booking['check_in_date'] );
		$to           = absint( $booking['check_out_date'] );

		// Increase $from to 1 to avoid overlapping check-in and check-out dates.
		$from ++;

		$args  = [ $from, $to, $from, $to, $from, $to, $apartment_id ];
		$where = '';

		if ( ! empty( $booking['apartment_unit'] ) ) {
			$where  .= ' AND `apartment_unit` = %d';
			$args[] = absint( $booking['apartment_unit'] );
		}

		$booked = self::wpdb()->get_results(
			self::wpdb()->prepare( "
				SELECT *
				FROM {$table}
				WHERE (
					( `check_in_date` BETWEEN %d AND %d )
					OR ( `check_out_date` BETWEEN %d AND %d )
					OR ( `check_in_date` <= %d AND `check_out_date` >= %d )
				) AND `apartment_id` = %d
				{$where}
			", $args ),
			ARRAY_A
		);

		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();

		foreach ( $booked as $index => $booking ) {
			if ( ! empty( $booking['status'] ) && in_array( $booking['status'], $skip_statuses ) ) {
				unset( $booked[ $index ] );
			}
		}

		return $booked;

	}

	/**
	 * Is booking dates available.
	 *
	 * Check if current booking dates is available.
	 *
	 * @since  2.7.1 Refactored.
	 *
	 * @param array         $booking    Booking data.
	 * @param number|string $booking_id Booking ID.
	 *
	 * @return boolean
	 */
	public function is_booking_dates_available( $booking = [], $booking_id = 0 ) {

		$booked = $this->get_booked_items( $booking );

		if ( empty( $booked ) ) {
			return true;
		}

		foreach ( $booked as $index => $booking ) {
			if ( absint( $booking['booking_id'] ) === absint( $booking_id ) ) {
				unset( $booked[ $index ] );
			}
		}

		if ( empty( $booked ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Insert booking.
	 *
	 * @since  2.1.0
	 * @since  2.5.5 Added additional `apartment_id` handling.
	 * @since  3.0.0 Fixed numeric column name handling.
	 * @since  3.7.0 Refactored.
	 * @since  4.0.0 Added `booking_vendor` handling.
	 *
	 * @param array $booking List of parameters.
	 *
	 * @return false|int
	 */
	public function insert_booking( $booking = [] ) {

		$booking['apartment_id']   = $this->get_initial_booking_item_id( $booking['apartment_id'] );
		$booking['booking_vendor'] = jet_abaf()->vendors->get_vendor( $booking['apartment_id'] );

		if ( $booking['check_in_date'] >= $booking['check_out_date'] ) {
			$booking['check_out_date'] = $booking['check_in_date'] + 12 * HOUR_IN_SECONDS;
		}

		$booking['check_in_date'] ++;

		if ( empty( $booking['apartment_unit'] ) ) {
			$booking['apartment_unit'] = $this->get_available_unit( $booking );
		}

		if ( isset( $booking['__guests'] ) ) {
			unset( $booking['__guests'] );
		}

		if ( isset( $booking['attributes'] ) ) {
			unset( $booking['attributes'] );
		}

		if ( ! $this->is_booking_dates_available( $booking ) ) {
			return false;
		}

		$booking_id = $this->bookings->insert( $booking );

		$booking['booking_id'] = $booking_id;

		$this->inserted_booking = $booking;

		// Trigger hook after booking inserted.
		do_action( 'jet-booking/db/booking-inserted', $booking );

		return $booking_id;

	}

	/**
	 * Update booking.
	 *
	 * Update booking information in database.
	 *
	 * @since  2.7.0 Added `'jet-booking/db/booking-updated'` hook.
	 *
	 * @param string|int $booking_id Booking ID.
	 * @param array      $data       Booking item data.
	 *
	 * @return void
	 */
	public function update_booking( $booking_id = 0, $data = [] ) {
		$this->bookings->update( $data, [ 'booking_id' => $booking_id ] );
		do_action( 'jet-booking/db/booking-updated', $booking_id );
	}

	/**
	 * Delete booking.
	 *
	 * Delete booking by passed parameters.
	 *
	 * @since  3.0.0 Added `'jet-booking/db/before-booking-delete'` hook.
	 * @since  3.3.0 Delete related meta.
	 *
	 * @param array $where Delete parameters.
	 *
	 * @return void
	 */
	public function delete_booking( $where = [] ) {

		do_action( 'jet-booking/db/before-booking-delete', $where );

		$bookings = $this->query( $where, $this->bookings->table() );

		if ( ! empty( $bookings ) ) {
			$this->bookings_meta->delete( [ 'booking_id' => $bookings[0]['booking_id'] ] );
		}

		$this->bookings->delete( $where );

	}

	/**
	 * Insert table columns.
	 *
	 * Insert new columns into the existing bookings table
	 *
	 * @since  3.0.0 Added backticks for numeric columns name handling.
	 * @since  3.3.0 Added column description handling.
	 * @since  3.7.0 Refactored.
	 *
	 * @param array $columns List of columns to insert.
	 *
	 * @return void
	 */
	public function insert_table_columns( $columns = [] ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $columns ) ) {
			return;
		}

		$table          = $this->bookings->table();
		$columns_schema = '';

		foreach ( $columns as $column => $desc ) {
			$desc           = $desc ?: 'text';
			$columns_schema .= "ADD `$column` $desc, ";
		}

		$columns_schema = rtrim( $columns_schema, ', ' );
		$query          = "ALTER TABLE `$table` $columns_schema;";

		self::wpdb()->query( $query );

	}

	/**
	 * Update the database with new columns.
	 *
	 * @param array $new_columns List of column names.
	 *
	 * @return false|void
	 */
	public function update_columns_diff( $new_columns = [] ) {

		$table   = $this->bookings->table();
		$columns = self::wpdb()->get_results( "SHOW COLUMNS FROM $table", ARRAY_A );

		if ( empty( $columns ) ) {
			return false;
		}

		$default_columns  = $this->get_default_fields();
		$existing_columns = [];

		foreach ( $columns as $column ) {
			if ( ! in_array( $column['Field'], $default_columns ) ) {
				$existing_columns[] = $column['Field'];
			}
		}

		if ( empty( $new_columns ) && empty( $existing_columns ) ) {
			return;
		}

		$to_delete = array_diff( $existing_columns, $new_columns );
		$to_add    = array_diff( $new_columns, $existing_columns );

		if ( ! empty( $to_delete ) ) {
			$this->delete_table_columns( $to_delete );
		}

		if ( ! empty( $to_add ) ) {
			$columns_to_add = [];

			foreach ( $to_add as $column ) {
				$columns_to_add[ $column ] = 'text';
			}

			$this->insert_table_columns( $columns_to_add );
		}

	}

	/**
	 * Delete table columns.
	 *
	 * Delete columns into the existing bookings table.
	 *
	 * @since  3.0.0 Added backticks for numeric columns name handling.
	 *
	 * @param array $columns List of columns to delete.
	 *
	 * @return void
	 */
	public function delete_table_columns( $columns ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$columns_schema = '';

		foreach ( $columns as $column ) {
			$columns_schema .= "DROP COLUMN `$column`, ";
		}

		$columns_schema = rtrim( $columns_schema, ', ' );
		$table          = $this->bookings->table();
		$sql            = "ALTER TABLE $table $columns_schema;";

		self::wpdb()->query( $sql );

	}

	/**
	 * Check if booking DB column is exists.
	 *
	 * @param string $column Column name.
	 *
	 * @return bool|int|\mysqli_result
	 */
	public function column_exists( $column ) {
		$table = $this->bookings->table();

		return self::wpdb()->query( "SHOW COLUMNS FROM `$table` LIKE '$column'" );
	}

	/**
	 * Check if booking already exists.
	 *
	 * @since 3.3.0 Fixed default parameter value.
	 *
	 * @param string $by_field Column name.
	 * @param null   $value    Column value.
	 *
	 * @return bool
	 */
	public function booking_exists( $by_field = 'booking_id', $value = null ) {
		$count = $this->count( [ $by_field => $value ] );

		return ! empty( $count );
	}

	/**
	 * Get future bookings.
	 *
	 * Returns future bookings for apartment ID (or all future bookings if apartment ID is not passed).
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @param int|string $apartment_id Booking instance post type ID.
	 *
	 * @return array|object
	 */
	public function get_future_bookings( $apartment_id = null ) {

		$args = [
			'date_query' => [
				[
					'column'   => 'check_out_date',
					'operator' => '>=',
					'value'    => 'today'
				]
			],
			'return'     => 'arrays'
		];

		if ( $apartment_id ) {
			$args['apartment_id'] = $apartment_id;
		}

		return jet_abaf_get_bookings( $args );

	}

	/**
	 * Returns booking details by passed field and value.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @param string $field Database column name.
	 * @param null   $value Database column value.
	 *
	 * @return false|mixed|\stdClass
	 */
	public function get_booking_by( $field = 'booking_id', $value = null ) {

		$bookings = $this->query( [ $field => $value ], $this->bookings->table() );

		if ( empty( $bookings ) ) {
			return false;
		}

		return reset( $bookings );

	}

	/**
	 * Get booked apartments.
	 *
	 * Get already booked apartments for passed dates.
	 *
	 * @since  1.0.0
	 * @since  2.5.2 Added compatibility with checkout only option.
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param string $from Booking start date.
	 * @param string $to   Booking end date.
	 *
	 * @return array
	 */
	public function get_booked_apartments( $from, $to ) {

		$table       = $this->bookings->table();
		$units_table = $this->units->table();

		// Increase $from to 1 to avoid overlapping check-in and check-out dates.
		$from ++;

		$skip_statuses       = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[]     = jet_abaf()->statuses->temporary_status();
		$status_placeholders = implode( ', ', array_fill( 0, count( $skip_statuses ), '%s' ) );

		$booked = self::wpdb()->get_results(
			self::wpdb()->prepare( "
				SELECT apartment_id AS `apartment_id`, count( * ) AS `units`, check_in_date AS `check_in_date`
				FROM {$table}
				WHERE ( 
				    ( `check_in_date` BETWEEN %d AND %d )
					OR ( `check_out_date` BETWEEN %d AND %d )
					OR ( `check_in_date` <= %d AND `check_out_date` >= %d ) 
				) AND `status` NOT IN ( {$status_placeholders} )
				GROUP BY apartment_id
			", array_merge( [ $from, $to, $from, $to, $from, $to ], $skip_statuses ) ),
			ARRAY_A
		);

		if ( empty( $booked ) ) {
			return [];
		}

		$available = self::wpdb()->get_results( "
			SELECT apartment_id AS `apartment_id`, count( * ) AS `units`
			FROM {$units_table}
			GROUP BY apartment_id
		", ARRAY_A );

		$available = ! empty( $available ) ? array_column( $available, 'units', 'apartment_id' ) : [];
		$result    = [];

		foreach ( $booked as $apartment ) {
			$apartment_id = $apartment['apartment_id'];

			if ( jet_abaf()->settings->checkout_only_allowed( $apartment_id ) ) {
				if ( date( 'Y-m-d', $to ) === date( 'Y-m-d', $apartment['check_in_date'] ) ) {
					$available[ $apartment_id ] = ! empty( $available[ $apartment_id ] ) ? $available[ $apartment_id ] : 1;
					$apartment['units']         = 0;
				}
			}

			if ( empty( $available[ $apartment_id ] ) ) {
				$result[] = $apartment_id;
			} else {
				$booked          = absint( $apartment['units'] );
				$available_units = absint( $available[ $apartment_id ] );

				if ( $booked >= $available_units ) {
					$result[] = $apartment_id;
				}
			}
		}

		return $result;

	}

	/**
	 * Booking availability.
	 *
	 * Check if is booking instance available for new bookings.
	 *
	 * @since  2.5.0
	 * @since  2.7.1 Refactored.
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param array         $booking    Booking data.
	 * @param number|string $booking_id Booking ID.
	 *
	 * @return bool
	 */
	public function booking_availability( $booking = [], $booking_id = 0 ) {

		$booked = $this->get_booked_items( $booking );

		if ( empty( $booked ) ) {
			return true;
		}

		$this->latest_result = $booked;

		$units_table    = $this->units->table();
		$apartment_id   = absint( $booking['apartment_id'] );
		$apartment_unit = isset( $booking['apartment_unit'] ) ? absint( $booking['apartment_unit'] ) : null;
		$count          = 0;
		$booked_units   = [];

		foreach ( $booked as $item ) {
			if ( absint( $item['booking_id'] ) === absint( $booking_id ) || in_array( absint( $item['apartment_unit'] ), $booked_units ) ) {
				continue;
			}

			if ( absint( $item['apartment_unit'] ) === $apartment_unit ) {
				return false;
			}

			$booked_units[] = absint( $item['apartment_unit'] );
			$count ++;
		}

		$available = self::wpdb()->get_results(
			self::wpdb()->prepare( "
				SELECT apartment_id AS `apartment_id`, count( * ) AS `units`
				FROM {$units_table}
				WHERE `apartment_id` = %d
				GROUP BY apartment_id;
			", $apartment_id ),
			ARRAY_A
		);

		if ( empty( $available ) && 0 < $count ) {
			return false;
		}

		if ( empty( $available ) && 0 === $count ) {
			return true;
		}

		if ( $count >= absint( $available[0]['units'] ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Update unit.
	 *
	 * @param int|string $unit_id Booking unit ID.
	 * @param array      $data    Data to update
	 */
	public function update_unit( $unit_id, $data ) {
		$this->units->update( $data, [ 'unit_id' => $unit_id ] );
	}

	/**
	 * Delete unit by passed parameters.
	 *
	 * @param array $where Delete parameters.
	 */
	public function delete_unit( $where = [] ) {
		$this->units->delete( $where );
	}

	/**
	 * Returns all available units for apartment.
	 *
	 * @param int|string $apartment_id Booking instance post type ID.
	 * @param int|string $unit_id      Booking instance post type unit ID.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_apartment_unit( $apartment_id, $unit_id ) {
		return $this->query( [ 'apartment_id' => $apartment_id, 'unit_id' => $unit_id, ], $this->units->table() );
	}

	/**
	 * Get available units.
	 *
	 * Returns the list of available units for passed/selected dates.
	 *
	 * @since  2.5.2
	 * @since  4.0.4 Better available units handling.
	 *
	 * @param array $booking Booking parameters list.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_available_units( $booking ) {

		$all_units = $this->get_apartment_units( $booking['apartment_id'] );

		if ( empty( $all_units ) ) {
			return null;
		}

		$booked_units = $this->get_booked_units( $booking );

		if ( empty( $booked_units ) ) {
			return $all_units;
		}

		$booked_unit_ids = array_column( $booked_units, 'apartment_unit' );

		$available_units = array_filter( $all_units, function ( $unit ) use ( $booked_unit_ids ) {
			return ! in_array( absint( $unit['unit_id'] ), $booked_unit_ids, true );
		} );

		return array_values( $available_units );

	}

	/**
	 * Check available units count.
	 *
	 * Check available units count for passed/selected dates.
	 *
	 * @since  2.5.2
	 * @since  3.2.1 Refactored.
	 * @since  3.8.3 Refactored.
	 */
	public function check_available_units_count() {

		// phpcs:disable WordPress.Security.NonceVerification
		$booking = ! empty( $_POST['booking'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_POST['booking'] ) ) : []; // phpcs:ignore
		$type    = ! empty( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'available';
		// phpcs:enable WordPress.Security.NonceVerification

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $booking['apartment_id'], 'jet-booking', 60 );

		wp_send_json_success( [ 'count' => $this->get_units_count( $booking, $type ) ] );

	}

	/**
	 * Get available units count.
	 *
	 * Calculate and return the count of available units.
	 *
	 * @since 3.8.3
	 * @since 4.0.1 Fixed incorrect calculation of available units count.
	 *
	 * @param array  $booking An associative array containing booking details.
	 * @param string $type    Units type to count.
	 *
	 * @return int The number of available units.
	 */
	public function get_units_count( $booking, $type = 'available' ) {

		$units = $this->get_apartment_units( $booking['apartment_id'] );

		if ( empty( $units ) || empty( $booking['check_in_date'] ) || empty( $booking['check_out_date'] ) ) {
			return 0;
		}

		if ( $booking['check_in_date'] === $booking['check_out_date'] ) {
			$booking['check_out_date'] += 12 * HOUR_IN_SECONDS;
		}

		$booking['check_out_date'] ++;

		if ( jet_abaf()->settings->is_per_nights_booking( $booking['apartment_id'] ) ) {
			$booking['check_in_date'] ++;
		}

		if ( jet_abaf()->settings->checkout_only_allowed( $booking['apartment_id'] ) ) {
			$booking['check_out_date'] --;
		}

		$booked_units = $this->get_booked_units( $booking );

		if ( 'booked' === $type ) {
			return empty( $booked_units ) ? 0 : count( $booked_units );
		}

		return count( $this->get_available_units( $booking ) );

	}

	/**
	 * Prepare params.
	 *
	 * Return a prepared list of parameters to use in a query.
	 *
	 * @since  3.2.0
	 * @since  4.0.0 Updated booking period filter handling.
	 * @access public
	 *
	 * @param array $params List of query parameters.
	 *
	 * @return array
	 */
	public function prepare_params( $params ) {

		$mode = ! empty( $params['mode'] ) ? $params['mode'] : 'all';
		$view = ! empty( $params['view'] ) ? $params['view'] : 'list';
		$args = ! empty( $params['filters'] ) ? json_decode( $params['filters'], true ) : [];
		$args = ! empty( $args ) && is_array( $args ) ? array_filter( $args ) : [];
		$sort = ! empty( $params['sort'] ) ? json_decode( $params['sort'], true ) : [];

		$args['limit']     = ! empty( $params['per_page'] ) ? absint( $params['per_page'] ) : 0;
		$args['offset']    = ! empty( $params['offset'] ) ? absint( $params['offset'] ) : 0;
		$args['sorting'][] = ! empty( $sort ) && is_array( $sort ) ? array_filter( $sort ) : [
			'orderby' => 'booking_id',
			'order'   => 'DESC',
		];

		switch ( $mode ) {
			case 'upcoming':
				$args['date_query'][] = [
					'column'   => 'check_in_date',
					'operator' => '>=',
					'value'    => 'today'
				];

				break;

			case 'past':
				$args['date_query'][] = [
					'column'   => 'check_in_date',
					'operator' => '<',
					'value'    => 'today'
				];

				break;
		}

		if ( ! empty( $args['check_in_date'] ) && ! empty( $args['check_out_date'] ) ) {
			$args['check_out_date'] = jet_abaf()->tools->is_valid_timestamp( $args['check_out_date'] ) ? $args['check_out_date'] : strtotime( $args['check_out_date'] );

			$args['date_query']['relation'] = 'OR';
			$args['date_query'][]           = [
				'column'   => 'check_in_date',
				'operator' => 'BETWEEN',
				'value'    => [ $args['check_in_date'], $args['check_out_date'] ],
			];
			$args['date_query'][]           = [
				'column'   => 'check_out_date',
				'operator' => 'BETWEEN',
				'value'    => [
					$args['check_in_date'],
					$args['check_out_date'] + 12 * HOUR_IN_SECONDS
				],
			];

			unset( $args['check_in_date'] );
			unset( $args['check_out_date'] );
		}

		if ( 'list' === $view ) {
			if ( ! empty( $args['check_in_date'] ) ) {
				$args['date_query'][] = [
					'column'   => 'check_in_date',
					'operator' => '=',
					'value'    => $args['check_in_date']
				];

				unset( $args['check_in_date'] );
			}

			if ( ! empty( $args['check_out_date'] ) ) {
				$args['date_query']['relation'] = 'OR';

				$args['date_query'][] = [
					'column'   => 'check_out_date',
					'operator' => '=',
					'value'    => $args['check_out_date']
				];
				$args['date_query'][] = [
					'column'   => 'check_out_date',
					'operator' => '=',
					'value'    => strtotime( $args['check_out_date'] ) + 12 * HOUR_IN_SECONDS
				];

				unset( $args['check_out_date'] );
			}
		}

		if ( ! empty( $args['date'] ) ) {
			$args['meta_query']['relation'] = 'OR';
			$args['meta_query'][]           = [
				'column'   => $args['date'],
				'operator' => 'BETWEEN',
				'value'    => [ 'check_in_date', 'check_out_date' ],
			];
			$args['meta_query'][]           = [
				'column'   => $args['date'] + 1,
				'operator' => 'BETWEEN',
				'value'    => [ 'check_in_date', 'check_out_date' ],
			];
		}

		return apply_filters( 'jet-booking/db/query/prepared-params', $args, $params );

	}

	/**
	 * Add nested query arguments.
	 *
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param string  $key    Column name.
	 * @param array   $value  Compared value.
	 * @param boolean $format Data format.
	 *
	 * @return string
	 */
	public function get_sub_query( $key = '', $value = [], $format = false ) {

		if ( ! $format ) {
			if ( false !== strpos( $key, '!' ) ) {
				$key    = ltrim( $key, '!' );
				$format = '%1$i != %2$s';
			} else {
				$format = '%1$i = %2$s';
			}
		}

		$query = '';
		$glue  = '';

		foreach ( $value as $child ) {
			$query .= $glue;
			$query .= self::wpdb()->prepare( $format, $key, $child );
			$glue  = ' OR ';
		}

		return $query;

	}

	/**
	 * Add where args.
	 *
	 * Add where arguments to query.
	 *
	 * @since  2.8.0 Added new arguments handling for `>=` & `<=`.
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param array  $args Query arguments.
	 * @param string $rel  Query relation.
	 *
	 * @return string
	 */
	public function add_where_args( $args = [], $rel = 'AND' ) {

		$query = '';

		if ( ! empty( $args ) ) {
			$query .= ' WHERE ';
			$glue  = '';

			foreach ( $args as $key => $value ) {
				// Default format: %1$i handles the column name, %2$s handles the string value.
				$format = '%1$i = %2$s';
				$query  .= $glue;

				// Parse operators and update the format string.
				if ( false !== strpos( $key, '!' ) ) {
					$key    = ltrim( $key, '!' );
					$format = '%1$i != %2$s';
				} elseif ( false !== strpos( $key, '>=' ) ) {
					$key    = rtrim( $key, '>=' );
					$format = '%1$i >= %2$d';
				} elseif ( false !== strpos( $key, '>' ) ) {
					$key    = rtrim( $key, '>' );
					$format = '%1$i > %2$d';
				} elseif ( false !== strpos( $key, '<=' ) ) {
					$key    = rtrim( $key, '<=' );
					$format = '%1$i <= %2$d';
				} elseif ( false !== strpos( $key, '<' ) ) {
					$key    = rtrim( $key, '<' );
					$format = '%1$i < %2$d';
				}

				if ( is_array( $value ) ) {
					$query .= '( ' . $this->get_sub_query( $key, $value, $format ) . ' )';
				} else {
					$query .= self::wpdb()->prepare( $format, $key, $value );
				}

				$glue = ' ' . $rel . ' ';
			}
		}

		return $query;

	}

	/**
	 * Add order arguments to the query.
	 *
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param array $args Query order arguments.
	 *
	 * @return string
	 */
	public function add_order_args( $args = [] ) {

		$query = '';

		if ( ! empty( $args['orderby'] ) ) {
			$order = ! empty( $args['order'] ) ? $args['order'] : 'DESC';

			if ( ! in_array( strtoupper( $order ), [ 'ASC', 'DESC' ], true ) ) {
				$order = 'DESC';
			}

			$query .= self::wpdb()->prepare( " ORDER BY %i $order", $args['orderby'] );
		}

		return $query;

	}

	/**
	 * Return count of queried items.
	 *
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param array  $args List of query arguments.
	 * @param string $rel  Query relation.
	 *
	 * @return string|null
	 */
	public function count( $args = [], $rel = 'AND' ) {

		if ( ! in_array( strtoupper( $rel ), [ 'AND', 'OR' ], true ) ) {
			$rel = 'AND';
		}

		$query = self::wpdb()->prepare( "SELECT count(*) FROM %i", $this->bookings->table() );

		if ( isset( $args['after'] ) ) {
			$args['ID>'] = $args['after'];
			unset( $args['after'] );
		}

		if ( isset( $args['before'] ) ) {
			$args['ID<'] = $args['before'];
			unset( $args['before'] );
		}

		$query .= $this->add_where_args( $args, $rel );

		return self::wpdb()->get_var( $query );

	}

	/**
	 * Query.
	 *
	 * Query data from db table.
	 *
	 * @since  2.0.0
	 * @since  3.0.0 Check for bookings table existence.
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @param array  $args   List of query arguments.
	 * @param null   $table  Queried table name.
	 * @param int    $limit  Result limit number.
	 * @param int    $offset Result offset number.
	 * @param array  $order  List of query order options.
	 * @param string $rel    Arguments relation.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function query( $args = [], $table = null, $limit = 0, $offset = 0, $order = [], $rel = 'AND' ) {

		if ( ! $this->tables_exists() ) {
			return [];
		}

		if ( ! in_array( strtoupper( $rel ), [ 'AND', 'OR' ], true ) ) {
			$rel = 'AND';
		}

		if ( ! $table ) {
			$table = $this->bookings->table();
		}

		$query = self::wpdb()->prepare( "SELECT * FROM %i", $table );

		if ( isset( $args['after'] ) ) {
			$args['ID>'] = $args['after'];
			unset( $args['after'] );
		}

		if ( isset( $args['before'] ) ) {
			$args['ID<'] = $args['before'];
			unset( $args['before'] );
		}

		$query .= $this->add_where_args( $args, $rel );
		$query .= $this->add_order_args( $order );

		if ( intval( $limit ) > 0 ) {
			$query .= self::wpdb()->prepare( " LIMIT %d, %d", absint( $offset ), absint( $limit ) );
		}

		return self::wpdb()->get_results( $query, ARRAY_A );

	}

}
