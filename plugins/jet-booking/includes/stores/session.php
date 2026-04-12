<?php

namespace JET_ABAF\Stores;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Session extends Base {

	public function type_id() {
		return 'session';
	}

	public function set( $key, $value ) {

		$this->start_session();

		if ( empty( $_SESSION[ $this->key ] ) ) {
			$_SESSION[ $this->key ] = [];
		}

		$_SESSION[ $this->key ][ $key ] = $value;

	}

	public function get( $key ) {

		$this->start_session();

		if ( empty( $_SESSION[ $this->key ] ) ) {
			$_SESSION[ $this->key ] = [];
		}

		return ! empty( $_SESSION[ $this->key ][ $key ] ) ? sanitize_text_field( $_SESSION[ $this->key ][ $key ] ) : '';

	}

	public function on_init() {
		add_action( 'parse_request', [ $this, 'init_session' ] );
	}

	/**
	 * Initialize session.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function init_session() {
		$this->start_session();
	}

	/**
	 * Start session.
	 *
	 * Maybe start session.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function start_session() {

		if ( headers_sent() ) {
			return;
		}

		if ( ! session_id() ) {
			session_start();
		}

	}

}