<?php

namespace JET_ABAF\Workflows;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Workflows {

	/**
	 * Option key for storing workflows in the database.
	 *
	 * @var string
	 */
	private $option_key = 'jet_booking_workflows';

	/**
	 * Array of Workflow objects.
	 *
	 * @var Workflow[]
	 */
	private $items = [];

	public function __construct() {
		$this->items = $this->create_workflows_from_array( get_option( $this->option_key, [] ) );
	}

	/**
	 * Create workflows from array.
	 *
	 * Creates an array of Workflow objects from an array of workflow data.
	 *
	 * @since 3.7.0
	 *
	 * @param array $workflows Array of workflow data.
	 *
	 * @return Workflow[] Array of Workflow objects.
	 */
	public function create_workflows_from_array( $workflows = [] ) {

		$result = [];

		foreach ( $workflows as $workflow ) {
			$result[] = new Workflow( $workflow );
		}

		return $result;

	}

	/**
	 * Updates workflows.
	 *
	 * This function takes an array of workflow data and updates the workflows in the database.
	 *
	 * @since 3.7.0
	 *
	 * @param array $workflows Array of workflow data.
	 *
	 * @return void
	 */
	public function update_workflows( $workflows = [] ) {
		update_option( $this->option_key, $this->to_array( $this->create_workflows_from_array( $workflows ) ) );
	}

	/**
	 * Dispatches workflows.
	 *
	 * This function iterates through each workflow in the internal items array and calls the
	 * method is responsible for executing the workflow's.
	 *
	 * @since 3.7.0
	 *
	 * @param bool $scheduled Whether to dispatch only scheduled workflows.
	 *
	 * @return void
	 */
	public function dispatch_workflows( $scheduled = false ) {
		foreach ( $this->items as $item ) {
			$item->dispatch_workflow( $scheduled );
		}
	}

	/**
	 * Converts an array of Workflow objects to an array of workflow data.
	 *
	 * @param Workflow[]|null $items Array of Workflow objects.
	 *
	 * @return array Array of workflow data.
	 */
	public function to_array( $items = null ) {

		if ( null === $items ) {
			$items = $this->items;
		}

		return array_map( function ( $item ) {
			return $item->get_item();
		}, $items );

	}

}