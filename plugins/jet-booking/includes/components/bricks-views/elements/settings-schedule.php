<?php
namespace JET_ABAF\Components\Bricks_Views\Elements;

use Bricks\Element;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Settings_Schedule extends Element {

	// Element properties
	public $category     = 'jetbooking';
	public $name         = 'jet-booking-settings-schedule';
	public $icon         = 'jet-booking-icon-settings-schedule';
	public $scripts      = [ 'jetBookingWidgetsInit' ];

	/**
	 * Get label.
	 *
	 * Return localised element label.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Settings Schedule', 'jet-booking' );
	}

	/**
	 * Set Control Groups.
	 *
	 * Define and register control groups with their respective properties.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function set_control_groups() {

		$this->control_groups['controls_section'] = [
			'title' => __( 'Controls', 'jet-booking' ),
			'tab'   => 'content',
		];

		$this->control_groups['style_controls_section'] = [
			'title' => __( 'Controls', 'jet-booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['style_fields_section'] = [
			'title' => __( 'Fields', 'jet-booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['style_toggle_section'] = [
			'title' => __( 'Toggle', 'jet-booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['style_table_section'] = [
			'title' => __( 'Table', 'jet-booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['style_panel_section'] = [
			'title' => __( 'Panel', 'jet-booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['style_card_section'] = [
			'title' => __( 'Card', 'jet-booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['style_button_section'] = [
			'title' => __( 'Save Button', 'jet-booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['style_messages_section'] = [
			'title' => __( 'Messages', 'jet-booking' ),
			'tab'   => 'style',
		];

	}

	/**
	 * Set controls.
	 *
	 * Define element controls.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function set_controls() {

		$this->controls['show_timepicker_controls'] = [
			'tab'         => 'content',
			'group'       => 'controls_section',
			'type'        => 'checkbox',
			'label'       => __( 'Timepicker', 'jet-booking' ),
			'description' => __( 'If this option is checked, the timepicker controls will show.', 'jet-booking' ),
			'default'     => true,
		];

		$this->controls['show_rules_controls'] = [
			'tab'         => 'content',
			'group'       => 'controls_section',
			'type'        => 'checkbox',
			'label'       => __( 'Booking Rules', 'jet-booking' ),
			'description' => __( 'If this option is checked, the booking rules controls will show.', 'jet-booking' ),
			'default'     => true,
		];

		$this->controls['show_days_off_controls'] = [
			'tab'         => 'content',
			'group'       => 'controls_section',
			'type'        => 'checkbox',
			'label'       => __( 'Days Off', 'jet-booking' ),
			'description' => __( 'If this option is checked, the days off controls will show.', 'jet-booking' ),
			'default'     => true,
		];

		// Controls styles section start.
		$this->controls['heading_label'] = [
			'tab'   => 'style',
			'group' => 'style_controls_section',
			'label' => __( 'Label', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['label_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_controls_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-base-control label',
				],
				[
					'property' => 'typography',
					'selector' => '.components-base-control .components-base-control__label',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['heading_description'] = [
			'tab'   => 'style',
			'group' => 'style_controls_section',
			'label' => __( 'Description', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['description_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_controls_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-base-control .components-base-control__help',
				],
			],
			'exclude' => [ 'text-align' ],
		];
		// Controls styles section end.

		// Fields styles section start.
		$this->controls['field_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_fields_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-base-control .components-text-control__input',
				],
				[
					'property' => 'typography',
					'selector' => '.components-base-control .components-select-control__input',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['field_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_fields_section',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-base-control .components-text-control__input',
				],
				[
					'property' => 'background-color',
					'selector' => '.components-base-control .components-select-control__input',
				],
			],
		];

		$this->controls['field_border'] = [
			'tab'   => 'style',
			'group' => 'style_fields_section',
			'label' => __( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.components-base-control .components-text-control__input',
				],
				[
					'property' => 'border',
					'selector' => '.components-base-control .components-input-control__backdrop',
				],
				[
					'property' => 'border-radius',
					'selector' => '.components-base-control .components-select-control__input',
				],
				[
					'property' => 'border',
					'selector' => '.components-base-control .components-select-control__input',
					'value'    => 'none'
				],
			],
		];
		// Fields styles section end.

		// Toggle styles section start.
		$this->controls['toggle_color'] = [
			'tab'   => 'style',
			'group' => 'style_toggle_section',
			'label' => __( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.components-form-toggle:not(.is-checked) .components-form-toggle__track',
				],
				[
					'property' => 'background-color',
					'selector' => '.components-form-toggle:not(.is-checked) .components-form-toggle__thumb',
				]
			],
		];

		$this->controls['toggle_color_active'] = [
			'tab'   => 'style',
			'group' => 'style_toggle_section',
			'label' => __( 'Active color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.components-form-toggle.is-checked .components-form-toggle__track',
				],
				[
					'property' => 'background-color',
					'selector' => '.components-form-toggle.is-checked .components-form-toggle__track',
				],
				[
					'property' => 'background-color',
					'selector' => '.components-form-toggle.is-checked .components-form-toggle__thumb',
					'value'    => '#fff'
				]
			],
		];
		// Toggle styles section end.

		// Table styles section start.
		$this->controls['table_color'] = [
			'tab'   => 'style',
			'group' => 'style_table_section',
			'label' => __( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.components-booking-rules-control .components-grid',
				],
				[
					'property' => 'fill',
					'selector' => '.components-booking-rules-control .components-grid svg',
				]
			],
		];

		$this->controls['table_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_table_section',
			'label' => __( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-booking-rules-control .components-grid',
				],
			],
		];

		$this->controls['table_even_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_table_section',
			'label' => __( 'Even background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-booking-rules-control .components-grid:nth-child( even )',
				],
			],
		];

		$this->controls['heading_table_headings'] = [
			'tab'   => 'style',
			'group' => 'style_table_section',
			'label' => __( 'Headings', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['table_headings_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_table_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-booking-rules-control .components-item',
				],
			],
			'exclude' => [ 'text-align' ],
		];
		// Table styles section end.

		// Panel styles section start.
		$this->controls['heading_panel_title'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Title', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['panel_title_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_panel_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-days-off-control .components-panel__body-toggle',
				],
			],
			'exclude' => [ 'color', 'text-align' ],
		];

		$this->controls['panel_title_color'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.components-days-off-control .components-panel__body-toggle',
				],
				[
					'property' => 'fill',
					'selector' => '.components-days-off-control .components-panel__body-toggle svg',
				],
			],
		];

		$this->controls['heading_panel_buttons'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Buttons', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['panel_buttons_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_panel_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-days-off-control__add-days-actions .components-button',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['panel_buttons_border'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.components-days-off-control__add-days-actions .components-button',
				],
			],
		];

		$this->controls['heading_panel_confirm_button'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Confirm Button', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['confirm_panel_button_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-days-off-control__add-days-actions .components-button.is-primary',
				],
			],
		];

		$this->controls['heading_panel_cancel_button'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Cancel Button', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['cancel_panel_button_color'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.components-days-off-control__add-days-actions .components-button.is-secondary',
				],
			],
		];

		$this->controls['cancel_panel_button_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-days-off-control__add-days-actions .components-button.is-secondary',
				],
			],
		];

		$this->controls['cancel_panel_button_border_color'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Border color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.components-days-off-control__add-days-actions .components-button.is-secondary',
				],
			],
		];

		$this->controls['panel_buttons_padding'] = [
			'tab'   => 'style',
			'group' => 'style_panel_section',
			'label' => __( 'Padding', 'bricks' ),
			'type'  => 'dimensions',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.components-days-off-control__add-days-actions .components-button',
				]
			],
		];
		// Panel styles section end.

		// Card styles section start.
		$this->controls['heading_card_header'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Header', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['card_header_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_card_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-days-off-control__days-list .components-card__header .components-text',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['heading_card_footer'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Footer', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['card_footer_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_card_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-days-off-control__days-list .components-card__footer .components-text',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['heading_card_buttons'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Buttons', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['card_buttons_icon_size'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Icon size', 'bricks' ),
			'type'  => 'slider',
			'css'   => [
				[
					'property' => 'height',
					'selector' => '.components-days-off-control__days-list .components-button svg',
				],
				[
					'property' => 'width',
					'selector' => '.components-days-off-control__days-list .components-button svg',
				],
			],
			'units' => [
				'px' => [
					'min'  => 1,
					'max'  => 50,
					'step' => 1,
				],
				'em' => [
					'min'  => 1,
					'max'  => 20,
					'step' => 0.1,
				],
			],
		];

		$this->controls['card_buttons_border'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.components-days-off-control__days-list .components-button',
				],
			],
		];

		$this->controls['heading_edit_confirm_card_button'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Edit/Confirm Button', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['edit_confirm_card_button_color'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.components-days-off-control__days-list .components-button:not(.is-destructive)',
				],
			],
		];

		$this->controls['edit_confirm_card_button_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-days-off-control__days-list .components-button:not(.is-destructive)',
				],
			],
		];

		$this->controls['heading_delete_cancel_card_button'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Delete/Cancel Button', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['delete_cancel_card_button_color'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.components-days-off-control__days-list .components-button.is-destructive',
				],
			],
		];

		$this->controls['delete_cancel_card_button_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-days-off-control__days-list .components-button.is-destructive',
				],
			],
		];

		$this->controls['delete_cancel_card_button_border_color'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Border color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-color',
					'selector' => '.components-days-off-control__days-list .components-button.is-destructive',
				],
			],
		];

		$this->controls['card_buttons_padding'] = [
			'tab'   => 'style',
			'group' => 'style_card_section',
			'label' => __( 'Padding', 'bricks' ),
			'type'  => 'dimensions',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.components-days-off-control__days-list .components-button',
				]
			],
		];
		// Card styles section end.

		// Button styles section start.
		$this->controls['button_position'] = [
			'tab'     => 'style',
			'group'   => 'style_button_section',
			'label'   => __( 'Position', 'jet-booking' ),
			'type'    => 'justify-content',
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.components-actions-control',
				],
			],
			'exclude' => 'space',
		];

		$this->controls['button_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_button_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-actions-control .components-button',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['button_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_button_section',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-actions-control .components-button',
				],
			],
		];

		$this->controls['button_border'] = [
			'tab'   => 'style',
			'group' => 'style_button_section',
			'label' => __( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.components-actions-control .components-button',
				],
			],
		];

		$this->controls['button_padding'] = [
			'tab'   => 'style',
			'group' => 'style_button_section',
			'label' => __( 'Padding', 'bricks' ),
			'type'  => 'dimensions',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.components-actions-control .components-button',
				]
			],
		];
		// Button styles section end.

		// Messages styles section start.
		$this->controls['message_typography'] = [
			'tab'     => 'style',
			'group'   => 'style_messages_section',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.components-notice-list .components-notice__content',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['heading_success'] = [
			'tab'   => 'style',
			'group' => 'style_messages_section',
			'label' => __( 'Success', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['message_color'] = [
			'tab'   => 'style',
			'group' => 'style_messages_section',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.components-notice-list .components-notice.is-success',
				],
			],
		];

		$this->controls['message_bg_color'] = [
			'tab'   => 'style',
			'group' => 'style_messages_section',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-notice-list .components-notice.is-success',
				],
			],
		];

		$this->controls['heading_error'] = [
			'tab'   => 'style',
			'group' => 'style_messages_section',
			'label' => __( 'Error', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['message_color_error'] = [
			'tab'   => 'style',
			'group' => 'style_messages_section',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.components-notice-list .components-notice.is-error',
				],
			],
		];

		$this->controls['message_bg_color_error'] = [
			'tab'   => 'style',
			'group' => 'style_messages_section',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.components-notice-list .components-notice.is-error',
				],
			],
		];
		// Messages styles section end.

	}

	/**
	 * Enqueue scripts.
	 *
	 * Load element-specific scripts and styles. Those are loaded only on pages where this element is used.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'jet-booking-widgets' );

		wp_enqueue_style( 'jet-booking-widgets' );
		wp_enqueue_style( 'wp-components' );

	}

	/**
	 * Render.
	 *
	 * Render the element output on the frontend.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function render() {

		$data = [
			'show_timepicker_controls' => $this->settings['show_timepicker_controls'] ?? false,
			'show_rules_controls'      => $this->settings['show_rules_controls'] ?? false,
			'show_days_off_controls'   => $this->settings['show_days_off_controls'] ?? false,
		];

		$this->set_attribute( '_root', 'class', 'jet-abaf-settings-schedule-root' );
		$this->set_attribute( '_root', 'data-settings', json_encode( $data ) );

		echo "<div {$this->render_attributes( '_root' )}></div>"; // phpcs:ignore

	}

}
