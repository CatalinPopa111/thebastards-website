<?php

namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags;

use \Elementor\Controls_Manager;
use \Elementor\Core\DynamicTags\Tag;
use \Elementor\Modules\DynamicTags\Module as Parent_Module;
use \JET_ABAF\Price;
use \JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Module as Child_Module;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Price_Per_Night extends Tag {

	/**
	 * Get name.
	 *
	 * Retrieve the dynamic tag name.
	 *
	 * @since 2.1.0
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'jet-price-per-night';
	}

	/**
	 * Get title.
	 *
	 * Retrieve the dynamic tag title.
	 *
	 * @since 2.1.0
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return __( 'Price per day/night', 'jet-booking' );
	}

	/**
	 * Get group.
	 *
	 * Retrieve the dynamic tag group.
	 *
	 * @since 2.1.0
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
	 * @since 2.1.0
	 *
	 * @return array The categories.
	 */
	public function get_categories() {
		return array(
			Parent_Module::TEXT_CATEGORY,
			Parent_Module::NUMBER_CATEGORY,
			Parent_Module::POST_META_CATEGORY,
		);
	}

	/**
	 * Is settings required.
	 *
	 * Point to the requirements of the additional settings.
	 *
	 * @since 2.1.0
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
	 * @since 2.1.0
	 *
	 * @return void
	 */
	protected function register_controls() {

		$this->add_control(
			'show_price',
			[
				'type'    => Controls_Manager::SELECT,
				'label'   => __( 'Show price', 'jet-booking' ),
				'default' => 'default',
				'options' => [
					'default' => __( 'Default', 'jet-booking' ),
					'min'     => __( 'Min price', 'jet-booking' ),
					'max'     => __( 'Max price', 'jet-booking' ),
					'range'   => __( 'Prices range', 'jet-booking' ),
				],
			]
		);

		$this->add_control(
			'change_dynamically',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Change Dynamically', 'jet-booking' ),
				'description' => __( 'Change price dynamically on check-in check-out dates select. Will work correctly only when appropriate form presented on the page.', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->add_control(
			'currency_sign',
			[
				'type'    => Controls_Manager::TEXT,
				'label'   => __( 'Currency', 'jet-booking' ),
				'default' => __( '$', 'jet-booking' ),
			]
		);

		$this->add_control(
			'currency_sign_position',
			[
				'type'    => Controls_Manager::SELECT,
				'label'   => __( 'Currency position', 'jet-booking' ),
				'default' => 'before',
				'options' => [
					'before' => __( 'Before', 'jet-booking' ),
					'after'  => __( 'After', 'jet-booking' ),
				],
			]
		);

	}

	/**
	 * Render element.
	 *
	 * Generates the final HTML on the frontend.
	 *
	 * @since 2.1.0
	 * @since 3.6.1 Refactored.
	 *
	 * @return void
	 */
	public function render() {

		$price = new Price( get_the_ID() );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $price->get_price_for_display( [
			'show_price'             => $this->get_settings( 'show_price' ),
			'change_dynamically'     => filter_var( $this->get_settings( 'change_dynamically' ), FILTER_VALIDATE_BOOLEAN ),
			'currency_sign'          => $this->get_settings( 'currency_sign' ),
			'currency_sign_position' => $this->get_settings( 'currency_sign_position' ),
		] );
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

	}

}
