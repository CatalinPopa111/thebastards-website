<?php

namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags;

use \Elementor\Modules\DynamicTags\Module as Tags_Module;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Module extends Tags_Module {

	/**
	 * Dynamic tags bookings category.
	 *
	 * @var string
	 */
	const JET_GROUP = 'jet_booking';

	/**
	 * Get tag classes names.
	 *
	 * Retrieve the dynamic tag classes names.
	 *
	 * @since 3.2.0
	 * @since 3.4.0 Added `Accommodation_Status` class.
	 * @since 3.6.1 Added `Booking_Price` class.
	 *
	 * @return array Tag dynamic tag classes names.
	 */
	public function get_tag_classes_names() {
		return [
			'Accommodation_Status',
			'Booking_Price',
			'Bookings_Count',
			'Price_Per_Night',
			'Units_Count',
		];
	}

	/**
	 * Get groups.
	 *
	 * Retrieve the dynamic tag groups.
	 *
	 * @since 3.2.0
	 *
	 * @return array Tag dynamic tag groups.
	 */
	public function get_groups() {
		return [
			self::JET_GROUP => [
				'title' => __( 'JetBooking', 'jet-booking' ),
			],
		];
	}

	/**
	 * Register tags.
	 *
	 * Add all the available dynamic tags.
	 *
	 * @since 3.2.0
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Dynamic tags manager instance.
	 */
	public function register_tags( $dynamic_tags ) {
		foreach ( $this->get_tag_classes_names() as $tag_class ) {
			$class_name = '\JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags\\' . $tag_class;

			$dynamic_tags->register( new $class_name() );
		}
	}

}