<?php
namespace JET_ABAF\Visibility_Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Apartment_Has_No_Units extends Apartment_Has_Units{

	/**
	 * Check condition.
	 *
	 * Check if an apartment has no units by passed arguments.
	 *
	 * @since 3.8.0
	 *
	 * @param array $args List of visibility condition arguments.
	 *
	 * @return bool
	 */
	public function check( $args = [] ) {
		return ! parent::check( $args );
	}

}