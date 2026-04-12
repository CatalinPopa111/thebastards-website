<?php

namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as Parent_Module;
use JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Module as Child_Module;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Accommodation_Status extends Tag {

	/**
	 * Get name.
	 *
	 * Retrieve the dynamic tag name.
	 *
	 * @since 3.4.0
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'jet-accommodation-status';
	}

	/**
	 * Get title.
	 *
	 * Retrieve the dynamic tag title.
	 *
	 * @since 3.4.0
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return __( 'Accommodation Status', 'jet-booking' );
	}

	/**
	 * Get group.
	 *
	 * Retrieve the dynamic tag group.
	 *
	 * @since 3.4.0
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
	 * @since 3.4.0
	 *
	 * @return array The categories.
	 */
	public function get_categories() {
		return [
			Parent_Module::TEXT_CATEGORY,
			Parent_Module::POST_META_CATEGORY,
		];
	}

	/**
	 * Is settings required.
	 *
	 * Point to the requirements of the additional settings.
	 *
	 * @since 3.4.0
	 *
	 * @return boolean
	 */
	public function is_settings_required() {
		return false;
	}

	/**
	 * Register controls.
	 *
	 * Used to add new controls to any element type.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	protected function register_controls() {

		$this->add_control(
			'available_label',
			[
				'type'    => Controls_Manager::TEXT,
				'label'   => __( 'Available Label', 'jet-booking' ),
				'default' => __( 'Available', 'jet-booking' ),
			]
		);

		$this->add_control(
			'pending_label',
			[
				'type'    => Controls_Manager::TEXT,
				'label'   => __( 'Pending Label', 'jet-booking' ),
				'default' => __( 'Available on', 'jet-booking' ),
			]
		);

		$this->add_control(
			'reserved_label',
			[
				'type'    => Controls_Manager::TEXT,
				'label'   => __( 'Reserved Label', 'jet-booking' ),
				'default' => __( 'Reserved', 'jet-booking' ),
			]
		);

	}

	/**
	 * Register controls.
	 *
	 * Used to add new controls to any element type.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function render() {

		$show_reserved_label = apply_filters( 'jet-booking/accommodation-status/show-reserved-label', true );
		$from           	 = strtotime( 'today' );
		$to             	 = $show_reserved_label ? strtotime( '+1 week', $from ) : strtotime( '+6 months', $from );
		$period         	 = jet_abaf()->tools->get_booking_period( $from, $to, get_the_ID() );
		$booked_dates   	 = jet_abaf()->settings->get_off_dates( get_the_ID() );
		$disabled_days  	 = jet_abaf()->settings->get_days_by_rule( get_the_ID() );
		$dates_count    	 = 0;
		$available_date 	 = '';

		foreach ( $period as $value ) {
			$available_date = date_i18n( get_option( 'date_format' ), $value->getTimestamp() );

			if ( ! in_array( $value->format( 'Y-m-d' ), $booked_dates ) && ! in_array( $value->format( 'w' ), $disabled_days ) ) {
				break;
			}

			$dates_count ++;
		}

		$format = '<span class="status %s">%s %s</span>';

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! $dates_count ) {
			printf( $format, 'available', esc_html( $this->get_settings( 'available_label' ) ), '' );
		} elseif ( $dates_count >= 7 && $show_reserved_label ) {
			printf( $format, 'reserved', esc_html( $this->get_settings( 'reserved_label' ) ), '' );
		} else {
			printf( $format, 'pending', esc_html( $this->get_settings( 'pending_label' ) ), esc_html( $available_date ) );
		}
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

	}

}
