<?php

namespace JET_ABAF\WC_Integration\Modes;

use JET_ABAF\WC_Integration\WC_Attributes_Manager;
use JET_ABAF\WC_Integration\WC_Cart_Manager;
use JET_ABAF\WC_Integration\WC_Order_Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Based {

	/**
	 * Product type.
	 *
	 * Hold the name of custom product type.
	 *
	 * @since  3.0.0
	 *
	 * @var string
	 */
	private $product_type = 'jet_booking';

	/**
	 * Additional services key.
	 *
	 * Holds WooCommerce based booking additional services key.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $additional_services_key = 'booking_additional_services';

	/**
	 * Mode.
	 *
	 * WooCommerce mode attributes manager instance holder.
	 *
	 * @since  3.6.0
	 *
	 * @var WC_Attributes_Manager|null
	 */
	public $attributes = null;

	public function __construct() {

		// Returns apartment post type as `product`.
		add_filter( 'jet-booking/settings/get/apartment_post_type', function () {
			return ['product'];
		} );

		// Register booking product type.
		add_action( 'init', [ $this, 'register_custom_product_type' ] );
		add_filter( 'woocommerce_product_class', [ $this, 'woocommerce_custom_product_class' ], 10, 2 );

		// Display custom product type in Product data selector dropdown.
		add_filter( 'product_type_selector', [ $this, 'product_type_selector' ] );

		// Initialize product creation as JetBooking product type.
		add_filter( 'woocommerce_product_type_query', [ $this, 'maybe_override_product_type' ], 10, 2 );

		// Initialize and handle product edit tabs.
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'booking_data_panel' ] );
		add_filter( 'product_type_options', [ $this, 'product_type_options' ] );

		// Display booking meta boxes only for booking product type.
		add_filter( 'postbox_classes_product_jet-abaf-units', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet_abaf_configuration', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet_abaf_custom_schedule', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet_abaf_price', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet-abaf-upcoming-booking-meta', [ $this, 'meta_box_classes' ] );

		// Save product custom meta data.
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'save_custom_meta_data' ], 20 );

		// Enqueue booking product related assets.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Display add to cart button for single and loop appearances.
		add_action( 'woocommerce_jet_booking_add_to_cart', [ $this, 'display_add_to_cart_button' ] );

		// Display date picker form.
		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'before_add_to_cart_button' ] );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'after_add_to_cart_button' ] );

		// Price calculation and display after date selection.
		add_action( 'wp_ajax_jet_booking_product_set_total_price', [ $this, 'set_total_price' ] );
		add_action( 'wp_ajax_nopriv_jet_booking_product_set_total_price', [ $this, 'set_total_price' ] );

		// Retrieve booked dates for a specific unit.
		add_action( 'wp_ajax_jet_booking_product_get_unit_booked_dates', [ $this, 'get_unit_booked_dates' ] );
		add_action( 'wp_ajax_nopriv_jet_booking_product_get_unit_booked_dates', [ $this, 'get_unit_booked_dates' ] );

		// Display only booking products in booking admin area.
		add_filter( 'jet-booking/tools/post-type-args', [ $this, 'set_additional_post_type_args' ] );

		// Calculate booking total price.
		add_filter( 'jet-booking/booking-total-price', [ $this, 'set_booking_total_price' ], 10, 2 );

		// Handle booking attributes in JetEngine listing (dynamic field).
		add_filter( 'jet-engine/listing/data/jet-booking-query/object-fields-groups', [ $this, 'set_object_fields'] );
		add_filter( 'jet-engine/listings/data/prop-not-found', [ $this, 'get_dynamic_field_prop' ], 10, 3 );

		// Return selected attributes for display.
		add_action( 'wp_ajax_jet_booking_get_attributes', [ $this, 'get_booking_attributes' ] );

		// Initialize datepicker functionality in quick view popup.
		add_action( 'wp_footer', [ $this, 'init_quick_view_datepicker' ] );

		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-attributes-manager.php';
		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-cart-manager.php';
		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-order-manager.php';

		$this->attributes = new WC_Attributes_Manager();

		new WC_Cart_Manager();
		new WC_Order_Manager();

	}

	/**
	 * Enqueue assets.
	 *
	 * Enqueue booking product related admin assets.
	 *
	 * @since 3.6.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		wp_register_style(
			'jet-booking-admin-css',
			JET_ABAF_URL . 'includes/wc-integration/assets/dist/css/admin.css',
			null,
			JET_ABAF_VERSION
		);

		wp_register_script(
			'jet-booking-admin-js',
			JET_ABAF_URL . 'includes/wc-integration/assets/dist/js/admin.min.js',
			[ 'jquery' ],
			JET_ABAF_VERSION,
			true
		);

	}

	/**
	 * Register custom product type.
	 *
	 * Includes files that contains custom product type logic.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_custom_product_type() {
		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-product-jet-booking.php';
	}

	/**
	 * WooCommerce custom product class.
	 *
	 * Return class name for custom product type.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string $classname    Extended class name to return.
	 * @param string $product_type Product type.
	 *
	 * @return mixed|string
	 */
	public function woocommerce_custom_product_class( $classname, $product_type ) {

		if ( $this->product_type === $product_type ) {
			$classname = 'WC_Product_Jet_Booking';
		}

		return $classname;

	}

	/**
	 * Add custom product type.
	 *
	 * Add new custom product type to product data select dropdown.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $types Array containing product types.
	 *
	 * @return mixed
	 */
	public function product_type_selector( $types ) {
		$types[ $this->product_type ] = __( 'JetBooking product', 'jet-booking' );

		return $types;
	}

	/**
	 * Maybe override product type.
	 *
	 * Override product type for New Product screen, if a request parameter is set.
	 *
	 * @param string $override Product Type
	 * @param int    $product_id
	 *
	 * @return string
	 */
	public function maybe_override_product_type( $override, $product_id ) {

		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! empty( $_REQUEST['jet_booking_product'] ) ) {
			return $this->product_type;
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return $override;

	}

	/**
	 * Product data tabs.
	 *
	 * Handle custom product type data tabs.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added new guests related tab.
	 *
	 * @param array $tabs Existing tabs list.
	 *
	 * @return array
	 */
	public function product_data_tabs( $tabs ) {

		$tabs['shipping']['class'][]       = 'hide_if_' . $this->product_type;
		$tabs['linked_product']['class'][] = 'hide_if_' . $this->product_type;
		$tabs['advanced']['class'][]       = 'hide_if_' . $this->product_type;

		$tabs['jet_booking_guests'] = [
			'label'  => __( 'Guests', 'jet-booking' ),
			'target' => 'jet_booking_guests',
			'class'  => [ 'show_if_' . $this->product_type ],
		];

		return $tabs;

	}

	/**
	 * Product data panels.
	 *
	 * Show the booking product custom data panels views.
	 *
	 * @since 3.6.0
	 *
	 * @return void
	 */
	public function product_data_panels() {

		echo '<div id="jet_booking_guests" class="panel woocommerce_options_panel">';
		echo '<div class="options_group">';

		woocommerce_wp_text_input( [
			'id'                => '_jet_booking_min_guests',
			'label'             => __( 'Min guests', 'jet-booking' ),
			'description'       => __( 'The minimum number of guests per booking.', 'jet-booking' ),
			'desc_tip'          => true,
			'type'              => 'number',
			'value'             => get_post_meta( get_the_ID(), '_jet_booking_min_guests', true ) ?: 1,
			'custom_attributes' => [
				'min'  => '0',
				'step' => '1',
			],
		] );

		woocommerce_wp_text_input( [
			'id'                => '_jet_booking_max_guests',
			'label'             => __( 'Max guests', 'jet-booking' ),
			'description'       => __( 'The maximum number of guests per booking.', 'jet-booking' ),
			'desc_tip'          => true,
			'type'              => 'number',
			'value'             => get_post_meta( get_the_ID(), '_jet_booking_max_guests', true ) ?: 1,
			'custom_attributes' => [
				'min'  => '0',
				'step' => '1',
			],
		] );

		woocommerce_wp_checkbox( [
			'id'          => '_jet_booking_guests_multiplier',
			'label'       => __( 'Multiply cost by guests count', 'jet-booking' ),
			'description' => __( 'Enable this to multiply the cost of the booking by the guests count.', 'jet-booking' ),
			'desc_tip'    => true,
			'value'       => get_post_meta( get_the_ID(), '_jet_booking_guests_multiplier', true ),
		] );

		echo '</div>';
		echo '</div>';

		wp_enqueue_style( 'jet-booking-admin-css' );
		wp_enqueue_script( 'jet-booking-admin-js' );

	}

	/**
	 * Booking data panel.
	 *
	 * Show the booking product data panel view.
	 *
	 * @since  3.0.0
	 * @since  3.8.0 Added Booking multiple units option.
	 *
	 * @return void
	 */
	public function booking_data_panel() {

		if ( ! wc_tax_enabled() ) : ?>
			<div class="options_group show_if_<?php echo esc_attr( $this->product_type ); ?>">
				<p> <?php esc_html_e( 'JetBooking product types not require default product data panels. Instead, you can control your booking instance with the help of custom meta boxes, improving user experience and streamlining management.', 'jet-booking' ); ?> </p>
				<p> <?php esc_html_e( 'Custom meta boxes showcase relevant information and settings, ensuring efficiency and simplicity in booking product creation. This customization allows for a more focused and intuitive interface for managing and configuring your booking products.', 'jet-booking' ); ?> </p>
			</div>
		<?php endif; ?>

		<div class="notice inline show_if_<?php echo esc_attr( $this->product_type ); ?>">
			<span class="dashicons dashicons-info-outline"></span>
			<p>
				<?php esc_html_e( 'Settings below apply only to the booking products with configured units.', 'jet-booking' ); ?>
				<a target="_blank" href="https://crocoblock.com/knowledge-base/jetbooking/how-to-manage-units-via-the-wp-dashboard/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help">
					<?php esc_html_e( 'Learn more', 'jet-booking' ); ?>
				</a>
			</p>
		</div>

		<div class="jet_booking_multiple_units options_group show_if_<?php echo esc_attr( $this->product_type ); ?>">
			<?php
				woocommerce_wp_checkbox( [
					'id'          => '_jet_booking_multiple_units',
					'label'       => __( 'Booking multiple units', 'jet-booking' ),
					'description' => __( 'Allows the user to select the number of units to book.', 'jet-booking' ),
					'value'       => get_post_meta( get_the_ID(), '_jet_booking_multiple_units', true ),
				] );

				echo wc_help_tip( esc_html__( 'Check to let customers to book more than one item at a time in the single form.', 'jet-booking' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>

		<?php
	}

	/**
	 * Product type options.
	 *
	 * Tweak and add extra product type options.
	 *
	 * @since 3.4.1
	 * @since 3.6.0 Added new guests related option.
	 * @since 3.7.1 Added new units related option.
	 *
	 * @param array $options List of product type options.
	 *
	 * @return array
	 */
	public function product_type_options( $options ) {

		$options['virtual']['wrapper_class'] .= ' show_if_' . $this->product_type;

		$options['jet_booking_has_guests'] = [
			'id'            => '_jet_booking_has_guests',
			'wrapper_class' => 'show_if_' . $this->product_type,
			'label'         => __( 'Has guests', 'jet-booking' ),
			'description'   => __( 'Enable this if booking product can be booked with a defined number of guests.', 'jet-booking' ),
			'default'       => 'no',
		];

		$options['jet_booking_units_selection'] = [
			'id'            => '_jet_booking_units_selection',
			'wrapper_class' => 'show_if_' . $this->product_type,
			'label'         => __( 'Unit Selection', 'jet-booking' ),
			'description'   => __( 'Enable this if booking product has multiple bookable units to display as a list for selection during booking, for example room types.', 'jet-booking' ),
			'default'       => 'no',
		];

		return $options;

	}

	/**
	 * Meta box classes.
	 *
	 * Returns the list of classes to be used by a meta box.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $classes Array of meta box classes.
	 *
	 * @return array
	 */
	public function meta_box_classes( $classes ) {
		$classes[] = 'show_if_' . $this->product_type;

		return $classes;
	}

	/**
	 * Save custom meta data.
	 *
	 * Save custom product meta boxes data.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added guest meta handling.
	 * @since  3.7.1 Added units meta handling.
	 * @since  3.8.0 Added multiple units meta handling.
	 *
	 * @param \WC_Product $product WooCommerce product instance.
	 *
	 * @return void
	 */
	public function save_custom_meta_data( $product ) {

		if ( ! $this->is_booking_product( $product ) ) {
			return;
		}

		$apartment_price = get_post_meta( $product->get_id(), '_apartment_price', true );

		if ( ! empty( $apartment_price ) ) {
			$product->set_props( [
				'price'         => $apartment_price,
				'regular_price' => $apartment_price,
			] );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$min_guests = ! empty( $_POST['_jet_booking_min_guests'] ) ? absint( $_POST['_jet_booking_min_guests'] ) : 1;
		$max_guests = ! empty( $_POST['_jet_booking_max_guests'] ) ? absint( $_POST['_jet_booking_max_guests'] ) : 1;

		$product_meta = [
			'_jet_booking_has_guests'        => isset( $_POST['_jet_booking_has_guests'] ) ? 'yes' : 'no',
			'_jet_booking_min_guests'        => wc_clean( min( $min_guests, $max_guests ) ),
			'_jet_booking_max_guests'        => wc_clean( max( $min_guests, $max_guests ) ),
			'_jet_booking_guests_multiplier' => isset( $_POST['_jet_booking_guests_multiplier'] ) ? 'yes' : 'no',
			'_jet_booking_units_selection'   => isset( $_POST['_jet_booking_units_selection'] ) ? 'yes' : 'no',
			'_jet_booking_multiple_units'    => isset( $_POST['_jet_booking_multiple_units'] ) ? 'yes' : 'no',
		];
		// phpcs:enable WordPress.Security.NonceVerification

		foreach ( $product_meta as $key => $value ) {
			update_post_meta( $product->get_id(), $key, $value );
		}

	}

	/**
	 * Display add to cart button.
	 *
	 * Display single product add to cart button for custom JetBooking product type.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function display_add_to_cart_button() {
		do_action( 'woocommerce_simple_add_to_cart' );
	}

	/**
	 * Before add to cart button.
	 *
	 * Adding JetBooking form open wrapper and custom booking (date picker) input field(s) to the single booking
	 * product page cart form.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added attributes and guests fields.
	 * @since  3.7.0 Added user email field.
	 * @since  3.7.1 Refactored.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function before_add_to_cart_button() {

		global $product;

		if ( ! $product || ! $this->is_booking_product( $product ) ) {
			return;
		}

		$product_id = $product->get_id();

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $product_id, 'jet-booking' );

		$layout          = jet_abaf()->settings->get( 'field_layout' );
		$field_format    = jet_abaf()->settings->get( 'field_date_format' );
		$field_separator = jet_abaf()->settings->get( 'field_separator' );
		$field_classes   = [ 'jet-abaf-field__input', 'input-text', 'text' ];
		$args            = [ 'name' => 'jet_abaf_field', 'required' => true ];
		$attrs           = '';

		if ( $field_separator ) {
			if ( 'space' === $field_separator ) {
				$field_separator = ' ';
			}

			$field_format = str_replace( '-', $field_separator, $field_format );
		}

		$default_value = '';
		$options       = jet_abaf()->tools->get_field_default_value( $default_value, $field_format, $product_id );

		jet_abaf()->assets->enqueue_deps( $product_id );

		wp_localize_script( 'jquery-date-range-picker', 'JetABAFInput', [
			'layout'        => $layout,
			'field_format'  => $field_format,
			'start_of_week' => jet_abaf()->settings->get( 'field_start_of_week' ),
			'options'       => $options,
		] );

		$checkin  = '';
		$checkout = '';

		if ( ! empty( $options ) ) {
			$checkin  = $options['checkin'] ?? '';
			$checkout = $options['checkout'] ?? '';

			if ( $checkin && $checkout ) {
				$default_value = jet_abaf()->settings->is_one_day_bookings( $product_id ) ? $checkin : $checkin . ' - ' . $checkout;
			}
		}

		echo '<div class="jet-booking-form">';

		$this->get_form_notice();
		$this->get_units_form_field( $product_id );
		$this->get_guests_form_field( $product_id );

		echo '<div class="jet-abaf-product-check-in-out">';

		if ( 'single' === $layout ) {
			$classes     = [ 'jet-abaf-field' ];
			$label       = jet_abaf()->settings->get( 'field_label' );
			$placeholder = jet_abaf()->settings->get( 'field_placeholder' );

			if ( jet_abaf()->settings->get( 'timepicker' ) ) {
				$classes[] = 'jet-abaf-field--has-timepicker';
			}

			if ( $label ) {
				/* translators: 1: field label */
				printf(
					'<div class="jet-abaf-field__label" ><label for="jet_abaf_field">%1$s <span class="jet-abaf-field__required">&nbsp;<abbr class="required" title="required">*</abbr></span></label></div>',
					esc_html( $label )
				);
			}

			include JET_ABAF_PATH . 'templates/form-field-single.php';
		} else {
			$fields_position      = jet_abaf()->settings->get( 'field_position' );
			$checkin_label        = jet_abaf()->settings->get( 'check_in_field_label' );
			$checkin_placeholder  = jet_abaf()->settings->get( 'check_in_field_placeholder' );
			$checkout_label       = jet_abaf()->settings->get( 'check_out_field_label' );
			$checkout_placeholder = jet_abaf()->settings->get( 'check_out_field_placeholder' );
			$label_classes        = [ 'jet-abaf-separate-field__label' ];
			$required_classes     = [ 'jet-abaf-field__required' ];
			$col_classes          = [ 'jet-abaf-separate-field' ];

			if ( 'list' === $fields_position ) {
				$col_classes[] = 'jet-abaf-separate-field__list';
			} else {
				$col_classes[] = 'jet-abaf-separate-field__inline';
			}

			if ( jet_abaf()->settings->get( 'timepicker' ) ) {
				$col_classes[] = 'jet-abaf-separate-field--has-timepicker';
			}

			include JET_ABAF_PATH . 'templates/form-field-separate.php';
		}

		$desc = jet_abaf()->settings->get( 'field_description' );

		if ( $desc ) {
			/* translators: 1: field description */
			printf( '<div class="jet-abaf-field__desc" ><small>%1$s</small></div>', esc_html( $desc ) );
		}

		echo '</div>';

		if ( ! jet_abaf()->settings->get( 'disable_email_field' ) ) {
			include JET_ABAF_PATH . 'templates/form-fields/email-field.php';
		}

		$this->get_attributes_form_field( $product_id );

		// Allow to add any additional HTML into Booking part of add to cart form on a single product page.
		do_action( 'jet-booking/wc-integration/add-to-cart/before-total', $product );

		echo '<div class="jet-abaf-product-total"></div>';

	}

	/**
	 * After add to cart button.
	 *
	 * Adding JetBooking form close wrapper to the single booking product page cart form.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function after_add_to_cart_button() {

		global $product;

		if ( ! $product || ! $this->is_booking_product( $product ) ) {
			return;
		}

		echo '</div>';

	}

	/**
	 * Print form notice.
	 *
	 * Checks if a booking form notice should be printed.
	 *
	 * @since 3.7.2
	 *
	 * @return void
	 */
	public function get_form_notice() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! jet_abaf()->settings->get( 'disable_email_field' ) ) {
			return;
		}

		if ( ! jet_abaf()->settings->get( 'enable_workflows' ) ) {
			return;
		}

		if ( empty( jet_abaf()->workflows->collection->to_array() ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning" style="background-color: #fcf8e3; color: #8a6d3b; border: 1px solid #faebcc; border-left-width: 4px; margin: 10px 0; padding: 10px;">%s</div>',
			esc_html__( 'Workflows is enabled and configured. To ensure proper functionality, please enable email field in the plugin settings.', 'jet-booking' )
		);

	}

	/**
	 * Get guests form field.
	 *
	 * This function retrieves and displays the guests form field for a booking product.
	 *
	 * @since 3.7.1
	 *
	 * @param int $id The ID of the booking product.
	 *
	 * @return void
	 */
	public function get_guests_form_field( $id ) {

		$has_guests = get_post_meta( $id, '_jet_booking_has_guests', true );

		if ( ! filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$min_guests = get_post_meta( $id, '_jet_booking_min_guests', true ) ?: 1;
		$max_guests = get_post_meta( $id, '_jet_booking_max_guests', true ) ?: 1;

		include JET_ABAF_PATH . 'templates/form-fields/guests-field.php';

	}

	/**
	 * Get units form field.
	 *
	 * This function retrieves and displays the unit form field for a booking product.
	 *
	 * @since 3.7.1
	 * @since 3.8.0 Added multiple units support.
	 *
	 * @param int $id The ID of the booking product.
	 *
	 * @return void
	 */
	public function get_units_form_field( $id ) {

		$multiple_units = get_post_meta( $id, '_jet_booking_multiple_units', true );

		if ( filter_var( $multiple_units, FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$units           = jet_abaf()->db->get_apartment_units( $id );
		$units_count     = ! empty( $units ) ? count( $units ) : 0;
		$units_selection = get_post_meta( $id, '_jet_booking_units_selection', true );

		if ( ! $units_count || 1 === $units_count || ! filter_var( $units_selection, FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		include JET_ABAF_PATH . 'templates/form-fields/units-field.php';

	}

	/**
	 * Get attributes form field.
	 *
	 * This function retrieves and displays the attributes form field for a booking product.
	 *
	 * @since 3.7.1
	 *
	 * @param int $id The ID of the booking product.
	 *
	 * @return void
	 */
	public function get_attributes_form_field( $id ) {

		$attributes = $this->attributes->get_attributes( $id );

		if ( empty( $attributes ) ) {
			return;
		}

		include JET_ABAF_PATH . 'templates/form-fields/attributes-field.php';

	}

	/**
	 * Get unit booked dates.
	 *
	 * Retrieve and return booked dates for a specific unit.
	 *
	 * @since 3.8.4
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function get_unit_booked_dates() {

		// phpcs:disable WordPress.Security.NonceVerification
		$post_id = ! empty( $_POST['postID'] ) ? absint( $_POST['postID'] ) : 0;
		$unit_id = ! empty( $_POST['unitID'] ) ? absint( $_POST['unitID'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $post_id, 'jet-booking', 60 );

		$booked_dates = jet_abaf()->settings->get_off_dates( $post_id, $unit_id );

		wp_send_json_success( [
			'booked_dates' => $booked_dates,
			'booked_next'  => jet_abaf()->tools->get_next_booked_dates( $booked_dates, $post_id )
		] );

	}

	/**
	 * Set total price.
	 *
	 * Set total cost for single booking product.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added attributes and guests cost calculation.
	 * @since  3.6.0 Added quantity multiplier..
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function set_total_price() {

		// phpcs:disable WordPress.Security.NonceVerification
		$post_id   = ! empty( $_POST['postID'] ) ? absint( $_POST['postID'] ) : 0;
		$total     = ! empty( $_POST['total'] ) ? floatval( $_POST['total'] ) : 0;
		$form_data = ! empty( $_POST['formData'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_POST['formData'] ) ) : []; // phpcs:ignore
		// phpcs:enable WordPress.Security.NonceVerification

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $post_id, 'jet-booking', 60 );

		$product = wc_get_product( $post_id );

		if ( $this->is_booking_product( $product ) ) {
			$guests     = 0;
			$has_guests = get_post_meta( $product->get_id(), '_jet_booking_has_guests', true );

			if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) && ! empty( $form_data['jet_abaf_guests'] ) ) {
				$guests            = $form_data['jet_abaf_guests'];
				$guests_multiplier = get_post_meta( $product->get_id(), '_jet_booking_guests_multiplier', true );

				if ( filter_var( $guests_multiplier, FILTER_VALIDATE_BOOLEAN ) ) {
					$total *= $guests;
				}
			}

			$attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );
			$terms      = [];

			foreach ( $attributes as $attribute ) {
				if ( $attribute->is_taxonomy() && ! empty( $form_data[ 'jet_abaf_product_' . $attribute->get_name() ] ) ) {
					$terms[ $attribute->get_name() ] = $form_data[ 'jet_abaf_product_' . $attribute->get_name() ];
				}
			}

			if ( ! empty( $terms ) && ! empty( $form_data['jet_abaf_field'] ) ) {
				$dates    = $this->get_transformed_dates( $form_data['jet_abaf_field'], $post_id );
				$interval = jet_abaf()->tools->get_booking_period_interval( $dates[0], $dates[1], $post_id );
				$total    += $this->attributes->get_attributes_cost( $terms, $interval, $post_id, $guests );
			}

			$multiple_units = get_post_meta( $product->get_id(), '_jet_booking_multiple_units', true );

			if ( filter_var( $multiple_units, FILTER_VALIDATE_BOOLEAN ) && ! empty( $form_data['quantity'] ) && absint( $form_data['quantity'] ) > 1 ) {
				$total *= $form_data['quantity'];
			}
		}

		/**
		 * Allows modifying total price when any changes happened in the Add to cart form on a single product page.
		 *
		 * @var float $total
		 */
		$total = apply_filters( 'jet-booking/wc-integration/add-to-cart/price', $total );

		ob_start();

		echo '<div class="jet-abaf-product-total__label">' . esc_html__( 'Total:', 'jet-booking' ) . '</div>';
		echo '<div class="jet-abaf-product-total__price">' . wc_price( $total ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$response['html'] = ob_get_clean();

		wp_send_json_success( $response );

	}

	/**
	 * Set booking total price.
	 *
	 * Calculate booking total price for admin booking popups.
	 *
	 * @param float $price   Booking total price.
	 * @param array $booking Booking data list.
	 *
	 * @return float|int|mixed
	 * @throws \Exception
	 */
	public function set_booking_total_price( $price, $booking ) {

		$guests     = 0;
		$has_guests = get_post_meta( $booking['apartment_id'], '_jet_booking_has_guests', true );

		if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) && ! empty( $booking['__guests'] ) ) {
			$guests            = $booking['__guests'];
			$guests_multiplier = get_post_meta( $booking['apartment_id'], '_jet_booking_guests_multiplier', true );

			if ( filter_var( $guests_multiplier, FILTER_VALIDATE_BOOLEAN ) ) {
				$price *= $guests;
			}
		}

		if ( empty( $booking['attributes'] ) ) {
			return $price;
		}

		$interval = jet_abaf()->tools->get_booking_period_interval( $booking['check_in_date'], $booking['check_out_date'], $booking['apartment_id'] );
		$price    += $this->attributes->get_attributes_cost( $booking['attributes'], $interval, $booking['apartment_id'], $guests );

		return $price;

	}

	/**
	 * Set additional post type args.
	 *
	 * Returns a list of arguments to display only booking products.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $args List of post arguments.
	 *
	 * @return mixed
	 */
	public function set_additional_post_type_args( $args ) {

		$args['tax_query'][] = [
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => $this->product_type,
		];

		return $args;

	}

	/**
	 * Get transformed dates.
	 *
	 * Returns transformed list of dates.
	 *
	 * @since  3.0.0
	 * @since  3.0.1 Added $product_id parameter.
	 * @access public
	 *
	 * @param string $dates      Initial booking dates.
	 * @param int    $product_id Booking product ID.
	 *
	 * @return false|string[]
	 */
	public function get_transformed_dates( $dates, $product_id ) {

		if ( empty( $dates ) ) {
			return false;
		}

		$dates     = explode( ' - ', $dates );
		$format    = jet_abaf()->settings->get( 'field_date_format' );
		$format    = jet_abaf()->tools->date_format_js_to_php( '!' . $format );
		$separator = jet_abaf()->settings->get( 'field_separator' );

		if ( 'space' === $separator ) {
			$separator = ' ';
		}

		if ( ! empty( $dates[0] ) ) {
			$dates[0] = str_replace( $separator, '-', $dates[0] );
		}

		if ( jet_abaf()->settings->is_one_day_bookings( $product_id ) ) {
			$dates[1] = $dates[0];
		} elseif ( ! empty( $dates[1] ) ) {
			$dates[1] = str_replace( $separator, '-', $dates[1] );
		}

		$check_in_object  = \DateTime::createFromFormat( $format, $dates[0] );
		$check_out_object = \DateTime::createFromFormat( $format, $dates[1] );

		$dates[0] = $check_in_object ? $check_in_object->getTimestamp() : strtotime( $dates[0] );
		$dates[1] = $check_out_object ? $check_out_object->getTimestamp() : strtotime( $dates[1] );

		if ( empty( $dates[0] ) || empty( $dates[1] ) ) {
			return false;
		}

		return $dates;

	}

	/**
	 * Get booking related order.
	 *
	 * Return booking related WooCommerce object instance.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Moved to WC based class and refactored.
	 *
	 * @param string|int $booking_id Booking ID.
	 *
	 * @return bool|\WC_Order|\WC_Order_Refund
	 */
	public function get_booking_related_order( $booking_id ) {

		if ( ! $booking_id ) {
			return false;
		}

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking ) {
			return false;
		}

		$order_id = $booking->get_order_id();

		if ( ! $order_id ) {
			return false;
		}

		return wc_get_order( $order_id );

	}

	/**
	 * Get booking related order item.
	 *
	 * Return booking related WooCommerce object instance item.
	 *
	 * @since  3.6.0
	 *
	 * @param string|int $booking_id Booking ID.
	 * @param string|int $order_id   Booking related order ID.
	 *
	 * @return bool|\WC_Order_Item
	 */
	public function get_booking_related_order_item( $booking_id, $order_id ) {

		if ( ! $booking_id || ! $order_id ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			if ( absint( $booking_id ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
				return $item;
			}
		}

		return false;

	}

	/**
	 * Set object fields.
	 *
	 * Add booking object fields into the dynamic field widget.
	 *
	 * @since 3.6.0
	 *
	 * @param array $options List of object field options.
	 *
	 * @return array
	 */
	public function set_object_fields( $options ) {

		$options['jet_abaf_get_attributes'] = __( 'Attributes', 'jet-booking' );
		$options['jet_abaf_get_guests']     = __( 'Guests', 'jet-booking' );

		return $options;

	}

	/**
	 * Get dynamic field prop.
	 *
	 * Returns dynamic property values.
	 *
	 * @since  3.6.0
	 *
	 * @param mixed  $result   Dynamic field property value.
	 * @param string $property Property name.
	 * @param object $object   Current listing object.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function get_dynamic_field_prop( $result, $property, $object ) {

		if ( ! $object || ! is_a( $object, '\JET_ABAF\Resources\Booking' ) ) {
			return $result;
		}

		if ( false !== strpos( $property, 'jet_abaf_' ) ) {
			$property = str_replace( 'jet_abaf_', '', $property );
		}

		if ( 'get_attributes' === $property && is_callable( [ $object, $property ] ) ) {
			$interval   = jet_abaf()->tools->get_booking_period_interval( $object->get_check_in_date(), $object->get_check_out_date(), $object->get_apartment_id() );
			$attributes = $this->attributes->get_attributes_for_display( call_user_func( [ $object, $property ] ), $interval, $object->get_apartment_id() );

			ob_start();

			echo "<div class='jet-booking-attributes'>";

			foreach ( $attributes as $key => $attribute ) {
				if ( ! empty( $attribute['value'] ) ) {
					/* translators: 1: attribute key, 2: attribute label, 3: attribute terms list */
					printf( '<div class="jet-booking-attribute jet-booking-attribute__%1$s"><span>%2$s:</span> %3$s</div>', esc_attr( $key ), esc_html( $attribute['label'] ), esc_html( $attribute['value'] ) );
				}
			}

			echo "</div>";

			$result = ob_get_clean();
		}


		return $result;

	}

	/**
	 * Get booking attributes for display.
	 *
	 * Price calculation and display after date selection in admin area for add & edit popups.
	 *
	 * @since  3.6.0
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function get_booking_attributes() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-bookings' ) ) { // phpcs:ignore
			wp_send_json_error();
		}

		$booking = ! empty( $_REQUEST['booking'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_REQUEST['booking'] ) ) : []; // phpcs:ignore

		if ( empty( $booking ) ) {
			wp_send_json_error();
		}

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $booking['apartment_id'], 'jet-booking', 60 );

		$response = [];

		if ( ! empty( $booking['attributes'] ) ) {
			$interval               = jet_abaf()->tools->get_booking_period_interval( strtotime( $booking['check_in_date'] ), strtotime( $booking['check_out_date'] ), $booking['apartment_id'] );
			$response['attributes'] = $this->attributes->get_attributes_for_display( $booking['attributes'], $interval, $booking['apartment_id'] );
		}

		wp_send_json_success( $response );

	}

	/**
	 * Init quick view datepicker.
	 *
	 * Initialize datepicker functionality in JetWooBuilder quick view popup.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	function init_quick_view_datepicker() {

		if ( ! function_exists( 'jet_woo_builder' ) || ! function_exists( 'jet_popup' ) ) {
			return;
		}

		$data = "
			jQuery( window ).on( 'jet-popup/render-content/ajax/success', function ( _, popupData ) {
				if ( ! popupData.data.isJetWooBuilder ) {
					return;
				}
		
				setTimeout( function() {
					JetBooking.initializeCheckInOut( null, 'form.cart' );
				}, 500 );
			} );
		";

		wp_add_inline_script( 'jet-popup-frontend', $data );

	}

	/**
	 * Is booking product.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param object $obj Object instance to check.
	 *
	 * @return bool
	 */
	public function is_booking_product( $obj ) {
		return is_object( $obj ) && is_a( $obj, 'WC_Product' ) && $this->product_type === $obj->get_type();
	}

}
