<?php
// Motore PDF scritto a mano, senza librerie: A4, caratteri TrueType incorporati (risorse/*.ttf,
// codifica WinAnsi), rettangoli, linee, cerchi e il logo CAME con trasparenza.
// Coordinate dall'alto della pagina (y cresce verso il basso), convertite quando si scrive.

class Pdf {
  const L = 595.28, A = 841.89;
  const FONT = [
    'titolo'  => 'Bricolage-ExtraBold',
    'titolo2' => 'Bricolage-Bold',
    'testo'   => 'InterTight-Regular',
    'grassetto' => 'InterTight-Bold',
    'mono'    => 'SpaceMono-Regular',
    'mono2'   => 'SpaceMono-Bold',
  ];
  private static array $metriche = [];
  private array $pagine = [];
  private string $flusso = '';
  private array $usati = [];
  public float $y = 0;
  public float $margineSotto = 70;
  public $alCambioPagina = null;   // funzione chiamata a ogni pagina nuova (piè di pagina)

  static function metriche(string $f): array {
    return self::$metriche[$f] ??= json_decode(file_get_contents(__DIR__ . '/../risorse/' . self::FONT[$f] . '.json'), true);
  }
  static function cp(string $s): string {
    $s = str_replace(["\u{00A0}", "\u{2192}", "\u{2212}"], [' ', '->', '-'], $s);
    $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
    return $r === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $r;
  }
  static function largo(string $s, string $f, float $corpo, float $spaziatura = 0): float {
    $cp = self::cp($s); $w = 0; $t = self::metriche($f)['larghezze'];
    for ($i = 0, $n = strlen($cp); $i < $n; $i++) { $c = ord($cp[$i]); $w += $c >= 32 ? $t[$c - 32] : 0; }
    return $w * $corpo / 1000 + $spaziatura * max(0, strlen($cp) - 1);
  }
  static function rgb(string $hex): string {
    $h = ltrim($hex, '#');
    return sprintf('%.3F %.3F %.3F', hexdec(substr($h, 0, 2)) / 255, hexdec(substr($h, 2, 2)) / 255, hexdec(substr($h, 4, 2)) / 255);
  }

  function nuovaPagina(): void {
    if ($this->flusso !== '' || $this->pagine) $this->pagine[] = $this->flusso;
    $this->flusso = ''; $this->y = 56;
    if ($this->alCambioPagina) ($this->alCambioPagina)($this, count($this->pagine) + 1);
  }
  function spazio(float $alto): void { if ($this->y + $alto > self::A - $this->margineSotto) $this->nuovaPagina(); }

  // percorso di un rettangolo (con angoli arrotondati se $r > 0), senza riempire né tracciare
  private function percorso(float $x, float $y, float $w, float $h, float $r): string {
    $Y = self::A - $y - $h;
    if ($r <= 0) return sprintf("%.2F %.2F %.2F %.2F re", $x, $Y, $w, $h);
    $r = min($r, $w / 2, $h / 2); $k = 0.5523 * $r;
    return sprintf("%.2F %.2F m %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c h",
      $x + $r, $Y, $x + $w - $r, $Y, $x + $w - $r + $k, $Y, $x + $w, $Y + $r - $k, $x + $w, $Y + $r,
      $x + $w, $Y + $h - $r, $x + $w, $Y + $h - $r + $k, $x + $w - $r + $k, $Y + $h, $x + $w - $r, $Y + $h,
      $x + $r, $Y + $h, $x + $r - $k, $Y + $h, $x, $Y + $h - $r + $k, $x, $Y + $h - $r,
      $x, $Y + $r, $x, $Y + $r - $k, $x + $r - $k, $Y, $x + $r, $Y);
  }
  function rett(float $x, float $y, float $w, float $h, string $colore, float $r = 0): void {
    $this->flusso .= self::rgb($colore) . " rg " . $this->percorso($x, $y, $w, $h, $r) . " f\n";
  }
  function bordo(float $x, float $y, float $w, float $h, string $colore, float $r = 0, float $sp = 0.8): void {
    $this->flusso .= sprintf("%s RG %.2F w ", self::rgb($colore), $sp) . $this->percorso($x, $y, $w, $h, $r) . " S\n";
  }
  function cerchio(float $cx, float $cy, float $r, string $colore): void {
    $Y = self::A - $cy; $k = 0.5523 * $r;
    $this->flusso .= self::rgb($colore) . sprintf(" rg %.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c f\n",
      $cx + $r, $Y, $cx + $r, $Y + $k, $cx + $k, $Y + $r, $cx, $Y + $r, $cx - $k, $Y + $r, $cx - $r, $Y + $k, $cx - $r, $Y,
      $cx - $r, $Y - $k, $cx - $k, $Y - $r, $cx, $Y - $r, $cx + $k, $Y - $r, $cx + $r, $Y - $k, $cx + $r, $Y);
  }
  function linea(float $x1, float $y1, float $x2, float $y2, string $colore, float $sp = 0.8): void {
    $this->flusso .= sprintf("%s RG %.2F w %.2F %.2F m %.2F %.2F l S\n", self::rgb($colore), $sp, $x1, self::A - $y1, $x2, self::A - $y2);
  }
  function logo(float $x, float $y, float $lato): void {
    $this->usati['logo'] = true;
    $this->flusso .= sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /Logo Do Q\n", $lato, $lato, $x, self::A - $y - $lato);
  }
  // testo su una riga; $y = linea di base; allinea s / d / c
  function testo(float $x, float $y, string $s, string $f, float $corpo, string $colore, string $allinea = 's', float $spaziatura = 0): float {
    $this->usati[$f] = true;
    $w = self::largo($s, $f, $corpo, $spaziatura);
    if ($allinea === 'd') $x -= $w; elseif ($allinea === 'c') $x -= $w / 2;
    $this->flusso .= sprintf("BT %s rg /%s %.2F Tf %s%.2F %.2F Td (%s) Tj ET\n", self::rgb($colore), $f, $corpo,
      $spaziatura ? sprintf('%.2F Tc ', $spaziatura) : '', $x, self::A - $y, strtr(self::cp($s), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']));
    return $w;
  }
  static function righe(string $s, float $w, string $f, float $corpo): array {
    $out = [];
    foreach (preg_split('/\n/', trim($s)) as $par) {
      $riga = '';
      foreach (preg_split('/\s+/u', trim($par)) as $p) {
        $prova = $riga === '' ? $p : "$riga $p";
        if ($riga !== '' && self::largo($prova, $f, $corpo) > $w) { $out[] = $riga; $riga = $p; } else $riga = $prova;
      }
      $out[] = $riga;
    }
    return $out;
  }
  // paragrafo dalla posizione corrente (y = cima del blocco); restituisce l'altezza
  function paragrafo(string $s, float $x, float $w, string $f, float $corpo, string $colore, float $interlinea = 1.5): float {
    $h0 = $this->y; $passo = $corpo * $interlinea;
    foreach (self::righe($s, $w, $f, $corpo) as $r) {
      $this->spazio($passo);
      $this->testo($x, $this->y + $corpo * 0.95 + ($passo - $corpo) / 2, $r, $f, $corpo, $colore);
      $this->y += $passo;
    }
    return $this->y - $h0;
  }
  static function altezza(string $s, float $w, string $f, float $corpo, float $interlinea = 1.5): float {
    return count(self::righe($s, $w, $f, $corpo)) * $corpo * $interlinea;
  }

  function salva(string $titolo): string {
    $this->pagine[] = $this->flusso; $this->flusso = '';
    $ogg = []; $n = 3;
    $risorseFont = [];
    foreach (array_keys(self::FONT) as $f) {
      if (empty($this->usati[$f])) continue;
      $m = self::metriche($f); $ttf = file_get_contents(__DIR__ . '/../risorse/' . self::FONT[$f] . '.ttf');
      $z = gzcompress($ttf);
      $ogg[$n] = "<< /Length " . strlen($z) . " /Length1 " . strlen($ttf) . " /Filter /FlateDecode >>\nstream\n$z\nendstream";
      $ogg[$n + 1] = sprintf('<< /Type /FontDescriptor /FontName /%s /Flags 32 /FontBBox [%s] /ItalicAngle 0 /Ascent %d /Descent %d /CapHeight %d /StemV 80 /FontFile2 %d 0 R >>',
        self::FONT[$f], implode(' ', $m['bbox']), $m['ascent'], $m['descent'], $m['capHeight'], $n);
      $ogg[$n + 2] = sprintf('<< /Type /Font /Subtype /TrueType /BaseFont /%s /FirstChar 32 /LastChar 255 /Widths [%s] /Encoding /WinAnsiEncoding /FontDescriptor %d 0 R >>',
        self::FONT[$f], implode(' ', $m['larghezze']), $n + 1);
      $risorseFont[] = "/$f " . ($n + 2) . ' 0 R';
      $n += 3;
    }
    $xobj = '';
    if (!empty($this->usati['logo'])) {
      $alfa = file_get_contents(__DIR__ . '/../risorse/logo-came.alfa.z');
      $rgb = file_get_contents(__DIR__ . '/../risorse/logo-came.rgb.z');
      $ogg[$n] = "<< /Type /XObject /Subtype /Image /Width 256 /Height 256 /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($alfa) . " >>\nstream\n$alfa\nendstream";
      $ogg[$n + 1] = "<< /Type /XObject /Subtype /Image /Width 256 /Height 256 /ColorSpace /DeviceRGB /BitsPerComponent 8 /SMask $n 0 R /Filter /FlateDecode /Length " . strlen($rgb) . " >>\nstream\n$rgb\nendstream";
      $xobj = ' /XObject << /Logo ' . ($n + 1) . ' 0 R >>';
      $n += 2;
    }
    $info = $n++;
    $ogg[$info] = '<< /Title (' . strtr(self::cp($titolo), ['(' => '\\(', ')' => '\\)', '\\' => '\\\\']) . ') /Producer (CAME Comunicazione) /CreationDate (D:' . date('YmdHis') . ') >>';
    $kids = [];
    foreach ($this->pagine as $c) {
      $z = gzcompress($c);
      $ogg[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << %s >>%s >> /Contents %d 0 R >>', self::L, self::A, implode(' ', $risorseFont), $xobj, $n + 1);
      $ogg[$n + 1] = '<< /Length ' . strlen($z) . " /Filter /FlateDecode >>\nstream\n$z\nendstream";
      $kids[] = "$n 0 R"; $n += 2;
    }
    $ogg[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $ogg[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
    ksort($ogg);
    $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $pos = [];
    foreach ($ogg as $i => $o) { $pos[$i] = strlen($out); $out .= "$i 0 obj\n$o\nendobj\n"; }
    $xref = strlen($out); $tot = max(array_keys($ogg));
    $out .= "xref\n0 " . ($tot + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= $tot; $i++) $out .= sprintf("%010d 00000 n \n", $pos[$i]);
    return $out . "trailer\n<< /Size " . ($tot + 1) . " /Root 1 0 R /Info $info 0 R >>\nstartxref\n$xref\n%%EOF\n";
  }
}
