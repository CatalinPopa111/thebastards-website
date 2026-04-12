<?php
namespace JET_ABAF\Components\Elementor_Views\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

abstract class Settings_Widget_Base extends Widget_Base {

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories widget belongs to.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'jet-booking' ];
	}

	/**
	 * Get script dependencies.
	 *
	 * Retrieve the list of script dependencies the widget requires.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array Widget script dependencies.
	 */
	public function get_script_depends() {
		return [ 'jet-booking-widgets' ];
	}

	/**
	 * Get style dependencies.
	 *
	 * Retrieve the list of style dependencies the widget requires.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array Widget style dependencies.
	 */
	public function get_style_depends() {
		return [ 'jet-booking-widgets', 'wp-components' ];
	}

	/**
	 * Get section controls style.
	 *
	 * Registers and configures the controls for the controls style section.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function get_section_controls_style() {

		$this->start_controls_section(
			'section_controls_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Controls', 'jet-booking' ),
			]
		);

		$this->add_control(
			'heading_label',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Label', 'jet-booking' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .components-base-control label, {{WRAPPER}} .components-base-control .components-base-control__label',
			]
		);

		$this->add_control(
			'label_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-base-control label'                           => 'color: {{VALUE}}',
					'{{WRAPPER}} .components-base-control .components-base-control__label' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_description',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Description', 'jet-booking' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .components-base-control .components-base-control__help',
			]
		);

		$this->add_control(
			'description_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-base-control .components-base-control__help' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Get section fields style.
	 *
	 * Registers and configures the controls for the fields style section.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function get_section_fields_style() {

		$this->start_controls_section(
			'section_fields_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Fields', 'jet-booking' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'field_typography',
				'selector' => '{{WRAPPER}} .components-base-control .components-text-control__input, {{WRAPPER}} .components-base-control .components-select-control__input',
			]
		);

		$this->add_control(
			'field_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-base-control .components-text-control__input'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .components-base-control .components-select-control__input' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'field_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-base-control .components-text-control__input'   => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .components-base-control .components-select-control__input' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'field_border',
				'selector' => '{{WRAPPER}} .components-base-control .components-text-control__input, {{WRAPPER}} .components-base-control .components-input-control__backdrop',
			]
		);

		$this->add_control(
			'field_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .components-base-control .components-text-control__input'     => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .components-base-control .components-select-control__input'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .components-base-control .components-input-control__backdrop' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Get section toggle style.
	 *
	 * Registers and configures the controls for the toggle style section.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function get_section_toggle_style() {

		$this->start_controls_section(
			'section_toggle_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Toggle', 'jet-booking' ),
			]
		);

		$this->add_control(
			'toggle_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-form-toggle:not(.is-checked) .components-form-toggle__track' => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .components-form-toggle:not(.is-checked) .components-form-toggle__thumb' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'toggle_color_active',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Active Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-form-toggle.is-checked .components-form-toggle__track' => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
					'{{WRAPPER}} .components-form-toggle.is-checked .components-form-toggle__thumb' => 'background-color: #fff',
				],
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Get section button style.
	 *
	 * Registers and configures the controls for the button style section.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function get_section_button_style() {

		$this->start_controls_section(
			'section_button_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Save Button', 'jet-booking' ),
			]
		);

		$this->add_control(
			'button_position',
			[
				'type'      => Controls_Manager::CHOOSE,
				'label'     => __( 'Position', 'jet-booking' ),
				'options'   => [
					'start'  => [
						'title' => __( 'Start', 'jet-booking' ),
						'icon'  => ! is_rtl() ? 'eicon-h-align-left' : 'eicon-h-align-right',
					],
					'center' => [
						'title' => __( 'Center', 'jet-booking' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => __( 'End', 'jet-booking' ),
						'icon'  => ! is_rtl() ? 'eicon-h-align-right' : 'eicon-h-align-left',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .components-actions-control' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .components-actions-control .components-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .components-actions-control .components-button',
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->add_control(
			'button_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-actions-control .components-button' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-actions-control .components-button' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->add_control(
			'button_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-actions-control .components-button:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_bg_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-actions-control .components-button:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_border_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-actions-control .components-button:hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'button_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .components-actions-control .components-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .components-actions-control .components-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Get section message style.
	 *
	 * Registers and configures the controls for the message style section.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function get_section_messages_style() {

		$this->start_controls_section(
			'section_messages_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Messages', 'jet-booking' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'message_typography',
				'selector' => '{{WRAPPER}} .components-notice-list .components-notice__content',
			]
		);

		$this->start_controls_tabs( 'tabs_message_style' );

		$this->start_controls_tab(
			'tab_message_success',
			[
				'label' => __( 'Success', 'jet-booking' ),
			]
		);

		$this->add_control(
			'message_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-notice-list .components-notice.is-success' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'message_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-notice-list .components-notice.is-success' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_message_error',
			[
				'label' => __( 'Error', 'jet-booking' ),
			]
		);

		$this->add_control(
			'message_color_error',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-notice-list .components-notice.is-error' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'message_bg_color_error',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .components-notice-list .components-notice.is-error' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

	}

}