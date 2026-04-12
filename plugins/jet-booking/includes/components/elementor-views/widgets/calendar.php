<?php
namespace JET_ABAF\Components\Elementor_Views\Widgets;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use \JET_ABAF\Render\Calendar as Calendar_Renderer;

defined( 'ABSPATH' ) || exit;

class Calendar extends Widget_Base {

	public function get_name() {
		return 'jet-booking-calendar';
	}

	public function get_title() {
		return __( 'Booking Availability Calendar', 'jet-booking' );
	}

	public function get_icon() {
		return 'jet-booking-icon-availability-calendar';
	}

	public function get_categories() {
		return [ 'jet-booking' ];
	}

	public function get_help_url() {
		return 'https://crocoblock.com/knowledge-base/jetbooking/booking-availability-calendar-overview/?utm_source=jetbooking&utm_medium=booking-availability-calendar&utm_campaign=need-help';
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_general',
			[
				'label' => __( 'Content', 'jet-booking' ),
			]
		);

		$this->add_control(
			'select_dates',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Allow to select dates', 'jet-booking' ),
				'description' => __( 'Find booking form on the page and set selected dates into check in/out field(s).', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->add_control(
			'scroll_to_form',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Scroll to the form', 'jet-booking' ),
				'description' => __( 'Scroll page to the start of the booking form on dates select.', 'jet-booking' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_calendar_container_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Container', 'jet-booking' ),
			]
		);

		$this->add_control(
			'gap_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Gap Line Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .gap:before' => 'border-left-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'container_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_calendar_month_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Month', 'jet-booking' ),
			]
		);

		$this->add_control(
			'table_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar .month1' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar .month2' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'table_border',
				'selector' => '{{WRAPPER}} .jet-booking-calendar .month1, {{WRAPPER}} .jet-booking-calendar .month2',
			]
		);

		$this->add_responsive_control(
			'table_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar .month1' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .jet-booking-calendar .month2' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'table_box_shadow',
				'selector' => '{{WRAPPER}} .jet-booking-calendar .month1, {{WRAPPER}} .jet-booking-calendar .month2',
			]
		);

		$this->add_responsive_control(
			'table_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar .month1' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .jet-booking-calendar .month2' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'month_names_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Headings', 'jet-booking' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'month_names_typography',
				'selector' => '{{WRAPPER}} .jet-booking-calendar thead .caption .month-name .month-element',
			]
		);

		$this->add_control(
			'month_names_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'month_names_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'month_names_border',
				'selector' => '{{WRAPPER}} .jet-booking-calendar thead .caption .month-name',
			]
		);

		$this->add_responsive_control(
			'month_names_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'month_names_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'month_switcher_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Switchers', 'jet-booking' ),
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'month_switcher_font_size',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Font Size', 'jet-booking' ),
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 30,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'font-size: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'font-size: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'month_switcher_size',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Box Size', 'jet-booking' ),
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 30,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_month_switcher_style' );

		$this->start_controls_tab(
			'month_switcher_normal',
			[
				'label' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->add_control(
			'month_switcher_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'month_switcher_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'month_switcher_hover',
			[
				'label' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->add_control(
			'month_switcher_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev:hover' => 'color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'month_switcher_bg_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev:hover' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'month_switcher_border_color_hover',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev:hover' => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next:hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'month_switcher_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'month_switcher_border',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .jet-booking-calendar thead .caption .prev, {{WRAPPER}} .jet-booking-calendar thead .caption .next',
			]
		);

		$this->add_responsive_control(
			'month_switcher_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_days_style',
			[
				'tab'   => Controls_Manager::TAB_STYLE,
				'label' => __( 'Days', 'jet-booking' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'calendar_days_typography',
				'selector' => '{{WRAPPER}} .jet-booking-calendar tbody .day',
			]
		);

		$this->add_control(
			'calendar_days_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody .day.valid' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'calendar_days_day_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody .day' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_days_style' );

		$this->start_controls_tab(
			'calendar_days_inactive',
			[
				'label' => __( 'Inactive', 'jet-booking' ),
			]
		);

		$this->add_control(
			'calendar_days_inactive_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.invalid' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'calendar_days_inactive_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.invalid' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'calendar_days_inactive_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.invalid' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'calendar_days_day_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'calendar_days_today',
			[
				'label' => __( 'Today', 'jet-booking' ),
			]
		);

		$this->add_control(
			'calendar_days_today_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody .day.real-today:not(.invalid)' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'calendar_days_today_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody .day.real-today:not(.invalid)' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'calendar_days_today_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody .day.real-today:not(.invalid)' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'calendar_days_day_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'calendar_days_edges',
			[
				'label' => __( 'Start/End', 'jet-booking' ),
			]
		);

		$this->add_control(
			'calendar_days_edges_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.first-date-selected' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.last-date-selected'  => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'calendar_days_edges_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.first-date-selected' => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.last-date-selected'  => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'calendar_days_edges_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.first-date-selected' => 'border-color: {{VALUE}} !important;',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.last-date-selected'  => 'border-color: {{VALUE}} !important;',
				],
				'condition' => [
					'calendar_days_day_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'calendar_days_trace',
			[
				'label' => __( 'Trace', 'jet-booking' ),
			]
		);

		$this->add_control(
			'calendar_days_trace_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.checked'           => 'color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.hovering'          => 'color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.has-tooltip:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'calendar_days_trace_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.checked'           => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.hovering'          => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.has-tooltip:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'calendar_days_trace_border_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Border Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.checked'           => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.hovering'          => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .jet-booking-calendar tbody div.day.has-tooltip:hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'calendar_days_day_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'calendar_days_day_border',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .jet-booking-calendar tbody .day',
			]
		);

		$this->add_responsive_control(
			'calendar_days_border_radius',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar tbody .day' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'calendar_days_day_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar tbody .day' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'cell_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Cell', 'jet-booking' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'calendar_days_cell_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar tbody td' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'calendar_days_cell_border',
				'selector' => '{{WRAPPER}} .jet-booking-calendar tbody td',
			]
		);

		$this->add_responsive_control(
			'calendar_days_cell_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar tbody td' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'week_days_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Headings', 'jet-booking' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'week_days_typography',
				'selector' => '{{WRAPPER}} .jet-booking-calendar thead .week-name th',
			]
		);

		$this->add_control(
			'week_days_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .week-name' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'week_days_bg_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'jet-booking' ),
				'selectors' => [
					'{{WRAPPER}} .jet-booking-calendar thead .week-name' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'week_days_border',
				'selector' => '{{WRAPPER}} .jet-booking-calendar thead .week-name th',
			]
		);

		$this->add_responsive_control(
			'week_days_padding',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'jet-booking' ),
				'size_units' => $this->add_custom_size_unit( [ 'px', 'em', '%' ] ),
				'selectors'  => [
					'{{WRAPPER}} .jet-booking-calendar thead .week-name th' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		$renderer = new Calendar_Renderer( $this->get_settings() );
		$renderer->render();
	}


	/**
	 * Add custom size units.
	 *
	 * Extend list of units with custom option.
	 *
	 * @since  2.6.3
	 * @access public
	 *
	 * @param array $units List of units.
	 *
	 * @return mixed
	 */
	public function add_custom_size_unit( $units ) {

		if ( version_compare( ELEMENTOR_VERSION, '3.10.0', '>=' ) ) {
			$units[] = 'custom';
		}

		return $units;

	}

}