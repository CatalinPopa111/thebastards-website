<?php

namespace JET_ABAF\DB\Tables;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

abstract class Base {

	/**
	 * Check if DB table already exists.
	 *
	 * @var bool
	 */
	public $table_exists = null;

	public function __construct() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			add_action( 'init', [ $this, 'install_table' ] );
		}
	}

	/**
	 * Return table slug.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function table_slug() {
		return '';
	}

	/**
	 * Returns WPDB instance.
	 *
	 * @since 3.3.0
	 *
	 * @return \QM_DB|\wpdb
	 */
	public function wpdb() {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * Returns table name.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function table() {
		return $this->wpdb()->prefix . 'jet_' . $this->table_slug();
	}

	/**
	 * Returns table schema.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_table_schema() {
		return '';
	}

	/**
	 * Check if table already exists.
	 *
	 * @since 3.3.0
	 *
	 * @return boolean
	 */
	public function is_table_exists() {

		if ( null !== $this->table_exists ) {
			return $this->table_exists;
		}

		$table = $this->table();

		if ( $table === $this->wpdb()->get_var( "SHOW TABLES LIKE '$table'" ) ) {
			$this->table_exists = true;
		} else {
			$this->table_exists = false;
		}

		return $this->table_exists;

	}

	/**
	 * Create DB table.
	 *
	 * @since 3.3.0
	 *
	 * @param boolean $delete_if_exists Recreation status.
	 */
	public function create_table( $delete_if_exists = false ) {

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		if ( $delete_if_exists && $this->is_table_exists() ) {
			$table = $this->table();
			$this->wpdb()->query( "DROP TABLE $table;" );
			$this->table_exists = null;
		}

		if ( $this->is_table_exists() ) {
			return;
		}

		$sql = $this->get_table_schema();

		dbDelta( $sql );

	}

	/**
	 * Allow child classes do own sanitize of the data before write it into DB.
	 *
	 * @since 3.3.0
	 *
	 * @param array $data Table data.
	 *
	 * @return array|mixed
	 */
	public function sanitize_data_before_db( $data = [] ) {
		return $data;
	}

	/**
	 * Insert data into database table.
	 *
	 * @since 3.3.0
	 *
	 * @param array $data Data to insert.
	 *
	 * @return false|int
	 */
	public function insert( $data = [] ) {

		$data     = $this->sanitize_data_before_db( $data );
		$inserted = $this->wpdb()->insert( $this->table(), $data );

		return $inserted ? $this->wpdb()->insert_id : false;

	}

	/**
	 * Update table info.
	 *
	 * This function updates existing data in the database table based on the provided conditions.
	 *
	 * @since 3.3.0
	 *
	 * @param array $data  New data to update.
	 * @param array $where Update identification.
	 *
	 * @return void
	 */
	public function update( $data = [], $where = [] ) {

		$data = $this->sanitize_data_before_db( $data );

		$this->wpdb()->update( $this->table(), $data, $where );

		do_action( 'jet-booking/db/update/' . $this->table_slug(), $data, $where );

	}

	/**
	 * Delete table data.
	 *
	 * @since 3.3.0
	 *
	 * @param array $where Delete identification.
	 */
	public function delete( $where = [] ) {
		$this->wpdb()->delete( $this->table(), $where );
	}

	/**
	 * Install tables.
	 *
	 * Try to recreate DB table by request.
	 *
	 * @since 3.3.0
	 */
	public function install_table() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->create_table();

	}

}