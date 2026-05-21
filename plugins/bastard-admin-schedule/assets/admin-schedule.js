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
		artistFilter:    0,

		// Formular eveniment nou
		newEvent: {
			open:      false,
			saving:    false,
			error:     '',
			artist_id: '',
			status:    'pending',
			fields: {
				data_start:   '',
				data_end:     '',
				ora_inceput:  '',
				locatie:      '',
				oras:         '',
				tip:          '',
				client:       '',
				telefon:      '',
				email:        '',
				participanti: '',
				sonorizare:   '',
				durata:       '',
				mesaj:        '',
			},
		},

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

			const extDates = this.conflicts[artistId] || [];
			if (extDates.includes(date)) return true;

			const others = document.querySelectorAll(`.bas-artist-select[data-day="${day}"]`);
			for (const other of others) {
				if (other !== select && parseInt(other.value) === artistId) return true;
			}
			return false;
		},

		getCellClass(select) {
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
				this.captureInitialSchedule();
				this.showNotice('success');
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

		// ── Filtru artist ──────────────────────────────────────────
		filterEvents(artistId) {
			this.artistFilter = artistId;
			document.querySelectorAll('.bas-event-tbody').forEach(tbody => {
				const rowArtistId = parseInt(tbody.dataset.artistId) || 0;
				const visible = !artistId || rowArtistId === artistId;
				tbody.style.display = visible ? '' : 'none';
			});
		},

		// ── Formular eveniment nou ─────────────────────────────────
		resetNewEvent() {
			this.newEvent.artist_id = '';
			this.newEvent.status    = 'pending';
			this.newEvent.error     = '';
			this.newEvent.fields    = {
				data_start: '', data_end: '', ora_inceput: '',
				locatie: '', oras: '', tip: '', client: '',
				telefon: '', email: '', participanti: '',
				sonorizare: '', durata: '', mesaj: '',
			};
		},

		createEvent() {
			if (!this.newEvent.artist_id) {
				this.newEvent.error = 'Selectează un artist.';
				return;
			}
			if (!this.newEvent.fields.data_start) {
				this.newEvent.error = 'Data evenimentului este obligatorie.';
				return;
			}
			this.newEvent.error  = '';
			this.newEvent.saving = true;

			this.ajax('bas_create_event', {
				artist_id: this.newEvent.artist_id,
				status:    this.newEvent.status,
				fields:    this.newEvent.fields,
			}).then(() => {
				this.showNotice('success');
				setTimeout(() => location.reload(), 1500);
			}).catch(err => {
				this.newEvent.error = err.message || 'Eroare la creare. Încearcă din nou.';
			}).finally(() => {
				this.newEvent.saving = false;
			});
		},

		// ── Adaugă artist suplimentar la eveniment ────────────────
		addArtistToEvent(eventId, newArtistId, btn) {
			if (!newArtistId) return;

			if (btn) btn.disabled = true;

			this.ajax('bas_add_artist_to_event', {
				event_id:       eventId,
				new_artist_id:  newArtistId,
			}).then(() => {
				this.showNotice('success');
				setTimeout(() => location.reload(), 1500);
			}).catch(err => {
				alert(err.message || 'Eroare la adăugarea artistului.');
				if (btn) btn.disabled = false;
			});
		},

		// ── Șterge un artist dintr-un grup (confirmă via modal existent) ──
		askDeleteGroupMember(member) {
			this.askDeleteEvent(
				member.event_id,
				member.date_label  || '—',
				member.artist_name || '—',
				member.location    || '—',
				member.type_label  || '—'
			);
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

			tr.querySelectorAll('.bas-new-select').forEach(sel => {
				sel.addEventListener('change', () => self.onSelectChange(sel));
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
				.then(data => {
					if (row) {
						row.dataset.status = status;
						row.className = `bas-row bas-row-${status}`;
					}

					// ── Actualizăm artistul dacă s-a schimbat ──────
					if (data.artist_changed && data.new_artist_name && row) {
						// Actualizăm numele artistului în rândul principal
						const artistCell = row.querySelector('.bas-artist-cell');
						if (artistCell) artistCell.textContent = data.new_artist_name;

						// Actualizăm data-artist pe rând
						row.dataset.artist = data.new_artist_id;

						// Actualizăm data-artist-id pe tbody (pentru filtru)
						const tbody = row.closest('.bas-event-tbody');
						if (tbody) tbody.dataset.artistId = data.new_artist_id;

						// Re-aplicăm filtrul dacă e activ
						if (this.artistFilter) {
							this.filterEvents(this.artistFilter);
						}
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
			const date     = row.dataset.date || '';

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
						const artistId = parseInt(row.dataset.artist);
						const date     = row.dataset.date || '';
						if (date && this.conflicts[artistId]) {
							this.conflicts[artistId] = this.conflicts[artistId].filter(d => d !== date);
						}
						const tbody = row.closest('.bas-event-tbody');
						row.style.transition = 'opacity 0.3s';
						row.style.opacity    = '0';
						setTimeout(() => {
							if (tbody) tbody.remove();
							else row.remove();
							this.refreshAllCells();
						}, 300);
					}

					const slotKey = data?.slot_key || '';
					if (slotKey) {
						const prefix = `${basData.year}_W${basData.week}_`;
						if (slotKey.startsWith(prefix)) {
							const calKey = slotKey.slice(prefix.length);
							const sel = document.querySelector(`.bas-artist-select[data-key="${calKey}"]`);
							if (sel) {
								sel.value = '';
								this.updateCellVisual(sel);
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
