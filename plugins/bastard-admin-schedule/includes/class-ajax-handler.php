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
			'bas_create_event',
			'bas_add_artist_to_event',
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

	// ── Relație JetEngine: artist (parent) → eveniment (child) ────

	/**
	 * Inserează relația JetEngine dintre pagina artist și eveniment.
	 *
	 * @param int $artist_cpt_id  ID postare CPT 'artist' (NU user ID)
	 * @param int $event_id       ID postare CPT 'evenimente'
	 */
	private static function insert_jet_relation( int $artist_cpt_id, int $event_id ): void {
		if ( ! $artist_cpt_id || ! $event_id ) {
			return;
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'jet_rel_default',
			[
				'rel_id'           => BAS_JET_REL_ARTIST_EVENTS,
				'parent_rel'       => 0,
				'parent_object_id' => $artist_cpt_id,
				'child_object_id'  => $event_id,
			],
			[ '%s', '%d', '%d', '%d' ]
		);
	}

	/**
	 * Șterge relația JetEngine pentru un eveniment (la schimbare artist sau ștergere).
	 *
	 * @param int $event_id  ID postare CPT 'evenimente'
	 */
	private static function delete_jet_relation( int $event_id ): void {
		if ( ! $event_id ) {
			return;
		}
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'jet_rel_default',
			[
				'rel_id'          => BAS_JET_REL_ARTIST_EVENTS,
				'child_object_id' => $event_id,
			],
			[ '%s', '%d' ]
		);
	}

	// Mapare status eveniment → status JetBooking
	private static function event_to_booking_status( string $status ): string {
		return match ( $status ) {
			'confirmed' => 'completed',
			'canceled'  => 'canceled',
			default     => 'pending',
		};
	}

	// Schimbă artistul unui eveniment: actualizează post_author + booking + relație JetEngine
	private static function do_change_artist( int $event_id, int $new_artist_id ): bool {
		// Găsim CPT artist al noului artist
		$artist_cpt = get_posts( [
			'post_type'      => 'artist',
			'author'         => $new_artist_id,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		] );

		if ( ! $artist_cpt ) {
			return false;
		}

		$artist_cpt_id = (int) $artist_cpt[0]->ID;

		// 1. Actualizăm post_author pe eveniment (asociere user WordPress)
		wp_update_post( [
			'ID'          => $event_id,
			'post_author' => $new_artist_id,
		] );

		// 2. Actualizăm booking-ul: apartment_id (CPT artist ID) + user_id
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'jet_apartment_bookings',
			[
				'apartment_id' => $artist_cpt_id,
				'user_id'      => $new_artist_id,
			],
			[ 'order_id' => $event_id ],
			[ '%d', '%d' ],
			[ '%d' ]
		);

		// 3. Actualizăm relația JetEngine: ștergem pe cea veche, inserăm pe cea nouă
		self::delete_jet_relation( $event_id );
		self::insert_jet_relation( $artist_cpt_id, $event_id );

		return true;
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

				if ( $new_id && ! is_wp_error( $new_id ) ) {
					$artist_cpt = get_posts( [
						'post_type'      => 'artist',
						'author'         => $new_artist,
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'post_status'    => 'publish',
					] );

					if ( $artist_cpt ) {
						$cpt_id = (int) $artist_cpt[0];
						global $wpdb;
						$wpdb->insert(
							$wpdb->prefix . 'jet_apartment_bookings',
							[
								'apartment_id'   => $cpt_id,
								'check_in_date'  => $day_ts,
								'check_out_date' => $day_ts,
								'status'         => 'completed',
								'order_id'       => $new_id,
								'user_id'        => $new_artist,
							],
							[ '%d', '%d', '%d', '%s', '%d', '%d' ]
						);

						// Relație JetEngine: pagina artist (parent) → eveniment (child)
						self::insert_jet_relation( $cpt_id, $new_id );
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
					unset( $raw['tip'] );
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

		// Date → timestamp
		if ( ! empty( $raw['data_start'] ) ) {
			$ts = strtotime( sanitize_text_field( $raw['data_start'] ) );
			if ( $ts ) $meta_input['data-evenimentului'] = $ts;
		}
		if ( ! empty( $raw['data_end'] ) ) {
			$ts = strtotime( sanitize_text_field( $raw['data_end'] ) );
			if ( $ts ) $meta_input['data-sfarsit'] = $ts;
		}

		// ── Schimbare artist (doar pe evenimentul curent, nu se propagă în grup) ──
		$new_artist_id   = absint( $raw['new_artist_id'] ?? 0 );
		$current_author  = (int) get_post_field( 'post_author', $event_id );
		$artist_changed  = false;
		$new_artist_name = '';

		if ( $new_artist_id && $new_artist_id !== $current_author ) {
			$artist_changed = self::do_change_artist( $event_id, $new_artist_id );

			if ( $artist_changed ) {
				$artist_post = get_posts( [
					'post_type'      => 'artist',
					'author'         => $new_artist_id,
					'posts_per_page' => 1,
					'post_status'    => 'publish',
				] );
				$new_artist_name = $artist_post
					? $artist_post[0]->post_title
					: ( get_userdata( $new_artist_id )->display_name ?? '' );
			}
		}

		// ── Citim group_id înainte de update (post_meta va fi actualizat mai jos) ──
		$group_id = (int) get_post_meta( $event_id, 'bas_event_group_id', true );

		// wp_update_post declanșează save_post → Snippet 8 (titlu), Snippet 9 (booking status)
		wp_update_post( [
			'ID'         => $event_id,
			'meta_input' => $meta_input,
		] );

		// ── Sincronizare grup: propagăm statusul și detaliile la toate clonele ──
		if ( ! empty( $meta_input ) ) {
			self::sync_group_meta( $event_id, $group_id, $meta_input );
		}

		wp_send_json_success( [
			'status'          => $status,
			'artist_changed'  => $artist_changed,
			'new_artist_id'   => $artist_changed ? $new_artist_id : 0,
			'new_artist_name' => $new_artist_name,
		] );
	}

	// ── Adaugă un artist suplimentar la un eveniment existent ──────

	public static function add_artist_to_event(): void {
		self::verify();

		$event_id      = absint( $_POST['event_id'] ?? 0 );
		$new_artist_id = absint( $_POST['new_artist_id'] ?? 0 );

		if ( ! $event_id || ! $new_artist_id ) {
			wp_send_json_error( [ 'message' => 'Date invalide.' ] );
		}
		if ( get_post_type( $event_id ) !== 'evenimente' ) {
			wp_send_json_error( [ 'message' => 'Eveniment negăsit.' ] );
		}

		// Nu putem adăuga același artist de două ori în grup
		if ( (int) get_post_field( 'post_author', $event_id ) === $new_artist_id ) {
			wp_send_json_error( [ 'message' => 'Artistul face deja parte din acest eveniment.' ] );
		}

		// Găsim CPT artist pentru noul artist
		$new_artist_cpt = get_posts( [
			'post_type'      => 'artist',
			'author'         => $new_artist_id,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		] );
		if ( ! $new_artist_cpt ) {
			wp_send_json_error( [ 'message' => 'Artist negăsit în sistem.' ] );
		}
		$new_artist_cpt_id = (int) $new_artist_cpt[0]->ID;

		// Determinăm sau creăm group_id
		$group_id = (int) get_post_meta( $event_id, 'bas_event_group_id', true );
		if ( $group_id <= 0 ) {
			$group_id = $event_id; // prima clonă: root-ul devine grup
			update_post_meta( $event_id, 'bas_event_group_id', $group_id );
		}

		// Verificăm că noul artist nu e deja în grup
		global $wpdb;
		$already = $wpdb->get_var( $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
			 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish'
			   AND p.post_author = %d AND m.meta_value = %d
			 LIMIT 1",
			$new_artist_id,
			$group_id
		) );
		if ( $already ) {
			wp_send_json_error( [ 'message' => 'Artistul este deja în grupul acestui eveniment.' ] );
		}

		// Copiem TOATE meta câmpurile evenimentului original (mai puțin slot-key propriu)
		$all_meta    = get_post_meta( $event_id );
		$meta_input  = [ 'bas_event_group_id' => $group_id ];
		$skip_keys   = [ 'bas_slot_key', 'bas_event_group_id' ];

		foreach ( $all_meta as $key => $values ) {
			if ( in_array( $key, $skip_keys, true ) ) continue;
			$meta_input[ $key ] = maybe_unserialize( $values[0] );
		}

		// Creăm postarea clonă pentru noul artist
		$clone_id = wp_insert_post( [
			'post_type'   => 'evenimente',
			'post_status' => 'publish',
			'post_author' => $new_artist_id,
			'meta_input'  => $meta_input,
		] );

		if ( ! $clone_id || is_wp_error( $clone_id ) ) {
			wp_send_json_error( [ 'message' => 'Eroare la crearea clonei.' ] );
		}

		// Booking pentru noul artist
		$check_in     = (int) get_post_meta( $event_id, 'data-evenimentului', true );
		$check_out    = (int) get_post_meta( $event_id, 'data-sfarsit', true ) ?: $check_in;
		$ev_status    = (string) get_post_meta( $event_id, 'status-eveniment', true );

		$wpdb->insert(
			$wpdb->prefix . 'jet_apartment_bookings',
			[
				'apartment_id'   => $new_artist_cpt_id,
				'check_in_date'  => $check_in,
				'check_out_date' => $check_out,
				'status'         => self::event_to_booking_status( $ev_status ?: 'pending' ),
				'order_id'       => $clone_id,
				'user_id'        => $new_artist_id,
			],
			[ '%d', '%d', '%d', '%s', '%d', '%d' ]
		);

		// Relație JetEngine: pagina artist (parent) → eveniment clonă (child)
		self::insert_jet_relation( $new_artist_cpt_id, $clone_id );

		wp_send_json_success( [ 'clone_id' => $clone_id ] );
	}

	// ── Sincronizează meta la toate evenimentele din grup ──────────

	/**
	 * Aplică $meta_input pe toate evenimentele din grup (excepție: evenimentul curent).
	 * Nu se sincronizează new_artist_id (fiecare clonă are artistul ei propriu).
	 */
	private static function sync_group_meta( int $event_id, int $group_id, array $meta_input ): void {
		if ( $group_id <= 0 || empty( $meta_input ) ) {
			return;
		}

		global $wpdb;
		$sibling_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
			 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish'
			   AND m.meta_value = %d AND p.ID != %d",
			$group_id,
			$event_id
		) );

		foreach ( $sibling_ids as $sid ) {
			wp_update_post( [
				'ID'         => (int) $sid,
				'meta_input' => $meta_input,
			] );
		}
	}

	// ── Creează eveniment nou ──────────────────────────────────────

	public static function create_event(): void {
		self::verify();

		$artist_id = absint( $_POST['artist_id'] ?? 0 );
		if ( ! $artist_id ) {
			wp_send_json_error( [ 'message' => 'Artistul este obligatoriu.' ] );
		}

		$status  = sanitize_key( $_POST['status'] ?? 'pending' );
		$allowed = [ 'confirmed', 'pending', 'canceled', 'vacation' ];
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'pending';
		}

		// Verificăm că există un CPT artist pentru acest user
		$artist_cpt = get_posts( [
			'post_type'      => 'artist',
			'author'         => $artist_id,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		] );

		if ( ! $artist_cpt ) {
			wp_send_json_error( [ 'message' => 'Artist negăsit.' ] );
		}

		$raw        = (array) ( $_POST['fields'] ?? [] );
		$meta_input = [ 'status-eveniment' => $status ];

		// Câmpuri text
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
			if ( ! empty( $raw[ $js_key ] ) ) {
				$meta_input[ $meta_key ] = sanitize_text_field( $raw[ $js_key ] );
			}
		}

		if ( ! empty( $raw['mesaj'] ) ) {
			$meta_input['mesaj-detalii'] = sanitize_textarea_field( $raw['mesaj'] );
		}

		// Date → timestamp
		if ( ! empty( $raw['data_start'] ) ) {
			$ts = strtotime( sanitize_text_field( $raw['data_start'] ) );
			if ( $ts ) $meta_input['data-evenimentului'] = $ts;
		}
		if ( ! empty( $raw['data_end'] ) ) {
			$ts = strtotime( sanitize_text_field( $raw['data_end'] ) );
			if ( $ts ) $meta_input['data-sfarsit'] = $ts;
		}

		if ( empty( $meta_input['data-evenimentului'] ) ) {
			wp_send_json_error( [ 'message' => 'Data evenimentului este obligatorie.' ] );
		}

		// Creare post CPT evenimente
		$event_id = wp_insert_post( [
			'post_type'   => 'evenimente',
			'post_status' => 'publish',
			'post_author' => $artist_id,
			'meta_input'  => $meta_input,
		] );

		if ( ! $event_id || is_wp_error( $event_id ) ) {
			wp_send_json_error( [ 'message' => 'Eroare la crearea evenimentului.' ] );
		}

		$artist_cpt_id = (int) $artist_cpt[0]->ID;

		// Creare booking
		global $wpdb;
		$check_in  = (int) $meta_input['data-evenimentului'];
		$check_out = isset( $meta_input['data-sfarsit'] ) ? (int) $meta_input['data-sfarsit'] : $check_in;

		$wpdb->insert(
			$wpdb->prefix . 'jet_apartment_bookings',
			[
				'apartment_id'   => $artist_cpt_id,
				'check_in_date'  => $check_in,
				'check_out_date' => $check_out,
				'status'         => self::event_to_booking_status( $status ),
				'order_id'       => $event_id,
				'user_id'        => $artist_id,
			],
			[ '%d', '%d', '%d', '%s', '%d', '%d' ]
		);

		// Relație JetEngine: pagina artist (parent) → eveniment (child)
		self::insert_jet_relation( $artist_cpt_id, $event_id );

		wp_send_json_success( [
			'event_id' => $event_id,
			'message'  => 'Eveniment creat cu succes.',
		] );
	}

	// ── Șterge eveniment ───────────────────────────────────────────

	public static function delete_event(): void {
		self::verify();

		$event_id = absint( $_POST['event_id'] ?? 0 );

		if ( ! $event_id || get_post_type( $event_id ) !== 'evenimente' ) {
			wp_send_json_error( [ 'message' => 'Eveniment negăsit.' ] );
		}

		// Reținem datele ÎNAINTE de ștergere
		$slot_key = (string) get_post_meta( $event_id, 'bas_slot_key', true );
		$group_id = (int) get_post_meta( $event_id, 'bas_event_group_id', true );

		// Ștergere definitivă — Snippet 22 șterge automat booking-ul asociat
		$result = wp_delete_post( $event_id, true );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => 'Ștergerea a eșuat.' ] );
		}

		// ── Cleanup grup: dacă rămâne un singur eveniment, eliminăm marcajul ──
		if ( $group_id > 0 ) {
			global $wpdb;
			$remaining = $wpdb->get_col( $wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
				 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish'
				   AND m.meta_value = %d AND p.ID != %d",
				$group_id,
				$event_id
			) );

			if ( count( $remaining ) === 1 ) {
				// Ultimul eveniment rămas → nu mai e grup
				delete_post_meta( (int) $remaining[0], 'bas_event_group_id' );
			}
		}

		// Actualizăm schedula salvată în wp_options
		if ( $slot_key && preg_match( '/^(\d+)_W(\d+)_(.+)$/', $slot_key, $m ) ) {
			$opt_year = $m[1];
			$opt_week = $m[2];
			$cal_key  = $m[3];

			$schedule = get_option( "bas_schedule_{$opt_year}_W{$opt_week}", [] );
			if ( isset( $schedule[ $cal_key ] ) ) {
				$schedule[ $cal_key ] = 0;
				update_option( "bas_schedule_{$opt_year}_W{$opt_week}", $schedule, false );
			}
		}

		wp_send_json_success( [ 'slot_key' => $slot_key ] );
	}
}
