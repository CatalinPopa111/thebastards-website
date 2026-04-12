<?php

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Listings\Blocks_Views;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	public function __construct() {

		// Register attributes for dynamic link source controls.
		add_filter( 'jet-engine/blocks-views/block-types/attributes/dynamic-link', [ $this, 'register_add_to_cart_atts' ] );
		// Register additional controls for dynamic link source.
		add_filter( 'jet-engine/blocks-views/custom-blocks-controls', [ $this, 'register_dynamic_link_controls' ] );

	}

	/**
	 * Register add to cart atts.
	 *
	 * Register add to cart source custom attributes.
	 *
	 * @since  3.3.0
	 *
	 * @param array $atts Attributes list
	 *
	 * @return array
	 */
	public function register_add_to_cart_atts( $atts ) {

		$atts['dynamic_link_cancel_redirect_url'] = [
			'type'    => 'text',
			'default' => '',
		];

		return $atts;

	}

	/**
	 * Register links controls.
	 *
	 * Register add to cart source custom controls.
	 *
	 * @since  3.3.0
	 *
	 * @param array $controls Controls list.
	 *
	 * @return array
	 */
	public function register_dynamic_link_controls( $controls = [] ) {

		$link_controls = ! empty( $controls['dynamic-link'] ) ? $controls['dynamic-link'] : [];

		$link_controls[] = [
			'name'      => 'dynamic_link_cancel_redirect_url',
			'type'      => 'text',
			'label'     => __( 'Redirect URL', 'jet-booking' ),
			'condition' => [
				'dynamic_link_source' => [ 'booking_cancel_link' ],
			],
		];

		$controls['dynamic-link'] = $link_controls;

		return $controls;

	}

}