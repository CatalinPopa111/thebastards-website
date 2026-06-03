# Design: Booking Sync, Artist Events List, Dynamic Conflict Map

**Date:** 2026-06-03  
**Plugin:** `bastard-admin-schedule`  
**Status:** Approved

---

## Context

Three related fixes for the bastard-admin-schedule WordPress plugin:

1. Events created via WP Admin (not via bastard-admin-schedule) do not create a booking in `wp4u_jet_apartment_bookings`, so dates stay open in the client-facing JetBooking calendar.
2. The JetEngine ListGrid showing an artist's future events takes 30–60 seconds to load.
3. The conflict map in the admin schedule page loads once at page load; new client bookings that arrive while the page is open are not reflected until a full reload.

---

## Fix 1 — Universal Booking Sync (`BAS_Booking_Sync`)

### New file: `includes/class-booking-sync.php`

Hooks into `save_post_evenimente` at priority 20 (after Snippet 7 which converts dates to Unix timestamps, ensuring timestamps are correct when this runs).

### Logic

```
if status = 'vacation_pending'  → delete booking if exists (date stays free until approved)
if status = 'canceled'          → update booking status to 'cancelled' if exists
if status IN (confirmed, pending, vacation):
    if booking exists (order_id = post_id) → UPDATE status + check_in_date + check_out_date
    if no booking exists          → INSERT new booking
```

### Status mapping (consistent with existing code)

| `status-eveniment` | `jet_apartment_bookings.status` |
|--------------------|---------------------------------|
| `confirmed`        | `completed`                     |
| `pending`          | `pending`                       |
| `vacation`         | `on-hold`                       |
| `canceled`         | `cancelled`                     |
| `vacation_pending` | (no booking)                    |

### Guards

- Skip on autosave / revision (`wp_is_post_autosave`, `wp_is_post_revision`)
- Static `$processing` flag to prevent re-entrancy loops with `BAS_Vacation_Request`
- `apartment_id` resolved via `get_posts(['post_type'=>'artist','author'=>$author_id])`
- `check_out_date` falls back to `check_in_date` if `data-sfarsit` is empty or earlier than start

### Snippets to deactivate

- **Snippet 9** (Status Sync Evenimente → Bookings) — replaced by this class
- **Snippet 17** (Creare Booking Formular Vacanta) — replaced by this class

Snippets are toggled off (not deleted) for safety.

### Registration

In `bastard-admin-schedule.php`:
```php
require_once plugin_dir_path(__FILE__) . 'includes/class-booking-sync.php';
BAS_Booking_Sync::register();
```

---

## Fix 2 — Artist Events Shortcode (`BAS_Artist_Events`)

### New file: `includes/class-artist-events.php`

Registers shortcode `[bas_lista_evenimente]` and AJAX action `bas_artist_delete_vacation`.

### Query

Single `$wpdb->get_results()` with direct JOINs on `wp4u_postmeta`:

```sql
SELECT p.ID, p.post_author,
  m_data.meta_value   AS data_start,
  m_status.meta_value AS status,
  m_tip.meta_value    AS tip,
  m_loc.meta_value    AS locatie,
  m_oras.meta_value   AS oras,
  m_ora.meta_value    AS ora_inceput
FROM wp4u_posts p
JOIN  wp4u_postmeta m_data   ON m_data.post_id   = p.ID AND m_data.meta_key   = 'data-evenimentului'
JOIN  wp4u_postmeta m_status ON m_status.post_id = p.ID AND m_status.meta_key = 'status-eveniment'
LEFT JOIN wp4u_postmeta m_tip  ON m_tip.post_id  = p.ID AND m_tip.meta_key   = 'tipul-evenimentului'
LEFT JOIN wp4u_postmeta m_loc  ON m_loc.post_id  = p.ID AND m_loc.meta_key   = 'locatia-evenimentului'
LEFT JOIN wp4u_postmeta m_oras ON m_oras.post_id = p.ID AND m_oras.meta_key  = 'oras-eveniment'
LEFT JOIN wp4u_postmeta m_ora  ON m_ora.post_id  = p.ID AND m_ora.meta_key   = 'ora-de-inceput'
WHERE p.post_type   = 'evenimente'
  AND p.post_status = 'publish'
  AND p.post_author = %d
  AND CAST(m_data.meta_value AS UNSIGNED) >= %d
  AND m_status.meta_value IN ('confirmed','pending','vacation','vacation_pending','canceled')
ORDER BY CAST(m_data.meta_value AS UNSIGNED) ASC
```

Parameters: `[$user_id, strtotime('today midnight')]`

### HTML output

Rendered as an HTML table. A separator row `── YYYY ──` is injected between the last event of one year and the first event of the next year.

**Columns:** Dată | Tip | Locație | Oraș | Ora | Status | Acțiune

**Status badges** (inline CSS, no external dependencies):

| Status | Label | Color |
|--------|-------|-------|
| `confirmed` | Confirmat | green |
| `pending` | Cerere client | yellow |
| `vacation` | Vacanță | blue |
| `vacation_pending` | Vacanță în așteptare | orange |
| `canceled` | Anulat | grey/red |

**Delete button** conditions (both must be true):
- `status IN ('vacation', 'vacation_pending')`
- `post_author == get_current_user_id()`

Click → JS `confirm()` dialog → `fetch()` AJAX → on success, row removed from DOM without page reload.

### AJAX: `bas_artist_delete_vacation`

Registered for logged-in users only (`wp_ajax_` prefix, no `wp_ajax_nopriv_`).

Server-side checks (in order):
1. `wp_verify_nonce($nonce, 'bas_artist_events_nonce')`
2. `is_user_logged_in()`
3. Post exists and is type `evenimente`
4. `(int) get_post_field('post_author', $event_id) === get_current_user_id()`
5. `status-eveniment IN ('vacation', 'vacation_pending')`

On pass:
- `wp_delete_post($event_id, true)` — Snippet 22 handles booking cleanup automatically

Response: `wp_send_json_success()` → frontend removes the `<tr>` from DOM.

### Registration

In `bastard-admin-schedule.php`:
```php
require_once plugin_dir_path(__FILE__) . 'includes/class-artist-events.php';
BAS_Artist_Events::register();
```

---

## Fix 3 — Dynamic Conflict Map

### New AJAX endpoint: `bas_get_conflicts`

Added in `includes/class-ajax-handler.php`:

```php
add_action('wp_ajax_bas_get_conflicts', [__CLASS__, 'get_conflicts']);

public static function get_conflicts(): void {
    check_ajax_referer('bas_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error([], 403);
    wp_send_json_success(BAS_Conflict_Detector::get_conflicts_map());
}
```

Response format: `{ "7": ["2026-07-03", "2026-07-10"], "12": [...] }`

### Frontend: `assets/admin.js`

- Event listener: `focusin` on artist `<select>` dropdowns in the weekly schedule grid
- Debounce: 2 seconds (multiple rapid focuses trigger only one fetch)
- On response: `this.conflictMap = data.data` — Alpine.js reactivity re-renders all conflict badges automatically

```js
let conflictDebounce = null;

document.addEventListener('focusin', (e) => {
    if (!e.target.matches('.bas-artist-select')) return;
    clearTimeout(conflictDebounce);
    conflictDebounce = setTimeout(() => refreshConflictMap(), 2000);
});

async function refreshConflictMap() {
    const body = new FormData();
    body.append('action', 'bas_get_conflicts');
    body.append('nonce', basData.nonce);
    const res = await fetch(ajaxurl, { method: 'POST', body });
    const json = await res.json();
    if (json.success) Alpine.store('schedule').conflictMap = json.data;
}
```

### Year separator in admin events list

In `class-admin-page.php`, method `get_future_events()` (or its render loop): track `$current_year`. When `date('Y', $event_start_ts)` differs from `$current_year`, emit a separator row before the event row and update `$current_year`.

```php
$current_year = null;
foreach ($events as $event) {
    $year = date('Y', $event['data_start']);
    if ($year !== $current_year) {
        // emit separator row: ── {$year} ──
        $current_year = $year;
    }
    // emit event row
}
```

---

## Files changed

| File | Action |
|------|--------|
| `includes/class-booking-sync.php` | New |
| `includes/class-artist-events.php` | New |
| `includes/class-ajax-handler.php` | Add `get_conflicts` action |
| `includes/class-admin-page.php` | Add year separator in events list render |
| `assets/admin.js` | Add focusin listener + debounce + refreshConflictMap |
| `bastard-admin-schedule.php` | Require two new classes |
| Code Snippets Pro DB | Deactivate Snippet 9 and Snippet 17 |

---

## Out of scope

- Changes to JetEngine ListGrid templates (replaced by shortcode)
- Changes to JetBooking plugin code
- Changes to the "Formular Blocheaza Data" JetEngine form (vacation_pending flow already works correctly)
