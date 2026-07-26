# The Bastards Agency — WordPress Site

## Stack
- WordPress 6.9.4
- JetEngine 3.8.6.2 (CPT, Meta Boxes, Relations, Query Builder, Forms)
- JetBooking 4.0.4
- JetSmartFilters 3.7.5
- Elementor Pro 3.35.1
- Code Snippets Pro 3.9.5
- Prefix DB: wp4u_
- Local dev: http://the-bastards-agency.local
- Live: https://thebastards.ro

## CPT Structure
- `artist` — profil artist, legat de User WordPress ca autor
- `evenimente` — rezervări/calendar, child al `artist` prin relație JetEngine

## Meta Fields `evenimente`
- `data-evenimentului` — timestamp (check-in)
- `data-sfarsit` — timestamp (check-out)
- `status-eveniment` — pending / confirmed / vacation / canceled
- `tipul-evenimentului` — din Glossary JetEngine
- `oras-eveniment`, `locatia-evenimentului`, `nume-client`
- `numar-de-telefon`, `adresa-de-email`
- `ora-de-inceput`, `numar-participanti`
- `sonorizare-eveniment`, `durata-prestatie`
- `mesaj-detalii`
- `tip-eveniment-intern` — flag special (ex: `rezidentiat`)
- `locatie-rezidentiat` — ID postare CPT `locatie`
- `bas_confirmed_at` — timestamp setat la trecerea în `confirmed` (data acceptării)
- `bas_canceled_at` — timestamp setat la trecerea în `canceled` (pornește ciclul de ștergere; se șterge dacă iese din canceled)

## CPT `locatie` (de creat)
- `zile-active` — checkboxes zile săptămână
- `culoare-locatie` — color picker
- `slug-locatie` — text

## Ciclul de viață cereri (bastard-admin-schedule ≥ 1.7.0)
- **Pending nu blochează calendarul**: filtre pe `jet-booking/statuses/{invalid,valid,in-progress}` fac status booking `pending` neutru (ignorat de toate verificările de disponibilitate + exclus din iCal). Mai mulți clienți pot cere aceeași dată; abia confirmarea (→ `completed`) blochează.
- **Cron zilnic `bas_daily_lifecycle`** (`class-lifecycle.php`): (a) `pending` mai vechi de 7 zile după `post_date` → `canceled`; (b) `canceled` mai vechi de 30 zile după `bas_canceled_at` → trash; (c) trash mai vechi de 90 zile după `_wp_trash_meta_time` → ștergere definitivă.
- **Curățare lanț** pe `before_delete_post` (evenimente): șterge relația JetEngine `jet_rel_default` + curăță `bas_event_group_id`. Booking-ul e șters de Snippet 22 (before_delete/wp_trash).

## JetBooking
- Tabel: `wp4u_jet_apartment_bookings`
- `order_id` = ID postare `evenimente`
- `apartment_id` = ID postare `artist`
- Status sync cu `status-eveniment`

## Formulare JetEngine
- `Formular Cerere Oferta` — creat de client, creează `evenimente` + booking nativ (Apartment booking action)
- `Formular Blocheaza Data` — creat de artist, vacanță cu `status-eveniment = vacation`

## Snippets active (Code Snippets Pro)
- Conversie data in timestamp la save_post_evenimente
- Creare booking vacanta (doar vacation status)
- Modificare titlu evenimente automat
- Status sync evenimente → bookings
- Stergere eveniment = sterge booking + cleanup orfani (cron 5s)
- iCal titlu si detalii eveniment
- Fix nume artist in email
- Shortcode artist_post_id
- Shortcode link_ical_final
- MixCloud player shortcode

## Pluginuri proprii
- `plugins/cerere-oferta-butoane/` — adaugă în email-ul admin de la `Formular Cerere Oferta` un buton WhatsApp (wa.me, text precompletat) și un buton "Salvează contact" (link semnat HMAC → endpoint `?tba_vcard=1` care livrează un `.vcf` cu detaliile cererii în câmpul NOTE). Hook: `jet-engine/forms/booking/email/message_content`. Gating pe `field_nume_artist` + `field_telefon` (nu afectează formularul de vacanță). Necesită activare din admin (plugin normal, nu mu-plugin — deploy-ul Git nu crea foldere noi de nivel înalt sub wp-content).
  - **≥ 1.1.0**: anti-dublare submit (guard idempotent transient 60s pe `before-send`, cheie email+dată+artist, eliberat la eșec în `after-send`) + JS blocare buton la submit + email de confirmare către client cu rezumatul cererii (`after-send`, formular `jet-engine-booking` ID 1263).

## Plugin nou în dezvoltare
`bastard-admin-schedule` — pagină admin frontend pentru:
- Calendar săptămânal rezidentiat (săptămâna curentă + următoarea)
- 2+ locații fixe configurabile prin CPT `locatie`
- Dropdown artist per zi per locație cu conflict detection
- Salvare program cu confirmare la suprasciere
- Listă evenimente viitoare toate artistele cu status editabil inline
- Evenimente rezidentiat: tip `evenimente`, status `confirmed`, flag `tip-eveniment-intern = rezidentiat`