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

	public static function render( bool $die_on_fail = true, bool $is_frontend = false ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			if ( $die_on_fail ) wp_die( 'Acces interzis.' );
			return;
		}

		$week_offset = (int) ( $_GET['week_offset'] ?? 0 );
		$week_days   = self::get_week_days( $week_offset );
		[ $year, $week ] = self::get_year_week( $week_offset );

		$week_label  = $week_days[0]['label_short'] . ' – ' . $week_days[6]['label_full'];
		$prev_offset = $week_offset - 1;
		$next_offset = $week_offset + 1;

		if ( $is_frontend ) {
			$current_url = strtok( ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?' );
			$base_url    = $current_url . '?';
		} else {
			$base_url = admin_url( 'admin.php?page=bastard-schedule&' );
		}

		$locations   = get_option( 'bas_locations', [] );
		$artists     = self::get_artists();
		$saved       = get_option( "bas_schedule_{$year}_W{$week}", [] );
		$events      = self::get_future_events();
		$event_types = self::get_glossary_options( 3 );

		?>
		<div class="bas-wrap" x-data="basSchedule()" x-init="init()">

			<h1 class="bas-title">Program Săptămânal</h1>

			<!-- Navigare săptămână -->
			<div class="bas-week-nav">
				<a href="<?php echo esc_url( $base_url . 'week_offset=' . $prev_offset ); ?>" class="bas-btn-nav">← Săpt. precedentă</a>
				<span class="bas-week-label"><?php echo esc_html( $week_label ); ?></span>
				<a href="<?php echo esc_url( $base_url . 'week_offset=' . $next_offset ); ?>" class="bas-btn-nav">Săpt. următoare →</a>
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
						</tr>
						<?php endforeach; ?>

						<?php if ( empty( $locations ) ) : ?>
						<tr>
							<td colspan="8" class="bas-empty">Nicio locație configurată. Adaugă prima locație cu +</td>
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
				<button class="bas-btn-email" @click="sendWeeklySchedule()" :disabled="sendingEmail"
					title="Trimite programul săptămânii pe email artiștilor">
					<span x-show="!sendingEmail">✉</span>
					<span x-show="sendingEmail" style="display:none">...</span>
				</button>
				<button class="bas-btn-save" @click="saveSchedule()" :disabled="saving || !isDirty">
					<span x-show="!saving">Salvează programul</span>
					<span x-show="saving" style="display:none">Se salvează...</span>
				</button>
			</div>

			<!-- Notificare salvare -->
			<div class="bas-notice bas-notice-success" x-show="notice === 'success'" x-transition>Program salvat cu succes.</div>
			<div class="bas-notice bas-notice-error"   x-show="notice === 'error'"   x-transition>Eroare la salvare. Încearcă din nou.</div>
			<div class="bas-notice bas-notice-success" x-show="notice === 'email-success'" x-transition>Email-uri trimise cu succes.</div>
			<div class="bas-notice bas-notice-error"   x-show="notice === 'email-error'"   x-transition>Eroare la trimiterea email-urilor. Încearcă din nou.</div>

			<!-- ── Header secțiune evenimente + filtru ───────────────── -->
			<div class="bas-section-header">
				<h2 class="bas-section-title" style="margin:0; border:none; padding:0;">Evenimente viitoare</h2>
				<button class="bas-filter-toggle" :class="{ 'is-open': filtersOpen }" @click="filtersOpen = !filtersOpen">
					<span class="bas-filter-toggle-icon">⚲</span>
					<span>Filtre</span>
					<span class="bas-filter-count" x-show="activeFilterCount > 0" x-text="activeFilterCount" style="display:none"></span>
				</button>
			</div>

			<!-- ── Panou filtre ──────────────────────────────────────── -->
			<div class="bas-filter-panel" x-show="filtersOpen" x-transition style="display:none">
				<div class="bas-filter-grid">

					<!-- Artist -->
					<div class="bas-filter-field">
						<label class="bas-filter-label">Artist</label>
						<select class="bas-filter-input" x-model.number="filters.artist" @change="applyFilters()">
							<option value="0">Toți artiștii</option>
							<?php foreach ( $artists as $artist ) : ?>
								<option value="<?php echo esc_attr( $artist['id'] ); ?>">
									<?php echo esc_html( $artist['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Status -->
					<div class="bas-filter-field">
						<label class="bas-filter-label">Status</label>
						<select class="bas-filter-input" x-model="filters.status" @change="applyFilters()">
							<option value="">Toate statusurile</option>
							<option value="confirmed">Confirmat</option>
							<option value="pending">Cerere client</option>
							<option value="vacation">Vacanță</option>
							<option value="canceled">Anulat</option>
						</select>
					</div>

					<!-- Nume client -->
					<div class="bas-filter-field">
						<label class="bas-filter-label">Nume client</label>
						<input type="text" class="bas-filter-input" x-model="filters.client"
							@input.debounce.250ms="applyFilters()" placeholder="Caută după client...">
					</div>

					<!-- Interval dată -->
					<div class="bas-filter-field bas-filter-field-dates">
						<label class="bas-filter-label">Interval dată</label>
						<div class="bas-filter-date-row">
							<input type="date" class="bas-filter-input" x-model="filters.dateFrom"
								:disabled="filters.useWeek" @change="filters.useWeek = false; applyFilters()">
							<span class="bas-filter-date-sep">→</span>
							<input type="date" class="bas-filter-input" x-model="filters.dateTo"
								:disabled="filters.useWeek" @change="filters.useWeek = false; applyFilters()">
						</div>
						<label class="bas-filter-check">
							<input type="checkbox" x-model="filters.useWeek" @change="toggleWeek()">
							<span>Săptămâna afișată (<?php echo esc_html( $week_label ); ?>)</span>
						</label>
					</div>

					<!-- Tip eveniment -->
					<div class="bas-filter-field bas-filter-field-types">
						<label class="bas-filter-label">Tip eveniment</label>
						<div class="bas-filter-chips">
							<?php foreach ( $event_types as $opt ) : ?>
								<label class="bas-filter-chip">
									<input type="checkbox" value="<?php echo esc_attr( strtolower( $opt['value'] ) ); ?>"
										x-model="filters.types" @change="applyFilters()">
									<span><?php echo esc_html( $opt['label'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

				</div><!-- .bas-filter-grid -->

				<div class="bas-filter-actions">
					<span class="bas-filter-result" x-show="activeFilterCount > 0"
						x-text="visibleCount + (visibleCount === 1 ? ' eveniment' : ' evenimente')" style="display:none"></span>
					<button class="bas-btn-reset-filters" @click="resetFilters()" :disabled="activeFilterCount === 0">
						Resetează filtrele
					</button>
				</div>
			</div><!-- .bas-filter-panel -->

			<!-- Mesaj fără rezultate -->
			<div class="bas-no-results" x-show="filtersOpen && visibleCount === 0 && activeFilterCount > 0" style="display:none">
				Niciun eveniment pentru filtrele selectate.
			</div>

			<!-- ── Formular adaugă eveniment ─────────────────────────── -->
			<div class="bas-add-event-wrap">
				<button class="bas-btn-add-event" @click="newEvent.open = !newEvent.open">
					<span x-show="!newEvent.open">+ Adaugă eveniment nou</span>
					<span x-show="newEvent.open" style="display:none">− Închide formular</span>
				</button>

				<div class="bas-add-event-form" x-show="newEvent.open" x-transition style="display:none">
					<div class="bas-add-event-grid">

						<!-- Rândul 1: Artist + Status + Data start + Data sfârşit -->
						<div class="bas-detail-field">
							<label class="bas-detail-label">Artist *</label>
							<select class="bas-detail-input" x-model="newEvent.artist_id">
								<option value="">— selectează artist —</option>
								<?php foreach ( $artists as $artist ) : ?>
									<option value="<?php echo esc_attr( $artist['id'] ); ?>">
										<?php echo esc_html( $artist['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Status</label>
							<select class="bas-detail-input" x-model="newEvent.status">
								<option value="pending">pending</option>
								<option value="confirmed">confirmed</option>
								<option value="canceled">canceled</option>
								<option value="vacation">vacation</option>
							</select>
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Data eveniment *</label>
							<input type="date" class="bas-detail-input" x-model="newEvent.fields.data_start">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Data sfârşit</label>
							<input type="date" class="bas-detail-input" x-model="newEvent.fields.data_end">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Ora început</label>
							<input type="time" class="bas-detail-input" x-model="newEvent.fields.ora_inceput">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Tip eveniment</label>
							<select class="bas-detail-input" x-model="newEvent.fields.tip">
								<option value="">— selectează —</option>
								<?php foreach ( $event_types as $opt ) : ?>
									<option value="<?php echo esc_attr( $opt['value'] ); ?>"><?php echo esc_html( $opt['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Locaţie</label>
							<input type="text" class="bas-detail-input" x-model="newEvent.fields.locatie" placeholder="Numele locaţiei">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Oraş</label>
							<input type="text" class="bas-detail-input" x-model="newEvent.fields.oras" placeholder="Oraş">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Nume client</label>
							<input type="text" class="bas-detail-input" x-model="newEvent.fields.client" placeholder="Client">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Telefon</label>
							<input type="text" class="bas-detail-input" x-model="newEvent.fields.telefon" placeholder="07xx xxx xxx">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Email</label>
							<input type="email" class="bas-detail-input" x-model="newEvent.fields.email" placeholder="email@exemplu.ro">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Nr. participanţi</label>
							<input type="text" class="bas-detail-input" x-model="newEvent.fields.participanti" placeholder="ex: 200">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Sonorizare</label>
							<input type="text" class="bas-detail-input" x-model="newEvent.fields.sonorizare" placeholder="da / nu / proprie">
						</div>

						<div class="bas-detail-field">
							<label class="bas-detail-label">Durata prestaţie</label>
							<input type="text" class="bas-detail-input" x-model="newEvent.fields.durata" placeholder="ex: 3h">
						</div>

						<div class="bas-detail-field bas-detail-field-full">
							<label class="bas-detail-label">Mesaj / Detalii</label>
							<textarea class="bas-detail-textarea" x-model="newEvent.fields.mesaj" rows="3" placeholder="Detalii suplimentare..."></textarea>
						</div>

					</div><!-- .bas-add-event-grid -->

					<div class="bas-add-event-actions">
						<p class="bas-add-event-error" x-show="newEvent.error" x-text="newEvent.error" style="display:none"></p>
						<button class="bas-btn-secondary" @click="resetNewEvent()">Resetează</button>
						<button class="bas-btn-save" @click="createEvent()" :disabled="newEvent.saving">
							<span x-show="!newEvent.saving">Creează evenimentul</span>
							<span x-show="newEvent.saving" style="display:none">Se creează...</span>
						</button>
					</div>

				</div><!-- .bas-add-event-form -->
			</div><!-- .bas-add-event-wrap -->

			<!-- ── Lista evenimente viitoare ─────────────────────────── -->
			<table class="bas-events-table">
				<thead>
					<tr>
						<th>Eveniment</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $events ) ) : ?>
					<tbody><tr><td colspan="2" class="bas-empty">Nu există evenimente viitoare.</td></tr></tbody>
					<?php endif; ?>

					<?php
					$__current_year = null;
					foreach ( $events as $ev ) :
						$status   = $ev['status'];
						$__ev_year = $ev['fields']['data_start']
							? substr( $ev['fields']['data_start'], 0, 4 )
							: null;
						if ( $__ev_year && $__ev_year !== $__current_year ) :
							if ( $__current_year !== null ) : ?>
							<tbody class="bas-year-sep-tbody">
								<tr><td colspan="2" style="text-align:center;padding:10px 0;color:#555;font-size:11px;letter-spacing:2px;border-top:1px solid #1e1e1e;border-bottom:1px solid #1e1e1e;">── <?php echo esc_html( $__ev_year ); ?> ──</td></tr>
							</tbody>
							<?php endif;
							$__current_year = $__ev_year;
						endif;
					?>
					<?php
					$__cli  = $ev['client'] !== '—' ? mb_strtolower( $ev['client'] ) : '';
					$__dend = $ev['fields']['data_end'] ?: $ev['fields']['data_start'];
					?>
					<tbody
						class="bas-event-tbody"
						data-artist-id="<?php echo esc_attr( $ev['artist_id'] ); ?>"
						data-start="<?php echo esc_attr( $ev['fields']['data_start'] ); ?>"
						data-end="<?php echo esc_attr( $__dend ); ?>"
						data-type="<?php echo esc_attr( $ev['type'] ); ?>"
						data-status="<?php echo esc_attr( $status ); ?>"
						data-client="<?php echo esc_attr( $__cli ); ?>"
						x-data="{ status: '<?php echo esc_js( $status ); ?>', open: false, addArtistId: '', fields: <?php echo esc_attr( wp_json_encode( $ev['fields'] ) ); ?>, groupMembers: <?php echo esc_attr( wp_json_encode( $ev['group_members'] ) ); ?> }">

					<tr class="bas-row bas-row-<?php echo esc_attr( $status ); ?>"
						id="bas-ev-<?php echo esc_attr( $ev['id'] ); ?>"
						data-event-id="<?php echo esc_attr( $ev['id'] ); ?>"
						data-artist="<?php echo esc_attr( $ev['artist_id'] ); ?>"
						data-type="<?php echo esc_attr( $ev['type'] ); ?>"
						data-status="<?php echo esc_attr( $status ); ?>"
					>
						<td class="bas-data-cell bas-main-cell">
							<div class="bas-row-l1">
								<span class="bas-ev-date"><?php echo esc_html( $ev['date_label'] ); ?></span>
								<span class="bas-ev-dot">·</span>
								<span class="bas-ev-artist">
									<?php echo esc_html( $ev['artist_name'] ); ?>
									<?php if ( $ev['group_count'] > 1 ) : ?>
										<span class="bas-group-badge">+<?php echo $ev['group_count'] - 1; ?></span>
									<?php endif; ?>
								</span>
								<?php if ( $ev['type_label'] !== '—' ) : ?>
									<span class="bas-ev-dot">·</span>
									<span><?php echo esc_html( $ev['type_label'] ); ?></span>
								<?php endif; ?>
							</div>
							<?php
							$l2_parts = array_filter( [
								$ev['location'] !== '—' ? $ev['location'] : '',
								$ev['client']   !== '—' ? $ev['client']   : '',
							] );
							if ( $l2_parts ) : ?>
							<div class="bas-row-l2">
								<?php
								$first = true;
								foreach ( $l2_parts as $part ) {
									if ( ! $first ) echo '<span class="bas-ev-dot">·</span>';
									echo '<span>' . esc_html( $part ) . '</span>';
									$first = false;
								}
								?>
							</div>
							<?php endif; ?>
							<?php
							// ── Info subtil: zile de la depunere (pending) / data acceptării (confirmed) ──
							$info = '';
							if ( 'pending' === $status && ! empty( $ev['created_ts'] ) ) {
								$days = (int) floor( ( current_time( 'timestamp' ) - $ev['created_ts'] ) / DAY_IN_SECONDS );
								$info = $days <= 0
									? 'depusă azi'
									: ( 1 === $days ? 'depusă acum 1 zi' : "depusă acum {$days} zile" );
							} elseif ( 'confirmed' === $status && ! empty( $ev['confirmed_ts'] ) ) {
								$info = 'acceptată ' . date_i18n( 'j M Y', $ev['confirmed_ts'] );
							}
							if ( $info ) : ?>
							<div class="bas-row-info" style="margin-top:3px;font-size:11px;color:#6a6a6a;font-style:italic;"><?php echo esc_html( $info ); ?></div>
							<?php endif; ?>
						</td>
						<td class="bas-status-cell">
							<div class="bas-sc-inner">
							<span class="bas-status-badge"
								x-text="{'confirmed':'Confirmat','pending':'Cerere client','canceled':'Anulat','vacation':'Vacanță'}[status] || status">
							</span>
							<div class="bas-sc-buttons">
							<select class="bas-status-select"
								x-model="status"
								@change="$el.closest('tr').dataset.status = status; $el.closest('tr').className = 'bas-row bas-row-' + status;">
								<option value="confirmed">confirmed</option>
								<option value="pending">pending</option>
								<option value="canceled">canceled</option>
								<option value="vacation">vacation</option>
							</select>
							<button class="bas-btn-save-inline"
								@click="saveEvent($el.closest('tr').dataset.eventId, status, fields, $el)">Salvează</button>
							<button class="bas-btn-expand"
								@click="open = !open"
								:class="{ 'is-open': open }"
								title="Detalii eveniment"></button>
							<button class="bas-btn-delete-ev"
								style="display:none"
								x-show="['pending','canceled'].includes(status)"
								@click="askDeleteEvent(
									'<?php echo esc_js( $ev['id'] ); ?>',
									'<?php echo esc_js( $ev['date_label'] ); ?>',
									'<?php echo esc_js( $ev['artist_name'] ); ?>',
									'<?php echo esc_js( $ev['location'] ); ?>',
									'<?php echo esc_js( $ev['type_label'] ); ?>'
								)">✕</button>
							</div><!-- .bas-sc-buttons -->
							</div><!-- .bas-sc-inner -->
						</td>
					</tr>

					<!-- Rând detalii (expand) -->
					<tr class="bas-detail-row" x-show="open">
						<td colspan="2" class="bas-detail-cell">
							<div class="bas-detail-grid">

								<!-- Artist (schimbare) -->
								<div class="bas-detail-field bas-detail-field-artist">
									<label class="bas-detail-label bas-artist-change-label">Artist</label>
									<select class="bas-detail-input bas-artist-change-select" x-model="fields.new_artist_id">
										<option value="">— selectează —</option>
										<?php foreach ( $artists as $artist ) : ?>
											<option value="<?php echo esc_attr( $artist['id'] ); ?>"><?php echo esc_html( $artist['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="bas-detail-field">
									<label class="bas-detail-label">Data eveniment</label>
									<input type="date" class="bas-detail-input" x-model="fields.data_start">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Data sfârşit</label>
									<input type="date" class="bas-detail-input" x-model="fields.data_end">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Ora început</label>
									<input type="time" class="bas-detail-input" x-model="fields.ora_inceput">
								</div>

								<div class="bas-detail-field">
									<label class="bas-detail-label">Locaţie</label>
									<input type="text" class="bas-detail-input" x-model="fields.locatie" placeholder="Numele locaţiei">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Oraş</label>
									<input type="text" class="bas-detail-input" x-model="fields.oras" placeholder="Oraş">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Tip eveniment</label>
									<select class="bas-detail-input" x-model="fields.tip">
										<option value="">— selectează —</option>
										<?php foreach ( $event_types as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt['value'] ); ?>"><?php echo esc_html( $opt['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="bas-detail-field">
									<label class="bas-detail-label">Nume client</label>
									<input type="text" class="bas-detail-input" x-model="fields.client" placeholder="Client">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Telefon</label>
									<input type="text" class="bas-detail-input" x-model="fields.telefon" placeholder="07xx xxx xxx">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Email</label>
									<input type="email" class="bas-detail-input" x-model="fields.email" placeholder="email@exemplu.ro">
								</div>

								<div class="bas-detail-field">
									<label class="bas-detail-label">Nr. participanţi</label>
									<input type="text" class="bas-detail-input" x-model="fields.participanti" placeholder="ex: 200">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Sonorizare</label>
									<input type="text" class="bas-detail-input" x-model="fields.sonorizare" placeholder="da / nu / proprie">
								</div>
								<div class="bas-detail-field">
									<label class="bas-detail-label">Durata prestaţie</label>
									<input type="text" class="bas-detail-input" x-model="fields.durata" placeholder="ex: 3h">
								</div>

								<div class="bas-detail-field bas-detail-field-full">
									<label class="bas-detail-label">Mesaj / Detalii</label>
									<textarea class="bas-detail-textarea" x-model="fields.mesaj" rows="3" placeholder="Detalii suplimentare..."></textarea>
								</div>

							</div><!-- .bas-detail-grid -->

							<!-- ── Artiști în grup ─────────────────────────── -->
							<div class="bas-group-section">
								<div class="bas-group-section-title">Artiști în acest eveniment</div>

								<!-- Lista artiștilor din grup -->
								<div class="bas-group-members-list" x-show="groupMembers.length > 0">
									<template x-for="member in groupMembers" :key="member.event_id">
										<div class="bas-group-member-item">
											<span class="bas-group-member-name"
												:class="{'bas-group-member-self': member.is_self}"
												x-text="member.artist_name + (member.is_self ? ' ★' : '')"></span>
											<button
												class="bas-btn-remove-member"
												x-show="!member.is_self"
												style="display:none"
												@click="askDeleteGroupMember(member)"
												title="Scoate artistul din eveniment">✕</button>
										</div>
									</template>
								</div>

								<!-- Adaugă artist nou la eveniment -->
								<div class="bas-group-add-row">
									<select class="bas-group-add-select" x-model="addArtistId">
										<option value="">— adaugă artist —</option>
										<?php foreach ( $artists as $artist ) : ?>
											<option value="<?php echo esc_attr( $artist['id'] ); ?>"><?php echo esc_html( $artist['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
									<button class="bas-btn-add-artist"
										@click="addArtistToEvent(<?php echo esc_js( $ev['id'] ); ?>, addArtistId, $el)"
										:disabled="!addArtistId">
										+ Adaugă artist
									</button>
								</div>
							</div><!-- .bas-group-section -->

						</td>
					</tr>

					</tbody>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Modal: adaugă locație -->
			<div class="bas-modal-overlay" x-cloak x-show="modal === 'addLocation'" x-transition @click.self="modal = null">
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
			<div class="bas-modal-overlay" x-cloak x-show="modal === 'deleteLocation'" x-transition @click.self="modal = null">
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
			<div class="bas-modal-overlay" x-cloak x-show="modal === 'deleteEvent'" x-transition @click.self="modal = null">
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

	private static function current_week_monday(): int {
		$n = (int) date( 'N' ); // 1 = Lun, 7 = Dum
		return strtotime( '-' . ( $n - 1 ) . ' days', strtotime( 'today midnight' ) );
	}

	private static function get_year_week( int $offset ): array {
		$ts   = strtotime( "+{$offset} weeks", self::current_week_monday() );
		$year = (int) date( 'Y', $ts );
		$week = (int) date( 'W', $ts );
		return [ $year, $week ];
	}

	private static function get_week_days( int $offset ): array {
		$monday = strtotime( "+{$offset} weeks", self::current_week_monday() );
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

	private static function get_glossary_options( int $glossary_id ): array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT meta_fields FROM {$wpdb->prefix}jet_post_types WHERE id = %d AND status = 'glossary'",
			$glossary_id
		) );
		if ( ! $row || empty( $row->meta_fields ) ) return [];
		$data = maybe_unserialize( $row->meta_fields );
		if ( ! is_array( $data ) ) return [];
		$opts = [];
		foreach ( $data as $opt ) {
			$value = $opt['value'] ?? '';
			if ( $value !== '' ) {
				$opts[] = [
					'value' => $value,
					'label' => $opt['label'] ?? $value,
				];
			}
		}
		return $opts;
	}

	private static function get_artist_name_map(): array {
		$posts = get_posts( [
			'post_type'      => 'artist',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		] );
		$map = [];
		foreach ( $posts as $p ) {
			$map[ $p->post_author ] = $p->post_title;
		}
		return $map;
	}

	public static function get_artists(): array {
		$posts = get_posts( [
			'post_type'      => 'artist',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		return array_map( fn( $p ) => [
			'id'      => $p->post_author, // WordPress user ID
			'post_id' => $p->ID,          // CPT artist post ID (pt. bookings)
			'name'    => $p->post_title,
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

		$months   = [ 1 => 'Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
		$name_map = self::get_artist_name_map();
		$events   = [];

		// ── Build grup map: o singură interogare pentru TOATE grupurile ──────────
		global $wpdb;
		$group_rows = $wpdb->get_results(
			"SELECT p.ID as event_id, p.post_author, m.meta_value as group_id
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'bas_event_group_id'
			 WHERE p.post_type = 'evenimente' AND p.post_status = 'publish' AND m.meta_value > 0",
			ARRAY_A
		);

		$event_to_group   = []; // event_id → group_id
		$group_member_map = []; // group_id → [ event_id → {event_id, artist_id, artist_name} ]
		foreach ( $group_rows as $row ) {
			$eid = (int) $row['event_id'];
			$gid = (int) $row['group_id'];
			$aid = (int) $row['post_author'];
			$event_to_group[ $eid ] = $gid;
			$group_member_map[ $gid ][ $eid ] = [
				'event_id'    => $eid,
				'artist_id'   => $aid,
				'artist_name' => $name_map[ $aid ] ?? '',
				'is_self'     => false,
			];
		}

		foreach ( $posts as $post ) {
			$start_ts  = (int) get_post_meta( $post->ID, 'data-evenimentului', true );
			$end_ts    = (int) get_post_meta( $post->ID, 'data-sfarsit', true );
			$status    = get_post_meta( $post->ID, 'status-eveniment', true );
			$type      = get_post_meta( $post->ID, 'tipul-evenimentului', true );
			$city      = get_post_meta( $post->ID, 'oras-eveniment', true );
			$location  = get_post_meta( $post->ID, 'locatia-evenimentului', true );
			$client    = get_post_meta( $post->ID, 'nume-client', true );
			$artist_id = $post->post_author;

			$start_d = (int) date( 'j', $start_ts );
			$start_m = (int) date( 'n', $start_ts );
			$start_y = (int) date( 'Y', $start_ts );

			if ( 'vacation' === $status && $end_ts && $end_ts > $start_ts ) {
				$end_d = (int) date( 'j', $end_ts );
				$end_m = (int) date( 'n', $end_ts );
				$end_y = (int) date( 'Y', $end_ts );
				$date_label = $start_d . ' ' . $months[ $start_m ] . ' → ' . $end_d . ' ' . $months[ $end_m ] . ' ' . $end_y;
			} else {
				$date_label = $start_d . ' ' . $months[ $start_m ] . ' ' . $start_y;
			}

			$loc_parts   = array_filter( [ $location, $city ] );
			$loc_str     = implode( ', ', $loc_parts ) ?: '—';
			$artist_name = $name_map[ $artist_id ] ?? get_the_author_meta( 'display_name', $artist_id );
			// Pentru vacanțe, tipul e gol — folosim titlul postării (ce a scris artistul)
			$type_label  = $type ?: ( in_array( $status, [ 'vacation', 'vacation_pending' ], true ) ? $post->post_title : '—' );

			// ── Date grup ────────────────────────────────────────────────
			$group_id = $event_to_group[ $post->ID ] ?? 0;
			$group_members = [];
			if ( $group_id > 0 && isset( $group_member_map[ $group_id ] ) ) {
				foreach ( $group_member_map[ $group_id ] as $mid => $mdata ) {
					$group_members[] = array_merge( $mdata, [
						'is_self'    => $mid === $post->ID,
						'date_label' => $date_label,
						'type_label' => $type_label,
						'location'   => $loc_str,
					] );
				}
			}
			$group_count = count( $group_members );

			$events[] = [
				'id'            => $post->ID,
				'date_label'    => $date_label,
				'artist_id'     => $artist_id,
				'artist_name'   => $artist_name,
				'location'      => $loc_str,
				'client'        => $client ?: '—',
				'type'          => strtolower( $type ),
				'type_label'    => $type_label,
				'status'        => $status ?: 'pending',
				'created_ts'    => strtotime( $post->post_date ),
				'confirmed_ts'  => (int) get_post_meta( $post->ID, 'bas_confirmed_at', true ),
				'group_id'      => $group_id,
				'group_count'   => $group_count,
				'group_members' => $group_members,
				'fields'        => [
					'new_artist_id' => $artist_id, // pentru dropdown schimbare artist
					'data_start'    => $start_ts ? date( 'Y-m-d', $start_ts ) : '',
					'data_end'      => $end_ts    ? date( 'Y-m-d', $end_ts )  : '',
					'ora_inceput'   => (string) get_post_meta( $post->ID, 'ora-de-inceput', true ),
					'locatie'       => (string) $location,
					'oras'          => (string) $city,
					'tip'           => (string) $type,
					'client'        => (string) $client,
					'telefon'       => (string) get_post_meta( $post->ID, 'numar-de-telefon', true ),
					'email'         => (string) get_post_meta( $post->ID, 'adresa-de-email', true ),
					'participanti'  => (string) get_post_meta( $post->ID, 'numar-participanti', true ),
					'sonorizare'    => (string) get_post_meta( $post->ID, 'sonorizare-eveniment', true ),
					'durata'        => (string) get_post_meta( $post->ID, 'durata-prestatie', true ),
					'mesaj'         => (string) get_post_meta( $post->ID, 'mesaj-detalii', true ),
				],
			];
		}

		return $events;
	}
}
