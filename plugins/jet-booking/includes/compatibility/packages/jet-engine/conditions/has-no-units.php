<?php
namespace JET_ABAF\Compatibility\Packages\Conditions;

use Jet_Engine\Modules\Dynamic_Visibility\Conditions\Base;
use JET_ABAF\Visibility_Conditions\Apartment_Has_No_Units;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Has_No_Units extends Base {

	/**
	 * Returns condition ID.
	 *
	 * @since 3.8.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'has-no-units';
	}

	/**
	 * Returns condition name.
	 *
	 * @since 3.8.0
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Apartment has no Units', 'jet-booking' );
	}

	/**
	 * Returns a group for current condition.
	 *
	 * @since 3.8.0
	 *
	 * @return string
	 */
	public function get_group() {
		return 'jet_booking';
	}

	/**
	 * Check condition by passed arguments.
	 *
	 * @since 3.8.0
	 *
	 * @param array $args List of visibility condition arguments.
	 *
	 * @return boolean
	 */
	public function check( $args = [] ) {

		$condition = new Apartment_Has_No_Units();

		return $condition->check( $args );

	}

	/**
	 * Check if is condition available for meta-fields control.
	 *
	 * @since 3.8.0
	 *
	 * @return boolean
	 */
	public function is_for_fields() {
		return false;
	}

	/**
	 * Check if is condition available for meta-value control.
	 *
	 * @since 3.8.0
	 *
	 * @return boolean
	 */
	public function need_value_detect() {
		return false;
	}

}
