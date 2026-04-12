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

## CPT `locatie` (de creat)
- `zile-active` — checkboxes zile săptămână
- `culoare-locatie` — color picker
- `slug-locatie` — text

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

## Plugin nou în dezvoltare
`bastard-admin-schedule` — pagină admin frontend pentru:
- Calendar săptămânal rezidentiat (săptămâna curentă + următoarea)
- 2+ locații fixe configurabile prin CPT `locatie`
- Dropdown artist per zi per locație cu conflict detection
- Salvare program cu confirmare la suprasciere
- Listă evenimente viitoare toate artistele cu status editabil inline
- Evenimente rezidentiat: tip `evenimente`, status `confirmed`, flag `tip-eveniment-intern = rezidentiat`