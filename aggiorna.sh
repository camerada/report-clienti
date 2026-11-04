#!/bin/sh
# Scarica da GitHub l'ultima versione del programma (cron di Hostinger ogni 10 minuti: sh /home/u745278502/report-clienti/aggiorna.sh).
# Il server legge il repo con la sua chiave Git (deploy key «Server Hostinger», sola lettura).
# config.php, chiave-google.json e uscite/ non sono su GitHub: restano come sono.
cd "$HOME/report-clienti" || exit 1
[ -d .git ] || git init -q
GIT_SSH_COMMAND='ssh -oStrictHostKeyChecking=accept-new -oBatchMode=yes' git fetch -q git@github.com:camerada/report-clienti main || { echo "GitHub non raggiungibile"; exit 1; }
# niente di nuovo (e la pagina del pulsante c'è): zitto
[ "$(git rev-parse -q --verify HEAD)" = "$(git rev-parse FETCH_HEAD)" ] && [ -f "$HOME/domains/camecomunicazione.it/public_html/report/invia.php" ] && exit 0
git checkout -q -f -B main FETCH_HEAD && exec sh dopo-aggiornamento.sh
