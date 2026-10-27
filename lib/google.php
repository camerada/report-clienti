<?php
// Accesso alle API di Google con un account di servizio (file JSON), senza librerie:
// JWT firmato con openssl → token → chiamate a Analytics Data API e Search Console API.

function google_token(): string {
  static $token = null, $scade = 0;
  if ($token && time() < $scade - 60) return $token;
  $chiave = json_decode((string)@file_get_contents(CHIAVE_GOOGLE), true);
  if (!$chiave || empty($chiave['private_key'])) throw new RuntimeException('Chiave Google mancante o non valida: ' . CHIAVE_GOOGLE);
  $b64 = fn($s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
  $ora = time();
  $testa = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
  $corpo = $b64(json_encode([
    'iss'   => $chiave['client_email'],
    'scope' => 'https://www.googleapis.com/auth/analytics.readonly https://www.googleapis.com/auth/webmasters.readonly https://www.googleapis.com/auth/tagmanager.readonly',
    'aud'   => $chiave['token_uri'] ?? 'https://oauth2.googleapis.com/token',
    'iat'   => $ora,
    'exp'   => $ora + 3600,
  ]));
  if (!openssl_sign("$testa.$corpo", $firma, $chiave['private_key'], OPENSSL_ALGO_SHA256)) throw new RuntimeException('Firma del token Google non riuscita');
  $r = http_json($chiave['token_uri'] ?? 'https://oauth2.googleapis.com/token', null,
    'grant_type=' . urlencode('urn:ietf:params:oauth:grant-type:jwt-bearer') . '&assertion=' . "$testa.$corpo.{$b64($firma)}",
    ['Content-Type: application/x-www-form-urlencoded']);
  $token = $r['access_token'] ?? throw new RuntimeException('Token Google non ricevuto');
  $scade = $ora + (int)($r['expires_in'] ?? 3600);
  return $token;
}

// POST JSON (o form) con risposta JSON; errore chiaro se lo stato non è 2xx
function http_json(string $url, ?array $json, ?string $grezzo = null, array $intestazioni = []): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $json !== null ? json_encode($json) : $grezzo,
    CURLOPT_HTTPHEADER => $json !== null ? array_merge(['Content-Type: application/json'], $intestazioni) : $intestazioni,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 120,
  ]);
  $risposta = curl_exec($ch);
  $stato = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $errore = curl_error($ch);
  curl_close($ch);
  if ($risposta === false) throw new RuntimeException("Chiamata a $url non riuscita: $errore");
  $j = json_decode($risposta, true);
  if ($stato < 200 || $stato >= 300) {
    $msg = $j['error']['message'] ?? (is_string($j['error'] ?? null) ? $j['error'] : substr($risposta, 0, 300));
    throw new RuntimeException("Errore $stato da " . parse_url($url, PHP_URL_HOST) . ": $msg");
  }
  return is_array($j) ? $j : [];
}

// Analytics Data API: righe come array associativi [dimensione => valore, metrica => numero]
function ga4(string $proprieta, string $dal, string $al, array $metriche, array $dimensioni = [], array $extra = []): array {
  $corpo = array_merge([
    'dateRanges' => [['startDate' => $dal, 'endDate' => $al]],
    'metrics'    => array_map(fn($m) => ['name' => $m], $metriche),
    'dimensions' => array_map(fn($d) => ['name' => $d], $dimensioni),
  ], $extra);
  $r = http_json("https://analyticsdata.googleapis.com/v1beta/properties/$proprieta:runReport", $corpo, null,
    ['Authorization: Bearer ' . google_token()]);
  $righe = [];
  foreach ($r['rows'] ?? [] as $riga) {
    $x = [];
    foreach ($dimensioni as $i => $d) $x[$d] = $riga['dimensionValues'][$i]['value'] ?? '';
    foreach ($metriche as $i => $m) $x[$m] = (float)($riga['metricValues'][$i]['value'] ?? 0);
    $righe[] = $x;
  }
  return $righe;
}

// Search Console: righe con keys, clicks, impressions, ctr, position
function search_console(string $sito, string $dal, string $al, array $dimensioni = [], int $quante = 10): array {
  $r = http_json('https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($sito) . '/searchAnalytics/query', [
    'startDate'  => $dal,
    'endDate'    => $al,
    'dimensions' => $dimensioni,
    'rowLimit'   => $quante,
  ], null, ['Authorization: Bearer ' . google_token()]);
  return $r['rows'] ?? [];
}
