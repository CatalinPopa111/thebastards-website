<?php

namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags;

use \Elementor\Controls_Manager;
use \Elementor\Core\DynamicTags\Tag;
use \Elementor\Modules\DynamicTags\Module as Parent_Module;
use \JET_ABAF\Price;
use \JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Module as Child_Module;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Price extends Tag {

	/**
	 * Get name.
	 *
	 * Retrieve the dynamic tag name.
	 *
	 * @since 3.6.1
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'jet-booking-price';
	}

	/**
	 * Get title.
	 *
	 * Retrieve the dynamic tag title.
	 *
	 * @since 3.6.1
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return __( 'Booking Price', 'jet-booking' );
	}

	/**
	 * Get group.
	 *
	 * Retrieve the dynamic tag group.
	 *
	 * @since 3.6.1
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
	 * @since 3.6.1
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
	 * @since 3.6.1
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
	 * @since 3.6.1
	 *
	 * @return void
	 */
	protected function register_controls() {

		$this->add_control(
			'period_start',
			[
				'type'    => Controls_Manager::TEXT,
				'label'   => __( 'Period Start', 'jet-booking' ),
				'dynamic' => [ 'active' => true ],
			]
		);

		$this->add_control(
			'period_end',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'Period End', 'jet-booking' ),
				'description' => __( 'Enter about any English textual datetime description. <b>Example:</b> `1996-04-09`, `first day of this month`. See more <a target="_blank" href="https://www.php.net/manual/en/function.strtotime.php">here</a>.', 'jet-booking' ),
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'currency',
			[
				'type'    => Controls_Manager::TEXT,
				'label'   => __( 'Currency', 'jet-booking' ),
				'default' => __( '$', 'jet-booking' ),
			]
		);

		$this->add_control(
			'currency_position',
			[
				'type'    => Controls_Manager::SELECT,
				'label'   => __( 'Currency Position', 'jet-booking' ),
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
	 * @since 3.6.1
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function render() {

		$currency          = $this->get_settings( 'currency' );
		$currency_position = $this->get_settings( 'currency_position' );
		$price             = new Price( get_the_ID() );
		$period            = apply_filters( 'jet-booking/elementor-views/dynamic-tags/booking-price/period', [] );

		if ( empty( $period ) ) {
			$from = strtotime( $this->get_settings_for_display( 'period_start' ) );
			$to   = strtotime( $this->get_settings_for_display( 'period_end' ) );

			if ( empty( $from ) ) {
				echo $price->get_min_price( $currency, $currency_position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				return;
			}

			if ( empty( $to ) ) {
				$to = $from;
			}
		} else {
			[ $from, $to ] = $period;
		}

		$data = [
			'apartment_id'   => get_the_ID(),
			'check_in_date'  => intval( $from ),
			'check_out_date' => intval( $to ),
		];

		echo $price->formatted_price( $price->get_booking_price( $data ), $currency, $currency_position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

}
