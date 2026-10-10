<?php
/**
 * Un cliente = una voce. Per aggiungerne uno: copia il blocco di identia.
 *
 * ga4            ID numerico della proprietà Analytics (Amministrazione → Dettagli proprietà)
 * search_console proprietà di Search Console: 'sc-domain:esempio.it' o 'https://esempio.it/'
 * email          a chi va il report quando 'automatico' è true (anche più indirizzi)
 * automatico     false = il report arriva solo a CAME, da controllare e inoltrare a mano
 *                true  = il report va direttamente al cliente, con CAME in copia
 * colore         colore principale del PDF (#RRGGBB), meglio quello del sito
 * contatti       eventi di Analytics che contano come contatto => come chiamarli nel report
 * settore        aiuta Claude a dare consigli adatti
 * note           cose che Claude deve sapere (servizi, obiettivi, cose da non dire)
 */
return [
  'identia' => [
    'nome'           => 'Identia — Studio Odontoiatrico D\'Urso',
    'sito'           => 'identia.it',
    'ga4'            => '549671189',
    'search_console' => 'sc-domain:identia.it',
    'email'          => [],
    'automatico'     => false,
    'colore'         => '#3B4A75',
    'settore'        => 'studio dentistico a Genova (Piazza Cavour), un solo professionista',
    'contatti'       => [
      'clic_telefono' => 'Telefonate dal sito',
      'clic_whatsapp' => 'Messaggi WhatsApp',
      'generate_lead' => 'Richieste dal modulo',
      'clic_email'    => 'Email',
    ],
    'altri_eventi'   => [
      'clic_mappa'    => 'Aperture della mappa',
      'clic_prenota'  => 'Clic su «Prenota la prima visita»',
      'video_start'   => 'Video avviati',
    ],
    'note'           => 'Sito online da settembre 2026. Analytics attivo dal 10/10/2026 e conta solo i visitatori che accettano i cookie. '
                      . 'Blog con articoli programmati ogni settimana. Comunicazione sanitaria: niente promesse di risultati, niente prezzi.',
  ],
];
