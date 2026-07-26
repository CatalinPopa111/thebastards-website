<?php
/**
 * Plugin Name: Cerere Oferta — Butoane WhatsApp + Salvează Contact
 * Description: Adaugă în email-ul admin de la "Formular Cerere Oferta" un buton WhatsApp (wa.me, text precompletat) și un buton "Salvează contact" (link semnat HMAC → endpoint care livrează un .vcf cu detaliile cererii în câmpul NOTE).
 * Author: The Bastards Agency
 * Version: 1.1.0
 *
 * Hook principal: jet-engine/forms/booking/email/message_content
 * Gating pe field_nume_artist + field_telefon (formularul de vacanță nu e afectat).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Bastards — Cerere Oferta: butoane WhatsApp + Salvează contact (vCard)
 *
 * Adaugă în email-ul de la "Formular Cerere Oferta" (notificare către admin)
 * două butoane:
 *   1. Salvează contact  -> link semnat către un endpoint care generează un
 *      fișier .vcf cu datele clientului + detaliile cererii în câmpul NOTE.
 *   2. Deschide WhatsApp  -> link wa.me către numărul clientului cu text
 *      precompletat.
 *
 * Gândit să afecteze DOAR formularul de cerere ofertă (verifică prezența
 * câmpurilor field_nume_artist + field_telefon), nu și alte formulare booking.
 */

if ( ! function_exists( 'tba_co_get_field' ) ) {
	/** Citește un câmp din datele formularului, curățat. */
	function tba_co_get_field( $data, $key ) {
		if ( ! is_array( $data ) || ! isset( $data[ $key ] ) ) {
			return '';
		}
		$value = $data[ $key ];
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}
		return trim( wp_strip_all_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'tba_co_normalize_phone' ) ) {
	/**
	 * Normalizează numărul pentru wa.me.
	 *  - 0040... / 00... -> fără 00 (deja internațional)
	 *  - +40... / + ...   -> doar cifrele (deja internațional)
	 *  - 07xxxxxxxx (RO, 10 cifre, începe cu 0) -> 40 + restul
	 *  - 40...            -> lăsat așa
	 *  - orice altceva (număr străin) -> doar cifrele, lăsat așa cum e
	 */
	function tba_co_normalize_phone( $raw ) {
		$raw      = trim( (string) $raw );
		$has_plus = ( strpos( $raw, '+' ) === 0 );
		$digits   = preg_replace( '/\D+/', '', $raw );

		if ( '' === $digits ) {
			return '';
		}

		// 0040... -> 40...
		if ( 0 === strpos( $digits, '00' ) ) {
			return substr( $digits, 2 );
		}

		// +<ceva> -> deja în format internațional
		if ( $has_plus ) {
			return $digits;
		}

		// Format românesc local: 0 urmat de 9 cifre (total 10) -> 40 + rest
		if ( 0 === strpos( $digits, '0' ) && 10 === strlen( $digits ) ) {
			return '40' . substr( $digits, 1 );
		}

		// Deja cu prefix de țară RO sau număr străin — lăsat așa cum e
		return $digits;
	}
}

if ( ! function_exists( 'tba_co_build_wa_text' ) ) {
	/** Construiește textul precompletat pentru WhatsApp. */
	function tba_co_build_wa_text( $data ) {
		$lines = array();

		$lines[] = 'Salut,';
		$lines[] = 'Sunt Catalin Popa, event planner si manager pentru mai multi artisti.';
		$lines[] = '';
		$lines[] = 'Am primit detaliile completate de tine pe site.';
		$lines[] = '';
		$lines[] = 'Detalii cerere: ' . tba_co_get_field( $data, 'field_nume_artist' );
		$lines[] = '• Tip eveniment: ' . tba_co_get_field( $data, 'field_select_tip_eveniment' );
		$lines[] = '• Data: ' . tba_co_get_field( $data, 'field_data' );
		$lines[] = '• Ora Început: ' . tba_co_get_field( $data, 'field_ora_inceput' );
		$lines[] = '• Durata: ' . tba_co_get_field( $data, 'field_durata_prestatie' );
		$lines[] = '';
		$lines[] = '• Orasul: ' . tba_co_get_field( $data, 'field_oras' );
		$lines[] = '• Locația: ' . tba_co_get_field( $data, 'field_locatie' );
		$lines[] = '';
		$lines[] = '• Sonorizare: ' . tba_co_get_field( $data, 'field_sonorizare' );
		$lines[] = '• Nr persoane: ' . tba_co_get_field( $data, 'field_persoane' );
		$lines[] = '';
		$lines[] = 'Detalii client:';
		$lines[] = '• Full Name: ' . tba_co_get_field( $data, 'field_nume' );
		$lines[] = '• Email: ' . tba_co_get_field( $data, 'field_email' );
		$lines[] = '• Telefon: ' . tba_co_get_field( $data, 'field_telefon' );

		return implode( "\n", $lines );
	}
}

if ( ! function_exists( 'tba_co_build_vcard' ) ) {
	/** Construiește conținutul vCard 3.0 (cu detaliile cererii în NOTE). */
	function tba_co_build_vcard( $data ) {
		$name  = tba_co_get_field( $data, 'field_nume' );
		$phone = tba_co_get_field( $data, 'field_telefon' );
		$email = tba_co_get_field( $data, 'field_email' );

		if ( '' === $name ) {
			$name = 'Client The Bastards';
		}

		$note_lines = array(
			'Detalii cerere: ' . tba_co_get_field( $data, 'field_nume_artist' ),
			'Tip eveniment: ' . tba_co_get_field( $data, 'field_select_tip_eveniment' ),
			'Data: ' . tba_co_get_field( $data, 'field_data' ),
			'Ora Început: ' . tba_co_get_field( $data, 'field_ora_inceput' ),
			'Durata: ' . tba_co_get_field( $data, 'field_durata_prestatie' ),
			'Orasul: ' . tba_co_get_field( $data, 'field_oras' ),
			'Locația: ' . tba_co_get_field( $data, 'field_locatie' ),
			'Sonorizare: ' . tba_co_get_field( $data, 'field_sonorizare' ),
			'Nr persoane: ' . tba_co_get_field( $data, 'field_persoane' ),
		);

		$mesaj = tba_co_get_field( $data, 'field_mesaj' );
		if ( '' !== $mesaj ) {
			$note_lines[] = '';
			$note_lines[] = 'Mesaj client: ' . $mesaj;
		}

		$note = implode( "\n", $note_lines );

		$esc = function( $value ) {
			$value = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), $value );
			$value = str_replace( array( "\r\n", "\r", "\n" ), '\\n', $value );
			return $value;
		};

		$vcard  = "BEGIN:VCARD\r\n";
		$vcard .= "VERSION:3.0\r\n";
		$vcard .= 'FN:' . $esc( $name ) . "\r\n";
		$vcard .= 'N:' . $esc( $name ) . ";;;;\r\n";
		if ( '' !== $phone ) {
			$vcard .= 'TEL;TYPE=CELL:' . $esc( $phone ) . "\r\n";
		}
		if ( '' !== $email ) {
			$vcard .= 'EMAIL;TYPE=INTERNET:' . $esc( $email ) . "\r\n";
		}
		$vcard .= 'NOTE:' . $esc( $note ) . "\r\n";
		$vcard .= "END:VCARD\r\n";

		return array( 'vcf' => $vcard, 'fn' => $name );
	}
}

if ( ! function_exists( 'tba_co_vcard_url' ) ) {
	/** Construiește link-ul semnat către endpoint-ul care livrează .vcf-ul. */
	function tba_co_vcard_url( $data ) {
		$payload = tba_co_build_vcard( $data );
		$json    = wp_json_encode( $payload );
		$b64     = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
		$sig     = hash_hmac( 'sha256', $b64, wp_salt( 'auth' ) );

		return add_query_arg(
			array(
				'tba_vcard' => '1',
				'p'         => $b64,
				's'         => $sig,
			),
			home_url( '/' )
		);
	}
}

if ( ! function_exists( 'tba_co_render_buttons' ) ) {
	/** HTML-ul cu cele două butoane (email-safe, inline styles). */
	function tba_co_render_buttons( $data ) {
		$phone   = tba_co_normalize_phone( tba_co_get_field( $data, 'field_telefon' ) );
		$wa_text = tba_co_build_wa_text( $data );
		$wa_url  = $phone ? 'https://wa.me/' . $phone . '?text=' . rawurlencode( $wa_text ) : '';
		$vcf_url = esc_url( tba_co_vcard_url( $data ) );

		$btn_base = 'display:inline-block;padding:14px 22px;margin:6px 8px 6px 0;border-radius:8px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;text-decoration:none;line-height:1;';

		$html  = '<div style="margin-top:24px;padding-top:18px;border-top:1px solid #e0e0e0;font-family:Arial,Helvetica,sans-serif;">';
		$html .= '<div style="font-size:13px;text-transform:uppercase;letter-spacing:1px;color:#888;margin-bottom:12px;">Acțiuni rapide</div>';
		$html .= '<a href="' . $vcf_url . '" style="' . $btn_base . 'background:#1a1a1a;color:#ffffff;">📇 Salvează contact</a>';
		if ( $wa_url ) {
			// esc_attr (nu esc_url): esc_url sterge %0A din WhatsApp -> textul si-ar pierde randurile.
			$html .= '<a href="' . esc_attr( $wa_url ) . '" style="' . $btn_base . 'background:#25D366;color:#ffffff;">💬 Deschide WhatsApp</a>';
		}
		$html .= '</div>';

		return $html;
	}
}

// ── 1. Adaugă butoanele în conținutul email-ului de cerere ofertă ──────────
add_filter(
	'jet-engine/forms/booking/email/message_content',
	function( $content, $notification ) {
		$data = ( isset( $notification->data ) && is_array( $notification->data ) ) ? $notification->data : array();

		// Gating: doar formularul de cerere ofertă are aceste câmpuri.
		if ( '' === tba_co_get_field( $data, 'field_nume_artist' ) || '' === tba_co_get_field( $data, 'field_telefon' ) ) {
			return $content;
		}

		return $content . tba_co_render_buttons( $data );
	},
	20,
	2
);

// ── 2. Endpoint care livrează fișierul .vcf ────────────────────────────────
add_action(
	'init',
	function() {
		if ( empty( $_GET['tba_vcard'] ) ) {
			return;
		}

		$b64 = isset( $_GET['p'] ) ? (string) $_GET['p'] : '';
		$sig = isset( $_GET['s'] ) ? (string) $_GET['s'] : '';

		if ( '' === $b64 || '' === $sig ) {
			status_header( 400 );
			exit;
		}

		$expected = hash_hmac( 'sha256', $b64, wp_salt( 'auth' ) );
		if ( ! hash_equals( $expected, $sig ) ) {
			status_header( 403 );
			exit;
		}

		$json = base64_decode( strtr( $b64, '-_', '+/' ), true );
		$data = $json ? json_decode( $json, true ) : null;

		if ( ! is_array( $data ) || empty( $data['vcf'] ) ) {
			status_header( 400 );
			exit;
		}

		$filename = ! empty( $data['fn'] ) ? $data['fn'] : 'contact';
		$filename = preg_replace( '/[^\p{L}\p{N}\-_ ]+/u', '', $filename );
		$filename = trim( preg_replace( '/\s+/', '_', $filename ) );
		if ( '' === $filename ) {
			$filename = 'contact';
		}

		nocache_headers();
		header( 'Content-Type: text/vcard; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '.vcf"' );
		echo $data['vcf'];
		exit;
	},
	1
);

// ═══════════════════════════════════════════════════════════════════════════
//  Anti-dublare submit + email de confirmare către client
// ═══════════════════════════════════════════════════════════════════════════

if ( ! function_exists( 'tba_co_is_offer_form' ) ) {
	/** Doar formularul de cerere ofertă are aceste câmpuri (nu și cel de vacanță). */
	function tba_co_is_offer_form( $data ) {
		return '' !== tba_co_get_field( $data, 'field_nume_artist' )
		    && '' !== tba_co_get_field( $data, 'field_telefon' );
	}
}

if ( ! function_exists( 'tba_co_dedup_key' ) ) {
	/** Cheie idempotentă pe email + dată + artist. */
	function tba_co_dedup_key( $data ) {
		$parts = strtolower( tba_co_get_field( $data, 'field_email' ) )
		       . '|' . tba_co_get_field( $data, 'field_data' )
		       . '|' . strtolower( tba_co_get_field( $data, 'field_nume_artist' ) );
		return 'tba_co_lock_' . md5( $parts );
	}
}

// ── Backstop server-side: oprește cererile duplicate (dublu/triplu-click) ──
// redirect() închide request-ul (wp_send_json / die), deci a doua cerere identică
// primită în fereastra de blocare NU mai creează postare/booking; clientul vede „success".
add_action(
	'jet-engine/forms/handler/before-send',
	function ( $handler ) {
		$data = ( isset( $handler->form_data ) && is_array( $handler->form_data ) ) ? $handler->form_data : array();

		if ( ! tba_co_is_offer_form( $data ) ) {
			return;
		}

		$key = tba_co_dedup_key( $data );

		if ( get_transient( $key ) ) {
			// Duplicat în fereastra de 60s → răspunde success fără a re-procesa.
			$handler->redirect( array( 'status' => 'success' ) );
			return; // redirect() oricum termină request-ul
		}

		set_transient( $key, 1, 60 );
	},
	5,
	1
);

// ── Email de confirmare către client (cu rezumatul datelor introduse) ──
add_action(
	'jet-engine/forms/handler/after-send',
	function ( $handler, $success ) {
		$data = ( isset( $handler->form_data ) && is_array( $handler->form_data ) ) ? $handler->form_data : array();

		if ( ! tba_co_is_offer_form( $data ) ) {
			return;
		}

		// Dacă trimiterea a eșuat, eliberăm lock-ul ca un retry legitim să fie posibil imediat.
		if ( true !== $success ) {
			delete_transient( tba_co_dedup_key( $data ) );
			return;
		}

		$email = tba_co_get_field( $data, 'field_email' );
		if ( ! $email || ! is_email( $email ) ) {
			return;
		}

		tba_co_send_client_confirmation( $data, $email );
	},
	20,
	2
);

if ( ! function_exists( 'tba_co_send_client_confirmation' ) ) {
	/** Trimite clientului confirmarea de primire + rezumatul cererii. */
	function tba_co_send_client_confirmation( $data, $email ) {
		$nume = tba_co_get_field( $data, 'field_nume' );

		// Rezumatul datelor introduse (doar câmpurile completate)
		$rows_def = array(
			'Artist / cerere' => tba_co_get_field( $data, 'field_nume_artist' ),
			'Tip eveniment'   => tba_co_get_field( $data, 'field_select_tip_eveniment' ),
			'Data'            => tba_co_get_field( $data, 'field_data' ),
			'Ora început'     => tba_co_get_field( $data, 'field_ora_inceput' ),
			'Durata'          => tba_co_get_field( $data, 'field_durata_prestatie' ),
			'Oraș'            => tba_co_get_field( $data, 'field_oras' ),
			'Locația'         => tba_co_get_field( $data, 'field_locatie' ),
			'Sonorizare'      => tba_co_get_field( $data, 'field_sonorizare' ),
			'Nr. persoane'    => tba_co_get_field( $data, 'field_persoane' ),
		);

		$rows_client = array(
			'Nume'    => $nume,
			'Email'   => tba_co_get_field( $data, 'field_email' ),
			'Telefon' => tba_co_get_field( $data, 'field_telefon' ),
		);

		$row_html = function ( $label, $value ) {
			if ( '' === trim( (string) $value ) ) {
				return '';
			}
			return '<tr>'
				. '<td style="padding:9px 16px 9px 0;border-bottom:1px solid #f5f5f5;color:#888;font-size:12px;white-space:nowrap;vertical-align:top;width:130px;">' . esc_html( $label ) . '</td>'
				. '<td style="padding:9px 0;border-bottom:1px solid #f5f5f5;color:#1a1a1a;font-size:13px;font-weight:500;">' . esc_html( $value ) . '</td>'
				. '</tr>';
		};

		$det = '';
		foreach ( $rows_def as $label => $value ) {
			$det .= $row_html( $label, $value );
		}

		$cli = '';
		foreach ( $rows_client as $label => $value ) {
			$cli .= $row_html( $label, $value );
		}

		$mesaj      = tba_co_get_field( $data, 'field_mesaj' );
		$mesaj_html = '';
		if ( '' !== $mesaj ) {
			$mesaj_html = '<tr><td colspan="2" style="padding:20px 0 10px;">'
				. '<div style="font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:#aaa;border-top:1px solid #f0f0f0;padding-top:16px;">Mesajul tău</div>'
				. '</td></tr>'
				. '<tr><td colspan="2" style="padding:8px 0;color:#444;font-size:13px;line-height:1.6;">' . nl2br( esc_html( $mesaj ) ) . '</td></tr>';
		}

		$greet = $nume ? 'Salut, ' . esc_html( $nume ) . '!' : 'Salut!';

		$content = '
			<div style="display:inline-block;background:#fff3e0;color:#e65100;font-size:12px;font-weight:bold;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
				✓ Cerere primită
			</div>
			<h2 style="margin:0 0 12px;font-size:20px;color:#1a1a1a;font-weight:bold;">' . $greet . '</h2>
			<p style="margin:0 0 22px;color:#444;font-size:14px;line-height:1.6;">
				Am primit cererea ta de ofertă și revenim în cel mai scurt timp cu un răspuns.
				Mai jos ai un rezumat al datelor pe care le-ai completat.
			</p>
			<table width="100%" cellpadding="0" cellspacing="0">
				' . $det . '
				<tr><td colspan="2" style="padding:20px 0 10px;">
					<div style="font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:#aaa;border-top:1px solid #f0f0f0;padding-top:16px;">Datele tale de contact</div>
				</td></tr>
				' . $cli . '
				' . $mesaj_html . '
			</table>
			<p style="margin:24px 0 0;color:#888;font-size:12px;line-height:1.6;">
				Dacă vreun detaliu este greșit, poți răspunde direct la acest email.
			</p>
		';

		$subject = 'Am primit cererea ta — The Bastards Agency';
		$html    = tba_co_email_wrapper( $content );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: The Bastards Agency <hello@thebastards.ro>',
			'Reply-To: The Bastards Agency <hello@thebastards.ro>',
		);

		wp_mail( $email, $subject, $html, $headers );
	}
}

if ( ! function_exists( 'tba_co_email_wrapper' ) ) {
	/** Șablon email brandat, self-contained (fără dependință de alt plugin). */
	function tba_co_email_wrapper( $content ) {
		return '<!DOCTYPE html><html lang="ro"><head><meta charset="UTF-8">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
			. '<body style="margin:0;padding:0;background-color:#f2f2f2;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">'
			. '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f2f2f2;padding:32px 16px;"><tr><td align="center">'
			. '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">'
			. '<tr><td style="background:#111111;padding:22px 32px;"><span style="color:#FF6A00;font-size:18px;font-weight:bold;letter-spacing:-0.3px;font-family:Arial,sans-serif;">The Bastards Agency</span></td></tr>'
			. '<tr><td style="padding:32px;">' . $content . '</td></tr>'
			. '<tr><td style="background:#f9f9f9;padding:16px 32px;border-top:1px solid #eeeeee;"><span style="color:#bbb;font-size:11px;">The Bastards Agency &bull; <a href="https://thebastards.ro" style="color:#bbb;text-decoration:none;">thebastards.ro</a></span></td></tr>'
			. '</table></td></tr></table></body></html>';
	}
}

// ── Blocare buton la submit (anti dublu/triplu-click, front-end) ──
add_action(
	'wp_footer',
	function () {
		if ( is_admin() ) {
			return;
		}
		?>
<script>
(function () {
	// Formularele JetEngine de tip „reload" fac submit nativ — blocăm re-trimiterea.
	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (!f || !f.classList || !f.classList.contains('jet-form')) return;
		if (f.dataset.tbaSubmitting === '1') { e.preventDefault(); e.stopImmediatePropagation(); return false; }
		f.dataset.tbaSubmitting = '1';
		var btns = f.querySelectorAll('button[type=submit], input[type=submit], .jet-form__submit');
		btns.forEach(function (b) {
			b.disabled = true;
			b.style.opacity = '0.6';
			if (b.tagName === 'BUTTON') {
				if (!b.dataset.tbaLabel) b.dataset.tbaLabel = b.innerHTML;
				b.innerHTML = 'Se trimite…';
			}
		});
		// Fallback de siguranță (ex. eroare de validare fără reload)
		setTimeout(function () {
			f.dataset.tbaSubmitting = '';
			btns.forEach(function (b) {
				b.disabled = false;
				b.style.opacity = '';
				if (b.tagName === 'BUTTON' && b.dataset.tbaLabel) b.innerHTML = b.dataset.tbaLabel;
			});
		}, 8000);
	}, true);

	// Formularele de tip „ajax" folosesc click pe buton — blocăm click-urile rapide repetate.
	document.addEventListener('click', function (e) {
		var b = e.target.closest ? e.target.closest('.jet-form__submit.submit-type-ajax') : null;
		if (!b) return;
		var f = b.closest('.jet-form');
		if (!f) return;
		if (f.dataset.tbaClicking === '1') { e.preventDefault(); e.stopImmediatePropagation(); return false; }
		f.dataset.tbaClicking = '1';
		setTimeout(function () { f.dataset.tbaClicking = ''; }, 8000);
	}, true);
})();
</script>
		<?php
	}
);
