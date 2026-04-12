<?php
namespace JET_ABAF\Formbuilder_Plugin\Presets;

use Jet_Form_Builder\Presets\Sources\Preset_Source_Post;

class Source_Apartments extends Preset_Source_Post {
	/**
	 * Retrieves the number of apartment units.
	 *
	 * @since 3.8.0
	 *
	 * @return int The count of apartment units.
	 */
	public function source__post_units_number() {

		$units       = jet_abaf()->db->get_apartment_units( $this->src()->ID );
		$units_count = ! empty( $units ) ? count( $units ) : 0;

		return $units_count && $units_count > 1 ? $units_count : 1;

	}
}