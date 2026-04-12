<?php

namespace JET_ABAF\Render;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Calendar {

	/**
	 * Calendar instance ID.
	 *
	 * @var bool
	 */
	private $instance_id = false;

	/**
	 * Settings list.
	 *
	 * @var array|mixed
	 */
	private $settings = null;

	public function __construct( $settings = [] ) {
		$this->settings = $settings;
	}

	/**
	 * Get settings.
	 *
	 * Return all settings list. If setting parameter specified< return specific setting.
	 *
	 * @since 3.2.0
	 *
	 * @param null $setting Setting name.
	 *
	 * @return array|false|mixed
	 */
	public function get_settings( $setting = null ) {
		if ( $setting ) {
			return $this->settings[ $setting ] ?? false;
		} else {
			return $this->settings;
		}
	}

	/**
	 * Render.
	 *
	 * Render calendar element content.
	 *
	 * @since 2.1.0
	 * @since 3.6.5 Added post ID check.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function render() {

		if ( ! $this->instance_id ) {
			$this->instance_id = 'calendar_' . rand( 1000, 9999 );
		}

		$post_id = get_the_ID();

		wp_cache_delete( jet_abaf()->settings::CONTEXT_KEY, 'jet-booking' );
		wp_cache_set( jet_abaf()->settings::CONTEXT_KEY, $post_id, 'jet-booking', 60 );

		jet_abaf()->assets->enqueue_deps( $post_id );

		$settings        = $this->get_settings();
		$select_dates    = ! empty( $settings['select_dates'] ) ? filter_var( $settings['select_dates'], FILTER_VALIDATE_BOOLEAN ) : false;
		$scroll_to_form  = ! empty( $settings['scroll_to_form'] ) ? filter_var( $settings['scroll_to_form'], FILTER_VALIDATE_BOOLEAN ) : false;
		$wrapper_classes = [ 'jet-booking-calendar' ];

		if ( ! $select_dates ) {
			$wrapper_classes[] = 'disable-dates-select';
		}

		printf(
			'<div class="%s"><input type="hidden" class="jet-booking-calendar__input"/><div id="%s" class="jet-booking-calendar__container" data-scroll-to-form="%s"></div></div>',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			esc_attr( $this->instance_id ),
			esc_attr( $scroll_to_form )
		);

		jet_abaf()->assets->ensure_ajax_js();

	}

}
