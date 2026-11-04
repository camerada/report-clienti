<?php
// API finte per le prove in locale: Claude risponde con testi d'esempio, Brevo dice sempre ok.
// php -S 127.0.0.1:8770 prova/claude-finto.php
header('Content-Type: application/json');
if (str_contains($_SERVER['REQUEST_URI'], 'smtp')) {
  file_put_contents(sys_get_temp_dir() . '/email-finta.json', file_get_contents('php://input'));
  echo '{"messageId":"finto"}'; exit;
}
$testi = [
  'in_breve' => 'Il sito cresce ogni mese. A settembre 412 persone lo hanno visitato, il 16% in più di agosto, e una visita su due arriva da Google. Chi ti contatta lo fa quasi sempre al telefono o su WhatsApp: 37 persone in un mese.',
  'kpi' => [['valore' => '412', 'spiegazione' => 'persone che hanno visitato il sito a settembre'], ['valore' => '6.120', 'spiegazione' => 'volte in cui Google ha mostrato il tuo sito'], ['valore' => '23 + 14', 'spiegazione' => 'persone che hanno toccato il pulsante per chiamarti (23) o per scriverti su WhatsApp (14)']],
  'nota_grafico' => 'Le persone sul sito crescono da tre mesi di fila: da 298 a luglio a 412 a settembre.',
  'scoperte' => [
    ['titolo' => 'Chi ti trova, ti cerca già per nome', 'testo' => 'Le ricerche che portano più clic contengono il nome dello studio: "identia genova" e "d\'urso dentista genova". Chi cerca solo "dentista genova centro" ti vede ancora poco: il sito compare 1.150 volte ma in seconda pagina.'],
    ['titolo' => 'Ti contattano al telefono e su WhatsApp', 'testo' => 'In un mese 23 persone hanno toccato il numero e 14 il pulsante WhatsApp. Il modulo è stato usato 5 volte. Per una visita dal dentista, parlare con una persona conta.'],
    ['titolo' => 'Il blog comincia a portare persone', 'testo' => 'L\'articolo sulle gengive che sanguinano è la quinta pagina più letta e compare 880 volte su Google. Gli articoli intercettano chi ha un problema e non conosce ancora lo studio.'],
    ['titolo' => 'Sette persone su dieci usano il telefono', 'testo' => 'La maggior parte delle visite arriva da smartphone. Il numero e il pulsante WhatsApp sono già in alto su ogni pagina: è la scelta giusta.'],
  ],
  'consigli' => [['etichetta' => 'Allineatori', 'testo' => 'Un articolo con le domande più comuni sugli allineatori trasparenti: il sito è già vicino alla prima pagina per questa ricerca.'], ['etichetta' => 'Scheda Google', 'testo' => 'Chiedere una recensione ai pazienti soddisfatti, poche alla volta.']],
  'nota_provenienza' => 'Una visita su due arriva da Google. Le 41 da Google Maps sono persone che hanno trovato la scheda dello studio e poi aperto il sito.',
  'nota_pagine' => 'Dopo la pagina iniziale, la più letta è quella dei contatti: chi arriva cerca soprattutto come raggiungerti.',
  'nota_google' => 'Su Google il sito compare sempre di più: 6.120 volte, erano 4.870.',
];
echo json_encode(['type' => 'message', 'stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => json_encode($testi, JSON_UNESCAPED_UNICODE)]], 'usage' => ['input_tokens' => 2900, 'output_tokens' => 1400]]);
