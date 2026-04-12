<?php
/**
 * Registering form actions.
 *
 * The script for registering the action is displayed in the \JET_ABAF\Formbuilder_Plugin\Blocks\Blocks_Manager class.
 *
 * @since   2.2.5
 * @package JET_ABAF\Formbuilder_Plugin\Actions
 */

namespace JET_ABAF\Formbuilder_Plugin\Actions;

use JET_ABAF\Formbuilder_Plugin\With_Form_Builder;
use Jet_Form_Builder\Actions\Manager;

class Action_Manager {

	use With_Form_Builder;

	public function manager_init() {
		add_action( 'jet-form-builder/actions/register', [ $this, 'register_actions' ] );
	}

	/**
	 * Register actions.
	 *
	 * Register booking related form actions.
	 *
	 * @since 2.2.5
	 * @singe 3.3.0 Update action added.
	 *
	 * @param Manager $manager JetFormBuilder action manager instance.
	 */
	public function register_actions( $manager ) {
		$manager->register_action_type( new Types\Insert_Booking() );
		$manager->register_action_type( new Types\Update_Booking() );
	}

}