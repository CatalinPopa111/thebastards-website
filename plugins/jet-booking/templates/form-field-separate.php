<?php
/**
 * Render check-in/check-out separate fields for booking form.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/form-field-separate.php
 *
 * @version 3.7.1
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<div class="jet-abaf-separate-fields" <?php echo esc_attr( $attrs ); ?> >
	<div class="<?php echo esc_attr( implode( ' ', $col_classes ) ); ?>">
		<?php if ( $checkin_label ) : ?>
			<div class="<?php echo esc_attr( implode( ' ', $label_classes ) ); ?>">
				<?php
					echo esc_html( $checkin_label );

					if ( ! empty( $args['required'] ) ) {
						echo '<span class="' . esc_attr( implode( ' ', $required_classes ) ) . '">&nbsp;<abbr class="required" title="required">*</abbr></span>';
					}
				?>
			</div>
		<?php endif; ?>

		<div class="jet-abaf-separate-field__control">
			<input
				type="text"
				id="jet_abaf_field_1"
				name="<?php echo esc_attr( $args['name'] ); ?>__in"
				class="<?php echo esc_attr( implode( ' ', $field_classes ) ); ?>"
				placeholder="<?php echo esc_attr( $checkin_placeholder ); ?>"
				value="<?php echo esc_attr( $checkin ); ?>"
				data-value="<?php echo esc_attr( $checkin ); ?>"
				autocomplete="off"
				readonly
				<?php echo ! empty( $args['required'] ) ? 'required' : ''; ?>
			/>

			<?php echo jet_abaf_get_timepicker_field( 'check-in', $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>

	<div class="<?php echo esc_attr( implode( ' ', $col_classes ) ); ?>">
		<?php if ( $checkout_label ) : ?>
			<div class="<?php echo esc_attr( implode( ' ', $label_classes ) ); ?>">
				<?php
					echo esc_html( $checkout_label );

					if ( ! empty( $args['required'] ) ) {
						echo '<span class="' . esc_attr( implode( ' ', $required_classes ) ) . '">&nbsp;<abbr class="required" title="required">*</abbr></span>';
					}
				?>
			</div>
		<?php endif; ?>

		<div class="jet-abaf-separate-field__control">
			<input
				type="text"
				id="jet_abaf_field_2"
				name="<?php echo esc_attr( $args['name'] ); ?>__out"
				class="<?php echo esc_attr( implode( ' ', $field_classes ) ); ?>"
				placeholder="<?php echo esc_attr( $checkout_placeholder ); ?>"
				value="<?php echo esc_attr( $checkout ); ?>"
				data-value="<?php echo esc_attr( $checkout ); ?>"
				autocomplete="off"
				readonly
				<?php echo ! empty( $args['required'] ) ? 'required' : ''; ?>
			/>

			<?php echo jet_abaf_get_timepicker_field( 'check-out', $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>

	<input
		type="hidden"
		id="jet_abaf_field_range"
		name="<?php echo esc_attr( $args['name'] ); ?>"
		class="<?php echo esc_attr( implode( ' ', $field_classes ) ); ?>"
		value="<?php echo esc_attr( $default_value ); ?>"
		data-value="<?php echo esc_attr( $default_value ); ?>"
		data-field="checkin-checkout"
		data-format="<?php echo esc_attr( $field_format ); ?>"
	/>
</div>

<?php jet_abaf()->assets->ensure_ajax_js(); ?>