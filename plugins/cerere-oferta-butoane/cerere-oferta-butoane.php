<?php
/**
 * Plugin Name: Cerere Oferta — Butoane WhatsApp + Salvează Contact
 * Description: Adaugă în email-ul admin de la "Formular Cerere Oferta" un buton WhatsApp (wa.me, text precompletat) și un buton "Salvează contact" (link semnat HMAC → endpoint care livrează un .vcf cu detaliile cererii în câmpul NOTE).
 * Author: The Bastards Agency
 * Version: 1.0.0
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
