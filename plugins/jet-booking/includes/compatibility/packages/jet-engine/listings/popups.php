<?php

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Listings;

use \Jet_Engine\Query_Builder\Manager as Query_Manager;
use \Elementor\Plugin as Elementor;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Popups {

	public function __construct() {
		add_filter( 'jet-engine/compatibility/popup-package/custom-content', [ $this, 'set_custom_content' ], 10, 2 );
	}

	/**
	 * Set custom content for booking listings.
	 *
	 * @since 3.3.0
	 *
	 * @param string $content    Popup HTML content.
	 * @param array  $popup_data Popup data list.
	 *
	 * @return mixed|void
	 */
	public function set_custom_content( $content, $popup_data ) {

		if ( empty( $popup_data['listingSource'] ) || 'query' !== $popup_data['listingSource'] ) {
			return $content;
		}

		$query = Query_Manager::instance()->get_query_by_id( $popup_data['queryId'] );

		if ( 'jet-booking-query' !== $query->query_type || empty( $popup_data['postId'] ) ) {
			return $content;
		}

		$item = jet_abaf_get_booking( $popup_data['postId'] );

		jet_engine()->listings->data->set_current_object( $item, true );

		$content_type = ! empty( $popup_data['content_type'] ) ? $popup_data['content_type'] : 'elementor';

		if ( 'elementor' === $content_type && jet_engine()->has_elementor() ) {
			$content = Elementor::instance()->frontend->get_builder_content( $popup_data['popup_id'] );
		} else {
			$popup_post = get_post( $popup_data['popup_id'] );

			if ( $popup_post ) {
				$content = do_blocks( $popup_post->post_content );
				$content = do_shortcode( $content );
			}
		}

		return apply_filters( 'jet-engine/compatibility/popup-package/the_content', $content, $popup_data );

	}

}