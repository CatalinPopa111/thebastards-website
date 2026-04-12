<?php
namespace JET_ABAF\WC_Integration\Modes;

use Automattic\WooCommerce\Utilities\OrderUtil;
use JET_ABAF\Price;
use JET_ABAF\WC_Integration\WC_Order_Details_Builder;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Plain {

	/**
	 * Is enabled.
	 *
	 * WooCommerce integration status holder.
	 *
	 * @since  3.0.0 Fixed typo.
	 * @access private
	 *
	 * @var bool
	 */
	private $is_enabled = false;

	/**
	 * Product ID.
	 *
	 * Holds product ID for plain integration.
	 *
	 * @access private
	 *
	 * @var int
	 */
	private $product_id = 0;

	/**
	 * Price key.
	 *
	 * Booking product price key holder.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $price_key = 'wc_booking_price';

	/**
	 * Price adjusted.
	 *
	 * Holds adjustments status.
	 *
	 * @access private
	 *
	 * @var bool
	 */
	private $price_adjusted = false;

	/**
	 * Details.
	 *
	 * WC order details builder object instance holder.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @var WC_Order_Details_Builder|null
	 */
	public $details = null;

	public function __construct() {

		$this->is_enabled = jet_abaf()->settings->wc_integration_enabled();
		$this->product_id = jet_abaf()->settings->get( 'wc_product_id' );

		if ( ! $this->get_status() || ! $this->get_product_id() ) {
			return;
		}

		// Set booking admin page config;
		add_filter( 'jet-abaf/dashboard/helpers/page-config/config', [ $this, 'set_booking_page_config' ] );

		// Form-related
		add_action( 'jet-abaf/form/notification/success', [ $this, 'process_wc_notification' ], 10, 3 );
		add_action( 'jet-abaf/jet-fb/action/success', [ $this, 'process_wc_notification' ], 10, 3 );

		// Cart related
		add_filter( 'woocommerce_get_cart_contents', [ $this, 'set_booking_price' ] );
		add_filter( 'woocommerce_cart_item_name', [ $this, 'set_booking_item_name' ], 10, 2 );
		add_filter( 'woocommerce_checkout_get_value', [ $this, 'maybe_set_checkout_defaults' ], 10, 2 );
		add_filter( 'woocommerce_cart_item_permalink', [ $this, 'woocommerce_cart_item_permalink' ], 10, 3 );
		add_filter( 'woocommerce_cart_item_thumbnail', [ $this, 'woocommerce_cart_item_thumbnail' ], 10, 3 );

		// Order related.
		add_filter( 'woocommerce_order_item_name', [ $this, 'set_booking_item_name' ], 10, 2 );
		add_action( 'woocommerce_thankyou', [ $this, 'order_details' ], 0 );
		add_action( 'woocommerce_view_order', [ $this, 'order_details' ], 0 );
		add_action( 'woocommerce_email_order_meta', [ $this, 'email_order_details' ], 0, 3 );
		// Register Booking Details meta box in the admin order screen.
		add_action( 'add_meta_boxes', [ $this, 'admin_order_booking_details' ] );
		add_action( 'jet-booking/rest-api/add-booking/set-related-order-data', [ $this, 'set_booking_related_order' ], 10, 3 );
		// Process order creation.
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'process_order' ], 20, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'process_order_by_api' ], 20 );

		if ( jet_abaf()->settings->get( 'wc_sync_orders' ) ) {
			add_action( 'jet-booking/db/booking-updated', [ $this, 'update_order_on_status_update' ] );
			// Prevent circular status updates.
			add_action( 'jet-booking/wc-integration/before-update-status', [ $this, 'validate_booking_update' ] );
			add_action( 'jet-booking/wc-integration/before-set-order-data', [ $this, 'validate_booking_update' ] );
		}

		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-order-details-builder.php';
		$this->details = new WC_Order_Details_Builder();

		add_action( 'wp_login', [ $this, 'reset_saved_cart_after_login' ], 20, 2 );

	}

	/**
	 * Get status.
	 *
	 * Return WooCommerce integration status.
	 *
	 * @since  3.0.0 Fixed typo.
	 * @access public
	 *
	 * @return bool
	 */
	public function get_status() {
		return $this->is_enabled;
	}

	/**
	 * Get product ID.
	 *
	 * Return WooCommerce plain integration product ID.
	 *
	 * @access public
	 *
	 * @return int|mixed
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * Process wc notification.
	 *
	 * Process WooCommerce related notification part.
	 *
	 * @param array  $booking Booking arguments.
	 * @param object $action  Smart Notification Action Trait object instance.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function process_wc_notification( $booking, $action, $bookings ) {

		if ( filter_var( $action->getSettings( 'disable_wc_integration' ), FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$price_field = $action->getSettings( 'booking_wc_price' );
		$price       = $price_field && $action->issetRequest( $price_field ) ? floatval( $action->getRequest( $price_field ) ) : false;

		if ( $price ) {
			$cart_item_data[ $this->price_key ] = apply_filters( 'jet-booking/wc-integration/booking-wc-price', $price, $action, $bookings );
		}

		WC()->cart->empty_cart();

		foreach ( $bookings as $item ) {
			$cart_item_data[ jet_abaf()->wc->data_key ]      = $item;
			$cart_item_data[ jet_abaf()->wc->form_data_key ] = $action->getRequest();
			$cart_item_data[ jet_abaf()->wc->form_id_key ]   = $action->getFormId();

			WC()->cart->add_to_cart( $this->get_product_id(), 1, 0, [], $cart_item_data );
			jet_abaf()->wc->schedule->schedule_single_event( [ $item['booking_id'] ] );
		}

		$checkout_fields_map = [];

		foreach ( $action->getSettings() as $key => $value ) {
			if ( false !== strpos( $key, 'wc_fields_map__' ) && ! empty( $value ) ) {
				$checkout_fields_map[ str_replace( 'wc_fields_map__', '', $key ) ] = $value;
			}
		}

		if ( ! empty( $checkout_fields_map ) ) {
			$checkout_fields = [];

			foreach ( $checkout_fields_map as $checkout_field => $form_field ) {
				if ( $action->issetRequest( $form_field ) ) {
					$checkout_fields[ $checkout_field ] = $action->getRequest( $form_field );
				}
			}

			if ( ! empty( $checkout_fields ) ) {
				WC()->session->set( 'jet_booking_fields', $checkout_fields );
			}
		}

		$action->filterQueryArgs( function ( $query_args, $handler, $args ) use ( $action ) {
			$url = apply_filters( 'jet-engine/forms/handler/wp_redirect_url', wc_get_checkout_url() );

			if ( $action->isAjax() ) {
				$query_args['redirect'] = $url;

				return $query_args;
			} else {
				wp_safe_redirect( $url );
				die();
			}
		} );

	}

	/**
	 * Set booking price.
	 *
	 * Set custom price per booking item.
	 *
	 * @since  2.8.0 Refactored.
	 * @since  3.0.0 New way of calculating fallback booking price.
	 * @access public
	 *
	 * @param array $cart_items List cart items.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function set_booking_price( $cart_items ) {

		if ( $this->price_adjusted || empty( $cart_items ) ) {
			return $cart_items;
		}

		foreach ( $cart_items as $item ) {
			if ( ! empty( $item[ jet_abaf()->wc->data_key ] ) ) {
				if ( ! empty( $item[ $this->price_key ] ) ) {
					$booking_price = $item[ $this->price_key ];
				} else {
					$price         = new Price( $item[ jet_abaf()->wc->data_key ]['apartment_id'] );
					$booking_price = $price->get_booking_price( $item[ jet_abaf()->wc->data_key ] );
				}

				if ( $booking_price ) {
					$item['data']->set_price( floatval( $booking_price ) );
				}

				$this->price_adjusted = true;
			}
		}

		return $cart_items;

	}

	/**
	 * Set booking name.
	 *
	 * Set booking item name for checkout, thank you page and e-mail order details.
	 *
	 * @since 2.0.0
	 * @since 2.6.0 Refactor code. Added orders item name handling. Added link for items names.
	 * @since 3.0.0 Small improvements, naming.
	 * @since 4.0.2 Fixed undefined method call.
	 *
	 * @param string       $product_name HTML for the product name.
	 * @param array|object $item         WooCommerce item data list or Instance.
	 *
	 * @return string;
	 */
	public function set_booking_item_name( $product_name, $item ) {

		$booking = [];

		if ( ! empty( $item[ jet_abaf()->wc->data_key ] ) ) {
			$booking = $item[ jet_abaf()->wc->data_key ];
		} elseif ( is_object( $item ) && method_exists( $item, 'get_product_id' ) && $item->get_product_id() === $this->get_product_id() ) {
			$booking = $this->get_booking_by_order_id( $item->get_order_id() );
		}

		$apartment_id = ! empty( $booking['apartment_id'] ) ? absint( $booking['apartment_id'] ) : false;
		$apartment_id = apply_filters( 'jet-booking/wc-integration/apartment-id', $apartment_id );

		if ( ! $apartment_id ) {
			return $product_name;
		}

		/* translators: 1: booking instance label, 2: post permalink, 3: post title */
		return sprintf( '%1$s: <a href="%2$s">%3$s</a>', $this->get_booking_instance_label( $apartment_id ), get_permalink( $apartment_id ), get_the_title( $apartment_id ) );

	}

	/**
	 * Maybe set checkout defaults.
	 *
	 * Set checkout default fields values for checkout forms.
	 *
	 * @since  2.8.0 Refactored.
	 * @access public
	 *
	 * @param mixed  $value Initial value of the input.
	 * @param string $input Name of the input we want to set data for. E.g., billing_country.
	 *
	 * @return mixed The default value.
	 */
	public function maybe_set_checkout_defaults( $value, $input ) {
		if ( function_exists( 'WC' ) && WC()->session ) {
			$fields = WC()->session->get( 'jet_booking_fields' );

			if ( ! empty( $fields ) && ! empty( $fields[ $input ] ) ) {
				return $fields[ $input ];
			} else {
				return $value;
			}
		} else {
			return $value;
		}
	}

	/**
	 * Replaces Booking product permalink with Apartment permalink in the product cart.
	 *
	 * @param bool   $permalink     Product permalink usage.
	 * @param array  $cart_item     The product in the cart.
	 * @param string $cart_item_key Key for the product in the cart.
	 *
	 * @return bool|string Product permalink usage.
	 */
	public function woocommerce_cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {

		if ( ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			return '';
		}

		return $permalink;

	}

	/**
	 * Replaces Booking product thumbnail with Apartment featured image in the product cart.
	 *
	 * @param string $thumbnail     HTML img element.
	 * @param array  $cart_item     The product in the cart.
	 * @param string $cart_item_key Key for the product in the cart.
	 *
	 * @return string HTML img element or empty string on failure.
	 */
	public function woocommerce_cart_item_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {

		if ( ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			$thumbnail = get_the_post_thumbnail( $cart_item[ jet_abaf()->wc->data_key ]['apartment_id'], 'woocommerce_thumbnail' );
		}

		return $thumbnail;

	}

	/**
	 * Order details.
	 *
	 * Show booking related order details on order page.
	 *
	 * @access public
	 *
	 * @param string|int $order_id WC order ID.
	 *
	 * @return void
	 */
	public function order_details( $order_id ) {
		$this->order_details_template( $order_id );
	}

	/**
	 * Email order details.
	 *
	 * Show booking related order details in order email.
	 *
	 * @access public
	 *
	 * @param \WC_Order $order         Order instance.
	 * @param bool      $sent_to_admin If should send to admin.
	 * @param bool      $plain_text    If is plain text email.
	 *
	 * @return void
	 */
	public function email_order_details( $order, $sent_to_admin, $plain_text ) {
		$template = $plain_text ? 'email-order-details-plain' : 'email-order-details-html';
		$this->order_details_template( $order->get_id(), $template );
	}

	/**
	 * Adds the Booking Details meta box to the admin order screen.
	 *
	 * Registers a meta box to display booking details in the admin interface
	 * for orders. Determines the correct screen ID based on custom orders table usage.
	 *
	 * @since  3.8.0
	 * @access public
	 *
	 * @return void
	 */
	public function admin_order_booking_details() {

		$screen = OrderUtil::custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		add_meta_box(
			'jet-abaf-bookings-details-meta',
			__( 'Booking details', 'jet-booking' ),
			[ $this, 'render_meta_box' ],
			$screen
		);

	}

	/**
	 * Render the meta box.
	 *
	 * Displays booking order details in the meta box for a given post.
	 *
	 * @since  3.8.0
	 * @access public
	 *
	 * @param WP_Post|\WC_Order $post The post object or WooCommerce order object.
	 *
	 * @return void
	 */
	public function render_meta_box( $post ) {

		// Get the order object regardless of HPOS status.
		$order   = $post instanceof \WP_Post ? wc_get_order( $post->ID ) : $post;
		$details = $this->get_booking_order_details( $order->get_id() );

		if ( ! $details ) {
			return;
		}

		include JET_ABAF_PATH . 'templates/admin/order/details.php';

	}

	/**
	 * Set booking related order.
	 *
	 * Set WooCommerce related order for booking instance item.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added `$booking` parameter.
	 * @since  3.7.1 Added tax handling.
	 * @since  3.8.1 Refactored
	 * @since  4.0.0 Added booking vendor handling.
	 *
	 * @param array      $order_data Related order data list.
	 * @param string|int $booking_id Created booking ID.
	 * @param array      $booking    Booking data list.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function set_booking_related_order( $order_data, $booking_id, $booking ) {

		$price         = new Price( $booking['apartment_id'] );
		$booking_price = $price->get_booking_price( $booking );
		$product       = wc_get_product( $this->get_product_id() );

		if ( wc_prices_include_tax() ) {
			$booking_price = wc_get_price_excluding_tax( $product, [ 'price' => $booking_price ] );
		}

		$order = wc_create_order();

		$order->add_product( $product, 1, [
			'subtotal' => $booking_price,
			'total'    => $booking_price,
		] );

		$booking_object = jet_abaf_get_booking( $booking_id );
		$booking_vendor = $booking_object->get_booking_vendor();

		if ( $booking_vendor ) {
			$order->update_meta_data( '__jet_booking_vendor', $booking_vendor );
		}

		$order->set_status( $booking_object->get_status() );
		$order->calculate_totals();
		$order->save();

		jet_abaf()->db->update_booking( $booking_id, [ 'order_id' => $order->get_id() ] );

	}

	/**
	 * Update order on status update.
	 *
	 * Update an order status on related booking update.
	 *
	 * @since  2.8.0
	 * @since  3.3.0 Refactored.
	 * @since  3.8.0 Added multiple bookings handling.
	 *
	 * @param string|int $booking_id Booking ID.
	 *
	 * @return void
	 */
	public function update_order_on_status_update( $booking_id ) {

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking || empty( $booking->get_status() ) || empty( $booking->get_order_id() ) ) {
			return;
		}

		$order = wc_get_order( $booking->get_order_id() );

		if ( ! $order ) {
			return;
		}

		$bookings = jet_abaf()->db->query( [ 'order_id' => $booking->get_order_id() ] );

		if ( ! empty( $bookings ) && count( $bookings ) > 1 ) {
			return;
		}

		if ( $order->get_status() === $booking->get_status() ) {
			return;
		}

		remove_action( 'woocommerce_order_status_changed', [ jet_abaf()->wc, 'update_status_on_order_update' ] );

		$order->update_status( $booking->get_status(), sprintf( __( 'Booking #%d updated.', 'jet-booking' ), $booking_id ), true );

	}

	/**
	 * Get booking by order id.
	 *
	 * Returns booking detail by order id.
	 *
	 * @access public
	 *
	 * @param int|string $order_id WC Order ID.
	 *
	 * @return false|mixed|\stdClass
	 */
	public function get_booking_by_order_id( $order_id ) {

		$booking = jet_abaf()->db->get_booking_by( 'order_id', $order_id );

		if ( ! $booking || ! $booking['apartment_id'] ) {
			return false;
		}

		return $booking;

	}

	/**
	 * Get booking instance label.
	 *
	 * Retrieves the singular label of a booking instance's post type based on its ID.
	 *
	 * @since  3.0.0
	 * @since  3.8.0 Refactored.
	 * @access public
	 *
	 * @param int $id The post ID of the booking instance.
	 *
	 * @return string|null The singular label of the post type, or null if not found.
	 */
	public function get_booking_instance_label( $id ) {

		$post_type = get_post_type( $id );

		if ( ! $post_type ) {
			return null;
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object ) {
			return null;
		}

		return $post_type_object->labels->singular_name;

	}

	/**
	 * Order details template.
	 *
	 * @access public
	 *
	 * @param string|int $order_id WC order ID
	 * @param string     $template Template name.
	 *
	 * @return void
	 */
	public function order_details_template( $order_id, $template = 'order-details' ) {

		$details = $this->get_booking_order_details( $order_id );

		if ( ! $details ) {
			return;
		}

		include jet_abaf()->get_template( $template . '.php' );

	}

	/**
	 * Booking order details.
	 *
	 * Returns sanitized booking order details.
	 *
	 * @since  2.4.4
	 * @access public
	 *
	 * @param string|int $order_id WooCommerce order ID.
	 *
	 * @return mixed
	 */
	public function get_booking_order_details( $order_id ) {

		$booking = $this->get_booking_by_order_id( $order_id );

		if ( ! $booking ) {
			return false;
		}

		$details = apply_filters( 'jet-booking/wc-integration/pre-get-order-details', [], $order_id, $booking );

		if ( ! empty( $details ) ) {
			return $details;
		}

		$detail = [ [
			'key'     => __( 'Instance', 'jet-booking' ),
			'display' => get_the_title( $booking['apartment_id'] ),
		] ];

		$details[] = wp_parse_args( jet_abaf()->wc->get_formatted_info( $booking ), $detail );

		return apply_filters( 'jet-booking/wc-integration/order-details', $details, $order_id, $booking );

	}

	/**
	 * Set booking admin page WC configuration.
	 *
	 * @param array $config Configuration list.
	 *
	 * @return array
	 */
	public function set_booking_page_config( $config ) {

		$config['wc_integration'] = $this->get_status();

		return $config;

	}

	/**
	 * Validate booking update.
	 *
	 * Removes the action that updates a related order line item when a booking update occurs.
	 *
	 * @since 3.8.0
	 *
	 * @return void
	 */
	public function validate_booking_update() {
		remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_order_on_status_update' ] );
	}

	/**
	 * Resets the saved cart for a user after login.
	 *
	 * This function checks if the user's saved cart contains a booking product.
	 * If it does, the saved cart is deleted to prevent conflicts with new bookings.
	 *
	 * @param string   $user_login The user's login name.
	 * @param \WP_User $user       The user object.
	 *
	 * @return void
	 */
	public function reset_saved_cart_after_login( $user_login, $user ) {

		if ( ! $user ) {
			return;
		}

		$saved_cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

		if ( ! $saved_cart ) {
			return;
		}

		$reset_cart = false;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( $cart_item['data']->get_id() === intval( $this->get_product_id() ) ) {
				$reset_cart = true;
			}
		}

		if ( $reset_cart ) {
			delete_user_meta( $user->ID, '_woocommerce_persistent_cart_' . get_current_blog_id() );
		}

	}

	/**
	 * Get checkout fields.
	 *
	 * Returns checkout fields list.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_checkout_fields() {
		return apply_filters( 'jet-booking/wc-integration/checkout-fields', [
			'billing_first_name',
			'billing_last_name',
			'billing_email',
			'billing_phone',
			'billing_company',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_country',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'order_comments',
		] );
	}

	/**
	 * Process order.
	 *
	 * Processes the order to store booking vendor information from the cart items into the order metadata.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param int       $order_id WC order ID.
	 * @param array     $data     An array of order data associated with the current order.
	 * @param \WC_Order $order    WC order object instance.
	 *
	 * @return void
	 */
	public function process_order( $order_id, $data, $order ) {

		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			$booking_data = $cart_item[ jet_abaf()->wc->data_key ] ?? [];

			if ( empty( $booking_data ) || empty( $booking_data['booking_vendor'] ) ) {
				continue;
			}

			$order->update_meta_data( '__jet_booking_vendor', $booking_data['booking_vendor'] );
			break;
		}

		$order->save();

	}

	/**
	 * Process order by API.
	 *
	 * Handles order processing through API by delegating to the `process_order` method.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param \WC_Order $order WC order object instance.
	 *
	 * @return void
	 */
	public function process_order_by_api( $order ) {
		$this->process_order( $order->get_id(), [], $order );
	}

}