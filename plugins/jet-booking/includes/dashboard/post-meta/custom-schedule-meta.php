<?php
namespace JET_ABAF\Dashboard\Post_Meta;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Custom_Schedule_Meta extends Base_Vue_Meta_Box {

	public function __construct() {

		$this->meta_key = 'jet_abaf_custom_schedule';

		if ( wp_doing_ajax() ) {
			// Saves metadata to the database.
			add_action( 'wp_ajax_' . $this->meta_key, [ $this, 'save_post_meta' ] );
		}

		$this->post_types = jet_abaf()->settings->get( 'apartment_post_type' );

		parent::__construct( $this->post_types );

	}

	public function add_meta_box() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		add_meta_box(
			$this->meta_key,
			__( 'Custom Schedule', 'jet-booking' ),
			[ $this, 'meta_box_callback' ],
			$this->post_types
		);

	}

	/**
	 * Meta box callback.
	 *
	 * Handles the inclusion of the meta box template in the admin panel.
	 *
	 * @since  2.5.0
	 * @access public
	 *
	 * @return void
	 */
	public function meta_box_callback() {
		require_once( JET_ABAF_PATH . 'templates/admin/post-meta/custom-schedule-meta.php' );
	}

	public function get_default_meta() {
		return [
			'custom_schedule' => [
				'use_custom_schedule' => false,
				'days_off'            => [],
				'disable_weekday_1'   => false,
				'check_in_weekday_1'  => false,
				'check_out_weekday_1' => false,
				'disable_weekday_2'   => false,
				'check_in_weekday_2'  => false,
				'check_out_weekday_2' => false,
				'disable_weekday_3'   => false,
				'check_in_weekday_3'  => false,
				'check_out_weekday_3' => false,
				'disable_weekday_4'   => false,
				'check_in_weekday_4'  => false,
				'check_out_weekday_4' => false,
				'disable_weekday_5'   => false,
				'check_in_weekday_5'  => false,
				'check_out_weekday_5' => false,
				'disable_weekend_1'   => false,
				'check_in_weekend_1'  => false,
				'check_out_weekend_1' => false,
				'disable_weekend_2'   => false,
				'check_in_weekend_2'  => false,
				'check_out_weekend_2' => false,
			],
		];
	}

	/**
	 * Parse settings.
	 *
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @since  2.2.5
	 * @since  3.8.1 Removed some settings custom sanitize.
	 * @access public
	 *
	 * @param array $settings List of settings.
	 *
	 * @return array
	 */
	public function parse_settings( $settings ) {

		foreach ( $settings['custom_schedule'] as $key => $value ) {
			switch ( $key ) {
				case 'use_custom_schedule':
				case 'disable_weekday_1':
				case 'check_in_weekday_1':
				case 'check_out_weekday_1':
				case 'disable_weekday_2':
				case 'check_in_weekday_2':
				case 'check_out_weekday_2':
				case 'disable_weekday_3':
				case 'check_in_weekday_3':
				case 'check_out_weekday_3':
				case 'disable_weekday_4':
				case 'check_in_weekday_4':
				case 'check_out_weekday_4':
				case 'disable_weekday_5':
				case 'check_in_weekday_5':
				case 'check_out_weekday_5':
				case 'disable_weekend_1':
				case 'check_in_weekend_1':
				case 'check_out_weekend_1':
				case 'disable_weekend_2':
				case 'check_in_weekend_2':
				case 'check_out_weekend_2':
					$settings['custom_schedule'][ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
					break;

				default:
					$settings['custom_schedule'][ $key ] = $value;
					break;
			}
		}

		return $settings;

	}

	public function assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		parent::assets();

		wp_enqueue_script(
			'jet-abaf-schedule-manager',
			JET_ABAF_URL . 'assets/js/admin/schedule-manager.js',
			[],
			JET_ABAF_VERSION,
			true
		);

		wp_enqueue_script(
			'jet-abaf-meta-custom-schedule',
			JET_ABAF_URL . 'assets/js/admin/meta-custom-schedule.js',
			[ 'jet-abaf-meta-extras', 'jet-abaf-schedule-manager', 'vuejs-datepicker' ],
			JET_ABAF_VERSION,
			true
		);

	}

	protected function get_vue_templates() {
		return [ [
			'dir'  => 'jet-abaf-settings',
			'file' => 'settings-schedule',
		] ];
	}

}