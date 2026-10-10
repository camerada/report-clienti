<?php
/**
 * Un cliente = una voce. Quello che manca prende i valori di PREDEFINITI (in fondo).
 *
 * ga4            ID numerico della proprietà Analytics (Amministrazione → Dettagli proprietà). Vuoto = saltato.
 * search_console vuoto = prova da solo 'sc-domain:sito', poi https://www.sito/ e https://sito/
 * email          a chi va il report quando 'automatico' è true
 * automatico     false = arriva solo a CAME (EMAIL_CAME), da controllare; true = al cliente con CAME in copia
 * colore         colore principale del PDF (#RRGGBB)
 * contatti       eventi di Analytics che contano come contatto => nome nel report
 * altri_eventi   altre azioni da mostrare
 * settore, note  aiutano Claude a dare consigli adatti (più sono precisi, meglio è)
 */
const PREDEFINITI = [
  'search_console' => '',
  'email'          => [],
  'automatico'     => false,
  'colore'         => '#2F3A56',
  'nota_contatti'  => 'moduli inviati',
  // Eventi che Google misura da solo (misurazione avanzata), senza codice sul sito
  'contatti'       => ['form_submit' => 'Moduli inviati'],
  'altri_eventi'   => ['click' => 'Clic verso altri siti (WhatsApp, social…)', 'file_download' => 'File scaricati'],
  'settore'        => '',
  'note'           => '',
];

return [
  'identia' => [
    'nome' => 'Identia — Studio Odontoiatrico D\'Urso', 'sito' => 'identia.it', 'ga4' => '549671189',
    'colore' => '#3B4A75',
    'settore' => 'studio dentistico a Genova (Piazza Cavour), un solo professionista',
    'nota_contatti' => 'chiamate e messaggi',
    'contatti' => ['clic_telefono' => 'Telefonate dal sito', 'clic_whatsapp' => 'Messaggi WhatsApp', 'generate_lead' => 'Richieste dal modulo', 'clic_email' => 'Email'],
    'altri_eventi' => ['clic_mappa' => 'Aperture della mappa', 'clic_prenota' => 'Clic su «Prenota la prima visita»', 'video_start' => 'Video avviati'],
    'note' => 'Analytics attivo dal 10/10/2026 e conta solo chi accetta i cookie. Blog con articoli programmati ogni settimana. '
            . 'Comunicazione sanitaria: niente promesse di risultati, niente prezzi.',
  ],
  'be-first'          => ['nome' => 'BE-FIRST', 'sito' => 'be-first.it', 'ga4' => '552898861'],
  'came'              => ['nome' => 'CAME Comunicazione', 'sito' => 'camecomunicazione.it', 'ga4' => '520232629',
                          'settore' => 'agenzia di comunicazione e siti web a Genova (sito proprio di CAME)'],
  'mauriziocamerada'  => ['nome' => 'Maurizio Camerada', 'sito' => 'mauriziocamerada.it', 'ga4' => '332423611', 'settore' => 'sito personale'],
  'mvrantincendio'    => ['nome' => 'MVR Antincendio', 'sito' => 'mvrantincendio.it', 'ga4' => '377207095',
                          'settore' => 'antincendio e corsi sulla sicurezza',
                          'note' => 'In Analytics la proprietà si chiama "https://mvrantincendio.com/ - GA4": verificare che sia quella giusta.'],
  'edoardocamerada'   => ['nome' => 'Edoardo Camerada', 'sito' => 'edoardocamerada.it', 'ga4' => '480692589', 'settore' => 'sito personale'],
  'motoracer'         => ['nome' => 'Moto Racer Genova', 'sito' => 'motoracergenova.it', 'ga4' => '364318665', 'settore' => 'moto a Genova'],
  'ferramentagenova'  => ['nome' => 'Paradiso della Brugola', 'sito' => 'ferramentagenova.it', 'ga4' => '461834714', 'settore' => 'ferramenta a Genova'],
  'marcofirinu'       => ['nome' => 'Marco Firinu', 'sito' => 'marcofirinu.it', 'ga4' => '480296162'],
  'veleco'            => ['nome' => 'Concessionario Veleco Italia', 'sito' => 'concessionariovelecoitalia.it', 'ga4' => '546703442', 'settore' => 'concessionario di veicoli elettrici Veleco'],
  'tuosito'           => ['nome' => 'TuoSito.si', 'sito' => 'tuosito.si', 'ga4' => '', // G-3SSSGG4FXT: manca l'ID numerico della proprietà
                          'settore' => 'servizio online di CAME per creare siti per piccole attività (landing tuosito.si + motore vetrina.si)',
                          'note' => 'tuosito.si e vetrina.si hanno la stessa proprietà Analytics.',
                          'contatti' => ['anteprima_creata' => 'Anteprime create', 'pagamento_completato' => 'Pagamenti'],
                          'altri_eventi' => ['clic_crea_sito' => 'Clic su «Crea il tuo sito»', 'form_iniziato' => 'Moduli iniziati', 'codice_chiesto' => 'Codici email chiesti', 'attivazione_aperta' => 'Pagine di attivazione aperte'],
                          'nota_contatti' => 'anteprime create'],
  'supzena'           => ['nome' => 'SUP Zena', 'sito' => 'supzena.it', 'ga4' => '343925367', 'settore' => 'corsi di stand up paddle (SUP) a Genova'],
  'recruitingagenti'  => ['nome' => 'Recruiting agenti', 'sito' => 'recruitingagenti.camecomunicazione.it', 'ga4' => ''], // proprietà da trovare
  'dojotalentcheck'   => ['nome' => 'Dojo Talent Check', 'sito' => 'dojotalentcheck.camecomunicazione.it', 'ga4' => ''], // proprietà da trovare
];
