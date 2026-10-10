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

// Account di servizio Google (file JSON scaricato da Google Cloud), fuori dal sito
define('CHIAVE_GOOGLE', __DIR__ . '/chiave-google.json');

// Solo per le prove in locale: API finte al posto di quelle vere
// define('CLAUDE_API_URL', 'http://127.0.0.1:8770/v1/messages');
// define('BREVO_API_URL', 'http://127.0.0.1:8770/v3/smtp/email');
