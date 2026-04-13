document.addEventListener('alpine:init', () => {
	Alpine.data('basSchedule', () => ({

		// ── State ──────────────────────────────────────────────────
		modal:           null,
		saving:          false,
		notice:          null,
		noticeTimer:     null,
		modalError:      '',
		newLocation:     { name: '', city: '' },
		pendingDelete:   {},
		conflicts:       {}, // { artist_id: ['YYYY-MM-DD', ...] }
		initialSchedule: {}, // stare inițială pentru dirty tracking
		isDirty:         false,

		// ── Init ───────────────────────────────────────────────────
		init() {
			this.conflicts = JSON.parse(JSON.stringify(basData.conflicts || {}));
			this.$nextTick(() => {
				this.refreshAllCells();
				this.captureInitialSchedule();
			});
		},

		// ── Dirty tracking ─────────────────────────────────────────
		captureInitialSchedule() {
			const s = {};
			document.querySelectorAll('.bas-artist-select').forEach(sel => {
				s[sel.dataset.key] = sel.value || '';
			});
			this.initialSchedule = s;
			this.isDirty = false;
		},

		checkDirty() {
			const selects = document.querySelectorAll('.bas-artist-select');
			for (const sel of selects) {
				if ((this.initialSchedule[sel.dataset.key] ?? '') !== (sel.value || '')) {
					this.isDirty = true;
					return;
				}
			}
			this.isDirty = false;
		},

		// ── Conflict detection ─────────────────────────────────────
		hasConflict(select) {
			if (!select || !select.value) return false;
			const artistId = parseInt(select.value);
			const date     = select.dataset.date;
			const day      = select.dataset.day;

			// Conflict extern: eveniment non-club în aceeași dată
			const extDates = this.conflicts[artistId] || [];
			if (extDates.includes(date)) return true;

			// Conflict intra-calendar: același artist în altă locație aceeași zi
			const others = document.querySelectorAll(`.bas-artist-select[data-day="${day}"]`);
			for (const other of others) {
				if (other !== select && parseInt(other.value) === artistId) return true;
			}
			return false;
		},

		getCellClass(select) {
			// Folosit pentru :class binding — returnează string de clase
			// Nu folosim Alpine reactive pentru selects individuale,
			// gestionăm clasele direct în onSelectChange
			return '';
		},

		updateCellVisual(select) {
			if (!select) return;
			const icon = select.nextElementSibling;
			if (!select.value) {
				select.classList.remove('is-assigned', 'is-conflict');
				if (icon) icon.style.display = 'none';
				return;
			}
			const conflict = this.hasConflict(select);
			select.classList.toggle('is-assigned', true);
			select.classList.toggle('is-conflict', conflict);
			if (icon) icon.style.display = conflict ? 'block' : 'none';
		},

		refreshAllCells() {
			document.querySelectorAll('.bas-artist-select').forEach(s => this.updateCellVisual(s));
		},

		onSelectChange(select) {
			this.updateCellVisual(select);
			// Re-evaluează celelalte celule din aceeași zi
			const day = select.dataset.day;
			document.querySelectorAll(`.bas-artist-select[data-day="${day}"]`).forEach(s => {
				if (s !== select) this.updateCellVisual(s);
			});
			this.checkDirty();
		},

		// ── Salvare program ────────────────────────────────────────
		saveSchedule() {
			if (basData.hasSavedSchedule) {
				if (!confirm('Există deja un program salvat pentru această săptămână. Îl suprascriem?')) return;
			}

			const schedule = {};
			document.querySelectorAll('.bas-artist-select').forEach(sel => {
				const key = sel.dataset.key;
				schedule[key] = sel.value ? parseInt(sel.value) : 0;
			});

			this.saving = true;
			this.ajax('bas_save_schedule', {
				week:     basData.week,
				year:     basData.year,
				schedule: schedule,
			}).then(() => {
				basData.hasSavedSchedule = true;
				this.captureInitialSchedule(); // resetăm baseline → isDirty devine false
				this.showNotice('success');
				// Reîncărcăm pagina după 1.5s pentru a actualiza lista de evenimente
				setTimeout(() => location.reload(), 1500);
			}).catch(() => {
				this.showNotice('error');
			}).finally(() => {
				this.saving = false;
			});
		},

		resetCalendar() {
			document.querySelectorAll('.bas-artist-select').forEach(sel => {
				sel.value = '';
				this.updateCellVisual(sel);
			});
			this.checkDirty();
		},

		// ── Adaugă locație ─────────────────────────────────────────
		openAddLocation() {
			this.newLocation = { name: '', city: '' };
			this.modalError  = '';
			this.modal       = 'addLocation';
			this.$nextTick(() => {
				const input = document.querySelector('.bas-modal-input');
				if (input) input.focus();
			});
		},

		confirmAddLocation() {
			const name = this.newLocation.name.trim();
			if (!name) {
				this.modalError = 'Numele locației este obligatoriu.';
				return;
			}
			this.saving = true;
			this.ajax('bas_add_location', { name, city: this.newLocation.city.trim() })
				.then(data => {
					this.modal = null;
					this.appendLocationRow(data.location);
					this.$nextTick(() => this.refreshAllCells());
				})
				.catch(() => { this.modalError = 'Eroare la adăugare. Încearcă din nou.'; })
				.finally(() => { this.saving = false; });
		},

		appendLocationRow(loc) {
			const tbody = document.getElementById('bas-calendar-body');
			const tr    = document.createElement('tr');
			tr.dataset.location = loc.slug;

			const cityHtml = loc.city
				? `<div class="bas-location-city">${this.esc(loc.city)}</div>`
				: '';

			const self = this;

			let html = `<td class="bas-location-label">
				<div class="bas-location-inner">
					<div>
						<div class="bas-location-name">${this.esc(loc.name)}</div>
						${cityHtml}
					</div>
					<button class="bas-btn-delete-loc bas-new-del-loc"
						data-slug="${this.esc(loc.slug)}"
						data-name="${this.esc(loc.name)}"
						title="Șterge locație">✕</button>
				</div>
			</td>`;

			basData.weekDays.forEach((day, i) => {
				const key = `${loc.slug}_${i}`;
				const artistOptions = basData.artists.map(a =>
					`<option value="${a.id}">${this.esc(a.name)}</option>`
				).join('');

				html += `<td>
					<div class="bas-cell-inner">
						<select class="bas-artist-select bas-new-select"
							data-day="${i}"
							data-date="${day.date}"
							data-key="${key}">
							<option value="">selectează</option>
							${artistOptions}
						</select>
						<span class="bas-conflict-icon" style="display:none">⚠</span>
					</div>
				</td>`;
			});

			tr.innerHTML = html;
			tbody.appendChild(tr);

			// Atașăm event listeners manual pe elementele noi
			tr.querySelectorAll('.bas-new-select').forEach(sel => {
				sel.addEventListener('change', () => self.onSelectChange(sel));
				// Adăugăm în initialSchedule (slot nou = gol)
				self.initialSchedule[sel.dataset.key] = '';
			});
			tr.querySelectorAll('.bas-new-select').forEach(s => s.classList.remove('bas-new-select'));
			tr.querySelectorAll('.bas-new-del-loc').forEach(btn => {
				btn.addEventListener('click', () => self.askDeleteLocation(btn.dataset.slug, btn.dataset.name));
				btn.classList.remove('bas-new-del-loc');
			});
		},

		// ── Șterge locație ─────────────────────────────────────────
		askDeleteLocation(slug, name) {
			this.pendingDelete = { slug, name };
			this.modal = 'deleteLocation';
		},

		confirmDeleteLocation() {
			this.saving = true;
			this.ajax('bas_delete_location', { slug: this.pendingDelete.slug })
				.then(() => {
					const row = document.querySelector(`tr[data-location="${this.pendingDelete.slug}"]`);
					if (row) {
						row.style.transition = 'opacity 0.3s';
						row.style.opacity    = '0';
						setTimeout(() => { row.remove(); this.refreshAllCells(); }, 300);
					}
					this.modal = null;
				})
				.catch(() => { alert('Eroare la ștergere.'); })
				.finally(() => { this.saving = false; });
		},

		// ── Salvare status + detalii eveniment ────────────────────
		saveEvent(eventId, status, fields, btn) {
			const row = document.getElementById(`bas-ev-${eventId}`);

			if (btn) {
				btn.disabled    = true;
				btn.textContent = '...';
				btn.classList.add('is-saving');
			}

			this.ajax('bas_save_event', { event_id: eventId, status, fields })
				.then(() => {
					if (row) {
						row.dataset.status = status;
						row.className = `bas-row bas-row-${status}`;
					}
					this.updateConflictsFromRow(row, status);
					this.refreshAllCells();

					if (btn) {
						btn.classList.remove('is-saving');
						btn.classList.add('is-saved');
						btn.textContent = '✓ Salvat';
						setTimeout(() => {
							btn.textContent = 'Salvează';
							btn.classList.remove('is-saved');
							btn.disabled = false;
						}, 2000);
					}
				})
				.catch(() => {
					alert('Eroare la salvarea evenimentului.');
					if (btn) {
						btn.disabled = false;
						btn.textContent = 'Salvează';
						btn.classList.remove('is-saving');
					}
				});
		},

		updateConflictsFromRow(row, newStatus) {
			if (!row) return;
			const artistId = parseInt(row.dataset.artist);
			const type     = row.dataset.type;
			const day      = row.dataset.day;      // index relativ la săptămâna vizibilă
			const date     = row.dataset.date || '';

			// Tipul "club" nu generează conflicte externe niciodată
			if (type === 'club') return;

			const dates = this.conflicts[artistId] || [];

			if (['confirmed', 'pending', 'vacation'].includes(newStatus)) {
				if (date && !dates.includes(date)) {
					this.conflicts[artistId] = [...dates, date];
				}
			} else if (newStatus === 'canceled') {
				this.conflicts[artistId] = dates.filter(d => d !== date);
			}
		},

		// ── Șterge eveniment ───────────────────────────────────────
		askDeleteEvent(id, date, artist, location, type) {
			this.pendingDelete = { id, date, artist, location, type };
			this.modal = 'deleteEvent';
		},

		confirmDeleteEvent() {
			this.saving = true;
			this.ajax('bas_delete_event', { event_id: this.pendingDelete.id })
				.then((data) => {
					const row = document.getElementById(`bas-ev-${this.pendingDelete.id}`);
					if (row) {
						// Ridicăm conflictele generate de acest eveniment
						const artistId = parseInt(row.dataset.artist);
						const date     = row.dataset.date || '';
						if (date && this.conflicts[artistId]) {
							this.conflicts[artistId] = this.conflicts[artistId].filter(d => d !== date);
						}
						row.style.transition = 'opacity 0.3s';
						row.style.opacity    = '0';
						setTimeout(() => { row.remove(); this.refreshAllCells(); }, 300);
					}

					// Dacă evenimentul era în calendarul săptămânii curente, resetăm celula
					const slotKey = data?.slot_key || '';
					if (slotKey) {
						const prefix = `${basData.year}_W${basData.week}_`;
						if (slotKey.startsWith(prefix)) {
							const calKey = slotKey.slice(prefix.length); // {slug}_{day_index}
							const sel = document.querySelector(`.bas-artist-select[data-key="${calKey}"]`);
							if (sel) {
								sel.value = '';
								this.updateCellVisual(sel);
								// Actualizăm și initialSchedule ca să nu blocheze butonul Salvează
								// dacă user-ul re-selectează același artist
								this.initialSchedule[calKey] = '';
								this.checkDirty();
							}
						}
					}

					this.modal = null;
				})
				.catch(() => { alert('Eroare la ștergere.'); })
				.finally(() => { this.saving = false; });
		},

		// ── AJAX helper ────────────────────────────────────────────
		ajax(action, data = {}) {
			const body = new URLSearchParams({ action, nonce: basData.nonce, ...data });
			// Aplatizăm obiectele (ex: schedule)
			for (const [k, v] of Object.entries(data)) {
				if (typeof v === 'object' && v !== null) {
					body.delete(k);
					for (const [sk, sv] of Object.entries(v)) {
						body.append(`${k}[${sk}]`, sv);
					}
				}
			}
			return fetch(basData.ajaxUrl, { method: 'POST', body })
				.then(r => r.json())
				.then(json => {
					if (!json.success) throw new Error(json.data?.message || 'Eroare');
					return json.data;
				});
		},

		// ── Utilitare ──────────────────────────────────────────────
		showNotice(type) {
			this.notice = type;
			clearTimeout(this.noticeTimer);
			this.noticeTimer = setTimeout(() => { this.notice = null; }, 3000);
		},

		esc(str) {
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		},
	}));
});
