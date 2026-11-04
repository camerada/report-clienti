<?php
/**
 * Report mensile dei siti dei clienti: dati da Analytics e Search Console → testi di Claude → PDF → email.
 *
 *   php report.php --cliente=identia                 mese scorso, anteprima a CAME
 *   php report.php --cliente=identia --mese=2026-10
 *   php report.php --cliente=veleco --dal=2026-10-01 --al=2026-10-10   una parte del mese (confronto con gli stessi giorni del mese prima)
 *   php report.php --tutti                           tutti i clienti (questo lo lancia il cron)
 *   php report.php --cliente=identia --solo-pdf      crea il PDF in uscite/ senza mandare email
 *   php report.php --cliente=identia --solo-dati     salva solo i numeri in uscite/<cliente>/<periodo>-dati.json (niente Claude, niente email)
 *   php report.php --cliente=identia --prova         dati finti da prova/esempio.json (niente Google)
 *
 * Ogni cliente ha 'automatico' in clienti.php: false = il PDF arriva solo a CAME (da controllare e
 * inoltrare), true = va al cliente con CAME in copia. Un report già inviato non riparte due volte
 * (registro in uscite/registro.csv), a meno di --forza.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Europe/Rome');
require __DIR__ . '/config.php';
foreach (['google', 'dati', 'imbuto', 'testo', 'pdf', 'impagina', 'email'] as $f) require __DIR__ . "/lib/$f.php";

$opz = getopt('', ['cliente:', 'mese:', 'dal:', 'al:', 'tutti', 'solo-pdf', 'solo-dati', 'prova', 'forza']);
$clienti = require __DIR__ . '/clienti.php';
$mese = $opz['mese'] ?? (new DateTimeImmutable('first day of last month'))->format('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mese)) exit("Mese non valido: usa AAAA-MM\n");
$per = null;
if (isset($opz['dal']) || isset($opz['al'])) {
  $dal = $opz['dal'] ?? ''; $al = $opz['al'] ?? '';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dal) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $al) || $dal > $al || substr($dal, 0, 7) !== substr($al, 0, 7))
    exit("Periodo non valido: --dal=AAAA-MM-GG --al=AAAA-MM-GG, nello stesso mese\n");
  $mese = substr($al, 0, 7);
  $per = periodo($mese, $dal, $al);
}
$chiave = $per ? "{$per['dal']}_{$per['al']}" : $mese;   // nome dei file e riga del registro
$quali = isset($opz['tutti']) ? array_keys($clienti) : [($opz['cliente'] ?? '')];
if (!$quali[0] || !isset($clienti[$quali[0]])) exit("Indica --cliente=NOME (" . implode(', ', array_keys($clienti)) . ") oppure --tutti\n");

$registro = __DIR__ . '/uscite/registro.csv';
@mkdir(__DIR__ . '/uscite', 0750, true);
$errori = 0;
$falliti = [];

foreach ($quali as $id) {
  $c = $clienti[$id] + PREDEFINITI;
  if (empty($c['ga4']) && empty($c['db_config']) && !isset($opz['prova'])) { echo "$id: manca l'ID della proprietà Analytics in clienti.php, salto\n"; continue; }
  try {
    $giaInviato = is_file($registro) && preg_match('/^' . preg_quote("$id;$chiave;", '/') . '.*;inviato$/m', file_get_contents($registro));
    if ($giaInviato && !isset($opz['forza']) && !isset($opz['solo-pdf']) && !isset($opz['solo-dati']) && !isset($opz['prova'])) { echo "$id $chiave: già inviato, salto (--forza per rifarlo)\n"; continue; }

    $d = isset($opz['prova']) ? json_decode(file_get_contents(__DIR__ . (empty($c['db_config']) ? '/prova/esempio.json' : '/prova/esempio-imbuto.json')), true) + ['mese' => $mese]
      : (empty($c['db_config']) ? raccogli_dati($c, $mese, $per) : ($per ? throw new RuntimeException('--dal/--al non vale per il report del test') : raccogli_imbuto($c, $mese)));
    $d['periodo'] = ($per ?? []) + ($d['periodo'] ?? []) + periodo($mese);
    $d['lavori'] = isset($opz['prova']) ? leggi_lavori_file(__DIR__ . '/prova/lavori-esempio.md') : leggi_lavori($id, $mese);
    if (isset($opz['solo-dati'])) {
      @mkdir(__DIR__ . "/uscite/$id", 0750, true);
      file_put_contents(__DIR__ . "/uscite/$id/$chiave-dati.json", json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      echo "$id $chiave: numeri in uscite/$id/$chiave-dati.json\n";
      continue;
    }
    $t = scrivi_testi($c, $d);
    $pdf = crea_pdf($c, $d, $t, isset($opz['prova']));
    $nome = "report-{$c['sito']}-$chiave.pdf";
    @mkdir(__DIR__ . "/uscite/$id", 0750, true);
    file_put_contents(__DIR__ . "/uscite/$id/$nome", $pdf);
    file_put_contents(__DIR__ . "/uscite/$id/$chiave.json", json_encode(['dati' => $d, 'testi' => $t], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "$id $chiave: PDF in uscite/$id/$nome\n";
    if (isset($opz['solo-pdf'])) continue;

    $meseIt = $d['periodo']['etichetta'];
    $alCliente = !empty($c['automatico']) && !empty($c['email']) && !isset($opz['prova']);
    if ($alCliente) {
      invia_email($c['email'], "Il tuo sito, $meseIt: il report", email_cliente($c, $t, $meseIt), $pdf, $nome, [EMAIL_CAME]);
      $esito = 'inviato';
    } else {
      invia_email([EMAIL_CAME], "Da controllare: report di {$c['sito']} - $meseIt", email_anteprima($c, $t, $meseIt), $pdf, $nome);
      $esito = 'anteprima';
    }
    file_put_contents($registro, "$id;$chiave;" . date('c') . ";$esito\n", FILE_APPEND);
    echo "$id $chiave: email ($esito)\n";
  } catch (Throwable $e) {
    $errori++;
    echo "$id $chiave: ERRORE " . $e->getMessage() . "\n";
    file_put_contents($registro, "$id;$chiave;" . date('c') . ";errore: " . str_replace(["\n", ';'], ' ', $e->getMessage()) . "\n", FILE_APPEND);
    $falliti[] = ['sito' => $c['sito'], 'periodo' => $chiave, 'errore' => $e->getMessage()];
  }
}
// Un'unica email a CAME con tutti i report non riusciti (es. credito di Claude finito: un avviso, non uno per cliente)
if ($falliti && !isset($opz['prova'])) {
  $credito = (bool)array_filter($falliti, fn($f) => str_contains($f['errore'], 'credit balance'));
  try {
    invia_email([EMAIL_CAME], count($falliti) === 1 ? "Report di {$falliti[0]['sito']} non riuscito" : count($falliti) . ' report non riusciti',
      ($credito ? '<p><b>Credito di Claude finito:</b> ricaricalo su console.anthropic.com (Plans &amp; Billing), poi rilancia con <code>php report.php --tutti</code> (i report già inviati non ripartono).</p>' : '')
      . '<ul>' . implode('', array_map(fn($f) => '<li><b>' . htmlspecialchars($f['sito']) . '</b> (' . htmlspecialchars($f['periodo']) . '): ' . htmlspecialchars($f['errore']) . '</li>', $falliti)) . '</ul>',
      '', 'vuoto.txt');
  } catch (Throwable $e) { echo "Email di avviso non partita: " . $e->getMessage() . "\n"; }
}
exit($errori ? 1 : 0);

function email_cliente(array $c, array $t, string $mese): string {
  $wa = whatsapp_url($c['sito'], str_starts_with($mese, 'dal ') ? $mese : "di $mese");
  return '<p>Buongiorno,</p><p>in allegato il report del tuo sito <b>' . htmlspecialchars($c['sito']) . "</b> per $mese.</p>"
    . '<p>' . htmlspecialchars($t['in_breve']) . '</p>'
    . ($wa ? '<p>In fondo al report trovi qualche consiglio. Vuoi approfondirlo? <a href="' . htmlspecialchars($wa) . '">Scrivimi su WhatsApp</a>, il messaggio è già pronto.</p>' : '')
    . '<p>Per qualsiasi domanda rispondi pure a questa email.</p><p>Maurizio Camerada<br>CAME Comunicazione</p>';
}
function email_anteprima(array $c, array $t, string $mese): string {
  return '<p>Report di <b>' . htmlspecialchars($c['nome']) . "</b> per $mese, da controllare.</p>"
    . '<p>' . htmlspecialchars($t['in_breve']) . '</p>'
    . '<p>Se va bene, inoltralo al cliente. Quando i report sono sempre a posto, metti <code>automatico =&gt; true</code> e l\'email del cliente in clienti.php: dal mese dopo partono da soli.</p>';
}
