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

		global $wpdb;

		$events = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_author, p.post_title,
			  m_data.meta_value   AS data_start,
			  m_status.meta_value AS status,
			  m_tip.meta_value    AS tip,
			  m_loc.meta_value    AS locatie,
			  m_oras.meta_value   AS oras,
			  m_ora.meta_value    AS ora_inceput,
			  m_client.meta_value AS client
			FROM {$wpdb->posts} p
			JOIN  {$wpdb->postmeta} m_data   ON m_data.post_id   = p.ID AND m_data.meta_key   = 'data-evenimentului'
			JOIN  {$wpdb->postmeta} m_status ON m_status.post_id = p.ID AND m_status.meta_key = 'status-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_tip    ON m_tip.post_id    = p.ID AND m_tip.meta_key    = 'tipul-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_loc    ON m_loc.post_id    = p.ID AND m_loc.meta_key    = 'locatia-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_oras   ON m_oras.post_id   = p.ID AND m_oras.meta_key   = 'oras-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_ora    ON m_ora.post_id    = p.ID AND m_ora.meta_key    = 'ora-de-inceput'
			LEFT JOIN {$wpdb->postmeta} m_client ON m_client.post_id = p.ID AND m_client.meta_key = 'nume-client'
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
			return '<p style="color:#888;font-style:italic;">Nu există evenimente viitoare.</p>';
		}

		ob_start();
		?>
		<style>
		.bas-el-list{font-family:Inter,sans-serif;font-size:14px;}
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

			$date_fmt = date_i18n( 'd M Y', $ts );
			// Pentru vacanțe, tipul e gol — folosim titlul postării (ce a scris artistul)
			$tip      = $event['tip'] ?: ( in_array( $status, [ 'vacation', 'vacation_pending' ], true ) ? $event['post_title'] : '' );
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
			'vacation_pending' => 'color:#FF6A00;',
			'canceled'         => 'color:#525252;',
			default            => '',
		};
	}

	private static function status_badge( string $status ): array {
		return match ( $status ) {
			'confirmed'        => [ 'Confirmat',              'background:#0f2d0f;color:#6fcf6f;' ],
			'pending'          => [ 'Cerere client',          'background:#2d2200;color:#e6c000;' ],
			'vacation'         => [ 'Vacanță',                'background:#0a1a2d;color:#5b9bd5;' ],
			'vacation_pending' => [ 'Vacanță în așteptare',   'background:#2d1500;color:#FF6A00;' ],
			'canceled'         => [ 'Anulat',                 'background:#2a0a0a;color:#eb5757;' ],
			default            => [ ucfirst( $status ),       'background:#1e1e1e;color:#888;' ],
		};
	}
}
