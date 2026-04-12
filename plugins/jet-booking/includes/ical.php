<?php
namespace JET_ABAF;

use JET_ABAF\Resources\Booking;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class iCal {

	/**
	 * Trigger to get iCal file
	 *
	 * @var string
	 */
	private $trigger = '_get_ical';
	private $hash = null;
	private $domain = null;
	private $ical_meta = '_import_ical';

	const ICAL_TEMPLATE_KEY = 'jet_booking_ical_template';

	public function __construct() {

		if ( ! filter_var( jet_abaf()->settings->get( 'ical_synch' ), FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$this->hash = md5( $this->get_domain() );

		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! empty( $_GET[ $this->trigger ] ) && $this->hash === $_GET[ $this->trigger ] && ! empty( $_GET['_id'] ) ) {
			$this->get_calendar_file();
		}
		// phpcs:enable WordPress.Security.NonceVerification

	}

	/**
	 * Returns current domain
	 *
	 * @return [type] [description]
	 */
	public function get_domain() {

		if ( $this->domain ) {
			return $this->domain;
		}

		$find         = array( 'http://', 'https://' );
		$replace      = '';
		$this->domain = str_replace( $find, $replace, home_url() );

		return $this->domain;

	}

	/**
	 * Sync.
	 *
	 * Synchronize calendars.
	 *
	 * @since  3.0.0 Minor refactor.
	 * @since  3.5.1 Added `jet-booking/ical/sync/before-import` and `jet-booking/ical/sync/after-import` action hooks.
	 *
	 * @param int   $post_id Booking instance ID.
	 * @param false $unit_id Booking instance unit ID.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function synch( $post_id = 0, $unit_id = false ) {

		$log = [];

		if ( ! $post_id ) {
			return [ __( 'Post ID not found.', 'jet-booking' ) ];
		}

		if ( ! $unit_id ) {
			$unit_id = 'default';
		}

		$import = get_post_meta( $post_id, $this->ical_meta, true );

		if ( ! $import ) {
			$import = [];
		}

		if ( empty( $import[ $unit_id ] ) ) {
			return [ __( 'External calendars is not found for this item.', 'jet-booking' ) ];
		}

		do_action( 'jet-booking/ical/sync/before-import', $post_id, $unit_id );

		foreach ( $import[ $unit_id ] as $url ) {
			$response = wp_remote_get( $url );
			$label    = '<b>' . $url . ':</b><br> ';

			if ( is_wp_error( $response ) ) {
				$log[] = $label . __( 'Can`t access calendar', 'jet-booking' ) . ', ' . $response->get_error_message();

				continue;
			}

			$body = wp_remote_retrieve_body( $response );

			if ( ! $body ) {
				$log[] = $label . __( 'Empty response from calendar.', 'jet-booking' );

				continue;
			}

			if ( false === strpos( $body, 'BEGIN:VCALENDAR' ) ) {
				$log[] = $label . __( 'Invalid iCal format.', 'jet-booking' );

				continue;
			}

			$log[] = $label . $this->import_calendar( $body, $post_id, $unit_id, $url );
		}

		do_action( 'jet-booking/ical/sync/after-import', $post_id, $unit_id );

		return $log;

	}

	/**
	 * Import calendar.
	 *
	 * Import third-party calendar data.
	 *
	 * @since  3.0.0 Added filter hook `jet-booking/ical/import/log`.
	 * @since  3.5.0 Added $calendar_url parameter to method and `jet-booking/ical/import/node` hook.
	 *
	 * @param string     $data         Body of the import calendar.
	 * @param int|string $post_id      Booking instance post type ID.
	 * @param int|string $unit_id      Booking instance post type unit ID.
	 * @param string     $calendar_url iCalendar URL
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function import_calendar( $data = null, $post_id = null, $unit_id = null, $calendar_url = '' ) {

		$this->load_deps();

		$calendar_object = new \ZCiCal( $data );
		$inserted        = [];
		$skipped         = [];

		if ( ! $calendar_object->countEvents() || ! $calendar_object->tree->child ) {
			$import_log = __( 'Bookings not found.', 'jet-booking' );
		} else {
			foreach ( $calendar_object->tree->child as $node ) {
				if ( 'VEVENT' !== $node->getName() ) {
					continue;
				}

				$import_node = [
					'apartment_id' => $post_id,
					'status'       => 'pending',
				];

				if ( 'default' !== $unit_id ) {
					$import_node['apartment_unit'] = $unit_id;
				}

				$import_id = false;

				foreach ( $node->data as $key => $value ) {
					switch ( $key ) {
						case 'DTSTART':
							$import_node['check_in_date'] = \ZDateHelper::fromiCaltoUnixDateTime( $value->getValues() );
							break;

						case 'DTEND':
							$import_node['check_out_date'] = \ZDateHelper::fromiCaltoUnixDateTime( $value->getValues() ) - DAY_IN_SECONDS;
							break;

						case 'UID':
							$import_node['import_id'] = $import_id = untrailingslashit( $value->getValues() );
							break;
					}
				}

				$import_node = apply_filters( 'jet-booking/ical/import/node', $import_node, $node, $calendar_object, $calendar_url );

				if ( jet_abaf()->db->is_booking_dates_available( $import_node ) ) {
					remove_all_actions( 'jet-booking/db/booking-inserted' );

					$inserted[] = jet_abaf()->db->insert_booking( $import_node );
				} else {
					$skipped[] = $import_id;
				}
			}

			/* translators: 1: inserted bookings count, 2: skipped bookings count */
			$import_log = sprintf( __( '<i> Inserted bookings: </i> %1$d units <br><i>Skipped bookings: </i> %2$d units', 'jet-booking' ), count( $inserted ), count( $skipped ) );
		}

		return apply_filters( 'jet-booking/ical/import/log', $import_log, $post_id, $unit_id, $inserted, $skipped, $calendar_url );

	}

	/**
	 * Get calendar file.
	 *
	 * Get calendar export file.
	 *
	 * @since 2.0.0
	 */
	public function get_calendar_file() {

		// phpcs:disable WordPress.Security.NonceVerification
		$post_id = ! empty( $_GET['_id'] ) ? absint( $_GET['_id'] ) : null;
		$uid     = ! empty( $_GET['_uid'] ) ? absint( $_GET['_uid'] ) : null;
		// phpcs:enable WordPress.Security.NonceVerification

		if ( ! $post_id ) {
			esc_html_e( 'Invalid request data', 'jet-booking' );
			die();
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			esc_html_e( 'Invalid request data', 'jet-booking' );
			die();
		}

		$this->load_deps();

		$datestamp = \ZCiCal::fromUnixDateTime() . 'Z';
		$calendar  = new \ZCiCal();
		$filename  = 'calendar-export--' . $post->post_name;

		$params = [
			'apartment_id' => $post_id,
			'status'       => jet_abaf()->statuses->valid_statuses(),
		];

		if ( $uid ) {
			$params['apartment_unit'] = $uid;
			$filename                 .= '-' . $uid;
		}

		$params   = apply_filters( 'jet-booking/ical/export/bookings-params', $params, $post_id );
		$bookings = jet_abaf_get_bookings( $params );

		if ( ! empty( $bookings ) ) {
			foreach ( $bookings as $booking ) {
				$this->add_booking( $booking, $calendar, $datestamp );
			}
		}

		header( 'Content-type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename=' . $filename . '.ics' );

		echo esc_html( $calendar->export() );

		die();

	}

	/**
	 * Add booking.
	 *
	 * Add new booking into existing calendar.
	 *
	 * @since  2.7.0 Updated summary and description handling.
	 * @since  3.6.1 Refactored.
	 * @access public
	 *
	 * @param Booking $booking   Booking instance.
	 * @param object  $calendar  iCal object instance.
	 * @param string  $datestamp Formatted iCal date/time string.
	 *
	 * @return void
	 */
	public function add_booking( $booking, $calendar, $datestamp ) {

		$check_out_ts = $booking->get_check_out_date();

		if ( ! jet_abaf()->settings->is_per_nights_booking( $booking->get_apartment_id() ) ) {
			$check_out_ts = $check_out_ts + DAY_IN_SECONDS;
		}

		$check_in_date  = date( 'Y-m-d', $booking->get_check_in_date() );
		$check_out_date = date( 'Y-m-d', $check_out_ts );

		$data = apply_filters( 'jet-booking/ical/ical-booking-data', [
			'uid'         => [
				'node'  => 'UID',
				'value' => md5( $booking->get_check_in_date() . $booking->get_check_out_date() . $booking->get_id() ) . '@' . $this->get_domain(),
			],
			'dtstart'     => [
				'node'  => 'DTSTART;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $check_in_date ),
			],
			'dtend'       => [
				'node'  => 'DTEND;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $check_out_date ),
			],
			'dtstamp'     => [
				'node'  => 'DTSTAMP',
				'value' => $datestamp,
			],
			'summary'     => [
				'node'  => 'SUMMARY',
				'value' => $this->get_ical_template_data( 'summary', $booking ),
			],
			'description' => [
				'node'  => 'DESCRIPTION',
				'value' => $this->get_ical_template_data( 'description', $booking ),
			],
		], $booking, $calendar );

		$event = new \ZCiCalNode( 'VEVENT', $calendar->curnode );

		foreach ( $data as $row ) {
			$event->addNode( new \ZCiCalDataNode( $row['node'] . ':' . $row['value'] ) );
		}

		do_action_ref_array( 'jet-abaf/ical/export-booking', [ & $booking, & $calendar ] );

	}

	/**
	 * Get iCal template data.
	 *
	 * @since  2.7.0
	 * @since  4.0.0 Refactored.
	 * @access public
	 *
	 * @param string  $data_type Data type key name.
	 * @param Booking $booking   Booking instance.
	 *
	 * @return mixed|string|void
	 */
	public function get_ical_template_data( $data_type, $booking ) {

		$template = $this->get_ical_template();

		if ( ! $template || empty( $template[ $data_type ] ) ) {
			return $this->get_booking_data( $data_type, $booking );
		}

		return $this->parse_macros( $template[ $data_type ], $booking );

	}

	/**
	 * Retrieve iCal template.
	 *
	 * Fetches the iCal template based on the user type.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array|string|null The iCalendar template content or null if not set.
	 */
	public function get_ical_template() {

		$user_id = get_current_user_id();

		if ( jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			$template = get_user_meta( $user_id, self::ICAL_TEMPLATE_KEY, true );

			// Fallback to global option if user meta is empty.
			if ( ! $template ) {
				$template = get_option( self::ICAL_TEMPLATE_KEY );
			}
		} else {
			$template = get_option( self::ICAL_TEMPLATE_KEY );
		}

		return $template;

	}

	/**
	 * Set iCal template.
	 *
	 * Updates iCal template based on the user type.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array $template The iCalendar template content.
	 *
	 * @return void
	 */
	public function set_ical_template( $template ) {

		$user_id = get_current_user_id();

		if ( jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			update_user_meta( $user_id, self::ICAL_TEMPLATE_KEY, $template );
		} else {
			update_option( self::ICAL_TEMPLATE_KEY, $template );
		}

	}

	/**
	 * Returns booking summary.
	 *
	 * @since  2.0.0
	 * @since  2.8.0 Refactored.
	 * @since  3.2.0 Renamed and refactored.
	 * @since  4.0.0 Escape literal ICS special chars.
	 * @access public
	 *
	 * @param string  $data_type Data type name.
	 * @param Booking $booking   Booking instance.
	 *
	 * @return mixed|void
	 */
	public function get_booking_data( $data_type, $booking ) {

		switch ( $data_type ) {
			case 'summary':
				$data = sprintf( 'Booking #%d - %s', $booking->get_id(), get_the_title( $booking->get_apartment_id() ) );
				break;

			case 'description':
				$data = get_the_excerpt( $booking->get_apartment_id() );
				break;

			default:
				$data = __( 'Booking Item', 'jet-booking' );
				break;
		}

		$data = preg_replace( '/[^\p{L}\p{N}\s_-]/u', '', $data );

		return apply_filters( 'jet-abaf/ical/export-booking-summary', $data, $booking, $data_type );

	}

	/**
	 * Load dependencies
	 *
	 * @return [type] [description]
	 */
	public function load_deps() {

		if ( defined( '_ZAPCAL' ) ) {
			return;
		}

		require_once JET_ABAF_PATH . 'includes/vendor/icalendar/zapcallib.php';

	}

	/**
	 * Returns export URL
	 *
	 * @param  [type] $post_id [description]
	 * @param  [type] $unit_id [description]
	 *
	 * @return [type]          [description]
	 */
	public function get_export_url( $post_id = 0, $unit_id = 0 ) {
		return add_query_arg(
			array(
				$this->trigger => $this->hash,
				'_id'          => $post_id,
				'_uid'         => $unit_id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Strore external calendar URL to synch
	 *
	 * @param array   $urls    [description]
	 * @param integer $post_id [description]
	 * @param boolean $unit_id [description]
	 *
	 * @return [type]           [description]
	 */
	public function update_import_urls( $urls = array(), $post_id = 0, $unit_id = false ) {

		if ( ! $post_id ) {
			return;
		}

		$existing = get_post_meta( $post_id, $this->ical_meta, true );

		if ( ! $existing ) {
			$existing = array();
		}

		if ( ! $unit_id ) {
			$unit_id = 'default';
		}

		$existing[ $unit_id ] = $urls;

		update_post_meta( $post_id, $this->ical_meta, $existing );

	}

	/**
	 * Get calendars.
	 *
	 * Returns all URLs for calendars.
	 *
	 * @since  2.6.1 Code refactor.
	 * @since  3.0.0 Minor refactor.
	 * @since  4.0.0 Added vendor post handling.
	 * @access public
	 *
	 * @return array
	 */
	public function get_calendars() {

		$args    = [];
		$user_id = get_current_user_id();

		if ( jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			$args['author'] = $user_id;
		}

		$posts = jet_abaf()->tools->get_booking_posts( $args );

		if ( empty( $posts ) ) {
			return [];
		}

		$result = [];

		foreach ( $posts as $post ) {
			$import = get_post_meta( $post->ID, $this->ical_meta, true );

			if ( ! $import ) {
				$import = [];
			}

			$item = [
				'post_id'    => $post->ID,
				'title'      => $post->post_title,
				'unit_id'    => false,
				'unit_title' => '',
				'import_url' => $import['default'] ?? false,
				'export_url' => $this->get_export_url( $post->ID, false ),
			];

			$units = jet_abaf()->db->get_apartment_units( $post->ID );

			if ( empty( $units ) ) {
				$result[] = $item;
			} else {
				foreach ( $units as $unit ) {
					$unit_item = $item;
					$unit_id   = $unit['unit_id'];

					$unit_item['unit_id']    = $unit_id;
					$unit_item['unit_title'] = $unit['unit_title'];
					$unit_item['import_url'] = $import[ $unit_id ] ?? false;
					$unit_item['export_url'] = $this->get_export_url( $post->ID, $unit_id );

					$result[] = $unit_item;
				}
			}
		}

		return $result;

	}

	/**
	 * Parse macros.
	 *
	 * Parse export calendar template macros.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param string  $content Content string to parse.
	 * @param Booking $booking Booking instance.
	 *
	 * @return string|string[]|null
	 */
	public function parse_macros( $content, $booking ) {
		return jet_abaf()->macros->macros_handler->do_macros( $content, $booking );
	}

}
