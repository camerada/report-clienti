<?php
// Pulsante «Invia al cliente» dei report: il programma vero è fuori dai siti, in ~/report-clienti/invia.php.
// Questo file lo copia carica.sh in camecomunicazione.it/report/invia.php (…/domains/<sito>/public_html/report/).
require dirname(__DIR__, 4) . '/report-clienti/invia.php';
