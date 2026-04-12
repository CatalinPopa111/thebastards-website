<?php

namespace JET_ABAF\Workflows\Actions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

abstract class Base {

	/**
	 * List of settings associated with the action.
	 *
	 * @var array
	 */
	public $settings = [];

	/**
	 * Booking data associated with the action.
	 *
	 * @var array
	 */
	public $booking = [];

	public function __construct() {
		add_action( 'jet-booking/workflows/action-controls', [ $this, 'register_action_controls' ] );
	}

	/**
	 * Get action ID.
	 *
	 * This method should return the unique identifier for the action.
	 *
	 * @since 3.7.0
	 *
	 * @return string Unique identifier for the action.
	 */
	abstract public function get_id();

	/**
	 * Get action name.
	 *
	 * This method should return the human-readable name for the action.
	 *
	 * @since 3.7.0
	 *
	 * @return string The name of the action.
	 */
	abstract public function get_name();

	/**
	 * Perform the action.
	 *
	 * This method should contain the logic for the action to be executed.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	abstract public function do_action();

	/**
	 * Registers action controls for the workflows.
	 *
	 * This method is responsible for adding any necessary controls or settings to the workflow
	 * action controls section.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function register_action_controls() {}

	/**
	 * Sets up the action with the provided settings and booking data.
	 *
	 * This method is used to initialize the action with the necessary data for execution.
	 *
	 * @since 3.7.0
	 *
	 * @param array $settings An associative array containing the action's settings.
	 * @param array $booking  An associative array containing the booking data.
	 *
	 * @return void
	 */
	public function setup( $settings = [], $booking = [] ) {
		$this->settings = $settings;
		$this->booking  = $booking;
	}

	/**
	 * Retrieves action settings.
	 *
	 * This method retrieves the settings associated with the action.
	 *
	 * @since 3.7.0
	 *
	 * @param string|null $setting Optional. The specific setting to retrieve.
	 *
	 * @return mixed The value of the specified setting.
	 */
	public function get_settings( $setting = null ) {

		if ( ! $setting ) {
			return $this->settings;
		}

		return $this->settings[ $setting ] ?? false;

	}

	/**
	 * Updates the action settings.
	 *
	 * This method allows updating a specific setting within the action's settings array.
	 *
	 * @since 3.7.0
	 *
	 * @param string $setting The key of the setting to update.
	 * @param mixed  $value   The new value for the setting.
	 *
	 * @return void
	 */
	public function update_settings( $setting, $value = null ) {
		$this->settings[ $setting ] = $value;
	}

}