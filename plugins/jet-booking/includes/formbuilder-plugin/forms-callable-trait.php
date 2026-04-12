<?php

namespace JET_ABAF\Formbuilder_Plugin;

use JET_ABAF\Formbuilder_Plugin\Jfb_Plugin as Builder_Plugin;

trait Forms_Callable_Trait {

	public function form_types_map() {
		return array(
			'create_single_form' => array( $this, 'get_single_form' ),
		);
	}

	public function get_single_form( $args = array() ) {
		if ( $args['order_post_type'] ) {
			$form = file_get_contents(
				Builder_Plugin::get_path( 'forms/single-form-with-order.json' )
			);
			$form = str_replace( 'order-booking', $args['order_post_type'], $form );
		} else {
			$form = file_get_contents( Builder_Plugin::get_path( 'forms/single-form.json' ) );
		}

		return json_decode( $form, true );
	}

}