<?php
/**
 * Pulsante «Invia al cliente» dell'anteprima: manda al cliente il report già pronto in uscite/, con CAME in copia.
 * Lo apre web/invia.php (copiata da carica.sh in camecomunicazione.it/report/invia.php); questo file resta fuori dai siti.
 *
 *   GET  ?c=cliente&p=periodo&t=firma   pagina di conferma (non invia niente: Gmail e antivirus aprono i link da soli)
 *   POST stessi campi                   invia, una volta sola (registro uscite/registro.csv, riga «inviato»)
 */
date_default_timezone_set('Europe/Rome');
require __DIR__ . '/config.php';
foreach (['google', 'dati', 'testo', 'pdf', 'impagina', 'email'] as $f) require __DIR__ . "/lib/$f.php";
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$clienti = require __DIR__ . '/clienti.php';
$id = (string)($_REQUEST['c'] ?? ''); $chiave = (string)($_REQUEST['p'] ?? ''); $firma = (string)($_REQUEST['t'] ?? '');
if (!isset($clienti[$id]) || !preg_match('/^\d{4}-\d{2}(-\d{2}_\d{4}-\d{2}-\d{2})?$/', $chiave) || !hash_equals(token_invio($id, $chiave), $firma)) {
  http_response_code(403); pagina('Link non valido', '<p>Questo link non è valido. Usa il pulsante nell\'email dell\'anteprima.</p>');
}
$c = $clienti[$id] + PREDEFINITI;
if (empty($c['email'])) pagina('Nessun indirizzo', '<p>Per ' . h($c['nome']) . ' non c\'è un indirizzo email in clienti.php: il report non si può inviare da qui.</p>');

$nome = "report-{$c['sito']}-$chiave.pdf";
$fPdf = __DIR__ . "/uscite/$id/$nome"; $fJson = __DIR__ . "/uscite/$id/$chiave.json";
if (!is_file($fPdf) || !is_file($fJson)) pagina('Report non trovato', '<p>Il report di ' . h($c['nome']) . " ($chiave) non c'è più sul server. Rifallo con <code>php report.php --cliente=$id</code>.</p>");
$x = json_decode(file_get_contents($fJson), true);
$t = $x['testi']; $meseIt = $x['dati']['periodo']['etichetta'] ?? mese_italiano($x['dati']['mese']);
$a = implode(', ', $c['email']);

$registro = __DIR__ . '/uscite/registro.csv';
$lock = fopen(__DIR__ . '/uscite/.invio.lock', 'c');
flock($lock, LOCK_EX);   // due clic insieme: il secondo aspetta e trova già «inviato»
if (is_file($registro) && preg_match('/^' . preg_quote("$id;$chiave;", '/') . '([^;]*);inviato$/m', file_get_contents($registro), $m)) {
  pagina('Già inviato', '<p>Il report di <b>' . h($c['nome']) . "</b> per $meseIt è già stato inviato a " . h($a) . ' il ' . date('d/m/Y \a\l\l\e H:i', strtotime($m[1])) . '.</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    invia_email($c['email'], oggetto_cliente($meseIt), email_cliente($c, $t, $meseIt), file_get_contents($fPdf), $nome, [EMAIL_CAME]);
  } catch (Throwable $e) {
    pagina('Invio non riuscito', '<p>Il report non è partito: ' . h($e->getMessage()) . '</p><p>Riprova più tardi dallo stesso pulsante.</p>');
  }
  file_put_contents($registro, "$id;$chiave;" . date('c') . ";inviato\n", FILE_APPEND);
  pagina('Inviato', '<p>Fatto: il report di <b>' . h($c['nome']) . "</b> per $meseIt è partito a <b>" . h($a) . '</b>, con te in copia.</p>');
}

pagina('Invia il report a ' . $c['nome'],
  '<p><b>A:</b> ' . h($a) . ' <span class="tenue">(tu in copia)</span><br><b>Oggetto:</b> ' . h(oggetto_cliente($meseIt)) . '<br><b>Allegato:</b> ' . h($nome) . '</p>'
  . '<div class="email">' . email_cliente($c, $t, $meseIt) . '</div>'
  . '<form method="post"><input type="hidden" name="c" value="' . h($id) . '"><input type="hidden" name="p" value="' . h($chiave) . '"><input type="hidden" name="t" value="' . h($firma) . '">'
  . '<button type="submit">Conferma invio</button></form>');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }
function pagina(string $titolo, string $corpo): void {
  echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">'
    . '<title>' . h($titolo) . '</title><style>body{font-family:system-ui,Arial,sans-serif;background:#EEF1EF;color:#1E2A2E;margin:0;padding:24px 16px}'
    . 'main{max-width:640px;margin:0 auto;background:#fff;border-radius:10px;padding:24px;border-top:6px solid #00596D}h1{color:#00596D;font-size:22px;margin:0 0 16px}'
    . '.email{background:#F7F9F7;border-left:4px solid #A4D233;padding:4px 16px;margin:16px 0}.tenue{color:#4F5D62}'
    . 'button{background:#A4D233;color:#00596D;font-weight:700;font-size:16px;border:0;border-radius:24px;padding:12px 28px;cursor:pointer}</style></head>'
    . '<body><main><h1>' . h($titolo) . "</h1>$corpo</main></body></html>";
  exit;
}
