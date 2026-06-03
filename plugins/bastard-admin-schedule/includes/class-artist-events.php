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
			"SELECT p.ID, p.post_author,
			  m_data.meta_value   AS data_start,
			  m_status.meta_value AS status,
			  m_tip.meta_value    AS tip,
			  m_loc.meta_value    AS locatie,
			  m_oras.meta_value   AS oras,
			  m_ora.meta_value    AS ora_inceput
			FROM {$wpdb->posts} p
			JOIN  {$wpdb->postmeta} m_data   ON m_data.post_id   = p.ID AND m_data.meta_key   = 'data-evenimentului'
			JOIN  {$wpdb->postmeta} m_status ON m_status.post_id = p.ID AND m_status.meta_key = 'status-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_tip  ON m_tip.post_id  = p.ID AND m_tip.meta_key    = 'tipul-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_loc  ON m_loc.post_id  = p.ID AND m_loc.meta_key    = 'locatia-evenimentului'
			LEFT JOIN {$wpdb->postmeta} m_oras ON m_oras.post_id = p.ID AND m_oras.meta_key   = 'oras-eveniment'
			LEFT JOIN {$wpdb->postmeta} m_ora  ON m_ora.post_id  = p.ID AND m_ora.meta_key    = 'ora-de-inceput'
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
		<div class="bas-events-wrap" style="font-family:Inter,sans-serif;">
		<table class="bas-events-table" style="width:100%;border-collapse:collapse;font-size:14px;">
			<thead>
				<tr style="border-bottom:2px solid #2a2a2a;color:#aaa;text-align:left;">
					<th style="padding:8px 10px;">Dată</th>
					<th style="padding:8px 10px;">Tip</th>
					<th style="padding:8px 10px;">Locație</th>
					<th style="padding:8px 10px;">Oraș</th>
					<th style="padding:8px 10px;">Ora</th>
					<th style="padding:8px 10px;">Status</th>
					<th style="padding:8px 10px;"></th>
				</tr>
			</thead>
			<tbody>
			<?php
			$current_year = null;

			foreach ( $events as $event ) {
				$ts   = (int) $event['data_start'];
				$year = date( 'Y', $ts );

				if ( $year !== $current_year ) {
					if ( $current_year !== null ) {
						echo '<tr class="bas-year-sep"><td colspan="7" style="text-align:center;padding:12px 0;color:#555;font-size:12px;letter-spacing:2px;border-top:1px solid #2a2a2a;border-bottom:1px solid #2a2a2a;">── ' . esc_html( $year ) . ' ──</td></tr>';
					}
					$current_year = $year;
				}

				$status   = $event['status'];
				$can_delete = in_array( $status, [ 'vacation', 'vacation_pending' ], true )
				              && (int) $event['post_author'] === $user_id;

				[ $badge_label, $badge_color ] = self::status_badge( $status );

				$date_fmt = date_i18n( 'd M Y', $ts );
				$ora      = $event['ora_inceput'] ? esc_html( $event['ora_inceput'] ) : '—';
				$tip      = $event['tip']     ? esc_html( $event['tip'] )     : '—';
				$locatie  = $event['locatie'] ? esc_html( $event['locatie'] ) : '—';
				$oras     = $event['oras']    ? esc_html( $event['oras'] )    : '—';

				echo '<tr data-event-id="' . esc_attr( $event['ID'] ) . '" style="border-bottom:1px solid #1e1e1e;">';
				echo '<td style="padding:8px 10px;white-space:nowrap;">' . esc_html( $date_fmt ) . '</td>';
				echo '<td style="padding:8px 10px;">' . $tip . '</td>';
				echo '<td style="padding:8px 10px;">' . $locatie . '</td>';
				echo '<td style="padding:8px 10px;">' . $oras . '</td>';
				echo '<td style="padding:8px 10px;white-space:nowrap;">' . $ora . '</td>';
				echo '<td style="padding:8px 10px;"><span style="' . esc_attr( $badge_color ) . 'display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;">' . esc_html( $badge_label ) . '</span></td>';
				echo '<td style="padding:8px 10px;">';
				if ( $can_delete ) {
					echo '<button class="bas-delete-vacation" data-id="' . esc_attr( $event['ID'] ) . '" style="background:#2a0a0a;color:#eb5757;border:1px solid #5a2020;padding:3px 10px;border-radius:4px;cursor:pointer;font-size:12px;">Șterge</button>';
				}
				echo '</td>';
				echo '</tr>';
			}
			?>
			</tbody>
		</table>
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
								var row = document.querySelector('tr[data-event-id="' + eventId + '"]');
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

	private static function status_badge( string $status ): array {
		return match ( $status ) {
			'confirmed'        => [ 'Confirmat',              'background:#0f2d0f;color:#6fcf6f;' ],
			'pending'          => [ 'Cerere client',          'background:#2d2200;color:#e6c000;' ],
			'vacation'         => [ 'Vacanță',                'background:#0a1a2d;color:#5b9bd5;' ],
			'vacation_pending' => [ 'Vacanță în așteptare',   'background:#2d1500;color:#FF6A00;' ],
			'canceled'         => [ 'Anulat',                 'background:#1e1e1e;color:#555;' ],
			default            => [ ucfirst( $status ),       'background:#1e1e1e;color:#888;' ],
		};
	}
}
