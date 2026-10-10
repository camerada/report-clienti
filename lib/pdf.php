<?php
// PDF del report, scritto a mano (niente librerie): A4, Helvetica, riquadri, barre e testo a capo.
// Coordinate dall'alto della pagina (y cresce verso il basso), convertite al momento di scrivere.

const LARGHEZZE_HELV = [
  'F1' => [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584,350,556,350,222,556,333,1000,556,556,333,1000,667,333,1000,350,611,350,350,222,222,333,333,350,556,1000,333,1000,500,333,944,350,500,667,278,333,556,556,556,556,260,556,333,737,370,556,584,333,737,333,400,584,333,333,333,556,537,278,333,333,365,556,834,834,834,611,667,667,667,667,667,667,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,500,556,556,556,556,278,278,278,278,556,556,556,556,556,556,556,584,611,556,556,556,556,500,556,500],
  'F2' => [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584,350,556,350,278,556,500,1000,556,556,333,1000,667,333,1000,350,611,350,350,278,278,500,500,350,556,1000,333,1000,556,333,944,350,500,667,278,333,556,556,556,556,280,556,333,737,370,556,584,333,737,333,400,584,333,333,333,611,556,278,333,333,365,556,834,834,834,611,722,722,722,722,722,722,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,556,556,556,556,556,278,278,278,278,611,611,611,611,611,611,611,584,611,611,611,611,611,556,611,556],
];

class Pdf {
  const L = 595.28, A = 841.89, M = 48;           // A4 in punti, margine
  const INCHIOSTRO = '#1F2328', SECONDARIO = '#5B6470', TENUE = '#8A929C', LINEA = '#E3E6EA', FONDO = '#F5F6F8';
  const SU = '#1E7B4E', GIU = '#B3402E';             // variazioni: sempre con il segno + / - scritto accanto

  private array $pagine = [];
  private string $flusso = '';
  public float $y = self::M;
  public string $colore;
  private string $piede = '';

  function __construct(string $colore, string $piede) { $this->colore = $colore; $this->piede = $piede; }

  static function cp(string $s): string {
    $s = str_replace(["\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}", "\u{2014}", "\u{2013}", "\u{00A0}", "\u{2192}", "\u{2026}"], ["'", "'", '"', '"', '-', '-', ' ', '->', '...'], $s);
    $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
    return $r === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $r;
  }
  static function largo(string $cp, string $f, float $corpo): float {
    $w = 0; $t = LARGHEZZE_HELV[$f];
    for ($i = 0, $n = strlen($cp); $i < $n; $i++) { $c = ord($cp[$i]); $w += $c >= 32 ? $t[$c - 32] : 0; }
    return $w * $corpo / 1000;
  }
  private static function rgb(string $hex): string {
    $h = ltrim($hex, '#');
    return sprintf('%.3F %.3F %.3F', hexdec(substr($h, 0, 2)) / 255, hexdec(substr($h, 2, 2)) / 255, hexdec(substr($h, 4, 2)) / 255);
  }
  // colore schiarito verso il bianco (0 = uguale, 1 = bianco)
  static function chiaro(string $hex, float $q): string {
    $h = ltrim($hex, '#'); $o = '#';
    for ($i = 0; $i < 6; $i += 2) { $v = hexdec(substr($h, $i, 2)); $o .= sprintf('%02X', round($v + (255 - $v) * $q)); }
    return $o;
  }

  function nuovaPagina(): void {
    if ($this->flusso !== '' || $this->pagine) $this->pagine[] = $this->flusso;
    $this->flusso = ''; $this->y = self::M;
  }
  // va a pagina nuova se non c'è spazio per $alto punti
  function spazio(float $alto): void { if ($this->y + $alto > self::A - self::M - 20) $this->nuovaPagina(); }

  function rett(float $x, float $y, float $w, float $h, string $colore, float $raggio = 0): void {
    $Y = self::A - $y - $h;
    $this->flusso .= self::rgb($colore) . " rg\n";
    if ($raggio <= 0) { $this->flusso .= sprintf("%.2F %.2F %.2F %.2F re f\n", $x, $Y, $w, $h); return; }
    $r = min($raggio, $w / 2, $h / 2); $k = 0.5523 * $r;
    $this->flusso .= sprintf("%.2F %.2F m %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c f\n",
      $x + $r, $Y, $x + $w - $r, $Y, $x + $w - $r + $k, $Y, $x + $w, $Y + $r - $k, $x + $w, $Y + $r,
      $x + $w, $Y + $h - $r, $x + $w, $Y + $h - $r + $k, $x + $w - $r + $k, $Y + $h, $x + $w - $r, $Y + $h,
      $x + $r, $Y + $h, $x + $r - $k, $Y + $h, $x, $Y + $h - $r + $k, $x, $Y + $h - $r,
      $x, $Y + $r, $x, $Y + $r - $k, $x + $r - $k, $Y, $x + $r, $Y);
  }
  function linea(float $x1, float $y1, float $x2, float $y2, string $colore = self::LINEA, float $spessore = 0.8): void {
    $this->flusso .= sprintf("%s RG %.2F w %.2F %.2F m %.2F %.2F l S\n", self::rgb($colore), $spessore, $x1, self::A - $y1, $x2, self::A - $y2);
  }
  // testo su una riga; $y = linea di base; allinea: s, d (destra), c
  function testo(float $x, float $y, string $s, float $corpo = 10, string $colore = self::INCHIOSTRO, bool $grassetto = false, string $allinea = 's'): float {
    $cp = self::cp($s); $f = $grassetto ? 'F2' : 'F1'; $w = self::largo($cp, $f, $corpo);
    if ($allinea === 'd') $x -= $w; elseif ($allinea === 'c') $x -= $w / 2;
    $this->flusso .= sprintf("BT %s rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", self::rgb($colore), $f, $corpo, $x, self::A - $y,
      strtr($cp, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']));
    return $w;
  }
  // spezza in righe larghe al massimo $w
  static function righe(string $s, float $w, float $corpo, bool $grassetto = false): array {
    $f = $grassetto ? 'F2' : 'F1'; $out = []; $riga = '';
    foreach (preg_split('/\s+/u', trim($s)) as $parola) {
      $prova = $riga === '' ? $parola : "$riga $parola";
      if ($riga !== '' && self::largo(self::cp($prova), $f, $corpo) > $w) { $out[] = $riga; $riga = $parola; }
      else $riga = $prova;
    }
    if ($riga !== '') $out[] = $riga;
    return $out;
  }
  // paragrafo a capo dalla posizione corrente; restituisce l'altezza usata
  function paragrafo(string $s, float $x = self::M, ?float $w = null, float $corpo = 10.5, string $colore = self::INCHIOSTRO, float $interlinea = 1.45, bool $grassetto = false): float {
    $w ??= self::L - 2 * self::M - ($x - self::M);
    $h0 = $this->y;
    foreach (self::righe($s, $w, $corpo, $grassetto) as $r) {
      $this->spazio($corpo * $interlinea);
      $this->y += $corpo * $interlinea;
      $this->testo($x, $this->y - $corpo * ($interlinea - 1) / 2 - 2, $r, $corpo, $colore, $grassetto);
    }
    return $this->y - $h0;
  }

  function titoloSezione(string $t, string $sottotitolo = ''): void {
    $this->spazio(80);
    $this->y += 26;
    $this->rett(self::M, $this->y - 11, 4, 14, $this->colore);
    $this->testo(self::M + 12, $this->y, $t, 14, self::INCHIOSTRO, true);
    $this->y += 8;
    if ($sottotitolo !== '') { $this->paragrafo($sottotitolo, self::M, null, 9.5, self::SECONDARIO, 1.4); }
    $this->y += 6;
  }

  function salva(): string {
    $this->pagine[] = $this->flusso; $this->flusso = '';
    $tot = count($this->pagine);
    foreach ($this->pagine as $i => &$p) {
      $piede = self::cp($this->piede);
      $num = self::cp('Pagina ' . ($i + 1) . ' di ' . $tot);
      $p .= sprintf("BT %s rg /F1 8 Tf %.2F 28 Td (%s) Tj ET\n", self::rgb(self::TENUE), self::M, strtr($piede, ['(' => '\\(', ')' => '\\)']));
      $p .= sprintf("BT %s rg /F1 8 Tf %.2F 28 Td (%s) Tj ET\n", self::rgb(self::TENUE), self::L - self::M - self::largo($num, 'F1', 8), $num);
    }
    unset($p);
    $ogg = [];
    $kids = [];
    for ($i = 0; $i < $tot; $i++) $kids[] = (6 + 2 * $i) . ' 0 R';
    $ogg[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $ogg[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . "] /Count $tot >>";
    $ogg[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $ogg[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    $ogg[5] = '<< /Title (' . strtr(self::cp($this->piede), ['(' => '\\(', ')' => '\\)']) . ') /Producer (CAME Comunicazione) /CreationDate (D:' . date('YmdHis') . ') >>';
    foreach ($this->pagine as $i => $c) {
      $n = 6 + 2 * $i;
      $ogg[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', self::L, self::A, $n + 1);
      $z = gzcompress($c);
      $ogg[$n + 1] = '<< /Length ' . strlen($z) . " /Filter /FlateDecode >>\nstream\n$z\nendstream";
    }
    $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $pos = [];
    foreach ($ogg as $n => $o) { $pos[$n] = strlen($out); $out .= "$n 0 obj\n$o\nendobj\n"; }
    $xref = strlen($out);
    $out .= "xref\n0 " . (count($ogg) + 1) . "\n0000000000 65535 f \n";
    for ($n = 1; $n <= count($ogg); $n++) $out .= sprintf("%010d 00000 n \n", $pos[$n]);
    return $out . "trailer\n<< /Size " . (count($ogg) + 1) . " /Root 1 0 R /Info 5 0 R >>\nstartxref\n$xref\n%%EOF\n";
  }
}

// ── Il report ─────────────────────────────────────────────────────────────

function numero(float $n): string { return number_format($n, 0, ',', '.'); }

function variazione(float $ora, float $prima): ?array {
  if ($prima <= 0) return null;
  $p = (int)round(($ora - $prima) / $prima * 100);
  return [$p === 0 ? '=' : ($p > 0 ? "+$p%" : "$p%"), $p > 0 ? Pdf::SU : ($p < 0 ? Pdf::GIU : Pdf::SECONDARIO)];
}

function crea_pdf(array $c, array $d, array $t, bool $esempio = false): string {
  $mese = mese_italiano($d['mese']);
  $pdf = new Pdf($c['colore'] ?? '#3B4A75', "Report di {$c['sito']} - $mese - CAME Comunicazione");
  $pdf->nuovaPagina();
  $L = Pdf::L; $M = Pdf::M; $W = $L - 2 * $M;

  // Testata colorata
  $pdf->rett(0, 0, $L, 150, $pdf->colore);
  $chiaro = Pdf::chiaro($pdf->colore, 0.72);
  $pdf->testo($M, 46, 'REPORT MENSILE DEL SITO' . ($esempio ? '  -  ESEMPIO CON DATI FINTI' : ''), 8.5, $chiaro, true);
  $pdf->testo($M, 78, $c['nome'], 20, '#FFFFFF', true);
  $pdf->testo($M, 100, ucfirst($mese) . '  -  ' . $c['sito'], 11.5, '#FFFFFF');
  $pdf->testo($M, 128, 'A cura di CAME Comunicazione', 9, $chiaro);
  $pdf->y = 178;

  // Titolo e sintesi
  $pdf->paragrafo($t['titolo'], $M, $W, 15, Pdf::INCHIOSTRO, 1.3, true);
  $pdf->y += 6;
  $pdf->paragrafo($t['sintesi'], $M, $W, 10.5, Pdf::INCHIOSTRO, 1.5);
  $pdf->y += 16;

  // Riquadri con i numeri principali
  $tt = $d['totali']; $tp = $d['totali_prima'];
  $contatti = array_sum(array_map(fn($k) => $d['eventi'][$k] ?? 0, array_keys($c['contatti'] ?? [])));
  $contattiPrima = array_sum(array_map(fn($k) => $d['eventi_prima'][$k] ?? 0, array_keys($c['contatti'] ?? [])));
  $riquadri = [
    ['Persone', $tt['activeUsers'] ?? 0, $tp['activeUsers'] ?? 0, 'hanno visitato il sito'],
    ['Visite', $tt['sessions'] ?? 0, $tp['sessions'] ?? 0, 'contando i ritorni'],
    ['Pagine lette', $tt['screenPageViews'] ?? 0, $tp['screenPageViews'] ?? 0, 'in totale'],
    ['Contatti', $contatti, $contattiPrima, 'chiamate e messaggi'],
  ];
  $gap = 10; $rw = ($W - 3 * $gap) / 4; $rh = 92; $y0 = $pdf->y;
  foreach ($riquadri as $i => [$nome, $ora, $prima, $nota]) {
    $x = $M + $i * ($rw + $gap);
    $pdf->rett($x, $y0, $rw, $rh, Pdf::FONDO, 6);
    $pdf->testo($x + 12, $y0 + 20, mb_strtoupper($nome), 7.5, Pdf::SECONDARIO, true);
    $pdf->testo($x + 12, $y0 + 50, numero($ora), 24, Pdf::INCHIOSTRO, true);
    $v = variazione($ora, $prima);
    $pdf->testo($x + 12, $y0 + 67, $v ? "{$v[0]} sul mese prima" : 'primo mese misurato', 8, $v ? $v[1] : Pdf::TENUE, (bool)$v);
    foreach (array_slice(Pdf::righe($nota, $rw - 24, 7.5), 0, 1) as $r) $pdf->testo($x + 12, $y0 + 81, $r, 7.5, Pdf::TENUE);
  }
  $pdf->y = $y0 + $rh + 10;
  $pdf->paragrafo($t['nota_visite'], $M, $W, 9.5, Pdf::SECONDARIO, 1.45);

  // Bene / da migliorare, su due colonne
  $pdf->y += 18;
  $colW = ($W - 16) / 2;
  $altezza = function (array $voci) use ($colW) { $h = 34; foreach ($voci as $v) $h += count(Pdf::righe($v, $colW - 40, 10)) * 14.5 + 8; return $h; };
  $h = max($altezza($t['bene']), $altezza($t['migliorare']));
  $pdf->spazio($h);
  $yb = $pdf->y;
  foreach ([[$t['bene'], 'Cosa è andato bene', '+', Pdf::SU], [$t['migliorare'], 'Cosa possiamo migliorare', '>', $pdf->colore]] as $k => [$voci, $tit, $segno, $col]) {
    $x = $M + $k * ($colW + 16);
    $pdf->rett($x, $yb, $colW, $h, Pdf::chiaro($col, 0.93), 6);
    $pdf->testo($x + 14, $yb + 22, $tit, 11, Pdf::INCHIOSTRO, true);
    $pdf->y = $yb + 32;
    foreach ($voci as $v) {
      $pdf->testo($x + 14, $pdf->y + 11.5, $segno, 10, $col, true);
      $pdf->paragrafo($v, $x + 28, $colW - 40, 10, Pdf::INCHIOSTRO, 1.45);
      $pdf->y += 8;
    }
  }
  $pdf->y = $yb + $h + 18;

  // Consiglio del mese
  $righe = Pdf::righe($t['consiglio'], $W - 40, 10.5);
  $hc = 52 + count($righe) * 15.2;
  $pdf->spazio($hc);
  $yc = $pdf->y;
  $pdf->rett($M, $yc, $W, $hc, Pdf::chiaro($pdf->colore, 0.88), 6);
  $pdf->rett($M, $yc, 5, $hc, $pdf->colore);
  $pdf->testo($M + 20, $yc + 24, 'Il consiglio per il mese prossimo', 11, Pdf::INCHIOSTRO, true);
  $pdf->y = $yc + 34;
  $pdf->paragrafo($t['consiglio'], $M + 20, $W - 40, 10.5, Pdf::INCHIOSTRO, 1.45);

  if ($d['dispositivi']) {
    $tot = array_sum(array_column($d['dispositivi'], 'visitatori')) ?: 1;
    $pdf->y = $yc + $hc + 22;
    $w = $pdf->testo($M, $pdf->y, 'Con cosa vi visitano:  ', 10, Pdf::INCHIOSTRO, true);
    $pdf->testo($M + $w, $pdf->y, implode('   -   ', array_map(fn($x) => $x['nome'] . ' ' . round($x['visitatori'] / $tot * 100) . '%', $d['dispositivi'])), 10, Pdf::SECONDARIO);
  }

  // Nota finale
  $pdf->y += 22;
  $pdf->linea($M, $pdf->y, $M + $W, $pdf->y);
  $pdf->y += 4;
  $pdf->paragrafo('Come leggere questi numeri: Google Analytics conta solo chi accetta i cookie statistici, quindi le persone reali sono di più. '
    . 'I dati delle ricerche vengono da Google Search Console. Per domande o per approfondire: CAME Comunicazione.', $M, $W, 8.5, Pdf::TENUE, 1.45);


  // ── Pagina 2: i dettagli
  $pdf->nuovaPagina();
  $barre = function (array $voci, string $etichetta, string $valore, string $unita) use ($pdf, $M, $W) {
    $max = max(1, ...array_map(fn($v) => $v[$valore], $voci ?: [[$valore => 1]]));
    $tot = array_sum(array_map(fn($v) => $v[$valore], $voci)) ?: 1;
    $wEt = 190; $wBar = $W - $wEt - 70;
    foreach ($voci as $v) {
      $pdf->spazio(22);
      $et = Pdf::righe($v[$etichetta], $wEt - 10, 9.5)[0] ?? '';
      if ($et !== $v[$etichetta]) $et = rtrim(mb_substr($et, 0, max(1, mb_strlen($et) - 1))) . '...';
      $pdf->testo($M, $pdf->y + 13, $et, 9.5, Pdf::INCHIOSTRO);
      $pdf->rett($M + $wEt, $pdf->y + 4, $wBar, 12, Pdf::FONDO, 3);
      $pdf->rett($M + $wEt, $pdf->y + 4, max(3, $wBar * $v[$valore] / $max), 12, $pdf->colore, 3);
      $pdf->testo($M + $W, $pdf->y + 13, numero($v[$valore]) . ($unita === '%' ? ' (' . round($v[$valore] / $tot * 100) . '%)' : ''), 9, Pdf::SECONDARIO, true, 'd');
      $pdf->y += 19;
    }
  };

  $pdf->y -= 10;
  $pdf->titoloSezione('Da dove arrivano le visite', $t['nota_provenienza']);
  $barre($d['canali'], 'nome', 'visite', '%');

  $pdf->titoloSezione('Le pagine più lette', $t['nota_pagine']);
  $barre(array_slice($d['pagine'], 0, 6), 'titolo', 'viste', '');

  // Contatti: tabella con mese e confronto
  $pdf->titoloSezione('Come vi hanno contattato dal sito', $t['nota_contatti']);
  $tab = function (array $righe) use ($pdf, $M, $W) {
    $pdf->testo($M + $W - 110, $pdf->y + 10, 'Questo mese', 8, Pdf::TENUE, true, 'd');
    $pdf->testo($M + $W, $pdf->y + 10, 'Mese prima', 8, Pdf::TENUE, true, 'd');
    $pdf->y += 16;
    foreach ($righe as [$nome, $ora, $prima]) {
      if ($nome === null) { $pdf->y += 8; $pdf->testo($M, $pdf->y + 4, 'Altre azioni sul sito', 8, Pdf::TENUE, true); $pdf->y += 8; continue; }
      $pdf->spazio(20);
      $pdf->linea($M, $pdf->y, $M + $W, $pdf->y);
      $pdf->testo($M, $pdf->y + 14, $nome, 10, Pdf::INCHIOSTRO);
      $pdf->testo($M + $W - 110, $pdf->y + 14, numero($ora), 10, Pdf::INCHIOSTRO, true, 'd');
      $pdf->testo($M + $W, $pdf->y + 14, numero($prima), 10, Pdf::SECONDARIO, false, 'd');
      $pdf->y += 18;
    }
    $pdf->linea($M, $pdf->y, $M + $W, $pdf->y);
  };
  $righe = [];
  foreach ($c['contatti'] ?? [] as $k => $nome) $righe[] = [$nome, $d['eventi'][$k] ?? 0, $d['eventi_prima'][$k] ?? 0];
  if (!empty($c['altri_eventi'])) $righe[] = [null, 0, 0];
  foreach ($c['altri_eventi'] ?? [] as $k => $nome) $righe[] = [$nome, $d['eventi'][$k] ?? 0, $d['eventi_prima'][$k] ?? 0];
  $tab($righe);

  // Ricerche su Google
  if (!empty($d['google']['ricerche'])) {
    $g = $d['google'];
    $pdf->titoloSezione('Cosa cercano su Google', $t['nota_google']);
    $pdf->testo($M + $W - 110, $pdf->y + 10, 'Volte che è comparso', 8, Pdf::TENUE, true, 'd');
    $pdf->testo($M + $W, $pdf->y + 10, 'Clic sul sito', 8, Pdf::TENUE, true, 'd');
    $pdf->y += 16;
    foreach (array_slice($g['ricerche'], 0, 6) as $r) {
      $pdf->spazio(20);
      $pdf->linea($M, $pdf->y, $M + $W, $pdf->y);
      $pdf->testo($M, $pdf->y + 14, '"' . $r['testo'] . '"', 10, Pdf::INCHIOSTRO);
      $pdf->testo($M + $W - 110, $pdf->y + 14, numero($r['comparse']), 10, Pdf::SECONDARIO, false, 'd');
      $pdf->testo($M + $W, $pdf->y + 14, numero($r['clic']), 10, Pdf::INCHIOSTRO, true, 'd');
      $pdf->y += 18;
    }
    $pdf->linea($M, $pdf->y, $M + $W, $pdf->y);
  }

  return $pdf->salva();
}
