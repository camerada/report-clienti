<?php
// Dati del percorso di selezione (recruitingagenti → dojotalentcheck) letti dal database MySQL del test.
// Solo totali: nessun nome, email o risposta esce da qui.

function db_imbuto(array $c): mysqli {
  $conf = @file_get_contents($c['db_config']);
  if ($conf === false) throw new RuntimeException("config.php del test non trovato: {$c['db_config']}");
  $v = [];
  foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $k) {
    if (!preg_match("/define\\(\\s*'$k'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'/", $conf, $m)) throw new RuntimeException("$k mancante nel config.php del test");
    $v[$k] = stripcslashes($m[1]);
  }
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $db = new mysqli($v['DB_HOST'], $v['DB_USER'], $v['DB_PASS'], $v['DB_NAME']);
  $db->set_charset('utf8mb4');
  return $db;
}

function righe_db(mysqli $db, string $sql, array $par): array {
  $st = $db->prepare($sql);
  $st->bind_param(str_repeat('s', count($par)), ...$par);
  $st->execute();
  return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function totali_imbuto(mysqli $db, string $dal, string $al): array {
  $v = righe_db($db, 'SELECT COALESCE(SUM(visite),0) v, COALESCE(SUM(click),0) c FROM visite WHERE giorno BETWEEN ? AND ?', [$dal, $al])[0];
  $t = righe_db($db, "SELECT COUNT(*) n, ROUND(AVG(score_totale)) p,
      SUM(livello_suggerito = 'Agente diretto') a, SUM(livello_suggerito = 'Segnalatore') s, SUM(livello_suggerito = 'Non idoneo') x
    FROM candidati WHERE creato_il BETWEEN ? AND ?", [$dal . ' 00:00:00', $al . ' 23:59:59'])[0];
  return ['visite' => (int)$v['v'], 'click' => (int)$v['c'], 'test' => (int)$t['n'], 'punteggio' => (int)$t['p'],
          'agente' => (int)$t['a'], 'segnalatore' => (int)$t['s'], 'non_idoneo' => (int)$t['x']];
}

function raccogli_imbuto(array $c, string $mese): array {
  $p = periodo($mese);
  $db = db_imbuto($c);
  $fonti = [];
  foreach (righe_db($db, 'SELECT fonte, SUM(visite) v, SUM(click) c FROM visite WHERE giorno BETWEEN ? AND ? GROUP BY fonte', [$p['dal'], $p['al']]) as $r)
    $fonti[$r['fonte']] = ['nome' => $r['fonte'], 'visite' => (int)$r['v'], 'click' => (int)$r['c'], 'test' => 0, 'idonei' => 0];
  foreach (righe_db($db, "SELECT LOWER(COALESCE(NULLIF(fonte, ''), '(diretto al test)')) f, COUNT(*) n,
        SUM(livello_suggerito IN ('Agente diretto', 'Segnalatore')) i
      FROM candidati WHERE creato_il BETWEEN ? AND ? GROUP BY f", [$p['dal'] . ' 00:00:00', $p['al'] . ' 23:59:59']) as $r) {
    $fonti[$r['f']] ??= ['nome' => $r['f'], 'visite' => 0, 'click' => 0, 'test' => 0, 'idonei' => 0];
    $fonti[$r['f']]['test'] = (int)$r['n']; $fonti[$r['f']]['idonei'] = (int)$r['i'];
  }
  uasort($fonti, fn($a, $b) => [$b['test'], $b['visite']] <=> [$a['test'], $a['visite']]);
  $tot = totali_imbuto($db, $p['dal'], $p['al']);
  // senza Partita IVA: profilo S2 = 3 (vedi includes/scoring.php del test)
  $senzaPiva = (int)(righe_db($db, "SELECT COUNT(*) n FROM candidati c JOIN risposte r ON r.candidato_id = c.id
      WHERE r.domanda_id = 'S2' AND r.valore = 3 AND c.creato_il BETWEEN ? AND ?", [$p['dal'] . ' 00:00:00', $p['al'] . ' 23:59:59'])[0]['n'] ?? 0);
  $andamento = [];
  for ($i = 2; $i >= 0; $i--) {
    $m = (new DateTimeImmutable("$mese-01"))->modify("-$i months");
    $t = totali_imbuto($db, $m->format('Y-m-01'), $m->format('Y-m-t'));
    $andamento[] = ['mese' => $m->format('Y-m'), 'persone' => $t['test'], 'visite' => $t['visite']];
  }
  return [
    'tipo' => 'imbuto', 'mese' => $mese, 'periodo' => $p,
    'grafico_titolo' => 'Test completati, mese per mese',
    'fonte_dati' => 'della pagina e del test',
    'totali' => $tot,
    'totali_prima' => totali_imbuto($db, $p['dal_prima'], $p['al_prima']),
    'fonti' => array_values($fonti),
    'esiti' => ['agente' => $tot['agente'], 'segnalatore' => $tot['segnalatore'], 'non_idoneo' => $tot['non_idoneo'], 'senza_piva' => $senzaPiva],
    'andamento' => $andamento,
  ];
}

function dati_imbuto_per_claude(array $d): array {
  return [
    'percorso' => 'pagina recruitingagenti (visite) → clic su «Dimostralo» → test completato su dojotalentcheck',
    'questo_mese' => $d['totali'], 'mese_prima' => $d['totali_prima'],
    'per_fonte' => $d['fonti'],
    'esiti_test' => $d['esiti'],
    'andamento_ultimi_3_mesi' => array_map(fn($a) => ['mese' => mese_italiano($a['mese']), 'test_completati' => $a['persone'], 'visite' => $a['visite']], $d['andamento']),
    'note' => 'visite e clic contati senza cookie, una volta per sessione; "(diretto al test)" = chi ha aperto il test senza passare dalla pagina; "landing" = fonte non indicata nel link',
  ];
}
