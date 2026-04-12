<?php
/**
 * JetEngine compatibility package Listings manager.
 *
 * @package JET_ABAF\Compatibility\Packages\Jet_Engine\Listings
 */

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Listings;

use \JET_ABAF\Resources\Booking;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * A reference to an instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	public function __construct() {

		new Elementor_Views\Manager();
		new Blocks_Views\Manager();
		new Popups();

		// Register JetBooking object fields group.
		add_filter( 'jet-engine/listing/data/object-fields-groups', [ $this, 'add_object_fields_groups' ] );
		// Handle dynamic field property output.
		add_filter( 'jet-engine/listings/data/prop-not-found', [ $this, 'get_dynamic_field_prop' ], 10, 3 );
		// Register additional columns object fields groups.
		add_filter( 'jet-engine/listing/data/jet-booking-query/object-fields-groups', [ $this, 'add_additional_column_object_fields' ] );
		// Handle title macros for booking item.
		add_filter( 'jet-engine/listing/current-object-title', [ $this, 'get_current_booking_object_title' ], 10, 2 );
		// Handle meta macros for booking item.
		add_filter( 'jet-engine/macros/current-meta', [ $this, 'get_current_booking_meta' ], 10, 3 );
		// Register additional calendar widget group keys.
		add_filter( 'jet-engine/listing/calendar/group-keys', [ $this, 'register_calendar_group_keys' ] );
		// Handle calendar widget date key.
		add_filter( 'jet-engine/listing/calendar/date-key', [ $this, 'prepare_booking_date_key' ], 10, 4 );
		add_filter( 'jet-engine/calendar/get-event-start', [ $this, 'prepare_mday_caledar_starts' ], 0, 3 );
		add_filter( 'jet-engine/calendar/get-event-end', [ $this, 'prepare_mday_caledar_ends' ], 0, 3 );
		// Set multidays parameters for booking group date key.
		add_action( 'jet-engine/listing/calendar/before', [ $this, 'set_booking_multiday_params' ], 10, 2 );
		// Custom dynamic link url.
		add_filter( 'jet-engine/listings/dynamic-link/custom-url', [ $this, 'set_custom_url_link' ], 10, 2 );
		// Set listing custom post type id.
		add_filter( 'jet-engine/listing/custom-post-id', [ $this, 'set_queried_booking_id' ], 10, 2 );
		// Handle custom values in dynamic field for Booking object.
		add_filter( 'jet-engine/listings/dynamic-field/custom-value', [ $this, 'dynamic_field_custom_value_handle' ], 10, 3 );
		// Create booking related dynamic link source fields.
		add_filter( 'jet-engine/listings/dynamic-link/fields', [ $this, 'add_link_source_fields' ] );
		// Add dynamic link custom attributes.
		add_filter( 'jet-engine/listings/dynamic-link/custom-attrs', [ $this, 'add_link_custom_attributes' ], 10, 2 );

		// Set booking object for JetFormBuilder elements handling.
		add_filter( 'jet-booking/formbuilder-plugin/actions/object', [ $this, 'get_listing_object' ] );
		add_filter( 'jet-booking/form-builder/preset-source/object', [ $this, 'get_listing_object' ] );
		add_filter( 'jet-booking/bricks-views/condition/object', [ $this, 'get_listing_object' ] );
		add_filter( 'jet-booking/form-builder/hidden-field/object', [ $this, 'get_listing_object' ] );

		// Prevent conflict with Kava theme.
		remove_filter( 'get_post_metadata', 'kava_set_default_single_post_template', 10 );

	}

	/**
	 * Returns listing object.
	 *
	 * @since  3.3.0
	 *
	 * @return object|null
	 */
	public function get_listing_object() {
		return jet_engine()->listings->data->get_current_object();
	}

	/**
	 * Add object fields groups.
	 *
	 * Add JetBooking related fields into dynamic object fields groups.
	 *
	 * @since  3.1.0
	 * @since  3.7.0 Added user email, check in/out time options.
	 *
	 * @param array $groups List of objects groups.
	 *
	 * @return array
	 */
	public function add_object_fields_groups( $groups ) {

		$groups[] = [
			'label'   => __( 'JetBooking', 'jet-booking' ),
			'options' => apply_filters( 'jet-engine/listing/data/jet-booking-query/object-fields-groups', [
				'jet_abaf_get_id'             => __( 'Booking ID', 'jet-booking' ),
				'jet_abaf_get_status'         => __( 'Status', 'jet-booking' ),
				'jet_abaf_get_apartment_id'   => __( 'Booking Instance ID', 'jet-booking' ),
				'jet_abaf_get_apartment_unit' => __( 'Booking Instance Unit ID', 'jet-booking' ),
				'jet_abaf_get_check_in_date'  => __( 'Check In Date', 'jet-booking' ),
				'jet_abaf_get_check_in_time'  => __( 'Check In Time', 'jet-booking' ),
				'jet_abaf_get_check_out_date' => __( 'Check Out Date', 'jet-booking' ),
				'jet_abaf_get_check_out_time' => __( 'Check Out Time', 'jet-booking' ),
				'jet_abaf_get_user_id'        => __( 'User ID', 'jet-booking' ),
				'jet_abaf_get_user_email'     => __( 'User E-mail', 'jet-booking' ),
				'jet_abaf_get_order_id'       => __( 'Related Order ID', 'jet-booking' ),
			] ),
		];

		return $groups;

	}

	/**
	 * Get dynamic field prop.
	 *
	 * Returns dynamic property values.
	 *
	 * @since  3.1.0
	 *
	 * @param mixed  $result   Dynamic field property value.
	 * @param string $property Property name.
	 * @param object $object   Current listing object.
	 *
	 * @return mixed
	 */
	public function get_dynamic_field_prop( $result, $property, $object ) {

		if ( false !== strpos( $property, 'jet_abaf_' ) ) {
			$property = str_replace( 'jet_abaf_', '', $property );
		}

		if ( is_callable( [ $object, $property ] ) ) {
			$result = call_user_func( [ $object, $property ] );
		} elseif ( false !== strpos( $property, 'jet_abaf::' ) && is_callable( [ $object, 'get_column' ] ) ) {
			$result = $object->get_column( str_replace( 'jet_abaf::', '', $property ) );
		}

		return $result;

	}

	/**
	 * Add additional column object fields.
	 *
	 * Add JetBooking additional columns source fields into the dynamic field widget.
	 *
	 * @since  3.1.0
	 *
	 * @param array $fields List of field.
	 *
	 * @return array
	 */
	public function add_additional_column_object_fields( $fields ) {

		$columns = jet_abaf()->settings->get_clean_columns();

		if ( empty( $columns ) ) {
			return $fields;
		}

		foreach ( $columns as $column ) {
			$fields[ 'jet_abaf::' . $column ] = __( 'Additional column: ', 'jet-booking' ) . $column;
		}

		return $fields;

	}

	/**
	 * Get current booking object title.
	 *
	 * Returns booking instance title of current booking item.
	 *
	 * @since  2.7.0
	 * @since  3.2.0 Change object type.
	 *
	 * @param string  $title   Current object title.
	 * @param Booking $booking Booking instance.
	 *
	 * @return string
	 */
	public function get_current_booking_object_title( $title, $booking ) {

		if ( ! $booking || ! is_a( $booking, '\JET_ABAF\Resources\Booking' ) || empty( $booking->get_apartment_id() ) ) {
			return $title;
		}

		return get_the_title( $booking->get_apartment_id() );

	}

	/**
	 * Get current booking meta.
	 *
	 * Returns meta value of booking instance for current booking item.
	 *
	 * @since  2.7.0
	 * @since  3.2.0 Change object type.
	 *
	 * @param boolean $meta_value Initial value.
	 * @param Booking $booking    Booking instance.
	 * @param string  $meta_key   Meta field key name.
	 *
	 * @return mixed
	 */
	public function get_current_booking_meta( $meta_value, $booking, $meta_key ) {

		if ( ! $booking || ! is_a( $booking, '\JET_ABAF\Resources\Booking' ) || empty( $booking->get_apartment_id() ) ) {
			return $meta_value;
		}

		return get_post_meta( $booking->get_apartment_id(), $meta_key, true );

	}

	/**
	 * Register calendar group keys.
	 *
	 * Register additional booking key for the calendar widget.
	 *
	 * @since  3.1.0
	 *
	 * @param array $keys List of initial groups keys.
	 *
	 * @return array
	 */
	public function register_calendar_group_keys( $keys ) {

		$keys['jet_booking'] = __( 'Booking date', 'jet-booking' );

		return $keys;

	}

	/**
	 * Prepare booking date key.
	 *
	 * Return calendar date key for appointments in calendar
	 *
	 * @since  3.1.0
	 *
	 * @param string                       $key      Timestamp of required month day.
	 * @param object                       $item     Listing item object.
	 * @param string                       $group_by Items group by key.
	 * @param \Jet_Listing_Render_Calendar $calendar Calendar widget render object.
	 *
	 * @return mixed
	 */
	public function prepare_booking_date_key( $key, $item, $group_by, $calendar ) {

		if ( 'jet_booking' === $group_by ) {
			$allowed_range = $calendar->get_date_period_for_query( $calendar->get_settings() );

			$key = $item->get_check_in_date();

			if ( $key < $allowed_range['start'] || $key > $allowed_range['end'] ) {
				return false;
			}
		}

		return $key;

	}

	/**
	 * Get start date (check in date) for multiday calendar from booking item
	 *
	 * @since  4.0.0
	 *
	 * @param  mixed  $key      Initial key.
	 * @param  object $item     Booking object
	 * @param  array  $settings Multiday calendar settings
	 *
	 * @return mixed  Timestamp of start date or inherits initial value.
	 */
	public function prepare_mday_caledar_starts( $key, $item, $settings ) {
		$group_by = isset( $settings['group_by'] ) ? $settings['group_by'] : 'meta_date';

		if ( 'jet_booking' === $group_by
			&& is_object( $item )
			&& is_callable( array( $item, 'get_check_in_date' ) )
		) {
			$key = $item->get_check_in_date();
		}

		return $key;
	}

	/**
	 * Get end date (check out date) for multiday calendar from booking item
	 *
	 * @since  4.0.0
	 *
	 * @param  mixed  $key      Initial key.
	 * @param  object $item     Booking object
	 * @param  array  $settings Multiday calendar settings
	 *
	 * @return mixed  Timestamp of end date or inherits initial value.
	 */
	public function prepare_mday_caledar_ends( $key, $item, $settings ) {
		$group_by = isset( $settings['group_by'] ) ? $settings['group_by'] : 'meta_date';

		if ( 'jet_booking' === $group_by
			&& is_object( $item )
			&& is_callable( array( $item, 'get_check_out_date' ) )
		) {
			$key = $item->get_check_out_date();
		}

		return $key;
	}

	/**
	 * Set booking multidays params.
	 *
	 * Handle booking groups key and set appropriate parameters for multiday output.
	 *
	 * @since  3.1.0
	 *
	 * @param array                        $settings List of Calendar widget settings.
	 * @param \Jet_Listing_Render_Calendar $calendar Calendar widget render object.
	 *
	 * @throws \Exception
	 */
	public function set_booking_multiday_params( $settings, $calendar ) {

		if ( 'jet_booking' !== $settings['group_by'] ) {
			return;
		}

		$query_id = null;

		if ( isset( $settings['custom_query'] ) && filter_var( $settings['custom_query'], FILTER_VALIDATE_BOOLEAN ) ) {
			$query_id = absint( $settings['custom_query_id'] );
		} else {
			$listing          = jet_engine()->listings->data->get_listing();
			$listing_settings = $listing->get_settings();

			if ( 'query' === $listing_settings['listing_source'] ) {
				$query_id = \Jet_Engine\Query_Builder\Manager::instance()->listings->get_query_id( $listing->get_main_id(), $listing_settings );
			}
		}

		if ( ! $query_id ) {
			return;
		}

		$query = \Jet_Engine\Query_Builder\Manager::instance()->get_query_by_id( $query_id );

		if ( ! $query ) {
			return;
		}

		$query->setup_query();

		$month = [
			'start' => $calendar->get_current_month(),
			'end'   => $calendar->get_current_month( true ),
		];

		foreach ( $query->get_items() as $item ) {
			$interval        = jet_abaf()->tools->get_booking_period_interval( $item->get_check_in_date(), $item->get_check_out_date(), $item->get_apartment_id(), true );
			$calendar_period = $calendar->get_date_period_for_query( $settings );

			for ( $i = 1; $i <= $interval->days; $i ++ ) {
				$day = strtotime( date( 'Y-m-d', $item->get_check_in_date() ) . '+ ' . $i . ' days' );

				if ( $day < $calendar_period['start'] || $day > $calendar_period['end'] ) {
					continue;
				}

				$j = absint( date( 'j', $day ) );

				if ( $day < $month['start'] ) {
					if ( empty( $calendar->prev_month_posts[ $j ] ) ) {
						$calendar->prev_month_posts[ $j ] = [ $item ];
					} else {
						$calendar->prev_month_posts[ $j ][] = $item;
					}

					continue;
				}

				if ( $day > $month['end'] ) {
					if ( empty( $calendar->next_month_posts[ $j ] ) ) {
						$calendar->next_month_posts[ $j ] = [ $item ];
					} else {
						$calendar->next_month_posts[ $j ][] = $item;
					}

					continue;
				}

				if ( empty( $calendar->multiday_events[ $j ] ) ) {
					$calendar->multiday_events[ $j ] = array( $item );
				} else {
					$calendar->multiday_events[ $j ][] = $item;
				}

				$calendar->posts_cache[ jet_engine()->listings->data->get_current_object_id( $item ) ] = false;
			}
		}

	}

	/**
	 * Set queried booking id.
	 *
	 * Set correct booking id  for listing output.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string $id     Queried object id.
	 * @param object     $object Listing queried object.
	 *
	 * @return mixed
	 */
	public function set_queried_booking_id( $id, $object ) {

		if ( $object && is_object( $object ) && is_a( $object, '\JET_ABAF\Resources\Booking' ) ) {
			$id = $object->get_id();
		}

		return $id;

	}

	/**
	 * Dynamic field custom value handle.
	 *
	 * Handle custom values in dynamic field for Booking object.
	 *
	 * @since 3.1.0
	 *
	 * @param boolean                          $value         Initial custom field value.
	 * @param array                            $settings      List of dynamic field settings.
	 * @param \Jet_Engine_Render_Dynamic_Field $dynamic_field Dynamic field class instance.
	 *
	 * @return mixed|string
	 */
	public function dynamic_field_custom_value_handle( $value, $settings, $dynamic_field ) {

		if ( 'object' !== $dynamic_field->get( 'dynamic_field_source' ) ) {
			return $value;
		}

		$object_context = $dynamic_field->get( 'object_context' );
		$post_object    = jet_engine()->listings->data->get_object_by_context( $object_context );

		if ( ! $post_object ) {
			$post_object = jet_engine()->listings->data->get_current_object();
		}

		if ( $post_object && is_object( $post_object ) && is_a( $post_object, '\JET_ABAF\Resources\Booking' ) ) {
			$field = $dynamic_field->get( 'dynamic_field_post_object' );
			$auto  = $dynamic_field->get( 'dynamic_field_wp_excerpt', '' );

			if ( 'post_excerpt' === $field && ! empty( $auto ) ) {
				$post_excerpt = get_the_excerpt( $post_object->get_apartment_id() );
				$value        = ! empty( $post_excerpt ) ? $post_excerpt : __( 'Unlock unforgettable experiences with our booking service.', 'jet-booking' );
			}
		}

		return $value;

	}


	/**
	 * Set custom url link.
	 *
	 * Returns custom URL for dynamic link widget.
	 *
	 * @since  3.2.0
	 * @since  3.3.0 Added cancel link handling.
	 *
	 * @param mixed $url      Initial URL for dynamic link widget.
	 * @param array $settings List of settings for dynamic link widget.
	 *
	 * @return false|string
	 */
	public function set_custom_url_link( $url, $settings ) {

		$source = $settings['dynamic_link_source'] ?? '_permalink';

		if ( ! in_array( $source, [ jet_abaf()->google_cal->query_var, 'booking_cancel_link' ] ) ) {
			return $url;
		}

		$booking = jet_engine()->listings->data->get_current_object();

		if ( ! $booking || ! is_a( $booking, '\JET_ABAF\Resources\Booking' ) ) {
			return $url;
		}

		switch ( $source ) {
			case jet_abaf()->google_cal->query_var:
				return jet_abaf()->google_cal->get_internal_link( $booking->get_id() );

			case 'booking_cancel_link':
				if ( ! $booking->is_cancellable() ) {
					break;
				}

				$redirect = home_url();

				if ( ! empty( $settings['dynamic_link_cancel_redirect_url'] ) ) {
					$redirect = jet_engine()->listings->macros->do_macros( $settings['dynamic_link_cancel_redirect_url'] );
				}

				return $booking->get_cancel_url( '', esc_url( $redirect ) );
		}

		return $url;

	}

	/**
	 * Add link source fields.
	 *
	 * Returns extended dynamic links source fields list.
	 *
	 * @since  3.3.0
	 *
	 * @param array $groups Source fields groups list.
	 *
	 * @return mixed
	 */
	public function add_link_source_fields( $groups ) {

		$options = [
			jet_abaf()->google_cal->query_var => __( 'Add to Google Calendar', 'jet-booking' ),
			'booking_cancel_link'             => __( 'Cancel Link', 'jet-booking' ),
		];

		$groups[] = [
			'label'   => __( 'JetBooking', 'jet-booking' ),
			'options' => apply_filters( 'jet-booking/listings/dynamic-link/jet-booking-options', $options ),
		];

		return $groups;

	}

	/**
	 * Add dynamic link custom HTML attributes.
	 *
	 * @since 3.3.0
	 *
	 * @param string                          $attrs    List of dynamic link custom attributes.
	 * @param \Jet_Engine_Render_Dynamic_Link $renderer Dynamic link instance.
	 *
	 * @return mixed|string
	 */
	public function add_link_custom_attributes( $attrs, $renderer ) {

		$settings = $renderer->get_settings();

		if ( ! empty( $settings['dynamic_link_source'] ) && 'booking_cancel_link' === $settings['dynamic_link_source'] ) {
			$attrs .= ' data-cancel-booking="1"';
		}

		return $attrs;
	}

	/**
	 * Returns the instance.
	 *
	 * @since  3.1.0
	 *
	 * @return Manager
	 */
	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

}
