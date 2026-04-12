<?php
namespace JET_ABAF\WC_Integration;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use JET_ABAF\Cron\Manager as Cron_Manager;
use JET_ABAF\Settings;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Data key.
	 *
	 * Holds booking data key.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $data_key = 'booking_data';

	/**
	 * Form data key.
	 *
	 * Holds JetForms/JetEngine Forms data key.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $form_data_key = 'booking_form_data';

	/**
	 * Form ID key.
	 *
	 * Holds JetForms/JetEngine Forms ID key.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $form_id_key = 'booking_form_id';

	/**
	 * Mode.
	 *
	 * Current WooCommerce mode holder.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @var Modes\Based|Modes\Plain|null
	 */
	public $mode = null;

	/**
	 * Schedule.
	 *
	 * Schedule event holder.
	 *
	 * @since  3.8.0
	 * @access public
	 *
	 * @var array|false|mixed
	 */
	public $schedule;

	/**
	 * Product key.
	 *
	 * Booking product key holder.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $product_key = '_is_jet_booking';

	public function __construct() {

		if ( ! $this->has_woocommerce() ) {
			$this->reset_wc_related_settings();

			return;
		}

		$this->mode     = 'plain' === jet_abaf()->settings->get( 'booking_mode' ) ? new Modes\Plain() : new Modes\Based();
		$this->schedule = Cron_Manager::instance()->get_schedules( 'jet-booking-clear-on-expire' );

		// Set required plain mode settings.
		add_action( 'jet-abaf/settings/before-write', [ $this, 'maybe_set_required_mode_settings' ] );

		// Cart related.
		add_filter( 'woocommerce_get_item_data', [ $this, 'add_custom_item_meta' ], 10, 2 );
		// Handle removed and restored cart item.
		add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 20 );
		add_action( 'woocommerce_cart_item_restored', [ $this, 'cart_item_restored' ], 20, 2 );
		// Remove expired cart items.
		add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'remove_expired_cart_items' ] );

		// Order related.
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'process_order' ], 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'process_order_by_api' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'update_status_on_order_update' ], 10, 4 );

		// Format booking price in admin add/edit popups.
		add_filter( 'jet-booking/booking-total-price', function ( $price ) {
			return wc_price( floatval( $price ) );
		}, 20 );

		// Add Booking area to the My Account page.
		add_filter( 'woocommerce_get_query_vars', [ $this, 'add_query_vars' ], 0 );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'my_account_menu_item' ] );
		add_action( 'woocommerce_account_' . $this->get_endpoint() . '_endpoint', [ $this, 'endpoint_content' ] );

		// Notifications on booking cancellation.
		add_action( 'jet-booking/actions/cancel-booking/invalid-booking', function () {
			wc_add_notice( __( 'Invalid booking.', 'jet-booking' ), 'error' );
		} );
		add_action( 'jet-booking/actions/cancel-booking/cancelled', function () {
			wc_add_notice( __( 'Your booking was cancelled.', 'jet-booking' ), 'notice' );
		} );

	}

	/**
	 * Has WooCommerce.
	 *
	 * Check if WooCommerce plugin is enabled.
	 *
	 * @since  2.8.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function has_woocommerce() {
		return class_exists( '\WooCommerce' );
	}

	/**
	 * Maybe set required plain mode settings.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param Settings $settings Plugin settings object instance.
	 *
	 * @return void
	 */
	public function maybe_set_required_mode_settings( $settings ) {

		if ( ! $settings->wc_integration_enabled() ) {
			return;
		}

		$product_id = $this->get_product_id_from_db() ? $this->get_product_id_from_db() : $settings->get( 'wc_product_id' );
		$product    = get_post( $product_id );

		if ( ! $product || $product->post_status !== 'publish' ) {
			$product_id = $this->create_booking_product();
		}

		$settings->update( 'wc_product_id', $product_id, false );

	}

	/**
	 * Get endpoint.
	 *
	 * Return the my-account page booking endpoint.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return apply_filters( 'jet-booking/wc-integration/myaccount/endpoint', 'jet-bookings' );
	}

	/**
	 * Add new query var.
	 *
	 * @since 3.3.0
	 *
	 * @param array $vars List of query variables.
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = $this->get_endpoint();

		return $vars;
	}

	/**
	 * My account menu item.
	 *
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @since 3.3.0
	 *
	 * @param array $items List of my account menu items.
	 *
	 * @return array
	 */
	public function my_account_menu_item( $items ) {

		// Remove logout menu item.
		if ( array_key_exists( 'customer-logout', $items ) ) {
			$logout = $items['customer-logout'];
			unset( $items['customer-logout'] );
		}
		// Add bookings menu item.
		$items[ $this->get_endpoint() ] = __( 'Bookings', 'jet-booking' );

		// Add back the logout item.
		if ( isset( $logout ) ) {
			$items['customer-logout'] = $logout;
		}

		return $items;

	}

	/**
	 * Booking endpoint HTML content.
	 *
	 * @since 3.3.0
	 *
	 * @param int $current_page Current page number.
	 */
	public function endpoint_content( $current_page ) {

		$current_page      = empty( $current_page ) ? 1 : absint( $current_page );
		$bookings_per_page = apply_filters( 'jet-booking/wc-integration/myaccount/bookings-per-page', 10 );

		$query_args = [
			'user_id' => get_current_user_id(),
			'sorting' => [
				[
					'orderby' => 'booking_id',
					'order'   => 'DESC',
				]
			],
			'offset'  => ( $current_page - 1 ) * $bookings_per_page,
			'limit'   => $bookings_per_page + 1, // Increment to detect pagination.
		];

		$past_bookings_query = new \JET_ABAF\Resources\Booking_Query( wp_parse_args( [
			'date_query' => [
				[
					'column'   => 'check_in_date',
					'operator' => '<',
					'value'    => 'today'
				]
			]
		], $query_args ) );

		$today_bookings_query = new \JET_ABAF\Resources\Booking_Query( wp_parse_args( [
			'date_query' => [
				[
					'column'   => 'check_in_date',
					'operator' => '=',
					'value'    => 'today'
				]
			]
		], $query_args ) );

		$upcoming_bookings_query = new \JET_ABAF\Resources\Booking_Query( wp_parse_args( [
			'date_query' => [
				[
					'column'   => 'check_in_date',
					'operator' => '>=',
					'value'    => 'tomorrow'
				]
			]
		], $query_args ) );

		$past_bookings     = $past_bookings_query->get_bookings();
		$today_bookings    = $today_bookings_query->get_bookings();
		$upcoming_bookings = $upcoming_bookings_query->get_bookings();

		$tables = [];

		if ( ! empty( $past_bookings ) ) {
			$tables['past'] = [
				'heading'  => __( 'Past Bookings', 'jet-booking' ),
				'bookings' => $past_bookings,
			];
		}

		if ( ! empty( $today_bookings ) ) {
			$tables['today'] = [
				'heading'  => __( 'Today\'s Bookings', 'jet-booking' ),
				'bookings' => $today_bookings,
			];
		}

		if ( ! empty( $upcoming_bookings ) ) {
			$tables['upcoming'] = [
				'heading'  => __( 'Upcoming Bookings', 'jet-booking' ),
				'bookings' => $upcoming_bookings,
			];
		}

		include JET_ABAF_PATH . 'templates/woocommerce/myaccount/bookings.php';

	}

	/**
	 * Reset WC related settings.
	 *
	 * @since  3.0.0.
	 * @access public
	 *
	 * @return void
	 */
	public function reset_wc_related_settings() {

		if ( 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			jet_abaf()->settings->update( 'booking_mode', 'plain', false );
		}

		if ( jet_abaf()->settings->wc_integration_enabled() ) {
			jet_abaf()->settings->update( 'wc_integration', false, false );
		}

	}

	/**
	 * Add custom item meta.
	 *
	 * Adding custom booking data into cart meta data.
	 *
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @param array $item_data      Data for each item in the cart.
	 * @param array $cart_item_data List with stored custom values.
	 *
	 * @return mixed
	 */
	public function add_custom_item_meta( $item_data, $cart_item_data ) {

		if ( ! empty( $cart_item_data[ $this->data_key ] ) ) {
			$form_data = ! empty( $cart_item_data[ $this->form_data_key ] ) ? $cart_item_data[ $this->form_data_key ] : [];
			$form_id   = ! empty( $cart_item_data[ $this->form_id_key ] ) ? $cart_item_data[ $this->form_id_key ] : null;
			$item_data = array_merge( $item_data, $this->get_formatted_info( $cart_item_data[ $this->data_key ], $form_data, $form_id ) );
		}

		return $item_data;

	}

	/**
	 * Handles the removal of a cart item and performs related cleanup operations.
	 *
	 * @since 3.8.0
	 *
	 * @param string $cart_item_key The key of the cart item that was removed.
	 *
	 * @return void
	 */
	public function cart_item_removed( $cart_item_key ) {

		$cart_item = WC()->cart->removed_cart_contents[ $cart_item_key ];

		if ( ! empty( $cart_item[ $this->data_key ] ) ) {
			$booking_id = $cart_item[ $this->data_key ]['booking_id'];

			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking_id ] );
			$this->schedule->unschedule_single_event( [ $booking_id ] );
		}

	}

	/**
	 * Handle restoring a cart item.
	 *
	 * @since 3.8.0
	 *
	 * @param string   $cart_item_key The key of the cart item being restored.
	 * @param \WC_Cart $cart          The WooCommerce cart object.
	 *
	 * @return void
	 */
	public function cart_item_restored( $cart_item_key, $cart ) {

		$cart_items = $cart->get_cart();
		$cart_item  = $cart_items[ $cart_item_key ];

		if ( ! empty( $cart_item[ $this->data_key ] ) ) {
			$booking_id = jet_abaf()->db->insert_booking( $cart_item[ $this->data_key ] );

			if ( $booking_id ) {
				$this->schedule->schedule_single_event( [ $booking_id ] );

				do_action( 'jet-booking/wc-integration/booking-inserted', $booking_id );
			}
		}

	}

	/**
	 * Removes expired booking items from the WooCommerce cart.
	 *
	 * This method checks the items in the cart and removes expired booking products.
	 * It also displays a notice to the user about the removed items.
	 *
	 * @since 3.8.0
	 *
	 * @param \WC_Cart $cart The WooCommerce cart object.
	 *
	 * @return void
	 */
	public function remove_expired_cart_items( $cart ) {

		$titles   = [];
		$bookings = [];

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$booking_data = $cart_item[ $this->data_key ] ?? [];

			if ( empty( $booking_data ) ) {
				continue;
			}

			$booking = jet_abaf_get_booking( $booking_data['booking_id'] );

			if ( $booking ) {
				continue;
			}

			$bookings[] = $booking_data[ 'booking_id' ];

			unset( $cart->cart_contents[ $cart_item_key ] );

			/* translators: 1: booking instance permalink, 2: booking instance title */
			$title = sprintf( '<a href="%1$s">%2$s</a>', get_permalink( $booking_data['apartment_id'] ), get_the_title( $booking_data['apartment_id'] ) );

			if ( ! in_array( $title, $titles, true ) ) {
				$titles[] = $title;
			}
		}

		$titles_count = count( $titles );

		if ( $titles_count > 0 ) {
			$cart->calculate_totals();

			// Check if we've already shown this notice in this session
			$notice_key = 'jet_booking_expired_notice_' . md5( implode( ',', $bookings ) );

			if ( WC()->session->get( $notice_key ) ) {
				return;
			}

			// Mark this notice as shown
			WC()->session->set( $notice_key, true );

			$formatted_titles = wc_format_list_of_items( $titles );

			/* translators: Formated post title. */
			$notice = sprintf(
				_n(
					'A booking for %s has been removed from your cart due to inactivity.',
					'Bookings for %s have been removed from your cart due to inactivity.',
					$titles_count,
					'jet-booking'
				),
				$formatted_titles
			);

			wc_add_notice( $notice, 'notice' );
		}

	}

	/**
	 * Process order.
	 *
	 * Process new order creation.
	 *
	 * @param int    $order_id Process order ID.
	 * @param array  $data     Posted data from the checkout form.
	 * @param object $order    WC order object instance.
	 *
	 * @return void
	 */
	public function process_order( $order_id, $data, $order ) {
		foreach ( WC()->cart->get_cart_contents() as $item ) {
			if ( ! empty( $item[ $this->data_key ] ) ) {
				$this->set_order_data( $item[ $this->data_key ], $order_id, $order, $item );
			}
		}
	}

	/**
	 * Process order by API.
	 *
	 * Process new order creation with new checkout block API.
	 *
	 * @since  2.7.1
	 * @access public
	 *
	 * @param object $order WC order instance.
	 *
	 * @return void
	 */
	public function process_order_by_api( $order ) {
		$this->process_order( $order->get_id(), [], $order );
	}

	/**
	 * Update status on order update.
	 *
	 * Update an booking status on related WC order update.
	 *
	 * @since  3.0.0 Refactored.
	 * @since  3.8.0 Refactored.
	 * @access public
	 *
	 * @param int    $order_id   WC order ID.
	 * @param string $old_status Old status name.
	 * @param string $new_status New status name.
	 * @param object $order      WC order object instance.
	 *
	 * @return void
	 */
	public function update_status_on_order_update( $order_id, $old_status, $new_status, $order ) {

		$bookings = jet_abaf()->db->query( [ 'order_id' => $order_id ] );

		if ( empty( $bookings ) ) {
			return;
		}

		do_action( 'jet-booking/wc-integration/before-update-status', $order_id, $old_status, $new_status, $order );

		$statuses = jet_abaf()->statuses->get_statuses();

		foreach ( $bookings as $booking ) {
			if ( $booking['status'] === $new_status ) {
				continue;
			}

			jet_abaf()->db->update_booking( $booking['booking_id'], [
				'status' => isset( $statuses[ $new_status ] ) ? $new_status : 'created',
			] );
		}

	}

	/**
	 * Get formatted info.
	 *
	 * Get formatted booking information.
	 *
	 * @since  3.0.0 Refactored.
	 * @since  3.7.0 Added time info.
	 *
	 * @param array      $data      Booking data list.
	 * @param array      $form_data Submitted form data list.
	 * @param string|int $form_id   Submitted form id.
	 *
	 * @return array
	 */
	public function get_formatted_info( $data = [], $form_data = [], $form_id = null ) {

		$pre_cart_info = apply_filters( 'jet-booking/wc-integration/pre-cart-info', false, $data, $form_data, $form_id );

		if ( $pre_cart_info ) {
			return $pre_cart_info;
		}

		$from = ! empty( $data['check_in_date'] ) ? absint( $data['check_in_date'] ) : false;
		$to   = ! empty( $data['check_out_date'] ) ? absint( $data['check_out_date'] ) : false;

		if ( ! $from || ! $to ) {
			return [];
		}

		$check_in_display  = date_i18n( get_option( 'date_format' ), $from );
		$check_out_display = date_i18n( get_option( 'date_format' ), $to );

		if ( ! empty( $data['check_in_time'] ) ) {
			$check_in_time    = jet_abaf()->tools->is_valid_timestamp( $data['check_in_time'] ) ? date_i18n( get_option( 'time_format' ), absint( $data['check_in_time'] ) ) : $data['check_in_time'];
			$check_in_display .= ' - ' . $check_in_time;
		}

		if ( ! empty( $data['check_out_time'] ) ) {
			$check_out_time    = jet_abaf()->tools->is_valid_timestamp( $data['check_out_time'] ) ? date_i18n( get_option( 'time_format' ), absint( $data['check_out_time'] ) ) : $data['check_out_time'];
			$check_out_display .= ' - ' . $check_out_time;
		}

		$result[] = [
			'key'     => __( 'Check In', 'jet-booking' ),
			'display' => $check_in_display,
		];

		$result[] = [
			'key'     => __( 'Check Out', 'jet-booking' ),
			'display' => $check_out_display,
		];

		return apply_filters( 'jet-booking/wc-integration/cart-info', $result, $data, $form_data, $form_id );

	}

	/**
	 * Setup order data.
	 *
	 * @since  2.8.0 Added `wc_sync_orders` option handling.
	 * @access public
	 *
	 * @param array      $data      Process booking data list.
	 * @param string|int $order_id  Process order ID.
	 * @param object     $order     WC order object instance.
	 * @param array      $cart_item Processed cart item data list.
	 *
	 * @return void
	 */
	public function set_order_data( $data, $order_id, $order, $cart_item = [] ) {

		$booking_id = ! empty( $data['booking_id'] ) ? $data['booking_id'] : false;

		if ( ! $booking_id ) {
			return;
		}

		do_action( 'jet-booking/wc-integration/before-set-order-data', $data, $order_id, $order, $cart_item );

		$booking_statuses = jet_abaf()->statuses->get_statuses();

		jet_abaf()->db->update_booking( $booking_id, [
			'order_id' => $order_id,
			'status'   => array_key_exists( $order->get_status(), $booking_statuses ) ? $order->get_status() : 'created',
		] );

		do_action( 'jet-booking/wc-integration/process-order', $data, $order_id, $order, $cart_item );

	}

	/**
	 * Get product ID from DB.
	 *
	 * Try to get previously created product ID from database.
	 *
	 * @since  4.0.3.1 Fixed vulnerability to SQL injections.
	 *
	 * @access public
	 *
	 * @return false|int
	 */
	public function get_product_id_from_db() {

		global $wpdb;

		$product_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE `meta_key` = %s ORDER BY post_id DESC LIMIT 1",
				$this->product_key
			)
		);

		if ( ! $product_id ) {
			return false;
		}

		if ( 'product' !== get_post_type( $product_id ) ) {
			return false;
		}

		return absint( $product_id );

	}

	/**
	 * Create booking product.
	 *
	 * @access public
	 *
	 * @return int
	 */
	public function create_booking_product() {

		$product = new \WC_Product_Simple( 0 );

		$product->set_name( $this->get_product_name() );
		$product->set_status( 'publish' );
		$product->set_price( 1 );
		$product->set_regular_price( 1 );
		$product->set_slug( sanitize_title( $this->get_product_name() ) );
		$product->save();

		$product_id = $product->get_id();

		if ( $product_id ) {
			update_post_meta( $product_id, $this->product_key, true );
		}

		return $product_id;

	}

	/**
	 * Get the product name.
	 *
	 * Returns booking product name.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function get_product_name() {
		return apply_filters( 'jet-abaf/wc-integration/product-name', __( 'Booking', 'jet-booking' ) );
	}

	/**
	 * Get formatted price.
	 *
	 * Returns a formatted string representation for a numeric price value.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string|int $price Price value.
	 *
	 * @return string
	 */
	public function get_formatted_price( $price ) {
		return sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), number_format( floatval( $price ), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) );
	}

	/**
	 * Check if WooCommerce order can be edited.
	 *
	 * Determines whether WooCommerce order editing is allowed based on the availability of WooCommerce
	 * and specific conditions related to custom order table usage and data synchronization settings.
	 *
	 * @since   4.0.0
	 * @access  public
	 *
	 * @return bool True if WooCommerce order editing is allowed, false otherwise.
	 */
	public function can_edit_wc_order() {

		if ( jet_abaf()->vendors->is_booking_vendor( wp_get_current_user() ) && ( jet_abaf()->settings->wc_integration_enabled() || 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) ) {
			return ! OrderUtil::custom_orders_table_usage_is_enabled() || OrderUtil::custom_orders_table_usage_is_enabled() && wc_get_container()->get( DataSynchronizer::class )->data_sync_is_enabled();
		}

		return true;

	}

}