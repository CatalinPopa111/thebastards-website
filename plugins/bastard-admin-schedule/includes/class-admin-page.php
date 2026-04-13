<?php
defined( 'ABSPATH' ) || exit;

class BAS_Admin_Page {

	// ── Date pentru JS ─────────────────────────────────────────────

	public static function get_js_data(): array {
		$week_offset = (int) ( $_GET['week_offset'] ?? 0 );
		[ $year, $week ] = self::get_year_week( $week_offset );

		$saved     = get_option( "bas_schedule_{$year}_W{$week}", [] );
		$locations = get_option( 'bas_locations', [] );
		$artists   = self::get_artists();
		$conflicts = BAS_Conflict_Detector::get_conflicts_map();

		return [
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'bas_nonce' ),
			'weekOffset'       => $week_offset,
			'week'             => $week,
			'year'             => $year,
			'hasSavedSchedule' => ! empty( $saved ),
			'savedSchedule'    => $saved,
			'locations'        => $locations,
			'artists'          => $artists,
			'conflicts'        => $conflicts,
			'weekDays'         => self::get_week_days( $week_offset ),
		];
	}

	// ── Render pagina ──────────────────────────────────────────────

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Acces interzis.' );
		}

		$week_offset = (int) ( $_GET['week_offset'] ?? 0 );
		$week_days   = self::get_week_days( $week_offset );
		[ $year, $week ] = self::get_year_week( $week_offset );

		$week_label  = $week_days[0]['label_short'] . ' – ' . $week_days[6]['label_full'];
		$prev_offset = $week_offset - 1;
		$next_offset = $week_offset + 1;
		$base_url    = admin_url( 'admin.php?page=bastard-schedule' );

		$locations = get_option( 'bas_locations', [] );
		$artists   = self::get_artists();
		$saved     = get_option( "bas_schedule_{$year}_W{$week}", [] );
		$events    = self::get_future_events();

		?>
		<div class="bas-wrap" x-data="basSchedule()" x-init="init()">

			<h1 class="bas-title">Schedule Rezidențiat</h1>

			<!-- Navigare săptămână -->
			<div class="bas-week-nav">
				<a href="<?php echo esc_url( $base_url . '&week_offset=' . $prev_offset ); ?>" class="bas-btn-nav">← Săpt. precedentă</a>
				<span class="bas-week-label"><?php echo esc_html( $week_label ); ?></span>
				<a href="<?php echo esc_url( $base_url . '&week_offset=' . $next_offset ); ?>" class="bas-btn-nav">Săpt. următoare →</a>
			</div>

			<!-- Calendar -->
			<div class="bas-table-wrap">
				<table class="bas-calendar">
					<thead>
						<tr>
							<th class="bas-th-location">Locație</th>
							<?php foreach ( $week_days as $i => $day ) : ?>
								<th class="<?php echo $day['is_today'] ? 'bas-today' : ''; ?>">
									<?php echo esc_html( $day['col_label'] ); ?>
								</th>
							<?php endforeach; ?>
							<th class="bas-th-delete"></th>
						</tr>
					</thead>
					<tbody id="bas-calendar-body">
						<?php foreach ( $locations as $loc ) :
							$slug = $loc['slug'];
						?>
						<tr data-location="<?php echo esc_attr( $slug ); ?>">
							<td class="bas-location-label">
								<div class="bas-location-inner">
									<div>
										<div class="bas-location-name"><?php echo esc_html( $loc['name'] ); ?></div>
										<?php if ( ! empty( $loc['city'] ) ) : ?>
											<div class="bas-location-city"><?php echo esc_html( $loc['city'] ); ?></div>
										<?php endif; ?>
									</div>
									<button class="bas-btn-delete-loc"
										@click="askDeleteLocation('<?php echo esc_js( $slug ); ?>', '<?php echo esc_js( $loc['name'] ); ?>')"
										title="Șterge locație">✕</button>
								</div>
							</td>
							<?php foreach ( $week_days as $i => $day ) :
								$key        = $slug . '_' . $i;
								$saved_id   = $saved[ $key ] ?? 0;
							?>
							<td>
								<div class="bas-cell-inner">
									<select
										class="bas-artist-select"
										data-day="<?php echo esc_attr( $i ); ?>"
										data-date="<?php echo esc_attr( $day['date'] ); ?>"
										data-key="<?php echo esc_attr( $key ); ?>"
										x-ref="sel_<?php echo esc_attr( $key ); ?>"
										@change="onSelectChange($event.target)"
										:class="getCellClass($el)"
									>
										<option value="">selectează</option>
										<?php foreach ( $artists as $artist ) : ?>
											<option value="<?php echo esc_attr( $artist['id'] ); ?>"
												<?php selected( $saved_id, $artist['id'] ); ?>>
												<?php echo esc_html( $artist['name'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<span class="bas-conflict-icon"
										x-show="hasConflict($el.previousElementSibling)">⚠</span>
								</div>
							</td>
							<?php endforeach; ?>
							<td></td>
						</tr>
						<?php endforeach; ?>

						<?php if ( empty( $locations ) ) : ?>
						<tr>
							<td colspan="9" class="bas-empty">Nicio locație configurată. Adaugă prima locație cu +</td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Buton adaugă locație -->
			<button class="bas-btn-add-location" @click="openAddLocation()" title="Adaugă locație">+</button>

			<!-- Legendă + butoane salvare -->
			<div class="bas-legend">
				<span class="bas-legend-icon">⚠</span>
				<span>Artistul are un eveniment extern confirmat, pending sau este în vacanță în această zi</span>
			</div>

			<div class="bas-save-bar">
				<button class="bas-btn-secondary" @click="resetCalendar()">Resetează</button>
				<button class="bas-btn-save" @click="saveSchedule()" :disabled="saving">
					<span x-text="saving ? 'Se salvează...' : 'Salvează programul'"></span>
				</button>
			</div>

			<!-- Notificare salvare -->
			<div class="bas-notice bas-notice-success" x-show="notice === 'success'" x-transition>Program salvat cu succes.</div>
			<div class="bas-notice bas-notice-error"   x-show="notice === 'error'"   x-transition>Eroare la salvare. Încearcă din nou.</div>

			<!-- Lista evenimente viitoare -->
			<h2 class="bas-section-title">Evenimente viitoare</h2>

			<table class="bas-events-table">
				<thead>
					<tr>
						<th>Data</th>
						<th>Artist</th>
						<th>Locație / Oraș</th>
						<th>Client</th>
						<th>Tip</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $events ) ) : ?>
					<tr><td colspan="6" class="bas-empty">Nu există evenimente viitoare.</td></tr>
					<?php endif; ?>

					<?php foreach ( $events as $ev ) :
						$status    = $ev['status'];
						$deletable = in_array( $status, [ 'pending', 'canceled' ], true );
					?>
					<tr class="bas-row bas-row-<?php echo esc_attr( $status ); ?>"
						id="bas-ev-<?php echo esc_attr( $ev['id'] ); ?>"
						data-event-id="<?php echo esc_attr( $ev['id'] ); ?>"
						data-artist="<?php echo esc_attr( $ev['artist_id'] ); ?>"
						data-type="<?php echo esc_attr( $ev['type'] ); ?>"
						data-status="<?php echo esc_attr( $status ); ?>"
						data-day="<?php echo esc_attr( $ev['day_index'] ?? '' ); ?>"
						data-day-end="<?php echo esc_attr( $ev['day_end_index'] ?? '' ); ?>"
						x-data="{ status: '<?php echo esc_js( $status ); ?>' }"
					>
						<td class="bas-data-cell"><?php echo esc_html( $ev['date_label'] ); ?></td>
						<td class="bas-data-cell"><?php echo esc_html( $ev['artist_name'] ); ?></td>
						<td class="bas-data-cell"><?php echo esc_html( $ev['location'] ); ?></td>
						<td class="bas-data-cell"><?php echo esc_html( $ev['client'] ); ?></td>
						<td class="bas-data-cell"><?php echo esc_html( $ev['type_label'] ); ?></td>
						<td class="bas-status-cell">
							<select class="bas-status-select"
								x-model="status"
								@change="$el.closest('tr').dataset.status = status; $el.closest('tr').className = 'bas-row bas-row-' + status;">
								<option value="confirmed">confirmed</option>
								<option value="pending">pending</option>
								<option value="canceled">canceled</option>
								<option value="vacation">vacation</option>
							</select>
							<button class="bas-btn-save-inline"
								@click="saveEventStatus($el.closest('tr').dataset.eventId, status)">Salvează</button>
							<?php if ( $deletable ) : ?>
							<button class="bas-btn-delete-ev"
								@click="askDeleteEvent(
									'<?php echo esc_js( $ev['id'] ); ?>',
									'<?php echo esc_js( $ev['date_label'] ); ?>',
									'<?php echo esc_js( $ev['artist_name'] ); ?>',
									'<?php echo esc_js( $ev['location'] ); ?>',
									'<?php echo esc_js( $ev['type_label'] ); ?>'
								)">✕</button>
							<?php else: ?>
							<button class="bas-btn-delete-ev bas-btn-delete-ev--hidden"
								@click="askDeleteEvent(
									'<?php echo esc_js( $ev['id'] ); ?>',
									'<?php echo esc_js( $ev['date_label'] ); ?>',
									'<?php echo esc_js( $ev['artist_name'] ); ?>',
									'<?php echo esc_js( $ev['location'] ); ?>',
									'<?php echo esc_js( $ev['type_label'] ); ?>'
								)"
								x-show="['pending','canceled'].includes(status)">✕</button>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Modal: adaugă locație -->
			<div class="bas-modal-overlay" x-show="modal === 'addLocation'" x-transition @click.self="modal = null">
				<div class="bas-modal">
					<h3>Adaugă locație nouă</h3>
					<div class="bas-modal-field">
						<label>Nume locație *</label>
						<input type="text" class="bas-modal-input" x-model="newLocation.name" placeholder="ex: Club Fabrica" @keyup.enter="confirmAddLocation()">
					</div>
					<div class="bas-modal-field">
						<label>Oraș</label>
						<input type="text" class="bas-modal-input" x-model="newLocation.city" placeholder="ex: București" @keyup.enter="confirmAddLocation()">
					</div>
					<p class="bas-modal-error" x-show="modalError" x-text="modalError"></p>
					<div class="bas-modal-actions">
						<button class="bas-btn-cancel" @click="modal = null">Anulează</button>
						<button class="bas-btn-confirm" @click="confirmAddLocation()" :disabled="saving">Adaugă</button>
					</div>
				</div>
			</div>

			<!-- Modal: confirmare ștergere locație -->
			<div class="bas-modal-overlay" x-show="modal === 'deleteLocation'" x-transition @click.self="modal = null">
				<div class="bas-modal">
					<h3 class="bas-danger">Confirmare ștergere locație</h3>
					<p>Ești sigur că vrei să ștergi locația:</p>
					<p class="bas-modal-highlight" x-text="pendingDelete.name"></p>
					<p class="bas-modal-warning">Evenimentele de tip Club asociate nu se vor șterge.</p>
					<div class="bas-modal-actions">
						<button class="bas-btn-cancel" @click="modal = null">Anulează</button>
						<button class="bas-btn-confirm-delete" @click="confirmDeleteLocation()" :disabled="saving">Șterge locația</button>
					</div>
				</div>
			</div>

			<!-- Modal: confirmare ștergere eveniment -->
			<div class="bas-modal-overlay" x-show="modal === 'deleteEvent'" x-transition @click.self="modal = null">
				<div class="bas-modal">
					<h3 class="bas-danger">Confirmare ștergere eveniment</h3>
					<p>Ești sigur că vrei să ștergi evenimentul:</p>
					<p class="bas-modal-highlight" x-text="pendingDelete.date"></p>
					<p><strong x-text="pendingDelete.artist"></strong> — <span x-text="pendingDelete.location + ' · ' + pendingDelete.type"></span></p>
					<p class="bas-modal-warning">Această acțiune este ireversibilă și va șterge și booking-ul asociat.</p>
					<div class="bas-modal-actions">
						<button class="bas-btn-cancel" @click="modal = null">Anulează</button>
						<button class="bas-btn-confirm-delete" @click="confirmDeleteEvent()" :disabled="saving">Șterge evenimentul</button>
					</div>
				</div>
			</div>

		</div><!-- .bas-wrap -->
		<?php
	}

	// ── Helpers PHP ────────────────────────────────────────────────

	private static function get_year_week( int $offset ): array {
		$ts   = strtotime( "+{$offset} weeks", strtotime( 'this monday midnight' ) );
		$year = (int) date( 'Y', $ts );
		$week = (int) date( 'W', $ts );
		return [ $year, $week ];
	}

	private static function get_week_days( int $offset ): array {
		$monday = strtotime( "+{$offset} weeks", strtotime( 'this monday midnight' ) );
		$today  = strtotime( 'today midnight' );
		$days   = [];

		$day_names = [ 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sâm', 'Dum' ];
		$months    = [ 1 => 'Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

		for ( $i = 0; $i < 7; $i++ ) {
			$ts     = strtotime( "+{$i} days", $monday );
			$day_n  = (int) date( 'j', $ts );
			$mon_n  = (int) date( 'n', $ts );
			$year_n = (int) date( 'Y', $ts );

			$days[] = [
				'date'        => date( 'Y-m-d', $ts ),
				'col_label'   => $day_names[ $i ] . ' ' . $day_n,
				'label_short' => $day_n . ' ' . $months[ $mon_n ],
				'label_full'  => $day_n . ' ' . $months[ $mon_n ] . ' ' . $year_n,
				'is_today'    => $ts === $today,
			];
		}

		return $days;
	}

	private static function get_artists(): array {
		$posts = get_posts( [
			'post_type'      => 'artist',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		return array_map( fn( $p ) => [
			'id'   => $p->post_author,
			'name' => get_the_author_meta( 'display_name', $p->post_author ),
		], $posts );
	}

	private static function get_future_events(): array {
		$today = strtotime( 'today midnight' );

		$posts = get_posts( [
			'post_type'      => 'evenimente',
			'posts_per_page' => 500,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'data-evenimentului',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => 'data-evenimentului',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				],
			],
		] );

		$months = [ 1 => 'Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
		$events = [];

		foreach ( $posts as $post ) {
			$start_ts  = (int) get_post_meta( $post->ID, 'data-evenimentului', true );
			$end_ts    = (int) get_post_meta( $post->ID, 'data-sfarsit', true );
			$status    = get_post_meta( $post->ID, 'status-eveniment', true );
			$type      = get_post_meta( $post->ID, 'tipul-evenimentului', true );
			$city      = get_post_meta( $post->ID, 'oras-eveniment', true );
			$location  = get_post_meta( $post->ID, 'locatia-evenimentului', true );
			$client    = get_post_meta( $post->ID, 'nume-client', true );
			$artist_id = $post->post_author;

			$start_d  = (int) date( 'j', $start_ts );
			$start_m  = (int) date( 'n', $start_ts );
			$start_y  = (int) date( 'Y', $start_ts );

			if ( 'vacation' === $status && $end_ts && $end_ts > $start_ts ) {
				$end_d = (int) date( 'j', $end_ts );
				$end_m = (int) date( 'n', $end_ts );
				$end_y = (int) date( 'Y', $end_ts );
				$date_label = $start_d . ' ' . $months[ $start_m ] . ' → ' . $end_d . ' ' . $months[ $end_m ] . ' ' . $end_y;
			} else {
				$date_label = $start_d . ' ' . $months[ $start_m ] . ' ' . $start_y;
			}

			$loc_parts = array_filter( [ $location, $city ] );
			$loc_str   = implode( ', ', $loc_parts ) ?: '—';

			$events[] = [
				'id'          => $post->ID,
				'date_label'  => $date_label,
				'artist_id'   => $artist_id,
				'artist_name' => get_the_author_meta( 'display_name', $artist_id ),
				'location'    => $loc_str,
				'client'      => $client ?: '—',
				'type'        => strtolower( $type ),
				'type_label'  => $type ?: '—',
				'status'      => $status ?: 'pending',
			];
		}

		return $events;
	}
}
