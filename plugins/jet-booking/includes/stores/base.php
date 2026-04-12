<?php

namespace JET_ABAF\Stores;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Base {

	/**
	 * Key.
	 *
	 * Stores key name holder.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @var string
	 */
	public $key = 'jet_booking';

	public function __construct() {
		$this->on_init();
	}

	/**
	 * Type ID.
	 *
	 * Returns store type ID.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return string
	 */
	abstract public function type_id();

	/**
	 * Set.
	 *
	 * Get data for store.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string $key   Store key name.
	 * @param string $value Store data.
	 *
	 * @return string
	 */
	abstract public function set( $key, $value );

	/**
	 * Get.
	 *
	 * Get data from store.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string $key Store key name.
	 *
	 * @return string
	 */
	abstract public function get( $key );

	/**
	 * On init.
	 *
	 * Store-specific initialization actions.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function on_init() {}

}
