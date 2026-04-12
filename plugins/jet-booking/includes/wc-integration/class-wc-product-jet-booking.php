<?php

use JET_ABAF\Price;

class WC_Product_Jet_Booking extends WC_Product {

	/**
	 * Initialize JetBooking product.
	 *
	 * @param WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {
		parent::__construct( $product );
	}

	/**
	 * Get type.
	 *
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'jet_booking';
	}

	/**
	 * Is sold individually.
	 *
	 * Check if a product is sold individually (no quantities).
	 *
	 * @since 3.3.1 Added `woocommerce_is_sold_individually` hook.
	 * @since 3.8.0 Handled sold individually parameter.
	 *
	 * @return boolean
	 */
	public function is_sold_individually() {

		$units             = jet_abaf()->db->get_apartment_units( $this->get_id() );
		$units_count       = ! empty( $units ) ? count( $units ) : 0;
		$sold_individually = ! $units_count || 1 === $units_count || ! filter_var( $this->get_meta( '_jet_booking_multiple_units' ), FILTER_VALIDATE_BOOLEAN );

		return apply_filters( 'woocommerce_is_sold_individually', $sold_individually, $this );

	}

	/**
	 * Is purchasable.
	 *
	 * Returns false if the product cannot be bought.
	 *
	 * @access public
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', true, $this );
	}

	/**
	 * Ger virtual.
	 *
	 * Set product as virtual.
	 *
	 * @since 3.3.1 Added `jet-booking/wc-integration/booking-product/get-virtual` hook.
	 *
	 * @param string $context What the value is for. Valid values are `view` and `edit`.
	 *
	 * @return boolean
	 */
	public function get_virtual( $context = 'view' ) {
		return apply_filters( 'jet-booking/wc-integration/booking-product/get-virtual', $this->get_prop( 'virtual', $context ), $this );
	}

	/**
	 * Add to cart url.
	 *
	 * Get the add to url used mainly in loops.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		return apply_filters( 'woocommerce_product_add_to_cart_url', $this->get_permalink(), $this );
	}

	/**
	 * Add to cart text.
	 *
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'View details', 'woocommerce' ), $this );
	}

	/**
	 * Single add to cart text.
	 *
	 * Get the add to cart button text for the single page.
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		return apply_filters( 'woocommerce_product_single_add_to_cart_text', __( 'Book now', 'jet-booking' ), $this );
	}

	/**
	 * Add to cart description.
	 *
	 * Get the add to cart button text description - used in aria tags.
	 *
	 * @since  3.3.0
	 * @access public
	 *
	 * @return string
	 */
	public function add_to_cart_description() {
		/* translators: %s: Product title */
		return apply_filters( 'woocommerce_product_add_to_cart_description', sprintf( __( 'Book &ldquo;%s&rdquo;', 'woocommerce' ), $this->get_name() ), $this );
	}

	/**
	 * Get price HTML.
	 *
	 * @since 3.5.0
	 *
	 * @param string $price Product price.
	 *
	 * @return string
	 */
	public function get_price_html( $price = '' ) {

		$base_price = new Price( $this->get_id() );
		$min_price  = $base_price->get_price_value( 'min' );
		$max_price  = $base_price->get_price_value( 'max' );

		if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			$display_price = wc_get_price_including_tax( $this, [ 'qty' => 1, 'price' => $min_price ] );
		} else {
			$display_price = wc_get_price_excluding_tax( $this, [ 'qty' => 1, 'price' => $min_price ] );
		}

		$display_price_suffix  = wc_price( apply_filters( 'woocommerce_product_get_price', $display_price, $this ) ) . $this->get_price_suffix();
		$original_price_suffix = wc_price( $display_price ) . $this->get_price_suffix();

		if ( $original_price_suffix !== $display_price_suffix ) {
			$price_html = "<del>{$original_price_suffix}</del><ins>{$display_price_suffix}</ins>";
		} elseif ( $display_price ) {
			$default_price = $base_price->get_default_price();

			if ( $default_price !== $min_price || $default_price !== $max_price ) {
				$price_html = sprintf( __( 'From: %s', 'jet-bookings' ), wc_price( $display_price ) ) . $this->get_price_suffix();
			} else {
				$price_html = wc_price( $display_price ) . $this->get_price_suffix();
			}
		} else {
			$price_html = '';
		}

		return apply_filters( 'woocommerce_get_price_html', $price_html, $this );

	}

	/**
	 * Get max purchase quantity.
	 *
	 * Get the maximum quantity which can be purchased at once.
	 *
	 * @since  3.8.0
	 *
	 * @return int Quantity or -1 if unlimited.
	 */
	public function get_max_purchase_quantity() {
		return $this->is_sold_individually() ? 1 : count( jet_abaf()->db->get_apartment_units( $this->get_id() ) );
	}

}
