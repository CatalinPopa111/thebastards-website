<?php

namespace JET_ABAF\Stores;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Cookies extends Base {

	public function type_id() {
		return 'cookies';
	}

	public function set( $key, $value ) {

		if ( headers_sent() ) {
			return;
		}

		$name = $this->key . '_' . $key;

		setcookie(
			$name,
			$value,
			time() + YEAR_IN_SECONDS,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			( false !== strstr( get_option( 'home' ), 'https:' ) && is_ssl() ),
			true
		);

		$_COOKIE[ $name ] = $value;

	}

	public function get( $key ) {

		$name = $this->key . '_' . $key;

		if ( empty( $_COOKIE[ $name ] ) ) {
			$_COOKIE[ $name ] = '';
		}

		return isset( $_COOKIE[ $name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) : false;

	}

}
