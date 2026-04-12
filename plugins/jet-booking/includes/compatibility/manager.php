<?php

namespace JET_ABAF\Compatibility;

defined( 'ABSPATH' ) || exit;

class Manager {

	public function __construct() {
		$this->load_compatibility_packages();
	}

	/**
	 * Load compatibility packages.
	 *
	 * Includes all available compatibility packages for provided plugins.
	 *
	 * @since  2.5.5
	 * @since  2.6.3 Added Polylang compatibility package.
	 * @since  3.1.0 Added JetEngine & Jet_Smart_Filters compatibility packages.
	 * @access public
	 *
	 * @return void
	 */
	public function load_compatibility_packages() {

		$packages_list = [
			'jet-engine/jet-engine.php' => [
				'cb'   => 'class_exists',
				'args' => 'Jet_Engine',
			],
			'jet-smart-filters.php'     => [
				'cb'   => 'class_exists',
				'args' => 'Jet_Smart_Filters',
			],
			'polylang.php'              => [
				'cb'   => 'class_exists',
				'args' => 'Polylang',
			],
			'wpml.php'                  => [
				'cb'   => 'defined',
				'args' => 'WPML_ST_VERSION',
			],
		];

		foreach ( $packages_list as $file => $condition ) {
			if ( true === call_user_func( $condition['cb'], $condition['args'] ) ) {
				require JET_ABAF_PATH . 'includes/compatibility/packages/' . $file;
			}
		}

	}

}
