#!/bin/sh
# Dopo che il server ha scaricato il programma da GitHub (cron di Hostinger ogni 10 minuti, vedi CLAUDE.md):
# copia la pagina del pulsante «Invia al cliente» nel sito di CAME e controlla la sintassi PHP.
# config.php, chiave-google.json e uscite/ non sono su GitHub: il git reset non li tocca.
cd "$HOME/report-clienti" || exit 1
mkdir -p "$HOME/domains/camecomunicazione.it/public_html/report"
cp web/invia.php "$HOME/domains/camecomunicazione.it/public_html/report/invia.php"
for f in report.php invia.php esporta.php gtm.php ga4-settimanale.php clienti.php lib/*.php; do
  php -l "$f" >/dev/null || echo "ERRORE di sintassi in $f"
done
echo "Aggiornato a $(git log -1 --format='%h %s')"
