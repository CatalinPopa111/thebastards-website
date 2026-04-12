# Design Spec: bastard-admin-schedule

**Data:** 2026-04-13
**Status:** Draft
**Stack:** WordPress 6.9.4, PHP, Alpine.js, WP AJAX

---

## 1. Scopul pluginului

Plugin WordPress custom (`bastard-admin-schedule`) care adaugă o pagină admin frontend pentru gestionarea:
1. **Calendarului săptămânal de rezidențiat** — asignare artisti per locație per zi
2. **Listei de evenimente viitoare** — vizualizare și editare inline a statusului

Acces exclusiv pentru utilizatorul cu rol **admin** WordPress.

---

## 2. Arhitectură generală

```
plugins/bastard-admin-schedule/
├── bastard-admin-schedule.php   ← plugin entry point, register menu, enqueue
├── includes/
│   ├── class-admin-page.php     ← render HTML pagina admin
│   ├── class-ajax-handler.php   ← toate endpoint-urile AJAX
│   └── class-conflict-detector.php ← logica de conflict detection
└── assets/
    ├── admin-schedule.js        ← Alpine.js + logica frontend
    └── admin-schedule.css       ← stiluri dark mode
```

**Principii:**
- Fără build step — JS și CSS enqueued direct
- Alpine.js încarcat local (fișier inclus în plugin, nu CDN)
- Toate mutațiile de date prin `admin-ajax.php` cu nonce WP
- Prefix funcții/acțiuni: `bas_` (bastard admin schedule)

---

## 3. Pagina admin WordPress

- Înregistrată cu `add_menu_page()`, accesibilă la `/wp-admin/admin.php?page=bastard-schedule`
- Capability check: `current_user_can('manage_options')` — altfel `wp_die()`
- Titlu meniu: **Schedule**

---

## 4. Secțiunea Calendar Săptămânal

### 4.1 Layout

Grid HTML: rânduri = locații (din meta `_bas_locations` sau CPT `locatie`), coloane = 7 zile ale săptămânii curente (Lun–Dum).

Header săptămână: `Săptămâna DD MMM – DD MMM YYYY` cu navigare prev/next (parametru GET `?week_offset=N`).

### 4.2 Date locații

Locațiile de rezidențiat sunt gestionate **din această pagină**, nu dintr-un CPT separat. Se stochează ca opțiune WordPress:

```php
// format
get_option('bas_locations') => [
  ['name' => 'Club Midi', 'city' => 'București'],
  ['name' => 'Club Suburbia', 'city' => 'Cluj-Napoca'],
]
```

### 4.3 Celule calendar

Fiecare celulă (locație × zi) conține un `<select>` Alpine.js cu:
- Opțiunea default: „selectează" (valoare `""`, text gri, fără chenar)
- Opțiunile artisti: din CPT `artist` (query la render, ordonat alfabetic)
- Stare **asignat**: chenar + text verde (`#6fcf6f`)
- Stare **conflict**: chenar + text verde + iconiță ⚠ portocalie

Programul săptămânii curente se salvează în `wp_options`:

```php
get_option("bas_schedule_{$year}_W{$week}") => [
  'club-midi_0'      => 42,   // post_id artist
  'club-suburbia_1'  => 17,
  // cheie: "{location_slug}_{day_index}"
  // day_index: 0=Luni, 1=Marți, ..., 6=Duminică (ISO week, Luni = 0)
]
```

**Generare slug locație:** La adăugare, slugul se generează din `name` prin `sanitize_title()` WordPress (ex: „Club Midi" → `club-midi`). Slugul este stocat împreună cu `name` și `city` în `bas_locations`:

```php
get_option('bas_locations') => [
  ['name' => 'Club Midi', 'city' => 'București', 'slug' => 'club-midi'],
]
```

Dacă două locații ar genera același slug, la adăugare se adaugă suffix numeric automat (`club-midi-2`). Coliziunile sunt astfel imposibile.

### 4.4 Conflict detection

**Regula:** ⚠ apare dacă artistul selectat are un eveniment de tip non-`club` cu status `confirmed` sau `pending`, sau orice eveniment cu status `vacation`, în aceeași zi calendaristică.

**Excludere:** Evenimentele CPT `evenimente` cu `tipul-evenimentului = club` nu generează conflict (sunt chiar evenimentele create de calendar).

**Intra-calendar:** Același artist selectat la două locații diferite în aceeași zi → ⚠ la ambele celule.

**Implementare:** La render PHP se construiește un array JSON `basConflicts`:

```js
// injectat inline în pagină
window.basConflicts = {
  "42": ["2026-04-17", "2026-04-18", "2026-04-19"], // artist_id => zile ocupate
  "17": ["2026-04-21"]
}
```

Alpine.js verifică la fiecare schimbare de select dacă `basConflicts[artistId]` conține data zilei respective.

### 4.5 Salvare program

Buton **„Salvează programul"** → request AJAX `bas_save_schedule`:
- Payload: `{ week, year, schedule: { "Club Midi_0": 42, ... } }`
- Server: validează nonce, capability, salvează în `wp_options`
- Dacă există deja date pentru săptămâna respectivă → confirm JS (`confirm()`) înainte de trimitere

**Verificare suprascriere:** La apăsarea „Salvează programul", Alpine.js verifică dacă `window.basHasSavedSchedule === true` (flag injectat de PHP la render dacă există deja un program salvat pentru săptămâna curentă). Dacă da, se afișează `confirm()` JS cu mesajul „Există deja un program salvat pentru această săptămână. Îl suprascriem?". Dacă utilizatorul confirmă, se trimite request-ul AJAX.

**Resetează:** golește toate selecturile la „selectează" (fără request AJAX).

### 4.6 Adăugare locație nouă

Buton **+** sub tabel → modal cu câmpuri:
- Nume locație (required)
- Oraș (optional)

La confirmare → AJAX `bas_add_location` → server salvează în `bas_locations` option și returnează JSON cu locația nouă (name, city, slug) → Alpine.js adaugă rândul nou în DOM fără page reload, toate celulele = „selectează".

### 4.7 Ștergere locație

Buton **✕** pe fiecare rând locație → modal confirmare → AJAX `bas_delete_location` → server elimină din `bas_locations` option → la succes Alpine.js elimină rândul din DOM fără page reload. Evenimentele de tip `club` existente asociate locației nu se șterg. Cheile orfane din `bas_schedule_*` opțiuni sunt ignorate la render (dacă slugul nu mai există în `bas_locations`, celula e sărită).

---

## 5. Secțiunea Listă Evenimente Viitoare

### 5.1 Date afișate

Query: CPT `evenimente` cu `data-evenimentului` >= azi, ordonate ASC după dată. Toate artistele.

Coloane tabel:
| Coloana | Sursă |
|---|---|
| Data | meta `data-evenimentului` (timestamp → format `DD MMM YYYY`) |
| Artist | `post_author` → display name |
| Locație / Oraș | meta `oras-eveniment` + `locatia-evenimentului` |
| Client | meta `nume-client` |
| Tip | meta `tipul-evenimentului` (din Glossary JetEngine) |
| Status | meta `status-eveniment` |

### 5.2 Colorare rânduri per status

| Status | Culoare text `.data-cell` |
|---|---|
| `confirmed` | `#ffffff` |
| `pending` | `#777777` |
| `canceled` | `#525252` |
| `vacation` | `#FF6A00` |

### 5.3 Editare inline status

Fiecare rând: `<select>` cu opțiunile `confirmed / pending / canceled / vacation` + buton **„Salvează"**.

Dropdown și buton au același aspect indiferent de status — doar `.data-cell` se colorează.

La salvare → AJAX `bas_update_event_status`:
- Apelează `wp_update_post(['ID' => $event_id, 'meta_input' => ['status-eveniment' => $status]])` — aceasta declanșează hook-ul `save_post` care activează snippet-ul existent de sync status → bookings → iCal
- **Nu** se folosește `update_post_meta()` direct, deoarece nu declanșează `save_post`
- Rândul își schimbă culoarea imediat în frontend (Alpine.js)

### 5.4 Ștergere eveniment

Buton **✕** vizibil **doar** la statusurile `pending` și `canceled`.

Click → modal confirmare cu: data, artist, locație/tip.

Confirmare → AJAX `bas_delete_event`:
- `wp_delete_post($event_id, true)` — ștergere definitivă
- Snippet-ul existent de cleanup booking orfan se declanșează automat
- Rândul dispare cu animație fade-out
- Calendarul se reîmprospătează (recheck conflicte)

### 5.5 Status vacation — afișare interval

Evenimentele cu `status-eveniment = vacation` afișează în coloana **Data** intervalul complet: `DD MMM → DD MMM YYYY` (din `data-evenimentului` și `data-sfarsit`).

---

## 6. Conflict Detection — flux complet

```
1. PHP render → query toate evenimentele viitoare (non-club, confirmed/pending/vacation)
2. Construiește window.basConflicts = { artist_id: ['YYYY-MM-DD', ...] }
3. Alpine.js: la fiecare onchange pe un select:
   a. Dacă value = "" → remove is-assigned, remove ⚠
   b. Dacă value = artist_id:
      - Verifică basConflicts[artist_id].includes(ziua_celulei)
      - Verifică dacă același artist_id apare în alt select cu același data-day
      - Dacă oricare e true → add is-conflict + show ⚠
      - Altfel → add is-assigned, remove ⚠
4. La saveStatus() în lista evenimente → refreshAllCalendar():
   - Funcție client-side pură: re-evaluează toate selecturile din calendar față de
     window.basConflicts (deja în memorie) + starea curentă a celorlalte selecturi
   - NU face request AJAX suplimentar
   - Actualizează window.basConflicts local: dacă statusul s-a schimbat în `canceled`,
     șterge ziua respectivă din conflicte; dacă s-a schimbat în confirmed/pending/vacation
     și tipul e non-club, adaugă ziua în array
5. La confirmDelete() eveniment → șterge artist+zi din window.basConflicts local
   → refreshAllCalendar()
```

---

## 7. AJAX Endpoints

Toate acțiunile verifică `check_ajax_referer('bas_nonce')` și `current_user_can('manage_options')`.

| Acțiune WP | Metodă | Efect |
|---|---|---|
| `bas_save_schedule` | POST | Salvează program săptămână în `wp_options` |
| `bas_add_location` | POST | Adaugă locație în `bas_locations` option |
| `bas_delete_location` | POST | Șterge locație din `bas_locations` option |
| `bas_update_event_status` | POST | Actualizează `status-eveniment` meta |
| `bas_delete_event` | POST | `wp_delete_post()` definitiv |

---

## 8. Design System aplicat

Pagina admin override-uiește stilurile WP admin pentru zona de conținut:

- Fundal: `#0E0E0E`
- Text: `#ffffff`
- Accent: `#FF6A00`
- Font headings: Space Grotesk 600–700 (Google Fonts, enqueued via `wp_enqueue_style`)
- Font body/UI: Inter 400 (Google Fonts, enqueued via `wp_enqueue_style`)
- Border radius: `10px`, border: `1px solid #2a2a2a`

> **Notă dev:** Fonturile sunt încărcate de pe Google Fonts (conexiune externă). Pe medii locale fără internet, browser-ul fallback-uiește silențios la sans-serif — comportament acceptabil pentru un tool admin intern.

---

## 9. Edge Cases

| Situație | Comportament |
|---|---|
| Nicio locație configurată | Calendar gol cu mesaj „Adaugă prima locație cu +" |
| Niciun artist în CPT `artist` | Selecturile au doar opțiunea „selectează" |
| Săptămână fără program salvat | Toate celulele = „selectează" |
| Artist șters din CPT | La render PHP — dacă artist_id nu mai există, celula rămâne „selectează" |
| Eveniment șters din altă parte | Conflictele se recalculează la reload pagină |
| Locație ștearsă cu program salvat | Cheile orfane din `bas_schedule_*` sunt ignorate la render (slug absent din `bas_locations`) |
| Listă evenimente foarte mare | Fără paginare — out of scope. Se afișează maxim 500 evenimente viitoare (limită hardcodată în query `posts_per_page`). |

---

## 10. Excluderi din scope

- Nu se modifică pluginurile JetEngine, JetBooking sau snippeturile existente
- Nu se creează CPT nou `locatie` — locațiile se stochează în `wp_options`
- Nu există funcționalitate de drag & drop
- Nu există notificări email sau push din acest plugin
- Accesul DJ-ilor la propriul calendar rămâne prin mecanismul existent
