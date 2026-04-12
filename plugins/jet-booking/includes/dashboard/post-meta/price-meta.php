<?php
namespace JET_ABAF\Dashboard\Post_Meta;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Price_Meta extends Base_Vue_Meta_Box {

	public function __construct() {

		$this->meta_key = 'jet_abaf_price';

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
			__( 'Pricing Settings', 'jet-booking' ),
			[ $this, 'meta_box_callback' ],
			$this->post_types
		);

	}

	/**
	 * Meta box callback.
	 *
	 * Handles the inclusion of the meta box template in the admin panel.
	 *
	 * @since 2.2.5
	 *
	 * @return void
	 */
	public function meta_box_callback() {
		require_once( JET_ABAF_PATH . 'templates/admin/post-meta/price-meta.php' );
	}

	public function get_default_meta() {

		$apartment_price = get_post_meta( get_the_ID(), '_apartment_price', true );

		return [
			'_apartment_price' => $apartment_price,
			'_pricing_rates'   => [],
			'_seasonal_prices' => [],
			'_weekend_prices'  => $this->get_default_weekend_prices( $apartment_price ),
		];

	}

	public function parse_settings( $settings ) {

		foreach ( $settings as $key => $value ) {
			switch ( $key ) {
				case '_apartment_price':
					$value = empty( $value ) ? 0 : $value + 0;
					break;

				case '_pricing_rates':
					$value = is_array( $value ) ? $value : [];
					break;

				case '_weekend_prices':
					foreach ( $value as $weekend_key => $weekend_value ) {
						$weekend_value['active'] = filter_var( $weekend_value['active'], FILTER_VALIDATE_BOOLEAN );
						$weekend_value['price']  = empty( $weekend_value['price'] ) ? 0 : $weekend_value['price'] + 0;

						$value[ $weekend_key ] = $weekend_value;
					}

					break;

				case '_seasonal_prices':
					foreach ( $value as $seasonal_key => $seasonal_value ) {
						$seasonal_value = $this->parse_settings( $seasonal_value );

						$seasonal_value['_pricing_rates'] = $seasonal_value['_pricing_rates'] ?? [];
						$seasonal_value['enable_config']  = filter_var( $seasonal_value['enable_config'], FILTER_VALIDATE_BOOLEAN );

						$value[ $seasonal_key ] = $seasonal_value;
					}

					break;
			}

			$settings[ $key ] = $value;
		}

		return $settings;

	}

	protected function backward_save_post_meta( $meta ) {

		if ( isset( $meta['_apartment_price'] ) ) {
			update_post_meta( $meta['ID'], '_apartment_price', $meta['_apartment_price'] );
		}

		if ( isset( $meta['_pricing_rates'] ) ) {
			update_post_meta( $meta['ID'], '_pricing_rates', $meta['_pricing_rates'] );
		}

	}

	public function assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		parent::assets();

		wp_enqueue_script(
			'jet-abaf-meta-price',
			JET_ABAF_URL . 'assets/js/admin/meta-price.js',
			[ 'jet-abaf-meta-extras', 'vuejs-datepicker' ],
			JET_ABAF_VERSION,
			true
		);

		wp_localize_script( 'jet-abaf-meta-price', 'jetAbafAssets', [
			'confirm_message'        => __( 'Are you sure?', 'jet-booking' ),
			'price_label'            => __( 'Price:', 'jet-booking' ),
			'season_label'           => __( 'Season:', 'jet-booking' ),
			'weekdays_label'         => [
				'sun' => __( 'Sun', 'jet-booking' ),
				'mon' => __( 'Mon', 'jet-booking' ),
				'tue' => __( 'Tue', 'jet-booking' ),
				'wed' => __( 'Wed', 'jet-booking' ),
				'thu' => __( 'Thu', 'jet-booking' ),
				'fri' => __( 'Fri', 'jet-booking' ),
				'sat' => __( 'Sat', 'jet-booking' ),
			],
			'period_repeats_seasons' => [
				[
					'label' => __( 'Not repeat', 'jet-booking' ),
					'value' => 'not_repeat',
				],
				[
					'label' => __( 'Week', 'jet-booking' ),
					'value' => 'week',
				],
				[
					'label' => __( 'Month', 'jet-booking' ),
					'value' => 'month',
				],
				[
					'label' => __( 'Year', 'jet-booking' ),
					'value' => 'year',
				],
			],
			'default_weekend_prices' => $this->get_default_weekend_prices(),
		] );

	}

	/**
	 * Retrieves the default pricing structure for the weekend.
	 *
	 * @since 2.2.5
	 *
	 * @param float $price The base price to set for all days.
	 *
	 * @return array
	 */
	public function get_default_weekend_prices( $price = 0 ) {
		return [
			'sun' => [
				'price'  => $price,
				'active' => false,
			],
			'mon' => [
				'price'  => $price,
				'active' => false,
			],
			'tue' => [
				'price'  => $price,
				'active' => false,
			],
			'wed' => [
				'price'  => $price,
				'active' => false,
			],
			'thu' => [
				'price'  => $price,
				'active' => false,
			],
			'fri' => [
				'price'  => $price,
				'active' => false,
			],
			'sat' => [
				'price'  => $price,
				'active' => false,
			],
		];
	}

}
