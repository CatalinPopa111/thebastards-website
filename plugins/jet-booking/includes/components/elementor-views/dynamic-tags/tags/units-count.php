<?php
namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as Parent_Module;
use JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Module as Child_Module;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Units_Count extends Tag {

	/**
	 * Get a dynamic tag name.
	 *
	 * Retrieve the name of the dynamic tag.
	 *
	 * @since 2.2.5
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'jet-units-count';
	}

	/**
	 * Get a dynamic tag title.
	 *
	 * Retrieve the title of the dynamic tag.
	 *
	 * @since 2.2.5
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return __( 'Units count', 'jet-booking' );
	}

	/**
	 * Get dynamic tag groups.
	 *
	 * Retrieve the list of groups the dynamic tag belongs to.
	 *
	 * @since 2.2.5
	 *
	 * @return string The group.
	 */
	public function get_group() {
		return Child_Module::JET_GROUP;
	}

	/**
	 * Get dynamic tag categories.
	 *
	 * Retrieve the list of categories the dynamic tag belongs to.
	 *
	 * @since 2.2.5
	 *
	 * @return array The categories.
	 */
	public function get_categories() {
		return [
			Parent_Module::TEXT_CATEGORY,
			Parent_Module::NUMBER_CATEGORY,
		];
	}

	/**
	 * Register dynamic tag controls.
	 *
	 * Add fields to allow the user to customize the dynamic tag settings.
	 *
	 * @since 3.8.3
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'units_type',
			[
				'type'        => Controls_Manager::SELECT,
				'label'       => __( 'Units Type', 'jet-booking' ),
				'description' => __( 'Choose whether to display available or booked units count.', 'jet-booking' ),
				'options'     => [
					'available' => __( 'Available', 'jet-booking' ),
					'booked'    => __( 'Booked', 'jet-booking' ),
				],
				'default'     => 'available',
			]
		);
	}

	/**
	 * Render tag output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 2.2.5
	 * @since 3.8.3 Added period handling.
	 *
	 * @return void
	 */
	public function render() {

		$period      = apply_filters( 'jet-booking/elementor-views/dynamic-tags/units-count/period', [] );
		$units_type  = ! empty( $this->get_settings( 'units_type' ) ) ? $this->get_settings( 'units_type' ) : 'available';
		$units_count = jet_abaf()->db->get_units_count( [
			'apartment_id'   => get_the_ID(),
			'check_in_date'  => $period[0] ?? time(),
			'check_out_date' => $period[1] ?? time(),
		], $units_type );

		$units_count = $units_count ? apply_filters( 'jet-booking/elementor-views/dynamic-tags/units-count', $units_count, $this ) : $this->get_settings( 'fallback' );

		printf( '<span data-post="%1$s" data-units-count="%2$s" data-units-type="%3$s">%2$s</span>', esc_attr( get_the_ID() ), wp_kses_post( $units_count ), esc_attr( $units_type ) );

	}

}
