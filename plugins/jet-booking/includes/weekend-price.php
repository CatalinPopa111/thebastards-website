<?php
namespace JET_ABAF;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Price class
 */
class Weekend_Price {

	/**
	 * Post meta
	 */
	private $post_meta;

	public function __construct( $post_meta = [] ) {
		$this->post_meta = $post_meta;
	}

	/**
	 * Returns weekend price
	 */
	public function get_price() {

		$output = [];

		if ( empty( $this->post_meta[ '_weekend_prices' ] ) ) {
			return $output;
		}

		foreach ( $this->post_meta[ '_weekend_prices' ] as $key => $value ) {
			switch ($key) {
				case 'sun':
					$day_number = 0;
				break;

				case 'mon':
					$day_number = 1;
				break;

				case 'tue':
					$day_number = 2;
				break;

				case 'wed':
					$day_number = 3;
				break;

				case 'thu':
					$day_number = 4;
				break;

				case 'fri':
					$day_number = 5;
				break;

				case 'sat':
					$day_number = 6;
				break;
			}

			$output[ $day_number ] = $value['active'] ? $value['price'] : false ;
		}

		return $output;

	}

}
