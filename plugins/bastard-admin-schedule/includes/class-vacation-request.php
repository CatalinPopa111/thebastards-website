<?php
defined( 'ABSPATH' ) || exit;

class BAS_Vacation_Request {

	private static bool $processing = false;

	public static function register(): void {
		// Priority 5 — înaintea snippet-ului de creare booking vacanță (priority 10)
		add_action( 'save_post_evenimente', [ __CLASS__, 'maybe_intercept_vacation' ], 5, 3 );

		// Procesează link-ul Accept / Respinge din email
		add_action( 'init', [ __CLASS__, 'handle_action_url' ] );
	}

	// ── Interceptează cererea de vacanță de la formularul frontend ──

	public static function maybe_intercept_vacation( int $post_id, WP_Post $post, bool $update ): void {
		if ( self::$processing ) return;
		if ( is_admin() ) return;
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) return;

		$status = get_post_meta( $post_id, 'status-eveniment', true );
		if ( $status !== 'vacation' ) return;

		// Deja procesat (are token) → nu mai procesăm
		if ( get_post_meta( $post_id, 'bas_vacation_token', true ) ) return;

		self::$processing = true;

		// Schimbăm în vacation_pending pentru a bloca snippet-ul de booking
		update_post_meta( $post_id, 'status-eveniment', 'vacation_pending' );

		// Token unic pentru link-urile Accept / Respinge
		$token = wp_generate_password( 32, false );
		update_post_meta( $post_id, 'bas_vacation_token', $token );

		// Trimitem emailurile
		BAS_Email_Notifier::send_vacation_request_to_admin( $post_id, $token );
		BAS_Email_Notifier::send_vacation_pending_to_artist( $post_id );

		self::$processing = false;
	}

	// ── Procesează link-ul din email ────────────────────────────────

	public static function handle_action_url(): void {
		if ( empty( $_GET['bas_vacation'] ) ) return;

		$action   = sanitize_key( $_GET['bas_vacation'] );
		$event_id = absint( $_GET['id'] ?? 0 );
		$token    = sanitize_text_field( $_GET['token'] ?? '' );

		if ( ! $event_id || ! $token || ! in_array( $action, [ 'accept', 'reject' ], true ) ) {
			wp_die( self::response_page( '❌ Link invalid.', 'error' ), 'Cerere Vacanță', [ 'response' => 400 ] );
		}

		$post = get_post( $event_id );
		if ( ! $post || $post->post_type !== 'evenimente' ) {
			wp_die( self::response_page( '❌ Evenimentul nu a fost găsit.', 'error' ), 'Cerere Vacanță', [ 'response' => 404 ] );
		}

		// Verifică token
		$stored_token = (string) get_post_meta( $event_id, 'bas_vacation_token', true );
		if ( ! $stored_token || ! hash_equals( $stored_token, $token ) ) {
			wp_die( self::response_page( '❌ Token invalid sau expirat.', 'error' ), 'Cerere Vacanță', [ 'response' => 403 ] );
		}

		// Verifică dacă a fost deja procesată
		$processed = (string) get_post_meta( $event_id, 'bas_vacation_processed', true );
		if ( $processed ) {
			wp_die(
				self::response_page( 'ℹ️ Această cerere a fost deja procesată: <strong>' . esc_html( $processed ) . '</strong>.', 'warning' ),
				'Cerere Vacanță'
			);
		}

		if ( $action === 'accept' ) {
			self::$processing = true;
			self::accept_vacation( $event_id );
			update_post_meta( $event_id, 'bas_vacation_processed', 'acceptat' );
			self::$processing = false;
			wp_die(
				self::response_page( '✅ Cererea de vacanță a fost <strong>ACCEPTATĂ</strong>.<br>Artistul a fost notificat.', 'success' ),
				'Cerere Vacanță'
			);
		} else {
			// Marcăm ca procesată ÎNAINTE de ștergere (pentru dublu-click)
			update_post_meta( $event_id, 'bas_vacation_processed', 'respins' );
			self::reject_vacation( $event_id );
			wp_die(
				self::response_page( '🚫 Cererea de vacanță a fost <strong>RESPINSĂ</strong>.<br>Artistul a fost notificat.', 'error' ),
				'Cerere Vacanță'
			);
		}
	}

	// ── Acceptă vacanța ─────────────────────────────────────────────

	private static function accept_vacation( int $event_id ): void {
		update_post_meta( $event_id, 'status-eveniment', 'vacation' );

		$artist_uid = (int) get_post_field( 'post_author', $event_id );
		$start_ts   = (int) get_post_meta( $event_id, 'data-evenimentului', true );
		$end_ts     = (int) get_post_meta( $event_id, 'data-sfarsit', true ) ?: $start_ts;

		$artist_cpt = get_posts( [
			'post_type'      => 'artist',
			'author'         => $artist_uid,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		] );

		if ( $artist_cpt ) {
			global $wpdb;
			$wpdb->insert(
				$wpdb->prefix . 'jet_apartment_bookings',
				[
					'apartment_id'   => (int) $artist_cpt[0],
					'check_in_date'  => $start_ts,
					'check_out_date' => $end_ts,
					'status'         => 'on-hold',
					'order_id'       => $event_id,
					'user_id'        => $artist_uid,
				],
				[ '%d', '%d', '%d', '%s', '%d', '%d' ]
			);
		}

		BAS_Email_Notifier::send_vacation_approved_to_artist( $event_id );
	}

	// ── Respinge vacanța ────────────────────────────────────────────

	private static function reject_vacation( int $event_id ): void {
		BAS_Email_Notifier::send_vacation_rejected_to_artist( $event_id );
		wp_delete_post( $event_id, true );
	}

	// ── Pagina de răspuns (afișată în browser după clic pe link) ────

	private static function response_page( string $message, string $type ): string {
		$colors = [
			'success' => [ '#0f2d0f', '#2d5a2d', '#6fcf6f' ],
			'warning' => [ '#2d2200', '#5a4500', '#e6c000' ],
			'error'   => [ '#2d0f0f', '#5a2d2d', '#eb5757' ],
		];
		[ $bg, $border, $text ] = $colors[ $type ] ?? $colors['error'];

		return "<!DOCTYPE html>
<html lang='ro'>
<head><meta charset='UTF-8'><title>Cerere Vacanță — The Bastards Agency</title></head>
<body style='margin:0;padding:60px 16px;background:#111;font-family:Arial,sans-serif;text-align:center;'>
  <div style='max-width:480px;margin:0 auto;'>
    <div style='font-size:18px;font-weight:bold;color:#FF6A00;margin-bottom:28px;letter-spacing:-0.3px;'>
      The Bastards Agency
    </div>
    <div style='background:{$bg};border:1px solid {$border};color:{$text};
                padding:28px 24px;border-radius:8px;font-size:15px;line-height:1.7;'>
      {$message}
    </div>
    <p style='color:#444;font-size:12px;margin-top:24px;'>thebastards.ro</p>
  </div>
</body>
</html>";
	}
}
