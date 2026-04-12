<?php
namespace JET_ABAF\WC_Integration;

use Automattic\WooCommerce\Utilities\OrderUtil;
use JET_ABAF\Price;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class WC_Order_Manager {

	/**
	 * Check if booking in deleting process.
	 *
	 * @var bool
	 */
	public $booking_deleting = false;

	public function __construct() {

		// Process order creation with validation.
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'process_order' ], 20, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'process_order_by_api' ], 20 );

		// Link order line item to the booking item with booking id.
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_custom_order_line_item_meta' ], 10, 4 );

		// Hide custom line item key from order edit page.
		add_filter( 'woocommerce_hidden_order_itemmeta', [ $this, 'hide_order_item_meta_data' ] );

		// Display booking data for related order.
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'display_order_booking_summary' ], 10, 4 );
		add_action( 'woocommerce_after_order_itemmeta', [ $this, 'display_admin_order_booking_summary' ], 10, 3 );

		// Synch vendor suborders status to parent order.
		add_action( 'woocommerce_order_status_changed', [ $this,'synch_parent_order_status' ], 20, 4 );

		// Cancel bookings when an order refunded partially.
		add_action( 'woocommerce_order_partially_refunded', [ $this, 'cancel_bookings_for_partial_refunds' ], 10, 2 );

		// Maybe recalculate booking order items.
		add_action( 'woocommerce_before_save_order_item', [ $this, 'recalculate_order_item' ] );

		// Handle order line item deletion.
		add_action( 'woocommerce_delete_order_item', [ $this, 'delete_related_booking_item' ] );

		// Handle order trash and delete.
		add_action( 'wp_trash_post', [ $this, 'trash_post' ] );
		add_action( 'before_delete_post', [ $this, 'delete_post' ] );

		// Handle HPOS order trash and delete.
		add_action( 'woocommerce_before_trash_order', [ $this, 'trash_post' ] );
		add_action( 'woocommerce_before_delete_order', [ $this, 'delete_post' ] );

		// Update line item meta data on order status update.
		add_action( 'jet-booking/wc-integration/process-order', [ $this, 'update_order_line_item_meta' ], 10, 4 );

		// Handle related order and order line items on linked booking item manipulations.
		add_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ], 10 );
		add_action( 'jet-booking/db/before-booking-delete', [ $this, 'maybe_remove_related_order_line_item' ], 10 );

		// Prevent circular bookings updates.
		add_action( 'jet-booking/wc-integration/before-update-status', [ $this, 'validate_booking_update' ] );
		add_action( 'jet-booking/wc-integration/before-set-order-data', [ $this, 'validate_booking_update' ] );

		// Set related order data for booking created via admin panel.
		add_action( 'jet-booking/rest-api/add-booking/set-related-order-data', [ $this, 'set_booking_related_order' ], 10, 3 );

		// Filter the "My Account" -> "Orders" list to show only parent orders.
		add_filter( 'woocommerce_my_account_my_orders_query', [ $this, 'woocommerce_my_account_my_orders_query' ] );

	}

	/**
	 * Process order.
	 *
	 * Process order with additional bookings expiration check.
	 *
	 * @since        3.0.0
	 * @since        4.0.0 Added support for vendor bookings.
	 * @access       public
	 *
	 * @param int       $order_id WC order ID.
	 * @param array     $data     Order data.
	 * @param \WC_Order $order    WC order object instance.
	 *
	 * @return void
	 * @throws \WC_Data_Exception
	 */
	public function process_order( $order_id, $data, $order ) {

		$vendor_items = [];

		foreach ( $order->get_items() as $item_id => $item ) {
			$author_id = get_post_field( 'post_author', $item->get_product_id() );
			$vendor_id = $author_id ?: 0;

			$vendor_items[ $vendor_id ][] = $item;

			if ( ! empty( $item->get_meta( '__jet_booking_id' ) ) ) {
				$booking = jet_abaf_get_booking( $item->get_meta( '__jet_booking_id' ) );

				if ( ! $booking ) {
					$order->remove_item( $item_id );
					$order->calculate_totals();
					$order->save();
				}
			}
		}

		if ( count( $vendor_items ) <= 1 ) {
			$vendor_id = key( $vendor_items );

			if ( $vendor_id && jet_abaf()->vendors->is_booking_vendor( $vendor_id ) ) {
				$order->update_meta_data( '__jet_booking_vendor', $vendor_id );
				$order->save();

				// Send email for a single order.
				$this->new_order_vendor_email( $order, $vendor_id );
			}

			return;
		}

		foreach ( $vendor_items as $vendor_id => $items ) {
			if ( ! $vendor_id || ! jet_abaf()->vendors->is_booking_vendor( $vendor_id ) ) {
				continue;
			}

			$sub_order = wc_create_order();

			$sub_order->set_parent_id( $order_id );
			$sub_order->set_customer_id( $order->get_customer_id() );
			$sub_order->set_status( $order->get_status() );
			$sub_order->set_address( $order->get_address() );
			$sub_order->set_address( $order->get_address( 'shipping' ), 'shipping' );

			foreach ( $items as $item ) {
				$line_item = $sub_order->add_product( $item->get_product(), $item->get_quantity(), [
					'subtotal'     => $item->get_subtotal(),
					'total'        => $item->get_total(),
					'subtotal_tax' => $item->get_subtotal_tax(),
					'total_tax'    => $item->get_total_tax(),
					'taxes'        => $item->get_taxes(),
				] );

				foreach ( $item->get_meta_data() as $meta_data ) {
					wc_add_order_item_meta( $line_item, $meta_data->key, $meta_data->value );

					if ( '__jet_booking_id' === $meta_data->key && $meta_data->value ) {
						remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ] );
						jet_abaf()->db->update_booking( $meta_data->value, [ 'order_id' => $sub_order->get_id() ] );
					}
				}
			}

			$sub_order->update_meta_data( '__jet_booking_vendor', $vendor_id );
			$sub_order->calculate_totals();
			$sub_order->save();

			// Send email to vendor for this suborder.
			$this->new_order_vendor_email( $sub_order, $vendor_id );
		}

		$order->save();

	}

	/**
	 * Process order by API.
	 *
	 * Process new order creation for new checkout block API with additional bookings expiration check.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param \WC_Order $order WC order object instance.
	 *
	 * @return void
	 * @throws \WC_Data_Exception
	 */
	public function process_order_by_api( $order ) {
		$this->process_order( $order->get_id(), [], $order );
	}

	/**
	 * Add custom order line item meta.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added attributes & guests handling.
	 *
	 * @param \WC_Order_Item_Product $item          WC order item instance.
	 * @param string                 $cart_item_key Arbitrary key of cart items.
	 * @param array                  $values        Values list for the cart item key.
	 * @param \WC_Order              $order         WC order instance.
	 *
	 * @return void
	 */
	public function add_custom_order_line_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values[ jet_abaf()->wc->data_key ] ) ) {
			$booking_data   = $values[ jet_abaf()->wc->data_key ];
			$line_item_meta = [
				'__jet_booking_id'                  => $booking_data['booking_id'] ?? 0,
				'__jet_booking_status'              => $booking_data['status'] ?? 'on-hold',
				'__jet_booking_check_in_date'       => $booking_data['check_in_date'] ?? '',
				'__jet_booking_check_out_date'      => $booking_data['check_out_date'] ?? '',
				'__jet_booking_additional_services' => $booking_data[ jet_abaf()->wc->mode->additional_services_key ] ?? [],
				'__jet_booking_guests'              => $booking_data['__guests'] ?? [],
			];

			foreach ( $line_item_meta as $key => $value ) {
				$item->add_meta_data( $key, $value, true );
			}

			/**
			 * Fires after booking data was written into order meta.
			 * Allows to add any additional booking-related order metadata.
			 */
			do_action( 'jet-booking/wc-integration/order/add-item-meta', $item, $booking_data, $order );
		}
	}

	/**
	 * Hide order item meta data.
	 *
	 * Hide custom line item key from order edit page.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added attributes & guests meta handling.
	 *
	 * @param array $keys List of keys to hide.
	 *
	 * @return array
	 */
	public function hide_order_item_meta_data( $keys ) {

		$custom_keys = [
			'__jet_booking_id',
			'__jet_booking_status',
			'__jet_booking_check_in_date',
			'__jet_booking_check_out_date',
			'__jet_booking_additional_services',
			'__jet_booking_guests',
		];

		return array_merge( $keys, $custom_keys );

	}

	/**
	 * Display order booking summary.
	 *
	 * Show booking data if a line item is linked to a booking ID.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int            $item_id WooCommerce order item ID.
	 * @param \WC_Order_Item $item    WooCommerce order item instance.
	 * @param \WC_Order      $order   WooCommerce order instance.
	 * @param string         $plain_text
	 *
	 * @return void
	 */
	public function display_order_booking_summary( $item_id, $item, $order, $plain_text ) {

		$booking_id = $item->get_meta( '__jet_booking_id' );

		if ( ! $booking_id ) {
			return;
		}

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		include JET_ABAF_PATH . 'templates/booking-summary.php';

	}

	/**
	 * Display admin order booking summary.
	 *
	 * Show booking data if a line item is linked to a booking ID.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int            $item_id WooCommerce order item ID.
	 * @param \WC_Order_Item $item    WooCommerce order item instance.
	 * @param \WC_Product    $product WooCommerce product instance.
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function display_admin_order_booking_summary( $item_id, $item, $product ) {

		$booking_id = $item->get_meta( '__jet_booking_id' );

		if ( ! $booking_id ) {
			return;
		}

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$statuses       = jet_abaf()->statuses->get_statuses();
		$status_classes = [ 'notice', 'notice-alt' ];

		if ( in_array( $booking->get_status(), jet_abaf()->statuses->finished_statuses() ) ) {
			$status_classes[] = 'notice-success';
		}

		if ( in_array( $booking->get_status(), jet_abaf()->statuses->in_progress_statuses() ) ) {
			$status_classes[] = 'notice-warning';
		}

		if ( in_array( $booking->get_status(), jet_abaf()->statuses->invalid_statuses() ) ) {
			$status_classes[] = 'notice-error';
		}

		include JET_ABAF_PATH . 'templates/admin/booking-summary.php';

	}

	/**
	 * Synchronize parent order status.
	 *
	 * Update the parent order status if all suborders share the same new status.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param int       $order_id   Order ID.
	 * @param string    $old_status Previous status of the order.
	 * @param string    $new_status New status of the order.
	 * @param \WC_Order $order      Order object.
	 *
	 * @return void
	 */
	public function synch_parent_order_status( $order_id, $old_status, $new_status, $order ) {

		if ( empty( $order->get_meta( '__jet_booking_vendor' ) ) ) {
			return;
		}

		$parent_order_id = $order->get_parent_id();

		if ( ! $parent_order_id ) {
			return;
		}

		$suborders = wc_get_orders( [
			'type'   => 'shop_order',
			'parent' => $parent_order_id,
			'limit'  => - 1,
		] );

		$all_statuses_same = true;

		foreach ( $suborders as $suborder ) {
			if ( $suborder->get_status() !== $new_status ) {
				$all_statuses_same = false;
				break;
			}
		}

		if ( $all_statuses_same ) {
			$parent_order = wc_get_order( $parent_order_id );
			$parent_order->update_status( $new_status, __( 'Sync from suborders:', 'woocommerce' ) );
		}

	}

	/**
	 * Display booking additional services.
	 *
	 * Show additional booking services in related order item.
	 *
	 * @since 3.6.0
	 *
	 * @param \JET_ABAF\Resources\Booking $booking  Booking instance.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function display_booking_additional_services( $booking ) {

		if ( empty( $booking->get_attributes() ) ) {
			return;
		}

		$interval   = jet_abaf()->tools->get_booking_period_interval( $booking->get_check_in_date(), $booking->get_check_out_date(), $booking->get_apartment_id() );
		$attributes = jet_abaf()->wc->mode->attributes->get_attributes_for_display( $booking->get_attributes(), $interval, $booking->get_apartment_id() );

		echo '<div class="jet-booking-summary__services">';

		foreach ( $attributes as $key => $attribute ) {
			if ( ! empty( $attribute['value'] ) ) {
				/* translators: 1: attribute key, 2: attribute label, 3: attribute terms list */
				printf( '<div class="jet-booking-summary__service jet-booking-summary__%1$s">%2$s: %3$s</div>', esc_attr( $key ), esc_html( $attribute['label'] ), esc_html( $attribute['value'] ) );
			}
		}

		echo '</div>';

	}

	/**
	 * Cancel booking for partial refunds.
	 *
	 * Cancel bookings when an order refunded partially.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 *
	 * @return void
	 */
	public function cancel_bookings_for_partial_refunds( $order_id, $refund_id ) {

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {
			$refunded_qty = $order->get_qty_refunded_for_item( $item_id );
			$booking_id   = $item->get_meta( '__jet_booking_id' );

			if ( $booking_id && 0 !== $refunded_qty ) {
				remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ], 10 );
				jet_abaf()->db->update_booking( $booking_id, [ 'status' => 'refunded' ] );
			}
		}

	}

	/**
	 * Recalculate order items.
	 *
	 * @since 3.6.0
	 *
	 * @param \WC_Order_Item $item WooCommerce order item instance.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function recalculate_order_item( $item ) {

		$booking_id = $item->get_meta( '__jet_booking_id' );

		if ( ! $booking_id ) {
			return;
		}

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$price           = new Price( $booking->get_apartment_id() );
		$booking_price   = $price->get_booking_price( $booking->get_object_vars() );
		$guests          = 0;
		$selected_guests = $item->get_meta( '__jet_booking_guests' );

		if ( $selected_guests ) {
			$has_guests = get_post_meta( $booking->get_apartment_id(), '_jet_booking_has_guests', true );

			if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) ) {
				$guests            = $selected_guests;
				$guests_multiplier = get_post_meta( $booking->get_apartment_id(), '_jet_booking_guests_multiplier', true );

				if ( filter_var( $guests_multiplier, FILTER_VALIDATE_BOOLEAN ) ) {
					$booking_price *= $selected_guests;
				}
			} else {
				$item->update_meta_data( '_jet_booking_has_guests', '' );
			}
		}

		$selected_attributes = $item->get_meta( '__jet_booking_additional_services' );

		if ( ! empty( $selected_attributes ) ) {
			$attributes       = jet_abaf()->wc->mode->attributes->get_attributes( $booking->get_apartment_id() );
			$booking_services = [];

			if ( ! empty( $attributes ) ) {
				foreach ( $selected_attributes as $name => $values ) {
					if ( isset( $attributes[ $name ] ) && ! empty( $attributes[ $name ]['terms'] ) ) {
						foreach ( $attributes[ $name ]['terms'] as $slug => $term ) {
							if ( in_array( $slug, $values ) ) {
								$booking_services[ $name ][] = $slug;
							}
						}
					}
				}

				$interval      = jet_abaf()->tools->get_booking_period_interval( $booking->get_check_in_date(), $booking->get_check_out_date(), $booking->get_apartment_id() );
				$booking_price += jet_abaf()->wc->mode->attributes->get_attributes_cost( $booking_services, $interval, $booking->get_apartment_id(), $guests );
			}

			$item->update_meta_data( '__jet_booking_additional_services', $booking_services );
		}

		if ( floatval( $item->get_total() ) !== floatval( $booking_price ) ) {
			$item->set_subtotal( $booking_price );
			$item->set_total( $booking_price );
		}

		$item->save();

	}

	/**
	 * Delete related booking item.
	 *
	 * Delete an booking item after deletion related order line item.
	 *
	 * @since  3.0.0
	 *
	 * @param int $item_id Item ID.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function delete_related_booking_item( $item_id ) {

		if ( $this->booking_deleting ) {
			return;
		}

		$booking_id = wc_get_order_item_meta( $item_id, '__jet_booking_id' );

		if ( $booking_id ) {
			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking_id ] );
		}

	}

	/**
	 * Trash post.
	 *
	 * Trash bookings with orders.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param mixed $post_id ID of post being trashed.
	 *
	 * @retur  void
	 */
	public function trash_post( $post_id ) {

		if ( ! $post_id || ! wc_get_order( $post_id ) ) {
			return;
		}

		$bookings = jet_abaf_get_bookings( [ 'order_id' => $post_id ] );

		if ( empty( $bookings ) ) {
			return;
		}

		remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ], 10 );

		foreach ( $bookings as $booking ) {
			jet_abaf()->db->update_booking( $booking->get_id(), [ 'status' => 'cancelled' ] );
		}

	}

	/**
	 * Delete post.
	 *
	 * Removes the bookings associated with the deleted WooCommerce order.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param mixed $post_id ID of post being deleted.
	 *
	 * @return void
	 */
	public function delete_post( $post_id ) {

		if ( ! current_user_can( 'delete_posts' ) || ! $post_id || ! wc_get_order( $post_id ) ) {
			return;
		}

		$bookings = jet_abaf_get_bookings( [ 'order_id' => $post_id ] );

		if ( empty( $bookings ) ) {
			return;
		}

		remove_action( 'jet-booking/db/before-booking-delete', [ $this, 'maybe_remove_related_order_line_item' ], 10 );

		foreach ( $bookings as $booking ) {
			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking->get_id() ] );
		}

	}

	/**
	 * Update order line item meta.
	 *
	 * Update line item meta data on order status update.
	 *
	 * @since  3.0.0
	 *
	 * @param array      $booking   Booking data list.
	 * @param string|int $order_id  Order ID.
	 * @param \WC_Order  $order     WooCommerce order instance.
	 * @param array      $cart_item Cart items list.
	 *
	 * @return void.
	 */
	public function update_order_line_item_meta( $booking, $order_id, $order, $cart_item ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! empty( $item->get_meta( '__jet_booking_status' ) ) && $item->get_meta( '__jet_booking_status' ) !== $order->get_status() ) {
				$item->update_meta_data( '__jet_booking_status', $order->get_status() );
				$item->save();
			}
		}
	}

	/**
	 * Update related order line item.
	 *
	 * Update related order booking line item data on booking item update.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string|int $booking_id Booking item ID.
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function update_related_order_line_item( $booking_id ) {

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$order_id = $booking->get_order_id();

		if ( ! $order_id ) {
			return;
		}

		$order =  wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( ! empty( $booking->get_status() ) ) {
			if ( 'refunded' === $booking->get_status() ) {
				$line_items    = [];
				$refund_amount = 0;
				$refund_reason = '';

				foreach ( $order->get_items() as $item_id => $item ) {
					if ( absint( $booking_id ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
						if ( ! $order->get_qty_refunded_for_item( $item_id ) ) {
							$item_total    = wc_get_order_item_meta( $item_id, '_line_total' );
							$refund_amount = wc_format_decimal( $refund_amount ) + wc_format_decimal( $item_total );
							/* translators: 1: booking ID, 2: new booking status */
							$refund_reason = sprintf( __( 'Booking #%1$s status changed to %2$s.', 'jet-booking' ), $booking_id, ucfirst( $booking->get_status() ) );

							$line_items[ $item_id ] = [
								'qty'          => wc_get_order_item_meta( $item_id, '_qty' ),
								'refund_total' => wc_format_decimal( $item_total ),
							];

							$item->update_meta_data( '__jet_booking_status', $booking->get_status() );
							$item->save();
						} else {
							return;
						}
					}
				}

				if ( ! empty( $line_items ) ) {
					wc_create_refund( [
						'amount'     => $refund_amount,
						'reason'     => $refund_reason,
						'order_id'   => $order->get_id(),
						'line_items' => $line_items,
					] );

					/* translators: booking ID */
					$order->add_order_note( sprintf( __( 'Refunded Booking #%s.', 'jet-booking' ), $booking_id ), false, true );
				}
			}

			$booking_status = $booking->get_status();
			$status_keys    = jet_abaf()->statuses->get_statuses_ids();

			if ( array_search( $booking_status, $status_keys ) > array_search( $order->get_status(), $status_keys ) ) {
				foreach ( $order->get_items() as $item_id => $item ) {
					if ( absint( $booking_id ) !== absint( $item->get_meta( '__jet_booking_id' ) ) ) {
						if ( array_search( $booking_status, $status_keys ) > array_search( $item->get_meta( '__jet_booking_status' ), $status_keys ) && ! empty( $item->get_meta( '__jet_booking_status' ) ) ) {
							$booking_status = $item->get_meta( '__jet_booking_status' );
						}
					}
				}
			}

			if ( $booking_status !== $order->get_status() ) {
				remove_action( 'woocommerce_order_status_changed', [ jet_abaf()->wc, 'update_status_on_order_update' ], 10 );
				$order->update_status( $booking_status, '', true );
			}
		}

		$calculate_totals = wp_cache_get( 'calculate_booking_totals_' . $booking_id );
		$note             = '';

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( absint( $booking_id ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
				if ( ! empty( $booking->get_status() ) && $booking->get_status() !== $item->get_meta( '__jet_booking_status' ) ) {
					/* translators: 1: previous status, 2: new status */
					$note .= sprintf( __( '<br> - Status changed from %1$s to %2$s.', 'jet-booking' ), wc_get_order_status_name( $item->get_meta( '__jet_booking_status' ) ), wc_get_order_status_name( $booking->get_status() ) );
					$item->update_meta_data( '__jet_booking_status', $booking->get_status() );
				}

				if ( ! empty( $booking->get_apartment_id() ) && absint( $booking->get_apartment_id() ) !== $item->get_product_id() ) {
					/* translators: 1: previous apartment, 2: new apartment */
					$note .= sprintf( __( '<br> - Item changed from %1$s to %2$s.', 'jet-booking' ), get_the_title( $item->get_product_id() ), get_the_title( $booking->get_apartment_id() ) );

					$item->set_product_id( $booking->get_apartment_id() );
					$item->set_name( get_the_title( $booking->get_apartment_id() ) );
				}

				if ( ! empty( $booking->get_check_in_date() ) && $booking->get_check_in_date() !== absint( $item->get_meta( '__jet_booking_check_in_date' ) ) ) {
					/* translators: 1: previous check-in date, 2: new check-in date */
					$note .= sprintf( __( '<br> - Check in date changed from %1$s to %2$s.', 'jet-booking' ), date_i18n( get_option( 'date_format' ), $item->get_meta( '__jet_booking_check_in_date' ) ), date_i18n( get_option( 'date_format' ), $booking->get_check_in_date() ) );
					$item->update_meta_data( '__jet_booking_check_in_date', $booking->get_check_in_date() );
				}

				if ( ! empty( $booking->get_check_out_date() ) && $booking->get_check_out_date() !== absint( $item->get_meta( '__jet_booking_check_out_date' ) ) ) {
					/* translators: 1: previous check-out date, 2: new check-out date */
					$note .= sprintf( __( '<br> - Check out date changed from %1$s to %2$s.', 'jet-booking' ), date_i18n( get_option( 'date_format' ), $item->get_meta( '__jet_booking_check_out_date' ) ), date_i18n( get_option( 'date_format' ), $booking->get_check_out_date() ) );
					$item->update_meta_data( '__jet_booking_check_out_date', $booking->get_check_out_date() );
				}

				$guests = wp_cache_get( 'booking_guests_' . $booking_id );

				if ( $guests && ! $item->get_meta( '__jet_booking_guests' ) ) {
					/* translators: 1: guests number */
					$note .= sprintf( __( '<br> - %1$s guests added.', 'jet-booking' ), $guests  );
					$item->update_meta_data( '__jet_booking_guests', $guests );
				} elseif ( ! $guests && $item->get_meta( '__jet_booking_guests' ) ) {
					/* translators: 1: guests number */
					$note .= sprintf( __( '<br> - Guests number removed.', 'jet-booking' ), $guests  );
					$item->update_meta_data( '__jet_booking_guests', $guests );
				} elseif ( absint( $guests ) !== absint( $item->get_meta( '__jet_booking_guests' ) ) ) {
					/* translators: 1: previous guests number, 2: new guests number */
					$note .= sprintf( __( '<br> - Guests number changed from %1$s to %2$s.', 'jet-booking' ), $item->get_meta( '__jet_booking_guests' ), $guests );
					$item->update_meta_data( '__jet_booking_guests', $guests );
				}

				if ( ! empty( jet_abaf()->wc->mode->attributes->get_attributes() ) ) {
					$attributes          = jet_abaf()->wc->mode->attributes->get_attributes( $booking->get_apartment_id() );
					$selected_attributes = wp_cache_get( 'booking_attributes_' . $booking_id );
					$booking_services    = [];

					if ( ! empty( $selected_attributes ) ) {
						foreach ( $selected_attributes as $name => $values ) {
							if ( isset( $attributes[ $name ] ) && ! empty( $attributes[ $name ]['terms'] ) ) {
								foreach ( $attributes[ $name ]['terms'] as $slug => $term ) {
									if ( in_array( $slug, $values ) ) {
										$booking_services[ $name ][] = $slug;
									}
								}
							}
						}
					}

					$item_services    = $item->get_meta( '__jet_booking_additional_services' );
					$services_updated = false;

					foreach ( $attributes as $name => $attribute ) {
						if ( isset( $item_services[ $name ] ) && isset( $booking_services[ $name ] ) ) {
							$selected_services = array_merge( $item_services[ $name ], $booking_services[ $name ] );

							if ( ! empty( array_diff( $selected_services, $item_services[ $name ] ) ) || ! empty( array_diff( $selected_services, $booking_services[ $name ] ) ) ) {
								$services_updated = true;
							}
						} elseif ( ! isset( $item_services[ $name ] ) && isset( $booking_services[ $name ] ) || isset( $item_services[ $name ] ) && ! isset( $booking_services[ $name ] ) ) {
							$services_updated = true;
						}
					}

					if ( $services_updated ) {
						if ( empty( $booking_services ) ) {
							$note .= __( '<br> - Additional services removed.', 'jet-booking' );
						} elseif ( empty( $item_services ) ) {
							$note .= __( '<br> - Additional services added.', 'jet-booking' );
						} else {
							$note .= __( '<br> - Additional services changed.', 'jet-booking' );
						}

						$item->update_meta_data( '__jet_booking_additional_services', $booking_services );
					}
				}

				if ( $calculate_totals ) {
					$price         = new Price( $booking->get_apartment_id() );
					$booking_price = $price->get_booking_price( $booking->get_object_vars() );
					$item_guests   = $item->get_meta( '__jet_booking_guests' );

					if ( $item_guests ) {
						$guests_multiplier = get_post_meta( $booking->get_apartment_id(), '_jet_booking_guests_multiplier', true );

						if ( filter_var( $guests_multiplier, FILTER_VALIDATE_BOOLEAN ) ) {
							$booking_price *= $item_guests;
						}
					}

					$item_services = $item->get_meta( '__jet_booking_additional_services' );

					if ( ! empty( $item_services ) ) {
						$interval      = jet_abaf()->tools->get_booking_period_interval( $booking->get_check_in_date(), $booking->get_check_out_date(), $booking->get_apartment_id() );
						$booking_price += jet_abaf()->wc->mode->attributes->get_attributes_cost( $item_services, $interval, $booking->get_apartment_id(), $item_guests );
					}

					$item->set_subtotal( $booking_price );
					$item->set_total( $booking_price );
				}

				$item->save();
			}
		}

		if ( $calculate_totals ) {
			$old_total = $order->get_total();

			$order->calculate_totals();
			$order->save();

			if ( $old_total > $order->get_total() ) {
				/* translators: decreased order price */
				$note .= sprintf( __( '<br> - Total price of the order decreased by %s.', 'jet-booking' ), jet_abaf()->wc->get_formatted_price( $old_total - $order->get_total() ) );
			} elseif ( $old_total < $order->get_total() ) {
				/* translators: increased order price */
				$note .= sprintf( __( '<br> - Total price of the order increased by %s.', 'jet-booking' ), jet_abaf()->wc->get_formatted_price( $order->get_total() - $old_total ) );
			}

			wp_cache_delete( 'calculate_booking_totals_' . $booking_id );
		}

		if ( ! empty( $note ) ) {
			/* translators: 1: booking ID, 2: update description */
			$order->add_order_note( sprintf( __( 'Updated Booking #%1$s. %2$s', 'jet-booking' ), $booking_id, $note ), false, true );
		}

	}

	/**
	 * Maybe remove related order line item.
	 *
	 * Remove Woocommerce relater order line item for when deleting linked booking item.
	 *
	 * @since  3.0.0
	 *
	 * @param array $args Deletion arguments array.
	 *
	 * @return void
	 */
	public function maybe_remove_related_order_line_item( $args ) {

		if ( ! $this->booking_deleting ) {
			$this->booking_deleting = true;
		}

		if ( empty( $args['booking_id'] ) ) {
			return;
		}

		$order = jet_abaf()->wc->mode->get_booking_related_order( $args['booking_id'] );

		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( absint( $args['booking_id'] ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
				$order->remove_item( $item_id );
			}
		}

		$order->add_order_note( sprintf( __( 'Deleted Booking #%s.', 'woocommerce' ), $args['booking_id'] ), false, true );
		$order->calculate_totals();
		$order->save();

		if ( ! $order->get_item_count() ) {
			$order->delete( true );
		}

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
		remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ] );
	}

	/**
	 * Set booking related order.
	 *
	 * Set WooCommerce related order for booking instance item.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added `$booking` parameter. Attributes & guests handling.
	 * @since  3.7.1 Added tax handling.
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

		$price          = new Price( $booking['apartment_id'] );
		$booking_price  = $price->get_booking_price( $booking );
		$line_item_meta = [];
		$guests         = 0;

		if ( ! empty( $booking['__guests'] ) ) {
			$guests            = $line_item_meta['__jet_booking_guests'] = $booking['__guests'];
			$guests_multiplier = get_post_meta( $booking['apartment_id'], '_jet_booking_guests_multiplier', true );

			if ( filter_var( $guests_multiplier, FILTER_VALIDATE_BOOLEAN ) ) {
				$booking_price *= $booking['__guests'];
			}
		}

		if ( ! empty( $booking['attributes'] ) ) {
			foreach ( $booking['attributes'] as $key => $value ) {
				if ( empty( $value ) ) {
					unset( $booking['attributes'][ $key ] );
				}
			}

			if ( ! empty( $booking['attributes'] ) ) {
				$line_item_meta['__jet_booking_additional_services'] = $booking['attributes'];

				$interval      = jet_abaf()->tools->get_booking_period_interval( $booking['check_in_date'], $booking['check_out_date'], $booking['apartment_id'] );
				$booking_price += jet_abaf()->wc->mode->attributes->get_attributes_cost( $booking['attributes'], $interval, $booking['apartment_id'], $guests );
			}
		}

		$product = wc_get_product( $booking['apartment_id'] );

		if ( wc_prices_include_tax() ) {
			$booking_price = wc_get_price_excluding_tax( $product, [ 'price' => $booking_price ] );
		}

		$order_id = ! empty( $order_data['orderID'] ) ? absint( $order_data['orderID'] ) : 0;

		if ( $order_id && ! OrderUtil::is_order( $order_id ) ) {
			return;
		}

		$order = new \WC_Order( $order_id );

		$order_item_id = $order->add_product( $product, 1, [
			'subtotal' => $booking_price,
			'total'    => $booking_price,
		] );

		if ( ! $order_id ) {
			$order->set_status( $booking['status'] );
		}

		$booking_object = jet_abaf_get_booking( $booking_id );
		$booking_vendor = $booking_object->get_booking_vendor();

		if ( $booking_vendor ) {
			$order->update_meta_data( '__jet_booking_vendor', $booking_vendor );
		}

		$order->calculate_totals();
		$order->save();

		$order_item     = $order->get_item( $order_item_id );
		$line_item_meta = array_merge( $line_item_meta, [
			'__jet_booking_id'             => $booking_id,
			'__jet_booking_status'         => $order->get_status(),
			'__jet_booking_check_in_date'  => isset( $booking['check_in_date'] ) ? $booking['check_in_date'] + 1 : '',
			'__jet_booking_check_out_date' => $booking['check_out_date'] ?? '',
		] );

		foreach ( $line_item_meta as $key => $value ) {
			$order_item->add_meta_data( $key, $value, true );
		}

		$order_item->save();

		remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ] );

		jet_abaf()->db->update_booking( $booking_id, [ 'order_id' => $order->get_id() ] );

	}

	/**
	 * Modify WooCommerce My Account Orders Query.
	 *
	 * Adjusts the query arguments for displaying orders on the My Account page.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array $args Query arguments for retrieving orders.
	 *
	 * @return array
	 */
	public function woocommerce_my_account_my_orders_query( $args ) {
		$args['parent'] = 0;

		return $args;
	}

	/**
	 * New order vendor email.
	 *
	 * This method triggers the new order email notification to the vendor
	 * associated with the given order.
	 *
	 * @param \WC_Order $order     The WooCommerce order object.
	 * @param int       $vendor_id The vendor's user ID.
	 *
	 * @return void
	 */
	public function new_order_vendor_email( $order, $vendor_id ) {

		$mailer = WC()->mailer()->get_emails()['WC_Email_New_Order'] ?? null;

		if ( ! $mailer ) {
			return;
		}

		$vendor_email = get_the_author_meta( 'user_email', $vendor_id );

		// Define a temporary filter to swap the recipient from admin to vendor.
		$recipient_filter = function ( $recipient, $object ) use ( $order, $vendor_email ) {
			// Change only if the order ID matches our specific suborder.
			if ( $object && is_a( $object, 'WC_Order' ) && $object->get_id() === $order->get_id() ) {
				return $vendor_email;
			}

			return $recipient;
		};

		// Add the filter.
		add_filter( 'woocommerce_email_recipient_new_order', $recipient_filter, 10, 2 );

		// Trigger the email for this suborder.
		$mailer->trigger( $order->get_id(), $order );

		// Remove the filter immediately so normal admin emails aren't affected.
		remove_filter( 'woocommerce_email_recipient_new_order', $recipient_filter );

	}

}
