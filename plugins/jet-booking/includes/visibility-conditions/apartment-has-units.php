<?php
namespace JET_ABAF\Visibility_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Apartment_Has_Units {

	/**
	 * Check condition.
	 *
	 * Check if an apartment has units by passed arguments.
	 *
	 * @since 3.8.0
	 *
	 * @param array $args List of visibility condition arguments.
	 *
	 * @return bool
	 */
	public function check( $args = [] ) {

		$type        = ! empty( $args['type'] ) ? $args['type'] : 'show';
		$units       = jet_abaf()->db->get_apartment_units( get_the_ID() );
		$units_count = ! empty( $units ) ? count( $units ) : 0;

		if ( 'hide' === $type ) {
			return ! $units_count || 1 === $units_count;
		} else {
			return $units_count && $units_count > 1;
		}

	}

}