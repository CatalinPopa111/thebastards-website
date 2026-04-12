<?php
namespace JET_ABAF\Components\Elementor_Views\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Settings_Schedule extends Settings_Widget_Base {

	/**
	 * Get a widget name.
	 *
	 * Retrieve widget name.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'settings-schedule';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve widget title.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Settings Schedule', 'jet-booking' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve widget icon.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'jet-booking-icon-settings-schedule';
	}

	/**
	 * Get custom help URL.
	 *
	 * Retrieve a URL where the user can get more information about the widget.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string Widget help URL.
	 */
	public function get_custom_help_url() {
		return 'https://crocoblock.com/knowledge-base/jetbooking/settings-schedule-widget-overview/?utm_source=jetbooking&utm_medium=schedule-widget&utm_campaign=need-help';
	}

	/**
	 * Register widget controls.
	 *
	 * Add input fields to allow the user to customize the widget settings.
	 *
	 * @since  4.0.0
	 * @access protected
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'controls_section',
			[
				'label' => __( 'Controls', 'jet-booking' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_timepicker_controls',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Timepicker', 'jet-booking' ),
				'description' => __( 'If this option is checked, the timepicker controls will show.', 'jet-booking' ),
				'label_on'    => __( 'Show', 'jet-booking' ),
				'label_off'   => __( 'Hide', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->add_control(
			'show_rules_controls',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Booking Rules', 'jet-booking' ),
				'description' => __( 'If this option is checked, the booking rules controls will show.', 'jet-booking' ),
				'label_on'    => __( 'Show', 'jet-booking' ),
				'label_off'   => __( 'Hide', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->add_control(
			'show_days_off_controls',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Days Off', 'jet-booking' ),
				'description' => __( 'If this option is checked, the days off controls will show.', 'jet-booking' ),
				'label_on'    => __( 'Show', 'jet-booking' ),
				'label_off'   => __( 'Hide', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->end_controls_section();

		$this->get_section_controls_style();

		$this->get_section_fields_style();

		$this->get_section_toggle_style();

		$this->start_controls_section(
			'section_table_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Table', 'jet-booking' ),
			]
		);

		$this->add_control(
			'table_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-booking-rules-control .components-grid'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .components-booking-rules-control .components-grid svg' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'table_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-booking-rules-control .components-grid' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'table_even_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Even Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-booking-rules-control .components-grid:nth-child( even )' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_table_headings',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Headings', 'jet-booking' ),
				'separator' => 'before'
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'table_headings_typography',
				'selector' => '{{WRAPPER}} .components-booking-rules-control .components-item',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_panel_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Panel', 'jet-booking' ),
			]
		);

		$this->add_control(
			'heading_panel_title',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Title', 'jet-booking' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'panel_title_typography',
				'selector' => '{{WRAPPER}} .components-days-off-control .components-panel__body-toggle',
			]
		);

		$this->add_control(
			'panel_title_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control .components-panel__body-toggle'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .components-days-off-control .components-panel__body-toggle svg' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_panel_buttons',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Buttons', 'jet-booking' ),
				'separator' => 'before'
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'panel_buttons_typography',
				'selector' => '{{WRAPPER}} .components-days-off-control__add-days-actions .components-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'panel_buttons_border',
				'exclude'  => [ 'color' ],
				'selector' => '{{WRAPPER}} .components-days-off-control__add-days-actions .components-button',
			]
		);

		$this->start_controls_tabs( 'tabs_panel_buttons_style' );

		$this->start_controls_tab(
			'tab_panel_buttons_normal',
			[
				'label' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->add_control(
			'heading_confirm_panel_button',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Confirm Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'confirm_panel_button_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'confirm_panel_button_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'confirm_panel_button_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'panel_buttons_border_border!' => '',
				],
			]
		);

		$this->add_control(
			'heading_cancel_panel_button',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Cancel Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'cancel_panel_button_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'cancel_panel_button_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'cancel_panel_button_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'panel_buttons_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_panel_buttons_hover',
			[
				'label' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->add_control(
			'heading_confirm_panel_button_hover',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Confirm Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'confirm_panel_button_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'confirm_panel_button_bg_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'confirm_panel_button_border_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary:hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'panel_buttons_border_border!' => '',
				],
			]
		);

		$this->add_control(
			'heading_cancel_panel_button_hover',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Cancel Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'cancel_panel_button_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'cancel_panel_button_bg_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'cancel_panel_button_border_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary:hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'panel_buttons_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'panel_buttons_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'panel_buttons_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_card_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Card', 'jet-booking' ),
			]
		);

		$this->add_control(
			'heading_card_header',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Header', 'jet-booking' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'card_header_typography',
				'selector' => '{{WRAPPER}} .components-days-off-control__days-list .components-card__header .components-text',
			]
		);

		$this->add_control(
			'card_header_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-card__header .components-text' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_card_footer',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Footer', 'jet-booking' ),
				'separator' => 'before'
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'card_footer_typography',
				'selector' => '{{WRAPPER}} .components-days-off-control__days-list .components-card__footer .components-text',
			]
		);

		$this->add_control(
			'card_footer_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-card__footer .components-text' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_card_buttons',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Buttons', 'jet-booking' ),
				'separator' => 'before'
			]
		);

		$this->add_control(
			'card_buttons_icon_size',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Icon SIze', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button svg' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'card_buttons_border',
				'exclude'  => [ 'color' ],
				'selector' => '{{WRAPPER}} .components-days-off-control__days-list .components-button',
			]
		);

		$this->start_controls_tabs( 'tabs_card_buttons_style' );

		$this->start_controls_tab(
			'tab_card_buttons_normal',
			[
				'label' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->add_control(
			'heading_edit_confirm_card_button',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Edit/Confirm Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'edit_confirm_card_button_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive) svg' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'edit_confirm_card_button_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive)' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'edit_confirm_card_button_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive)' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'card_buttons_border_border!' => '',
				],
			]
		);

		$this->add_control(
			'heading_delete_cancel_card_button',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Delete/Cancel Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'delete_cancel_card_button_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive svg' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'delete_cancel_card_button_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'delete_cancel_card_button_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'card_buttons_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_card_buttons_hover',
			[
				'label' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->add_control(
			'heading_edit_confirm_card_button_hover',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Edit/Confirm Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'edit_confirm_card_button_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive):hover svg' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'edit_confirm_card_button_bg_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive):hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'edit_confirm_card_button_border_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive):hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'card_buttons_border_border!' => '',
				],
			]
		);

		$this->add_control(
			'heading_delete_cancel_card_button_hover',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Delete/Cancel Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'delete_cancel_card_button_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive:hover svg' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'delete_cancel_card_button_bg_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'delete_cancel_card_button_border_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive:hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'card_buttons_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'card_buttons_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'card_buttons_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .components-days-off-control__days-list .components-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->get_section_button_style();

		$this->get_section_messages_style();

	}

	/**
	 * Render widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since  4.0.0
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$data = [
			'show_timepicker_controls' => filter_var( $settings['show_timepicker_controls'], FILTER_VALIDATE_BOOLEAN ),
			'show_rules_controls'      => filter_var( $settings['show_rules_controls'], FILTER_VALIDATE_BOOLEAN ),
			'show_days_off_controls'   => filter_var( $settings['show_days_off_controls'], FILTER_VALIDATE_BOOLEAN ),
		];

		echo '<div class="jet-abaf-settings-schedule-root" data-settings="' . esc_attr( json_encode( $data ) ) . '"></div>';

	}

}
