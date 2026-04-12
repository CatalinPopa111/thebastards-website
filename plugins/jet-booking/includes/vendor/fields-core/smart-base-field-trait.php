<?php

namespace JET_ABAF\Vendor\Fields_Core;

trait Smart_Base_Field_Trait {

	protected $_args;
	protected $_builder;

	abstract public function field_template();

	abstract public function isRequired();

	abstract public function getNamespace();

	public function scopeClass( $suffix = '' ) {
		return $this->getNamespace() . $suffix;
	}

	public function isNotEmptyArg( $key ) {
		return ( ! empty( $this->_args[ $key ] ) );
	}

	public function getArgs( $key = '', $ifNotExist = false, $wrap_callable = false ) {
		if ( ! $key ) {
			return $this->_args;
		}
		if ( ! $wrap_callable ) {
			$wrap_callable = function ( $value ) {
				return $value;
			};
		}

		return ! empty( $this->_args[ $key ] ) ? call_user_func( $wrap_callable, $this->_args[ $key ] ) : $ifNotExist;
	}

	abstract public function getCustomTemplate( $provider_id, $args );

	public function is_block_editor() {
		$action = ! empty( $_GET['context'] ) ? $_GET['context'] : '';

		if ( isset( $_GET['action'] ) ) {
			$action = $action ? $action : $_GET['action'];
		}

		return in_array( $action, array( 'add', 'edit' ) );
	}

	abstract public function get_queried_post_id();

}