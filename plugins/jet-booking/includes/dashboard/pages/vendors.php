<?php
namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Vendors extends Base{

	/**
	 * Page slug.
	 *
	 * @since   4.0.0
	 * @access  public
	 *
	 * @return string
	 */
	public function slug() {
		return 'jet-abaf-vendors';
	}

	/**
	 * Page title.
	 *
	 * @since   4.0.0
	 * @access  public
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Vendors', 'jet-booking' );
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
	 * Page config.
	 *
	 * Return page config object.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return Page_Config
	 */
	public function page_config() {

		$config = [
			'vendors_list' => jet_abaf()->vendors->get_extended_vendor_list(),
		];

		return new Page_Config( $this->slug(), $config );

	}

	/**
	 * Render.
	 *
	 * Page render function.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="jet-abaf-vendors-page"></div>';
	}

	/**
	 * Assets.
	 *
	 * Dashboard booking page specific assets.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {

		parent::assets();

		$this->enqueue_script( $this->slug(), 'assets/js/admin/vendors.js' );

	}

	/**
	 * Vue templates.
	 *
	 * Page components templates.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [
			'vendors',
			'vendors-list',
		];
	}

}