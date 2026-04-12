<?php

namespace JET_ABAF\WC_Integration;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class WC_Attributes_Manager {

	/**
	 * Attribute type.
	 *
	 * Hold the name of WC attribute type.
	 *
	 * @since  3.6.0
	 *
	 * @var string
	 */
	public $attribute_type = 'jet_booking_service';

	public function __construct() {

		// Register additional attribute type.
		add_filter( 'product_attributes_type_selector', [ $this, 'attribute_types' ] );

		// Add form fields for booking attributes terms.
		add_action( 'admin_init', [ $this, 'add_attribute_terms_meta' ] );

		// Display custom attribute terms.
		add_action( 'woocommerce_product_option_terms', [ $this, 'product_option_terms' ], 10, 3 );

		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-booking-term-meta.php';

	}

	/**
	 * Register additional attribute type.
	 *
	 * @since 3.6.0
	 *
	 * @param array $types List of attribute types.
	 *
	 * @return array
	 */
	public function attribute_types( $types ) {
		$types[ $this->attribute_type ] = __( 'Booking Service', 'jet-booking' );

		return $types;
	}

	/**
	 * Register form fields for booking attributes terms.
	 *
	 * @since 3.6.0
	 *
	 * @return void
	 */
	public function add_attribute_terms_meta() {

		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $taxonomy ) {
				if ( $this->attribute_type === $taxonomy->attribute_type ) {
					new WC_Booking_Term_Meta( $taxonomy->attribute_name );
				}
			}
		}

	}

	/**
	 * Get attributes.
	 *
	 * Returns list of booking service attributes & attributes terms.
	 *
	 * @since 3.6.0
	 *
	 * @param int|string $id Booking instance ID.
	 *
	 * @return array
	 */
	public function get_attributes( $id = null ) {

		$value = [];

		if ( $id ) {
			$product = wc_get_product( $id );

			if ( ! jet_abaf()->wc->mode->is_booking_product( $product ) ) {
				return $value;
			}

			$attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );
		} else {
			$attributes = wc_get_attribute_taxonomies();
		}

		if ( empty( $attributes ) ) {
			return $value;
		}

		foreach ( $attributes as $attribute ) {
			if ( $attribute && is_a( $attribute, 'WC_Product_Attribute' ) ) {
				$attribute = $attribute->get_taxonomy_object();
			}

			if ( ! $attribute || $this->attribute_type !== $attribute->attribute_type ) {
				continue;
			}

			$attribute_name = wc_attribute_taxonomy_name( $attribute->attribute_name );

			if ( $id ) {
				$attribute_terms = wc_get_product_terms( $id, $attribute_name, [ 'fields' => 'all' ] );
			} else {
				$attribute_terms = get_terms( [ 'taxonomy' => $attribute_name, 'hide_empty' => false ] );
			}

			$terms = [];

			foreach ( $attribute_terms as $term ) {
				$terms[ $term->slug ] = [
					'name'             => $term->name,
					'label'            => $this->get_attribute_term_formated_label( $term ),
					'description'      => $term->description,
					'cost'             => get_term_meta( $term->term_id, 'jet_abaf_service_cost', true ),
					'everyday_service' => get_term_meta( $term->term_id, 'jet_abaf_everyday_service', true ),
				];
			}

			if ( ! empty( $terms ) ) {
				$value[ $attribute_name ] = [
					'label' => wc_attribute_label( $attribute_name ),
					'terms' => $terms,
				];
			}
		}

		return $value;

	}

	/**
	 * Get attributes for display.
	 *
	 * Returns list of formated attribute terms for display.
	 *
	 * @since 3.6.0
	 *
	 * @param array         $attributes List of attributes terms.
	 * @param \DateInterval $interval   Date interval object.
	 * @param int|string    $post_id    Booking instance ID.
	 *
	 * @return array
	 */
	public function get_attributes_for_display( $attributes, $interval, $post_id ) {

		$result  = [];
		$product = wc_get_product( $post_id );

		if ( ! jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			return $result;
		}

		foreach ( $attributes as $key => $values ) {
			$terms     = [];
			$attribute = $product->get_attribute( $key );

			if ( empty( $attribute ) ) {
				continue;
			}

			$attribute_terms = explode( ', ', $attribute );

			foreach ( $values as $value ) {
				$term = get_term_by( 'slug', $value, $key );

				if ( ! $term ) {
					continue;
				}

				if ( ! in_array( $term->name, $attribute_terms ) ) {
					continue;
				}

				if ( get_term_meta( $term->term_id, 'jet_abaf_everyday_service', true ) ) {
					/* translators: 1: attribute term name, 2: interval days count */
					$terms[] = sprintf( __( '%1$s × %2$s', 'jet-booking' ), $term->name, $interval->days );
				} else {
					$terms[] = $term->name;
				}
			}

			if ( ! empty( $terms ) ) {
				$result[ $key ] = [
					'label' => wc_attribute_label( $key ),
					'value' => implode( ', ', $terms )
				];
			}
		}

		return $result;

	}

	/**
	 * Get attribute term formated label.
	 *
	 * Returns attributes term formated label based on terms meta.
	 *
	 * @since 3.6.0
	 *
	 * @param \WP_Term $term Attribute term instance.
	 *
	 * @return mixed|string
	 */
	public function get_attribute_term_formated_label( $term ) {

		$label = $term->name;

		$term_cost = get_term_meta( $term->term_id, 'jet_abaf_service_cost', true );

		if ( ! empty( $term_cost ) ) {
			$cost_format = get_term_meta( $term->term_id, 'jet_abaf_service_cost_format', true );
			$format      = ! empty( $cost_format ) ? esc_html__( $cost_format, 'jet-booking' ) : '%s';

			$label .= sprintf( ' ' . $format, wc_price( $term_cost ) );
		}

		return $label;

	}

	/**
	 * Get attributes cost.
	 *
	 * Returns calculated attributes cost based on term settings.
	 *
	 * @since 3.6.0
	 *
	 * @param array         $attributes List of attributes terms.
	 * @param \DateInterval $interval   Date interval object.
	 * @param int|string    $post_id    Booking instance ID.
	 * @param int           $guests     Booking guests number.
	 *
	 * @return float|int|mixed
	 */
	public function get_attributes_cost( $attributes, $interval, $post_id, $guests ) {

		$cost    = 0;
		$product = wc_get_product( $post_id );

		if ( ! jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			return $cost;
		}

		foreach ( $attributes as $key => $values ) {
			$attribute = $product->get_attribute( $key );

			if ( empty( $attribute ) ) {
				continue;
			}

			$attribute_terms = explode( ', ', $attribute );

			foreach ( $values as $value ) {
				$term = get_term_by( 'slug', $value, $key );

				if ( ! $term ) {
					continue;
				}

				if ( ! in_array( $term->name, $attribute_terms ) ) {
					continue;
				}

				$term_cost = get_term_meta( $term->term_id, 'jet_abaf_service_cost', true );

				if ( empty( $term_cost ) ) {
					continue;
				}

				if ( get_term_meta( $term->term_id, 'jet_abaf_guests_multiplier', true ) && $guests ) {
					$term_cost *= $guests;
				}

				if ( get_term_meta( $term->term_id, 'jet_abaf_everyday_service', true ) ) {
					$cost += $interval->days * $term_cost;
				} else {
					$cost += $term_cost;
				}
			}
		}

		return $cost;

	}

	/**
	 * Product option terms.
	 *
	 * Display custom attribute terms.
	 *
	 * @since 3.6.0
	 *
	 * @param array|null            $attribute_taxonomy Attribute taxonomy object.
	 * @param number                $i                  Attribute index.
	 * @param \WC_Product_Attribute $attribute          Woocommerce attribute instance.
	 */
	public function product_option_terms( $attribute_taxonomy, $i, $attribute ) {
		if ( 'select' !== $attribute_taxonomy->attribute_type && $this->attribute_type === $attribute_taxonomy->attribute_type ) {
			$attribute_orderby = ! empty( $attribute_taxonomy->attribute_orderby ) ? $attribute_taxonomy->attribute_orderby : 'name';

			/**
			 * Filter the length (number of terms) rendered in the list.
			 *
			 * @since 8.8.0
			 *
			 * @param int $term_limit The maximum number of terms to display in the list.
			 */
			$term_limit = absint( apply_filters( 'woocommerce_admin_terms_metabox_datalimit', 50 ) );
			?>
			<select
				multiple="multiple"
				data-minimum_input_length="0"
				data-limit="<?php echo esc_attr( $term_limit ); ?>" data-return_id="id"
				data-placeholder="<?php esc_attr_e( 'Select values', 'woocommerce' ); ?>"
				data-orderby="<?php echo esc_attr( $attribute_orderby ); ?>"
				class="multiselect attribute_values wc-taxonomy-term-search"
				name="attribute_values[<?php echo esc_attr( $i ); ?>][]"
				data-taxonomy="<?php echo esc_attr( $attribute->get_taxonomy() ); ?>"
			>
				<?php
					$selected_terms = $attribute->get_terms();

					if ( $selected_terms ) {
						foreach ( $selected_terms as $selected_term ) {
							/**
							 * Filter the selected attribute term name.
							 *
							 * @since 3.4.0
							 *
							 * @param string $name Name of selected term.
							 * @param array  $term The selected term object.
							 */
							echo '<option value="' . esc_attr( $selected_term->term_id ) . '" selected="selected">' . esc_html( apply_filters( 'woocommerce_product_attribute_term_name', $selected_term->name, $selected_term ) ) . '</option>';
						}
					}
				?>
			</select>
			<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></button>
			<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></button>
			<button class="button fr plus add_new_attribute"><?php esc_html_e( 'Create value', 'woocommerce' ); ?></button>
			<?php
		}
	}

}