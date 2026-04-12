<?php

namespace JET_ABAF\Formbuilder_Plugin;

class Manage_Calculated_Data {

	public function __construct() {

		// Macros %ADVANCED_PRICE% procession in the calculator field.
		add_filter( 'jet-engine/calculated-data/ADVANCED_PRICE', function ( $macros ) {
			return $macros;
		} );

		// Macros %BOOKING_TIME% procession in the calculator field.
		add_filter( 'jet-engine/calculated-data/BOOKING_TIME', function ( $macros ) {
			return $macros;
		} );

		add_filter( 'jet-form-builder/field-data/calculated-field', [ $this, 'prepare_calc_description' ] );

	}

	/**
	 * Prepare calc descriptions.
	 *
	 * Add booking related calculated field macros descriptions.
	 *
	 * @since 2.2.5
	 * @since 3.2.0 Changed template include.
	 *
	 * @param array $content List of calculated field description.
	 *
	 * @return mixed
	 */
	public function prepare_calc_description( $content ) {

		ob_start();

		include JET_ABAF_PATH . 'includes/compatibility/packages/jet-engine/templates/admin/macros-list.php';

		$content['field_desc'] .= ob_get_clean() . '<br>';

		return $content;

	}

}