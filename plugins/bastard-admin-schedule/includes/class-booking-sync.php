<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sincronizează wp4u_jet_apartment_bookings cu status-eveniment la fiecare save.
 *
 * Înlocuiește Snippet 9 (updated_post_meta sync) și Snippet 17 (vacation booking).
 * Rulează la priority 20, după Snippet 7 (conversie dată→timestamp, priority 10 default).
 */
class BAS_Booking_Sync {

	private static bool $processing = false;

	public static function register(): void {
		add_action( 'save_post_evenimente', [ __CLASS__, 'sync' ], 20, 1 );
	}

	public static function sync( int $post_id ): void {
		if ( self::$processing ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( wp_is_post_autosave( $post_id ) ) return;

		$status = (string) get_post_meta( $post_id, 'status-eveniment', true );
		if ( ! $status ) return;

		self::$processing = true;

		global $wpdb;
		$table = $wpdb->prefix . 'jet_apartment_bookings';

		if ( $status === 'vacation_pending' ) {
			// Data rămâne liberă până la aprobarea adminului
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
