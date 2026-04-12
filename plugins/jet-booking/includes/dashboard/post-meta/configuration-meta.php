<?php
namespace JET_ABAF\Dashboard\Post_Meta;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Configuration_Meta extends Base_Vue_Meta_Box {

	public function __construct() {

		$this->meta_key = 'jet_abaf_configuration';

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
			__( 'Date Picker Config', 'jet-booking' ),
			[ $this, 'meta_box_callback' ],
			$this->post_types
		);

	}

	/**
	 * Meta box callback.
	 *
	 * Handles the inclusion of the meta box template in the admin panel.
	 *
	 * @since  2.6.0
	 * @access public
	 *
	 * @return void
	 */
	public function meta_box_callback() {
		require_once( JET_ABAF_PATH . 'templates/admin/post-meta/configuration-meta.php' );
	}

	/**
	 * Default meta.
	 *
	 * Returns default meta values list.
	 *
	 * @since  2.6.3 Added `one_day_bookings`, `weekly_bookings`, `week_offset` meta parameters.
	 * @access public
	 *
	 * @return array[]
	 */
	public function get_default_meta() {
		return [
			'config' => [
				'enable_config'         => false,
				'booking_period'        => 'per_nights',
				'allow_checkout_only'   => false,
				'one_day_bookings'      => false,
				'weekly_bookings'       => false,
				'week_offset'           => '',
				'start_day_offset'      => '',
				'min_days'              => '',
				'max_days'              => '',
				'end_date_type'         => 'none',
				'end_date_range_number' => '1',
				'end_date_range_unit'   => 'year',
			],
		];
	}

	/**
	 * Parse settings.
	 *
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @since  2.6.3 Added `one_day_bookings`, `weekly_bookings`, `week_offset` meta parameters parsing.
	 * @access public
	 *
	 * @param array $settings List of settings.
	 *
	 * @return array
	 */
	public function parse_settings( $settings ) {

		foreach ( $settings['config'] as $key => $value ) {
			switch ( $key ) {
				case 'enable_config':
				case 'allow_checkout_only':
				case 'one_day_bookings':
				case 'weekly_bookings':
					$settings['config'][ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
					break;

				default:
					$settings['config'][ $key ] = $value;
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
			'jet-abaf-meta-configuration',
			JET_ABAF_URL . 'assets/js/admin/meta-configuration.js',
			[ 'jet-abaf-meta-extras' ],
			JET_ABAF_VERSION,
			true
		);

	}

	protected function get_vue_templates() {
		return [ [
			'dir'  => 'jet-abaf-settings',
			'file' => 'settings-common-config',
		] ];
	}

}
