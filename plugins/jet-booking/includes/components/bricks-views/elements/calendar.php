<?php

namespace JET_ABAF\Components\Bricks_Views\Elements;

use \Bricks\Element;
use \JET_ABAF\Render\Calendar as Calendar_Renderer;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Calendar extends Element {

	// Element properties
	public $category = 'jetbooking';
	public $name = 'jet-booking-calendar';
	public $icon = 'jet-booking-icon-availability-calendar';
	public $css_selector = '';
	public $scripts = [ 'jetBookingBricks' ];
	public $nestable = false;

	public function get_label() {
		return __( 'Booking Availability Calendar', 'jet-booking' );
	}

	public function set_control_groups() {

		$this->control_groups['section_container_styles'] = [
			'title' => __( 'Calendar Container', 'jet_booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['section_month_styles'] = [
			'title' => __( 'Calendar Months', 'jet_booking' ),
			'tab'   => 'style',
		];

		$this->control_groups['section_days_styles'] = [
			'title' => __( 'Calendar Days', 'jet_booking' ),
			'tab'   => 'style',
		];

	}

	public function set_controls() {

		$this->controls['select_dates'] = [
			'tab'         => 'content',
			'label'       => __( 'Allow to select dates', 'jet-booking' ),
			'description' => __( 'Find booking form on the page and set selected dates into check in/out field(s).', 'jet-booking' ),
			'type'        => 'checkbox',
			'default'     => true,
		];

		$this->controls['scroll_to_form'] = [
			'tab'         => 'content',
			'label'       => __( 'Scroll to the form', 'jet-booking' ),
			'description' => __( 'Scroll page to the start of the booking form on dates select.', 'jet-booking' ),
			'type'        => 'checkbox',
		];

		// Start of calendar container styles section.
		$this->controls['month_gap_color'] = [
			'tab'   => 'style',
			'group' => 'section_container_styles',
			'label' => __( 'Gap line color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-left-color',
					'selector' => '.jet-booking-calendar .date-picker-wrapper .gap:before',
				]
			],
		];

		$this->controls['container_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_container_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar .date-picker-wrapper',
				]
			],
		];

		$this->controls['container_padding'] = [
			'tab'   => 'style',
			'group' => 'section_container_styles',
			'label' => __( 'Padding', 'jet-booking' ),
			'type'  => 'dimensions',
			'units' => true,
			'css'   => [
				[
					'property'  => 'padding',
					'selector'  => '.jet-booking-calendar .date-picker-wrapper',
					'important' => true,
				]
			]
		];
		// End of calendar container styles section.

		// Start of calendar month styles section.
		$this->controls['month_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar .month1',
				],
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar .month2',
				]
			],
		];

		$this->controls['month_border'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Border', 'jet-booking' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar .month1',
				],
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar .month2',
				]
			],
		];

		$this->controls['month_box_shadow'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Box shadow', 'jet-booking' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.jet-booking-calendar .month1',
				],
				[
					'property' => 'box-shadow',
					'selector' => '.jet-booking-calendar .month2',
				]
			]
		];

		$this->controls['month_padding'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Padding', 'jet-booking' ),
			'type'  => 'dimensions',
			'units' => true,
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.jet-booking-calendar .month1',
				],
				[
					'property' => 'padding',
					'selector' => '.jet-booking-calendar .month2',
				]
			]
		];

		$this->controls['month_names_heading'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Headings', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['month_names_typography'] = [
			'tab'     => 'style',
			'group'   => 'section_month_styles',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.jet-booking-calendar thead .caption .month-name .month-element',
				],
			],
			'exclude' => [
				'text-align'
			]
		];

		$this->controls['month_names_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar thead .caption .month-name',
				]
			],
		];

		$this->controls['month_names_border'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Border', 'jet-booking' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar thead .caption .month-name',
				]
			],
		];

		$this->controls['month_names_padding'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Padding', 'jet-booking' ),
			'type'  => 'dimensions',
			'units' => true,
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.jet-booking-calendar thead .caption .month-name',
				]
			]
		];

		$this->controls['month_switcher_heading'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Switchers', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['month_switcher_font_size'] = [
			'tab'     => 'style',
			'group'   => 'section_month_styles',
			'label'   => __( 'Font size', 'jet-booking' ),
			'type'    => 'slider',
			'units'   => [
				'px' => [
					'min'  => 1,
					'max'  => 200,
					'step' => 1,
				],
			],
			'default' => '12px',
			'css'     => [
				[
					'property' => 'font-size',
					'selector' => '.jet-booking-calendar thead .caption .prev',
				],
				[
					'property' => 'font-size',
					'selector' => '.jet-booking-calendar thead .caption .next',
				]
			]
		];

		$this->controls['month_switcher_box_size'] = [
			'tab'     => 'style',
			'group'   => 'section_month_styles',
			'label'   => __( 'Box size', 'jet-booking' ),
			'type'    => 'slider',
			'units'   => [
				'px' => [
					'min'  => 1,
					'max'  => 200,
					'step' => 1,
				],
			],
			'default' => '12px',
			'css'     => [
				[
					'property' => 'width',
					'selector' => '.jet-booking-calendar thead .caption .prev',
				],
				[
					'property' => 'height',
					'selector' => '.jet-booking-calendar thead .caption .prev',
				],
				[
					'property' => 'width',
					'selector' => '.jet-booking-calendar thead .caption .next',
				],
				[
					'property' => 'height',
					'selector' => '.jet-booking-calendar thead .caption .next',
				]
			]
		];

		$this->controls['month_switcher_color'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.jet-booking-calendar thead .caption .prev',
				],
				[
					'property' => 'color',
					'selector' => '.jet-booking-calendar thead .caption .next',
				]
			],
		];

		$this->controls['month_switcher_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar thead .caption .prev',
				],
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar thead .caption .next',
				]
			],
		];

		$this->controls['month_switcher_border'] = [
			'tab'   => 'style',
			'group' => 'section_month_styles',
			'label' => __( 'Border', 'jet-booking' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar thead .caption .prev',
				],
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar thead .caption .next',
				]
			],
		];
		// End of calendar month styles section.

		// Start of calendar days styles section.
		$this->controls['calendar_days_typography'] = [
			'tab'     => 'style',
			'group'   => 'section_days_styles',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.jet-booking-calendar tbody .day',
				],
			],
			'exclude' => [
				'text-align'
			]
		];

		$this->controls['calendar_days_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar tbody .day',
				]
			],
		];

		$this->controls['calendar_days_border'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Border', 'jet-booking' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar tbody .day',
				]
			],
		];

		$this->controls['calendar_days_padding'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Padding', 'jet-booking' ),
			'type'  => 'dimensions',
			'units' => true,
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.jet-booking-calendar tbody .day',
				]
			]
		];

		$this->controls['calendar_days_inactive_heading'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Inactive', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['calendar_days_inactive_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.jet-booking-calendar div.day.invalid',
				]
			],
		];

		$this->controls['calendar_days_inactive_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar div.day.invalid',
				]
			],
		];

		$this->controls['calendar_days_inactive_border_color'] = [
			'tab'      => 'style',
			'group'    => 'section_days_styles',
			'label'    => __( 'Border color', 'jet-booking' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'border-color',
					'selector' => '.jet-booking-calendar div.day.invalid',
				]
			],
			'required' => [
				[ 'calendar_days_day_border', '!=', '' ],
			],
		];

		$this->controls['calendar_days_today_heading'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Today', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['calendar_days_today_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.jet-booking-calendar tbody .day.real-today:not(.invalid)',
				]
			],
		];

		$this->controls['calendar_days_today_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar tbody .day.real-today:not(.invalid)',
				]
			],
		];

		$this->controls['calendar_days_today_border_color'] = [
			'tab'      => 'style',
			'group'    => 'section_days_styles',
			'label'    => __( 'Border color', 'jet-booking' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'border-color',
					'selector' => '.jet-booking-calendar tbody .day.real-today:not(.invalid)',
				]
			],
			'required' => [
				[ 'calendar_days_day_border', '!=', '' ],
			],
		];

		$this->controls['calendar_days_edges_heading'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Start/End', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['calendar_days_edges_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property'  => 'color',
					'selector'  => '.jet-booking-calendar div.day.first-date-selected',
					'important' => true,
				],
				[
					'property'  => 'color',
					'selector'  => '.jet-booking-calendar div.day.last-date-selected',
					'important' => true,
				]
			],
		];

		$this->controls['calendar_days_edges_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property'  => 'background-color',
					'selector'  => '.jet-booking-calendar div.day.first-date-selected',
					'important' => true,
				],
				[
					'property'  => 'background-color',
					'selector'  => '.jet-booking-calendar div.day.last-date-selected',
					'important' => true,
				]
			],
		];

		$this->controls['calendar_days_edges_border_color'] = [
			'tab'      => 'style',
			'group'    => 'section_days_styles',
			'label'    => __( 'Border color', 'jet-booking' ),
			'type'     => 'color',
			'css'      => [
				[
					'property'  => 'border-color',
					'selector'  => '.jet-booking-calendar div.day.first-date-selected',
					'important' => true,
				],
				[
					'property'  => 'border-color',
					'selector'  => '.jet-booking-calendar div.day.last-date-selected',
					'important' => true,
				]
			],
			'required' => [
				[ 'calendar_days_day_border', '!=', '' ],
			],
		];

		$this->controls['calendar_days_trace_heading'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Trace', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['calendar_days_trace_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.jet-booking-calendar tbody div.day.checked',
				],
				[
					'property' => 'color',
					'selector' => '.jet-booking-calendar tbody div.day.hovering',
				],
				[
					'property' => 'color',
					'selector' => '.jet-booking-calendar tbody div.day.has-tooltip:hover',
				]
			],
		];

		$this->controls['calendar_days_trace_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar tbody div.day.checked',
				],
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar tbody div.day.hovering',
				],
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar tbody div.day.hovering.has-tooltip:hover',
				]
			]
		];

		$this->controls['calendar_days_trace_border_color'] = [
			'tab'      => 'style',
			'group'    => 'section_days_styles',
			'label'    => __( 'Border color', 'jet-booking' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'border-color',
					'selector' => '.jet-booking-calendar tbody div.day.checked',
				],
				[
					'property' => 'border-color',
					'selector' => '.jet-booking-calendar tbody div.day.hovering',
				],
				[
					'property' => 'border-color',
					'selector' => '.jet-booking-calendar tbody div.day.hovering.has-tooltip:hover',
				]
			],
			'required' => [
				[ 'calendar_days_day_border', '!=', '' ],
			],
		];

		$this->controls['calendar_cell_heading'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Cells', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['calendar_cell_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar tbody td',
				]
			]
		];

		$this->controls['calendar_cell_border'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Border', 'jet-booking' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar tbody td',
				]
			],
		];

		$this->controls['calendar_cell_padding'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Padding', 'jet-booking' ),
			'type'  => 'dimensions',
			'units' => true,
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.jet-booking-calendar tbody td',
				]
			]
		];

		$this->controls['calendar_days_heading'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Headings', 'jet-booking' ),
			'type'  => 'separator',
		];

		$this->controls['week_days_typography'] = [
			'tab'     => 'style',
			'group'   => 'section_days_styles',
			'label'   => __( 'Typography', 'jet-booking' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'typography',
					'selector' => '.jet-booking-calendar thead .week-name',
				],
			],
			'exclude' => [
				'text-align'
			]
		];

		$this->controls['week_days_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.jet-booking-calendar thead .week-name',
				]
			]
		];

		$this->controls['week_days_border'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Border', 'jet-booking' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.jet-booking-calendar thead .week-name th',
				]
			],
		];

		$this->controls['week_days_padding'] = [
			'tab'   => 'style',
			'group' => 'section_days_styles',
			'label' => __( 'Padding', 'jet-booking' ),
			'type'  => 'dimensions',
			'units' => true,
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.jet-booking-calendar thead .week-name th',
				]
			]
		];
		// End of calendar days styles section.

	}

	public function enqueue_scripts() {
		jet_abaf()->assets->enqueue_deps( get_the_ID() );
	}

	public function render() {

		$this->set_attribute( '_root', 'data-is-block', 'jet-booking/calendar' );

		echo "<div {$this->render_attributes( '_root' )}>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$renderer = new Calendar_Renderer( $this->settings );
		$renderer->render();

		echo '</div>';

	}

}