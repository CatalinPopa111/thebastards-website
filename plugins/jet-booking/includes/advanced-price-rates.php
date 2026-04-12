<?php
namespace JET_ABAF;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Advanced price rates class
 */
class Advanced_Price_Rates {

	/**
	 * Post meta key
	 */
	private $meta_key = 'jet_abaf_price';

	private $post_meta     = null;

	private $rates         = false;

	public function __construct( $post_id, $post_meta = false ) {
		$this->set_meta( $post_id, $post_meta );
	}

	public function set_meta( $post_id, $post_meta ) {
		if ( $post_meta ) {
			$this->post_meta = $post_meta;
		} else {
			$this->post_meta = get_post_meta( $post_id, $this->meta_key, true );
		}
	}

	/**
	 * Returns advanced price rates for current post
	 */
	public function get_rates() {
		if ( false === $this->rates ) {
			$pricing_rates = empty( $this->post_meta[ '_pricing_rates' ] ) ? false : $this->post_meta[ '_pricing_rates' ] ;

			if ( ! $pricing_rates ) {
				$pricing_rates = [];
			}

			usort( $pricing_rates, function( $a, $b ) {

				$a_duration = floatval( $a['duration'] );
				$b_duration = floatval( $b['duration'] );

				if ( $a_duration == $b_duration ) {
					return 0;
				}

				return ( $a_duration < $b_duration ) ? -1 : 1;

			} );

			$this->rates = $pricing_rates;
		}

		return $this->rates;
	}

}
