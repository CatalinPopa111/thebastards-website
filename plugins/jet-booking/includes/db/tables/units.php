<?php

namespace JET_ABAF\DB\Tables;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Units extends Base {

	/**
	 * Return table slug.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function table_slug() {
		return 'apartment_units';
	}

	/**
	 * Schema.
	 *
	 * Returns units table columns schema.
	 *
	 * @since  3.3.0
	 *
	 * @return string[]
	 */
	public function schema() {
		return [
			'unit_id'      => "bigint(20) NOT NULL AUTO_INCREMENT",
			'apartment_id' => "bigint(20)",
			'unit_title'   => "text",
			'notes'        => "text",
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
			$columns_schema .= "$column $desc,";
		}

		$charset_collate = $this->wpdb()->get_charset_collate();
		$table           = $this->table();

		return "CREATE TABLE $table ( $columns_schema PRIMARY KEY ( unit_id ) ) $charset_collate;";

	}

}