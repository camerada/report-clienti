<?php
/**
 * Legge la configurazione pubblicata (versione «Live») di un contenitore Google Tag Manager con l'account di servizio,
 * per controllare da Claude Code tag, attivatori, variabili e consenso (es. «c'è il Pixel di Meta?»).
 * L'account di servizio va aggiunto in GTM (Amministrazione → Gestione utenti del contenitore, permesso Lettura)
 * e nel progetto Google Cloud va abilitata la Tag Manager API.
 *
 *   ssh identia "cd report-clienti && php gtm.php"                          elenca i contenitori visibili
 *   ssh identia "cd report-clienti && php gtm.php --contenitore=GTM-XXXX" > ~/Desktop/gtm-XXXX.json
 *
 * Il riepilogo leggibile va su stderr, il JSON completo della versione Live su stdout
 * (copia in uscite/gtm/<contenitore>-<data>.json). Solo lettura: non cambia niente in GTM.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Europe/Rome');
require __DIR__ . '/config.php';
require __DIR__ . '/lib/google.php';

defined('GTM_API') || define('GTM_API', 'https://tagmanager.googleapis.com/tagmanager/v2/');   // nelle prove: API finta da config.php

function gtm_get(string $percorso): array {
  $ch = curl_init(GTM_API . $percorso);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . google_token()]]);
  $risposta = curl_exec($ch);
  $stato = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $errore = curl_error($ch);
  curl_close($ch);
  if ($risposta === false) throw new RuntimeException("Tag Manager non raggiungibile: $errore");
  $j = json_decode($risposta, true);
  if ($stato < 200 || $stato >= 300) throw new RuntimeException("Errore $stato da Tag Manager: " . ($j['error']['message'] ?? substr($risposta, 0, 300)));
  return is_array($j) ? $j : [];
}

$opz = getopt('', ['contenitore:']);
$scelto = strtoupper($opz['contenitore'] ?? '');

try {
  $contenitori = [];
  foreach (gtm_get('accounts')['account'] ?? [] as $a)
    foreach (gtm_get($a['path'] . '/containers')['container'] ?? [] as $c)
      $contenitori[] = $c + ['nome_account' => $a['name']];
} catch (RuntimeException $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
  if (str_contains($e->getMessage(), '403')) fwrite(STDERR, "Controlla: Tag Manager API abilitata nel progetto Google Cloud e account di servizio aggiunto al contenitore in GTM.\n");
  exit(1);
}

if ($scelto === '') {
  if (!$contenitori) { fwrite(STDERR, "Nessun contenitore visibile: aggiungi l'account di servizio in GTM (Gestione utenti del contenitore, Lettura).\n"); exit(1); }
  foreach ($contenitori as $c) fwrite(STDERR, "{$c['publicId']}  {$c['name']}  (account: {$c['nome_account']})\n");
  exit(0);
}

$c = array_values(array_filter($contenitori, fn($x) => strtoupper($x['publicId']) === $scelto))[0] ?? null;
if (!$c) { fwrite(STDERR, "$scelto non è tra i contenitori visibili all'account di servizio. Visibili: " . (implode(', ', array_column($contenitori, 'publicId')) ?: 'nessuno') . "\n"); exit(1); }

try { $v = gtm_get($c['path'] . '/versions:live'); }
catch (RuntimeException $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }

// Riepilogo leggibile: tag con tipo, attivatori, stato in pausa e impostazioni di consenso
$attivatori = array_column($v['trigger'] ?? [], 'name', 'triggerId');
$attivatori += ['2147479553' => 'All Pages (predefinito)', '2147479572' => 'Initialization (predefinito)', '2147479573' => 'Consent Initialization (predefinito)'];
fwrite(STDERR, "{$c['publicId']} «{$c['name']}» — versione Live {$v['containerVersionId']}" . (isset($v['name']) ? " «{$v['name']}»" : '') . "\n\nTAG\n");
foreach ($v['tag'] ?? [] as $t) {
  $nomi = fn($ids) => implode(', ', array_map(fn($id) => $attivatori[$id] ?? $id, $ids ?? [])) ?: '—';
  $consenso = $t['consentSettings']['consentStatus'] ?? 'non impostato';
  $richiesto = array_column($t['consentSettings']['consentType']['list'] ?? [], 'value');
  fwrite(STDERR, sprintf("- %s [%s]%s\n    scatta: %s%s\n    consenso: %s%s\n", $t['name'], $t['type'], !empty($t['paused']) ? ' IN PAUSA' : '',
    $nomi($t['firingTriggerId'] ?? []), !empty($t['blockingTriggerId']) ? '  · bloccato da: ' . $nomi($t['blockingTriggerId']) : '',
    $consenso, $richiesto ? ' (' . implode(', ', $richiesto) . ')' : ''));
}
fwrite(STDERR, "\nATTIVATORI: " . (implode(', ', array_map(fn($t) => "{$t['name']} [{$t['type']}]", $v['trigger'] ?? [])) ?: '—') . "\n");
fwrite(STDERR, "VARIABILI: " . (implode(', ', array_map(fn($x) => "{$x['name']} [{$x['type']}]", $v['variable'] ?? [])) ?: '—') . "\n");

$json = json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@mkdir(__DIR__ . '/uscite/gtm', 0750, true);
file_put_contents(__DIR__ . "/uscite/gtm/{$c['publicId']}-" . date('Y-m-d') . '.json', $json);
echo $json, "\n";
