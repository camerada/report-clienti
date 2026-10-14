<?php
/**
 * Un cliente = una voce. Quello che manca prende i valori di PREDEFINITI (in fondo).
 *
 * ga4            ID numerico della proprietà Analytics (Amministrazione → Dettagli proprietà). Vuoto = saltato.
 * search_console vuoto = prova da solo 'sc-domain:sito', poi https://www.sito/ e https://sito/
 * email          a chi va il report quando 'automatico' è true
 * automatico     false = arriva solo a CAME (EMAIL_CAME), da controllare; true = al cliente con CAME in copia
 * referente      nome sulla copertina («Preparato per …»), facoltativo
 * contatti       eventi di Analytics che contano come contatto => nome nel report
 * altri_eventi   altre azioni da mostrare
 * settore, note  aiutano Claude a dare consigli adatti (più sono precisi, meglio è)
 */
const PREDEFINITI = [
  'search_console' => '',
  'email'          => [],
  'automatico'     => false,
  'referente'      => '',
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
    'referente' => 'Dott. Maurizio D\'Urso',
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
  'mvrantincendio'    => ['nome' => 'MVR Antincendio', 'sito' => 'mvrantincendio.it', 'ga4' => '344604720',
                          'settore' => 'antincendio e corsi sulla sicurezza'],
  'edoardocamerada'   => ['nome' => 'Edoardo Camerada', 'sito' => 'edoardocamerada.it', 'ga4' => '480692589', 'settore' => 'sito personale'],
  'motoracer'         => ['nome' => 'Moto Racer Genova', 'sito' => 'motoracergenova.it', 'ga4' => '364318665', 'settore' => 'moto a Genova'],
  'ferramentagenova'  => ['nome' => 'Paradiso della Brugola', 'sito' => 'ferramentagenova.it', 'ga4' => '461834714', 'settore' => 'ferramenta a Genova'],
  'marcofirinu'       => ['nome' => 'Marco Firinu', 'sito' => 'marcofirinu.it', 'ga4' => '480296162'],
  'veleco'            => ['nome' => 'Concessionario Veleco Italia', 'referente' => 'Valentina Colomba', 'sito' => 'concessionariovelecoitalia.it', 'ga4' => '546703442', 'settore' => 'concessionario di veicoli elettrici Veleco',
                          'nota_contatti' => 'chiamate e messaggi',
                          'contatti' => ['click_call' => 'Telefonate dal sito', 'click_whatsapp' => 'Messaggi WhatsApp', 'click_email' => 'Email', 'form_start' => 'Moduli iniziati'],
                          'altri_eventi' => ['click_outbound' => 'Clic verso altri siti', 'play_video' => 'Video guardati']],
  'tuosito'           => ['nome' => 'TuoSito.si', 'sito' => 'tuosito.si', 'ga4' => '556336111', // stream G-3SSSGG4FXT
                          'settore' => 'servizio online di CAME per creare siti per piccole attività (landing tuosito.si + motore vetrina.si)',
                          'note' => 'tuosito.si e vetrina.si hanno la stessa proprietà Analytics.',
                          'contatti' => ['anteprima_creata' => 'Anteprime create', 'pagamento_completato' => 'Pagamenti'],
                          'altri_eventi' => ['clic_crea_sito' => 'Clic su «Crea il tuo sito»', 'form_iniziato' => 'Moduli iniziati', 'codice_chiesto' => 'Codici email chiesti', 'attivazione_aperta' => 'Pagine di attivazione aperte'],
                          'nota_contatti' => 'anteprime create'],
  'postavecchia'      => ['nome' => 'Posta Vecchia Cocktail Bar & Tequileria', 'sito' => 'cocktailbarpostavecchia.it', 'ga4' => '491108573', // stream G-RLLZ2T2340
                          'settore' => 'cocktail bar e tequileria nel centro storico di Genova (Piazza della Posta Vecchia): cocktail special, tequila e mezcal, '
                                     . 'Mexican aperitivo, cucina messicana, DJ set e Lotteria della Tequila il sabato; aperto la sera, chiuso il martedì',
                          'nota_contatti' => 'messaggi e chiamate',
                          'contatti' => ['prenotazione_whatsapp' => 'Prenotazioni dal modulo (WhatsApp)', 'clic_whatsapp' => 'Messaggi WhatsApp', 'clic_telefono' => 'Telefonate dal sito'],
                          'altri_eventi' => ['clic_indicazioni' => 'Indicazioni stradali', 'clic_just_eat' => 'Clic verso Just Eat', 'clic_social' => 'Clic verso Instagram e Facebook'],
                          'note' => 'Sito nuovo online dall\'11/10/2026 (italiano + inglese), Analytics dallo stesso giorno: ottobre è un mese parziale. '
                                  . 'Analytics conta solo chi accetta i cookie. Le prenotazioni arrivano su WhatsApp, non per email. '
                                  . 'clic_whatsapp comprende anche il clic sul pulsante del modulo di prenotazione. Pubblico anche turistico (versione inglese). '
                                  . 'Scheda Google con 4,8 stelle su 90 recensioni.'],
  'supzena'           => ['nome' => 'SUP Zena', 'sito' => 'supzena.it', 'ga4' => '343925367', 'settore' => 'corsi di stand up paddle (SUP) a Genova'],
  // Percorso di selezione agenti Dojo: landing recruitingagenti → test dojotalentcheck.
  // Niente Analytics: legge il contatore senza cookie e i test dal database del test (solo totali).
  'recruiting'        => ['nome' => 'Selezione agenti Dojo', 'sito' => 'recruitingagenti.camecomunicazione.it',
                          'db_config' => getenv('HOME') . '/domains/camecomunicazione.it/public_html/dojotalentcheck/config.php',
                          'nota_contatti' => 'test completati',
                          'settore' => 'selezione di venditori con Partita IVA per la rete commerciale Dojo (terminali POS): '
                                     . 'una pagina che convince (recruitingagenti) e un test di 24 domande (dojotalentcheck). '
                                     . 'Il report è interno, per CAME: Maurizio Camerada legge ogni risultato.',
                          'note' => 'Le fonti sono i valori di ?fonte= nei link delle campagne (es. linkedin, facebook, passaparola). '
                                  . 'Conta di più quanti test completati e idonei porta una fonte, non le visite. Non nominare mai singoli candidati.'],
];
