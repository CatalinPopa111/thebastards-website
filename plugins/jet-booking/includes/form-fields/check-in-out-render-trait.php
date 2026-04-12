<?php

namespace JET_ABAF\Form_Fields;

/**
 * @method getArgs( $key = '', $ifNotExist = false, $wrap_callable = false )
 * @method isRequired()
 * @method isNotEmptyArg( $key )
 * @method getCustomTemplate( $provider_id, $args )
 * @method scopeClass( $suffix = '' )
 * @method is_block_editor()
 * @method get_queried_post_id()
 *
 * Trait Check_In_Out_Render_Trait
 *
 * @package JET_ABAF\Form_Fields
 */
trait Check_In_Out_Render_Trait {

	/**
	 * Field Template.
	 *
	 * Print check-in-out field template.
	 *
	 * @since  2.3.0
	 * @since  2.5.2 Current date check for `$searched_dates` dates.
	 * @since  2.5.5 Updated date check code.
	 * @since  3.0.0 Refactored.
	 *
	 * @return false|string
	 * @throws \Exception
	 */
	public function field_template() {

		$args            = $this->getArgs();
		$layout          = $this->getArgs( 'cio_field_layout', 'single', 'esc_attr' );
		$default_value   = $this->getArgs( 'default', '', 'esc_attr' );
		$field_format    = $this->getArgs( 'cio_fields_format', 'YYYY-MM-DD', 'esc_attr' );
		$field_separator = $this->getArgs( 'cio_fields_separator', '', 'esc_attr' );
		$field_classes   = [ 'jet-abaf-field__input', $this->scopeClass( '__field' ) ];

		// Allow customizing check-in-out field attributes.
		$attrs = apply_filters( 'jet-booking/form-fields/check-in-out/attributes', '' );

		// Allow customizing check-in-out field default value.
		$default_value = apply_filters( 'jet-booking/form-fields/check-in-out/default-value', $default_value );

		if ( $field_separator ) {
			if ( 'space' === $field_separator ) {
				$field_separator = ' ';
			}

			$field_format = str_replace( '-', $field_separator, $field_format );
		}

		$post_id = apply_filters( 'jet-booking/form-fields/queried-post-id', $this->get_queried_post_id() );

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $post_id, 'jet-booking', 60 );

		$options = jet_abaf()->tools->get_field_default_value( $default_value, $field_format, $post_id );

		jet_abaf()->assets->enqueue_deps( $post_id );

		wp_localize_script( 'jquery-date-range-picker', 'JetABAFInput', [
			'layout'        => $layout,
			'field_format'  => $field_format,
			'start_of_week' => $this->getArgs( 'start_of_week', 'monday', 'esc_attr' ),
			'options'       => $options,
		] );

		ob_start();

		$checkin  = '';
		$checkout = '';

		if ( ! empty( $options ) ) {
			$checkin  = $options['checkin'] ?? '';
			$checkout = $options['checkout'] ?? '';

			if ( $checkin && $checkout ) {
				$default_value = jet_abaf()->settings->is_one_day_bookings( $post_id ) ? $checkin : $checkin . ' - ' . $checkout;
			}
		} else {
			$field_value = explode( ' - ', $default_value );

			if ( ! empty( $field_value ) ) {
				$checkin  = $field_value[0] ?? '';
				$checkout = $field_value[1] ?? '';
			}
		}

		if ( 'single' === $layout ) {
			$classes     = [ 'jet-abaf-field' ];
			$placeholder = $this->getArgs( 'first_field_placeholder', '', 'esc_attr' );

			if ( jet_abaf()->settings->get( 'timepicker' ) ) {
				$classes[] = 'jet-abaf-field--has-timepicker';
			}

			include JET_ABAF_PATH . 'templates/form-field-single.php';
		} else {
			$fields_position      = $this->getArgs( 'cio_fields_position', 'inline' );
			$checkin_label        = $this->getArgs( 'first_field_label', '', 'wp_kses_post' );
			$checkin_placeholder  = $this->getArgs( 'first_field_placeholder', '', 'esc_attr' );
			$checkout_label       = $this->getArgs( 'second_field_label', '', 'wp_kses_post' );
			$checkout_placeholder = $this->getArgs( 'second_field_placeholder', '', 'esc_attr' );
			$label_classes        = [ 'jet-abaf-separate-field__label', $this->scopeClass( '__label' ) ];
			$required_classes     = [ $this->scopeClass( '__required' ) ];
			$col_classes          = [ 'jet-abaf-separate-field' ];

			if ( jet_abaf()->settings->get( 'timepicker' ) ) {
				$col_classes[] = 'jet-abaf-separate-field--has-timepicker';
			}

			if ( 'list' === $fields_position ) {
				$col_classes[] = 'jet-form-col-12';
			} else {
				$col_classes[] = 'jet-form-col-6';
			}

			include JET_ABAF_PATH . 'templates/form-field-separate.php';
		}

		return ob_get_clean();

	}

}