<?php

namespace JET_ABAF\Components;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Manager {

	/**
	 * Components list.
	 *
	 * @var array
	 */
	private $_components = [];

	public function __construct() {
		add_action( 'init', [ $this, 'register_components' ], - 2 );
		add_action( 'init', [ $this, 'init_components' ], - 1 );
	}

	/**
	 * Register components.
	 *
	 * Register components before run init to allow unregister before init.
	 *
	 * @since 3.1.0
	 * @since 3.2.0 Elementor & Blocks components added.
	 */
	public function register_components() {

		$components = [
			'blocks_views'    => __NAMESPACE__ . '\Blocks_Views\Manager',
			'bricks_views'    => __NAMESPACE__ . '\Bricks_Views\Manager',
			'elementor_views' => __NAMESPACE__ . '\Elementor_Views\Manager'
		];

		foreach ( $components as $component_slug => $component_class ) {
			$this->register_component( $component_slug, $component_class );
		}

	}

	/**
	 * Init components.
	 *
	 * Initialize main components.
	 *
	 * @since 3.1.0
	 */
	public function init_components() {
		foreach ( $this->_components as $slug => $class ) {
			jet_abaf()->$slug = new $class();
		}
	}

	/**
	 * Register component.
	 *
	 * Register plugin component.
	 *
	 * @since 3.1.0
	 *
	 * @param string $slug  Component slug
	 * @param string $class Component class
	 */
	public function register_component( $slug = '', $class = '' ) {
		$this->_components[ $slug ] = $class;
	}

}