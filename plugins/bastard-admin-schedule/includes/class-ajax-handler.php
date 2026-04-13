<?php
defined( 'ABSPATH' ) || exit;

class BAS_Ajax_Handler {

	public static function register(): void {
		$actions = [
			'bas_save_schedule',
			'bas_add_location',
			'bas_delete_location',
			'bas_update_event_status',
			'bas_delete_event',
		];

		foreach ( $actions as $action ) {
			$method = str_replace( 'bas_', '', $action );
			add_action( "wp_ajax_{$action}", [ __CLASS__, $method ] );
		}
	}

	// ── Helpers ────────────────────────────────────────────────────

	private static function verify(): void {
		check_ajax_referer( 'bas_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
	}

	// ── Salvează programul săptămânal ──────────────────────────────

	public static function save_schedule(): void {
		self::verify();

		$week     = absint( $_POST['week'] ?? 0 );
		$year     = absint( $_POST['year'] ?? 0 );
		$schedule = $_POST['schedule'] ?? [];

		if ( ! $week || ! $year ) {
			wp_send_json_error( [ 'message' => 'Săptămână invalidă' ] );
		}

		// Sanitizare: chei slug_day, valori post_id
		$clean = [];
		foreach ( (array) $schedule as $key => $artist_id ) {
			$key       = sanitize_key( $key );
			$artist_id = $artist_id ? absint( $artist_id ) : 0;
			if ( $key ) {
				$clean[ $key ] = $artist_id;
			}
		}

		update_option( "bas_schedule_{$year}_W{$week}", $clean, false );
		wp_send_json_success( [ 'message' => 'Program salvat.' ] );
	}

	// ── Adaugă locație ─────────────────────────────────────────────

	public static function add_location(): void {
		self::verify();

		$name = sanitize_text_field( $_POST['name'] ?? '' );
		$city = sanitize_text_field( $_POST['city'] ?? '' );

		if ( ! $name ) {
			wp_send_json_error( [ 'message' => 'Numele locației este obligatoriu.' ] );
		}

		$locations = get_option( 'bas_locations', [] );

		// Generare slug unic
		$base_slug = sanitize_title( $name );
		$slug      = $base_slug;
		$existing_slugs = array_column( $locations, 'slug' );
		$i = 2;
		while ( in_array( $slug, $existing_slugs, true ) ) {
			$slug = $base_slug . '-' . $i++;
		}

		$location = compact( 'name', 'city', 'slug' );
		$locations[] = $location;
		update_option( 'bas_locations', $locations, false );

		wp_send_json_success( [ 'location' => $location ] );
	}

	// ── Șterge locație ─────────────────────────────────────────────

	public static function delete_location(): void {
		self::verify();

		$slug      = sanitize_key( $_POST['slug'] ?? '' );
		$locations = get_option( 'bas_locations', [] );
		$locations = array_values( array_filter( $locations, fn( $l ) => $l['slug'] !== $slug ) );
		update_option( 'bas_locations', $locations, false );

		wp_send_json_success();
	}

	// ── Actualizează status eveniment ──────────────────────────────

	public static function update_event_status(): void {
		self::verify();

		$event_id = absint( $_POST['event_id'] ?? 0 );
		$status   = sanitize_key( $_POST['status'] ?? '' );

		$allowed = [ 'confirmed', 'pending', 'canceled', 'vacation' ];
		if ( ! $event_id || ! in_array( $status, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => 'Date invalide.' ] );
		}

		if ( get_post_type( $event_id ) !== 'evenimente' ) {
			wp_send_json_error( [ 'message' => 'Eveniment negăsit.' ] );
		}

		// wp_update_post declanșează save_post → snippet sync status → bookings → iCal
		wp_update_post( [
			'ID'         => $event_id,
			'meta_input' => [ 'status-eveniment' => $status ],
		] );

		wp_send_json_success( [ 'status' => $status ] );
	}

	// ── Șterge eveniment ───────────────────────────────────────────

	public static function delete_event(): void {
		self::verify();

		$event_id = absint( $_POST['event_id'] ?? 0 );

		if ( ! $event_id || get_post_type( $event_id ) !== 'evenimente' ) {
			wp_send_json_error( [ 'message' => 'Eveniment negăsit.' ] );
		}

		// Ștergere definitivă — snippet-ul de cleanup booking orfan se declanșează automat
		$result = wp_delete_post( $event_id, true );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => 'Ștergerea a eșuat.' ] );
		}

		wp_send_json_success();
	}
}
