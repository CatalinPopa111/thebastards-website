<?php

namespace JET_ABAF\Components\Elementor_Views;

use \Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	public function __construct() {

		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		// Register widgets category.
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );

		// Register widgets.
		if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
			add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		} else {
			add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
		}

		// Enqueue preview scripts.
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'preview_scripts' ] );

		// Enqueue widgets icons styles.
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_icons_styles' ] );
		add_action( 'elementor/preview/enqueue_styles', [ $this, 'enqueue_icons_styles' ] );

		$init_action = 'elementor/init';

		// Init a module early on Elementor Data Updater
		if ( is_admin() && ( isset( $_GET['elementor_updater'] ) || isset( $_GET['elementor_pro_updater'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$init_action = 'elementor/documents/register';
		}
		// Initialize custom dynamic tags functionality.
		add_action( $init_action, [ $this, 'init_module' ] );

		if ( 'plain' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			// Inject date range picker style controls for JetFormBuilder plugin forms widget.
			add_action( 'elementor/element/jet-form-builder-form/section_repeater_style/after_section_end', [ $this, 'inject_date_range_picker_style_controls' ], 10, 2 );
		}

	}

	/**
	 * Register category.
	 *
	 * Register plugin category for elementor if not exists.
	 *
	 * @since 3.2.0
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor element manager instance.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'jet-booking',
			[
				'title' => __( 'JetBooking', 'jet-booking' ),
				'icon'  => 'font',
			]
		);
	}

	/**
	 * Register widgets.
	 *
	 * Register new Elementor widgets.
	 *
	 * @since  2.1.0
	 * @since  4.0.0 Registered new `Settings_Configuration` and `Settings_Schedule` widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new Widgets\Calendar() );
			$widgets_manager->register( new Widgets\Settings_Configuration() );
			$widgets_manager->register( new Widgets\Settings_Schedule() );
		} else {
			$widgets_manager->register_widget_type( new Widgets\Calendar() );
			$widgets_manager->register_widget_type( new Widgets\Settings_Configuration() );
			$widgets_manager->register_widget_type( new Widgets\Settings_Schedule() );
		}
	}

	/**
	 * Preview scripts.
	 *
	 * Enqueue preview scripts.
	 *
	 * @since  2.1.0
	 */
	public function preview_scripts() {
		jet_abaf()->assets->enqueue_deps( get_the_ID() );
	}

	/**
	 * Enqueue icons styles.
	 *
	 * Enqueue Elementor editor widgets icon styles.
	 *
	 * @since 2.1.0
	 */
	public function enqueue_icons_styles() {
		wp_enqueue_style(
			'jet-booking-icons',
			JET_ABAF_URL . 'assets/lib/jet-booking-icons/icons.css',
			[],
			JET_ABAF_VERSION
		);
	}

	/**
	 * Init module.
	 *
	 * Initialize custom dynamic tags module functionality.
	 *
	 * @since 3.2.0
	 */
	public function init_module() {
		new Dynamic_Tags\Module();
	}

	/**
	 * Inject date range picker style controls.
	 *
	 * Inject date range picker style controls for JetFormBuilder plugin forms widget.
	 *
	 * @since 3.2.0
	 *
	 * @param \Elementor\Controls_Stack $element The element type.
	 * @param array                     $args    Section arguments.
	 */
	public function inject_date_range_picker_style_controls( $element, $args ) {

		$element->start_controls_section(
			'date_range_picker_section',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Date Range Picker', 'jet-booking' ),
			]
		);

		$element->add_control(
			'date_range_picker_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper' => 'color: {{VALUE}}',
				],
			]
		);

		$element->add_control(
			'date_range_picker_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper' => 'background-color: {{VALUE}}',
				],
			]
		);

		$element->add_control(
			'date_range_picker_days_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Days', 'jet-booking' ),
				'separator' => 'before',
			]
		);

		$element->add_control(
			'date_range_picker_days_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody .day.valid' => 'color: {{VALUE}}',
				],
			]
		);

		$element->add_control(
			'date_range_picker_days_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody .day.toMonth' => 'background-color: {{VALUE}}',
				],
			]
		);

		$element->start_controls_tabs( 'tabs_date_range_picker_days_styles' );

		$element->start_controls_tab(
			'date_range_picker_inactive_days',
			[
				'label' => __( 'Inactive', 'jet-booking' ),
			]
		);

		$element->add_control(
			'date_range_picker_inactive_days_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.invalid' => 'color: {{VALUE}}',
				],
			]
		);

		$element->add_control(
			'date_range_picker_inactive_days_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.invalid' => 'background-color: {{VALUE}}',
				],
			]
		);

		$element->end_controls_tab();

		$element->start_controls_tab(
			'date_range_picker_current_day',
			[
				'label' => __( 'Current', 'jet-booking' ),
			]
		);

		$element->add_control(
			'date_range_picker_current_day_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody .day.real-today:not(.invalid)' => 'color: {{VALUE}}',
				],
			]
		);

		$element->add_control(
			'date_range_picker_current_day_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody .day.real-today:not(.invalid)' => 'background-color: {{VALUE}}',
				],
			]
		);

		$element->end_controls_tab();

		$element->start_controls_tab(
			'date_range_picker_edges',
			[
				'label' => __( 'Edges', 'jet-booking' ),
			]
		);

		$element->add_control(
			'date_range_picker_edges_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.first-date-selected' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.last-date-selected'  => 'color: {{VALUE}} !important;',
				],
			]
		);

		$element->add_control(
			'date_range_picker_edges_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.first-date-selected' => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.last-date-selected'  => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$element->end_controls_tab();

		$element->start_controls_tab(
			'date_range_picker_trace',
			[
				'label' => __( 'Trace', 'jet-booking' ),
			]
		);

		$element->add_control(
			'date_range_picker_trace_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.checked'           => 'color: {{VALUE}}',
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.hovering'          => 'color: {{VALUE}}',
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.has-tooltip:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$element->add_control(
			'date_range_picker_trace_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.checked'           => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.hovering'          => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .date-picker-wrapper tbody div.day.has-tooltip:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$element->end_controls_tab();

		$element->end_controls_tabs();

		$element->end_controls_section();

	}

}
