<?php
/**
 * Esporta i dati grezzi di un cliente (Analytics + Search Console) in JSON, per un'analisi approfondita
 * con Claude Code (che da solo non raggiunge le API di Google). Niente Claude, niente email, niente PDF.
 *
 *   ssh identia "cd report-clienti && php esporta.php --cliente=veleco" > ~/Desktop/veleco-dati.json
 *   … --dal=2026-01-01      inizio dei dati di Analytics (predefinito: 12 mesi fa)
 *
 * Search Console: gli ultimi 16 mesi (tutto quello che Google conserva). Il JSON va su stdout,
 * i messaggi su stderr; una copia resta in uscite/<cliente>/analisi-<data>.json.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Europe/Rome');
require __DIR__ . '/config.php';
require __DIR__ . '/lib/google.php';

$opz = getopt('', ['cliente:', 'dal:']);
$clienti = require __DIR__ . '/clienti.php';
$id = $opz['cliente'] ?? '';
if (!isset($clienti[$id])) { fwrite(STDERR, "Indica --cliente=NOME (" . implode(', ', array_keys($clienti)) . ")\n"); exit(1); }
$c = $clienti[$id] + PREDEFINITI;
if (empty($c['ga4'])) { fwrite(STDERR, "$id: manca l'ID della proprietà Analytics in clienti.php\n"); exit(1); }

$ieri = date('Y-m-d', strtotime('-1 day'));
$dal = $opz['dal'] ?? date('Y-m-d', strtotime('-12 months'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dal)) { fwrite(STDERR, "--dal non valido: usa AAAA-MM-GG\n"); exit(1); }
$sc_dal = date('Y-m-d', strtotime('-16 months +3 days'));
$sc_al = date('Y-m-d', strtotime('-3 days'));   // gli ultimi giorni di Search Console non sono ancora completi

// Ogni blocco per conto suo: se uno non riesce (metrica non disponibile, permessi) il resto esce lo stesso
$prova = function (string $nome, callable $f) {
  fwrite(STDERR, "  {$nome}… ");
  try { $r = $f(); fwrite(STDERR, count($r) . " righe\n"); return $r; }
  catch (Throwable $e) { fwrite(STDERR, "ERRORE: {$e->getMessage()}\n"); return ['errore' => $e->getMessage()]; }
};
$ordina = fn(string $m) => ['orderBys' => [['metric' => ['metricName' => $m], 'desc' => true]]];
$soloContatti = ['dimensionFilter' => ['filter' => ['fieldName' => 'eventName',
  'inListFilter' => ['values' => array_keys(($c['contatti'] ?? []) + ($c['altri_eventi'] ?? []))]]]];
$p = $c['ga4'];

fwrite(STDERR, "$id — Analytics dal $dal al $ieri\n");
$out = [
  'cliente' => $id, 'nome' => $c['nome'], 'sito' => $c['sito'], 'creato' => date('c'),
  'eventi_contatto' => $c['contatti'] ?? [], 'altri_eventi' => $c['altri_eventi'] ?? [],
  'analytics' => ['dal' => $dal, 'al' => $ieri,
    'mesi_canali' => $prova('mesi per canale', fn() => ga4($p, $dal, $ieri,
      ['sessions', 'activeUsers', 'engagedSessions', 'keyEvents'], ['yearMonth', 'sessionDefaultChannelGroup'])),
    'fonti' => $prova('fonti', fn() => ga4($p, $dal, $ieri,
      ['sessions', 'engagedSessions', 'averageSessionDuration', 'keyEvents'], ['sessionSourceMedium'], $ordina('sessions') + ['limit' => 60])),
    'pagine_di_arrivo' => $prova('pagine di arrivo', fn() => ga4($p, $dal, $ieri,
      ['sessions', 'engagedSessions', 'averageSessionDuration', 'keyEvents'], ['landingPage'], $ordina('sessions') + ['limit' => 100])),
    'pagine_viste' => $prova('pagine viste', fn() => ga4($p, $dal, $ieri,
      ['screenPageViews', 'activeUsers', 'userEngagementDuration'], ['pagePath'], $ordina('screenPageViews') + ['limit' => 100])),
    'eventi' => $prova('eventi', fn() => ga4($p, $dal, $ieri, ['eventCount', 'totalUsers'], ['eventName'], $ordina('eventCount') + ['limit' => 100])),
    'contatti_per_giorno' => $prova('contatti per giorno', fn() => ga4($p, $dal, $ieri, ['eventCount'], ['date', 'eventName'], $soloContatti + ['limit' => 5000])),
    'contatti_per_pagina' => $prova('contatti per pagina', fn() => ga4($p, $dal, $ieri, ['eventCount'], ['eventName', 'pagePath'], $soloContatti + $ordina('eventCount') + ['limit' => 300])),
    'contatti_per_fonte' => $prova('contatti per fonte', fn() => ga4($p, $dal, $ieri, ['eventCount'], ['eventName', 'sessionSourceMedium'], $soloContatti + $ordina('eventCount') + ['limit' => 300])),
    'contatti_per_citta' => $prova('contatti per città', fn() => ga4($p, $dal, $ieri, ['eventCount'], ['eventName', 'city', 'region'], $soloContatti + $ordina('eventCount') + ['limit' => 300])),
    'citta' => $prova('città', fn() => ga4($p, $dal, $ieri, ['activeUsers', 'sessions', 'engagedSessions'], ['city', 'region'], $ordina('activeUsers') + ['limit' => 150])),
    'dispositivi' => $prova('dispositivi', fn() => ga4($p, $dal, $ieri, ['sessions', 'engagedSessions', 'keyEvents'], ['deviceCategory'])),
    'eta' => $prova('età', fn() => ga4($p, $dal, $ieri, ['activeUsers'], ['userAgeBracket'])),
  ],
  'search_console' => null,
];

// Search Console: la proprietà indicata, oppure i formati più comuni (come in lib/dati.php)
$candidati = $c['search_console'] !== '' ? [$c['search_console']]
  : ["sc-domain:{$c['sito']}", "https://www.{$c['sito']}/", "https://{$c['sito']}/"];
foreach ($candidati as $sc) {
  try { search_console($sc, $sc_al, $sc_al, [], 1); } catch (RuntimeException $e) { continue; }
  fwrite(STDERR, "$id — Search Console $sc dal $sc_dal al $sc_al\n");
  $q = fn(array $dim, int $n, ?string $da = null) => fn() => array_map(
    fn($r) => array_combine($dim, $r['keys']) + ['clic' => (int)$r['clicks'], 'comparse' => (int)$r['impressions'], 'posizione' => round($r['position'], 1)],
    search_console($sc, $da ?? $sc_dal, $sc_al, $dim, $n));
  $giorni = $prova('giorni', $q(['date'], 25000));
  $mesi = [];
  foreach (isset($giorni['errore']) ? [] : $giorni as $g) {
    $m = substr($g['date'], 0, 7);
    $mesi[$m] ??= ['mese' => $m, 'clic' => 0, 'comparse' => 0, 'pos_x_comparse' => 0];
    $mesi[$m]['clic'] += $g['clic']; $mesi[$m]['comparse'] += $g['comparse']; $mesi[$m]['pos_x_comparse'] += $g['posizione'] * $g['comparse'];
  }
  foreach ($mesi as &$m) { $m['posizione'] = $m['comparse'] ? round($m['pos_x_comparse'] / $m['comparse'], 1) : null; unset($m['pos_x_comparse']); }
  unset($m);
  $out['search_console'] = ['proprieta' => $sc, 'dal' => $sc_dal, 'al' => $sc_al,
    'mesi' => array_values($mesi),
    'ricerche_16_mesi' => $prova('ricerche 16 mesi', $q(['query'], 3000)),
    'ricerche_90_giorni' => $prova('ricerche 90 giorni', $q(['query'], 1000, date('Y-m-d', strtotime('-93 days')))),
    'pagine' => $prova('pagine', $q(['page'], 300)),
    'ricerca_e_pagina' => $prova('ricerca e pagina', $q(['query', 'page'], 3000)),
    'paesi' => $prova('paesi', $q(['country'], 30)),
    'dispositivi' => $prova('dispositivi', $q(['device'], 5)),
  ];
  break;
}
if ($out['search_console'] === null) fwrite(STDERR, "$id — Search Console non raggiungibile con: " . implode(', ', $candidati) . "\n");

$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@mkdir(__DIR__ . "/uscite/$id", 0750, true);
file_put_contents(__DIR__ . "/uscite/$id/analisi-" . date('Y-m-d') . '.json', $json);
echo $json, "\n";
fwrite(STDERR, "Fatto. Copia anche in uscite/$id/analisi-" . date('Y-m-d') . ".json\n");
