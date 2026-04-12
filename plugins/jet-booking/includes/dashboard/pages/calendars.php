<?php

namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;

class Calendars extends Base {

	/**
	 * Page slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'jet-abaf-calendars';
	}

	/**
	 * Page title.
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Calendars', 'jet-booking' );
	}

	/**
	 * Page capability.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function capability() {
		return \JET_ABAF\Capabilities::CAP_MANAGE_POST;
	}

	/**
	 * Render.
	 *
	 * Page render function.
	 *
	 * @since  2.7.0 Updated styles.
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="jet-abaf-ical-page"></div>';
	}

	/**
	 * Return calendars page config object.
	 *
	 * @since 3.8.1 Added `macros_list` configuration.
	 *
	 * @return Page_Config
	 */
	public function page_config() {
		return new Page_Config( $this->slug(), [
			'api'         => jet_abaf()->rest_api->get_urls( false ),
			'macros_list' => jet_abaf()->tools->get_prepared_macros_list(),
		] );
	}

	/**
	 * Assets.
	 *
	 * Dashboard calendar page specific assets.
	 *
	 * @since  3.8.1 Added macros inserter script and styles.
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {

		parent::assets();

		$this->enqueue_script( 'jet-abaf-macros-inserter', 'assets/js/admin/macros-inserter.js' );
		$this->enqueue_script( $this->slug(), 'assets/js/admin/calendars.js' );

		$this->enqueue_style( 'jet-abaf-macros-inserter', 'assets/css/admin/macros-inserter.css' );

	}

	/**
	 * Set to true to hide page from admin menu.
	 *
	 * @return boolean
	 */
	public function is_hidden() {
		return ! jet_abaf()->settings->get( 'ical_synch' );
	}

	/**
	 * Page components templates.
	 *
	 * @since 3.8.1 Added macros inserter template.
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [
			'calendars',
			'calendars-list',
			[
				'dir'  => 'jet-abaf-settings',
				'file' => 'settings-macros-inserter',
			]
		];
	}

}