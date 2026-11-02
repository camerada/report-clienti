<?php
/**
 * Dati settimanali di Analytics (GA4) per il report social: sessioni, utenti e sessioni coinvolte per
 * sorgente / canale / campagna (gli UTM dei social) e gli eventi, per la settimana lunedì-domenica
 * appena chiusa e per quella prima (così il confronto lo trova già fatto).
 * Scrive un JSON in una cartella del sito chiusa al web (.htaccess); lo legge il «Report social settimanale»
 * (attività programmata di Claude) con lo strumento Hostinger, dal filesystem. Niente Claude, niente PDF.
 *
 *   php ga4-settimanale.php                           tuosito, settimana scorsa → cartella privata del sito
 *   php ga4-settimanale.php --settimana=2026-10-26    la settimana (lun-dom) che contiene quel giorno
 *   php ga4-settimanale.php --stampa                  JSON su stdout, non scrive niente (prova)
 *   php ga4-settimanale.php --diagnostica             cosa arriva davvero a GA4 negli ultimi 30 giorni; non scrive
 *   php ga4-settimanale.php --cliente=veleco          un altro cliente di clienti.php (la cartella è la sua)
 *
 * Cron (hPanel → Avanzate → Cron job), lunedì alle 7:30, prima del report social delle 7:45:
 *   30 7 * * 1 /usr/bin/php /home/<utente>/report-clienti/ga4-settimanale.php >/dev/null 2>&1
 *
 * File scritti in <sito>/public_html/_report-privato/ (si cambia con GA4_SETTIMANALE_CARTELLA in config.php):
 *   ga4-latest.json        l'ultima esecuzione (anche se è andata male: stato «errore» e il motivo)
 *   ga4-AAAA-Wnn.json      copia della settimana (numero ISO, lo stesso del titolo «Sett. NN» su Notion)
 *   .htaccess              «deny all»: il web non vede niente. Dopo ogni scrittura lo script controlla
 *                          da fuori che l'indirizzo risponda 403; se risponde 200 cancella i file ed esce con errore.
 * Il log delle esecuzioni è in uscite/ga4-settimanale.log. Se qualcosa va storto arriva un'email a EMAIL_CAME.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Europe/Rome');
require __DIR__ . '/config.php';
require __DIR__ . '/lib/google.php';
require __DIR__ . '/lib/email.php';

// utm_medium che contano come «social» (è lo stesso filtro del report: sessionMedium == social)
const MEDIUM_SOCIAL = ['social'];
// eventi di cui il JSON riporta il conteggio per sorgente/campagna, oltre a quelli di clienti.php
// (prova_gratuita: se un giorno si aggiunge in GTM, compare da solo)
const EVENTI_EXTRA = ['prova_gratuita'];
const NOME_CARTELLA = '_report-privato';

$opz = getopt('', ['cliente:', 'settimana:', 'stampa', 'diagnostica']);
$clienti = require __DIR__ . '/clienti.php';
$id = $opz['cliente'] ?? 'tuosito';
if (!isset($clienti[$id])) { fwrite(STDERR, "Cliente sconosciuto: $id (" . implode(', ', array_keys($clienti)) . ")\n"); exit(1); }
$c = $clienti[$id] + PREDEFINITI;
if (empty($c['ga4'])) { fwrite(STDERR, "$id: manca l'ID della proprietà Analytics in clienti.php\n"); exit(1); }
$prop = $c['ga4'];
$etichette = ($c['contatti'] ?? []) + ($c['altri_eventi'] ?? []);
$nomi = array_values(array_unique(array_merge(array_keys($etichette), EVENTI_EXTRA)));

// ---------- helper ----------
function intero(array $righe): array {   // le metriche di ga4() sono float: nel JSON servono numeri interi
  return array_map(function ($r) { foreach ($r as $k => $v) if (is_float($v)) $r[$k] = (int)round($v); return $r; }, $righe);
}
function filtro_social(): array { return ['filter' => ['fieldName' => 'sessionMedium', 'inListFilter' => ['values' => MEDIUM_SOCIAL]]]; }
function filtro_eventi(array $nomi): array { return ['filter' => ['fieldName' => 'eventName', 'inListFilter' => ['values' => $nomi]]]; }
function ordina_per(string $metrica, int $limite = 500): array {
  return ['orderBys' => [['metric' => ['metricName' => $metrica], 'desc' => true]], 'limit' => $limite];
}
const DIM_SORGENTE = ['sessionSource', 'sessionMedium', 'sessionCampaignName'];

// Tutti i numeri di una settimana. Un errore di Google risale: meglio nessun dato che dati a metà.
function settimana_ga4(string $prop, string $dal, string $al, array $nomi): array {
  $m3 = ['sessions', 'totalUsers', 'engagedSessions'];
  $totali = fn(array $extra = []) => intero(ga4($prop, $dal, $al, $m3, [], $extra))[0] ?? array_fill_keys($m3, 0);
  $eventiSocial = array_fill_keys($nomi, 0);   // 0 = nessun evento nella settimana (GA4 non restituisce righe a zero)
  foreach (ga4($prop, $dal, $al, ['eventCount'], ['eventName'],
    ['dimensionFilter' => ['andGroup' => ['expressions' => [filtro_social(), filtro_eventi($nomi)]]]]) as $r) $eventiSocial[$r['eventName']] = (int)$r['eventCount'];
  return [
    'totali'              => $totali(),                                   // tutto il sito
    'social'              => $totali(['dimensionFilter' => filtro_social()]),   // solo sessionMedium == social (utenti senza doppi conteggi)
    'sorgenti'            => intero(ga4($prop, $dal, $al, $m3, DIM_SORGENTE, ordina_per('sessions'))),
    'eventi'              => intero(ga4($prop, $dal, $al, ['eventCount'], ['eventName'], ordina_per('eventCount'))),
    'eventi_social'       => $eventiSocial,                               // evento => conteggio, solo sessioni social
    'eventi_per_campagna' => intero(ga4($prop, $dal, $al, ['eventCount'], array_merge(['eventName'], DIM_SORGENTE),
      ['dimensionFilter' => filtro_eventi($nomi)] + ordina_per('eventCount', 1000))),
  ];
}

function prepara_cartella(string $cartella): void {
  if (!is_dir($cartella) && !mkdir($cartella, 0755, true) && !is_dir($cartella)) throw new RuntimeException("Non riesco a creare la cartella $cartella");
  $voluto = "# Cartella privata del report: i file li legge solo il server, mai il web.\n"
    . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
    . "<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n";
  $ht = "$cartella/.htaccess";
  if (!is_file($ht) || file_get_contents($ht) !== $voluto) file_put_contents($ht, $voluto);
}

function scrivi_json(string $cartella, string $nome, array $dati): void {   // scrittura atomica: chi legge non vede mai un file a metà
  $json = json_encode($dati, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
  $tmp = "$cartella/.$nome.tmp";
  if (file_put_contents($tmp, $json . "\n") === false) throw new RuntimeException("Scrittura non riuscita: $tmp");
  chmod($tmp, 0644);
  if (!rename($tmp, "$cartella/$nome")) { @unlink($tmp); throw new RuntimeException("Non riesco a sostituire $cartella/$nome"); }
}

// true = il web vede il file (male), false = chiuso (403/401), null = non verificabile (404: forse la cartella non è sotto la document root)
function raggiungibile_dal_web(string $url): ?bool {
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => false]);
  $r = curl_exec($ch); $stato = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($r === false) return null;
  if ($stato >= 200 && $stato < 300) return true;
  return in_array($stato, [401, 403], true) ? false : null;
}

function registra(string $riga): void {
  $dir = __DIR__ . '/uscite';
  @mkdir($dir, 0755, true);
  @file_put_contents("$dir/ga4-settimanale.log", date('Y-m-d H:i:s') . " $riga\n", FILE_APPEND);
}

// ---------- diagnostica: cosa arriva davvero a GA4 ----------
function diagnostica(string $prop, array $nomi, array $etichette): void {
  $dal = date('Y-m-d', strtotime('-30 days')); $al = date('Y-m-d', strtotime('-1 day'));
  $b = ['periodo' => ['dal' => $dal, 'al' => $al]];
  $prova = function (string $nome, callable $f) use (&$b) {
    fwrite(STDERR, "  {$nome}… ");
    try { $b[$nome] = $f(); fwrite(STDERR, count($b[$nome]) . " righe\n"); }
    catch (Throwable $e) { $b[$nome] = ['errore' => $e->getMessage()]; fwrite(STDERR, "ERRORE: {$e->getMessage()}\n"); }
  };
  fwrite(STDERR, "Diagnostica GA4 $prop, dal $dal al $al\n");
  $prova('eventi', fn() => intero(ga4($prop, $dal, $al, ['eventCount', 'totalUsers'], ['eventName'], ordina_per('eventCount', 200))));
  $prova('eventi_per_dominio', fn() => intero(ga4($prop, $dal, $al, ['eventCount'], ['eventName', 'hostName'],
    ['dimensionFilter' => filtro_eventi($nomi)] + ordina_per('eventCount', 200))));
  $prova('sorgenti', fn() => intero(ga4($prop, $dal, $al, ['sessions', 'engagedSessions'], DIM_SORGENTE, ordina_per('sessions', 60))));
  $prova('auto_referral', fn() => intero(ga4($prop, $dal, $al, ['sessions'], ['sessionSource', 'sessionMedium'],
    ['dimensionFilter' => ['filter' => ['fieldName' => 'sessionSource', 'inListFilter' => ['values' => ['tuosito.si', 'www.tuosito.si', 'vetrina.si', 'www.vetrina.si']]]]])));

  fwrite(STDERR, "\nEventi attesi (ultimi 30 giorni):\n");
  $conteggi = [];
  foreach ($b['eventi'] ?? [] as $r) if (isset($r['eventName'])) $conteggi[$r['eventName']] = $r;
  foreach ($nomi as $n) {
    $r = $conteggi[$n] ?? null;
    fwrite(STDERR, sprintf("  %-22s %s\n", $n, $r ? "{$r['eventCount']} eventi, {$r['totalUsers']} utenti" : 'ASSENTE (mai ricevuto)'));
  }
  $sociali = 0; foreach ($b['sorgenti'] ?? [] as $r) if (isset($r['sessionMedium']) && in_array($r['sessionMedium'], MEDIUM_SOCIAL, true)) $sociali += $r['sessions'];
  fwrite(STDERR, "\nSessioni con medium " . implode('/', MEDIUM_SOCIAL) . ": $sociali\n");
  $auto = 0; foreach ($b['auto_referral'] ?? [] as $r) if (isset($r['sessions'])) $auto += $r['sessions'];
  fwrite(STDERR, "Sessioni con sorgente tuosito.si o vetrina.si (auto-referral): $auto"
    . ($auto > 0 ? " → il passaggio tra i due domini può spezzare la sessione e far perdere la sorgente social: in GA4 (Flussi di dati → Misurazione tra domini) controlla che compaiano entrambi\n" : " → nessun problema visibile\n"));
  $host = []; foreach ($b['eventi_per_dominio'] ?? [] as $r) if (isset($r['hostName'])) $host[$r['hostName']] = ($host[$r['hostName']] ?? 0) + $r['eventCount'];
  if ($host) { arsort($host); fwrite(STDERR, "Eventi attesi per dominio: " . json_encode($host, JSON_UNESCAPED_UNICODE) . "\n"); }
  echo json_encode($b, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
}

// ---------- periodo ----------
if (isset($opz['settimana']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$opz['settimana'])) { fwrite(STDERR, "--settimana non valida: usa AAAA-MM-GG\n"); exit(1); }
$rif = isset($opz['settimana']) ? new DateTimeImmutable($opz['settimana']) : new DateTimeImmutable('today -7 days');
$lun = $rif->modify('monday this week');            // senza --settimana: il lunedì della settimana scorsa
$dom = $lun->modify('+6 days');
$lunP = $lun->modify('-7 days'); $domP = $lun->modify('-1 day');
$etichetta = $lun->format('o-\WW');
$sito = $c['sito'];
$cartella = defined('GA4_SETTIMANALE_CARTELLA') ? GA4_SETTIMANALE_CARTELLA : dirname(__DIR__) . "/domains/$sito/public_html/" . NOME_CARTELLA;
$urlLatest = defined('GA4_SETTIMANALE_URL') ? GA4_SETTIMANALE_URL : "https://$sito/" . NOME_CARTELLA . '/ga4-latest.json';
$periodo = ['dal' => $lun->format('Y-m-d'), 'al' => $dom->format('Y-m-d')];

try {
  if (isset($opz['diagnostica'])) { diagnostica($prop, $nomi, $etichette); exit(0); }

  if ($dom >= new DateTimeImmutable('today')) fwrite(STDERR, "Attenzione: la settimana {$etichetta} non è ancora finita, i numeri sono parziali.\n");
  $out = [
    'stato'            => 'ok',
    'generato_il'      => date('c'),
    'cliente'          => $id,
    'sito'             => $sito,
    'proprieta'        => $prop,
    'settimana'        => $etichetta,
    'periodo'          => $periodo,
    'periodo_prima'    => ['dal' => $lunP->format('Y-m-d'), 'al' => $domP->format('Y-m-d')],
    'medium_social'    => MEDIUM_SOCIAL,
    'etichette_eventi' => $etichette,
    'note'             => [
      'Analytics conta solo chi accetta i cookie: i numeri sono più bassi dei clic reali visti da Metricool.',
      'Gli ultimi giorni di GA4 possono completarsi con 24-48 ore di ritardo.',
      'eventi_social: 0 = nessun evento nella settimana; se il nome manca in «eventi» l\'evento potrebbe non essere attivo.',
    ],
    'dati'             => settimana_ga4($prop, $periodo['dal'], $periodo['al'], $nomi),
    'dati_prima'       => settimana_ga4($prop, $lunP->format('Y-m-d'), $domP->format('Y-m-d'), $nomi),
  ];
  if (isset($opz['stampa'])) { echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n"; exit(0); }

  prepara_cartella($cartella);
  scrivi_json($cartella, "ga4-$etichetta.json", $out);
  scrivi_json($cartella, 'ga4-latest.json', $out);

  $aperta = raggiungibile_dal_web($urlLatest);
  if ($aperta === true) {
    foreach (["ga4-$etichetta.json", 'ga4-latest.json'] as $f) @unlink("$cartella/$f");
    throw new RuntimeException("La cartella è raggiungibile dal web ($urlLatest risponde 200): file cancellati. Controlla il .htaccess in $cartella.");
  }
  if ($aperta === null) fwrite(STDERR, "Attenzione: $urlLatest non risponde 403. Se dà 404 la cartella potrebbe non essere sotto la document root di $sito (e lo strumento Hostinger non la troverebbe).\n");
  $s = $out['dati']['social'];
  registra("ok $etichetta {$periodo['dal']}..{$periodo['al']} sessioni {$out['dati']['totali']['sessions']} social {$s['sessions']} → $cartella" . ($aperta === null ? ' (web: non verificato)' : ' (web: chiuso)'));
  fwrite(STDERR, "Scritto $cartella/ga4-$etichetta.json e ga4-latest.json (sessioni social: {$s['sessions']}).\n");
} catch (Throwable $e) {
  $msg = $e->getMessage();
  fwrite(STDERR, "ERRORE: $msg\n");
  registra("ERRORE $etichetta: $msg");
  if (!isset($opz['stampa']) && !isset($opz['diagnostica'])) {
    try {   // il report social legge ga4-latest.json: lo trova con lo stato «errore» e il motivo, invece di dati vecchi
      prepara_cartella($cartella);
      scrivi_json($cartella, 'ga4-latest.json', ['stato' => 'errore', 'generato_il' => date('c'), 'cliente' => $id, 'sito' => $sito,
        'settimana' => $etichetta, 'periodo' => $periodo, 'messaggio' => $msg]);
    } catch (Throwable $e2) { fwrite(STDERR, "Neanche il file di errore è stato scritto: {$e2->getMessage()}\n"); }
    try {
      invia_email([EMAIL_CAME], "Dati GA4 settimanali di $sito non riusciti",
        '<p>Il file settimanale di Analytics per il report social (settimana ' . htmlspecialchars($etichetta) . ') non è stato creato.</p><p>'
        . htmlspecialchars($msg) . '</p><p>Il report social di oggi scriverà «Dati sito non disponibili».</p>', '', '');
    } catch (Throwable $e3) { fwrite(STDERR, "Email di avviso non partita: {$e3->getMessage()}\n"); }
  }
  exit(1);
}
