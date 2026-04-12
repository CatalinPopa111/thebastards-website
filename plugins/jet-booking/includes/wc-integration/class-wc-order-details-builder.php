<?php

namespace JET_ABAF\WC_Integration;

use Automattic\WooCommerce\Utilities\OrderUtil;

class WC_Order_Details_Builder {

	/**
	 * Meta key.
	 *
	 * WooCommerce details meta key holder.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $meta_key = '_jet_abaf_wc_details';

	public function __construct() {
		add_action( 'jet-engine/forms/editor/assets', [ $this, 'enqueue_builder' ] );
		add_action( 'wp_ajax_jet_booking_save_wc_details', [ $this, 'save_wc_details' ] );
		add_filter( 'jet-booking/wc-integration/pre-cart-info', [ $this, 'set_cart_details' ], 10, 4 );
		add_filter( 'jet-booking/wc-integration/pre-get-order-details', [ $this, 'set_order_details' ], 10, 3 );
		add_action( 'jet-booking/wc-integration/process-order', [ $this, 'set_order_meta' ], 10, 4 );
	}

	/**
	 * Set cart details.
	 *
	 * Set cart order details.
	 *
	 * @access public
	 *
	 * @param boolean    $result    Pre cart info details.
	 * @param array      $data      Booking data list.
	 * @param array      $form_data Submitted form data list.
	 * @param string|int $form_id   Submitted form id.
	 *
	 * @return array|false|mixed
	 */
	public function set_cart_details( $result, $data, $form_data, $form_id ) {

		if ( ! $form_id ) {
			return $result;
		}

		return $this->get_order_details( $form_id, $data, $form_data );

	}

	/**
	 * Set order details.
	 *
	 * Set order details information.
	 *
	 * @since  2.6.3 Added integration with WooCommerce High-Performance Order Storage.
	 * @since  3.8.0 Added multiple booking support.
	 * @access public
	 *
	 * @param array         $details  Details list.
	 * @param string|number $order_id Order ID.
	 * @param array         $booking  Booking info list.
	 *
	 * @return array
	 */
	public function set_order_details( $details, $order_id, $booking ) {

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order = wc_get_order( $order_id );
			$meta  = $order->get_meta( $this->meta_key );
		} else {
			$meta = get_post_meta( $order_id, $this->meta_key, true );
		}

		if ( empty( $meta ) ) {
			return $details;
		}

		$form_id   = ! empty( $meta['form_id'] ) ? $meta['form_id'] : false;
		$form_data = ! empty( $meta['form_data'] ) ? $meta['form_data'] : [];

		if ( ! $form_id ) {
			return $details;
		}

		$bookings = jet_abaf()->db->query( [ 'order_id' => $order_id ] );

		if ( empty( $bookings ) ) {
			return $details;
		}

		$details = [];

		foreach ( $bookings as $item ) {
			$details[] = $this->get_order_details( $form_id, $item, $form_data );
		}

		return $details;

	}

	/**
	 * Get order details.
	 *
	 * Get order details for passed form, booking and form data.
	 *
	 * @since  2.6.3 Handle field item empty value.
	 * @since  2.8.0 Refactored. Added fallback label values.
	 * @since  3.7.0 Refactored dates info. Added display of formated time data.
	 * @access public
	 *
	 * @param string|number $form_id   Booking form ID.
	 * @param array         $booking   Booking data list.
	 * @param array         $form_data Form data list.
	 *
	 * @return mixed
	 */
	public function get_order_details( $form_id = null, $booking = [], $form_data = [] ) {

		$details = $this->get_details_schema( $form_id );

		if ( ! $details ) {
			return false;
		}

		$result = [];

		foreach ( $details as $item ) {
			switch ( $item['type'] ) {
				case 'booked-inst':
					$result[] = [
						'key'     => ! empty( $item['label'] ) ? $item['label'] : __( 'Booking instance', 'jet-booking' ),
						'display' => get_the_title( $booking['apartment_id'] ),
					];

					break;

				case 'check-in':
				case 'check-out':
					$format = ! empty( $item['format'] ) ? $item['format'] : get_option( 'date_format' );

					if ( 'check-in' === $item['type'] ) {
						$label = __( 'Check in', 'jet-booking' );
						$value = date_i18n( $format, absint( $booking['check_in_date'] ) );

						if ( ! empty( $booking['check_in_time'] ) ) {
							$check_in_time = jet_abaf()->tools->is_valid_timestamp( $booking['check_in_time'] ) ? date_i18n( get_option( 'time_format' ), absint( $booking['check_in_time'] ) ) : $booking['check_in_time'];
							$value         .= ' - ' . $check_in_time;
						}
					} else {
						$label = __( 'Check out', 'jet-booking' );
						$value = date_i18n( $format, absint( $booking['check_out_date'] ) );

						if ( ! empty( $booking['check_out_time'] ) ) {
							$check_out_time = jet_abaf()->tools->is_valid_timestamp( $booking['check_out_time'] ) ? date_i18n( get_option( 'time_format' ), absint( $booking['check_out_time'] ) ) : $booking['check_out_time'];
							$value          .= ' - ' . $check_out_time;
						}
					}

					$result[] = [
						'key'     => ! empty( $item['label'] ) ? $item['label'] : $label,
						'display' => $value,
					];

					break;

				case 'unit':
					$unit  = ! empty( $booking['apartment_unit'] ) ? absint( $booking['apartment_unit'] ) : false;
					$value = '';

					if ( $unit ) {
						$unit_data = jet_abaf()->db->get_apartment_unit( $booking['apartment_id'], $unit );

						if ( ! empty( $unit_data ) ) {
							$unit_data = $unit_data[0];
						}

						if ( ! empty( $unit_data['unit_title'] ) ) {
							$value = $unit_data['unit_title'];
						}
					}

					$result[] = [
						'key'     => ! empty( $item['label'] ) ? $item['label'] : __( 'Unit', 'jet-booking' ),
						'display' => $value,
						'hidden'  => empty( $value ),
					];

					break;

				case 'field':
					$field = $item['field'] ?? false;

					if ( $field ) {
						$value = $booking[ $field ] ?? $form_data[ $field ] ?? '';

						if ( is_array( $value ) ) {
							$value = implode( ', ', $value );
						}

						$result[] = [
							'key'     => ! empty( $item['label'] ) ? $item['label'] : __( 'Form field', 'jet-booking' ),
							'display' => ! empty( $value ) ? $value : '&nbsp;',
						];
					}

					break;

				case 'add_to_calendar':
					$url = jet_abaf()->google_cal->get_internal_link( $booking['booking_id'] );

					if ( $url ) {
						$link_text = ! empty( $item['link_label'] ) ? $item['link_label'] : __( 'Add', 'jet-booking' );
						$result[]  = [
							'key'           => ! empty( $item['label'] ) ? $item['label'] : __( 'Add to calendar', 'jet-booking' ),
							'is_html'       => true,
							'display'       => sprintf( '<strong><a href="%s" target="_blank">%s</a></strong>', esc_url( $url ), esc_html( $link_text ) ),
							'display_plain' => $url,
						];
					}

					break;
			}
		}

		return $result;

	}

	/**
	 * Set order meta.
	 *
	 * Store form ID and details into order meta.
	 *
	 * @since  2.6.3 Added integration with WooCommerce High-Performance Order Storage.
	 * @since  3.8.0 Refactored.
	 *
	 * @param array      $booking   Booking data list.
	 * @param string|int $order_id  Order ID.
	 * @param \WC_Order  $order     WooCommerce order instance.
	 * @param array      $cart_item Cart item info list.
	 *
	 * @return void
	 */
	public function set_order_meta( $booking, $order_id, $order, $cart_item ) {

		if ( empty( $cart_item[ jet_abaf()->wc->form_id_key ] ) || empty( $cart_item[ jet_abaf()->wc->form_data_key ] ) ) {
			return;
		}

		$meta = [
			'form_id'   => $cart_item[ jet_abaf()->wc->form_id_key ],
			'form_data' => $cart_item[ jet_abaf()->wc->form_data_key ],
		];

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order->update_meta_data( $this->meta_key, $meta );
			$order->save();
		} else {
			update_post_meta( $order_id, $this->meta_key, $meta );
		}

	}

	/**
	 * Save WC details.
	 *
	 * Save WooCommerce details settings.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Updated nonce check.
	 * @access public
	 *
	 * @return void
	 */
	public function save_wc_details() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], $this->meta_key ) ) { // phpcs:ignore
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'jet-booking' ) ] );
		}

		$post_id = ! empty( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : false;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'You don`t have access to this post.', 'jet-booking' ) ] );
		}

		$details = ! empty( $_REQUEST['details'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_REQUEST['details'] ) ) : []; // phpcs:ignore

		update_post_meta( $post_id, $this->meta_key, $details );

		wp_send_json_success();

	}

	/**
	 * Get details schema.
	 *
	 * Returns details config for current form.
	 *
	 * @access public
	 *
	 * @param null $post_id Booking instance ID.
	 *
	 * @return array[]|false|mixed
	 */
	public function get_details_schema( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;
			$post_id = $post ? $post->ID : false;
		}

		if ( ! $post_id ) {
			return false;
		}

		$details = get_post_meta( $post_id, $this->meta_key );

		if ( ! $details ) {
			$details = [
				[
					'type'   => 'check-in',
					'label'  => __( 'Check In', 'jet-booking' ),
					'format' => get_option( 'date_format' ),
				],
				[
					'type'   => 'check-out',
					'label'  => __( 'Check Out', 'jet-booking' ),
					'format' => get_option( 'date_format' ),
				],
			];
		} else {
			$details = $details[0];
		}

		return $details;

	}

	/**
	 * Enqueue builder.
	 *
	 * Enqueue order details builder.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function enqueue_builder() {

		wp_enqueue_style(
			'jet-abaf-meta',
			JET_ABAF_URL . 'assets/css/admin/jet-abaf-admin-style.css',
			[],
			JET_ABAF_VERSION
		);

		wp_enqueue_script(
			'jet-abaf-wc-details-builder',
			JET_ABAF_URL . 'assets/js/admin/wc-details-builder.js',
			[ 'jet-engine-forms' ],
			JET_ABAF_VERSION,
			true
		);

		global $post;

		wp_localize_script( 'jet-abaf-wc-details-builder', 'JetABAFWCDetails', [
			'apartment'       => $post->ID,
			'details'         => $this->get_details_schema( $post->ID ),
			'confirm_message' => __( 'Are you sure?', 'jet-booking' ),
			'nonce'           => wp_create_nonce( $this->meta_key ),
		] );

		add_action( 'admin_footer', [ $this, 'builder_template' ] );

	}

	/**
	 * Builder template.
	 *
	 * Include builder component template.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function builder_template() {
		ob_start();
		include JET_ABAF_PATH . 'templates/admin/common/wc-details-builder.php';
		printf( '<div id="jet_abaf_wc_details_builder_popup"></div><script type="text/x-template" id="jet-abaf-wc-details-builder">%s</script>', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}
