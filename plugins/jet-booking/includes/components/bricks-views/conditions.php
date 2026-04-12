<?php

namespace JET_ABAF\Components\Bricks_Views;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Conditions {

	public function __construct() {
		// Register elements visibility conditions group.
		add_filter( 'bricks/conditions/groups', [ $this, 'register_conditions_group' ] );
		// Register elements visibility conditions.
		add_action( 'bricks/conditions/options', [ $this, 'register_conditions_options' ] );
		// Execute elements visibility logic.
		add_filter( 'bricks/conditions/result', [ $this, 'check_condition' ], 10, 3 );
	}

	/**
	 * Register condition group.
	 *
	 * Register and returns specific JetBooking elements visibility conditions group.
	 *
	 * @since  3.3.0
	 *
	 * @param array $groups List of groups.
	 *
	 * @return array
	 */
	public function register_conditions_group( $groups ) {

		$groups[] = [
			'name'  => 'jet_booking',
			'label' => __( 'JetBooking', 'jet-booking' ),
		];

		return $groups;

	}

	/**
	 * Register conditions.
	 *
	 * Register specific JetBooking elements visibility conditions.
	 *
	 * @since  3.3.0
	 * @since  3.4.1 Refactored.
	 *
	 * @param array $options List of elements visibility options.
	 *
	 * @return array
	 */
	public function register_conditions_options( $options ) {

		$conditions = $this->get_conditions();

		foreach ( $conditions as $key => $value ) {
			$options[] = [
				'key'     => $key,
				'label'   => $value['label'],
				'group'   => 'jet_booking',
				'compare' => [],
				'value'   => []
			];
		}

		return $options;

	}

	/**
	 * Check condition.
	 *
	 * Execute elements visibility logic.
	 *
	 * @since  3.3.0
	 * @since  3.4.1 Refactored.
	 *
	 * @param boolean $result        Condition result.
	 * @param string  $condition_key Condition option name.
	 * @param array   $condition     Condition parameters.
	 *
	 * @return bool
	 */
	public function check_condition( $result, $condition_key, $condition ) {

		$conditions = $this->get_conditions();

		if ( ! isset( $conditions[ $condition_key ] ) ) {
			return $result;
		}

		$condition_class    = $conditions[ $condition_key ]['class'];
		$condition_instance = new $condition_class();

		$booking = apply_filters( 'jet-booking/bricks-views/condition/object', null );

		return $condition_instance->check( [ 'type' => 'show' ], $booking );

	}

	/**
	 * Get conditions.
	 *
	 * Returns a list of booking conditions.
	 *
	 * @since 3.4.1
	 * @since 3.8.0 Added apartment units related conditions.
	 *
	 * @return array
	 */
	public function get_conditions() {
		return [
			'apartment_has_units'        => [
				'label' => __( 'Apartment has Units', 'jet-booking' ),
				'class' => 'JET_ABAF\Visibility_Conditions\Apartment_Has_Units'
			],
			'apartment_has_no_units'     => [
				'label' => __( 'Apartment has no Units', 'jet-booking' ),
				'class' => 'JET_ABAF\Visibility_Conditions\Apartment_Has_No_Units'
			],
			'booking_is_cancellable'     => [
				'label' => __( 'Booking is Cancellable', 'jet-booking' ),
				'class' => 'JET_ABAF\Visibility_Conditions\Booking_Is_Cancellable'
			],
			'booking_is_not_cancellable' => [
				'label' => __( 'Booking is not Cancellable', 'jet-booking' ),
				'class' => 'JET_ABAF\Visibility_Conditions\Booking_Is_Not_Cancellable'
			],
			'booking_is_updatable'       => [
				'label' => __( 'Booking is Updatable', 'jet-booking' ),
				'class' => 'JET_ABAF\Visibility_Conditions\Booking_Is_Updatable'
			],
			'booking_is_not_updatable'   => [
				'label' => __( 'Booking is not Updatable', 'jet-booking' ),
				'class' => 'JET_ABAF\Visibility_Conditions\Booking_Is_Not_Updatable'
			]
		];
	}

}