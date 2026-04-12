<?php

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Listings\Elementor_Views;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	public function __construct() {
		// Register additional controls for dynamic link source.
		add_action( 'jet-engine/listings/dynamic-link/source-controls', [ $this, 'register_dynamic_link_controls' ] );
	}

	/**
	 * Register links controls.
	 *
	 * Register add to cart source custom controls.
	 *
	 * @since  3.3.0
	 *
	 * @param object $widget Dynamic link widget instance.
	 */
	public function register_dynamic_link_controls( $widget ) {
		$widget->add_control(
			'dynamic_link_cancel_redirect_url',
			[
				'type'        => 'text',
				'label'       => __( 'Redirect URL', 'jet-booking' ),
				'description' => __( 'The URL to redirect to after booking cancellation. If empty will redirect to home page. Use the %current_page_url% macro to redirect to the current page.', 'jet-booking' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
				'condition'   => [
					'dynamic_link_source' => 'booking_cancel_link',
				],
			]
		);
	}

}