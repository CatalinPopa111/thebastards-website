<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sincronizează wp4u_jet_apartment_bookings cu status-eveniment.
 *
 * Folosim added_post_meta + updated_post_meta pentru `status-eveniment` ca trigger
 * principal — acestea se declanșează DUPĂ ce meta este salvat, indiferent de calea
 * de creare (WP Admin, JetEngine form, bastard-admin-schedule).
 *
 * save_post_evenimente (priority 99) acoperă cazul accept_vacation() care apelează
 * wp_update_post() fără să schimbe statusul (meta deja salvat la acel moment).
 *
 * Înlocuiește Snippet 9 (updated_post_meta sync) și Snippet 17 (vacation booking).
 */
class BAS_Booking_Sync {

	private static bool $processing = false;

	// Per-request: previne dubla execuție dacă ambele hook-uri se declanșează la același save
	private static array $synced = [];

	// Permite callerilor care gestionează booking-ul manual să suspende sync-ul temporar
	public static function pause(): void  { self::$processing = true; }
	public static function resume(): void { self::$processing = false; }

	public static function register(): void {
		// Singurul trigger: când status-eveniment este adăugat sau modificat.
		// Aceste hook-uri se declanșează DUPĂ ce meta este efectiv salvat,
		// indiferent de calea de creare (WP Admin, JetEngine form, wp_update_post cu meta_input).
		add_action( 'added_post_meta',   [ __CLASS__, 'on_meta_change' ], 20, 4 );
		add_action( 'updated_post_meta', [ __CLASS__, 'on_meta_change' ], 20, 4 );
	}

	// Declanșat când status-eveniment este salvat/modificat
	public static function on_meta_change( int $meta_id, int $post_id, string $meta_key, mixed $meta_value ): void {
		if ( $meta_key !== 'status-eveniment' ) return;
		if ( ! is_string( $meta_value ) ) return;
		if ( get_post_type( $post_id ) !== 'evenimente' ) return;

		self::sync( $post_id, $meta_value );
	}

	// Logica principală de upsert — apelată din hook-urile de meta
	public static function sync( int $post_id, string $status ): void {
		if ( self::$processing ) return;

		// Previne dubla execuție în același request pentru același post
		if ( isset( self::$synced[ $post_id ] ) ) return;
		self::$synced[ $post_id ] = true;

		self::$processing = true;

		global $wpdb;
		$table = $wpdb->prefix . 'jet_apartment_bookings';

		if ( $status === 'vacation_pending' ) {
			$wpdb->delete( $table, [ 'order_id' => $post_id ], [ '%d' ] );
			self::$processing = false;
			return;
		}

		if ( $status === 'canceled' ) {
			$wpdb->update(
				$table,
				[ 'status' => 'cancelled' ],
				[ 'order_id' => $post_id ],
				[ '%s' ],
				[ '%d' ]
			);
			self::$processing = false;
			return;
		}

		if ( ! in_array( $status, [ 'confirmed', 'pending', 'vacation' ], true ) ) {
			self::$processing = false;
			return;
		}

		$booking_status = self::to_booking_status( $status );
		$check_in       = (int) get_post_meta( $post_id, 'data-evenimentului', true );
		$check_out      = (int) get_post_meta( $post_id, 'data-sfarsit', true );

		if ( ! $check_in ) {
			self::$processing = false;
			return;
		}

		if ( ! $check_out || $check_out < $check_in ) {
			$check_out = $check_in;
		}

		$artist_uid = (int) get_post_field( 'post_author', $post_id );
		$artist_cpt = get_posts( [
			'post_type'      => 'artist',
			'author'         => $artist_uid,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		] );

		if ( empty( $artist_cpt ) ) {
			self::$processing = false;
			return;
		}

		$apartment_id = (int) $artist_cpt[0];

		$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT booking_id FROM {$table} WHERE order_id = %d LIMIT 1",
			$post_id
		) );

		if ( $existing_id ) {
			$wpdb->update(
				$table,
				[
					'status'         => $booking_status,
					'check_in_date'  => $check_in,
					'check_out_date' => $check_out,
					'apartment_id'   => $apartment_id,
				],
				[ 'booking_id' => $existing_id ],
				[ '%s', '%d', '%d', '%d' ],
				[ '%d' ]
			);
		} else {
			$wpdb->insert(
				$table,
				[
					'apartment_id'   => $apartment_id,
					'check_in_date'  => $check_in,
					'check_out_date' => $check_out,
					'status'         => $booking_status,
					'order_id'       => $post_id,
					'user_id'        => $artist_uid,
				],
				[ '%d', '%d', '%d', '%s', '%d', '%d' ]
			);
		}

		self::$processing = false;
	}

	private static function to_booking_status( string $status ): string {
		return match ( $status ) {
			'confirmed' => 'completed',
			'vacation'  => 'on-hold',
			default     => 'pending',
		};
	}
}
