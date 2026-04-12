<?php
/**
 * Render check-in/check-out single fields for booking form.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/form-field-single.php
 *
 * @version 3.7.1
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" <?php echo esc_attr( $attrs ); ?> >
	<input
		type="text"
		id="jet_abaf_field"
		name="<?php echo esc_attr( $args['name'] ); ?>"
		class="<?php echo esc_attr( implode( ' ', $field_classes ) ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
		value="<?php echo esc_attr( $default_value ); ?>"
		data-value="<?php echo esc_attr( $default_value ); ?>"
		data-field="checkin-checkout"
		data-format="<?php echo esc_attr( $field_format ); ?>"
		autocomplete="off"
		readonly
		<?php echo ! empty( $args['required'] ) ? 'required' : ''; ?>
	/>

	<?php if ( jet_abaf()->settings->get( 'timepicker' ) ) : ?>
		<div class="jet-abaf-timepicker-fields">
			<?php echo jet_abaf_get_timepicker_field( 'check-in', $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo jet_abaf_get_timepicker_field( 'check-out', $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</div>

<?php jet_abaf()->assets->ensure_ajax_js(); ?>