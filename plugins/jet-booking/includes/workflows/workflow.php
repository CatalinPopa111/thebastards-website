<?php

namespace JET_ABAF\Workflows;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Workflow {

	/**
	 * The workflow item data.
	 *
	 * @var array
	 */
	private $item;

	/**
	 * Constructs a new Workflow object.
	 *
	 * @since 3.7.0
	 *
	 * @param array $data An array of workflow items.
	 */
	public function __construct( $data ) {

		$this->item = ! empty( $data ) ? $data : [];

		$this->ensure_items_hash();

	}

	/**
	 * Get item.
	 *
	 * Returns the workflow item data.
	 *
	 * @since 3.7.0
	 *
	 * @return array The workflow item data.
	 */
	public function get_item() {
		return $this->item;
	}

	/**
	 * Dispatches the workflow based on the item's event.
	 *
	 * @since 3.7.0
	 *
	 * @param bool $scheduled Whether the workflow dispatch is scheduled.
	 *
	 * @return void
	 */
	public function dispatch_workflow( $scheduled = false ) {

		if ( ! jet_abaf()->settings->get( 'enable_workflows' ) ) {
			return;
		}

		$this->dispatch_event( $this->item, $scheduled );

	}

	/**
	 * Dispatches the workflow event based on the provided item.
	 *
	 * @since 3.7.0
	 *
	 * @param array $item      An array representing the workflow item.
	 * @param bool  $scheduled Whether the workflow dispatch is scheduled.
	 *
	 * @return void
	 */
	public function dispatch_event( $item = [], $scheduled = false ) {

		if ( empty( $item['event'] ) ) {
			return;
		}

		$event = jet_abaf()->workflows->get_event( $item['event'] );

		if ( $event ) {
			if ( $scheduled ) {
				$event->dispatch_scheduled( $item );
			} else {
				$event->dispatch( $item );
			}
		}

	}

	/**
	 * Ensure item hash.
	 *
	 * Ensures workflow item has a unique hash.
	 * If an item does not have a hash, a random one is generated.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function ensure_items_hash() {
		if ( empty( $this->item['hash'] ) ) {
			$this->item['hash'] = rand( 100000, 999999 );
		}
	}

}