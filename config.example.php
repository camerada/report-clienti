<?php
// Copia in config.php (non va su GitHub). Sul server: stessa cartella di report.php.

// Claude (stessa chiave di TuoSito)
define('ANTHROPIC_API_KEY', 'sk-ant-...');
define('MODELLO', 'claude-opus-5-5');

// Brevo per l'invio delle email (stessa chiave di TuoSito)
define('BREVO_API_KEY', 'xkeysib-...');
define('MITTENTE_EMAIL', 'info@camecomunicazione.it');   // deve essere un mittente verificato in Brevo
define('MITTENTE_NOME', 'CAME Comunicazione');

// Dove arrivano le anteprime da controllare (e la copia di quelli inviati ai clienti)
define('EMAIL_CAME', 'info@camecomunicazione.it');

// Telefono nel riquadro «Vuoi metterli in pratica?» in fondo al report (vuoto = solo email)
define('TELEFONO_CAME', '');

// Account di servizio Google (file JSON scaricato da Google Cloud), fuori dal sito
define('CHIAVE_GOOGLE', __DIR__ . '/chiave-google.json');

// Solo per le prove in locale: API finte al posto di quelle vere
// define('CLAUDE_API_URL', 'http://127.0.0.1:8770/v1/messages');
// define('BREVO_API_URL', 'http://127.0.0.1:8770/v3/smtp/email');

// Facoltativo, solo per ga4-settimanale.php (dati GA4 settimanali per il report social).
// Cartella chiusa al web dove scrive i JSON; se manca usa ~/domains/<sito>/public_html/_report-privato
// define('GA4_SETTIMANALE_CARTELLA', '/home/<utente>/domains/tuosito.si/public_html/_report-privato');
// Indirizzo con cui controlla da fuori che la cartella risponda 403 (predefinito: https://<sito>/_report-privato/ga4-latest.json)
// define('GA4_SETTIMANALE_URL', 'https://tuosito.si/_report-privato/ga4-latest.json');
