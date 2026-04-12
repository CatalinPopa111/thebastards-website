<?php

namespace JET_ABAF\Stores;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Manager {

	/**
	 * Store types.
	 *
	 * Store types holder.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @var null
	 */
	private $stores = [];

	public function __construct() {
		$this->register_store_types();
	}

	/**
	 * Register store types.
	 *
	 * Register all available store types.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_store_types() {

		require JET_ABAF_PATH . 'includes/stores/session.php';
		require JET_ABAF_PATH . 'includes/stores/cookies.php';

		$this->register_store_type( new Session() );
		$this->register_store_type( new Cookies() );

	}

	/**
	 * Register store type.
	 *
	 * Register store type instance for global usage.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_store_type( $type_instance ) {
		$this->stores[ $type_instance->type_id() ] = $type_instance;
	}

	/**
	 * Get stores.
	 *
	 * Returns all available stores.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return null
	 */
	public function get_stores() {
		return $this->stores;
	}

	/**
	 * Get store.
	 *
	 * Return store instance by store slug.
	 */
	public function get_store( $store ) {
		return $this->stores[ $store ] ?? false;
	}

}
