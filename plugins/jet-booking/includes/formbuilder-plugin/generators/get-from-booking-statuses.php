<?php

namespace JET_ABAF\Formbuilder_Plugin\Generators;

use \Jet_Form_Builder\Generators\Base;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Get_From_Booking_Statuses extends Base {

	/**
	 * Returns generator ID.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'get_from_booking_statuses';
	}

	/**
	 * Returns generator name.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Get values list from booking statuses', 'jet-booking' );
	}

	/**
	 * Returns generated options list.
	 *
	 * @since 3.3.0
	 *
	 * @param string $args Generated option string.
	 *
	 * @return array
	 */
	public function generate( $args ) {

		$args     = ! empty( $args['generator_field'] ) ? explode( '|', $args['generator_field'] ) : [];
		$statuses = jet_abaf()->statuses->get_statuses();
		$result   = [];

		if ( empty( $args ) ) {
			foreach ( $statuses as $key => $label ) {
				$result[] = [
					'value' => $key,
					'label' => $label,
				];
			}
		} else {
			$statuses_schema = jet_abaf()->statuses->get_schema();

			foreach ( $args as $arg ) {
				if ( isset( $statuses_schema[ $arg ] ) ) {
					foreach ( $statuses_schema[ $arg ] as $status ) {
						$result[] = [
							'value' => $status,
							'label' => $statuses[ $status ],
						];
					}
				}
			}
		}

		return $result;
	}

}
