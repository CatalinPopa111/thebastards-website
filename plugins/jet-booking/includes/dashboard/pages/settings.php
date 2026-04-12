<?php

namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;
use JET_ABAF\Capabilities;

class Settings extends Base {

	/**
	 * Page slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'jet-abaf-settings';
	}

	/**
	 * Page title.
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Settings', 'jet-booking' );
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
		return Capabilities::CAP_MANAGE_POST;
	}

	/**
	 * Is settings page.
	 *
	 * @return boolean
	 */
	public function is_settings_page() {
		return true;
	}

	/**
	 * Render.
	 *
	 * Settings page render function.
	 *
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="jet-abaf-settings-page"></div>';
	}

	/**
	 * Get the configuration for the settings page.
	 *
	 * This function creates and returns a new Page_Config object with various settings
	 * and data needed for the settings page of the plugin.
	 *
	 * @since 2.0.0
	 * @since 3.7.0 Added workflows related configuration.
	 *
	 * @return Page_Config
	 */
	public function page_config() {
		return new Page_Config( $this->slug(), [
			'cron_schedules'   => $this->get_cron_schedules(),
			'db_tables_exists' => jet_abaf()->db->tables_exists(),
			'post_types'       => jet_abaf()->tools->get_post_types_for_js( [
				'value' => '',
				'label' => __( 'Select...', 'jet-booking' ),
			] ),
			'settings'         => jet_abaf()->settings->get_all(),
			'workflows'        => jet_abaf()->workflows->collection->to_array(),
			'events'           => jet_abaf()->workflows->get_options_for_js( 'events', __( 'Select trigger event...', 'jet-booking' ) ),
			'actions'          => jet_abaf()->workflows->get_options_for_js( 'actions', __( 'Select action to run...', 'jet-booking' ) ),
			'macros_list'      => jet_abaf()->tools->get_prepared_macros_list(),
			'api'              => jet_abaf()->rest_api->get_urls( false ),
			'setting_panels'   => [
				'general'        => [
					'label'      => __( 'General', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_OTHERS_POST ),
				],
				'labels'         => [
					'label'      => __( 'Labels', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_OTHERS_POST ),
				],
				'field-settings' => [
					'label'      => __( 'Fields Settings', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_OTHERS_POST ) && 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ),
				],
				'advanced'       => [
					'label'      => __( 'Advanced', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_OTHERS_POST ),
				],
				'configuration'  => [
					'label'      => __( 'Configuration', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_POST ),
				],
				'schedule'       => [
					'label'      => __( 'Schedule', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_POST ),
				],
				'workflows'      => [
					'label'      => __( 'Workflows', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_OTHERS_POST ),
				],
				'tools'          => [
					'label'      => __( 'Tools', 'jet-booking' ),
					'capability' => current_user_can( Capabilities::CAP_MANAGE_OTHERS_POST ),
				]
			],
		] );
	}

	/**
	 * Get cron schedules.
	 *
	 * Returns registered cron intervals.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_cron_schedules() {

		$schedules = wp_get_schedules();

		uasort( $schedules, function ( $a, $b ) {
			if ( $a['interval'] == $b['interval'] ) {
				return 0;
			}

			return ( $a['interval'] < $b['interval'] ) ? -1 : 1;
		} );

		$result          = [];
		$found_intervals = [];

		foreach ( $schedules as $name => $int ) {
			if ( ! in_array( $int['interval'], $found_intervals ) ) {
				$diff = human_time_diff( 0, $int['interval'] );

				$result[] = [
					'value' => $name,
					'label' => $int['display'] . ' (' . $diff . ')',
				];

				$found_intervals[] = $int['interval'];
			}
		}

		return $result;

	}

	/**
	 * Assets.
	 *
	 * Dashboard settings page specific assets.
	 *
	 * @since  3.8.1 Added macros inserter script and styles.
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {

		parent::assets();

		$this->enqueue_script( 'moment-js', 'assets/lib/moment/js/moment.js' );
		$this->enqueue_script( 'vuejs-datepicker', 'assets/js/lib/vuejs-datepicker.min.js' );
		$this->enqueue_script( 'jet-abaf-meta-extras', 'assets/js/admin/meta-extras.js' );
		$this->enqueue_script( 'jet-abaf-schedule-manager', 'assets/js/admin/schedule-manager.js' );
		$this->enqueue_script( 'jet-abaf-macros-inserter', 'assets/js/admin/macros-inserter.js' );
		$this->enqueue_script( $this->slug(), 'assets/js/admin/settings.js' );

		$this->enqueue_style( 'jet-abaf-admin-style', 'assets/css/admin/jet-abaf-admin-style.css' );
		$this->enqueue_style( 'jet-abaf-macros-inserter', 'assets/css/admin/macros-inserter.css' );

	}

	/**
	 * Vue templates.
	 *
	 * Returns templates for the settings page.
	 *
	 * @since  3.0.0 Added `settings-field-settings` template.
	 * @since  3.7.0 Added workflows related templates.
	 * @access public
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [
			'settings',
			'settings-advanced',
			'settings-common-config',
			'settings-configuration',
			'settings-field-settings',
			'settings-general',
			'settings-labels',
			'settings-macros-inserter',
			'settings-schedule',
			'settings-tools',
			'settings-workflow-item',
			'settings-workflows',
		];
	}

}