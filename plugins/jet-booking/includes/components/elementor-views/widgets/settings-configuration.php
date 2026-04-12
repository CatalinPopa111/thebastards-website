<?php
namespace JET_ABAF\Components\Elementor_Views\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Settings_Configuration extends Settings_Widget_Base {

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
		return 'settings-configuration';
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
		return __( 'Settings Configuration', 'jet-booking' );
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
		return 'jet-booking-icon-settings-configurations';
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
		return 'https://crocoblock.com/knowledge-base/jetbooking/settings-configurations-widget-overview/?utm_source=jetbooking&utm_medium=configuration-widget&utm_campaign=need-help';
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
				'tab'   => Controls_Manager::TAB_CONTENT,
				'label' => __( 'Controls', 'jet-booking' ),
			]
		);

		$this->add_control(
			'show_period_controls',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Booking Period', 'jet-booking' ),
				'description' => __( 'If this option is checked, the booking period controls will show.', 'jet-booking' ),
				'label_on'    => __( 'Show', 'jet-booking' ),
				'label_off'   => __( 'Hide', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->add_control(
			'show_range_controls',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Date Range', 'jet-booking' ),
				'description' => __( 'If this option is checked, the date range controls will show.', 'jet-booking' ),
				'label_on'    => __( 'Show', 'jet-booking' ),
				'label_off'   => __( 'Hide', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->add_control(
			'show_ui_controls',
			[
				'type'        => Controls_Manager::SWITCHER,
				'label'       => __( 'Calendar UI', 'jet-booking' ),
				'description' => __( 'If this option is checked, the calendar UI controls will show.', 'jet-booking' ),
				'label_on'    => __( 'Show', 'jet-booking' ),
				'label_off'   => __( 'Hide', 'jet-booking' ),
				'default'     => 'yes',
			]
		);

		$this->end_controls_section();

		$this->get_section_controls_style();

		$this->get_section_fields_style();

		$this->get_section_toggle_style();

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
			'show_period_controls' => filter_var( $settings['show_period_controls'], FILTER_VALIDATE_BOOLEAN ),
			'show_range_controls'  => filter_var( $settings['show_range_controls'], FILTER_VALIDATE_BOOLEAN ),
			'show_ui_controls'     => filter_var( $settings['show_ui_controls'], FILTER_VALIDATE_BOOLEAN ),
		];

		echo '<div class="jet-abaf-settings-configuration-root" data-settings="' . esc_attr( json_encode( $data ) ) . '"></div>';

	}

}
