<?php
defined( 'ABSPATH' ) || exit;

class BAS_Conflict_Detector {

	/**
	 * Returnează array de forma:
	 * [ artist_id => ['YYYY-MM-DD', ...] ]
	 *
	 * Conține zilele în care fiecare artist este ocupat cu un eveniment
	 * NON-club (confirmed/pending) sau vacation.
	 * Evenimentele de tip "club" sunt excluse — sunt chiar evenimentele
	 * create de calendar și nu constituie conflict.
	 */
	public static function get_conflicts_map(): array {
		$events = get_posts( [
			'post_type'      => 'evenimente',
			'posts_per_page' => 500,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'data-evenimentului',
					'value'   => strtotime( 'today midnight' ),
					'compare' => '>=',
					'type'    => 'NUMERIC',
				],
			],
		] );

		$map = [];

		foreach ( $events as $event ) {
			$status = get_post_meta( $event->ID, 'status-eveniment', true );
			$type   = get_post_meta( $event->ID, 'tipul-evenimentului', true );

			// Tipul "club" nu generează conflict
			if ( 'club' === strtolower( $type ) ) {
				continue;
			}

			// Canceled nu blochează
			if ( 'canceled' === $status ) {
				continue;
			}

			// confirmed, pending, vacation generează conflict
			if ( ! in_array( $status, [ 'confirmed', 'pending', 'vacation' ], true ) ) {
				continue;
			}

			$artist_id = $event->post_author;
			$start_ts  = (int) get_post_meta( $event->ID, 'data-evenimentului', true );
			$end_ts    = (int) get_post_meta( $event->ID, 'data-sfarsit', true );

			if ( ! $start_ts ) {
				continue;
			}

			// Dacă nu e vacanță, end = start
			if ( ! $end_ts || $end_ts < $start_ts ) {
				$end_ts = $start_ts;
			}

			// Generează toate zilele din interval
			$day = strtotime( 'midnight', $start_ts );
			$end = strtotime( 'midnight', $end_ts );

			while ( $day <= $end ) {
				$map[ $artist_id ][] = date( 'Y-m-d', $day );
				$day = strtotime( '+1 day', $day );
			}
		}

		// Elimină duplicate
		foreach ( $map as $id => $dates ) {
			$map[ $id ] = array_values( array_unique( $dates ) );
		}

		return $map;
	}
}
