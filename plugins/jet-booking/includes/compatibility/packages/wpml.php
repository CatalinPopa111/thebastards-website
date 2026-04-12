<?php
namespace JET_ABAF\Compatibility\Packages;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class WPML {

	public function __construct() {

		add_filter( 'jet-abaf/db/initial-apartment-id', [ $this, 'set_initial_booking_item_id' ] );
		add_filter( 'jet-booking/wc-integration/apartment-id', [ $this, 'set_booking_item_id' ] );
		add_filter( 'jet-booking/tools/post-type-args', [ $this, 'set_additional_post_type_args' ] );
		add_filter( 'jet-booking/settings/on-save/value', [ $this, 'register_settings_input_for_string_translation' ], 10, 2 );
		add_filter( 'jet-booking/compatibility/translate-labels', [ $this, 'set_translation_for_settings_input' ] );
		add_filter( 'jet-engine/forms/handler/wp_redirect_url', [ $this, 'prepare_redirect_link' ] );

		add_action( 'jet-abaf/dashboard/bookings-page/before-page-config', [ $this, 'set_required_page_language' ] );

		// Add to cart sold individually exception.
		add_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart', [ $this, 'add_to_cart_sold_individually_exception' ], 9, 4 );

	}

	/**
	 * Register settings input for string translation.
	 *
	 * Registers settings input fields an individual text string for translation.
	 *
	 * @since  2.7.0
	 * @access public
	 *
	 * @param string $value   Input field value.
	 * @param string $setting Setting key name.
	 *
	 * @return mixed
	 */
	public function register_settings_input_for_string_translation( $value, $setting ) {

		$default_lang = apply_filters( 'wpml_default_language', NULL );
		$current_lang = apply_filters( 'wpml_current_language', NULL );

		$setting = str_replace( '_', '-', $setting );
		$setting = str_replace( 'labels-', '', $setting );

		if ( $current_lang === $default_lang && jet_abaf()->settings->get_labels( $setting ) ) {
			do_action( 'wpml_register_single_string', 'JetBooking Custom Calendar Labels', 'Custom Label - ' . $value, $value );
		}

		return $value;

	}

	/**
	 * Set translations for settings input.
	 *
	 * Handle translations for individual settings input text string.
	 *
	 * @since  2.7.0
	 * @access public
	 *
	 * @param array $labels List of labels.
	 *
	 * @return mixed
	 */
	public function set_translation_for_settings_input( $labels ) {

		$default_lang = apply_filters( 'wpml_default_language', NULL );
		$current_lang = apply_filters( 'wpml_current_language', NULL );

		if ( $current_lang === $default_lang ) {
			return $labels;
		}

		foreach ( $labels as $key => $label ) {
			if ( 'month-name' === $key ) {
				$label = implode( ', ', $label );
			}

			$label = apply_filters( 'wpml_translate_single_string', $label, 'JetBooking Custom Calendar Labels', 'Custom Label - ' . $label, $current_lang );

			if ( 'month-name' === $key ) {
				$label = jet_abaf()->settings->get_array_from_string( $label );
			}

			$labels[ $key ] = $label;
		}

		return $labels;

	}

	/**
	 * Set initial booking item id.
	 *
	 * Returns a booking item id for the default site language.
	 *
	 * @since  2.5.5
	 * @access public
	 *
	 * @param int|string $id Apartment post type ID.
	 *
	 * @return mixed|void
	 */
	public function set_initial_booking_item_id( $id ) {

		$default_lang = apply_filters( 'wpml_default_language', NULL );

		return apply_filters( 'wpml_object_id', $id, get_post_type( $id ), FALSE, $default_lang );

	}

	/**
	 * Set booking item id.
	 *
	 * Returns a booking item id for current site language.
	 *
	 * @since  2.5.5
	 * @access public
	 *
	 * @param int|string $id Apartment post type ID.
	 *
	 * @return mixed|void
	 */
	public function set_booking_item_id( $id ) {

		$current_language = apply_filters( 'wpml_current_language', NULL );

		return apply_filters( 'wpml_object_id', $id, get_post_type( $id ), FALSE, $current_language );

	}

	/**
	 * Set additional post type args.
	 *
	 * Returns booking post type arguments with additional option so we can see only default language posts.
	 *
	 * @since  2.5.5
	 * @access public
	 *
	 * @param array $args List of post arguments.
	 *
	 * @return mixed
	 */
	public function set_additional_post_type_args( $args ) {

		$args['suppress_filters'] = 0;

		return $args;

	}

	/**
	 * Set required page language.
	 *
	 * Switch language to default for proper posts display in bookings list.
	 *
	 * @since  2.5.5
	 * @access public
	 */
	public function set_required_page_language() {

		$default_lang = apply_filters( 'wpml_default_language', NULL );

		do_action( 'wpml_switch_language', $default_lang );

	}

	/**
	 * Prepare redirect link.
	 *
	 * @param string $url Redirect link.
	 *
	 * @return mixed|void
	 */
	public function prepare_redirect_link( $url ) {

		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
			$url  = apply_filters( 'wpml_permalink', $url, $lang, true );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return $url;

	}

	/**
	 * Add to cart sold individually exception.
	 *
	 * Manages exceptions for adding products to the cart when they are classified
	 * as sold individually under specific conditions such as booking mode.
	 *
	 * @param bool  $found_in_cart  Whether the product is already in the cart.
	 * @param int   $product_id     The product ID.
	 * @param int   $variation_id   The variation ID of the product if applicable.
	 * @param array $cart_item_data Additional cart item data.
	 *
	 * @return bool Returns true if the product is found in the cart, false otherwise.
	 */
	public function add_to_cart_sold_individually_exception( $found_in_cart, $product_id, $variation_id, $cart_item_data ) {

		if ( 'plain' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			return $found_in_cart;
		}

		global $woocommerce_wpml;

		if ( empty( $woocommerce_wpml ) ) {
			return $found_in_cart;
		}

		remove_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart', [ $woocommerce_wpml->cart, 'add_to_cart_sold_individually_exception' ] );

		$post_id = $product_id;

		if ( $variation_id ) {
			$post_id = $variation_id;
		}

		$product = wc_get_product( $post_id );

		if ( jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			return $found_in_cart;
		}

		foreach ( WC()->cart->cart_contents as $cart_item ) {
			if ( $woocommerce_wpml->cart->sold_individually_product( $cart_item, $cart_item_data, $post_id ) ) {
				$found_in_cart = true;
				break;
			}
		}

		return $found_in_cart;

	}

}

new WPML();