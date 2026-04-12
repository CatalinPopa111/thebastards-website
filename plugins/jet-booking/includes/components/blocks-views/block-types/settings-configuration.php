<?php
namespace JET_ABAF\Components\Blocks_Views\Block_Types;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Settings_Configuration extends Base {

	/**
	 * Get name.
	 *
	 * Retrieves the name of the block.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_name() {
		return 'settings-configuration';
	}

	/**
	 * Get arguments.
	 *
	 * Retrieves the arguments of the block.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function get_args() {
		return [];
	}

	/**
	 * Add style manager options.
	 *
	 * Handles the addition of options specific to the style manager.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_style_manager_options() {

		$this->get_section_controls_style();

		$this->get_section_fields_style();

		$this->get_section_toggle_style();

		$this->get_section_button_style();

		$this->get_section_messages_style();

	}

}
