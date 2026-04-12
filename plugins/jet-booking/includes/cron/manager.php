<?php

namespace JET_ABAF\Cron;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Instance.
	 *
	 * Holds the cron instance.
	 *
	 * @since  3.0.0
	 * @access public
	 * @static
	 *
	 * @var null
	 */
	public static $instance = null;

	/**
	 * Schedules.
	 *
	 * Holder for plugin schedules.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @var array
	 */
	private $schedules = [];

	public function __construct() {
		$this->register_schedule( new Clear_On_Expire() );
		$this->register_schedule( new Events_Dispatcher() );
		$this->register_schedule( new Remove_Temporary_Bookings() );
		$this->register_schedule( new Switch_Statuses() );
		$this->register_schedule( new Sync_Calendars() );
	}

	/**
	 * Register schedule.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param object $schedule Schedule event instance.
	 *
	 * @return void
	 */
	public function register_schedule( $schedule ) {
		$this->schedules[ $schedule->event_name() ] = $schedule;
	}

	/**
	 * Get schedule.
	 *
	 * Return specific schedule event object instance.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param null $name Event name.
	 *
	 * @return array|false|mixed
	 */
	public function get_schedules( $name = null ) {

		if ( ! $name ) {
			return $this->schedules;
		}

		return $this->schedules[ $name ] ?? false;

	}

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the Cron manager class is loaded or can be loaded.
	 *
	 * @since  3.0.0
	 * @access public
	 * @static
	 *
	 * @return Manager|null
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}