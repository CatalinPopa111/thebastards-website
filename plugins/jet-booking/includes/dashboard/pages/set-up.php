<?php

namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;

class Set_Up extends Base {

	/**
	 * Page slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'jet-abaf-set-up';
	}

	/**
	 * Page title
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Set Up', 'jet-booking' );
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
		return \JET_ABAF\Capabilities::CAP_MANAGE_OTHERS_POST;
	}

	/**
	 * Page render function.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="jet-abaf-set-up-page"></div>';
	}

	/**
	 * Page config.
	 *
	 * Return set up page config object.
	 *
	 * @since  3.0.0 Removed unused configurations.
	 * @access public
	 *
	 * @return Page_Config
	 */
	public function page_config() {
		return new Page_Config( $this->slug(), [
			'has_woocommerce' => jet_abaf()->wc->has_woocommerce(),
		] );
	}

	/**
	 * Define that is setup page.
	 *
	 * @return boolean
	 */
	public function is_setup_page() {
		return true;
	}

	/**
	 * Assets.
	 *
	 * Dashboard set up page specific assets.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {
		$this->enqueue_script( $this->slug(), 'assets/js/admin/set-up.js' );
		$this->enqueue_style( $this->slug(), 'assets/css/admin/set-up.css' );
	}

	/**
	 * Set to true to hide page from admin menu.
	 *
	 * @return boolean
	 */
	public function is_hidden() {
		return jet_abaf()->settings->get( 'hide_setup' );
	}

	/**
	 * Page components templates.
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [ 'set-up' ];
	}

}