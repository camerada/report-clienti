<?php
// Invio con Brevo, PDF in allegato.
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
