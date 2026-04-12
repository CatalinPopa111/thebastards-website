<?php

namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags;

use \Elementor\Controls_Manager;
use \Elementor\Core\DynamicTags\Tag;
use \Elementor\Modules\DynamicTags\Module as Parent_Module;
use \JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Module as Child_Module;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Bookings_Count extends Tag {

	/**
	 * Get name.
	 *
	 * Retrieve the dynamic tag name.
	 *
	 * @since 2.2.5
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'jet-bookings-count';
	}

	/**
	 * Get title.
	 *
	 * Retrieve the dynamic tag title.
	 *
	 * @since 2.2.5
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return __( 'Bookings Count', 'jet-booking' );
	}

	/**
	 * Get group.
	 *
	 * Retrieve the dynamic tag group.
	 *
	 * @since 2.2.5
	 *
	 * @return string The group.
	 */
	public function get_group() {
		return Child_Module::JET_GROUP;
	}

	/**
	 * Get categories.
	 *
	 * Retrieve the dynamic tag categories.
	 *
	 * @since 2.2.5
	 *
	 * @return array The categories.
	 */
	public function get_categories() {
		return [
			Parent_Module::TEXT_CATEGORY,
			Parent_Module::NUMBER_CATEGORY,
			Parent_Module::POST_META_CATEGORY,
		];
	}

	/**
	 * Is settings required.
	 *
	 * Point to the requirements of the additional settings.
	 *
	 * @since 2.2.5
	 *
	 * @return boolean
	 */
	public function is_settings_required() {
		return true;
	}

	/**
	 * Register controls.
	 *
	 * Used to add new controls to any element type.
	 *
	 * @since 2.2.5
	 * @since 3.6.1 Refactored controls.
	 *
	 * @return void
	 */
	protected function register_controls() {

		$this->add_control(
			'start_date',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'Period Start', 'jet-booking' ),
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'end_date',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'Period End', 'jet-booking' ),
				'description'  => __( 'Enter about any English textual datetime description. <b>Example:</b> `1996-04-09`, `first day of this month`. See more <a target="_blank" href="https://www.php.net/manual/en/function.strtotime.php">here</a>.', 'jet-booking' ),
				'dynamic'     => [ 'active' => true ],
			]
		);

	}

	/**
	 * Render element.
	 *
	 * Generates the final HTML on the frontend.
	 *
	 * @since 2.2.5
	 *
	 * @return void
	 */
	public function render() {

		$from = $this->get_settings_for_display( 'start_date' );
		$to   = $this->get_settings_for_display( 'end_date' );

		if ( empty( $from ) ) {
			esc_html_e( 'Please select date range.', 'jet-booking' );

			return;
		}

		if ( empty( $to ) ) {
			$to = $from;
		}

		$units = jet_abaf()->db->get_booked_units( [
			'apartment_id'   => get_the_ID(),
			'check_in_date'  => strtotime( $from ),
			'check_out_date' => strtotime( $to ),
		] );

		echo ! empty( $units ) ? count( $units ) : esc_html( $this->get_settings_for_display( 'fallback' ) );

	}

}
