<?php
defined( 'ABSPATH' ) || exit;

class BAS_Artist_Events {

	public static function register(): void {
		add_shortcode( 'bas_lista_evenimente', [ __CLASS__, 'render_shortcode' ] );
		add_action( 'wp_ajax_bas_artist_delete_vacation', [ __CLASS__, 'delete_vacation' ] );
	}

	// ── Shortcode [bas_lista_evenimente] ──────────────────────────────

	public static function render_shortcode(): string {
		if ( ! is_user_logged_in() ) return '';

		$user_id   = get_current_user_id();
		$today_ts  = strtotime( 'today midnight' );
		$nonce     = wp_create_nonce( 'bas_artist_events_nonce' );
		$ajax_url  = admin_url( 'admin-ajax.php' );

		$out = self::render_weekly_view( $user_id );

		global $wpdb;

		$events = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_author, p.post_title,
			  m_data.meta_value   AS data_start,
			  m_status.meta_value AS status,
			  m_tip.meta_value    AS tip,
			  m_loc.meta_value    AS locatie,
			  m_oras.meta_value   AS oras,
			  m_ora.meta_value    AS ora_inceput,
			  m_client.meta_value  AS client,
			  m_sfarsit.meta_value AS data_sfarsit
			FROM {$wpdb->posts} p
			JOIN  {$wpdb->postmeta} m_data    ON m_data.post_id    = p.ID AND m_data.meta_key    = 'data-evenimentului'
			JOIN  {$wpdb->postmeta} m_status  ON m_status.post_id  = p.ID AND m_status.meta_key  = 'status-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_tip     ON m_tip.post_id     = p.ID AND m_tip.meta_key     = 'tipul-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_loc     ON m_loc.post_id     = p.ID AND m_loc.meta_key     = 'locatia-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_oras    ON m_oras.post_id    = p.ID AND m_oras.meta_key    = 'oras-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_ora     ON m_ora.post_id     = p.ID AND m_ora.meta_key     = 'ora-de-inceput'
			LEFT JOIN {$wpdb->postmeta} m_client  ON m_client.post_id  = p.ID AND m_client.meta_key  = 'nume-client'
			LEFT JOIN {$wpdb->postmeta} m_sfarsit ON m_sfarsit.post_id = p.ID AND m_sfarsit.meta_key = 'data-sfarsit'
			WHERE p.post_type   = 'evenimente'
			  AND p.post_status = 'publish'
			  AND p.post_author = %d
			  AND CAST(m_data.meta_value AS UNSIGNED) >= %d
			  AND m_status.meta_value IN ('confirmed','pending','vacation','vacation_pending','canceled')
			ORDER BY CAST(m_data.meta_value AS UNSIGNED) ASC",
			$user_id,
			$today_ts
		), ARRAY_A );

		if ( empty( $events ) ) {
			return $out . '<p style="color:#888;font-style:italic;">Nu există evenimente viitoare.</p>';
		}

		ob_start();
		?>
		<style>
		.bas-el-list{font-family:Inter,sans-serif;font-size:14px;border-top:1px solid #1e1e1e;}
		.bas-el-item{display:flex;align-items:center;padding:10px 2px;border-bottom:1px solid #1e1e1e;gap:10px;}
		.bas-el-body{flex:1;min-width:0;}
		.bas-el-l1{display:flex;flex-wrap:wrap;gap:3px 7px;align-items:baseline;font-size:14px;}
		.bas-el-l2{display:flex;flex-wrap:wrap;gap:3px 7px;align-items:baseline;font-size:12px;margin-top:4px;opacity:.8;}
		.bas-el-date{font-weight:600;white-space:nowrap;}
		.bas-el-dot{color:#3a3a3a;}
		.bas-el-right{display:flex;flex-direction:column;align-items:flex-end;justify-content:center;gap:5px;flex-shrink:0;min-width:0;}
		.bas-el-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;white-space:nowrap;text-align:center;}
		.bas-el-year{text-align:center;padding:8px 0;color:#555;font-size:11px;letter-spacing:2px;border-top:1px solid #1e1e1e;border-bottom:1px solid #1e1e1e;margin:2px 0;}
		.bas-btn-del-ev{background:#2a0a0a;color:#eb5757;border:1px solid #5a2020;padding:3px 10px;border-radius:4px;cursor:pointer;font-size:11px;white-space:nowrap;}
		</style>
		<div class="bas-el-list">
		<?php
		$current_year = null;

		foreach ( $events as $event ) {
			$ts   = (int) $event['data_start'];
			$year = date( 'Y', $ts );

			if ( $year !== $current_year ) {
				if ( $current_year !== null ) {
					echo '<div class="bas-el-year">── ' . esc_html( $year ) . ' ──</div>';
				}
				$current_year = $year;
			}

			$status     = $event['status'];
			$can_delete = in_array( $status, [ 'vacation', 'vacation_pending' ], true )
			              && (int) $event['post_author'] === $user_id;

			[ $badge_label, $badge_color ] = self::status_badge( $status );
			$row_color   = self::row_style( $status );
			$strike      = $status === 'canceled' ? 'text-decoration:line-through;' : '';

			// Dată afișată — pentru vacanțe cu perioadă, afișăm intervalul
			$end_ts   = (int) ( $event['data_sfarsit'] ?? 0 );
			$is_vac   = in_array( $status, [ 'vacation', 'vacation_pending' ], true );
			if ( $is_vac && $end_ts && $end_ts > $ts ) {
				$date_fmt = date_i18n( 'd M', $ts ) . ' → ' . date_i18n( 'd M Y', $end_ts );
			} else {
				$date_fmt = date_i18n( 'd M Y', $ts );
			}
			// Pentru vacanțe, tipul e gol — folosim titlul postării (ce a scris artistul)
			$tip      = $event['tip'] ?: ( $is_vac ? $event['post_title'] : '' );
			$client   = $event['client']  ?: '';
			$locatie  = $event['locatie'] ?: '';
			$oras     = $event['oras']    ?: '';
			$ora      = $event['ora_inceput'] ?: '';

			// Rând 1: dată · tip (sau titlu vacanță) · client (dacă există)
			$l1 = array_filter( [ $date_fmt, $tip, $client ] );
			// Rând 2: locație · oraș · oră (dacă există)
			$l2 = array_filter( [ $locatie, $oras, $ora ] );

			echo '<div class="bas-el-item" data-event-id="' . esc_attr( $event['ID'] ) . '">';

			// Corp stânga
			echo '<div class="bas-el-body" style="' . $row_color . $strike . '">';

			echo '<div class="bas-el-l1">';
			$first = true;
			foreach ( $l1 as $part ) {
				if ( ! $first ) echo '<span class="bas-el-dot">·</span>';
				$cls = $first ? ' class="bas-el-date"' : '';
				echo '<span' . $cls . '>' . esc_html( $part ) . '</span>';
				$first = false;
			}
			echo '</div>';

			if ( $l2 ) {
				echo '<div class="bas-el-l2">';
				$first = true;
				foreach ( $l2 as $part ) {
					if ( ! $first ) echo '<span class="bas-el-dot">·</span>';
					echo '<span>' . esc_html( $part ) . '</span>';
					$first = false;
				}
				echo '</div>';
			}

			echo '</div>'; // .bas-el-body

			// Dreapta: badge + buton ștergere
			echo '<div class="bas-el-right">';
			echo '<span class="bas-el-badge" style="' . esc_attr( $badge_color ) . '">' . esc_html( $badge_label ) . '</span>';
			if ( $can_delete ) {
				echo '<button class="bas-btn-del-ev bas-delete-vacation" data-id="' . esc_attr( $event['ID'] ) . '">Șterge</button>';
			}
			echo '</div>'; // .bas-el-right

			echo '</div>'; // .bas-el-item
		}
		?>
		</div>

		<script>
		(function() {
			var nonce   = <?php echo wp_json_encode( $nonce ); ?>;
			var ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;

			document.querySelectorAll('.bas-delete-vacation').forEach(function(btn) {
				btn.addEventListener('click', function() {
					var eventId = btn.dataset.id;
					if (!confirm('Sigur vrei să ștergi această vacanță?')) return;

					btn.disabled = true;
					btn.textContent = '...';

					var body = new FormData();
					body.append('action', 'bas_artist_delete_vacation');
					body.append('nonce', nonce);
					body.append('event_id', eventId);

					fetch(ajaxUrl, { method: 'POST', body: body })
						.then(function(r) { return r.json(); })
						.then(function(json) {
							if (json.success) {
								var row = document.querySelector('[data-event-id="' + eventId + '"]');
								if (row) row.remove();
							} else {
								alert(json.data && json.data.message ? json.data.message : 'Eroare la ștergere.');
								btn.disabled = false;
								btn.textContent = 'Șterge';
							}
						})
						.catch(function() {
							alert('Eroare de rețea.');
							btn.disabled = false;
							btn.textContent = 'Șterge';
						});
				});
			});
		})();
		</script>
		<?php
		// Înregistrăm popup-ul 1956 în locația „popup" a Elementor Pro.
		// Astfel print_popups() (wp_footer) îl printează nativ în DOM — cu toată
		// structura, CSS-ul, JS-ul și formularul JetEngine — exact ca un widget
		// Elementor cu acțiune popup. Fără asta, popup-ul nu există în pagină și
		// link-ul #elementor-action nu are ce deschide.
		if ( class_exists( '\ElementorPro\Modules\Popup\Module' ) ) {
			\ElementorPro\Modules\Popup\Module::add_popup_to_location( 1956 );
		}

		// Link nativ Elementor: handler-ul delegat de pe document (selector
		// a[href^="#elementor-action"]) declanșează acțiunea popup:open pentru ID 1956.
		$popup_link = '#elementor-action:action=popup:open&settings=eyJpZCI6IjE5NTYiLCJ0b2dnbGUiOmZhbHNlfQ==';

		$btn = '<div style="height:25px;"></div>'
		     . '<div style="text-align:center;">'
		     .   '<a id="bas-btn-blocheaza-date" href="' . esc_attr( $popup_link ) . '"'
		     .     ' style="display:inline-block;background:#FF6A00;color:#fff;font-family:Inter,sans-serif;'
		     .            'font-weight:600;font-size:14px;padding:11px 28px;border-radius:10px;'
		     .            'text-decoration:none;letter-spacing:.3px;">'
		     .     'Blochează Date'
		     .   '</a>'
		     . '</div>'
		     . '<div style="height:25px;"></div>';

		return $out . $btn . ob_get_clean();
	}

	// ── Program săptămânal artist ─────────────────────────────────────

	private static function current_week_monday(): int {
		$n = (int) date( 'N' ); // 1 = Lun, 7 = Dum
		return strtotime( '-' . ( $n - 1 ) . ' days', strtotime( 'today midnight' ) );
	}

	private static function get_week_days( int $offset ): array {
		$monday    = strtotime( "+{$offset} weeks", self::current_week_monday() );
		$today     = strtotime( 'today midnight' );
		$day_names = [ 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sâm', 'Dum' ];
		$months    = [ 1 => 'Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
		$days      = [];

		for ( $i = 0; $i < 7; $i++ ) {
			$ts     = strtotime( "+{$i} days", $monday );
			$day_n  = (int) date( 'j', $ts );
			$mon_n  = (int) date( 'n', $ts );
			$year_n = (int) date( 'Y', $ts );

			$days[] = [
				'date'        => date( 'Y-m-d', $ts ),
				'ts'          => $ts,
				'col_label'   => $day_names[ $i ] . ' ' . $day_n,
				'label_short' => $day_n . ' ' . $months[ $mon_n ],
				'label_full'  => $day_n . ' ' . $months[ $mon_n ] . ' ' . $year_n,
				'is_today'    => $ts === $today,
			];
		}

		return $days;
	}

	private static function render_weekly_view( int $user_id ): string {
		$week_offset = (int) ( $_GET['week_offset'] ?? 0 );
		$week_days   = self::get_week_days( $week_offset );
		$week_label  = $week_days[0]['label_short'] . ' – ' . $week_days[6]['label_full'];

		$week_start_ts = $week_days[0]['ts'];
		$week_end_ts   = $week_days[6]['ts'] + 86399;

		$prev_url = add_query_arg( 'week_offset', $week_offset - 1 );
		$next_url = add_query_arg( 'week_offset', $week_offset + 1 );

		global $wpdb;

		$events = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_title,
			  m_start.meta_value  AS data_start,
			  m_end.meta_value    AS data_sfarsit,
			  m_status.meta_value AS status,
			  m_tip.meta_value    AS tip,
			  m_loc.meta_value    AS locatie,
			  m_oras.meta_value   AS oras,
			  m_ora.meta_value    AS ora_inceput
			FROM {$wpdb->posts} p
			JOIN  {$wpdb->postmeta} m_start  ON m_start.post_id  = p.ID AND m_start.meta_key  = 'data-evenimentului'
			JOIN  {$wpdb->postmeta} m_status ON m_status.post_id = p.ID AND m_status.meta_key = 'status-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_end  ON m_end.post_id  = p.ID AND m_end.meta_key    = 'data-sfarsit'
			LEFT JOIN {$wpdb->postmeta} m_tip  ON m_tip.post_id  = p.ID AND m_tip.meta_key    = 'tipul-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_loc  ON m_loc.post_id  = p.ID AND m_loc.meta_key    = 'locatia-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_oras ON m_oras.post_id = p.ID AND m_oras.meta_key   = 'oras-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_ora  ON m_ora.post_id  = p.ID AND m_ora.meta_key    = 'ora-de-inceput'
			WHERE p.post_type   = 'evenimente'
			  AND p.post_status = 'publish'
			  AND p.post_author = %d
			  AND m_status.meta_value IN ('confirmed','vacation')
			  AND (
			    CAST(m_start.meta_value AS UNSIGNED) BETWEEN %d AND %d
			    OR (
			      m_status.meta_value IN ('vacation','vacation_pending')
			      AND CAST(m_start.meta_value AS UNSIGNED) < %d
			      AND m_end.meta_value IS NOT NULL
			      AND CAST(m_end.meta_value AS UNSIGNED) >= %d
			    )
			  )
			ORDER BY CAST(m_start.meta_value AS UNSIGNED) ASC",
			$user_id,
			$week_start_ts,
			$week_end_ts,
			$week_start_ts,
			$week_start_ts
		), ARRAY_A );

		// Grupăm evenimentele pe zile
		$day_map = [];
		foreach ( $week_days as $day ) {
			$day_map[ $day['date'] ] = [];
		}

		foreach ( $events as $ev ) {
			$start_ts = (int) $ev['data_start'];
			$end_ts   = (int) ( $ev['data_sfarsit'] ?? 0 );
			$is_vac   = in_array( $ev['status'], [ 'vacation', 'vacation_pending' ], true );

			if ( $is_vac && $end_ts > $start_ts ) {
				foreach ( $week_days as $day ) {
					if ( $start_ts <= ( $day['ts'] + 86399 ) && $end_ts >= $day['ts'] ) {
						$day_map[ $day['date'] ][] = $ev;
					}
				}
			} else {
				$start_date = date( 'Y-m-d', $start_ts );
				if ( isset( $day_map[ $start_date ] ) ) {
					$day_map[ $start_date ][] = $ev;
				}
			}
		}

		ob_start();
		?>
		<style>
		.bas-aw-wrap *{box-sizing:border-box;}
		.bas-aw-wrap{font-family:Inter,sans-serif;font-size:14px;margin-bottom:4px;}
		.bas-aw-nav{display:flex;align-items:center;gap:16px;margin-bottom:20px;}
		.bas-aw-btn-nav{background:#1a1a1a;border:1px solid #333;border-radius:10px;color:#fff;padding:8px 14px;font-size:13px;text-decoration:none;display:inline-block;transition:border-color .15s,color .15s;}
		.bas-aw-btn-nav:hover{border-color:#FF6A00;color:#FF6A00;}
		.bas-aw-week-label{font-weight:600;font-size:16px;color:#FF6A00;flex:1;text-align:center;}
		.bas-aw-table-wrap{overflow-x:auto;}
		.bas-aw-cal{width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;border:1px solid #2a2a2a;}
		.bas-aw-cal th{background:#1a1a1a;padding:10px 8px;font-weight:600;font-size:12px;text-align:center;border:1px solid #2a2a2a;color:#777;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;}
		.bas-aw-cal th.bas-aw-today{color:#FF6A00;}
		.bas-aw-cal td{background:#111;padding:10px 8px;border:1px solid #2a2a2a;vertical-align:top;min-width:100px;}
		.bas-aw-event{font-size:12px;line-height:1.4;margin-bottom:5px;}
		.bas-aw-event:last-child{margin-bottom:0;}
		.bas-aw-empty{color:#333;font-size:12px;font-style:italic;}
		.bas-aw-divider{border:none;border-top:1px solid #1e1e1e;margin:50px 0 20px;}
		</style>
		<div class="bas-aw-wrap">
			<div class="bas-aw-nav">
				<a href="<?php echo esc_url( $prev_url ); ?>" class="bas-aw-btn-nav">← Precedenta</a>
				<span class="bas-aw-week-label"><?php echo esc_html( $week_label ); ?></span>
				<a href="<?php echo esc_url( $next_url ); ?>" class="bas-aw-btn-nav">Urmatoarea →</a>
			</div>
			<div class="bas-aw-table-wrap">
				<table class="bas-aw-cal">
					<thead>
						<tr>
						<?php foreach ( $week_days as $day ) :
							$cls = $day['is_today'] ? ' class="bas-aw-today"' : '';
						?>
							<th<?php echo $cls; ?>><?php echo esc_html( $day['col_label'] ); ?></th>
						<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<tr>
						<?php foreach ( $week_days as $day ) :
							$day_evs = $day_map[ $day['date'] ] ?? [];
						?>
							<td>
							<?php if ( empty( $day_evs ) ) : ?>
								<span class="bas-aw-empty">—</span>
							<?php else :
								foreach ( $day_evs as $ev ) :
									$status = $ev['status'];
									$is_vac = in_array( $status, [ 'vacation', 'vacation_pending' ], true );
									$tip    = $ev['tip'] ?: ( $is_vac ? ( $ev['post_title'] ?: 'Vacanță' ) : '' );
									$parts  = array_filter( [ $tip, $ev['locatie'], $ev['oras'], $ev['ora_inceput'] ] );
									$color  = match( $status ) {
										'confirmed'        => '#ffffff',
										'pending'          => '#777777',
										'vacation',
										'vacation_pending'  => '#5b9bd5',
										default            => '#888888',
									};
							?>
								<div class="bas-aw-event" style="color:<?php echo esc_attr( $color ); ?>">
									<?php echo esc_html( implode( ' · ', $parts ) ?: ( $is_vac ? 'Vacanță' : '—' ) ); ?>
								</div>
							<?php endforeach; endif; ?>
							</td>
						<?php endforeach; ?>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── AJAX: șterge vacanță proprie ─────────────────────────────────

	public static function delete_vacation(): void {
		$nonce    = sanitize_text_field( $_POST['nonce'] ?? '' );
		$event_id = absint( $_POST['event_id'] ?? 0 );

		if ( ! wp_verify_nonce( $nonce, 'bas_artist_events_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Nonce invalid.' ], 403 );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Neautorizat.' ], 401 );
		}
		if ( ! $event_id || get_post_type( $event_id ) !== 'evenimente' ) {
			wp_send_json_error( [ 'message' => 'Eveniment negăsit.' ], 404 );
		}
		if ( (int) get_post_field( 'post_author', $event_id ) !== get_current_user_id() ) {
			wp_send_json_error( [ 'message' => 'Nu ai permisiunea să ștergi acest eveniment.' ], 403 );
		}

		$status = (string) get_post_meta( $event_id, 'status-eveniment', true );
		if ( ! in_array( $status, [ 'vacation', 'vacation_pending' ], true ) ) {
			wp_send_json_error( [ 'message' => 'Doar vacanțele pot fi șterse.' ], 400 );
		}

		// Snippet 22 (before_delete_post) șterge automat booking-ul asociat
		$result = wp_delete_post( $event_id, true );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => 'Ștergerea a eșuat.' ], 500 );
		}

		wp_send_json_success();
	}

	// ── Helper badge ──────────────────────────────────────────────────

	private static function row_style( string $status ): string {
		return match ( $status ) {
			'confirmed'        => 'color:#ffffff;',
			'pending'          => 'color:#777777;',
			'vacation'         => 'color:#5b9bd5;',
			'vacation_pending' => 'color:#5b9bd5;',
			'canceled'         => 'color:#525252;',
			default            => '',
		};
	}

	private static function status_badge( string $status ): array {
		return match ( $status ) {
			'confirmed'        => [ 'Accepted',               'background:#0f2d0f;color:#6fcf6f;' ],
			'pending'          => [ 'New',                    'background:#3a1e00;color:#ff9d3c;font-weight:600;' ],
			'vacation'         => [ 'Vacanță',                'background:#0a1a2d;color:#5b9bd5;' ],
			'vacation_pending' => [ 'Vacanță în așteptare',   'background:#0a1a2d;color:#5b9bd5;' ],
			'canceled'         => [ 'Anulat',                 'background:#2a0a0a;color:#eb5757;' ],
			default            => [ ucfirst( $status ),       'background:#1e1e1e;color:#888;' ],
		};
	}
}
