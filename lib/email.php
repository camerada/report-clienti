<?php
// Invio con Brevo, PDF in allegato. Testi delle email (al cliente e anteprima a CAME) e link del pulsante «Invia al cliente».
function invia_email(array $a, string $oggetto, string $html, string $pdf, string $nomeFile, array $cc = []): void {
  $corpo = [
    'sender' => ['email' => MITTENTE_EMAIL, 'name' => MITTENTE_NOME],
    'to' => array_map(fn($e) => ['email' => $e], $a),
    'subject' => $oggetto,
    'htmlContent' => $html,
  ];
  if ($pdf !== '') $corpo['attachment'] = [['content' => base64_encode($pdf), 'name' => $nomeFile]];
  if ($cc) $corpo['cc'] = array_map(fn($e) => ['email' => $e], $cc);
  http_json(defined('BREVO_API_URL') ? BREVO_API_URL : 'https://api.brevo.com/v3/smtp/email', $corpo, null,
    ['api-key: ' . BREVO_API_KEY, 'Accept: application/json']);
}

function oggetto_cliente(string $mese): string { return "Il tuo sito, $mese: il report"; }

function email_cliente(array $c, array $t, string $mese): string {
  $wa = whatsapp_url($c['sito'], str_starts_with($mese, 'dal ') ? $mese : "di $mese");
  return '<p>Buongiorno,</p><p>in allegato il report del tuo sito <b>' . htmlspecialchars($c['sito']) . "</b> per $mese.</p>"
    . '<p>' . htmlspecialchars($t['in_breve']) . '</p>'
    . ($wa ? '<p>In fondo al report trovi qualche consiglio. Vuoi approfondirlo? <a href="' . htmlspecialchars($wa) . '">Scrivimi su WhatsApp</a>, il messaggio è già pronto.</p>' : '')
    . '<p>Per qualsiasi domanda rispondi pure a questa email.</p><p>Maurizio Camerada<br>CAME Comunicazione</p>';
}

// Anteprima a CAME. Se il cliente ha un'email: riquadro con destinatario, testo che riceverà e pulsante «Invia al cliente».
function email_anteprima(array $c, array $t, string $mese, string $id, string $chiave): string {
  $h = '';
  if (!empty($c['email'])) {
    $h .= '<div style="border:2px solid #00596D;border-radius:8px;padding:16px 18px;margin:0 0 22px;font-family:Arial,sans-serif">'
      . '<p style="margin:0 0 6px"><b>Da inviare a:</b> ' . htmlspecialchars(implode(', ', $c['email'])) . ' <span style="color:#4F5D62">(tu in copia)</span><br>'
      . '<b>Oggetto:</b> ' . htmlspecialchars(oggetto_cliente($mese)) . '</p>'
      . '<div style="background:#F7F9F7;border-left:4px solid #A4D233;padding:4px 14px;margin:10px 0 14px">' . email_cliente($c, $t, $mese) . '</div>'
      . '<p style="margin:0 0 8px"><a href="' . htmlspecialchars(url_invio($id, $chiave)) . '" style="display:inline-block;background:#A4D233;color:#00596D;font-weight:bold;'
      . 'padding:12px 24px;border-radius:24px;text-decoration:none">Invia al cliente</a></p>'
      . '<p style="margin:0;font-size:12px;color:#4F5D62">Si apre una pagina di conferma: il report parte solo quando premi «Conferma invio», una volta sola.</p></div>';
  }
  return $h . '<p>Report di <b>' . htmlspecialchars($c['nome']) . "</b> per $mese" . (empty($c['email']) ? ' (sito nostro: solo per te, nessun invio).' : ', da controllare.') . '</p>'
    . '<p>' . htmlspecialchars($t['in_breve']) . '</p>';
}

// Link firmato del pulsante: vale solo per quel cliente e quel periodo (vedi invia.php)
function token_invio(string $id, string $chiave): string {
  $segreto = defined('SEGRETO_INVIO') && SEGRETO_INVIO !== '' ? SEGRETO_INVIO : hash('sha256', 'invio-report|' . BREVO_API_KEY);
  return hash_hmac('sha256', "$id|$chiave", $segreto);
}
function url_invio(string $id, string $chiave): string {
  return (defined('URL_INVIO') ? URL_INVIO : 'https://camecomunicazione.it/report/invia.php')
    . '?' . http_build_query(['c' => $id, 'p' => $chiave, 't' => token_invio($id, $chiave)]);
}
