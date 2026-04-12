<?php

namespace JET_ABAF\WC_Integration;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class WC_Booking_Term_Meta {

	/**
	 * Booking term.
	 *
	 * Hold the name of WC attribute.
	 *
	 * @since  3.6.0
	 *
	 * @var string
	 */
	private $term;

	public function __construct( $term ) {

		$this->term = wc_attribute_taxonomy_name( $term );

		// Add form fields to WooCommerce attributes.
		add_action( "{$this->term}_add_form_fields", [ $this, 'add_form_fields' ] );
		add_action( "{$this->term}_edit_form_fields", [ $this, 'edit_form_fields' ] );
		add_action( 'created_term', [ $this, 'save' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'save' ], 10, 3 );

	}

	/**
	 * Add form fields.
	 *
	 * Added booking related form fields to the interface of adding new attributes.
	 *
	 * @since 3.6.0
	 *
	 * @return void
	 */
	public function add_form_fields() {
		?>
		<div class="form-field">
			<label for="jet_abaf_service_cost"><?php esc_html_e( 'Service Cost', 'jet-booking' ); ?></label>
			<input name="jet_abaf_service_cost" id="jet_abaf_service_cost" type="number" value="" step="0.01" />
			<p><?php esc_html_e( 'One-off cost for the service.', 'jet-booking' ); ?></p>
		</div>

		<div class="form-field">
			<label for="jet_abaf_service_cost_format"><?php esc_html_e( 'Cost Format', 'jet-booking' ); ?></label>
			<input name="jet_abaf_service_cost_format" id="jet_abaf_service_cost_format" type="text" value="%s" />
			<p><?php esc_html_e( 'The format string, %s will be replaced with service cost field value.', 'jet-booking' ); ?></p>
		</div>

		<div class="form-field">
			<label for="jet_abaf_guests_multiplier"><input name="jet_abaf_guests_multiplier" id="jet_abaf_guests_multiplier" type="checkbox" value="1" /><?php esc_html_e( 'Multiply cost by guests count', 'jet-booking' ); ?></label>
			<p><?php esc_html_e( 'Enable this to multiply the service cost by booking instance guests count.', 'jet-booking' ); ?></p>
		</div>

		<div class="form-field">
			<label for="jet_abaf_everyday_service"><input name="jet_abaf_everyday_service" id="jet_abaf_everyday_service" type="checkbox" value="1" /><?php esc_html_e( 'Everyday Service?', 'jet-booking' ); ?></label>
			<p><?php esc_html_e( 'Enable this to multiply the service cost by booking days count.', 'jet-booking' ); ?></p>
		</div>

		<script type="text/javascript">
            jQuery( document ).ajaxComplete( function( event, request, options ) {
                if ( request && 4 === request.readyState && 200 === request.status && options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {
                    jQuery( '#jet_abaf_everyday_service, #jet_abaf_guests_multiplier' ).prop( 'checked', false );
                    jQuery( '#jet_abaf_service_cost_format' ).val( '%s' );
                }
            } );
		</script>
		<?php
	}

	/**
	 * Edit form fields.
	 *
	 * Added booking related form fields to the interface of editing attributes.
	 *
	 * @since 3.6.0
	 *
	 * @param object $term Attribute instance.
	 *
	 * @return void
	 */
	public function edit_form_fields( $term ) {
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="jet_abaf_service_cost"><?php esc_html_e( 'Service Cost', 'jet-booking' ); ?></label>
			</th>
			<td>
				<input name="jet_abaf_service_cost" id="jet_abaf_service_cost" type="number" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'jet_abaf_service_cost', true ) ); ?>" step="0.01" />
				<p class="description"><?php esc_html_e( 'One-off cost for the service.', 'jet-booking' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="jet_abaf_service_cost_format"><?php esc_html_e( 'Cost Format', 'jet-booking' ); ?></label>
			</th>
			<td>
				<input name="jet_abaf_service_cost_format" id="jet_abaf_service_cost_format" type="text" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'jet_abaf_service_cost_format', true ) ); ?>" />
				<p class="description"><?php esc_html_e( 'The format string, %s will be replaced with service cost field value.', 'jet-booking' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="jet_abaf_guests_multiplier"><?php esc_html_e( 'Multiply cost by guests count', 'jet-booking' ); ?></label>
			</th>
			<td>
				<input name="jet_abaf_guests_multiplier" id="jet_abaf_guests_multiplier" type="checkbox" value="1" <?php checked( get_term_meta( $term->term_id, 'jet_abaf_guests_multiplier', true ), 1 ); ?> />
				<p class="description"><?php esc_html_e( 'Enable this to multiply the service cost by booking instance guests count.', 'jet-booking' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="jet_abaf_everyday_service"><?php esc_html_e( 'Everyday Service?', 'jet-booking' ); ?></label>
			</th>
			<td>
				<input name="jet_abaf_everyday_service" id="jet_abaf_everyday_service" type="checkbox" value="1" <?php checked( get_term_meta( $term->term_id, 'jet_abaf_everyday_service', true ), 1 ); ?> />
				<p class="description"><?php esc_html_e( 'Enable this to multiply the service cost by booking days count.', 'jet-booking' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save attributes fields.
	 *
	 * @since 3.6.0
	 *
	 * @param mixed  $term_id  Term ID being saved.
	 * @param mixed  $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return void
	 */
	public function save( $term_id, $tt_id = '', $taxonomy = '' ) {

		if ( $this->term !== $taxonomy ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$term_meta = [
			'jet_abaf_service_cost'        => isset( $_POST['jet_abaf_service_cost'] ) ? wc_clean( wp_unslash( $_POST['jet_abaf_service_cost'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'jet_abaf_service_cost_format' => ! empty( $_POST['jet_abaf_service_cost_format'] ) ? wc_clean( wp_unslash( $_POST['jet_abaf_service_cost_format'] ) ) : '%s', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'jet_abaf_guests_multiplier'   => isset( $_POST['jet_abaf_guests_multiplier'] ),
			'jet_abaf_everyday_service'    => isset( $_POST['jet_abaf_everyday_service'] ),
		];
		// phpcs:enable WordPress.Security.NonceVerification

		foreach ( $term_meta as $key => $value ) {
			update_term_meta( $term_id, $key, $value );
		}

	}

}
