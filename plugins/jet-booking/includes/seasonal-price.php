<?php
namespace JET_ABAF;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Seasonal_Price {

	/**
	 * Post meta
	 */
	private $post_meta;

	public function __construct( $post_meta = [] ) {
		$this->post_meta = $post_meta;
	}

	/**
	 * Returns seasonal price
	 */
	public function get_price() {

		$output = [];

		if ( empty( $this->post_meta['_seasonal_prices'] ) ) {
			return $output;
		}

		foreach ( $this->post_meta['_seasonal_prices'] as $value ) {
			$rates_price   = new Advanced_Price_Rates( $this->post_meta['ID'], $value );
			$weekend_price = new Weekend_Price( $value );

			$output_value = [
				'start'         => $value['startTimestamp'],
				'end'           => $value['endTimestamp'],
				'price'         => $value['price'],
				'price_rates'   => $rates_price->get_rates(),
				'weekend_price' => $weekend_price->get_price(),
			];

			if ( isset( $value['enable_config'] ) && $value['enable_config'] ) {
				$output_value += [
					'enable_config'    => $value['enable_config'],
					'start_day_offset' => $value['start_day_offset'] ?? 0,
					'min_days'         => $value['min_days'] ?? 0,
					'max_days'         => $value['max_days'] ?? 0,
				];
			}

			$output[ $value['startTimestamp'] . $value['endTimestamp'] ] = $output_value;
		}

		return $output;

	}

}
