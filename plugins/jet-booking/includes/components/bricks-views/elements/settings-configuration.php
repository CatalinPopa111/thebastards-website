<?php
namespace JET_ABAF\Components\Bricks_Views\Elements;

use Bricks\Element;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Settings_Configuration extends Element {

	// Element properties
	public $category     = 'jetbooking';
	public $name         = 'jet-booking-settings-configuration';
	public $icon         = 'jet-booking-icon-settings-configurations';
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
		return __( 'Settings Configuration', 'jet-booking' );
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

		$this->controls['show_period_controls'] = [
			'tab'         => 'content',
			'group'       => 'controls_section',
			'type'        => 'checkbox',
			'label'       => __( 'Booking Period', 'jet-booking' ),
			'description' => __( 'If this option is checked, the booking period controls will show.', 'jet-booking' ),
			'default'     => true,
		];

		$this->controls['show_range_controls'] = [
			'tab'         => 'content',
			'group'       => 'controls_section',
			'type'        => 'checkbox',
			'label'       => __( 'Date Range', 'jet-booking' ),
			'description' => __( 'If this option is checked, the date range controls will show.', 'jet-booking' ),
			'default'     => true,
		];

		$this->controls['show_ui_controls'] = [
			'tab'         => 'content',
			'group'       => 'controls_section',
			'type'        => 'checkbox',
			'label'       => __( 'Calendar UI', 'jet-booking' ),
			'description' => __( 'If this option is checked, the calendar UI controls will show.', 'jet-booking' ),
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
			'label' => __( 'Active Color', 'bricks' ),
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

		// Button styles section start.
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
		// Button styles section end.

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
			'show_period_controls' => $this->settings['show_period_controls'] ?? false,
			'show_range_controls'  => $this->settings['show_range_controls'] ?? false,
			'show_ui_controls'     => $this->settings['show_ui_controls'] ?? false,
		];

		$this->set_attribute( '_root', 'class', 'jet-abaf-settings-configuration-root' );
		$this->set_attribute( '_root', 'data-settings', json_encode( $data ) );

		echo "<div {$this->render_attributes( '_root' )}></div>"; // phpcs:ignore

	}

}
