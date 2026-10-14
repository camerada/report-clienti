<?php
// Impaginazione del report nello stile dei resoconti CAME (es. Veleco, ottobre 2026):
// copertina petrolio, «In breve» con 3 numeri e grafico dei 3 mesi, lavoro fatto,
// «Cosa ci dicono i dati» numerato, i numeri del mese, prossimi passi e firma.

const PETROLIO = '#00596D', CIANO = '#0092A9', LIME = '#A4D233';
const INCHIOSTRO = '#1E2A2E', SECONDARIO = '#4F5D62', TENUE = '#8A979B', FONDO = '#EEF1EF', FONDO2 = '#F7F9F7', LINEA = '#DCE2E0';
const M = 56;                              // margine
const W = Pdf::L - 2 * M;                  // larghezza utile

function numero(float $n): string { return number_format($n, 0, ',', '.'); }

function titolo_sezione(Pdf $p, string $t, string $sotto = ''): void {
  $p->spazio(90);
  $p->testo(M, $p->y + 22, $t, 'titolo', 22, PETROLIO);
  $p->y += 34;
  if ($sotto !== '') { $p->paragrafo($sotto, M, W - 60, 'testo', 10, SECONDARIO, 1.5); }
  $p->y += 10;
}

// righe "etichetta mono a sinistra + testo a destra", con linea sotto
function righe_etichettate(Pdf $p, array $voci): void {
  foreach ($voci as $v) {
    $h = max(Pdf::altezza($v['testo'], W - 120, 'testo', 10), Pdf::altezza($v['etichetta'], 100, 'mono', 8.5, 1.6)) + 18;
    $p->spazio($h);
    $y0 = $p->y + 6;
    $p->y = $y0; $p->paragrafo($v['etichetta'], M, 100, 'mono', 8.5, CIANO, 1.6);
    $p->y = $y0; $p->paragrafo($v['testo'], M + 120, W - 120, 'testo', 10, INCHIOSTRO, 1.5);
    $p->y = $y0 + $h - 6;
    $p->linea(M, $p->y, M + W, $p->y, LINEA);
  }
}

function crea_pdf(array $c, array $d, array $t, bool $esempio = false): string {
  $per = $d['periodo'] ?? [];
  $parziale = !empty($per['parziale']);
  $mese = $per['etichetta'] ?? mese_italiano($d['mese']);
  $quando = $parziale ? $mese : explode(' ', $mese)[0];                  // «dal 1 al 10 ottobre 2026» oppure «ottobre»
  $colonne = [$per['breve'] ?? 'questo mese', $per['breve_prima'] ?? 'mese prima'];
  $p = new Pdf();
  $piede = "CAME Comunicazione per {$c['nome']}";
  $p->alCambioPagina = function (Pdf $p, int $n) use ($piede) {
    if ($n === 1) return;
    $p->testo(M, Pdf::A - 34, $piede, 'testo', 7.5, TENUE);
    $p->testo(M + W, Pdf::A - 34, (string)$n, 'testo', 7.5, TENUE, 'd');
  };

  // ── Copertina
  $p->nuovaPagina();
  $p->rett(0, 0, Pdf::L, 670, PETROLIO);
  $p->rett(0, 670, Pdf::L, Pdf::A - 670, CIANO);
  $p->cerchio(M + 40, 96, 40, '#FFFFFF');
  $p->logo(M + 4, 60, 72);
  $p->testo(M + 96, 92, 'CAME Comunicazione', 'grassetto', 10.5, '#FFFFFF');
  $p->testo(M + 96, 107, 'camecomunicazione.it', 'testo', 8.5, '#FFFFFF');
  $p->testo(M, 328, 'Report del sito, ' . $mese . ($esempio ? ' - esempio con dati finti' : ''), 'mono', 9, LIME);
  $p->y = 346;
  foreach (['Il tuo sito,', 'cosa è successo', $parziale ? preg_replace('/ \d{4}$/', '', $mese) : 'a ' . $quando] as $r) { $p->testo(M, $p->y + 34, $r, 'titolo', 36, '#FFFFFF'); $p->y += 42; }
  $p->testo(M, $p->y + 34, $c['nome'], 'testo', 12.5, '#FFFFFF');
  $p->testo(M, $p->y + 52, $c['sito'], 'testo', 12.5, '#FFFFFF');
  $p->rett(M, 662, 130, 8, LIME);
  $yF = 742;
  if (!empty($c['referente'])) {
    $w = $p->testo(M, $yF, 'Preparato per ', 'testo', 9, '#FFFFFF');
    $p->testo(M + $w, $yF, $c['referente'], 'grassetto', 9, '#FFFFFF');
    $yF += 14;
    $p->testo(M, $yF, 'da Maurizio Camerada, CAME Comunicazione', 'testo', 9, '#FFFFFF');
  } else {
    $p->testo(M, $yF, 'Preparato da Maurizio Camerada, CAME Comunicazione', 'testo', 9, '#FFFFFF');
  }
  $p->testo(M, $yF + 14, 'Genova, ' . data_italiana(new DateTimeImmutable()), 'testo', 9, '#FFFFFF');

  // ── In breve
  $p->nuovaPagina();
  titolo_sezione($p, 'In breve');
  $p->y -= 10;
  $p->paragrafo($t['in_breve'], M, W - 40, 'testo', 11, INCHIOSTRO, 1.55);
  $p->y += 16;
  $kpi = array_slice($t['kpi'], 0, 3);
  $gap = 12; $kw = (W - 2 * $gap) / 3;
  $kh = 30 + 36 + max(array_map(fn($k) => Pdf::altezza($k['spiegazione'], $kw - 28, 'testo', 9, 1.45), $kpi ?: [['spiegazione' => '']])) + 12;
  foreach ($kpi as $i => $k) {
    $x = M + $i * ($kw + $gap);
    $p->rett($x, $p->y, $kw, $kh, FONDO, 6);
    $p->rett($x, $p->y, $kw, 4, $i === 2 ? LIME : CIANO);
    $p->testo($x + 14, $p->y + 38, $k['valore'], 'mono2', 22, PETROLIO);
    $y0 = $p->y; $p->y += 50;
    $p->paragrafo($k['spiegazione'], $x + 14, $kw - 28, 'testo', 9, SECONDARIO, 1.45);
    $p->y = $y0;
  }
  $p->y += $kh + 18;

  // Grafico dei 3 mesi
  $and = $d['andamento'] ?? [];
  if ($and) {
    $gh = 210 + Pdf::altezza($t['nota_grafico'], W - 40, 'testo', 8.5);
    $p->spazio($gh);
    $y0 = $p->y;
    $p->rett(M, $y0, W, $gh, FONDO2, 8);
    $p->bordo(M, $y0, W, $gh, LINEA, 8);
    $p->testo(M + 20, $y0 + 30, $d['grafico_titolo'] ?? ($parziale ? 'Persone sul sito, gli stessi giorni mese per mese' : 'Persone sul sito, mese per mese'), 'grassetto', 11.5, INCHIOSTRO);
    $max = max(1, ...array_column($and, 'persone'));
    $base = $y0 + 168; $hMax = 105; $bw = 110; $passo = (W - 40) / count($and);
    $p->linea(M + 20, $base, M + W - 20, $base, INCHIOSTRO, 0.8);
    foreach ($and as $i => $a) {
      $cx = M + 20 + $passo * ($i + 0.5);
      $h = max(2, $hMax * $a['persone'] / $max);
      $p->rett($cx - $bw / 2, $base - $h, $bw, $h, $i === count($and) - 1 ? LIME : CIANO, 3);
      $p->rett($cx - $bw / 2, $base - 3, $bw, 3, $i === count($and) - 1 ? LIME : CIANO);
      $p->testo($cx, $base - $h - 8, numero($a['persone']), 'mono2', 11, PETROLIO, 'c');
      $p->testo($cx, $base + 16, $parziale ? (int)substr($per['dal'], 8) . '-' . (int)substr($per['al'], 8) . ' ' . explode(' ', mese_italiano($a['mese']))[0] : ucfirst(explode(' ', mese_italiano($a['mese']))[0]), 'testo', 9, SECONDARIO, 'c');
    }
    $p->y = $base + 30;
    $p->paragrafo($t['nota_grafico'], M + 20, W - 40, 'testo', 8.5, SECONDARIO, 1.5);
    $p->y = $y0 + $gh + 22;
  }

  // Lavoro fatto nel mese (se c'è il file lavori/<cliente>/<mese>.md)
  if (!empty($d['lavori']['fatto'])) {
    $p->spazio(80);
    $p->testo(M, $p->y + 14, 'Cosa abbiamo fatto, in una riga per punto', 'titolo2', 13, INCHIOSTRO);
    $p->y += 26;
    righe_etichettate($p, $d['lavori']['fatto']);
  }

  // ── Cosa ci dicono i dati
  $p->nuovaPagina();
  titolo_sezione($p, 'Cosa ci dicono i dati', 'Abbiamo letto i dati ' . ($d['fonte_dati'] ?? 'di Google') . ($parziale ? ', ' . $quando : ' di ' . $quando) . '. Ecco le cose più utili per te.');
  foreach ($t['scoperte'] as $i => $s) {
    $h = 20 + Pdf::altezza($s['testo'], W - 36, 'testo', 10) + 22;
    $p->spazio($h);
    $p->cerchio(M + 9, $p->y + 9, 9, CIANO);
    $p->testo(M + 9, $p->y + 12.5, (string)($i + 1), 'grassetto', 9, '#FFFFFF', 'c');
    $p->testo(M + 36, $p->y + 12, $s['titolo'], 'grassetto', 11.5, INCHIOSTRO);
    $p->y += 20;
    $p->paragrafo($s['testo'], M + 36, W - 36, 'testo', 10, INCHIOSTRO, 1.5);
    $p->y += 22;
  }

  // ── I numeri del mese
  $p->nuovaPagina();
  titolo_sezione($p, $parziale ? 'I numeri del periodo' : 'I numeri del mese');
  $barre = function (string $tit, string $nota, array $voci, string $et, string $val, bool $percento, ?float $base = null) use ($p) {
    if (!$voci) return;
    $p->spazio(60);
    $p->testo(M, $p->y + 12, $tit, 'grassetto', 11.5, INCHIOSTRO);
    $p->y += 18;
    if ($nota !== '') $p->paragrafo($nota, M, W, 'testo', 9, SECONDARIO, 1.45);
    $p->y += 6;
    $max = max(1, ...array_column($voci, $val)); $tot = $base ?: (array_sum(array_column($voci, $val)) ?: 1);
    $wEt = 190; $wBar = W - $wEt - 80;
    foreach ($voci as $v) {
      $p->spazio(20);
      $testo = $v[$et];
      while (Pdf::largo($testo, 'testo', 9.5) > $wEt - 10 && mb_strlen($testo) > 4) $testo = mb_substr($testo, 0, -2) . '…';
      $p->testo(M, $p->y + 12, $testo, 'testo', 9.5, INCHIOSTRO);
      $p->rett(M + $wEt, $p->y + 3, $wBar, 11, FONDO, 3);
      $p->rett(M + $wEt, $p->y + 3, max(4, $wBar * $v[$val] / $max), 11, CIANO, 3);
      $p->testo(M + W, $p->y + 12, numero($v[$val]) . ($percento ? '  ' . round($v[$val] / $tot * 100) . '%' : ''), 'mono', 8.5, SECONDARIO, 'd');
      $p->y += 19;
    }
    $p->y += 14;
  };
  // tabella con N colonne di numeri a destra (la prima in evidenza)
  $tabella = function (string $tit, string $nota, array $intest, array $righe, int $evidenza = 0) use ($p) {
    if (!$righe) return;
    $p->spazio(70);
    $p->testo(M, $p->y + 12, $tit, 'grassetto', 11.5, INCHIOSTRO);
    $p->y += 18;
    if ($nota !== '') $p->paragrafo($nota, M, W, 'testo', 9, SECONDARIO, 1.45);
    $p->y += 4;
    $col = 90; $n = count($intest);
    foreach ($intest as $i => $t) $p->testo(M + W - ($n - 1 - $i) * $col, $p->y + 10, $t, 'mono', 7.5, TENUE, 'd');
    $p->y += 16;
    foreach ($righe as $r) {
      $p->spazio(19);
      $p->linea(M, $p->y, M + W, $p->y, LINEA);
      $p->testo(M, $p->y + 13, $r[0], 'testo', 9.5, INCHIOSTRO);
      for ($i = 0; $i < $n; $i++) {
        $v = $r[$i + 1] ?? '';
        $p->testo(M + W - ($n - 1 - $i) * $col, $p->y + 13, is_numeric($v) ? numero($v) : $v, $i === $evidenza ? 'mono2' : 'mono', 9, $i === $evidenza ? PETROLIO : SECONDARIO, 'd');
      }
      $p->y += 19;
    }
    $p->linea(M, $p->y, M + W, $p->y, LINEA);
    $p->y += 22;
  };

  if (($d['tipo'] ?? '') === 'imbuto') {
    // Percorso di selezione: landing → clic → test completati, per fonte
    $tabella('Il percorso, fonte per fonte', $t['nota_provenienza'], ['visite', 'clic sul test', 'test completati'],
      array_map(fn($f) => [$f['nome'], $f['visite'], $f['click'], $f['test']], $d['fonti']), 2);
    $e = $d['esiti'];
    $barre('Esito dei test', $t['nota_pagine'], array_values(array_filter([
      ['nome' => 'Agente diretto', 'n' => $e['agente']], ['nome' => 'Segnalatore', 'n' => $e['segnalatore']],
      ['nome' => 'Non idoneo', 'n' => $e['non_idoneo']], ['nome' => 'di cui senza Partita IVA', 'n' => $e['senza_piva']],
    ], fn($x) => $x['nome'] !== 'di cui senza Partita IVA' || $x['n'] > 0)), 'nome', 'n', true, max(1, $d['totali']['test']));
    $tabella('Confronto con il mese prima', '', $colonne, [
      ['Visite alla pagina', $d['totali']['visite'], $d['totali_prima']['visite']],
      ['Clic su «Dimostralo»', $d['totali']['click'], $d['totali_prima']['click']],
      ['Test completati', $d['totali']['test'], $d['totali_prima']['test']],
      ['Punteggio medio (su 100)', $d['totali']['punteggio'], $d['totali_prima']['punteggio']],
    ]);
  } else {
    $barre('Da dove arrivano le visite', $t['nota_provenienza'], $d['canali'], 'nome', 'visite', true);
    $barre('Le pagine più lette', $t['nota_pagine'], array_slice($d['pagine'], 0, 5), 'titolo', 'viste', false);
    $righe = [];
    foreach ($c['contatti'] ?? [] as $k => $n) $righe[] = [$n, $d['eventi'][$k] ?? 0, $d['eventi_prima'][$k] ?? 0];
    foreach ($c['altri_eventi'] ?? [] as $k => $n) $righe[] = [$n, $d['eventi'][$k] ?? 0, $d['eventi_prima'][$k] ?? 0];
    $tabella('Come ti hanno contattato dal sito', '', $colonne, $righe);
    if (!empty($d['google']['ricerche'])) {
      $tabella('Cosa cercano su Google per trovarti', $t['nota_google'], ['volte che compari', 'clic sul sito'],
        array_map(fn($r) => ['«' . $r['testo'] . '»', $r['comparse'], $r['clic']], array_slice($d['google']['ricerche'], 0, 6)));
    }
  }

  // ── Cosa ci serve da te / prossimi passi / firma
  $serve = $d['lavori']['ci_serve'] ?? [];
  $p->nuovaPagina();
  if ($serve) {
    $h = 46 + array_sum(array_map(fn($s) => Pdf::altezza($s, W - 64, 'testo', 10) + 8, $serve));
    titolo_sezione($p, 'Cosa ci serve da te', 'Bastano poche righe, anche su WhatsApp.');
    $y0 = $p->y;
    $p->rett(M, $y0, W, $h, PETROLIO, 8);
    $p->testo(M + 20, $y0 + 26, 'Per completare il lavoro', 'grassetto', 11.5, LIME);
    $p->y = $y0 + 38;
    foreach ($serve as $i => $s) {
      $p->testo(M + 20, $p->y + 11, ($i + 1) . '.', 'grassetto', 10, '#FFFFFF');
      $p->paragrafo($s, M + 40, W - 64, 'testo', 10, '#FFFFFF', 1.5);
      $p->y += 8;
    }
    $p->y = $y0 + $h + 30;
  }
  titolo_sezione($p, 'I prossimi passi');
  $p->y -= 10;
  righe_etichettate($p, $t['prossimi_passi']);

  $p->y += 26; $p->spazio(80);
  $p->cerchio(M + 30, $p->y + 30, 30, '#FFFFFF');
  $p->logo(M + 3, $p->y + 3, 54);
  $p->testo(M + 76, $p->y + 18, 'Maurizio Camerada', 'titolo2', 12, INCHIOSTRO);
  $p->testo(M + 76, $p->y + 34, 'CAME Comunicazione, Genova', 'testo', 9.5, SECONDARIO);
  $p->testo(M + 76, $p->y + 49, 'camecomunicazione.it', 'mono', 8.5, CIANO);
  $p->y += 80;
  $p->spazio(30);
  $p->paragrafo('Come leggere questi numeri: Google Analytics conta solo chi accetta i cookie statistici, quindi le persone reali sono di più. '
    . 'I dati delle ricerche vengono da Google Search Console.', M, W, 'testo', 7.5, TENUE, 1.45);

  return $p->salva("Report del sito {$c['sito']} - $mese");
}

function data_italiana(DateTimeImmutable $d): string {
  return $d->format('j') . ' ' . explode(' ', mese_italiano($d->format('Y-m')))[0] . ' ' . $d->format('Y');
}
