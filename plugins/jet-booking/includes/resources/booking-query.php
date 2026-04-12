<?php
/**
 * Class for parameter-based Booking querying.
 *
 * @package JET_ABAF\Resources
 */

namespace JET_ABAF\Resources;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Query {

	/**
	 * Stores query data.
	 *
	 * @var array
	 */
	protected $query_vars = [];

	public function __construct( $args = [] ) {
		$this->query_vars = wp_parse_args( $args, $this->get_default_query_vars() );
	}

	/**
	 * Get the current query vars.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	public function get_query_vars() {
		return $this->query_vars;
	}

	/**
	 * Get default query vars.
	 *
	 * Returns list of default valid query variables for bookings.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return [
			'status'         => '',
			'include'        => '',
			'exclude'        => '',
			'apartment_id'   => '',
			'apartment_unit' => '',
			'order_id'       => '',
			'user_id'        => '',
			'date_query'     => [],
			'meta_query'     => [],
			'sorting'        => [],
			'limit'          => 0,
			'offset'         => 0,
			'return'         => 'objects',
		];
	}

	/**
	 * Get bookings.
	 *
	 * Returns bookings matching the current query vars.
	 *
	 * @since 3.3.0
	 *
	 * @return array|object of Bookings objects.
	 */
	public function get_bookings() {
		return $this->query( $this->get_query_vars() );
	}

	/**
	 * Query for bookings matching specific criteria.
	 *
	 * @since 3.3.0
	 * @since 3.6.0 Refactored array return type.
	 * @since 4.0.0 Added `booking_vendor` handling.
	 *
	 * @param array $query_vars Query variables.
	 *
	 * @return array
	 */
	public function query( $query_vars ) {

		global $wpdb;

		$query_clause = [];

		if ( ! empty( $query_vars['status'] ) ) {
			$query_clause[] = sprintf( "status IN ( '%s' )", is_array( $query_vars['status'] ) ? implode( "', '", $query_vars['status'] ) : $query_vars['status'] );
		}

		if ( ! empty( $query_vars['include'] ) ) {
			$query_clause[] = sprintf( "booking_id IN ( %s )", is_array( $query_vars['include'] ) ? implode( ", ", $query_vars['include'] ) : $query_vars['include'] );
		} else {
			if ( ! empty( $query_vars['exclude'] ) ) {
				$query_clause[] = sprintf( "booking_id NOT IN ( %s )", is_array( $query_vars['exclude'] ) ? implode( ", ", $query_vars['exclude'] ) : $query_vars['exclude'] );
			}
		}

		if ( ! empty( $query_vars['booking_vendor'] ) ) {
			$query_clause[] = sprintf( "booking_vendor IN ( %s )", is_array( $query_vars['booking_vendor'] ) ? implode( ", ", $query_vars['booking_vendor'] ) : $query_vars['booking_vendor'] );
		}

		if ( ! empty( $query_vars['apartment_id'] ) ) {
			$query_clause[] = sprintf( "apartment_id IN ( %s )", is_array( $query_vars['apartment_id'] ) ? implode( ", ", $query_vars['apartment_id'] ) : $query_vars['apartment_id'] );

			if ( ! empty( $query_vars['apartment_unit'] ) ) {
				$query_clause[] = sprintf( "apartment_unit IN ( %s )", is_array( $query_vars['apartment_unit'] ) ? implode( ", ", $query_vars['apartment_unit'] ) : $query_vars['apartment_unit'] );
			}
		}

		if ( ! empty( $query_vars['order_id'] ) ) {
			$query_clause[] = sprintf( "order_id IN ( %s )", is_array( $query_vars['order_id'] ) ? implode( ", ", $query_vars['order_id'] ) : $query_vars['order_id'] );
		}

		if ( ! empty( $query_vars['user_id'] ) ) {
			$query_clause[] = sprintf( "user_id IN ( %s )", is_array( $query_vars['user_id'] ) ? implode( ", ", $query_vars['user_id'] ) : $query_vars['user_id'] );
		}

		if ( ! empty( $query_vars['date_query'] ) ) {
			$date_query_clause = [];
			$date_query_rel    = 'AND';

			foreach ( $query_vars['date_query'] as $key => $clause ) {
				if ( 'relation' === $key ) {
					$date_query_rel = 'OR' === strtoupper( $clause ) ? 'OR' : 'AND';
				} elseif ( is_array( $clause ) ) {
					if ( empty( $clause['column'] ) ) {
						continue;
					}

					$operator = ! empty( $clause['operator'] ) ? $clause['operator'] : '=';
					$value    = $this->sanitize_date( $clause['value'] );

					if ( ! $value ) {
						continue;
					}

					if ( 'check_in_date' === $clause['column'] ) {
						$value ++;
					}

					$date_query_clause[] = sprintf( "%s %s %s", $clause['column'], $operator, $value );
				}
			}

			if ( ! empty( $date_query_clause ) ) {
				$query_clause[] = sprintf( "( %s )", implode( ' ' . $date_query_rel . ' ', $date_query_clause ) );
			}
		}

		if ( ! empty( $query_vars['meta_query'] ) ) {
			$meta_query_clause = [];
			$meta_query_rel    = 'AND';

			foreach ( $query_vars['meta_query'] as $key => $clause ) {
				if ( 'relation' === $key ) {
					$meta_query_rel = 'OR' === strtoupper( $clause ) ? 'OR' : 'AND';
				} elseif ( is_array( $clause ) ) {
					if ( empty( $clause['column'] ) ) {
						continue;
					}

					$value = false;

					if ( isset( $clause['value'] ) ) {
						$value = is_array( $clause['value'] ) ? "{$clause['value'][0]} AND {$clause['value'][1]}" : '\'' . $clause['value'] . '\'';
					}

					$operator            = ! empty( $clause['operator'] ) ? $clause['operator'] : '=';
					$meta_query_clause[] = $value ? sprintf( "%s %s %s", $clause['column'], $operator, $value ) : sprintf( "%s %s", $clause['column'], $operator );
				}
			}

			if ( ! empty( $meta_query_clause ) ) {
				$query_clause[] = sprintf( "( %s )", implode( ' ' . $meta_query_rel . ' ', $meta_query_clause ) );
			}
		}

		$query = sprintf( "SELECT * FROM %s", jet_abaf()->db->bookings->table() );

		if ( ! empty( $query_clause ) ) {
			$query .= sprintf( " WHERE %s", implode( ' AND ', $query_clause ) );
		}

		if ( ! empty( $query_vars['sorting'] ) ) {
			$sorting_query_clause = [];

			foreach ( $query_vars['sorting'] as $sorting ) {
				if ( empty( $sorting['orderby'] ) ) {
					continue;
				}

				$order                  = ! empty( $sorting['order'] ) ? $sorting['order'] : 'ASC';
				$sorting_query_clause[] = sprintf( "%s %s", $sorting['orderby'], $order );
			}

			if ( ! empty( $sorting_query_clause ) ) {
				$query .= sprintf( " ORDER BY %s", implode( ', ', $sorting_query_clause ) );
			}
		}

		if ( ! empty( $query_vars['limit'] ) && intval( $query_vars['limit'] ) > 0 ) {
			$query .= sprintf( " LIMIT %d", intval( $query_vars['limit'] ) );

			if ( ! empty( $query_vars['offset'] ) ) {
				$query .= sprintf( " OFFSET %d", intval( $query_vars['offset'] ) );
			}
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		return array_filter( array_map( function ( $item ) use ( $query_vars ) {
			$booking = new Booking( $item );

			if ( isset( $query_vars['return'] ) && 'arrays' === $query_vars['return'] ) {
				return $booking->get_object_vars();
			} else {
				return $booking;
			}
		}, $results ) );

	}

	/**
	 * Sanitizes date query.
	 *
	 * @since 3.3.0
	 *
	 * @param string|array $date Date query parameter row..
	 *
	 * @return string
	 */
	public function sanitize_date( $date ) {

		if ( empty( $date ) ) {
			return false;
		}

		if ( is_array( $date ) ) {
			$date_1 = is_numeric( $date[0] ) ? $date[0] : strtotime( $date[0] );
			$date_2 = is_numeric( $date[1] ) ? $date[1] : strtotime( $date[1] );

			if ( ! $date_1 || ! $date_2 ) {
				return false;
			}

			$date = "{$date_1} AND {$date_2}";
		} else {
			$date = is_numeric( $date ) ? $date : strtotime( $date );
		}

		return $date;

	}

}