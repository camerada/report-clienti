<?php
// Claude legge i numeri del mese e scrive i testi del report, in parole semplici.
// Risposta in JSON con schema fisso (structured outputs): il PDF sa sempre dove mettere cosa.

const SCHEMA_TESTI = [
  'type' => 'object',
  'properties' => [
    'in_breve'        => ['type' => 'string', 'description' => '3-4 frasi: com\'è andato il mese e cosa conta, in parole semplici'],
    'kpi'             => ['type' => 'array', 'description' => 'Esattamente 3 numeri chiave del mese presi dai dati, i più significativi',
      'items' => ['type' => 'object', 'properties' => [
        'valore' => ['type' => 'string', 'description' => 'Il numero com\'è da mostrare, es. "412" oppure "15 + 13". Solo cifre, punti delle migliaia e +'],
        'spiegazione' => ['type' => 'string', 'description' => 'Cosa misura, max 12 parole, es. "persone che hanno visitato il sito"'],
      ], 'required' => ['valore', 'spiegazione'], 'additionalProperties' => false]],
    'nota_grafico'    => ['type' => 'string', 'description' => '1-2 frasi sotto il grafico dei 3 mesi: cosa si vede. Se un mese è parziale o senza dati, dillo'],
    'scoperte'        => ['type' => 'array', 'description' => 'Da 3 a 5 cose utili che dicono i dati, dalla più importante',
      'items' => ['type' => 'object', 'properties' => [
        'titolo' => ['type' => 'string', 'description' => 'Frase breve, max 8 parole'],
        'testo'  => ['type' => 'string', 'description' => '2-3 frasi con i numeri e cosa significano per l\'attività'],
      ], 'required' => ['titolo', 'testo'], 'additionalProperties' => false]],
    'prossimi_passi'  => ['type' => 'array', 'description' => 'Da 2 a 3 azioni concrete per il mese prossimo',
      'items' => ['type' => 'object', 'properties' => [
        'etichetta' => ['type' => 'string', 'description' => 'Max 3 parole, es. "Scheda Google"'],
        'testo'     => ['type' => 'string', 'description' => '1-2 frasi'],
      ], 'required' => ['etichetta', 'testo'], 'additionalProperties' => false]],
    'nota_provenienza'=> ['type' => 'string', 'description' => '1-2 frasi su da dove arrivano le visite'],
    'nota_pagine'     => ['type' => 'string', 'description' => '1-2 frasi sulle pagine più lette'],
    'nota_google'     => ['type' => 'string', 'description' => '1-2 frasi sulle ricerche su Google; stringa vuota se non ci sono dati'],
  ],
  'required' => ['in_breve', 'kpi', 'nota_grafico', 'scoperte', 'prossimi_passi', 'nota_provenienza', 'nota_pagine', 'nota_google'],
  'additionalProperties' => false,
];

function scrivi_testi(array $c, array $d): array {
  $per = $d['periodo'] ?? [];
  $mese = $per['etichetta'] ?? mese_italiano($d['mese']);
  $sistema = <<<TXT
Scrivi il report mensile del sito web di un'attività, per conto di CAME Comunicazione, l'agenzia che lo gestisce.
Lo legge il titolare: non è un esperto di internet. Scrivi in italiano semplice, frasi brevi, niente termini tecnici
(non "sessioni", "bounce", "engagement", "traffico organico": di' "visite", "persone", "ricerche su Google").
Dai del tu al titolare ("il tuo sito", "ti hanno chiamato", "ti trova"). Parla a nome dell'agenzia con il noi ("abbiamo fatto").

Regole:
- Usa solo i numeri che ti do. Non inventare dati, cause o confronti che non ci sono.
- Confronta con il mese prima solo quando il dato del mese prima c'è e non è zero.
- Sii onesto: se un numero è basso o in calo, dillo con calma e spiega cosa si può fare.
- Numeri piccoli (sotto 30) non sono una tendenza: non trarre conclusioni forti.
- Analytics conta solo i visitatori che accettano i cookie: i numeri veri sono più alti. Ricordalo al massimo una volta, se serve.
- Se ti do il lavoro fatto dall'agenzia nel mese, collegalo ai dati quando c'è un legame vero; non ripeterlo per intero.
- Se ti do i prossimi passi già decisi dall'agenzia, includili per primi nei prossimi_passi con parole tue, poi aggiungi i tuoi se c'è posto.
- I consigli devono essere cose concrete che l'attività o l'agenzia possono fare (es. un articolo su un tema cercato, aggiornare la scheda Google, chiedere recensioni).
- Niente emoji, niente punti esclamativi, niente toni da venditore.
TXT;
  $utente = "Attività: {$c['nome']} — sito {$c['sito']}\nSettore: {$c['settore']}\n"
    . (!empty($c['note']) ? "Note: {$c['note']}\n" : '')
    . (!empty($per['parziale'])
      ? "Periodo del report: $mese (solo questi giorni, non il mese intero). Nei numeri «questo_mese» è questo periodo e «mese_prima» sono "
        . "gli stessi giorni del mese prima ({$per['etichetta_prima']}); il grafico confronta gli stessi giorni negli ultimi 3 mesi. "
        . "Parla di «questi giorni» o «questo periodo», non di «questo mese»; i prossimi passi sono per le prossime settimane.\n"
      : "Mese del report: $mese\n")
    . (!empty($d['lavori']['fatto']) ? "\nLavoro fatto da CAME nel mese:\n" . implode("\n", array_map(fn($l) => "- {$l['etichetta']}: {$l['testo']}", $d['lavori']['fatto'])) . "\n" : '')
    . (!empty($d['lavori']['prossimi']) ? "\nProssimi passi già decisi:\n" . implode("\n", array_map(fn($l) => "- {$l['etichetta']}: {$l['testo']}", $d['lavori']['prossimi'])) . "\n" : '')
    . "\nNumeri (JSON):\n" . json_encode(($d['tipo'] ?? '') === 'imbuto' ? dati_imbuto_per_claude($d) : dati_per_claude($c, $d), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  $corpo = [
    'model' => MODELLO,
    'max_tokens' => 16000,
    'system' => $sistema,
    'messages' => [['role' => 'user', 'content' => $utente]],
    'output_config' => ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => SCHEMA_TESTI]],
    'fallbacks' => 'default',
  ];
  $url = defined('CLAUDE_API_URL') ? CLAUDE_API_URL : 'https://api.anthropic.com/v1/messages';
  $ultimo = null;
  for ($tentativo = 1; $tentativo <= 3; $tentativo++) {
    try {
      $r = http_json($url, $corpo, null, [
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
        'anthropic-beta: server-side-fallback-2026-07-01',
      ]);
      if (($r['stop_reason'] ?? '') === 'refusal') throw new RuntimeException('Claude ha rifiutato la richiesta (' . ($r['stop_details']['category'] ?? '?') . ')');
      if (($r['stop_reason'] ?? '') === 'max_tokens') throw new RuntimeException('Risposta di Claude troncata');
      foreach ($r['content'] ?? [] as $b) {
        if (($b['type'] ?? '') === 'text') {
          $t = json_decode($b['text'], true);
          if (is_array($t)) { $t['_uso'] = $r['usage'] ?? []; return $t; }
        }
      }
      throw new RuntimeException('Risposta di Claude senza testo JSON');
    } catch (RuntimeException $e) {
      $ultimo = $e;
      // riprova solo per sovraccarico e problemi di rete, non per errori nella richiesta
      if (!preg_match('/Errore (429|5\d\d)|non riuscita/', $e->getMessage())) break;
      sleep(10 * $tentativo);
    }
  }
  throw $ultimo;
}

// Dati con nomi chiari e variazioni già calcolate, così Claude non deve fare conti
function dati_per_claude(array $c, array $d): array {
  $t = $d['totali']; $tp = $d['totali_prima'];
  $x = [
    'visitatori' => voce($t['activeUsers'] ?? 0, $tp['activeUsers'] ?? 0),
    'visitatori_nuovi' => (int)($t['newUsers'] ?? 0),
    'visite' => voce($t['sessions'] ?? 0, $tp['sessions'] ?? 0),
    'pagine_viste' => voce($t['screenPageViews'] ?? 0, $tp['screenPageViews'] ?? 0),
    'visite_con_interesse_percento' => round(($t['engagementRate'] ?? 0) * 100),
    'durata_media_visita_secondi' => (int)($t['averageSessionDuration'] ?? 0),
    'contatti' => [],
    'altre_azioni' => [],
    'provenienza_visite' => $d['canali'],
    'pagine_piu_lette' => array_map(fn($p) => ['pagina' => $p['titolo'], 'indirizzo' => $p['percorso'], 'viste' => $p['viste']], $d['pagine']),
    'dispositivi' => $d['dispositivi'],
    'citta' => $d['citta'],
    'andamento_ultimi_3_mesi' => array_map(fn($a) => ['mese' => mese_italiano($a['mese']), 'persone' => $a['persone'], 'visite_da_google' => $a['da_google']], $d['andamento'] ?? []),
  ];
  foreach ($c['contatti'] ?? [] as $k => $nome) $x['contatti'][$nome] = voce($d['eventi'][$k] ?? 0, $d['eventi_prima'][$k] ?? 0);
  foreach ($c['altri_eventi'] ?? [] as $k => $nome) $x['altre_azioni'][$nome] = voce($d['eventi'][$k] ?? 0, $d['eventi_prima'][$k] ?? 0);
  if (!empty($d['google']['totali'])) {
    $g = $d['google'];
    $x['ricerche_google'] = [
      'clic_dai_risultati' => voce($g['totali']['clicks'] ?? 0, $g['totali_prima']['clicks'] ?? 0),
      'volte_che_il_sito_e_comparso' => voce($g['totali']['impressions'] ?? 0, $g['totali_prima']['impressions'] ?? 0),
      'posizione_media' => round($g['totali']['position'] ?? 0, 1),
      'parole_cercate' => $g['ricerche'],
    ];
  }
  return $x;
}

function voce(float $ora, float $prima): array {
  $v = ['questo_mese' => (int)round($ora), 'mese_prima' => (int)round($prima)];
  if ($prima > 0) $v['variazione_percento'] = (int)round(($ora - $prima) / $prima * 100);
  return $v;
}

function mese_italiano(string $mese): string {
  static $nomi = ['', 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
  [$a, $m] = explode('-', $mese);
  return $nomi[(int)$m] . ' ' . $a;
}
