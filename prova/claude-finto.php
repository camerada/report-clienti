<?php
// API finte per le prove in locale: Claude risponde con testi d'esempio, Brevo dice sempre ok.
// php -S 127.0.0.1:8770 prova/claude-finto.php
header('Content-Type: application/json');
if (str_contains($_SERVER['REQUEST_URI'], 'smtp')) {
  file_put_contents(sys_get_temp_dir() . '/email-finta.json', file_get_contents('php://input'));
  echo '{"messageId":"finto"}'; exit;
}
$testi = [
  'titolo' => 'Un buon mese: più persone e più telefonate',
  'sintesi' => 'A settembre 412 persone hanno visitato il sito, il 16% in più di agosto. La maggior parte vi ha trovato cercando su Google, soprattutto con il nome dello studio o con "dentista piazza Cavour". Dal sito sono arrivati 44 contatti: 23 telefonate, 14 messaggi WhatsApp, 5 richieste dal modulo e 2 email.',
  'bene' => ['Le telefonate dal sito sono salite da 19 a 23 e i messaggi WhatsApp da 9 a 14.', 'L\'articolo sulle gengive che sanguinano è già la quinta pagina più letta: chi cerca quel problema su Google trova voi.', 'Quasi 3 visitatori su 4 usano il telefono, e il sito su telefono funziona bene: le persone restano in media un minuto e mezzo.'],
  'migliorare' => ['Su "allineatori trasparenti genova" il sito compare 610 volte ma è in seconda pagina di Google: pochi clic.', 'Le richieste dal modulo sono poche (5): la maggior parte delle persone preferisce chiamare o scrivere su WhatsApp, ed è normale.'],
  'consiglio' => 'Vale la pena rafforzare la pagina degli allineatori trasparenti, ad esempio con un articolo che risponda alle domande più comuni (quanto durano, se si vedono, come si puliscono). È una ricerca frequente a Genova e il sito è già vicino alla prima pagina.',
  'nota_visite' => 'Le persone che arrivano per la prima volta sono la maggioranza (351 su 412): il sito sta portando volti nuovi.',
  'nota_provenienza' => 'Una visita su due arriva da Google. Le 41 visite da Google Maps sono persone che hanno trovato la scheda dello studio e poi aperto il sito.',
  'nota_pagine' => 'Dopo la pagina iniziale, la più letta è quella dei contatti: chi arriva sul sito cerca soprattutto come raggiungervi.',
  'nota_contatti' => 'In totale 44 contatti, 7 in più di agosto. Molti cliccano anche «Prenota la prima visita» (47 volte) e aprono la mappa (31).',
  'nota_google' => 'Su Google il sito compare sempre di più (6.120 volte, erano 4.870) e sale in classifica.',
];
echo json_encode(['type' => 'message', 'stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => json_encode($testi, JSON_UNESCAPED_UNICODE)]], 'usage' => ['input_tokens' => 2900, 'output_tokens' => 1400]]);
