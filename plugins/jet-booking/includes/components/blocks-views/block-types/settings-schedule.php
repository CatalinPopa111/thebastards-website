<?php
namespace JET_ABAF\Components\Blocks_Views\Block_Types;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Settings_Schedule extends Base {

	/**
	 * Get name.
	 *
	 * Retrieves the name of the block.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_name() {
		return 'settings-schedule';
	}

	/**
	 * Get arguments.
	 *
	 * Retrieves the arguments of the block.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function get_args() {
		return [];
	}

	/**
	 * Add style manager options.
	 *
	 * Handles the addition of options specific to the style manager.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_style_manager_options() {

		$this->get_section_controls_style();

		$this->get_section_fields_style();

		$this->get_section_toggle_style();

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_table_style',
				'title' => __( 'Table', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'table_headings_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-booking-rules-control .components-item' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'table_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-booking-rules-control .components-grid .components-item'     => 'color: {{VALUE}}',
				'{{WRAPPER}} .components-booking-rules-control .components-grid .components-item svg' => 'fill: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'table_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-booking-rules-control .components-grid' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'table_even_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}}[class*=jet-abaf-] .components-booking-rules-control .components-grid:nth-child( even )' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_section();

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_panel_style',
				'title' => __( 'Panel', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'      => 'heading_panel_title',
			'type'    => 'text',
			'content' => __( 'Title', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'panel_title_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control .components-panel__body-toggle' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'panel_title_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}}[class*=jet-abaf-] .components-days-off-control .components-panel__body-toggle' => 'color: {{VALUE}}',
				'{{WRAPPER}} .components-days-off-control .components-panel__body-toggle svg'               => 'fill: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_panel_buttons',
			'type'    => 'text',
			'content' => __( 'Buttons', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'panel_buttons_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'panel_buttons_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			],
		] );

		$this->controls_manager->start_tabs(
			'style_controls',
			[
				'id'        => 'tabs_panel_buttons_style',
				'separator' => 'both',
			]
		);

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_panel_buttons_normal',
				'title' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'      => 'heading_confirm_panel_button',
			'type'    => 'text',
			'content' => __( 'Confirm Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'confirm_panel_button_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'confirm_panel_button_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_cancel_panel_button',
			'type'    => 'text',
			'content' => __( 'Cancel Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'cancel_panel_button_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'cancel_panel_button_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'cancel_panel_button_border_color',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_panel_buttons_hover',
				'title' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'      => 'heading_confirm_panel_button_hover',
			'type'    => 'text',
			'content' => __( 'Confirm Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'confirm_panel_button_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary:hover' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'confirm_panel_button_bg_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary:hover' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'confirm_panel_button_border_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-primary:hover' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_cancel_panel_button_hover',
			'type'    => 'text',
			'content' => __( 'Cancel Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'cancel_panel_button_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary:hover' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'cancel_panel_button_bg_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary:hover' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'cancel_panel_button_border_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button.is-secondary:hover' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->end_tabs();

		$this->controls_manager->add_control( [
			'id'           => 'panel_buttons_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', '%', 'em', 'rem' ],
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__add-days-actions .components-button' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
			],
		] );

		$this->controls_manager->end_section();

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_card_style',
				'title' => __( 'Card', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'      => 'heading_card_header',
			'type'    => 'text',
			'content' => __( 'Header', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'card_header_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-card__header .components-text' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'card_header_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-card__header .components-text' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_card_footer',
			'type'    => 'text',
			'content' => __( 'Footer', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'card_footer_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-card__footer .components-text' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'card_footer_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-card__footer .components-text' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_card_buttons',
			'type'    => 'text',
			'content' => __( 'Buttons', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'card_buttons_icon_size',
			'type'         => 'range',
			'label'        => __( 'Icons Size', 'jet-booking' ),
			'units'        => [
				[
					'value'     => 'px',
					'intervals' => [
						'step' => 1,
						'min'  => 0,
						'max'  => 100,
					]
				],
				[
					'value'     => 'em',
					'intervals' => [
						'step' => 1,
						'min'  => 0,
						'max'  => 60,
					]
				],
				[
					'value'     => '%',
					'intervals' => [
						'step' => 1,
						'min'  => 0,
						'max'  => 60,
					]
				],
			],
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button svg' => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'card_buttons_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			],
		] );

		$this->controls_manager->start_tabs(
			'style_controls',
			[
				'id'        => 'tabs_card_buttons_style',
				'separator' => 'both',
			]
		);

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_card_buttons_normal',
				'title' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'      => 'heading_edit_confirm_card_button',
			'type'    => 'text',
			'content' => __( 'Edit/Confirm Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'edit_confirm_card_button_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive) svg' => 'fill: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'edit_confirm_card_button_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive)' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_delete_cancel_card_button',
			'type'    => 'text',
			'content' => __( 'Delete/Cancel Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'delete_cancel_card_button_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive svg' => 'fill: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'delete_cancel_card_button_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'delete_cancel_card_button_border_color',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_card_buttons_hover',
				'title' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'      => 'heading_edit_confirm_card_button_hover',
			'type'    => 'text',
			'content' => __( 'Edit/Confirm Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'edit_confirm_card_button_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive):hover svg' => 'fill: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'edit_confirm_card_button_bg_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive):hover' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'edit_confirm_card_button_border_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button:not(.is-destructive):hover' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_delete_cancel_card_button_hover',
			'type'    => 'text',
			'content' => __( 'Delete/Cancel Button', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'delete_cancel_card_button_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive:hover svg' => 'fill: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'delete_cancel_card_button_bg_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}}[class*=jet-abaf-] .components-days-off-control__days-list .components-button.is-destructive:hover' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'delete_cancel_card_button_border_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-days-off-control__days-list .components-button.is-destructive:hover' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->end_tabs();

		$this->controls_manager->add_control( [
			'id'           => 'card_buttons_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', '%', 'em', 'rem' ],
			'css_selector' => [
				'{{WRAPPER}}[class*=jet-abaf-] .components-days-off-control__days-list .components-button.is-compact' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
			],
		] );

		$this->controls_manager->end_section();

		$this->get_section_button_style();

		$this->get_section_messages_style();

	}


}
