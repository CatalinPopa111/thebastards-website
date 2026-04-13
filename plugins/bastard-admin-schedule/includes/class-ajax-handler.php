<?php
defined( 'ABSPATH' ) || exit;

class BAS_Ajax_Handler {

	public static function register(): void {
		$actions = [
			'bas_save_schedule',
			'bas_add_location',
			'bas_delete_location',
			'bas_update_event_status',
			'bas_save_event',
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

		// Sanitizare: chei slug_day, valori user_id
		$clean = [];
		foreach ( (array) $schedule as $key => $artist_id ) {
			$key       = sanitize_key( $key );
			$artist_id = $artist_id ? absint( $artist_id ) : 0;
			if ( $key ) {
				$clean[ $key ] = $artist_id;
			}
		}

		// Programul vechi pentru comparație (ce s-a schimbat)
		$old_schedule = get_option( "bas_schedule_{$year}_W{$week}", [] );

		update_option( "bas_schedule_{$year}_W{$week}", $clean, false );

		// Sincronizăm evenimentele CPT rezidentiat
		self::sync_schedule_events( $year, $week, $clean, $old_schedule );

		wp_send_json_success( [ 'message' => 'Program salvat.' ] );
	}

	// ── Sincronizare evenimente CPT ────────────────────────────────

	private static function sync_schedule_events( int $year, int $week, array $new_schedule, array $old_schedule ): void {
		// Harta locații: slug → date locație
		$locations_data = get_option( 'bas_locations', [] );
		$loc_map        = [];
		foreach ( $locations_data as $loc ) {
			$loc_map[ $loc['slug'] ] = $loc;
		}

		// Harta artiști: user_id → titlu CPT artist
		$artist_posts = get_posts( [
			'post_type'      => 'artist',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		] );
		$artist_names = [];
		foreach ( $artist_posts as $ap ) {
			$artist_names[ $ap->post_author ] = $ap->post_title;
		}

		// Lunea săptămânii (ISO)
		$dt = new DateTime();
		$dt->setISODate( $year, $week, 1 );
		$dt->setTime( 0, 0, 0 );
		$monday_ts = $dt->getTimestamp();

		// Procesăm toate cheile care există în oricare dintre cele două schedule-uri
		$all_keys = array_unique( array_merge( array_keys( $new_schedule ), array_keys( $old_schedule ) ) );

		foreach ( $all_keys as $key ) {
			$new_artist = (int) ( $new_schedule[ $key ] ?? 0 );
			$old_artist = (int) ( $old_schedule[ $key ] ?? 0 );

			if ( $new_artist === $old_artist ) {
				continue; // nicio schimbare pentru acest slot
			}

			// Parsăm cheia: {slug}_{day_index}  (slug nu conține underscore)
			$last_us = strrpos( $key, '_' );
			if ( $last_us === false ) {
				continue;
			}
			$slug      = substr( $key, 0, $last_us );
			$day_index = (int) substr( $key, $last_us + 1 );
			$loc       = $loc_map[ $slug ] ?? null;

			if ( ! $loc ) {
				continue;
			}

			$slot_key = "{$year}_W{$week}_{$key}";
			$day_ts   = $monday_ts + $day_index * DAY_IN_SECONDS;

			// Căutăm evenimentul existent pentru acest slot
			$existing_posts = get_posts( [
				'post_type'      => 'evenimente',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'meta_query'     => [ [
					'key'   => 'bas_slot_key',
					'value' => $slot_key,
				] ],
			] );
			$existing_id = $existing_posts ? $existing_posts[0]->ID : 0;

			// Ștergem evenimentul vechi dacă artistul s-a schimbat sau slotul a fost golit
			if ( $existing_id ) {
				wp_delete_post( $existing_id, true );
				$existing_id = 0;
			}

			// Creăm eveniment nou dacă avem un artist
			if ( $new_artist > 0 ) {
				$loc_name = $loc['name'] ?? '';
				$loc_city = $loc['city'] ?? '';
				$date_str = date_i18n( 'd.m.Y', $day_ts );

				$new_id = wp_insert_post( [
					'post_type'   => 'evenimente',
					'post_status' => 'publish',
					'post_author' => $new_artist,
					'post_title'  => "Club – {$loc_name} – {$date_str}",
					'meta_input'  => [
						'data-evenimentului'    => $day_ts,
						'status-eveniment'      => 'confirmed',
						'tipul-evenimentului'   => 'club',
						'tip-eveniment-intern'  => 'rezidentiat',
						'locatia-evenimentului' => $loc_name,
						'oras-eveniment'        => $loc_city,
						'bas_slot_key'          => $slot_key,
					],
				] );

				// Creare booking manual (Snippet 17 face asta doar pentru vacation;
				// Snippet 9 doar update-ează booking-uri existente, nu creează).
				// Ștergerea e deja acoperită de Snippet 22 via before_delete_post.
				if ( $new_id && ! is_wp_error( $new_id ) ) {
					$artist_cpt = get_posts( [
						'post_type'      => 'artist',
						'author'         => $new_artist,
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'post_status'    => 'publish',
					] );

					if ( $artist_cpt ) {
						global $wpdb;
						$wpdb->insert(
							$wpdb->prefix . 'jet_apartment_bookings',
							[
								'apartment_id'   => (int) $artist_cpt[0], // ID CPT artist (nu user ID)
								'check_in_date'  => $day_ts,
								'check_out_date' => $day_ts,
								'status'         => 'completed', // confirmed → completed în JetBooking
								'order_id'       => $new_id,
								'user_id'        => $new_artist,
							],
							[ '%d', '%d', '%d', '%s', '%d', '%d' ]
						);
					}
				}
			}
		}
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

	// ── Salvează status + detalii eveniment ───────────────────────

	public static function save_event(): void {
		self::verify();

		$event_id = absint( $_POST['event_id'] ?? 0 );
		$status   = sanitize_key( $_POST['status'] ?? '' );
		$raw      = (array) ( $_POST['fields'] ?? [] );

		if ( ! $event_id || get_post_type( $event_id ) !== 'evenimente' ) {
			wp_send_json_error( [ 'message' => 'Eveniment negăsit.' ] );
		}

		$allowed = [ 'confirmed', 'pending', 'canceled', 'vacation' ];
		if ( $status && ! in_array( $status, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => 'Status invalid.' ] );
		}

		$meta_input = [];

		if ( $status ) {
			$meta_input['status-eveniment'] = $status;
		}

		// Validare tip eveniment (whitelist față de valorile acceptate)
		if ( isset( $raw['tip'] ) && $raw['tip'] !== '' ) {
			global $wpdb;
			$glossary = $wpdb->get_row( "SELECT meta_fields FROM {$wpdb->prefix}jet_post_types WHERE id = 3 AND status = 'glossary'" );
			if ( $glossary ) {
				$opts = maybe_unserialize( $glossary->meta_fields );
				$allowed_tips = is_array( $opts ) ? array_column( $opts, 'value' ) : [];
				if ( ! in_array( $raw['tip'], $allowed_tips, true ) ) {
					unset( $raw['tip'] ); // valoare nepermisă, ignorată
				}
			}
		}

		// Câmpuri text simplu
		$text_fields = [
			'ora_inceput'  => 'ora-de-inceput',
			'locatie'      => 'locatia-evenimentului',
			'oras'         => 'oras-eveniment',
			'tip'          => 'tipul-evenimentului',
			'client'       => 'nume-client',
			'telefon'      => 'numar-de-telefon',
			'email'        => 'adresa-de-email',
			'participanti' => 'numar-participanti',
			'sonorizare'   => 'sonorizare-eveniment',
			'durata'       => 'durata-prestatie',
		];
		foreach ( $text_fields as $js_key => $meta_key ) {
			if ( isset( $raw[ $js_key ] ) ) {
				$meta_input[ $meta_key ] = sanitize_text_field( $raw[ $js_key ] );
			}
		}

		// Textarea
		if ( isset( $raw['mesaj'] ) ) {
			$meta_input['mesaj-detalii'] = sanitize_textarea_field( $raw['mesaj'] );
		}

		// Date → timestamp (Snippet 7 verifică is_numeric, nu va interfera)
		if ( ! empty( $raw['data_start'] ) ) {
			$ts = strtotime( sanitize_text_field( $raw['data_start'] ) );
			if ( $ts ) $meta_input['data-evenimentului'] = $ts;
		}
		if ( ! empty( $raw['data_end'] ) ) {
			$ts = strtotime( sanitize_text_field( $raw['data_end'] ) );
			if ( $ts ) $meta_input['data-sfarsit'] = $ts;
		}

		// wp_update_post declanșează save_post → Snippet 8 (titlu), Snippet 9 (booking status)
		wp_update_post( [
			'ID'         => $event_id,
			'meta_input' => $meta_input,
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

		// Reținem slot_key înainte de ștergere (pentru reset calendar în JS)
		$slot_key = (string) get_post_meta( $event_id, 'bas_slot_key', true );

		// Ștergere definitivă — Snippet 22 șterge automat booking-ul asociat
		$result = wp_delete_post( $event_id, true );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => 'Ștergerea a eșuat.' ] );
		}

		// Actualizăm schedula salvată în wp_options pentru săptămâna respectivă
		// (altfel la navigare artistul tot apare în calendar)
		if ( $slot_key && preg_match( '/^(\d+)_W(\d+)_(.+)$/', $slot_key, $m ) ) {
			$opt_year = $m[1];
			$opt_week = $m[2];
			$cal_key  = $m[3]; // {slug}_{day_index}

			$schedule = get_option( "bas_schedule_{$opt_year}_W{$opt_week}", [] );
			if ( isset( $schedule[ $cal_key ] ) ) {
				$schedule[ $cal_key ] = 0;
				update_option( "bas_schedule_{$opt_year}_W{$opt_week}", $schedule, false );
			}
		}

		wp_send_json_success( [ 'slot_key' => $slot_key ] );
	}
}
