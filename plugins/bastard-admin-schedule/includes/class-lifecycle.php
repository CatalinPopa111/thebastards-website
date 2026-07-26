<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ciclul de viață al cererilor (evenimente).
 *
 * 1. Marcaje de timp la schimbarea statusului:
 *      - status → confirmed : setează `bas_confirmed_at` (dacă lipsește)
 *      - status → canceled  : setează `bas_canceled_at`
 *      - status iese din canceled : șterge `bas_canceled_at` (resetează timerul)
 *
 * 2. Cron zilnic `bas_daily_lifecycle`:
 *      a. pending mai vechi de 7 zile (după data creării) → canceled  (acoperă și
 *         cererile vechi existente la prima rulare — punctul 5)
 *      b. canceled mai vechi de 30 zile (după `bas_canceled_at`) → trash
 *      c. trash mai vechi de 90 zile (după `_wp_trash_meta_time`) → ștergere definitivă
 *
 * 3. Curățare lanț la ștergerea definitivă a unui eveniment (`before_delete_post`):
 *      - relația JetEngine artist↔eveniment din wp4u_jet_rel_default
 *      - marcajul de grup `bas_event_group_id` dacă rămâne un singur membru
 *    (booking-ul este șters de snippet-ul existent pe before_delete_post / wp_trash_post)
 */
class BAS_Lifecycle {

	const CRON_HOOK         = 'bas_daily_lifecycle';
	const CANCEL_AFTER_DAYS = 7;
	const TRASH_AFTER_DAYS  = 30;
	const DELETE_AFTER_DAYS = 90;

	public static function register(): void {
		// Marcaje de timp la schimbarea statusului (prioritate < 20 = înaintea booking sync)
		add_action( 'added_post_meta',   [ __CLASS__, 'on_status_meta' ], 15, 4 );
		add_action( 'updated_post_meta', [ __CLASS__, 'on_status_meta' ], 15, 4 );

		// Curățare lanț la ștergere definitivă
		add_action( 'before_delete_post', [ __CLASS__, 'on_before_delete' ], 5 );

		// Cron
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_daily' ] );
		add_action( 'init',          [ __CLASS__, 'maybe_schedule' ] );
	}

	// ── Programare cron (idempotent) ───────────────────────────────

	public static function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'daily', self::CRON_HOOK );
		}
	}

	// ── Marcaje de timp status ─────────────────────────────────────

	public static function on_status_meta( int $meta_id, int $post_id, string $meta_key, mixed $meta_value ): void {
		if ( 'status-eveniment' !== $meta_key ) {
			return;
		}
		if ( 'evenimente' !== get_post_type( $post_id ) ) {
			return;
		}

		$status = is_string( $meta_value ) ? $meta_value : '';

		if ( 'confirmed' === $status ) {
			if ( ! get_post_meta( $post_id, 'bas_confirmed_at', true ) ) {
				update_post_meta( $post_id, 'bas_confirmed_at', time() );
			}
		}

		if ( 'canceled' === $status ) {
			if ( ! get_post_meta( $post_id, 'bas_canceled_at', true ) ) {
				update_post_meta( $post_id, 'bas_canceled_at', time() );
			}
		} else {
			// Iese din canceled (reactivat manual) → resetează timerul de ștergere
			if ( get_post_meta( $post_id, 'bas_canceled_at', true ) ) {
				delete_post_meta( $post_id, 'bas_canceled_at' );
			}
		}
	}

	// ── Curățare lanț la ștergere definitivă ───────────────────────

	public static function on_before_delete( int $post_id ): void {
		if ( 'evenimente' !== get_post_type( $post_id ) ) {
			return;
		}

		global $wpdb;

		// 1. Relația JetEngine (eveniment = child)
		$wpdb->delete(
			$wpdb->prefix . 'jet_rel_default',
			[
				'rel_id'          => BAS_JET_REL_ARTIST_EVENTS,
				'child_object_id' => $post_id,
			],
			[ '%s', '%d' ]
		);

		// 2. Curățare grup: dacă rămâne un singur membru non-trash, eliminăm marcajul
		$group_id = (int) get_post_meta( $post_id, 'bas_event_group_id', true );
		if ( $group_id > 0 ) {
			$remaining = $wpdb->get_col( $wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
				 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish'
				   AND m.meta_value = %d AND p.ID != %d",
				$group_id,
				$post_id
			) );

			if ( 1 === count( $remaining ) ) {
				delete_post_meta( (int) $remaining[0], 'bas_event_group_id' );
			}
		}
	}

	// ── Cron zilnic ────────────────────────────────────────────────

	public static function run_daily(): void {
		self::auto_cancel_stale_pending();
		self::trash_old_canceled();
		self::delete_old_trashed();
	}

	/**
	 * (a) Cererile client `pending` mai vechi de 7 zile (după data creării) → canceled.
	 * Schimbarea de meta declanșează lanțul existent (booking → cancelled) + marcajul de timp.
	 */
	private static function auto_cancel_stale_pending(): void {
		$cutoff_str = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - self::CANCEL_AFTER_DAYS * DAY_IN_SECONDS );

		$ids = get_posts( [
			'post_type'      => 'evenimente',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => [
				[ 'column' => 'post_date', 'before' => $cutoff_str, 'inclusive' => true ],
			],
			'meta_query'     => [
				[ 'key' => 'status-eveniment', 'value' => 'pending' ],
			],
		] );

		foreach ( $ids as $pid ) {
			update_post_meta( (int) $pid, 'status-eveniment', 'canceled' );
		}
	}

	/**
	 * (b) Evenimentele `canceled` mai vechi de 30 zile (după `bas_canceled_at`) → trash.
	 * Backfill: celor canceled fără marcaj le setăm `bas_canceled_at = acum` (30 zile grație).
	 */
	private static function trash_old_canceled(): void {
		// Backfill marcaj lipsă (nu le trimitem în trash în aceeași rulare)
		$missing = get_posts( [
			'post_type'      => 'evenimente',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'AND',
				[ 'key' => 'status-eveniment', 'value' => 'canceled' ],
				[ 'key' => 'bas_canceled_at', 'compare' => 'NOT EXISTS' ],
			],
		] );
		foreach ( $missing as $pid ) {
			update_post_meta( (int) $pid, 'bas_canceled_at', time() );
		}

		$cutoff = time() - self::TRASH_AFTER_DAYS * DAY_IN_SECONDS;

		$ids = get_posts( [
			'post_type'      => 'evenimente',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'AND',
				[ 'key' => 'status-eveniment', 'value' => 'canceled' ],
				[ 'key' => 'bas_canceled_at', 'value' => $cutoff, 'compare' => '<=', 'type' => 'NUMERIC' ],
			],
		] );

		foreach ( $ids as $pid ) {
			wp_trash_post( (int) $pid );
		}
	}

	/**
	 * (c) Evenimentele din trash mai vechi de 90 zile → ștergere definitivă.
	 */
	private static function delete_old_trashed(): void {
		$cutoff = time() - self::DELETE_AFTER_DAYS * DAY_IN_SECONDS;

		$ids = get_posts( [
			'post_type'      => 'evenimente',
			'post_status'    => 'trash',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[ 'key' => '_wp_trash_meta_time', 'value' => $cutoff, 'compare' => '<=', 'type' => 'NUMERIC' ],
			],
		] );

		foreach ( $ids as $pid ) {
			wp_delete_post( (int) $pid, true );
		}
	}
}
