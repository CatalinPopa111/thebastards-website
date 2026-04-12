<?php

namespace JET_ABAF\Workflows;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Holds the collection of action types.
	 *
	 * @var array An associative array where the keys are action type IDs and the values are action type objects.
	 */
	private $_actions = [];

	/**
	 * Holds the collection of event types.
	 *
	 * @var array An associative array where the keys are event type IDs and the values are event type objects.
	 */
	private $_events = [];

	/**
	 * Holds the collection of workflows.
	 *
	 * @var Workflows The Workflows object instance.
	 */
	public $collection;

	public function __construct() {

		$this->collection = new Workflows();

		add_action( 'init', [ $this, 'init' ], 99 );
		add_action( 'init', [ $this->collection, 'dispatch_workflows' ], 999 );

	}

	/**
	 * Initializes.
	 *
	 * Initializes the workflow manager by registering event and action types.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function init() {
		$this->register_events();
		$this->register_actions();
	}

	/**
	 * Registers event types.
	 *
	 * This method initializes the workflow manager by registering event types.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function register_events() {
		$this->register_event_type( new Events\Booking_Created() );
		$this->register_event_type( new Events\Booking_Status_Changed() );
	}

	/**
	 * Registers action types.
	 *
	 * This method initializes the workflow manager by registering action types.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function register_actions() {
		$this->register_action_type( new Actions\Send_Email() );
		$this->register_action_type( new Actions\Webhook() );
	}

	/**
	 * Registers a new event type.
	 *
	 * This method adds a new event to the collection of registered event types.
	 *
	 * @since 3.7.0
	 *
	 * @param object $event The event object to be registered.
	 *
	 * @return void
	 */
	public function register_event_type( $event ) {
		$this->_events[ $event->get_id() ] = $event;
	}

	/**
	 * Registers a new action type.
	 *
	 * This method adds a new action to the collection of registered action types.
	 *
	 * @since 3.7.0
	 *
	 * @param object $action The action object to be registered.
	 *
	 * @return void
	 */
	public function register_action_type( $action ) {
		$this->_actions[ $action->get_id() ] = $action;
	}

	/**
	 * Retrieves an event by ID.
	 *
	 * This method searches for an event in the collection of registered event types
	 * based on the provided event ID.
	 *
	 * @since 3.7.0
	 *
	 * @param string $id The ID of the event to retrieve.
	 *
	 * @return object|bool The event object if found, or false if not found.
	 */
	public function get_event( $id ) {
		return $this->_events[ $id ] ?? false;
	}

	/**
	 * Retrieves an action by ID.
	 *
	 * This method searches for an action in the collection of registered action types
	 * based on the provided action ID.
	 *
	 * @since 3.7.0
	 *
	 * @param string $id The ID of the action to retrieve.
	 *
	 * @return object|bool The event object if found, or false if not found.
	 */
	public function get_action( $id ) {
		return $this->_actions[ $id ] ?? false;
	}

	/**
	 * Retrieves options for JavaScript.
	 *
	 * This function generates an array of options suitable for use in JavaScript,
	 * based on the provided key. The options are derived from the registered event
	 * or action types.
	 *
	 * @since 3.7.0
	 *
	 * @param string $key         The type of options to retrieve. Accepts 'events' or 'actions'.
	 * @param string $placeholder The placeholder text for the first option.
	 *
	 * @return array An array of options, where each option is an associative array with 'value' and 'label' keys.
	 */
	public function get_options_for_js( $key = 'events', $placeholder = 'Select...' ) {

		$items  = [];
		$result = [
			[
				'value' => '',
				'label' => $placeholder,
			]
		];

		switch ( $key ) {
			case 'events':
				$items = $this->_events;
				break;

			case 'actions':
				$items = $this->_actions;
				break;

			default:
				break;
		}

		foreach ( $items as $item ) {
			$result[] = [
				'value' => $item->get_id(),
				'label' => $item->get_name(),
			];
		}

		return $result;

	}

}