<?php
namespace JET_ABAF\WC_Integration;

use JET_ABAF\Price;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class WC_Cart_Manager {

	public function __construct() {

		// Validate data before adding product to cart.
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_custom_fields_data' ], 10, 3 );

		// Handle adding multiple instances of a product to the cart.
		add_action( 'woocommerce_add_to_cart', [ $this, 'handle_multiple_add_to_cart' ], 10, 6  );

		// Force individual quantities for booking products in the cart.
		add_filter( 'woocommerce_cart_item_quantity', [ $this, 'force_individual_quantities' ], 10, 3 );

		// Add posted booking data to the cart item.
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_custom_cart_item_data' ], 10, 4 );

		// Adjust the price of the booking product based on booking properties
		add_filter( 'woocommerce_add_cart_item', [ $this, 'add_cart_item' ], 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'get_cart_item_from_session' ], 10, 3 );

		// Add booking cart item info.
		add_filter( 'jet-booking/wc-integration/cart-info', [ $this, 'add_booking_cart_info' ], 10, 4 );

	}

	/**
	 * Validate custom fields data.
	 *
	 * Validate booking product custom dates input field value when a booking is added to the cart.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added guests options validation.
	 * @since  3.7.0 Added user email field & time restriction validation.
	 * @since  3.8.0 Added quantity validation.
	 *
	 * @param bool  $passed     Validation.
	 * @param int   $product_id Product ID.
	 * @param mixed $quantity   Products quantity.
	 *
	 * @return bool
	 */
	public function validate_custom_fields_data( $passed, $product_id, $quantity ) {

		$product = wc_get_product( $product_id );

		if ( ! jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			return $passed;
		}

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $product_id, 'jet-booking', 60 );

		// phpcs:disable WordPress.Security.NonceVerification
		if ( empty( $_POST['jet_abaf_field'] ) ) {
			wc_add_notice( __( 'Dates is a required field(s).', 'jet-booking' ), 'error' );

			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$dates = jet_abaf()->wc->mode->get_transformed_dates( sanitize_text_field( wp_unslash( $_POST['jet_abaf_field'] ) ), $product_id );

		if ( ! $dates ) {
			wc_add_notice( __( 'Dates is a required field(s).', 'jet-booking' ), 'error' );

			return false;
		}

		$now = current_time( 'timestamp' );

		if ( date( 'Ymd', $dates[0] ) < date( 'Ymd', $now ) ) {
			wc_add_notice( __( 'You must choose a future date.', 'jet-booking' ), 'error' );

			return false;
		}

		$start_day_offset = jet_abaf()->settings->get_config_setting( $product_id, 'start_day_offset' );

		if ( $start_day_offset ) {
			$start_date = strtotime( "midnight +{$start_day_offset} days", $now );

			if ( $dates[0] < $start_date ) {
				/* translators: next available date */
				wc_add_notice( sprintf( __( 'The earliest booking possible is currently %s.', 'jet-booking' ), date_i18n( get_option( 'date_format', 'F j, Y' ), $start_date ) ), 'error' );

				return false;
			}
		}

		if ( jet_abaf()->settings->get( 'timepicker' ) && ( empty( $_POST['check-in-time'] ) || empty( $_POST['check-out-time'] ) ) ) {
			wc_add_notice( __( 'Time is a required field(s).', 'jet-booking' ), 'error' );

			return false;
		}

		if ( ! jet_abaf()->settings->get( 'disable_email_field' ) ) {
			if ( empty( $_POST['jet_abaf_user_email'] ) ) {
				wc_add_notice( __( 'E-mail address is a required field.', 'jet-booking' ), 'error' );

				return false;
			} else if ( ! is_email( wp_unslash( $_POST['jet_abaf_user_email'] ) ) ) {
				wc_add_notice( __( 'E-mail address is invalid.', 'jet-booking' ), 'error' );

				return false;
			}
		}

		if ( ! empty( $_POST['jet_abaf_guests'] ) ) {
			$message    = '';
			$min_guests = get_post_meta( $product->get_id(), '_jet_booking_min_guests', true ) ?: 1;
			$max_guests = get_post_meta( $product->get_id(), '_jet_booking_max_guests', true ) ?: 1;

			if ( $_POST['jet_abaf_guests'] < $min_guests ) {
				/* translators: minimum guests number */
				$message = sprintf( __( 'The minimum guests number per booking is %s.', 'jet-booking' ), $min_guests );
			} elseif ( $_POST['jet_abaf_guests'] > $max_guests ) {
				/* translators: maximum guests number */
				$message = sprintf( __( 'The maximum guests number per booking is %s.', 'jet-booking' ), $max_guests );
			}

			if ( ! empty( $message ) ) {
				wc_add_notice( $message, 'error' );

				return false;
			}
		}

		$booking = [
			'apartment_id'   => $product_id,
			'check_in_date'  => absint( $dates[0] ) + 1,
			'check_out_date' => $dates[0] === $dates[1] ? absint( $dates[1] ) + 12 * HOUR_IN_SECONDS : absint( $dates[1] ),
		];

		if ( ! empty( $_POST['jet_abaf_units'] ) ) {
			$booked_units = jet_abaf()->db->get_booked_units( $booking );

			foreach ( $booked_units as $booked_unit ) {
				if ( absint( $_POST['jet_abaf_units'] ) === absint( $booked_unit['apartment_unit'] ) ) {
					wc_add_notice( __( 'Booking could not be added. Selected unit is no longer available.', 'jet-booking' ), 'error' );

					return false;
				}
			}

			$booking['apartment_unit'] = absint( $_POST['jet_abaf_units'] );
		} else {
			$booking['apartment_unit'] = jet_abaf()->db->get_available_unit( $booking );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		if ( $quantity && $quantity > 1 ) {
			$units_per_request = apply_filters( 'jet-booking/wc-integration/units-per-request', 25, $product_id, $quantity );

			if ( $quantity > $units_per_request ) {
				/* translators: per request units number */
				wc_add_notice( sprintf( __( 'You can add up to %d units per request.', 'jet-booking' ), $units_per_request ), 'error' );

				return false;
			}

			$available_units       = jet_abaf()->db->get_available_units( $booking );
			$available_units_count = count( $available_units );

			if ( $available_units_count < $quantity ) {
				/* translators: available units number */
				wc_add_notice( sprintf( __( 'Not enough available units. Only %d available.', 'jet-booking' ), $available_units_count ), 'error' );

				return false;
			}
		}

		$is_available       = jet_abaf()->db->booking_availability( $booking );
		$is_dates_available = jet_abaf()->db->is_booking_dates_available( $booking );

		if ( ! $is_available && ! $is_dates_available ) {
			wc_add_notice( __( 'Booking could not be added. Selected dates are no longer available.', 'jet-booking' ), 'error' );

			return false;
		}

		return $passed;

	}

	/**
	 * Handle adding multiple instances of a product to the cart.
	 *
	 * Processes instances where multiple quantities of a booking product are added to
	 * the cart, ensuring each item is added as an individual cart item.
	 *
	 * @param string $cart_item_key  The cart item key.
	 * @param int    $product_id     The product ID.
	 * @param int    $quantity       The quantity of the product added to the cart.
	 * @param int    $variation_id   The variation ID.
	 * @param array  $variation      The variation data.
	 * @param array  $cart_item_data Additional cart item data.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function handle_multiple_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		if ( ! $quantity || $quantity === 1 ) {
			return;
		}

		if ( ! jet_abaf()->wc->mode->is_booking_product( wc_get_product( $product_id ) ) ) {
			return;
		}

		// Remove the initial item added
		WC()->cart->remove_cart_item( $cart_item_key );

		// Limit how many you process per request.
		$units_per_request = apply_filters( 'jet-booking/wc-integration/units-per-request', 25, $product_id, $quantity );

		// Add the item multiple times as individual items.
		for ( $i = 0; $i < $quantity && $i < $units_per_request; $i ++ ) {
			WC()->cart->add_to_cart( $product_id, 1, $variation_id, $variation, $cart_item_data );
		}

	}

	/**
	 * Force individual quantities for booking products in the cart.
	 *
	 * Adjusts the product quantity input to ensure booking products are treated as sold individually.
	 *
	 * @param string $product_quantity HTML for the quantity input field.
	 * @param string $cart_item_key    Unique identifier for the cart item.
	 * @param array  $cart_item        Cart item data array.
	 *
	 * @return string Modified HTML for the quantity input field.
	 */
	public function force_individual_quantities( $product_quantity, $cart_item_key, $cart_item ) {

		$product = $cart_item['data'];

		if ( jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			/* translators: cart item quantity */
			$product_quantity = sprintf( '<span class="quantity">%s</span>', $cart_item['quantity'] );
			$product_quantity .= woocommerce_quantity_input( [
				'input_name'   => "cart[{$cart_item_key}][qty]",
				'input_value'  => $cart_item['quantity'],
				'max_value'    => 1,
				'min_value'    => 1,
				'product_name' => $product->get_name(),
			], $product, false );
		}

		return $product_quantity;

	}

	/**
	 * Add custom cart item data.
	 *
	 * Add posted custom booking data to cart item.
	 *
	 * @since  3.0.0
	 * @since  3.3.0 Fixed One-day bookings.
	 * @since  3.6.0 Added attributes & guests handling.
	 * @since  3.7.0 Added user email & check in/out time handling.
	 * @since  4.0.0 Added a filter hook to control booking data.
	 * @access public
	 *
	 * @param array $cart_item_data Cart items list..
	 * @param int   $product_id     ID of the added product.
	 * @param int   $variation_id   ID of the added product variation.
	 * @param int   $quantity       Quantity of items.
	 *
	 * @return array
	 */
	public function add_custom_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $product_id, 'jet-booking', 60 );

		$product = wc_get_product( $product_id );

		// phpcs:disable WordPress.Security.NonceVerification
		if ( jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			$dates      = ! empty( $_POST['jet_abaf_field'] ) ? jet_abaf()->wc->mode->get_transformed_dates( sanitize_text_field( wp_unslash( $_POST['jet_abaf_field'] ) ), $product_id ) : [];
			$user_email = ! empty( $_POST['jet_abaf_user_email'] ) ? sanitize_email( wp_unslash( $_POST['jet_abaf_user_email'] ) ) : '';

			if ( empty( $user_email ) && is_user_logged_in() ) {
				$user       = wp_get_current_user();
				$user_email = $user->user_email ?? '';
			}

			$args = [
				'apartment_id'   => $product_id,
				'apartment_unit' => ! empty( $_POST['jet_abaf_units'] ) ? absint( $_POST['jet_abaf_units'] ) : '',
				'status'         => 'on-hold',
				'check_in_date'  => $dates[0] ?? '',
				'check_out_date' => $dates[1] ?? '',
				'user_id'        => is_user_logged_in() ? get_current_user_id() : null,
				'user_email'     => $user_email,
			];

			if ( jet_abaf()->settings->get( 'timepicker' ) ) {
				$args['check_in_time']  = ! empty( $_POST['check-in-time'] ) ? sanitize_text_field( wp_unslash( $_POST['check-in-time'] ) ) : '';
				$args['check_out_time'] = ! empty( $_POST['check-out-time'] ) ? sanitize_text_field( wp_unslash( $_POST['check-out-time'] ) ) : '';
			}

			$args       = apply_filters( 'jet-booking/wc-integration/booking-data', $args, $product_id );
			$booking_id = jet_abaf()->db->insert_booking( $args );

			if ( $booking_id ) {
				$args       = jet_abaf()->db->inserted_booking;
				$attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );

				foreach ( $attributes as $attribute ) {
					if ( $attribute->is_taxonomy() && ! empty( $_POST[ 'jet_abaf_product_' . $attribute->get_name() ] ) ) {
						$args[ jet_abaf()->wc->mode->additional_services_key ][ $attribute->get_name() ] = jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_POST[ 'jet_abaf_product_' . $attribute->get_name() ] ) ); // phpcs:ignore
					}
				}

				$has_guests = get_post_meta( $product->get_id(), '_jet_booking_has_guests', true );

				if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) && ! empty( $_POST['jet_abaf_guests'] ) ) {
					$args['__guests'] = absint( $_POST['jet_abaf_guests'] );
				}

				$cart_item_data[ jet_abaf()->wc->data_key ] = apply_filters( 'jet-booking/wc-integration/cart-item-data', $args, $cart_item_data, $product, $variation_id, $quantity );

				jet_abaf()->wc->schedule->schedule_single_event( [ $booking_id ] );

				do_action( 'jet-booking/wc-integration/booking-inserted', $booking_id );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return $cart_item_data;

	}

	/**
	 * Add cart item.
	 *
	 * Adjust the price of the booking product based on booking properties.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added attributes & guests cost calculation.
	 *
	 * @param array  $cart_item_data WooCommerce cart item data array.
	 * @param string $cart_item_key  WooCommerce cart item key.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function add_cart_item( $cart_item_data, $cart_item_key ) {

		$product = $cart_item_data['data'];

		if ( jet_abaf()->wc->mode->is_booking_product( $product ) && ! empty( $cart_item_data[ jet_abaf()->wc->data_key ] ) ) {
			$booking_data  = $cart_item_data[ jet_abaf()->wc->data_key ];
			$price         = new Price( $booking_data['apartment_id'] );
			$booking_price = $price->get_booking_price( $booking_data );
			$guests        = 0;
			$has_guests    = get_post_meta( $product->get_id(), '_jet_booking_has_guests', true );

			if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) && ! empty( $booking_data['__guests'] ) ) {
				$guests            = $booking_data['__guests'];
				$guests_multiplier = get_post_meta( $product->get_id(), '_jet_booking_guests_multiplier', true );

				if ( filter_var( $guests_multiplier, FILTER_VALIDATE_BOOLEAN ) ) {
					$booking_price *= $guests;
				}
			}

			if ( ! empty( $booking_data[ jet_abaf()->wc->mode->additional_services_key ] ) ) {
				$interval      = jet_abaf()->tools->get_booking_period_interval( $booking_data['check_in_date'], $booking_data['check_out_date'], $booking_data['apartment_id'] );
				$booking_price += jet_abaf()->wc->mode->attributes->get_attributes_cost( $booking_data[ jet_abaf()->wc->mode->additional_services_key ], $interval, $booking_data['apartment_id'], $guests );
			}

			$product->set_price( $booking_price );
		}

		return $cart_item_data;

	}

	/**
	 * Get cart item from session.
	 *
	 * Get data from the session and add to the cart item's meta.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array  $cart_item_data WooCommerce cart item data array.
	 * @param array  $values         Cart values array.
	 * @param string $cart_item_key  WooCommerce cart item key.
	 *
	 * @return array|mixed
	 */
	public function get_cart_item_from_session( $cart_item_data, $values, $cart_item_key ) {

		if ( ! empty( $values[ jet_abaf()->wc->data_key ] ) ) {
			$cart_item_data[ jet_abaf()->wc->data_key ] = $values[ jet_abaf()->wc->data_key ];
			$cart_item_data                             = $this->add_cart_item( $cart_item_data, $cart_item_key );
		}

		return $cart_item_data;

	}

	/**
	 * Add booking cart info.
	 *
	 * Extend cart info data with booking related information.
	 *
	 * @since 3.6.0
	 * @since 3.7.1 Added units info display.
	 *
	 * @param array      $result    List of cart info.
	 * @param array      $data      Booking data list.
	 * @param array      $form_data Submitted form data list.
	 * @param string|int $form_id   Submitted form id.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function add_booking_cart_info( $result, $data, $form_data, $form_id ) {

		if ( ! empty( $data['apartment_unit'] ) ) {
			$booking = jet_abaf_get_booking( $data['booking_id'] );

			if ( $booking ) {
				array_unshift( $result, [
					'key'     => __( 'Unit', 'jet-booking' ),
					'display' => jet_abaf()->macros->macros_handler->do_macros( '%booking_unit_title%', $booking ),
				] );
			}
		}

		if ( ! empty( $data['__guests'] ) ) {
			$result[] = [
				'key'     => __( 'Guests', 'jet-booking' ),
				'display' => $data['__guests'],
			];
		}

		if ( ! empty( $data[ jet_abaf()->wc->mode->additional_services_key ] ) ) {
			$interval   = jet_abaf()->tools->get_booking_period_interval( $data['check_in_date'], $data['check_out_date'], $data['apartment_id'] );
			$attributes = jet_abaf()->wc->mode->attributes->get_attributes_for_display( $data[ jet_abaf()->wc->mode->additional_services_key ], $interval, $data['apartment_id'] );

			foreach ( $attributes as $attribute ) {
				$result[] = [
					'key'     => $attribute['label'],
					'display' => $attribute['value'],
				];
			}
		}

		return $result;

	}

}
