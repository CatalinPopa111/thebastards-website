<?php
namespace JET_ABAF\Components\Blocks_Views\Block_Types;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

abstract class Base {

	/**
	 * Defines the namespace for the plugin.
	 *
	 * @var string
	 */
	protected $namespace = 'jet-booking/';

	public $block_manager = null;
	public $controls_manager = null;

	public function __construct() {

		$this->set_style_manager_instance();
		$this->add_style_manager_options();

		register_block_type_from_metadata( JET_ABAF_PATH . 'assets/js/admin/blocks-view/build/blocks/' . $this->get_name(), $this->get_args() );

	}

	/**
	 * Get name.
	 *
	 * Retrieves the name of the block.
	 *
	 * @since  4.0.0
	 * @access public
	 * @abstract
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Get arguments.
	 *
	 * Retrieves the arguments of the block.
	 *
	 * @since  4.0.0
	 * @access public
	 * @abstract
	 *
	 * @return mixed
	 */
	abstract public function get_args();

	/**
	 * Set the style manager instance.
	 *
	 * Configures and registers the style manager for the current block by utilizing
	 * the block name and sets proxy instances for block and controls management.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function set_style_manager_instance() {

		jet_abaf()->blocks_views->style_manager->register_block_support( $this->get_block_name() );

		$proxy = jet_abaf()->blocks_views->style_manager->get_proxy( $this->get_block_name() );

		$this->block_manager    = $proxy;
		$this->controls_manager = $proxy;

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
	public function add_style_manager_options() {}

	/**
	 * Get block name.
	 *
	 * Combines the namespace and the name to generate the full block name.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_block_name() {
		return $this->namespace . $this->get_name();
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

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_controls_style',
				'title' => __( 'Controls', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'      => 'heading_label',
			'type'    => 'text',
			'content' => __( 'Label', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'label_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}}[class*=jet-abaf-] .components-base-control label[class*=label]'             => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
				'{{WRAPPER}}[class*=jet-abaf-] .components-base-control .components-base-control__label' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'label_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}}[class*=jet-abaf-] .components-base-control label[class*=label]'             => 'color: {{VALUE}}',
				'{{WRAPPER}}[class*=jet-abaf-] .components-base-control .components-base-control__label' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'heading_description',
			'type'    => 'text',
			'content' => __( 'Description', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'description_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}}[class*=jet-abaf-] .components-base-control .components-base-control__help' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'description_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-base-control .components-base-control__help' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_section();

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

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_fields_style',
				'title' => __( 'Fields', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'field_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-base-control .components-text-control__input'   => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
				'{{WRAPPER}} .components-base-control .components-select-control__input' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'field_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-base-control .components-text-control__input'   => 'color: {{VALUE}}',
				'{{WRAPPER}} .components-base-control .components-select-control__input' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'field_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-base-control .components-text-control__input'   => 'background-color: {{VALUE}}',
				'{{WRAPPER}} .components-base-control .components-select-control__input' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'field_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'hide_radius'  => true,
			'css_selector' => [
				'{{WRAPPER}} .components-base-control .components-text-control__input'     => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
				'{{WRAPPER}} .components-base-control .components-input-control__backdrop' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'field_border_radius',
			'type'         => 'dimensions',
			'label'        => __( 'Border Radius', 'jet-booking' ),
			'units'        => [ 'px', '%', 'em', 'rem' ],
			'css_selector' => [
				'{{WRAPPER}} .components-base-control .components-text-control__input'     => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
				'{{WRAPPER}} .components-base-control .components-select-control__input'   => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
				'{{WRAPPER}} .components-base-control .components-input-control__backdrop' => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
			],
		] );

		$this->controls_manager->end_section();

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

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_toggle_style',
				'title' => __( 'Toggle', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'toggle_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-form-toggle:not(.is-checked) .components-form-toggle__track' => 'border-color: {{VALUE}}',
				'{{WRAPPER}} .components-form-toggle:not(.is-checked) .components-form-toggle__thumb' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'toggle_color_active',
			'type'         => 'color-picker',
			'label'        => __( 'Active Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-form-toggle.is-checked .components-form-toggle__track' => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
				'{{WRAPPER}} .components-form-toggle.is-checked .components-form-toggle__thumb' => 'background-color: #fff',
			],
		] );

		$this->controls_manager->end_section();

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

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_button_style',
				'title' => __( 'Save Button', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'button_position',
			'type'         => 'choose',
			'label'        => __( 'Position', 'jet-booking' ),
			'options'      => [
				'start'  => [
					'label' => __( 'Start', 'jet-booking' ),
					'icon'  => ! is_rtl() ? 'dashicons-editor-alignleft' : 'dashicons-editor-alignright',
				],
				'center' => [
					'label' => __( 'Center', 'jet-booking' ),
					'icon'  => 'dashicons-editor-aligncenter',
				],
				'end'    => [
					'label' => __( 'End', 'jet-booking' ),
					'icon'  => ! is_rtl() ? 'dashicons-editor-alignright' : 'dashicons-editor-alignleft',
				],
			],
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control' => 'justify-content: {{VALUE}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'button_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'button_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			],
		] );

		$this->controls_manager->start_tabs(
			'style_controls',
			[
				'id'        => 'tabs_button_style',
				'separator' => 'both',
			]
		);

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_button_normal',
				'title' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'button_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'button_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_button_hover',
				'title' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'button_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button:hover:not(:disabled)' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'button_bg_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button:hover:not(:disabled)' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'button_border_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button:hover:not(:disabled)' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->end_tabs();

		$this->controls_manager->add_control( [
			'id'           => 'button_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', '%', 'em', 'rem' ],
			'css_selector' => [
				'{{WRAPPER}} .components-actions-control .components-button' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
			],
		] );

		$this->controls_manager->end_section();

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

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_messages_style',
				'title' => __( 'Messages', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'message_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .components-notice-list .components-notice__content' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->start_tabs(
			'style_controls',
			[
				'id'        => 'tabs_message_style',
				'separator' => 'both',
			]
		);

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_message_success',
				'title' => __( 'Success', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'message_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-notice-list .components-notice.is-success' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'message_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-notice-list .components-notice.is-success' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'tab_message_error',
				'title' => __( 'Error', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'message_color_error',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-notice-list .components-notice.is-error' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'message_bg_color_error',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .components-notice-list .components-notice.is-error' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->end_tabs();

		$this->controls_manager->end_section();

	}

}
