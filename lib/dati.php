<?php
// Raccoglie i numeri del mese (e del mese prima, per il confronto) da Analytics e Search Console.
// Restituisce un array semplice, lo stesso che si usa nelle prove (prova/esempio.json).

const CANALI_IT = [
  'Organic Search' => 'Ricerca su Google', 'Direct' => 'Accesso diretto', 'Organic Social' => 'Social (Facebook, Instagram…)',
  'Referral' => 'Link da altri siti', 'Paid Search' => 'Annunci su Google', 'Paid Social' => 'Annunci sui social',
  'Email' => 'Email', 'Organic Maps' => 'Google Maps', 'Unassigned' => 'Non riconosciuto', 'Display' => 'Banner pubblicitari',
  'Organic Video' => 'Video (YouTube…)', 'Cross-network' => 'Campagne Google',
];
const DISPOSITIVI_IT = ['mobile' => 'Telefono', 'desktop' => 'Computer', 'tablet' => 'Tablet'];

// Mese intero, oppure una parte del mese (--dal/--al): allora il confronto è con gli stessi giorni del mese prima.
function periodo(string $mese, ?string $dal = null, ?string $al = null): array {
  if ($dal === null) {
    $inizio = new DateTimeImmutable("$mese-01");
    $prima = $inizio->modify('-1 month');
    return [
      'dal' => $inizio->format('Y-m-d'), 'al' => $inizio->format('Y-m-t'),
      'dal_prima' => $prima->format('Y-m-d'), 'al_prima' => $prima->format('Y-m-t'),
      'parziale' => false, 'etichetta' => mese_italiano($mese), 'breve' => 'questo mese', 'breve_prima' => 'mese prima',
    ];
  }
  $a = new DateTimeImmutable($dal); $b = new DateTimeImmutable($al);
  $ap = mesi_indietro($a, 1); $bp = mesi_indietro($b, 1);
  return [
    'dal' => $a->format('Y-m-d'), 'al' => $b->format('Y-m-d'),
    'dal_prima' => $ap->format('Y-m-d'), 'al_prima' => $bp->format('Y-m-d'),
    'parziale' => true, 'etichetta' => giorni_italiano($a, $b), 'etichetta_prima' => giorni_italiano($ap, $bp),
    'breve' => giorni_breve($a, $b), 'breve_prima' => giorni_breve($ap, $bp),
  ];
}
// stesso giorno n mesi prima (31 ottobre → 30 settembre)
function mesi_indietro(DateTimeImmutable $x, int $n): DateTimeImmutable {
  $primo = $x->modify('first day of this month')->modify("-$n months");
  return $primo->setDate((int)$primo->format('Y'), (int)$primo->format('m'), min((int)$x->format('j'), (int)$primo->format('t')));
}
function giorni_italiano(DateTimeImmutable $a, DateTimeImmutable $b): string {
  return 'dal ' . $a->format('j') . ' al ' . $b->format('j') . ' ' . mese_italiano($b->format('Y-m'));
}
function giorni_breve(DateTimeImmutable $a, DateTimeImmutable $b): string {
  return $a->format('j') . '-' . $b->format('j') . ' ' . mb_substr(mese_italiano($b->format('Y-m')), 0, 3);
}

function raccogli_dati(array $c, string $mese, ?array $p = null): array {
  $p ??= periodo($mese);
  $prop = $c['ga4'];
  $tot = fn($dal, $al) => ga4($prop, $dal, $al, ['activeUsers', 'newUsers', 'sessions', 'screenPageViews', 'engagementRate', 'averageSessionDuration'])[0] ?? [];
  $eventi = function ($dal, $al) use ($prop, $c) {
    $nomi = array_keys(($c['contatti'] ?? []) + ($c['altri_eventi'] ?? []));
    $righe = ga4($prop, $dal, $al, ['eventCount'], ['eventName'], ['dimensionFilter' => ['filter' => [
      'fieldName' => 'eventName', 'inListFilter' => ['values' => $nomi]]]]);
    $x = array_fill_keys($nomi, 0);
    foreach ($righe as $r) $x[$r['eventName']] = (int)$r['eventCount'];
    return $x;
  };

  $d = [
    'mese' => $mese,
    'periodo' => $p,
    'totali' => $tot($p['dal'], $p['al']),
    'totali_prima' => $tot($p['dal_prima'], $p['al_prima']),
    'eventi' => $eventi($p['dal'], $p['al']),
    'eventi_prima' => $eventi($p['dal_prima'], $p['al_prima']),
    'canali' => array_map(fn($r) => ['nome' => CANALI_IT[$r['sessionDefaultChannelGroup']] ?? $r['sessionDefaultChannelGroup'], 'visite' => (int)$r['sessions']],
      ga4($prop, $p['dal'], $p['al'], ['sessions'], ['sessionDefaultChannelGroup'], ['orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]], 'limit' => 6])),
    'pagine' => array_map(fn($r) => ['titolo' => pulisci_titolo($r['pageTitle'], $c), 'percorso' => $r['pagePath'], 'viste' => (int)$r['screenPageViews']],
      ga4($prop, $p['dal'], $p['al'], ['screenPageViews'], ['pagePath', 'pageTitle'], ['orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]], 'limit' => 8])),
    'dispositivi' => array_map(fn($r) => ['nome' => DISPOSITIVI_IT[$r['deviceCategory']] ?? $r['deviceCategory'], 'visitatori' => (int)$r['activeUsers']],
      ga4($prop, $p['dal'], $p['al'], ['activeUsers'], ['deviceCategory'], ['orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]]])),
    'citta' => array_map(fn($r) => ['nome' => $r['city'] === '(not set)' ? 'Non rilevata' : $r['city'], 'visitatori' => (int)$r['activeUsers']],
      ga4($prop, $p['dal'], $p['al'], ['activeUsers'], ['city'], ['orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]], 'limit' => 5])),
    'google' => null,
  ];

  // Andamento degli ultimi 3 mesi (per il grafico): persone e visite da Google, mese per mese.
  // Per una parte del mese: gli stessi giorni in ognuno dei 3 mesi, così le barre sono confrontabili.
  if ($p['parziale']) {
    $d['andamento'] = [];
    $organico = ['dimensionFilter' => ['filter' => ['fieldName' => 'sessionDefaultChannelGroup', 'stringFilter' => ['value' => 'Organic Search']]]];
    for ($i = 2; $i >= 0; $i--) {
      $dal = mesi_indietro(new DateTimeImmutable($p['dal']), $i)->format('Y-m-d');
      $al = mesi_indietro(new DateTimeImmutable($p['al']), $i)->format('Y-m-d');
      $d['andamento'][] = ['mese' => substr($al, 0, 7),
        'persone' => (int)(ga4($prop, $dal, $al, ['activeUsers'])[0]['activeUsers'] ?? 0),
        'da_google' => (int)(ga4($prop, $dal, $al, ['sessions'], [], $organico)[0]['sessions'] ?? 0)];
    }
  } else {
  $inizio3 = (new DateTimeImmutable("$mese-01"))->modify('-2 months')->format('Y-m-d');
  $mesi = [];
  for ($i = 2; $i >= 0; $i--) $mesi[(new DateTimeImmutable("$mese-01"))->modify("-$i months")->format('Ym')] = ['persone' => 0, 'da_google' => 0];
  foreach (ga4($prop, $inizio3, $p['al'], ['activeUsers'], ['yearMonth']) as $r) if (isset($mesi[$r['yearMonth']])) $mesi[$r['yearMonth']]['persone'] = (int)$r['activeUsers'];
  foreach (ga4($prop, $inizio3, $p['al'], ['sessions'], ['yearMonth'], ['dimensionFilter' => ['filter' => [
    'fieldName' => 'sessionDefaultChannelGroup', 'stringFilter' => ['value' => 'Organic Search']]]]) as $r)
    if (isset($mesi[$r['yearMonth']])) $mesi[$r['yearMonth']]['da_google'] = (int)$r['sessions'];
  $d['andamento'] = [];
  foreach ($mesi as $ym => $v) $d['andamento'][] = ['mese' => substr($ym, 0, 4) . '-' . substr($ym, 4)] + $v;
  }

  // Search Console: la proprietà indicata, oppure prova i formati più comuni
  $candidati = $c['search_console'] !== '' ? [$c['search_console']]
    : ["sc-domain:{$c['sito']}", "https://www.{$c['sito']}/", "https://{$c['sito']}/"];
  $ultimoErrore = '';
  foreach ($candidati as $sc) {
    try {
      $somma = fn($dal, $al) => search_console($sc, $dal, $al, [], 1)[0] ?? ['clicks' => 0, 'impressions' => 0, 'position' => 0];
      $d['google'] = [
        'proprieta' => $sc,
        'totali' => $somma($p['dal'], $p['al']),
        'totali_prima' => $somma($p['dal_prima'], $p['al_prima']),
        'ricerche' => array_map(fn($r) => ['testo' => $r['keys'][0], 'clic' => (int)$r['clicks'], 'comparse' => (int)$r['impressions'], 'posizione' => round($r['position'], 1)],
          search_console($sc, $p['dal'], $p['al'], ['query'], 10)),
      ];
      break;
    } catch (RuntimeException $e) {
      // proprietà inesistente o account di servizio non autorizzato: prova la prossima, il report esce comunque
      $ultimoErrore = $e->getMessage();
    }
  }
  if ($d['google'] === null) $d['google'] = ['errore' => $ultimoErrore];
  return $d;
}

// "Titolo pagina — Studio odontoiatrico…" → "Titolo pagina"
function pulisci_titolo(string $t, array $c): string {
  $t = preg_split('/\s+[—|–-]\s+/u', $t)[0] ?? $t;
  return $t === '' || $t === '(not set)' ? '(senza titolo)' : $t;
}

// Lavoro fatto dall'agenzia nel mese (facoltativo): lavori/<cliente>/<AAAA-MM>.md
//   ## Fatto            righe "Etichetta: testo"
//   ## Ci serve da te   righe "- domanda"
//   ## Prossimi passi   righe "Etichetta: testo"
function leggi_lavori(string $id, string $mese): array {
  return leggi_lavori_file(__DIR__ . "/../lavori/$id/$mese.md");
}
function leggi_lavori_file(string $f): array {
  $x = ['fatto' => [], 'ci_serve' => [], 'prossimi' => []];
  if (!is_file($f)) return $x;
  $sezione = null;
  foreach (file($f, FILE_IGNORE_NEW_LINES) as $riga) {
    $riga = trim($riga);
    if ($riga === '') continue;
    if (str_starts_with($riga, '#')) {
      $t = mb_strtolower($riga);
      $sezione = str_contains($t, 'serve') ? 'ci_serve' : (str_contains($t, 'prossim') ? 'prossimi' : (str_contains($t, 'fatto') ? 'fatto' : null));
      continue;
    }
    if (!$sezione) continue;
    if ($sezione === 'ci_serve') { $x['ci_serve'][] = ltrim($riga, '-* '); continue; }
    [$et, $testo] = str_contains($riga, ':') ? array_map('trim', explode(':', ltrim($riga, '-* '), 2)) : ['', ltrim($riga, '-* ')];
    $x[$sezione][] = ['etichetta' => $et, 'testo' => $testo];
  }
  return $x;
}
