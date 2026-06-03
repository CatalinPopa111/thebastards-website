<?php
defined( 'ABSPATH' ) || exit;

class BAS_Email_Notifier {

	private static array $months_ro = [
		1  => 'Ianuarie', 2  => 'Februarie', 3  => 'Martie',
		4  => 'Aprilie',  5  => 'Mai',        6  => 'Iunie',
		7  => 'Iulie',    8  => 'August',     9  => 'Septembrie',
		10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie',
	];

	private static array $days_ro = [
		1 => 'Luni', 2 => 'Marți', 3 => 'Miercuri',
		4 => 'Joi',  5 => 'Vineri', 6 => 'Sâmbătă', 7 => 'Duminică',
	];

	// ═══════════════════════════════════════════════════════════════
	// EMAIL 1 — Program săptămânal
	// ═══════════════════════════════════════════════════════════════

	public static function send_weekly_schedule( int $year, int $week ): void {
		// Interval săptămână
		$dt_mon = new DateTime();
		$dt_mon->setISODate( $year, $week, 1 );
		$dt_mon->setTime( 0, 0, 0 );

		$dt_sun = clone $dt_mon;
		$dt_sun->modify( '+6 days' )->setTime( 23, 59, 59 );

		$mon_ts = $dt_mon->getTimestamp();
		$sun_ts = $dt_sun->getTimestamp();

		// Labels
		$week_short = $dt_mon->format( 'd.m' ) . ' - ' . $dt_sun->format( 'd.m' );
		$week_full  = $dt_mon->format( 'j' ) . ' ' . self::$months_ro[ (int) $dt_mon->format( 'n' ) ]
		            . ' – ' . $dt_sun->format( 'j' ) . ' ' . self::$months_ro[ (int) $dt_sun->format( 'n' ) ]
		            . ' ' . $dt_sun->format( 'Y' );

		// Toate evenimentele săptămânii (fără canceled)
		$posts = get_posts( [
			'post_type'      => 'evenimente',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'data-evenimentului',
			'order'          => 'ASC',
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => 'data-evenimentului',
					'value'   => [ $mon_ts, $sun_ts ],
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => 'status-eveniment',
					'value'   => 'canceled',
					'compare' => '!=',
				],
			],
		] );

		if ( empty( $posts ) ) {
			return;
		}

		// Group map (o singură interogare)
		global $wpdb;
		$grp_rows = $wpdb->get_results(
			"SELECT p.ID as eid, p.post_author as uid, m.meta_value as gid
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
			 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish' AND m.meta_value > 0",
			ARRAY_A
		);
		$event_to_group  = []; // event_id → group_id
		$group_to_events = []; // group_id → [ event_id → user_id ]
		foreach ( $grp_rows as $row ) {
			$eid = (int) $row['eid'];
			$gid = (int) $row['gid'];
			$event_to_group[ $eid ]           = $gid;
			$group_to_events[ $gid ][ $eid ]  = (int) $row['uid'];
		}

		// Artist map: user_id → name, email
		[ $name_map, $email_map ] = self::build_artist_maps();

		// Grupăm evenimentele pe artist
		$by_artist = []; // user_id → [WP_Post]
		foreach ( $posts as $p ) {
			$by_artist[ (int) $p->post_author ][] = $p;
		}

		// Sortăm artiștii alfabetic
		uksort( $by_artist, fn( $a, $b ) => strcmp( $name_map[ $a ] ?? '', $name_map[ $b ] ?? '' ) );

		$subject = "Program Artiști {$week_short}";

		// Email individual per artist
		foreach ( $by_artist as $uid => $artist_events ) {
			$email = $email_map[ $uid ] ?? '';
			if ( ! $email || ! is_email( $email ) ) {
				continue;
			}
			$artist_name = $name_map[ $uid ] ?? "Artist #{$uid}";
			$body = self::build_artist_schedule_html(
				$artist_name, $week_full, $week_short, $uid,
				$artist_events, $event_to_group, $group_to_events, $name_map
			);
			self::send( $email, $subject, $body );
		}

		// Email admin — rezumat complet
		$admin_body = self::build_admin_schedule_html(
			$week_full, $week_short, $by_artist, $name_map,
			$event_to_group, $group_to_events
		);
		self::send( get_option( 'admin_email' ), $subject, $admin_body );
	}

	// ── HTML email individual artist ───────────────────────────────

	private static function build_artist_schedule_html(
		string $artist_name, string $week_full, string $week_short, int $self_uid,
		array $events, array $event_to_group, array $group_to_events, array $name_map
	): string {
		$rows = '';
		foreach ( $events as $ev ) {
			$rows .= self::schedule_row( $ev, $self_uid, $event_to_group, $group_to_events, $name_map );
		}

		$content = "
			<p style='margin:0 0 4px; font-size:13px; color:#FF6A00; font-weight:bold;
			          text-transform:uppercase; letter-spacing:0.5px;'>{$artist_name}</p>
			<h2 style='margin:0 0 24px; font-size:20px; color:#1a1a1a; font-weight:bold;
			           line-height:1.3;'>Program Săptămâna<br>{$week_full}</h2>
			<table width='100%' cellpadding='0' cellspacing='0'>{$rows}</table>
		";

		return self::html_wrapper( $content );
	}

	// ── HTML email admin ───────────────────────────────────────────

	private static function build_admin_schedule_html(
		string $week_full, string $week_short,
		array $by_artist, array $name_map,
		array $event_to_group, array $group_to_events
	): string {
		$blocks = '';
		foreach ( $by_artist as $uid => $events ) {
			$artist_name = esc_html( $name_map[ $uid ] ?? "Artist #{$uid}" );
			$rows = '';
			foreach ( $events as $ev ) {
				$rows .= self::schedule_row( $ev, $uid, $event_to_group, $group_to_events, $name_map );
			}
			$blocks .= "
				<tr>
					<td style='padding:24px 0 6px;'>
						<div style='font-size:11px; font-weight:bold; text-transform:uppercase;
						           letter-spacing:1px; color:#FF6A00; padding-bottom:8px;
						           border-bottom:2px solid #FF6A00;'>{$artist_name}</div>
					</td>
				</tr>
				{$rows}
			";
		}

		$count = array_sum( array_map( 'count', $by_artist ) );
		$content = "
			<h2 style='margin:0 0 4px; font-size:20px; color:#1a1a1a; font-weight:bold;'>
				Program Complet — {$week_full}
			</h2>
			<p style='margin:0 0 24px; color:#888; font-size:13px;'>
				{$count} eveniment(e) &bull; Rezumat administrativ
			</p>
			<table width='100%' cellpadding='0' cellspacing='0'>{$blocks}</table>
		";

		return self::html_wrapper( $content );
	}

	// ── Rând eveniment în tabelul din program ──────────────────────

	private static function schedule_row(
		WP_Post $ev, int $self_uid,
		array $event_to_group, array $group_to_events, array $name_map
	): string {
		$start_ts = (int) get_post_meta( $ev->ID, 'data-evenimentului', true );
		$end_ts   = (int) get_post_meta( $ev->ID, 'data-sfarsit', true );
		$status   = (string) get_post_meta( $ev->ID, 'status-eveniment', true );
		$type     = (string) get_post_meta( $ev->ID, 'tipul-evenimentului', true );
		$location = (string) get_post_meta( $ev->ID, 'locatia-evenimentului', true );
		$city     = (string) get_post_meta( $ev->ID, 'oras-eveniment', true );
		$ora      = (string) get_post_meta( $ev->ID, 'ora-de-inceput', true );

		$dow    = (int) date( 'N', $start_ts );
		$day_ro = self::$days_ro[ $dow ] ?? '';
		$date   = (int) date( 'j', $start_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $start_ts ) ];

		if ( $status === 'vacation' ) {
			$detail = 'Vacanță';
			if ( $end_ts && $end_ts > $start_ts ) {
				$detail .= ' → ' . (int) date( 'j', $end_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $end_ts ) ];
			}
		} else {
			$parts  = array_filter( [ esc_html( $type ), esc_html( $location ), esc_html( $city ) ] );
			$detail = implode( ', ', $parts );
			if ( $ora ) {
				$detail .= ' <span style="color:#888;">/ Ora: ' . esc_html( $ora ) . '</span>';
			}
		}

		// Pending badge
		$status_badge = '';
		if ( $status === 'pending' ) {
			$status_badge = '<span style="display:inline-block; margin-left:8px; padding:1px 8px;
			                             background:#fff8e1; color:#e65100; border:1px solid #ffcc80;
			                             border-radius:10px; font-size:11px; font-weight:bold;">
			                    în așteptare
			                </span>';
		}

		// Grup badge (+N)
		$group_badge = '';
		$gid = $event_to_group[ $ev->ID ] ?? 0;
		if ( $gid > 0 && ! empty( $group_to_events[ $gid ] ) ) {
			$others = array_filter(
				$group_to_events[ $gid ],
				fn( $uid ) => $uid !== $self_uid
			);
			if ( $others ) {
				$other_names = array_map( fn( $uid ) => esc_html( $name_map[ $uid ] ?? "Artist #{$uid}" ), array_keys( $others ) );
				$group_badge = '<span style="display:inline-block; margin-left:8px; padding:1px 8px;
				                            background:#fff3e0; color:#FF6A00; border:1px solid #ffcc80;
				                            border-radius:10px; font-size:11px; font-weight:bold;">
				                   +' . count( $others ) . ' (' . implode( ', ', $other_names ) . ')
				               </span>';
			}
		}

		return "
			<tr>
				<td style='padding:10px 0; border-bottom:1px solid #f2f2f2; font-size:13px;
				           color:#1a1a1a; line-height:1.5;'>
					<strong>{$day_ro}</strong>,&nbsp;{$date}
					&nbsp;—&nbsp;{$detail}
					{$status_badge}{$group_badge}
				</td>
			</tr>
		";
	}

	// ═══════════════════════════════════════════════════════════════
	// EMAIL 2 — Eveniment confirmat
	// ═══════════════════════════════════════════════════════════════

	public static function send_event_confirmed( int $event_id ): void {
		if ( get_post_type( $event_id ) !== 'evenimente' ) {
			return;
		}

		// Datele evenimentului
		$start_ts    = (int)    get_post_meta( $event_id, 'data-evenimentului',    true );
		$end_ts      = (int)    get_post_meta( $event_id, 'data-sfarsit',          true );
		$type        = (string) get_post_meta( $event_id, 'tipul-evenimentului',   true );
		$location    = (string) get_post_meta( $event_id, 'locatia-evenimentului', true );
		$city        = (string) get_post_meta( $event_id, 'oras-eveniment',        true );
		$ora         = (string) get_post_meta( $event_id, 'ora-de-inceput',        true );
		$client      = (string) get_post_meta( $event_id, 'nume-client',           true );
		$telefon     = (string) get_post_meta( $event_id, 'numar-de-telefon',      true );
		$email_cl    = (string) get_post_meta( $event_id, 'adresa-de-email',       true );
		$participanti= (string) get_post_meta( $event_id, 'numar-participanti',    true );
		$sonorizare  = (string) get_post_meta( $event_id, 'sonorizare-eveniment',  true );
		$durata      = (string) get_post_meta( $event_id, 'durata-prestatie',      true );
		$mesaj       = (string) get_post_meta( $event_id, 'mesaj-detalii',         true );

		// Data formatată
		$dow     = (int) date( 'N', $start_ts );
		$day_ro  = self::$days_ro[ $dow ] ?? '';
		$date_ro = (int) date( 'j', $start_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $start_ts ) ] . ' ' . date( 'Y', $start_ts );

		// Artiști (inclusiv din grup)
		$artist_ids = self::get_group_artist_ids( $event_id );
		[ $name_map, $email_map ] = self::build_artist_maps();

		$artist_names_str = implode( ', ', array_map(
			fn( $uid ) => $name_map[ $uid ] ?? "Artist #{$uid}",
			$artist_ids
		) );

		// Subject
		$subject_parts = array_filter( [ 'Eveniment Confirmat', $type, $city, "{$day_ro} {$date_ro}" ] );
		$subject       = implode( ' — ', $subject_parts );

		// Body HTML
		$body = self::build_confirmed_html(
			$type, $day_ro, $date_ro, $ora, $location, $city,
			$artist_names_str, $client, $telefon, $email_cl,
			$participanti, $sonorizare, $durata, $mesaj,
			$start_ts, $end_ts
		);

		// Admin
		self::send( get_option( 'admin_email' ), $subject, $body );

		// Fiecare artist
		foreach ( $artist_ids as $uid ) {
			$email = $email_map[ $uid ] ?? '';
			if ( $email && is_email( $email ) && $email !== get_option( 'admin_email' ) ) {
				self::send( $email, $subject, $body );
			}
		}
	}

	// ── HTML email confirmare ──────────────────────────────────────

	private static function build_confirmed_html(
		string $type, string $day_ro, string $date_ro, string $ora,
		string $location, string $city, string $artist_names,
		string $client, string $telefon, string $email_cl,
		string $participanti, string $sonorizare, string $durata, string $mesaj,
		int $start_ts, int $end_ts
	): string {
		// Rânduri detalii principale
		$det = '';
		if ( $type )     $det .= self::detail_row( 'Tip eveniment', $type );
		$det .= self::detail_row( 'Data', "{$day_ro}, {$date_ro}" );
		if ( $end_ts && $end_ts > $start_ts ) {
			$end_date = (int) date( 'j', $end_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $end_ts ) ] . ' ' . date( 'Y', $end_ts );
			$det .= self::detail_row( 'Data sfârşit', $end_date );
		}
		if ( $ora )      $det .= self::detail_row( 'Ora', $ora );
		if ( $location ) $det .= self::detail_row( 'Locaţie', $location );
		if ( $city )     $det .= self::detail_row( 'Oraş', $city );
		$det .= self::detail_row( 'Artist', $artist_names );

		// Rânduri client
		$cli = '';
		if ( $client )        $cli .= self::detail_row( 'Nume client',     $client );
		if ( $telefon )       $cli .= self::detail_row( 'Telefon',          $telefon );
		if ( $email_cl )      $cli .= self::detail_row( 'Email',            $email_cl );
		if ( $participanti )  $cli .= self::detail_row( 'Nr. participanţi', $participanti );
		if ( $sonorizare )    $cli .= self::detail_row( 'Sonorizare',       $sonorizare );
		if ( $durata )        $cli .= self::detail_row( 'Durată prestaţie', $durata );

		$client_section = $cli ? "
			<tr>
				<td colspan='2' style='padding:20px 0 10px;'>
					<div style='font-size:11px; font-weight:bold; text-transform:uppercase;
					            letter-spacing:1px; color:#aaa; border-top:1px solid #f0f0f0;
					            padding-top:16px;'>Detalii client</div>
				</td>
			</tr>
			{$cli}
		" : '';

		$mesaj_section = $mesaj ? "
			<tr>
				<td colspan='2' style='padding:20px 0 10px;'>
					<div style='font-size:11px; font-weight:bold; text-transform:uppercase;
					            letter-spacing:1px; color:#aaa; border-top:1px solid #f0f0f0;
					            padding-top:16px;'>Note</div>
				</td>
			</tr>
			<tr>
				<td colspan='2' style='padding:8px 0; color:#444; font-size:13px; line-height:1.6;'>
					" . nl2br( esc_html( $mesaj ) ) . "
				</td>
			</tr>
		" : '';

		$title_event = esc_html( $type ?: 'Eveniment' );
		$title_city  = $city ? ' — ' . esc_html( $city ) : '';

		$content = "
			<div style='margin-bottom:28px;'>
				<div style='display:inline-block; background:#e8f5e9; color:#2e7d32;
				            font-size:12px; font-weight:bold; padding:5px 14px;
				            border-radius:20px; margin-bottom:16px;'>
					✓ Eveniment Confirmat
				</div>
				<h2 style='margin:0 0 4px; font-size:22px; color:#1a1a1a; font-weight:bold;'>
					{$title_event}{$title_city}
				</h2>
				<p style='margin:0; color:#888; font-size:14px;'>{$day_ro}, {$date_ro}</p>
			</div>

			<table width='100%' cellpadding='0' cellspacing='0'>
				{$det}
				{$client_section}
				{$mesaj_section}
			</table>
		";

		return self::html_wrapper( $content );
	}

	// ── Rând tabel detalii ─────────────────────────────────────────

	private static function detail_row( string $label, string $value ): string {
		if ( $value === '' ) {
			return '';
		}
		return "
			<tr>
				<td style='padding:9px 16px 9px 0; border-bottom:1px solid #f5f5f5;
				           color:#888; font-size:12px; white-space:nowrap;
				           vertical-align:top; width:130px;'>{$label}</td>
				<td style='padding:9px 0; border-bottom:1px solid #f5f5f5;
				           color:#1a1a1a; font-size:13px; font-weight:500;'>"
				. esc_html( $value ) . "</td>
			</tr>
		";
	}

	// ═══════════════════════════════════════════════════════════════
	// Helpers comune
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Returnează toți user_id ai artiștilor dintr-un grup (sau doar autorul dacă e solo).
	 */
	private static function get_group_artist_ids( int $event_id ): array {
		$group_id = (int) get_post_meta( $event_id, 'bas_event_group_id', true );

		if ( $group_id <= 0 ) {
			return [ (int) get_post_field( 'post_author', $event_id ) ];
		}

		global $wpdb;
		$rows = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT p.post_author
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
			 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish'
			   AND m.meta_value = %d",
			$group_id
		) );

		return array_map( 'intval', $rows );
	}

	/**
	 * Construiește mapele user_id → name și user_id → email din CPT artist.
	 * Returnează [ $name_map, $email_map ].
	 */
	private static function build_artist_maps(): array {
		$posts = get_posts( [
			'post_type'      => 'artist',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		] );

		$name_map  = [];
		$email_map = [];
		foreach ( $posts as $p ) {
			$uid             = (int) $p->post_author;
			$name_map[ $uid ]  = $p->post_title;
			$ud = get_userdata( $uid );
			if ( $ud ) {
				$email_map[ $uid ] = $ud->user_email;
			}
		}

		return [ $name_map, $email_map ];
	}

	// ═══════════════════════════════════════════════════════════════
	// EMAIL-uri cerere vacanță
	// ═══════════════════════════════════════════════════════════════

	// ── Email admin: cerere nouă cu butoane Accept / Respinge ──────

	public static function send_vacation_request_to_admin( int $event_id, string $token ): void {
		$artist_uid = (int) get_post_field( 'post_author', $event_id );
		$start_ts   = (int) get_post_meta( $event_id, 'data-evenimentului', true );
		$end_ts     = (int) get_post_meta( $event_id, 'data-sfarsit',       true );

		[ $name_map, $email_map ] = self::build_artist_maps();
		$artist_name = $name_map[ $artist_uid ] ?? "Artist #{$artist_uid}";

		$date_start = (int) date( 'j', $start_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $start_ts ) ] . ' ' . date( 'Y', $start_ts );
		$date_end   = $end_ts && $end_ts > $start_ts
			? ' → ' . (int) date( 'j', $end_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $end_ts ) ] . ' ' . date( 'Y', $end_ts )
			: '';

		// Săptămâna vacanței
		$dt = new DateTime();
		$dt->setTimestamp( $start_ts );
		$year = (int) $dt->format( 'o' );
		$week = (int) $dt->format( 'W' );

		// Link-uri Accept / Respinge
		$base_url    = home_url( '/' );
		$accept_url  = add_query_arg( [ 'bas_vacation' => 'accept', 'id' => $event_id, 'token' => $token ], $base_url );
		$reject_url  = add_query_arg( [ 'bas_vacation' => 'reject', 'id' => $event_id, 'token' => $token ], $base_url );

		$btn_accept = "<a href='" . esc_url( $accept_url ) . "'
			style='display:inline-block;background:#2a6e2a;color:#fff;text-decoration:none;
			       padding:12px 28px;border-radius:8px;font-weight:bold;font-size:14px;
			       font-family:Arial,sans-serif;margin-right:12px;'>
			✅ Acceptă vacanța
		</a>";
		$reject_btn = "<a href='" . esc_url( $reject_url ) . "'
			style='display:inline-block;background:#8b1a1a;color:#fff;text-decoration:none;
			       padding:12px 28px;border-radius:8px;font-weight:bold;font-size:14px;
			       font-family:Arial,sans-serif;'>
			🚫 Respinge cererea
		</a>";

		// Evenimente din perioada vacanței ±2 zile, grupate pe zile
		$context_from = strtotime( 'midnight', $start_ts ) - 2 * DAY_IN_SECONDS;
		$context_to   = strtotime( 'midnight', $end_ts ?: $start_ts ) + 2 * DAY_IN_SECONDS + DAY_IN_SECONDS - 1;
		$period_html  = self::build_period_events_html( $context_from, $context_to, $name_map );

		$context_label = date( 'j', $context_from ) . ' ' . self::$months_ro[ (int) date( 'n', $context_from ) ]
		               . ' – ' . date( 'j', $context_to ) . ' ' . self::$months_ro[ (int) date( 'n', $context_to ) ]
		               . ' ' . date( 'Y', $context_to );

		$content = "
			<div style='margin-bottom:28px;'>
				<div style='display:inline-block;background:#fff8e1;color:#e65100;
				            font-size:12px;font-weight:bold;padding:5px 14px;
				            border-radius:20px;margin-bottom:16px;'>
					🏖 Cerere Vacanță Nouă
				</div>
				<h2 style='margin:0 0 6px;font-size:20px;color:#1a1a1a;font-weight:bold;'>
					" . esc_html( $artist_name ) . "
				</h2>
				<p style='margin:0;color:#555;font-size:14px;'>
					" . esc_html( $date_start . $date_end ) . "
				</p>
			</div>

			<div style='margin:28px 0;text-align:center;padding:20px;
			            background:#f9f9f9;border-radius:8px;'>
				{$btn_accept}
				{$reject_btn}
			</div>

			<div style='margin-top:32px;'>
				<div style='font-size:11px;font-weight:bold;text-transform:uppercase;
				            letter-spacing:1px;color:#aaa;border-top:1px solid #f0f0f0;
				            padding-top:16px;margin-bottom:12px;'>
					Evenimente în perioada {$context_label}
				</div>
				{$period_html}
			</div>
		";

		$subject = '🏖 Cerere vacanță — ' . $artist_name . ' — ' . $date_start;
		self::send( get_option( 'admin_email' ), $subject, self::html_wrapper( $content ) );
	}

	// ── Email artist: cerere în așteptare ──────────────────────────

	public static function send_vacation_pending_to_artist( int $event_id ): void {
		$artist_uid  = (int) get_post_field( 'post_author', $event_id );
		$start_ts    = (int) get_post_meta( $event_id, 'data-evenimentului', true );
		$end_ts      = (int) get_post_meta( $event_id, 'data-sfarsit', true );
		$ud          = get_userdata( $artist_uid );

		if ( ! $ud || ! is_email( $ud->user_email ) ) return;

		$date_start = (int) date( 'j', $start_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $start_ts ) ] . ' ' . date( 'Y', $start_ts );
		$date_end   = $end_ts && $end_ts > $start_ts
			? ' – ' . (int) date( 'j', $end_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $end_ts ) ] . ' ' . date( 'Y', $end_ts )
			: '';

		$content = "
			<div style='display:inline-block;background:#fff8e1;color:#e65100;
			            font-size:12px;font-weight:bold;padding:5px 14px;
			            border-radius:20px;margin-bottom:20px;'>
				⏳ În așteptare
			</div>
			<h2 style='margin:0 0 12px;font-size:20px;color:#1a1a1a;font-weight:bold;'>
				Cererea ta de vacanță a fost trimisă
			</h2>
			<p style='margin:0 0 20px;color:#444;font-size:14px;line-height:1.6;'>
				Data solicitată: <strong>" . esc_html( $date_start . $date_end ) . "</strong>
			</p>
			<p style='margin:0;color:#888;font-size:13px;line-height:1.6;
			          background:#f9f9f9;padding:14px 16px;border-radius:6px;'>
				Cererea urmează să fie aprobată de administrator. <strong>Nu trimite o solicitare nouă</strong> pentru aceeași perioadă.
			</p>
		";

		$subject = '⏳ Cerere vacanță trimisă — ' . $date_start;
		self::send( $ud->user_email, $subject, self::html_wrapper( $content ) );
	}

	// ── Email artist: vacanță aprobată ─────────────────────────────

	public static function send_vacation_approved_to_artist( int $event_id ): void {
		$artist_uid = (int) get_post_field( 'post_author', $event_id );
		$start_ts   = (int) get_post_meta( $event_id, 'data-evenimentului', true );
		$end_ts     = (int) get_post_meta( $event_id, 'data-sfarsit', true );
		$ud         = get_userdata( $artist_uid );

		if ( ! $ud || ! is_email( $ud->user_email ) ) return;

		$date_start = (int) date( 'j', $start_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $start_ts ) ] . ' ' . date( 'Y', $start_ts );
		$date_end   = $end_ts && $end_ts > $start_ts
			? ' – ' . (int) date( 'j', $end_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $end_ts ) ] . ' ' . date( 'Y', $end_ts )
			: '';

		$content = "
			<div style='display:inline-block;background:#e8f5e9;color:#2e7d32;
			            font-size:12px;font-weight:bold;padding:5px 14px;
			            border-radius:20px;margin-bottom:20px;'>
				✅ Vacanță Aprobată
			</div>
			<h2 style='margin:0 0 12px;font-size:20px;color:#1a1a1a;font-weight:bold;'>
				Cererea ta de vacanță a fost aprobată
			</h2>
			<p style='margin:0;color:#444;font-size:14px;line-height:1.6;'>
				Perioada aprobată: <strong>" . esc_html( $date_start . $date_end ) . "</strong>
			</p>
		";

		$subject = '✅ Vacanță aprobată — ' . $date_start;
		self::send( $ud->user_email, $subject, self::html_wrapper( $content ) );
	}

	// ── Email artist: vacanță respinsă ─────────────────────────────

	public static function send_vacation_rejected_to_artist( int $event_id ): void {
		$artist_uid = (int) get_post_field( 'post_author', $event_id );
		$start_ts   = (int) get_post_meta( $event_id, 'data-evenimentului', true );
		$ud         = get_userdata( $artist_uid );

		if ( ! $ud || ! is_email( $ud->user_email ) ) return;

		$date_start = (int) date( 'j', $start_ts ) . ' ' . self::$months_ro[ (int) date( 'n', $start_ts ) ] . ' ' . date( 'Y', $start_ts );

		$content = "
			<div style='display:inline-block;background:#fce4e4;color:#c62828;
			            font-size:12px;font-weight:bold;padding:5px 14px;
			            border-radius:20px;margin-bottom:20px;'>
				🚫 Cerere Respinsă
			</div>
			<h2 style='margin:0 0 12px;font-size:20px;color:#1a1a1a;font-weight:bold;'>
				Cererea ta de vacanță nu a putut fi aprobată
			</h2>
			<p style='margin:0 0 16px;color:#444;font-size:14px;line-height:1.6;'>
				Data solicitată: <strong>" . esc_html( $date_start ) . "</strong>
			</p>
			<p style='margin:0;color:#888;font-size:13px;line-height:1.6;
			          background:#f9f9f9;padding:14px 16px;border-radius:6px;'>
				Pentru mai multe detalii, contactează administratorul.
			</p>
		";

		$subject = '🚫 Cerere vacanță respinsă — ' . $date_start;
		self::send( $ud->user_email, $subject, self::html_wrapper( $content ) );
	}

	// ── Helper: HTML-ul cu toate evenimentele dintr-o săptămână ────

	// ── Evenimente pe perioadă, grupate pe zile ───────────────────
	private static function build_period_events_html( int $from_ts, int $to_ts, array $name_map ): string {
		$posts = get_posts( [
			'post_type'      => 'evenimente',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'data-evenimentului',
			'order'          => 'ASC',
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => 'data-evenimentului',
					'value'   => [ $from_ts, $to_ts ],
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => 'status-eveniment',
					'value'   => [ 'canceled', 'vacation_pending' ],
					'compare' => 'NOT IN',
				],
			],
		] );

		if ( empty( $posts ) ) {
			return '<p style="color:#888;font-size:13px;">Niciun eveniment în această perioadă.</p>';
		}

		// Grupăm pe zile (YYYY-MM-DD)
		$by_day = [];
		foreach ( $posts as $p ) {
			$ts  = (int) get_post_meta( $p->ID, 'data-evenimentului', true );
			$day = date( 'Y-m-d', $ts );
			$by_day[ $day ][] = $p;
		}
		ksort( $by_day );

		$html = "<table width='100%' cellpadding='0' cellspacing='0'>";

		foreach ( $by_day as $day_str => $events ) {
			$day_ts   = strtotime( $day_str );
			$dow      = (int) date( 'N', $day_ts ); // 1=Luni … 7=Duminică
			$day_name = self::$days_ro[ $dow ] ?? '';
			$day_num  = (int) date( 'j', $day_ts );
			$month    = self::$months_ro[ (int) date( 'n', $day_ts ) ] ?? '';

			$html .= "
				<tr>
					<td style='padding:14px 0 4px;'>
						<div style='font-size:12px;font-weight:bold;text-transform:uppercase;
						            letter-spacing:0.5px;color:#FF6A00;border-bottom:1px solid #FF6A00;
						            padding-bottom:5px;'>
							{$day_name}, {$day_num} " . strtolower( $month ) . "
						</div>
					</td>
				</tr>
			";

			foreach ( $events as $ev ) {
				$uid    = (int) $ev->post_author;
				$artist = esc_html( $name_map[ $uid ] ?? "Artist #{$uid}" );
				$tip    = (string) get_post_meta( $ev->ID, 'tipul-evenimentului', true );
				$loc    = (string) get_post_meta( $ev->ID, 'locatia-evenimentului', true );
				$oras   = (string) get_post_meta( $ev->ID, 'oras-eveniment', true );
				$ora    = (string) get_post_meta( $ev->ID, 'ora-de-inceput', true );

				$parts = array_filter( [ $tip, $loc, $oras, $ora ] );
				$detail = $parts ? implode( ', ', $parts ) : '—';

				$html .= "
					<tr>
						<td style='padding:5px 0 5px 12px;font-size:13px;color:#333;border-bottom:1px solid #f0f0f0;'>
							<strong>{$artist}</strong> — " . esc_html( $detail ) . "
						</td>
					</tr>
				";
			}
		}

		$html .= "</table>";
		return $html;
	}

	private static function build_week_events_html( int $year, int $week, array $name_map ): string {
		$dt_mon = new DateTime();
		$dt_mon->setISODate( $year, $week, 1 );
		$dt_mon->setTime( 0, 0, 0 );
		$dt_sun = clone $dt_mon;
		$dt_sun->modify( '+6 days' )->setTime( 23, 59, 59 );

		$posts = get_posts( [
			'post_type'      => 'evenimente',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'data-evenimentului',
			'order'          => 'ASC',
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => 'data-evenimentului',
					'value'   => [ $dt_mon->getTimestamp(), $dt_sun->getTimestamp() ],
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => 'status-eveniment',
					'value'   => [ 'canceled', 'vacation_pending' ],
					'compare' => 'NOT IN',
				],
			],
		] );

		if ( empty( $posts ) ) {
			return '<p style="color:#888;font-size:13px;">Niciun eveniment în această săptămână.</p>';
		}

		$by_artist = [];
		foreach ( $posts as $p ) {
			$by_artist[ (int) $p->post_author ][] = $p;
		}
		uksort( $by_artist, fn( $a, $b ) => strcmp( $name_map[ $a ] ?? '', $name_map[ $b ] ?? '' ) );

		global $wpdb;
		$grp_rows = $wpdb->get_results(
			"SELECT p.ID as eid, p.post_author as uid, m.meta_value as gid
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
			 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish' AND m.meta_value > 0",
			ARRAY_A
		);
		$event_to_group  = [];
		$group_to_events = [];
		foreach ( $grp_rows as $row ) {
			$eid = (int) $row['eid'];
			$gid = (int) $row['gid'];
			$event_to_group[ $eid ]          = $gid;
			$group_to_events[ $gid ][ $eid ] = (int) $row['uid'];
		}

		$blocks = '';
		foreach ( $by_artist as $uid => $events ) {
			$artist_name = esc_html( $name_map[ $uid ] ?? "Artist #{$uid}" );
			$rows = '';
			foreach ( $events as $ev ) {
				$rows .= self::schedule_row( $ev, $uid, $event_to_group, $group_to_events, $name_map );
			}
			$blocks .= "
				<tr>
					<td style='padding:16px 0 4px;'>
						<div style='font-size:11px;font-weight:bold;text-transform:uppercase;
						            letter-spacing:1px;color:#FF6A00;border-bottom:2px solid #FF6A00;
						            padding-bottom:6px;'>{$artist_name}</div>
					</td>
				</tr>
				{$rows}
			";
		}

		return "<table width='100%' cellpadding='0' cellspacing='0'>{$blocks}</table>";
	}

	// ── HTML wrapper email ─────────────────────────────────────────

	public static function html_wrapper( string $content ): string {
		return '<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f2f2f2; font-family:Arial,Helvetica,sans-serif; -webkit-font-smoothing:antialiased;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f2f2f2; padding:32px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0"
               style="background:#ffffff; border-radius:8px; overflow:hidden;
                      box-shadow:0 2px 12px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:#111111; padding:22px 32px;">
              <span style="color:#FF6A00; font-size:18px; font-weight:bold;
                           letter-spacing:-0.3px; font-family:Arial,sans-serif;">
                The Bastards Agency
              </span>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px;">
              ' . $content . '
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f9f9f9; padding:16px 32px;
                       border-top:1px solid #eeeeee;">
              <span style="color:#bbb; font-size:11px;">
                The Bastards Agency &bull; <a href="https://thebastards.ro"
                style="color:#bbb; text-decoration:none;">thebastards.ro</a>
              </span>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
	}

	// ── Trimitere email HTML ───────────────────────────────────────

	public static function send( string $to, string $subject, string $html ): bool {
		if ( ! $to || ! is_email( $to ) ) {
			return false;
		}
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: The Bastards Agency <noreply@thebastards.ro>',
		];
		return wp_mail( $to, $subject, $html, $headers );
	}
}
