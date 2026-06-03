# Design: Booking Sync, Artist Events List, Dynamic Conflict Map

**Date:** 2026-06-03  
**Plugin:** `bastard-admin-schedule`  
**Status:** Approved (v2 — post spec-review)

---

## Context

Three related fixes for the bastard-admin-schedule WordPress plugin:

1. Events created via WP Admin (not via bastard-admin-schedule) do not create a booking in `wp4u_jet_apartment_bookings`, so dates stay open in the client-facing JetBooking calendar.
2. The JetEngine ListGrid showing an artist's future events takes 30–60 seconds to load.
3. The conflict map in the admin schedule page loads once at page load; new client bookings that arrive while the page is open are not reflected until a full reload.

---

## Fix 1 — Universal Booking Sync (`BAS_Booking_Sync`)

### New file: `includes/class-booking-sync.php`

Hooks into `save_post_evenimente` at priority 20 (after Snippet 7 at default priority 10, which converts dates to Unix timestamps).

### Status spelling

All code (new and existing) uses `'cancelled'` (double `l`) to match values already in the DB and JetBooking's own conventions. The existing `event_to_booking_status()` in `class-ajax-handler.php` currently returns `'canceled'` (single `l`) for the canceled case — this must be corrected to `'cancelled'` as part of this work.

### Status mapping

| `status-eveniment`  | `jet_apartment_bookings.status` |
|---------------------|---------------------------------|
| `confirmed`         | `completed`                     |
| `pending`           | `pending`                       |
| `vacation`          | `on-hold`                       |
| `canceled`          | `cancelled`                     |
| `vacation_pending`  | (no booking — data stays free)  |

### Logic

```
if status = 'vacation_pending':
    DELETE FROM bookings WHERE order_id = $post_id (data stays free until admin approves)
    RETURN

if status = 'canceled':
    UPDATE bookings SET status = 'cancelled' WHERE order_id = $post_id
    RETURN

if status IN (confirmed, pending, vacation):
    existing_id = SELECT booking_id FROM bookings WHERE order_id = $post_id LIMIT 1
    if existing_id:
        UPDATE bookings SET status=..., check_in_date=..., check_out_date=... WHERE booking_id = existing_id
    else:
        INSERT INTO bookings (apartment_id, check_in_date, check_out_date, status, order_id, user_id)
```

The `SELECT ... WHERE order_id = $post_id` check before insert prevents double-booking even though `wp_insert_post` internally fires `save_post` twice (once on insert, once on the implicit update). There is no UNIQUE constraint on `order_id` in the JetBooking table, so the code-level check is the guard.

`check_out_date` falls back to `check_in_date` if `data-sfarsit` is empty or earlier than `check_in_date`.

`apartment_id` is resolved via `get_posts(['post_type'=>'artist','author'=>$post_author,'posts_per_page'=>1])`.

### Guards

- Skip on autosave / revision (`wp_is_post_autosave`, `wp_is_post_revision`)
- Static `$processing` flag on `BAS_Booking_Sync` to prevent re-entrancy

### Coupling with `BAS_Vacation_Request`

`BAS_Vacation_Request::accept_vacation()` currently inserts the booking directly via `$wpdb->insert`. To avoid duplicate booking creation, `accept_vacation()` is refactored to:

1. `update_post_meta($event_id, 'status-eveniment', 'vacation')` — sets status
2. `wp_update_post(['ID' => $event_id])` — triggers `save_post_evenimente`
3. Remove the direct `$wpdb->insert` from `accept_vacation()`

This way BAS_Booking_Sync handles booking creation for all paths. `BAS_Vacation_Request::$processing = true` is already set in the caller (`handle_action_url`) before `accept_vacation()` runs, so `maybe_intercept_vacation()` (priority 5) returns early and does not re-intercept.

### Snippets to deactivate

- **Snippet 9** (Status Sync Evenimente → Bookings) — replaced
- **Snippet 17** (Creare Booking Formular Vacanta) — replaced

Toggle off (not delete) for safety.

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

Single `$wpdb->get_results(..., ARRAY_A)` with direct JOINs:

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
LEFT JOIN wp4u_postmeta m_tip  ON m_tip.post_id  = p.ID AND m_tip.meta_key    = 'tipul-evenimentului'
LEFT JOIN wp4u_postmeta m_loc  ON m_loc.post_id  = p.ID AND m_loc.meta_key    = 'locatia-evenimentului'
LEFT JOIN wp4u_postmeta m_oras ON m_oras.post_id = p.ID AND m_oras.meta_key   = 'oras-eveniment'
LEFT JOIN wp4u_postmeta m_ora  ON m_ora.post_id  = p.ID AND m_ora.meta_key    = 'ora-de-inceput'
WHERE p.post_type   = 'evenimente'
  AND p.post_status = 'publish'
  AND p.post_author = %d
  AND CAST(m_data.meta_value AS UNSIGNED) >= %d
  AND m_status.meta_value IN ('confirmed','pending','vacation','vacation_pending','canceled')
ORDER BY CAST(m_data.meta_value AS UNSIGNED) ASC
```

Parameters: `[$user_id, strtotime('today midnight')]`

Using `ARRAY_A` so rows are accessed as `$event['data_start']`, `$event['status']`, etc.

### HTML output

Rendered as an HTML table. Nonce output inline with the shortcode HTML via `wp_create_nonce('bas_artist_events_nonce')`.

A separator row `── YYYY ──` (full-width, centered, styled) is injected between the last event of one calendar year and the first event of the next:

```php
$current_year = null;
foreach ($events as $event) {
    $year = date('Y', (int) $event['data_start']);
    if ($year !== $current_year) {
        if ($current_year !== null) {
            // emit separator row: ── {$year} ──
        }
        $current_year = $year;
    }
    // emit event row
}
```

**Columns:** Dată | Tip | Locație | Oraș | Ora | Status | Acțiune

**Status badges** (inline CSS, no external dependencies):

| Status | Label | Color |
|--------|-------|-------|
| `confirmed` | Confirmat | green |
| `pending` | Cerere client | yellow |
| `vacation` | Vacanță | blue |
| `vacation_pending` | Vacanță în așteptare | orange |
| `canceled` | Anulat | grey |

**Delete button** conditions (both must be true):
- `status IN ('vacation', 'vacation_pending')`
- `(int) $event['post_author'] === get_current_user_id()`

Click → JS `confirm()` dialog → `fetch()` AJAX → on success, row removed from DOM without page reload.

### AJAX: `bas_artist_delete_vacation`

Registered for logged-in users only (`wp_ajax_` prefix, no `nopriv`).

Server-side checks (in order):
1. `wp_verify_nonce($nonce, 'bas_artist_events_nonce')`
2. `is_user_logged_in()`
3. Post exists and is `post_type = 'evenimente'`
4. `(int) get_post_field('post_author', $event_id) === get_current_user_id()`
5. `get_post_meta($event_id, 'status-eveniment', true) IN ['vacation', 'vacation_pending']`

On pass: `wp_delete_post($event_id, true)` — Snippet 22 handles booking cleanup.

Response: `wp_send_json_success()` → frontend removes the `<tr>` from DOM.

### Registration

In `bastard-admin-schedule.php`:
```php
require_once plugin_dir_path(__FILE__) . 'includes/class-artist-events.php';
BAS_Artist_Events::register();
```

---

## Fix 3 — Dynamic Conflict Map

### New AJAX endpoint `bas_get_conflicts`

Added in `includes/class-ajax-handler.php`:

```php
public static function get_conflicts(): void {
    check_ajax_referer('bas_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error([], 403);
    wp_send_json_success(BAS_Conflict_Detector::get_conflicts_map());
}
```

Registered via the existing `register()` loop by adding `'bas_get_conflicts'` to the actions array.

Response: `{ "7": ["2026-07-03", "2026-07-10"], "12": [...] }` (artist user_id → occupied dates)

### Frontend: `assets/admin-schedule.js`

The existing admin uses `Alpine.data('basSchedule', ...)` with `this.conflicts` as component state. External code cannot mutate component state directly. The approach uses a custom DOM event:

**In `basSchedule.init()`** — add listener (one line):
```js
document.addEventListener('bas:refresh-conflicts', (e) => {
    this.conflicts = e.detail;
    this.$nextTick(() => this.refreshAllCells());
});
```

**In global scope** — refresh function + focusin listener:
```js
let _conflictDebounce = null;

async function basRefreshConflicts() {
    const body = new FormData();
    body.append('action', 'bas_get_conflicts');
    body.append('nonce', basData.nonce);
    const res = await fetch(ajaxurl, { method: 'POST', body });
    const json = await res.json();
    if (json.success) {
        document.dispatchEvent(
            new CustomEvent('bas:refresh-conflicts', { detail: json.data })
        );
    }
}

document.addEventListener('focusin', (e) => {
    if (!e.target.matches('.bas-artist-select')) return;
    clearTimeout(_conflictDebounce);
    _conflictDebounce = setTimeout(basRefreshConflicts, 2000);
});
```

`refreshAllCells()` already exists in the component and re-evaluates conflict highlights for all grid cells.

### Year separator in admin events list

In `class-admin-page.php`, the render loop for future events uses the same pattern as the shortcode:

```php
$current_year = null;
foreach ($events as $event) {
    $year = date('Y', (int) $event['data_start']);
    if ($year !== $current_year) {
        if ($current_year !== null) {
            // emit separator row: ── {$year} ──
        }
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
| `includes/class-vacation-request.php` | Refactor `accept_vacation()`: remove direct `$wpdb->insert`, use `wp_update_post` to trigger BAS_Booking_Sync |
| `includes/class-ajax-handler.php` | Add `get_conflicts` to actions array + method; fix `'canceled'` → `'cancelled'` in `event_to_booking_status()` |
| `includes/class-admin-page.php` | Add year separator in events list render loop |
| `assets/admin-schedule.js` | Add `bas:refresh-conflicts` listener in `init()`; add `basRefreshConflicts()` + focusin debounce in global scope |
| `bastard-admin-schedule.php` | Require two new class files |
| Code Snippets Pro DB | Deactivate Snippet 9 and Snippet 17 |

---

## Out of scope

- Changes to JetEngine ListGrid templates (replaced by shortcode)
- Changes to JetBooking plugin code
- Changes to the "Formular Blocheaza Data" JetEngine form (vacation_pending flow already works correctly via `BAS_Vacation_Request`)
